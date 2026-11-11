<?php
/**
 * clothes.php
 * Displays all items from tblClothes — styled to match the Pastimes marketplace screenshot.
 *
 * SAVE TO: C:\xampp\htdocs\wede2\clothes.php
 *
 * Author: ClothingStore Team
 * Date:   2025
 */

// ─── No-cache headers — MUST come before any output ──────────────────────────
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) session_start();

// Session guard — send guests to login immediately
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/DBConn.php';

$conn = getDBConnection();

// Fetch filter values from GET
$filterCategory = $_GET['category'] ?? 'All';
$filterPrice    = $_GET['price']    ?? 'All';
$filterSize     = $_GET['size']     ?? 'All';
$search         = trim($_GET['search'] ?? '');

// ── Handle Add to Cart via POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $cid   = (int)$_POST['clothes_id'];
    $price = (float)$_POST['sell_price'];
    $name  = $_POST['item_name'] ?? '';
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$cid] = ['name' => $name, 'price' => $price, 'qty' => 1];
    // Return JSON so JS can show the popup without a page reload
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'cartCount' => count($_SESSION['cart'])]);
    exit;
}

// Build dynamic query with filters
$where  = [];
$params = [];
$types  = '';

if ($filterCategory !== 'All') {
    $where[]  = "Category = ?";
    $params[] = $filterCategory;
    $types   .= 's';
}
if ($filterSize !== 'All') {
    $where[]  = "Size = ?";
    $params[] = $filterSize;
    $types   .= 's';
}
if ($filterPrice === 'Under200') {
    $where[] = "SellPrice < 200";
} elseif ($filterPrice === '200-400') {
    $where[] = "SellPrice BETWEEN 200 AND 400";
} elseif ($filterPrice === 'Over400') {
    $where[] = "SellPrice > 400";
}
if ($search !== '') {
    $where[]  = "(ItemName LIKE ? OR Description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types   .= 'ss';
}

$sql = "SELECT ClothesID, ItemName, Description, Category, Size, SellPrice, StockQty, ImageFile
        FROM tblClothes"
    . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '')
    . " ORDER BY CreatedAt DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$clothesArray = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clothesArray[] = $row;
    }
}

// Fetch distinct categories and sizes for filter dropdowns
$cats  = $conn->query("SELECT DISTINCT Category FROM tblClothes ORDER BY Category");
$sizes = $conn->query("SELECT DISTINCT Size FROM tblClothes ORDER BY Size");
$conn->close();

// Count items in cart (stored in session)
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Emoji map for categories
$categoryEmoji = [
    'Jackets'     => '🧥',
    'Dresses'     => '👗',
    'Pants'       => '👖',
    'Tops'        => '👕',
    'Shoes'       => '👟',
    'Accessories' => '👜',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | Pastimes</title>
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
            --bg:          #f8f8fc;
            --card-bg:     #ffffff;
            --radius:      18px;
            --card-radius: 20px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
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
            transition: box-shadow .2s;
        }
        .search-wrap input:focus {
            box-shadow: 0 0 0 2px rgba(232,98,42,.2);
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

        /* Cart button — now links to cart.php */
        .cart-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            color: var(--text);
            text-decoration: none;
            position: relative;
            padding: .38rem .7rem;
            border-radius: 8px;
            transition: background .2s;
        }
        .cart-btn:hover { background: var(--orange-lite); }
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
            text-decoration: none;
        }

        /* ── Filters bar ─────────────────────────────────────── */
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
            margin-right: .3rem;
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
            transition: border-color .2s;
        }
        .filter-select:focus, .filter-select:hover { border-color: var(--orange); }

        /* ── Page wrapper ────────────────────────────────────── */
        .page {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* ── Empty state ─────────────────────────────────────── */
        .empty {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--muted);
        }
        .empty-icon { font-size: 3rem; margin-bottom: 1rem; display: block; }

        /* ── Product grid ────────────────────────────────────── */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.4rem;
        }

        /* ── Product card ────────────────────────────────────── */
        .product-card {
            background: var(--card-bg);
            border-radius: var(--card-radius);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: transform .22s, box-shadow .22s;
            animation: fadeUp .4s cubic-bezier(.22,.68,0,1.2) both;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px rgba(0,0,0,.10);
        }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .product-card:nth-child(1) { animation-delay: .00s; }
        .product-card:nth-child(2) { animation-delay: .07s; }
        .product-card:nth-child(3) { animation-delay: .14s; }
        .product-card:nth-child(4) { animation-delay: .21s; }
        .product-card:nth-child(5) { animation-delay: .28s; }
        .product-card:nth-child(6) { animation-delay: .35s; }

        .product-img-wrap {
            height: 185px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .product-card:nth-child(3n+1) .product-img-wrap {
            background: linear-gradient(135deg, #e8d5f5 0%, #c5d0fa 100%);
        }
        .product-card:nth-child(3n+2) .product-img-wrap {
            background: linear-gradient(135deg, #d5e8f5 0%, #c5f0fa 100%);
        }
        .product-card:nth-child(3n+3) .product-img-wrap {
            background: linear-gradient(135deg, #f5d5e8 0%, #fac5d0 100%);
        }

        .product-img-wrap img {
            max-width: 110px;
            max-height: 130px;
            object-fit: contain;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,.15));
            transition: transform .3s;
        }
        .product-card:hover .product-img-wrap img {
            transform: scale(1.08) translateY(-4px);
        }

        .product-emoji {
            font-size: 4.5rem;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,.12));
            transition: transform .3s;
        }
        .product-card:hover .product-emoji {
            transform: scale(1.1) translateY(-4px);
        }

        .stock-badge {
            position: absolute;
            top: .75rem;
            right: .75rem;
            font-size: .68rem;
            font-weight: 700;
            border-radius: 999px;
            padding: .22rem .65rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .in-stock     { background: #d1fae5; color: #065f46; }
        .out-of-stock { background: #fee2e2; color: #991b1b; }

        .product-body { padding: 1.1rem 1.2rem 1.3rem; }

        .product-name {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: .2rem;
        }
        .product-meta {
            font-size: .78rem;
            color: var(--muted);
            margin-bottom: .1rem;
        }
        .product-price {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--orange);
            margin: .55rem 0 .4rem;
        }

        .product-seller {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .76rem;
            color: var(--muted);
            margin-bottom: .9rem;
        }
        .stars { color: #ffa94d; letter-spacing: 1px; }

        .product-actions { display: flex; gap: .6rem; }

        .btn-cart {
            flex: 1;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .65rem .5rem;
            font-size: .82rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .1s;
            letter-spacing: .3px;
        }
        .btn-cart:hover:not(:disabled) {
            background: var(--orange-dark);
            transform: translateY(-1px);
        }
        .btn-cart:disabled { opacity: .45; cursor: not-allowed; }

        /* Loading state while AJAX runs */
        .btn-cart.loading {
            opacity: .7;
            cursor: wait;
        }

        .btn-msg {
            flex: 0 0 auto;
            background: var(--white);
            color: var(--orange);
            border: 1.5px solid var(--orange);
            border-radius: 10px;
            padding: .65rem 1rem;
            font-size: .82rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .1s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-msg:hover {
            background: var(--orange-lite);
            transform: translateY(-1px);
        }

        /* ── Add to Cart modal ───────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 24px;
            padding: 2.2rem 2rem;
            max-width: 340px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.18);
            animation: popIn .3s cubic-bezier(.22,.68,0,1.3) both;
        }
        @keyframes popIn {
            from { opacity:0; transform:scale(.88); }
            to   { opacity:1; transform:scale(1); }
        }

        .modal-icon  { font-size: 2.8rem; margin-bottom: .6rem; display: block; }
        .modal-title { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: .3rem; }
        .modal-name  { font-size: .85rem; color: var(--muted); margin-bottom: .8rem; }
        .modal-price {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--orange);
            margin-bottom: .3rem;
            line-height: 1;
        }
        .modal-sub { font-size: .76rem; color: var(--muted); margin-bottom: 1.4rem; }

        .modal-actions { display: flex; gap: .7rem; }

        .btn-continue {
            flex: 1;
            background: var(--white);
            color: var(--text);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: .75rem;
            font-size: .85rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, border-color .2s;
        }
        .btn-continue:hover { border-color: var(--orange); background: var(--orange-lite); }

        .btn-view-cart {
            flex: 1;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .75rem;
            font-size: .85rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
        }
        .btn-view-cart:hover { background: var(--orange-dark); }

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

        @media (max-width:640px) {
            .product-grid { grid-template-columns: 1fr 1fr; gap: .9rem; }
            nav { padding: 0 1rem; gap: .8rem; }
            .filters-bar { padding: .7rem 1rem; }
            .search-wrap { max-width: 180px; }
        }
        @media (max-width:420px) {
            .product-grid { grid-template-columns: 1fr; }
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
        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Search items..."
            autocomplete="off"
        >
        <?php foreach (['category','price','size'] as $k): ?>
            <?php if (!empty($_GET[$k])): ?>
                <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($_GET[$k]) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    </form>

    <div class="nav-right">
        <a href="index.php" class="cart-btn" style="color:var(--text);">
            🏠 Home
        </a>
        <a href="purchase_history.php" class="cart-btn">
    📜 Purchase History
</a>
        <!-- ✅ CHANGED: href now points to cart.php -->
        <a href="cart.php" class="cart-btn" id="navCartBtn">
            🛒 Cart
            <span class="cart-count" id="navCartCount" <?= $cartCount === 0 ? 'style="display:none"' : '' ?>>
                <?= $cartCount ?>
            </span>
        </a>
        <span class="nav-user">
            👤 <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>
        </span>
        <a href="logout.php" style="font-size:.82rem; color:var(--muted); text-decoration:none; font-weight:600;">
            Logout
        </a>
    </div>
</nav>

<!-- ── Filters bar ──────────────────────────────────────────────── -->
<form class="filters-bar" method="GET" action="clothes.php">
    <?php if ($search): ?>
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
    <?php endif; ?>

    <span class="filters-label">Filters:</span>

    <select class="filter-select" name="category" onchange="this.form.submit()">
        <option value="All" <?= $filterCategory==='All' ? 'selected':'' ?>>Brand: All</option>
        <?php while ($cat = $cats->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($cat['Category']) ?>"
                <?= $filterCategory===$cat['Category'] ? 'selected':'' ?>>
                <?= htmlspecialchars($cat['Category']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select class="filter-select" name="price" onchange="this.form.submit()">
        <option value="All"      <?= $filterPrice==='All'      ? 'selected':'' ?>>Price: All</option>
        <option value="Under200" <?= $filterPrice==='Under200' ? 'selected':'' ?>>Under R200</option>
        <option value="200-400"  <?= $filterPrice==='200-400'  ? 'selected':'' ?>>R200 – R400</option>
        <option value="Over400"  <?= $filterPrice==='Over400'  ? 'selected':'' ?>>Over R400</option>
    </select>

    <select class="filter-select" name="condition" onchange="this.form.submit()">
        <option>Condition: All</option>
        <option>Good</option>
        <option>Like New</option>
        <option>Very Good</option>
    </select>

    <select class="filter-select" name="size" onchange="this.form.submit()">
        <option value="All" <?= $filterSize==='All' ? 'selected':'' ?>>Size: All</option>
        <?php while ($sz = $sizes->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($sz['Size']) ?>"
                <?= $filterSize===$sz['Size'] ? 'selected':'' ?>>
                <?= htmlspecialchars($sz['Size']) ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<!-- ── Product grid ──────────────────────────────────────────────── -->
<div class="page">

    <?php if (empty($clothesArray)): ?>
        <div class="empty">
            <span class="empty-icon">🔍</span>
            <p style="font-size:1.05rem; font-weight:600; margin-bottom:.4rem;">No items found</p>
            <p style="font-size:.88rem;">Try adjusting your filters or search term.</p>
        </div>

    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($clothesArray as $item):
                $emoji   = $categoryEmoji[$item['Category']] ?? '👕';
                $inStock = (int)$item['StockQty'] > 0;
                $sellers = ['retro_john','fashion_kate','denim_king','style_pro','urban_wear'];
                $seller  = $sellers[$item['ClothesID'] % count($sellers)];
                $stars   = str_repeat('★', min(5, 3 + ($item['ClothesID'] % 3)))
                         . str_repeat('☆', max(0, 5 - (3 + ($item['ClothesID'] % 3))));
            ?>

            <div class="product-card">
                <div class="product-img-wrap">
                    <?php if (!empty($item['ImageFile']) && file_exists(__DIR__.'/images/'.$item['ImageFile'])): ?>
                        <img
                            src="images/<?= htmlspecialchars($item['ImageFile']) ?>"
                            alt="<?= htmlspecialchars($item['ItemName']) ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                        >
                        <span class="product-emoji" style="display:none;"><?= $emoji ?></span>
                    <?php else: ?>
                        <span class="product-emoji"><?= $emoji ?></span>
                    <?php endif; ?>

                    <span class="stock-badge <?= $inStock ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $inStock ? (int)$item['StockQty'].' left' : 'Sold Out' ?>
                    </span>
                </div>

                <div class="product-body">
                    <div class="product-name"><?= htmlspecialchars($item['ItemName']) ?></div>
                    <div class="product-meta">Condition: <?= htmlspecialchars($item['Category']) ?></div>
                    <div class="product-meta">Size: <?= htmlspecialchars($item['Size']) ?></div>
                    <div class="product-price">R<?= number_format((float)$item['SellPrice'], 0) ?></div>

                    <div class="product-seller">
                        <span><?= $seller ?></span>
                        <span class="stars"><?= $stars ?></span>
                    </div>

                    <div class="product-actions">
                        <!-- ✅ CHANGED: uses AJAX via addToCart() so popup shows AND item saves -->
                        <button
                            type="button"
                            class="btn-cart"
                            <?= !$inStock ? 'disabled' : '' ?>
                            data-id="<?= (int)$item['ClothesID'] ?>"
                            data-price="<?= (float)$item['SellPrice'] ?>"
                            data-name="<?= htmlspecialchars($item['ItemName'], ENT_QUOTES) ?>"
                            onclick="addToCart(this)"
                        >
                            ADD TO CART
                        </button>

                        <a
                            href="messages.php?item_id=<?= (int)$item['ClothesID'] ?>"
                            class="btn-msg"
                        >
                            MESSAGE
                        </a>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- ── Add to Cart modal ─────────────────────────────────────────── -->
<div class="modal-overlay" id="cartModal">
    <div class="modal-box">
        <span class="modal-icon">🛒</span>
        <div class="modal-title">Added to Cart!</div>
        <div class="modal-name"  id="modalItemName"></div>
        <div class="modal-price" id="modalPrice"></div>
        <div class="modal-sub">Price for this item</div>
        <div class="modal-actions">
            <button class="btn-continue" onclick="closeCartPopup()">Continue Shopping</button>
            <a class="btn-view-cart" href="cart.php">View Cart 🛒</a>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <?= date('Y') ?> Pastimes ClothingStore. All rights reserved.</p>
</footer>

<script>
    // ── Add to Cart via AJAX — saves to session AND shows popup ──
    function addToCart(btn) {
        if (btn.disabled || btn.classList.contains('loading')) return;

        const id    = btn.dataset.id;
        const price = btn.dataset.price;
        const name  = btn.dataset.name;

        btn.classList.add('loading');
        btn.textContent = '...';

        const formData = new FormData();
        formData.append('add_to_cart', '1');
        formData.append('clothes_id',  id);
        formData.append('sell_price',  price);
        formData.append('item_name',   name);

        fetch('clothes.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Update cart badge in navbar
                    const badge = document.getElementById('navCartCount');
                    badge.textContent = data.cartCount;
                    badge.style.display = 'flex';

                    // Show the popup
                    document.getElementById('modalItemName').textContent = name;
                    document.getElementById('modalPrice').textContent    = 'R' + parseFloat(price).toFixed(2);
                    document.getElementById('cartModal').classList.add('active');
                }
            })
            .catch(() => alert('Could not add item. Please try again.'))
            .finally(() => {
                btn.classList.remove('loading');
                btn.textContent = 'ADD TO CART';
            });
    }

    function closeCartPopup() {
        document.getElementById('cartModal').classList.remove('active');
    }

    // Close modal on backdrop click
    document.getElementById('cartModal').addEventListener('click', function(e) {
        if (e.target === this) closeCartPopup();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeCartPopup();
    });
</script>

</body>
</html>