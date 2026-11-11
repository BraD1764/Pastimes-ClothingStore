<?php
/**
 * admin_dashboard.php
 * SAVE TO: C:\xampp\htdocs\wede2\admin_dashboard.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/includes/DBconn.php';

// ─── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn2  = getDBConnection();
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'verify' && $uid) {
        $s = $conn2->prepare("UPDATE tblUser SET Status='active' WHERE UserID=?");
        $s->bind_param("i", $uid); $s->execute(); $s->close();
    } elseif ($action === 'suspend' && $uid) {
        $s = $conn2->prepare("UPDATE tblUser SET Status='suspended' WHERE UserID=?");
        $s->bind_param("i", $uid); $s->execute(); $s->close();
    } elseif ($action === 'delete' && $uid) {
        $s = $conn2->prepare("DELETE FROM tblUser WHERE UserID=?");
        $s->bind_param("i", $uid); $s->execute(); $s->close();

    // ── Clothes/Product Approvals CRUD ────────────────────────────────
    } elseif ($action === 'add_clothes') {
        $itemName = trim($_POST['ItemName'] ?? '');
        $category = trim($_POST['Category'] ?? '');
        $size     = trim($_POST['Size'] ?? '');
        $price    = (float)($_POST['SellPrice'] ?? 0);
        $stock    = (int)($_POST['StockQty'] ?? 0);

        if ($itemName !== '') {
            $s = $conn2->prepare(
                "INSERT INTO tblClothes (ItemName, Category, Size, SellPrice, StockQty, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $s->bind_param("sssdi", $itemName, $category, $size, $price, $stock);
            $s->execute();
            $s->close();
        }

    } elseif ($action === 'edit_clothes') {
        $cid      = (int)($_POST['clothes_id'] ?? 0);
        $itemName = trim($_POST['ItemName'] ?? '');
        $category = trim($_POST['Category'] ?? '');
        $size     = trim($_POST['Size'] ?? '');
        $price    = (float)($_POST['SellPrice'] ?? 0);
        $stock    = (int)($_POST['StockQty'] ?? 0);

        if ($cid && $itemName !== '') {
            $s = $conn2->prepare(
                "UPDATE tblClothes
                 SET ItemName=?, Category=?, Size=?, SellPrice=?, StockQty=?
                 WHERE ClothesID=?"
            );
            $s->bind_param("sssdii", $itemName, $category, $size, $price, $stock, $cid);
            $s->execute();
            $s->close();
        }

    } elseif ($action === 'delete_clothes') {
        $cid = (int)($_POST['clothes_id'] ?? 0);
        if ($cid) {
            $s = $conn2->prepare("DELETE FROM tblClothes WHERE ClothesID=?");
            $s->bind_param("i", $cid);
            $s->execute();
            $s->close();
        }
    }

    $conn2->close();
    header('Location: admin_dashboard.php?tab=' . ($_POST['tab'] ?? 'overview'));
    exit;
}

// ─── Fetch counts ─────────────────────────────────────────────────────────────
$conn = getDBConnection();

$pendingCount = (int) $conn->query(
    "SELECT COUNT(*) AS c FROM tblUser WHERE Status='pending'")->fetch_assoc()['c'];

$sellerCount  = $pendingCount;

$productCount = (int) $conn->query(
    "SELECT COUNT(*) AS c FROM tblClothes")->fetch_assoc()['c'];

$totalPending = $pendingCount + $productCount;

$usersResult = $conn->query(
    "SELECT UserID, FirstName, LastName, Email, Status, RegisteredAt
     FROM tblUser ORDER BY RegisteredAt DESC"
);
$usersArray = [];
while ($row = $usersResult->fetch_assoc()) $usersArray[] = $row;

$clothesResult = $conn->query(
    "SELECT ClothesID, ItemName, Category, Size, SellPrice, StockQty, CreatedAt
     FROM tblClothes ORDER BY CreatedAt DESC"
);
$clothesArray = [];
while ($row = $clothesResult->fetch_assoc()) $clothesArray[] = $row;

$conn->close();

$activeTab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:      #E8622A;
            --orange-dark: #c94e1e;
            --orange-lite: #fff3ee;
            --bg:          #eceef8;
            --white:       #ffffff;
            --text:        #1a1a2e;
            --muted:       #9a9aaf;
            --border:      #e2e4f0;
            --success:     #27ae60;
            --danger:      #e74c3c;
            --radius:      14px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
        }

        /* ── Navbar ──────────────────────────────────────────── */
        nav {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            color: var(--orange);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: .7rem;
            font-size: .84rem;
        }
        .admin-badge {
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            margin-right: .3rem;
        }

        .nav-pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .83rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            padding: .36rem .9rem;
            border-radius: 8px;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: background .2s, border-color .2s, transform .1s;
        }
        .nav-pill.home {
            color: var(--orange);
            border-color: var(--orange);
            background: transparent;
        }
        .nav-pill.home:hover { background: var(--orange-lite); transform: translateY(-1px); }

        .nav-pill.logout {
            color: #fff;
            background: var(--danger);
            border-color: var(--danger);
        }
        .nav-pill.logout:hover { background: #c0392b; border-color: #c0392b; transform: translateY(-1px); }

        .page { max-width: 980px; margin: 2.5rem auto; padding: 0 1.2rem; }

        .dash-card {
            background: #fff;
            border-radius: 22px;
            padding: 2rem 2.2rem 2.2rem;
            box-shadow: 0 2px 16px rgba(80,80,140,.08);
        }

        .dash-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: .8rem;
        }
        .dash-title {
            display: flex;
            align-items: center;
            gap: .55rem;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text);
        }

        .dash-actions {
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .btn-dash-home {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: var(--white);
            color: var(--orange);
            border: 1.5px solid var(--orange);
            border-radius: 10px;
            padding: .6rem 1.3rem;
            font-size: .88rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            transition: background .2s, transform .1s;
        }
        .btn-dash-home:hover { background: var(--orange-lite); transform: translateY(-1px); }

        .btn-dash-logout {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: var(--danger);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: .62rem 1.3rem;
            font-size: .88rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            text-decoration: none;
            box-shadow: 0 3px 12px rgba(231,76,60,.25);
            transition: background .2s, transform .1s, box-shadow .2s;
        }
        .btn-dash-logout:hover {
            background: #c0392b;
            transform: translateY(-1px);
            box-shadow: 0 5px 18px rgba(231,76,60,.35);
        }

        .tabs { display: flex; gap: .55rem; margin-bottom: 1.6rem; flex-wrap: wrap; }
        .tab-btn {
            padding: .48rem 1.15rem;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #fff;
            font-size: .86rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            cursor: pointer;
            transition: border-color .2s, color .2s, background .2s;
        }
        .tab-btn:hover { border-color: var(--orange); color: var(--orange); }
        .tab-btn.active { background: var(--orange); border-color: var(--orange); color: #fff; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.8rem;
        }
        .stat-card {
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover { box-shadow: 0 6px 22px rgba(0,0,0,.08); transform: translateY(-2px); }
        .stat-number { font-size: 2.4rem; font-weight: 700; color: var(--orange); line-height: 1; margin-bottom: .55rem; }
        .stat-label  { font-size: .7rem; font-weight: 700; color: var(--muted); letter-spacing: 1.8px; text-transform: uppercase; }

        .tab-panel        { display: none; }
        .tab-panel.active { display: block; animation: fadeIn .22s ease both; }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
            flex-wrap: wrap;
            font-size: .95rem; font-weight: 700; color: var(--text);
            margin-bottom: 1rem; padding-bottom: .55rem;
            border-bottom: 2px solid var(--orange-lite);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: .85rem;
        }
        .action-tile {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 1.15rem 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            font-size: .86rem;
            display: block;
            transition: border-color .2s, background .2s, color .2s, transform .2s;
        }
        .action-tile:hover { border-color: var(--orange); background: var(--orange-lite); color: var(--orange); transform: translateY(-2px); }
        .action-tile .t-icon { font-size: 1.6rem; display: block; margin-bottom: .4rem; }

        .action-tile.logout-tile:hover {
            border-color: var(--danger);
            background: #fff5f5;
            color: var(--danger);
        }

        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        thead tr { background: #f6f7fb; }
        th {
            padding: .72rem 1rem; text-align: left; font-size: .7rem;
            font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase;
            color: var(--muted); border-bottom: 1.5px solid var(--border);
        }
        td { padding: .72rem 1rem; border-bottom: 1px solid #f2f3f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #fafbff; }

        .badge { display: inline-block; padding: .2rem .7rem; border-radius: 20px; font-size: .7rem; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; }
        .badge-pending   { background: #fff3e0; color: #e65100; }
        .badge-active    { background: #e8f5e9; color: #2e7d32; }
        .badge-suspended { background: #fce4ec; color: #c62828; }

        .btn-act {
            padding: .3rem .8rem; border-radius: 7px; border: none;
            font-size: .76rem; font-weight: 600; font-family: 'DM Sans', sans-serif;
            cursor: pointer; transition: opacity .2s, transform .1s;
            text-decoration: none; display: inline-block; margin: .1rem;
        }
        .btn-act:hover { opacity:.85; transform:translateY(-1px); }
        .btn-verify  { background: var(--success); color:#fff; }
        .btn-suspend { background: #fd7e14; color:#fff; }
        .btn-delete  { background: var(--danger); color:#fff; }
        .btn-edit    { background: #3a7bd5; color:#fff; }

        /* ✅ Add Item button (panel heading) */
        .btn-add-item {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: .55rem 1.1rem;
            font-size: .82rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .1s;
        }
        .btn-add-item:hover { background: var(--orange-dark); transform: translateY(-1px); }

        /* ✅ Modal for Add/Edit clothes */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20,20,40,.45);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 18px;
            padding: 1.8rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 40px rgba(0,0,0,.2);
        }
        .modal-box h3 {
            font-size: 1.1rem;
            margin-bottom: 1.1rem;
            color: var(--text);
        }
        .form-row { margin-bottom: .85rem; }
        .form-row label {
            display: block;
            font-size: .76rem;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: .6px;
            text-transform: uppercase;
            margin-bottom: .35rem;
        }
        .form-row input {
            width: 100%;
            padding: .55rem .7rem;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-size: .9rem;
            font-family: 'DM Sans', sans-serif;
        }
        .form-row input:focus { outline: none; border-color: var(--orange); }
        .modal-actions {
            display: flex;
            gap: .6rem;
            margin-top: 1.3rem;
        }
        .btn-modal-save {
            flex: 1;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: .65rem;
            font-weight: 700;
            font-size: .88rem;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-modal-save:hover { background: var(--orange-dark); }
        .btn-modal-cancel {
            flex: 1;
            background: #f1f1f6;
            color: var(--text);
            border: none;
            border-radius: 9px;
            padding: .65rem;
            font-weight: 700;
            font-size: .88rem;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-modal-cancel:hover { background: #e6e6ee; }

        @media (max-width:600px) {
            .stats-grid { grid-template-columns:1fr; }
            .dash-header { flex-direction:column; align-items:flex-start; }
            nav { padding: 0 1rem; }
            .nav-right .admin-badge { display: none; }
        }
    </style>
</head>
<body>

<!-- ── Navbar ───────────────────────────────────────────────────── -->
<nav>
    <a class="nav-brand" href="index.php">👜 Pastimes</a>
    <div class="nav-right">
        <span class="admin-badge">🔐 <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
        <a href="index.php" class="nav-pill home">🏠 Home</a>
        <a href="admin_logout.php" class="nav-pill logout">🔓 Logout</a>
    </div>
</nav>

<div class="page">
<div class="dash-card">

    <div class="dash-header">
        <div class="dash-title">🔧 &nbsp;Admin Dashboard</div>
        <div class="dash-actions">
            <a href="index.php"        class="btn-dash-home">🏠 Home</a>
            <a href="admin_logout.php" class="btn-dash-logout">🔓 Logout</a>
        </div>
    </div>

    <div class="tabs">
        <button class="tab-btn <?= $activeTab==='overview'     ? 'active':'' ?>" onclick="switchTab('overview',this)">Overview</button>
        <button class="tab-btn <?= $activeTab==='verification' ? 'active':'' ?>" onclick="switchTab('verification',this)">Seller Verification</button>
        <button class="tab-btn <?= $activeTab==='approvals'    ? 'active':'' ?>" onclick="switchTab('approvals',this)">Product Approvals</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $totalPending ?></div>
            <div class="stat-label">Pending Requests</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $sellerCount ?></div>
            <div class="stat-label">Seller Verification</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $productCount ?></div>
            <div class="stat-label">Product Approvals</div>
        </div>
    </div>

    <!-- ══ OVERVIEW TAB ════════════════════════════════════════ -->
    <div class="tab-panel <?= $activeTab==='overview' ? 'active':'' ?>" id="tab-overview">
        <div class="panel-heading">Quick Actions</div>
        <div class="actions-grid">
            <a href="Manage users.php" class="action-tile">
                <span class="t-icon">👥</span>Manage Users
            </a>
            <a href="Manage users.php?filter=pending" class="action-tile">
                <span class="t-icon">⏳</span>Verify Pending
            </a>
            <a href="clothes.php" class="action-tile">
                <span class="t-icon">👗</span>View Shop
            </a>
            <a href="admin_logout.php" class="action-tile logout-tile">
                <span class="t-icon">🔓</span>Logout
            </a>
        </div>
    </div>

    <!-- ══ SELLER VERIFICATION TAB ═════════════════════════════ -->
    <div class="tab-panel <?= $activeTab==='verification' ? 'active':'' ?>" id="tab-verification">
        <div class="panel-heading">Seller Verification — User Accounts</div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th>
                        <th>Status</th><th>Registered</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersArray as $i => $u): ?>
                    <tr>
                        <td style="color:var(--muted);"><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($u['FirstName'].' '.$u['LastName']) ?></strong></td>
                        <td><?= htmlspecialchars($u['Email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $u['Status'] ?>">
                                <?= ucfirst($u['Status']) ?>
                            </span>
                        </td>
                        <td style="color:var(--muted);font-size:.78rem;">
                            <?= date('d M Y', strtotime($u['RegisteredAt'])) ?>
                        </td>
                        <td>
                            <?php if ($u['Status']==='pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action"  value="verify">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['UserID'] ?>">
                                    <input type="hidden" name="tab"     value="verification">
                                    <button class="btn-act btn-verify">✔ Verify</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($u['Status']==='active'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action"  value="suspend">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['UserID'] ?>">
                                    <input type="hidden" name="tab"     value="verification">
                                    <button class="btn-act btn-suspend">⏸ Suspend</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Delete this user permanently?');">
                                <input type="hidden" name="action"  value="delete">
                                <input type="hidden" name="user_id" value="<?= (int)$u['UserID'] ?>">
                                <input type="hidden" name="tab"     value="verification">
                                <button class="btn-act btn-delete">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usersArray)): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ══ PRODUCT APPROVALS TAB ═══════════════════════════════ -->
    <div class="tab-panel <?= $activeTab==='approvals' ? 'active':'' ?>" id="tab-approvals">
        <div class="panel-heading">
            <span>Product Approvals — Clothing Items</span>
            <button type="button" class="btn-add-item" onclick="openAddModal()">➕ Add Item</button>
        </div>
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Item Name</th><th>Category</th>
                        <th>Size</th><th>Price</th><th>Stock</th><th>Date Added</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clothesArray as $i => $item): ?>
                    <tr>
                        <td style="color:var(--muted);"><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($item['ItemName']) ?></strong></td>
                        <td><?= htmlspecialchars($item['Category']) ?></td>
                        <td><?= htmlspecialchars($item['Size']) ?></td>
                        <td><strong>R <?= number_format((float)$item['SellPrice'],2) ?></strong></td>
                        <td>
                            <span class="badge <?= (int)$item['StockQty']>0 ? 'badge-active':'badge-suspended' ?>">
                                <?= (int)$item['StockQty'] ?> left
                            </span>
                        </td>
                        <td style="color:var(--muted);font-size:.78rem;">
                            <?= date('d M Y', strtotime($item['CreatedAt'])) ?>
                        </td>
                        <td>
                            <button type="button" class="btn-act btn-edit"
                                onclick='openEditModal(<?= json_encode([
                                    "id"       => (int)$item["ClothesID"],
                                    "ItemName" => $item["ItemName"],
                                    "Category" => $item["Category"],
                                    "Size"     => $item["Size"],
                                    "SellPrice"=> $item["SellPrice"],
                                    "StockQty" => $item["StockQty"],
                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                ✏ Edit
                            </button>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Delete this item permanently?');">
                                <input type="hidden" name="action"     value="delete_clothes">
                                <input type="hidden" name="clothes_id" value="<?= (int)$item['ClothesID'] ?>">
                                <input type="hidden" name="tab"        value="approvals">
                                <button class="btn-act btn-delete">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clothesArray)): ?>
                        <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem;">No items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- end dash-card -->
</div><!-- end page -->

<!-- ══ ADD / EDIT CLOTHES MODAL ════════════════════════════════ -->
<div class="modal-overlay" id="clothesModalOverlay">
    <div class="modal-box">
        <h3 id="clothesModalTitle">Add New Item</h3>
        <form method="POST" id="clothesForm">
            <input type="hidden" name="action"     id="formAction" value="add_clothes">
            <input type="hidden" name="clothes_id" id="formClothesId" value="">
            <input type="hidden" name="tab"         value="approvals">

            <div class="form-row">
                <label for="ItemName">Item Name</label>
                <input type="text" name="ItemName" id="ItemName" required>
            </div>
            <div class="form-row">
                <label for="Category">Category</label>
                <input type="text" name="Category" id="Category" required>
            </div>
            <div class="form-row">
                <label for="Size">Size</label>
                <input type="text" name="Size" id="Size" required>
            </div>
            <div class="form-row">
                <label for="SellPrice">Sell Price (R)</label>
                <input type="number" step="0.01" min="0" name="SellPrice" id="SellPrice" required>
            </div>
            <div class="form-row">
                <label for="StockQty">Stock Quantity</label>
                <input type="number" min="0" name="StockQty" id="StockQty" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeClothesModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(name, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + name).classList.add('active');
        btn.classList.add('active');
    }

    const clothesOverlay = document.getElementById('clothesModalOverlay');
    const clothesForm    = document.getElementById('clothesForm');

    function openAddModal() {
        document.getElementById('clothesModalTitle').textContent = 'Add New Item';
        document.getElementById('formAction').value = 'add_clothes';
        document.getElementById('formClothesId').value = '';
        clothesForm.reset();
        clothesOverlay.classList.add('active');
    }

    function openEditModal(item) {
        document.getElementById('clothesModalTitle').textContent = 'Edit Item';
        document.getElementById('formAction').value = 'edit_clothes';
        document.getElementById('formClothesId').value = item.id;
        document.getElementById('ItemName').value  = item.ItemName;
        document.getElementById('Category').value  = item.Category;
        document.getElementById('Size').value      = item.Size;
        document.getElementById('SellPrice').value = item.SellPrice;
        document.getElementById('StockQty').value  = item.StockQty;
        clothesOverlay.classList.add('active');
    }

    function closeClothesModal() {
        clothesOverlay.classList.remove('active');
    }

    // Close modal when clicking outside the box
    clothesOverlay.addEventListener('click', function (e) {
        if (e.target === clothesOverlay) closeClothesModal();
    });
</script>

</body>
</html>