<?php
/**
 * cart.php
 * Shopping cart page for Pastimes marketplace.
 * SAVE TO: C:\xampp\htdocs\wede2\cart.php
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) session_start();

// Session guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Handle POST actions ──────────────────────────────────────────────────────

// Update quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $cid => $qty) {
            $qty = (int)$qty;
            if ($qty < 1) {
                unset($_SESSION['cart'][$cid]);
            } else {
                if (isset($_SESSION['cart'][$cid])) {
                    $_SESSION['cart'][$cid]['qty'] = $qty;
                }
            }
        }
    }
    header('Location: cart.php');
    exit;
}

// Remove single item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cid = (int)$_POST['clothes_id'];
    unset($_SESSION['cart'][$cid]);
    header('Location: cart.php');
    exit;
}

// Clear entire cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

// ── Cart data ────────────────────────────────────────────────────────────────
$cart      = $_SESSION['cart'] ?? [];
$cartCount = count($cart);

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$deliveryFee = ($subtotal > 0) ? 75.00 : 0.00;
$total       = $subtotal + $deliveryFee;

// Username first word
$userName = htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:       #E8622A;
            --orange-dark:  #c94e1e;
            --orange-lite:  #fff0ea;
            --text:         #1a1a2e;
            --muted:        #888;
            --border:       #ebebf0;
            --white:        #ffffff;
            --bg:           #f0f2f8;
            --card-bg:      #ffffff;
            --radius:       18px;
            --card-radius:  20px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #e8eaf6 0%, #f0e6f6 50%, #e6f0ff 100%);
            min-height: 100vh;
            color: var(--text);
        }

        /* ── Rainbow top bar ─────────────────────────────────── */
        .rainbow-bar {
            height: 4px;
            background: linear-gradient(90deg,
                #ff6b6b 0%, #ffa94d 15%, #ffd43b 30%,
                #69db7c 45%, #4dabf7 60%, #748ffc 75%,
                #da77f2 90%, #ff6b6b 100%);
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
            flex-shrink: 0;
        }

        .search-wrap {
            flex: 1;
            max-width: 340px;
            position: relative;
        }
        .search-wrap input {
            width: 100%;
            background: #f2f2f8;
            border: none;
            border-radius: 999px;
            padding: .55rem 1rem .55rem 2.4rem;
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            outline: none;
        }
        .search-wrap .search-icon {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .9rem;
        }
        .search-wrap input::placeholder { color: #bbb; }

        .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            font-size: .88rem;
        }

        .cart-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 700;
            color: var(--orange);
            text-decoration: none;
            position: relative;
            padding: .38rem .7rem;
            border-radius: 8px;
            background: var(--orange-lite);
        }
        .cart-count {
            background: var(--orange);
            color: #fff;
            font-size: .68rem;
            font-weight: 700;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            color: var(--text);
        }

        /* ── Filters bar (matches clothes.php) ───────────────── */
        .filters-bar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: .85rem 2rem;
            display: flex;
            align-items: center;
            gap: .7rem;
            flex-wrap: wrap;
        }
        .filters-label {
            font-size: .78rem;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .filter-select {
            appearance: none;
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 999px;
            padding: .42rem 2rem .42rem .9rem;
            font-size: .83rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            cursor: pointer;
            outline: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .75rem center;
        }

        /* ── Page wrapper ────────────────────────────────────── */
        .page {
            max-width: 820px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            animation: fadeUp .4s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 1.5rem;
        }

        /* ── Cart table card ─────────────────────────────────── */
        .cart-card {
            background: var(--white);
            border-radius: var(--card-radius);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table thead tr {
            background: var(--orange);
            color: #fff;
        }

        .cart-table thead th {
            padding: .85rem 1.2rem;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-align: left;
        }
        .cart-table thead th:last-child { text-align: right; }
        .cart-table thead th.center    { text-align: center; }

        .cart-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        .cart-table tbody tr:last-child { border-bottom: none; }
        .cart-table tbody tr:hover { background: #fafafa; }

        .cart-table td {
            padding: 1rem 1.2rem;
            font-size: .9rem;
            vertical-align: middle;
        }

        .item-name {
            font-weight: 600;
            color: var(--text);
        }

        .item-price {
            color: var(--muted);
        }

        .qty-select {
            appearance: none;
            background: #f5f5fa;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: .32rem 1.8rem .32rem .6rem;
            font-size: .85rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            cursor: pointer;
            outline: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .55rem center;
            transition: border-color .2s;
        }
        .qty-select:focus, .qty-select:hover { border-color: var(--orange); }

        /* Remove button */
        .btn-remove {
            background: none;
            border: none;
            color: #e57373;
            font-size: 1.1rem;
            cursor: pointer;
            padding: .25rem .4rem;
            border-radius: 6px;
            transition: background .15s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-remove:hover { background: #fee2e2; }

        .subtotal-cell {
            font-weight: 700;
            color: var(--orange);
            text-align: right;
            font-size: .95rem;
        }

        .qty-cell { text-align: center; }

        /* ── Empty cart state ────────────────────────────────── */
        .empty-cart {
            text-align: center;
            padding: 3.5rem 2rem;
            color: var(--muted);
        }
        .empty-cart .icon { font-size: 3.5rem; display: block; margin-bottom: 1rem; }
        .empty-cart p { font-size: 1rem; font-weight: 600; margin-bottom: .4rem; color: var(--text); }
        .empty-cart small { font-size: .85rem; }

        /* ── Summary box ─────────────────────────────────────── */
        .summary-box {
            background: var(--white);
            border-radius: var(--card-radius);
            border: 1px solid var(--border);
            padding: 1.4rem 1.6rem;
            max-width: 340px;
            margin-left: auto;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
            margin-bottom: 1.2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .88rem;
            color: var(--muted);
            padding: .32rem 0;
        }

        .summary-row.total {
            border-top: 2px solid var(--border);
            margin-top: .55rem;
            padding-top: .7rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--orange);
        }

        .summary-row.total .label { color: var(--orange); }

        /* ── Action buttons ──────────────────────────────────── */
        .actions {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            max-width: 820px;
        }

        .btn-checkout {
            width: 100%;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: .92rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .btn-checkout:hover:not(:disabled) {
            background: var(--orange-dark);
            transform: translateY(-1px);
        }
        .btn-checkout:disabled { opacity: .45; cursor: not-allowed; }

        .btn-continue {
            width: 100%;
            background: var(--white);
            color: var(--text);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: .9rem;
            font-size: .88rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: block;
            transition: border-color .2s, background .2s;
        }
        .btn-continue:hover { border-color: var(--orange); background: var(--orange-lite); }

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

        @media (max-width: 640px) {
            .page { padding: 0 1rem; margin: 1.5rem auto; }
            .cart-table thead th,
            .cart-table td { padding: .75rem .8rem; }
            nav { padding: 0 1rem; }
            .filters-bar { padding: .7rem 1rem; }
        }
    </style>
</head>
<body>

<div class="rainbow-bar"></div>

<!-- ── Navbar ───────────────────────────────────────────────────── -->
<nav>
    <a class="nav-brand" href="index.php">Pastimes</a>

    <form class="search-wrap" method="GET" action="clothes.php">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search items..." autocomplete="off">
    </form>

    <div class="nav-right">
        <a href="index.php" class="cart-btn">
            🏠 Home
        </a>
        <a href="cart.php" class="cart-btn">
            🛒 Cart
            <?php if ($cartCount > 0): ?>
                <span class="cart-count"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <span class="nav-user">👤 <?= $userName ?></span>
        <a href="logout.php" style="font-size:.82rem; color:var(--muted); text-decoration:none; font-weight:600;">Logout</a>
    </div>
</nav>

<!-- ── Filters bar (decorative, matches clothes.php) ─────────────── -->
<div class="filters-bar">
    <span class="filters-label">Filters:</span>
    <select class="filter-select" disabled><option>Brand: All</option></select>
    <select class="filter-select" disabled><option>Price: All</option></select>
    <select class="filter-select" disabled><option>Condition: All</option></select>
    <select class="filter-select" disabled><option>Size: All</option></select>
</div>

<!-- ── Main content ──────────────────────────────────────────────── -->
<div class="page">
    <div class="page-title">Shopping Cart</div>

    <!-- Cart table -->
    <form method="POST" action="cart.php">
        <div class="cart-card">
            <?php if (empty($cart)): ?>
                <div class="empty-cart">
                    <span class="icon">🛒</span>
                    <p>Your cart is empty</p>
                    <small>Browse items and add them to your cart!</small>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th class="center">QTY</th>
                            <th class="center">Remove</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $cid => $item):
                            $lineTotal = $item['price'] * $item['qty'];
                        ?>
                        <tr>
                            <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="item-price">R<?= number_format($item['price'], 0) ?></td>
                            <td class="qty-cell">
                                <select class="qty-select" name="qty[<?= (int)$cid ?>]" onchange="this.form.submit()">
                                    <?php for ($q = 1; $q <= 10; $q++): ?>
                                        <option value="<?= $q ?>" <?= $item['qty'] == $q ? 'selected' : '' ?>><?= $q ?></option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                            <td class="qty-cell">
                                <button
                                    type="submit"
                                    name="remove_item"
                                    value="1"
                                    class="btn-remove"
                                    onclick="document.getElementById('remove_id_<?= (int)$cid ?>').value='<?= (int)$cid ?>'"
                                    title="Remove item"
                                >🗑</button>
                                <input type="hidden" id="remove_id_<?= (int)$cid ?>" name="clothes_id" value="">
                            </td>
                            <td class="subtotal-cell">R<?= number_format($lineTotal, 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <input type="hidden" name="update_cart" value="1">
    </form>

    <!-- Summary -->
    <?php if (!empty($cart)): ?>
    <div class="summary-box">
        <div class="summary-row">
            <span>Subtotal:</span>
            <span>R<?= number_format($subtotal, 0) ?></span>
        </div>
        <div class="summary-row">
            <span>Delivery Fee:</span>
            <span>R<?= number_format($deliveryFee, 0) ?></span>
        </div>
        <div class="summary-row total">
            <span class="label">Total:</span>
            <span>R<?= number_format($total, 0) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action buttons -->
    <div class="actions">
        <button
            class="btn-checkout"
            <?= empty($cart) ? 'disabled' : '' ?>
            onclick="window.location.href='checkout.php'"
        >
            Proceed to Checkout
        </button>
        <a href="clothes.php" class="btn-continue">← Continue Shopping</a>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Pastimes ClothingStore. All rights reserved.</p>
</footer>

</body>
</html>