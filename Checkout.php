<?php
/**
 * checkout.php
 * Delivery Details — Page 5 of the Pastimes checkout flow.
 *
 * SAVE TO: C:\xampp\htdocs\wede2\checkout.php
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/DBConn.php';
$conn = getDBConnection();

// ── Ensure orders + order_items tables exist ─────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS tblOrders (
    OrderID       INT AUTO_INCREMENT PRIMARY KEY,
    UserID        INT          NOT NULL,
    AddressType   VARCHAR(20)  NOT NULL DEFAULT 'Residential',
    StreetAddress VARCHAR(255) NOT NULL,
    Suburb        VARCHAR(120) NOT NULL,
    City          VARCHAR(120) NOT NULL,
    PostalCode    VARCHAR(10)  NOT NULL,
    PhoneNumber   VARCHAR(20)  NOT NULL,
    Subtotal      DECIMAL(10,2) NOT NULL,
    DeliveryFee   DECIMAL(10,2) NOT NULL DEFAULT 75.00,
    Total         DECIMAL(10,2) NOT NULL,
    Status        VARCHAR(40)  NOT NULL DEFAULT 'Pending',
    CreatedAt     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// tblOrderItems serves as the order-line table: one row per item per order
$conn->query("CREATE TABLE IF NOT EXISTS tblOrderItems (
    ItemID    INT AUTO_INCREMENT PRIMARY KEY,
    OrderID   INT          NOT NULL,
    ClothesID INT          NOT NULL,
    ItemName  VARCHAR(255) NOT NULL,
    Price     DECIMAL(10,2) NOT NULL,
    Qty       INT          NOT NULL DEFAULT 1,
    INDEX idx_order (OrderID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Cart summary ─────────────────────────────────────────────────────────────
$cart      = $_SESSION['cart'] ?? [];
$cartCount = count($cart);

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$deliveryFee = 75.00;
$total       = $subtotal + $deliveryFee;

// ── Handle form submission ────────────────────────────────────────────────────
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $addrType  = in_array($_POST['address_type'] ?? '', ['Residential','Work']) ? $_POST['address_type'] : 'Residential';
    $street    = trim($_POST['street']   ?? '');
    $suburb    = trim($_POST['suburb']   ?? '');
    $city      = trim($_POST['city']     ?? '');
    $postal    = trim($_POST['postal']   ?? '');
    $phone     = trim($_POST['phone']    ?? '');

    if (!$street) $errors['street'] = 'Street address is required.';
    if (!$suburb) $errors['suburb'] = 'Suburb is required.';
    if (!$city)   $errors['city']   = 'City is required.';
    if (!preg_match('/^\d{4}$/', $postal)) $errors['postal'] = 'Enter a valid 4-digit postal code.';
    if (!preg_match('/^0[0-9]{9}$/', preg_replace('/\s+/','',$phone))) {
        $errors['phone'] = 'Enter a valid 10-digit SA phone number (e.g. 0123456789).';
    }

    if (empty($errors)) {
        $userId = (int)$_SESSION['user_id'];

        // Capture the session ID *before* it gets destroyed below.
        $orderSessionId = session_id();

        $stmt = $conn->prepare(
            "INSERT INTO tblOrders (UserID,AddressType,StreetAddress,Suburb,City,PostalCode,PhoneNumber,Subtotal,DeliveryFee,Total)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('issssssddd', $userId, $addrType, $street, $suburb, $city, $postal, $phone, $subtotal, $deliveryFee, $total);
        $stmt->execute();
        $orderId = $conn->insert_id;
        $stmt->close();

        // Save order line items + decrement stock for each purchased item
        $itemStmt  = $conn->prepare(
            "INSERT INTO tblOrderItems (OrderID,ClothesID,ItemName,Price,Qty) VALUES (?,?,?,?,?)"
        );
        $stockStmt = $conn->prepare(
            "UPDATE tblClothes
             SET StockQty = CASE WHEN StockQty >= ? THEN StockQty - ? ELSE 0 END
             WHERE ClothesID = ?"
        );

        foreach ($cart as $cid => $item) {
            $clothesId = (int)$cid;

            $itemStmt->bind_param('iisdi', $orderId, $clothesId, $item['name'], $item['price'], $item['qty']);
            $itemStmt->execute();

            $qty = (int)$item['qty'];
            $stockStmt->bind_param('iii', $qty, $qty, $clothesId);
            $stockStmt->execute();
        }
        $itemStmt->close();
        $stockStmt->close();

        // Human-friendly order reference, e.g. ORD-000123
        $orderNum = 'ORD-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);

        // Clear cart
        $_SESSION['cart'] = [];
        $success   = true;
        $cartCount = 0;

        $conn->close();

        // ── Log the user out and send them back to the login page ──────────
        // with the order reference + session ID passed through for display.
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $cookieParams['path'], $cookieParams['domain'],
                $cookieParams['secure'], $cookieParams['httponly']
            );
        }
        session_destroy();

        $redirectUrl = 'login.php?order_placed=1'
            . '&order_num=' . urlencode($orderNum)
            . '&session_id=' . urlencode($orderSessionId);

        header('Location: ' . $redirectUrl);
        exit;
    }
}

$conn->close();

$userName  = htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:      #E8622A;
            --orange-dark: #c94e1e;
            --orange-lite: #fff0ea;
            --text:        #1a1a2e;
            --muted:       #888;
            --border:      #ebebf0;
            --white:       #ffffff;
            --error:       #e53e3e;
            --error-bg:    #fff5f5;
            --success:     #38a169;
            --success-bg:  #f0fff4;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #e8eaf6 0%, #f0e6f6 50%, #e6f0ff 100%);
            min-height: 100vh;
            color: var(--text);
        }

        /* ── Rainbow bar ─────────────────────────────────────── */
        .rainbow-bar {
            height: 4px;
            background: linear-gradient(90deg,
                #ff6b6b 0%,#ffa94d 15%,#ffd43b 30%,
                #69db7c 45%,#4dabf7 60%,#748ffc 75%,
                #da77f2 90%,#ff6b6b 100%);
        }

        /* ── Navbar ──────────────────────────────────────────── */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }
        .nav-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--orange);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
        }
        .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: .88rem;
        }
        .nav-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            color: var(--text);
            text-decoration: none;
            padding: .38rem .7rem;
            border-radius: 8px;
            transition: background .2s;
            font-size: .88rem;
        }
        .nav-btn:hover { background: var(--orange-lite); color: var(--orange); }
        .cart-count {
            background: var(--orange);
            color: #fff;
            font-size: .68rem;
            font-weight: 700;
            border-radius: 50%;
            width: 18px; height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-user { font-weight: 600; color: var(--text); font-size: .88rem; }

        /* ── Page ────────────────────────────────────────────── */
        .page {
            max-width: 860px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            animation: fadeUp .4s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── Progress bar ────────────────────────────────────── */
        .progress-wrap {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 1.2rem 1.6rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
        }
        .progress-steps {
            display: flex;
            align-items: center;
            gap: 0;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .3rem;
            flex: 1;
            position: relative;
        }
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 14px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border);
            z-index: 0;
        }
        .step.done:not(:last-child)::after  { background: var(--orange); }
        .step-circle {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 700;
            border: 2px solid var(--border);
            background: var(--white);
            color: var(--muted);
            z-index: 1;
            position: relative;
        }
        .step.done  .step-circle { background: var(--orange); border-color: var(--orange); color: #fff; }
        .step.active .step-circle { border-color: var(--orange); color: var(--orange); font-weight: 800; }
        .step-label { font-size: .68rem; font-weight: 600; color: var(--muted); text-align: center; white-space: nowrap; }
        .step.active .step-label { color: var(--orange); }
        .step.done   .step-label { color: var(--text); }

        /* ── Two-column layout ───────────────────────────────── */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.4rem;
            align-items: start;
        }

        /* ── Card shared ─────────────────────────────────────── */
        .card {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
            padding: 2rem;
        }

        /* ── Form ────────────────────────────────────────────── */
        .card-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* Address type radios */
        .addr-type {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.6rem;
        }
        .addr-radio {
            display: flex;
            align-items: center;
            gap: .5rem;
            cursor: pointer;
            font-size: .9rem;
            font-weight: 600;
        }
        .addr-radio input[type="radio"] {
            appearance: none;
            width: 18px; height: 18px;
            border: 2px solid var(--border);
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            transition: border-color .2s;
            flex-shrink: 0;
        }
        .addr-radio input[type="radio"]:checked {
            border-color: var(--orange);
        }
        .addr-radio input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            inset: 3px;
            background: var(--orange);
            border-radius: 50%;
        }

        /* Form groups */
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group:last-of-type { margin-bottom: 0; }

        .form-label {
            display: block;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text);
            margin-bottom: .5rem;
        }

        .form-input {
            width: 100%;
            background: #f5f7ff;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .9rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(232,98,42,.1);
            background: var(--white);
        }
        .form-input::placeholder { color: #bbb; }
        .form-input.error-field  { border-color: var(--error); background: var(--error-bg); }

        .field-hint {
            font-size: .73rem;
            color: var(--muted);
            margin-top: .35rem;
            font-style: italic;
        }
        .field-error {
            font-size: .73rem;
            color: var(--error);
            margin-top: .35rem;
            font-weight: 600;
        }

        /* Two column row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* ── Order summary card ──────────────────────────────── */
        .summary-title {
            font-size: .95rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 1rem;
            padding-bottom: .7rem;
            border-bottom: 1px solid var(--border);
        }

        .order-items { margin-bottom: 1rem; }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .45rem 0;
            border-bottom: 1px solid #f5f5fa;
            font-size: .84rem;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item .item-name { font-weight: 600; flex: 1; }
        .order-item .item-qty  { color: var(--muted); margin: 0 .5rem; font-size: .78rem; }
        .order-item .item-price { font-weight: 700; color: var(--orange); white-space: nowrap; }

        .summary-totals { border-top: 1px solid var(--border); padding-top: .8rem; }
        .sum-row {
            display: flex;
            justify-content: space-between;
            font-size: .85rem;
            color: var(--muted);
            padding: .28rem 0;
        }
        .sum-row.total {
            font-size: 1rem;
            font-weight: 800;
            color: var(--orange);
            padding-top: .6rem;
            margin-top: .3rem;
            border-top: 2px solid var(--border);
        }
        .sum-row.total span:first-child { color: var(--orange); }

        /* ── Place order button ──────────────────────────────── */
        .btn-place {
            width: 100%;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: .92rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            margin-top: 1.2rem;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(232,98,42,.3);
        }
        .btn-place:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232,98,42,.4);
        }

        /* ── Alert banners ───────────────────────────────────── */
        .alert {
            border-radius: 12px;
            padding: .9rem 1.2rem;
            margin-bottom: 1.2rem;
            font-size: .87rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .alert-error   { background: var(--error-bg);   color: var(--error);   border: 1.5px solid #feb2b2; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1.5px solid #9ae6b4; }

        /* ── Footer ──────────────────────────────────────────── */
        footer {
            text-align: center;
            padding: 1.5rem;
            font-size: .8rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
            background: #fff;
            margin-top: 2rem;
        }

        @media (max-width: 700px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .form-row      { grid-template-columns: 1fr; }
            .page          { padding: 0 1rem; }
            nav            { padding: 0 1rem; }
            .progress-steps .step-label { display: none; }
        }
    </style>
</head>
<body>

<div class="rainbow-bar"></div>

<!-- ── Navbar ───────────────────────────────────────────────────── -->
<nav>
    <a class="nav-brand" href="index.php">Pastimes</a>
    <div class="nav-right">
        <a href="index.php"   class="nav-btn">🏠 Home</a>
        <a href="clothes.php" class="nav-btn">👗 Shop</a>
        <a href="cart.php"    class="nav-btn">
            🛒 Cart
            <?php if ($cartCount > 0): ?>
                <span class="cart-count"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <span class="nav-user">👤 <?= $userName ?></span>
        <a href="logout.php" style="font-size:.82rem;color:var(--muted);text-decoration:none;font-weight:600;">Logout</a>
    </div>
</nav>

<!-- ── Page ─────────────────────────────────────────────────────── -->
<div class="page">

    <!-- Progress steps -->
    <div class="progress-wrap">
        <div class="progress-steps">
            <div class="step done">
                <div class="step-circle">✓</div>
                <span class="step-label">Browse</span>
            </div>
            <div class="step done">
                <div class="step-circle">✓</div>
                <span class="step-label">Cart</span>
            </div>
            <div class="step done">
                <div class="step-circle">✓</div>
                <span class="step-label">Review</span>
            </div>
            <div class="step done">
                <div class="step-circle">✓</div>
                <span class="step-label">Payment</span>
            </div>
            <div class="step active">
                <div class="step-circle">5</div>
                <span class="step-label">Delivery</span>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">⚠️ Please fix the errors below before placing your order.</div>
    <?php endif; ?>

    <div class="checkout-grid">

        <!-- ── LEFT: Delivery form ───────────────────────────── -->
        <div class="card">
            <div class="card-title">📦 Delivery Details</div>

            <form method="POST" action="checkout.php" id="checkoutForm">

                <!-- Address type -->
                <div class="form-group">
                    <span class="form-label">Address Type</span>
                    <div class="addr-type">
                        <label class="addr-radio">
                            <input type="radio" name="address_type" value="Residential"
                                <?= (($_POST['address_type'] ?? 'Residential') === 'Residential') ? 'checked' : '' ?>>
                            Residential
                        </label>
                        <label class="addr-radio">
                            <input type="radio" name="address_type" value="Work"
                                <?= (($_POST['address_type'] ?? '') === 'Work') ? 'checked' : '' ?>>
                            Work Address
                        </label>
                    </div>
                </div>

                <!-- Street address -->
                <div class="form-group">
                    <label class="form-label" for="street">Street Address</label>
                    <input
                        type="text"
                        id="street"
                        name="street"
                        class="form-input <?= isset($errors['street']) ? 'error-field' : '' ?>"
                        placeholder="123 Main Street"
                        value="<?= htmlspecialchars($_POST['street'] ?? '') ?>"
                        autocomplete="street-address"
                    >
                    <?php if (isset($errors['street'])): ?>
                        <div class="field-error">⚠ <?= $errors['street'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Suburb -->
                <div class="form-group">
                    <label class="form-label" for="suburb">Suburb</label>
                    <input
                        type="text"
                        id="suburb"
                        name="suburb"
                        class="form-input <?= isset($errors['suburb']) ? 'error-field' : '' ?>"
                        placeholder="e.g., Sandton"
                        value="<?= htmlspecialchars($_POST['suburb'] ?? '') ?>"
                    >
                    <?php if (isset($errors['suburb'])): ?>
                        <div class="field-error">⚠ <?= $errors['suburb'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- City + Postal code (side by side) -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="city">City</label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            class="form-input <?= isset($errors['city']) ? 'error-field' : '' ?>"
                            placeholder="e.g., Johannesburg"
                            value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
                        >
                        <?php if (isset($errors['city'])): ?>
                            <div class="field-error">⚠ <?= $errors['city'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="postal">Postal Code</label>
                        <input
                            type="text"
                            id="postal"
                            name="postal"
                            class="form-input <?= isset($errors['postal']) ? 'error-field' : '' ?>"
                            placeholder="e.g., 2196"
                            value="<?= htmlspecialchars($_POST['postal'] ?? '') ?>"
                            maxlength="4"
                        >
                        <?php if (isset($errors['postal'])): ?>
                            <div class="field-error">⚠ <?= $errors['postal'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Phone number -->
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-input <?= isset($errors['phone']) ? 'error-field' : '' ?>"
                        placeholder="e.g., 0123456789"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                        maxlength="10"
                    >
                    <?php if (isset($errors['phone'])): ?>
                        <div class="field-error">⚠ <?= $errors['phone'] ?></div>
                    <?php else: ?>
                        <div class="field-hint">For courier communication</div>
                    <?php endif; ?>
                </div>

                <input type="hidden" name="place_order" value="1">
                <button type="submit" class="btn-place">🛍 Place Order</button>

            </form>
        </div>

        <!-- ── RIGHT: Order summary ──────────────────────────── -->
        <div class="card">
            <div class="summary-title">🧾 Order Summary</div>

            <div class="order-items">
                <?php foreach ($cart as $cid => $item): ?>
                <div class="order-item">
                    <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                    <span class="item-qty">×<?= (int)$item['qty'] ?></span>
                    <span class="item-price">R<?= number_format($item['price'] * $item['qty'], 0) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-totals">
                <div class="sum-row">
                    <span>Subtotal</span>
                    <span>R<?= number_format($subtotal, 0) ?></span>
                </div>
                <div class="sum-row">
                    <span>Delivery Fee</span>
                    <span>R<?= number_format($deliveryFee, 0) ?></span>
                </div>
                <div class="sum-row total">
                    <span>Total</span>
                    <span>R<?= number_format($total, 0) ?></span>
                </div>
            </div>
        </div>

    </div><!-- end grid -->
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Pastimes ClothingStore. All rights reserved.</p>
</footer>

<script>
    // Only allow digits in postal code
    document.getElementById('postal').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g,'').slice(0,4);
    });

    // Only allow digits in phone
    document.getElementById('phone').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g,'').slice(0,10);
    });
</script>

</body>
</html>