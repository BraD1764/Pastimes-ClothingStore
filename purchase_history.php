<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . '/includes/DBConn.php';

$conn = getDBConnection();

$userID = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// ── Always fetch all users for the picker dropdown ───────────────────────────
$allUsersResult = $conn->query("SELECT UserID, FirstName, LastName, Email FROM tblUser ORDER BY FirstName, LastName");
$allUsers = [];
while ($u = $allUsersResult->fetch_assoc()) $allUsers[] = $u;

// ── If no user selected, show picker page and stop ───────────────────────────
if ($userID === 0) {
    ?><!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Purchase History | Pastimes Admin</title>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
            :root{--orange:#E8622A;--orange-dark:#c94e1e;--orange-lite:#fff0ea;--text:#1a1a2e;--muted:#888;--border:#ebebf0;--white:#fff;--bg:#f4f6fb}
            body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
            .rainbow-bar{height:4px;background:linear-gradient(90deg,#ff6b6b 0%,#ffa94d 15%,#ffd43b 30%,#69db7c 45%,#4dabf7 60%,#748ffc 75%,#da77f2 90%,#ff6b6b 100%)}
            nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 2rem;height:60px;display:flex;align-items:center;gap:1.5rem}
            .nav-brand{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:900;color:var(--orange);letter-spacing:2px;text-transform:uppercase;text-decoration:none}
            .page{max-width:600px;margin:4rem auto;padding:0 1.5rem}
            .card{background:var(--white);border:1px solid var(--border);border-radius:20px;padding:2.5rem;box-shadow:0 4px 24px rgba(0,0,0,.06);text-align:center}
            .card-icon{font-size:3rem;margin-bottom:1rem}
            .card-title{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:900;color:var(--text);margin-bottom:.4rem}
            .card-sub{font-size:.88rem;color:var(--muted);margin-bottom:2rem}
            .user-list{display:flex;flex-direction:column;gap:.7rem;margin-bottom:1.5rem}
            .user-btn{display:flex;align-items:center;gap:1rem;background:#f5f7ff;border:1.5px solid var(--border);border-radius:12px;padding:.85rem 1.2rem;text-decoration:none;color:var(--text);transition:all .2s;text-align:left}
            .user-btn:hover{border-color:var(--orange);background:var(--orange-lite);transform:translateX(4px)}
            .user-avatar{width:38px;height:38px;border-radius:50%;background:var(--orange);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;flex-shrink:0}
            .user-name{font-weight:700;font-size:.9rem}
            .user-email{font-size:.75rem;color:var(--muted)}
            .arrow{margin-left:auto;color:var(--orange);font-size:1.1rem}
        </style>
    </head>
    <body>
    <div class="rainbow-bar"></div>
    <nav><a class="nav-brand" href="admin_dashboard.php">Pastimes</a></nav>
    <div class="page">
        <div class="card">
            <div class="card-icon">📜</div>
            <div class="card-title">Purchase History</div>
            <div class="card-sub">Select a customer to view their full order history and totals</div>
            <div class="user-list">
                <?php foreach ($allUsers as $u):
                    $initials = strtoupper(substr($u['FirstName'],0,1) . substr($u['LastName'],0,1));
                ?>
                <a class="user-btn" href="purchase_history.php?user_id=<?= $u['UserID'] ?>">
                    <div class="user-avatar"><?= $initials ?></div>
                    <div>
                        <div class="user-name"><?= htmlspecialchars($u['FirstName'] . ' ' . $u['LastName']) ?></div>
                        <div class="user-email"><?= htmlspecialchars($u['Email']) ?></div>
                    </div>
                    <span class="arrow">→</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    </body></html>
    <?php
    $conn->close();
    exit;
}

// ── Fetch selected user details ───────────────────────────────────────────────
$userStmt = $conn->prepare("
    SELECT FirstName, LastName, Email, Phone, Address, Status, RegisteredAt
    FROM tblUser WHERE UserID = ?
");
$userStmt->bind_param("i", $userID);
$userStmt->execute();
$userInfo = $userStmt->get_result()->fetch_assoc();

if (!$userInfo) {
    header("Location: purchase_history.php");
    exit;
}

// ── Try tblOrders + tblOrderItems first, fall back to tblAorder ──────────────
$orders = [];

$checkTable = $conn->query("SHOW TABLES LIKE 'tblOrders'");
$useNewTables = ($checkTable && $checkTable->num_rows > 0);

if ($useNewTables) {
    $stmt = $conn->prepare("
        SELECT
            o.OrderID, o.CreatedAt AS OrderDate, o.Status AS OrderStatus,
            o.StreetAddress, o.Suburb, o.City, o.PostalCode, o.AddressType,
            o.Subtotal, o.DeliveryFee, o.Total,
            oi.ItemID, oi.ItemName, oi.Qty, oi.Price,
            (oi.Qty * oi.Price) AS LineTotal
        FROM tblOrders o
        INNER JOIN tblOrderItems oi ON oi.OrderID = o.OrderID
        WHERE o.UserID = ?
        ORDER BY o.CreatedAt DESC, o.OrderID DESC, oi.ItemID ASC
    ");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $oid = $row['OrderID'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'OrderID'       => $oid,
                'OrderDate'     => $row['OrderDate'],
                'OrderStatus'   => $row['OrderStatus'],
                'StreetAddress' => $row['StreetAddress'] . ', ' . $row['Suburb'] . ', ' . $row['City'] . ' ' . $row['PostalCode'],
                'AddressType'   => $row['AddressType'],
                'Subtotal'      => $row['Subtotal'],
                'DeliveryFee'   => $row['DeliveryFee'],
                'Total'         => $row['Total'],
                'items'         => [],
            ];
        }
        $orders[$oid]['items'][] = [
            'ItemName'  => $row['ItemName'],
            'Qty'       => $row['Qty'],
            'Price'     => $row['Price'],
            'LineTotal' => $row['LineTotal'],
        ];
    }
    $stmt->close();
} else {
    // ── Fallback: use tblAorder + tblClothes (seed data) ─────────────────────
    $stmt = $conn->prepare("
        SELECT
            a.OrderID, a.OrderDate, a.OrderStatus,
            a.Quantity AS Qty, a.TotalPrice AS Total,
            c.ItemName, c.SellPrice AS Price,
            (a.Quantity * c.SellPrice) AS LineTotal
        FROM tblAorder a
        INNER JOIN tblClothes c ON c.ClothesID = a.ClothesID
        WHERE a.UserID = ?
        ORDER BY a.OrderDate DESC, a.OrderID DESC
    ");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $oid = $row['OrderID'];
        if (!isset($orders[$oid])) {
            $orders[$oid] = [
                'OrderID'       => $oid,
                'OrderDate'     => $row['OrderDate'],
                'OrderStatus'   => $row['OrderStatus'],
                'StreetAddress' => $userInfo['Address'] ?? '—',
                'AddressType'   => 'Residential',
                'Subtotal'      => $row['Total'],
                'DeliveryFee'   => 0,
                'Total'         => $row['Total'],
                'items'         => [],
            ];
        }
        $orders[$oid]['items'][] = [
            'ItemName'  => $row['ItemName'],
            'Qty'       => $row['Qty'],
            'Price'     => $row['Price'],
            'LineTotal' => $row['LineTotal'],
        ];
    }
    $stmt->close();
}

// Grand total across all orders
$grandTotal      = array_sum(array_column($orders, 'Total'));
$totalItemsCount = array_sum(array_map(fn($o) => array_sum(array_column($o['items'], 'Qty')), $orders));

$userStmt->close();
$conn->close();

// Status badge colours
$statusColors = [
    'Pending'   => ['bg' => '#fff3cd', 'text' => '#856404', 'border' => '#ffc107'],
    'confirmed' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'border' => '#17a2b8'],
    'shipped'   => ['bg' => '#cce5ff', 'text' => '#004085', 'border' => '#4dabf7'],
    'delivered' => ['bg' => '#d4edda', 'text' => '#155724', 'border' => '#28a745'],
    'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24', 'border' => '#dc3545'],
];

function statusBadge(string $status, array $map): string {
    $key   = strtolower($status);
    // match case-insensitively
    $found = null;
    foreach ($map as $k => $v) { if (strtolower($k) === $key) { $found = $v; break; } }
    $c = $found ?? ['bg' => '#e9ecef', 'text' => '#495057', 'border' => '#adb5bd'];
    $label = ucfirst($status);
    return "<span style=\"display:inline-block;padding:.25rem .65rem;border-radius:20px;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;background:{$c['bg']};color:{$c['text']};border:1px solid {$c['border']};\">{$label}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase History — <?= htmlspecialchars($userInfo['FirstName'] . ' ' . $userInfo['LastName']) ?></title>
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
            --bg:          #f4f6fb;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .rainbow-bar {
            height: 4px;
            background: linear-gradient(90deg,
                #ff6b6b 0%,#ffa94d 15%,#ffd43b 30%,
                #69db7c 45%,#4dabf7 60%,#748ffc 75%,
                #da77f2 90%,#ff6b6b 100%);
        }

        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 1.5rem;
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
        .nav-back {
            font-size: .85rem;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            padding: .35rem .8rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: all .2s;
        }
        .nav-back:hover { color: var(--orange); border-color: var(--orange); background: var(--orange-lite); }
        .nav-print {
            margin-left: auto;
            font-size: .85rem;
            font-weight: 700;
            color: var(--white);
            background: var(--orange);
            border: none;
            border-radius: 8px;
            padding: .4rem 1rem;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: background .2s;
        }
        .nav-print:hover { background: var(--orange-dark); }

        .page {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem 4rem;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            font-weight: 900;
            color: var(--text);
            line-height: 1.2;
        }
        .page-title span { color: var(--orange); }
        .page-sub { font-size: .83rem; color: var(--muted); margin-top: .3rem; }

        .customer-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.6rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.04);
        }
        .customer-field label {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            display: block;
            margin-bottom: .2rem;
        }
        .customer-field span {
            font-size: .9rem;
            font-weight: 600;
            color: var(--text);
        }
        .status-badge-active   { color: #155724; }
        .status-badge-pending  { color: #856404; }
        .status-badge-suspended { color: #721c24; }

        .summary-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.6rem;
        }
        .strip-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem 1.2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }
        .strip-card .strip-label {
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: .3rem;
        }
        .strip-card .strip-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text);
        }
        .strip-card.highlight .strip-value { color: var(--orange); }

        .empty-state {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            color: var(--muted);
            font-size: .95rem;
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: .8rem; }

        .order-block {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 1.2rem;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,.04);
        }
        .order-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.4rem;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            background: #fafbff;
        }
        .order-id {
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--orange);
        }
        .order-date {
            font-size: .8rem;
            color: var(--muted);
            font-weight: 500;
        }
        .order-addr {
            font-size: .78rem;
            color: var(--muted);
            margin-left: auto;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            background: #f5f7ff;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--muted);
            padding: .65rem 1.4rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .items-table th:last-child,
        .items-table td:last-child { text-align: right; }
        .items-table td {
            padding: .7rem 1.4rem;
            font-size: .86rem;
            border-bottom: 1px solid #f5f5fa;
            color: var(--text);
        }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table .td-price { color: var(--muted); }
        .items-table .td-total { font-weight: 700; color: var(--text); }

        .order-totals {
            border-top: 1px solid var(--border);
            padding: .8rem 1.4rem;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .3rem;
            background: #fafbff;
        }
        .tot-row {
            font-size: .82rem;
            color: var(--muted);
            display: flex;
            gap: 1.5rem;
        }
        .tot-row span:last-child { min-width: 80px; text-align: right; }
        .tot-row.grand {
            font-size: .95rem;
            font-weight: 800;
            color: var(--orange);
            padding-top: .35rem;
            border-top: 1px solid var(--border);
            margin-top: .2rem;
        }

        .grand-total-footer {
            margin-top: 2rem;
            background: var(--orange);
            border-radius: 16px;
            padding: 1.4rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--white);
            box-shadow: 0 6px 24px rgba(232,98,42,.3);
        }
        .gt-label {
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: .85;
        }
        .gt-sub { font-size: .82rem; opacity: .7; margin-top: .2rem; }
        .gt-amount {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -1px;
        }

        @media print {
            .rainbow-bar, nav, .nav-print { display: none !important; }
            body { background: #fff; }
            .page { max-width: 100%; padding: 0; }
            .order-block { break-inside: avoid; }
            .grand-total-footer { box-shadow: none; border: 2px solid var(--orange); color: var(--orange); background: #fff !important; }
            .gt-amount { color: var(--orange); }
        }

        @media (max-width: 600px) {
            .summary-strip { grid-template-columns: 1fr; }
            .order-header { flex-direction: column; align-items: flex-start; gap: .4rem; }
            .order-addr { margin-left: 0; }
            .items-table th, .items-table td { padding: .6rem .8rem; }
            .grand-total-footer { flex-direction: column; gap: .8rem; text-align: center; }
        }
    </style>
</head>
<body>

<div class="rainbow-bar"></div>

<nav>
    <a class="nav-brand" href="admin_dashboard.php">Pastimes</a>
    <a class="nav-back" href="manage_users.php">← Back to Users</a>
    <button class="nav-print" onclick="window.print()">🖨 Print Report</button>
</nav>

<div class="page">

    <div class="page-heading">
        <div>
            <div class="page-title">📜 Purchase History<br><span><?= htmlspecialchars($userInfo['FirstName'] . ' ' . $userInfo['LastName']) ?></span></div>
            <div class="page-sub">Order history report · Generated <?= date('d M Y, H:i') ?></div>
        </div>
    </div>

    <div class="customer-card">
        <div class="customer-field">
            <label>Full Name</label>
            <span><?= htmlspecialchars($userInfo['FirstName'] . ' ' . $userInfo['LastName']) ?></span>
        </div>
        <div class="customer-field">
            <label>Email</label>
            <span><?= htmlspecialchars($userInfo['Email']) ?></span>
        </div>
        <div class="customer-field">
            <label>Account Status</label>
            <span class="status-badge-<?= htmlspecialchars($userInfo['Status']) ?>"><?= ucfirst(htmlspecialchars($userInfo['Status'])) ?></span>
        </div>
        <div class="customer-field">
            <label>Member Since</label>
            <span><?= date('d M Y', strtotime($userInfo['RegisteredAt'])) ?></span>
        </div>
    </div>

    <div class="summary-strip">
        <div class="strip-card">
            <div class="strip-label">Total Orders</div>
            <div class="strip-value"><?= count($orders) ?></div>
        </div>
        <div class="strip-card">
            <div class="strip-label">Items Purchased</div>
            <div class="strip-value"><?= $totalItemsCount ?></div>
        </div>
        <div class="strip-card highlight">
            <div class="strip-label">Grand Total Spent</div>
            <div class="strip-value">R<?= number_format($grandTotal, 2) ?></div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <strong>No orders found</strong><br>
            This customer has not placed any orders yet.
        </div>
    <?php else: ?>

        <?php foreach ($orders as $order): ?>
        <div class="order-block">

            <div class="order-header">
                <span class="order-id">ORD-<?= str_pad($order['OrderID'], 6, '0', STR_PAD_LEFT) ?></span>
                <?= statusBadge($order['OrderStatus'], $statusColors) ?>
                <span class="order-date">📅 <?= date('d M Y, H:i', strtotime($order['OrderDate'])) ?></span>
                <span class="order-addr">
                    📍 <?= htmlspecialchars($order['StreetAddress']) ?>
                    <em style="color:var(--muted);font-size:.72rem;">(<?= htmlspecialchars($order['AddressType']) ?>)</em>
                </span>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $i => $item): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:.78rem;"><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($item['ItemName']) ?></strong></td>
                        <td class="td-price">× <?= (int)$item['Qty'] ?></td>
                        <td class="td-price">R<?= number_format($item['Price'], 2) ?></td>
                        <td class="td-total">R<?= number_format($item['LineTotal'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="order-totals">
                <div class="tot-row">
                    <span>Subtotal</span>
                    <span>R<?= number_format($order['Subtotal'], 2) ?></span>
                </div>
                <div class="tot-row">
                    <span>Delivery Fee</span>
                    <span>R<?= number_format($order['DeliveryFee'], 2) ?></span>
                </div>
                <div class="tot-row grand">
                    <span>Order Total</span>
                    <span>R<?= number_format($order['Total'], 2) ?></span>
                </div>
            </div>

        </div>
        <?php endforeach; ?>

        <div class="grand-total-footer">
            <div>
                <div class="gt-label">Grand Total of All Purchases</div>
                <div class="gt-sub"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> · <?= $totalItemsCount ?> item<?= $totalItemsCount !== 1 ? 's' : '' ?></div>
            </div>
            <div class="gt-amount">R<?= number_format($grandTotal, 2) ?></div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>