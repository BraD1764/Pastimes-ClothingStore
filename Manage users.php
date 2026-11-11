<?php
/**
 * admin/manage_users.php
 * Administrator: manage all users (tblUser).
 * Features:
 *  - List all users with status badges, filterable by status.
 *  - Verify pending registrations (set Status = 'active').
 *  - Add a new user.
 *  - Edit / update an existing user.
 *  - Delete a user.
 *  - Suspend a user.
 *
 * Author: ClothingStore Team
 * Date: 2025
 */

// ─── Session guard ────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/../includes/DbConn.php';

// ─── State ───────────────────────────────────────────────────────────────────
$message     = '';
$messageType = 'success';
$editUser    = null;       // Populated when editing a specific user
$filter      = $_GET['filter'] ?? 'all';   // Status filter from query string

// ─── Handle actions (POST) ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn   = getDBConnection();
    $action = $_POST['action'] ?? '';

    // ── Verify pending user ──────────────────────────────────────────────────
    if ($action === 'verify') {
        $uid  = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE tblUser SET Status = 'active' WHERE UserID = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $message = "User verified and activated successfully.";
        $stmt->close();

    // ── Suspend user ─────────────────────────────────────────────────────────
    } elseif ($action === 'suspend') {
        $uid  = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE tblUser SET Status = 'suspended' WHERE UserID = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $message = "User account suspended.";
        $stmt->close();

    // ── Delete user ──────────────────────────────────────────────────────────
    } elseif ($action === 'delete') {
        $uid  = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM tblUser WHERE UserID = ?");
        $stmt->bind_param("i", $uid);
        if ($stmt->execute()) {
            $message = "User deleted successfully.";
        } else {
            $message     = "Error deleting user: " . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();

    // ── Add new user ─────────────────────────────────────────────────────────
    } elseif ($action === 'add') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $address   = trim($_POST['address']    ?? '');
        $status    = $_POST['status']          ?? 'pending';
        $password  = $_POST['password']        ?? 'password123';

        $hashedPass = md5($password);

        $stmt = $conn->prepare(
            "INSERT INTO tblUser (FirstName, LastName, Email, UserPass, Phone, Address, Status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss",
            $firstName, $lastName, $email,
            $hashedPass, $phone, $address, $status
        );
        if ($stmt->execute()) {
            $message = "New user added successfully.";
        } else {
            $message     = "Error adding user (email may already exist): " . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();

    // ── Update existing user ─────────────────────────────────────────────────
    } elseif ($action === 'update') {
        $uid       = (int)$_POST['user_id'];
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name']  ?? '');
        $email     = trim($_POST['email']      ?? '');
        $phone     = trim($_POST['phone']      ?? '');
        $address   = trim($_POST['address']    ?? '');
        $status    = $_POST['status']          ?? 'pending';

        $stmt = $conn->prepare(
            "UPDATE tblUser
             SET FirstName=?, LastName=?, Email=?, Phone=?, Address=?, Status=?
             WHERE UserID=?"
        );
        $stmt->bind_param("ssssssi",
            $firstName, $lastName, $email,
            $phone, $address, $status, $uid
        );
        if ($stmt->execute()) {
            $message = "User updated successfully.";
        } else {
            $message     = "Error updating user: " . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    }

    $conn->close();
}

// ─── Load edit user data (GET action=edit) ───────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $conn     = getDBConnection();
    $editId   = (int)$_GET['id'];
    $editStmt = $conn->prepare(
        "SELECT UserID, FirstName, LastName, Email, Phone, Address, Status
         FROM tblUser WHERE UserID = ?"
    );
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    $editUser   = $editResult->fetch_assoc();
    $editStmt->close();
    $conn->close();
}

// ─── Fetch users (filtered) ──────────────────────────────────────────────────
$conn = getDBConnection();

// Build WHERE clause based on filter
$filterSQL = '';
if (in_array($filter, ['pending', 'active', 'suspended'])) {
    $filterSQL = "WHERE Status = '$filter'";
}

$usersResult = $conn->query(
    "SELECT UserID, FirstName, LastName, Email, Phone, Address, Status, RegisteredAt
     FROM tblUser $filterSQL
     ORDER BY RegisteredAt DESC"
);

// Read all users into associative array
$usersArray = [];
while ($row = $usersResult->fetch_assoc()) {
    $usersArray[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin | Pastimes</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
    <a class="brand" href="../index.php">Past<span>imes</span></a>
    <div class="nav-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="admin_logout.php">Logout</a>
    </div>
</nav>
<div class="user-bar" style="background:var(--clr-danger);">
    <span>🔐 Admin: <?= htmlspecialchars($_SESSION['admin_name']) ?> logged in</span>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="page-wrapper">

    <!-- ── Status message ────────────────────────────────────── -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ── Add / Edit User form ──────────────────────────────── -->
    <div class="card">
        <h2><?= $editUser ? '✏️ Edit User' : '➕ Add New User' ?></h2>
        <form method="POST" action="manage_users.php">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'add' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="user_id" value="<?= (int)$editUser['UserID'] ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.8rem;">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required
                           value="<?= htmlspecialchars($editUser['FirstName'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required
                           value="<?= htmlspecialchars($editUser['LastName'] ?? '') ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.8rem;">
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required
                           value="<?= htmlspecialchars($editUser['Email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone"
                           value="<?= htmlspecialchars($editUser['Phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <textarea name="address"><?= htmlspecialchars($editUser['Address'] ?? '') ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.8rem;">
                <?php if (!$editUser): ?>
                <div class="form-group">
                    <label>Password (default: password123)</label>
                    <input type="text" name="password" value="password123">
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="pending"   <?= ($editUser['Status'] ?? '') === 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="active"    <?= ($editUser['Status'] ?? '') === 'active'    ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= ($editUser['Status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-<?= $editUser ? 'info' : 'success' ?>">
                <?= $editUser ? '💾 Update User' : '➕ Add User' ?>
            </button>
            <?php if ($editUser): ?>
                <a href="manage_users.php" class="btn btn-primary" style="margin-left:.5rem;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- ── Filter tabs ───────────────────────────────────────── -->
    <div class="card" style="padding:1rem 1.5rem;">
        <strong>Filter:</strong>
        <a href="manage_users.php?filter=all"       class="btn btn-sm <?= $filter === 'all'       ? 'btn-primary' : 'btn-info' ?>" style="margin:.2rem;">All</a>
        <a href="manage_users.php?filter=pending"   class="btn btn-sm <?= $filter === 'pending'   ? 'btn-primary' : 'btn-accent' ?>" style="margin:.2rem;">Pending</a>
        <a href="manage_users.php?filter=active"    class="btn btn-sm <?= $filter === 'active'    ? 'btn-primary' : 'btn-success' ?>" style="margin:.2rem;">Active</a>
        <a href="manage_users.php?filter=suspended" class="btn btn-sm <?= $filter === 'suspended' ? 'btn-primary' : 'btn-danger' ?>" style="margin:.2rem;">Suspended</a>
    </div>

    <!-- ── Users table ──────────────────────────────────────── -->
    <div class="card">
        <h2>👥 User Accounts
            <small style="font-size:.85rem; color:var(--clr-muted);">
                (<?= count($usersArray) ?> shown)
            </small>
        </h2>

        <?php if (empty($usersArray)): ?>
            <div class="alert alert-info">No users found with the selected filter.</div>
        <?php else: ?>
            <div class="tbl-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersArray as $user): ?>
                        <tr>
                            <td><?= (int)$user['UserID'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($user['Email']) ?></td>
                            <td><?= htmlspecialchars($user['Phone'] ?? '—') ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($user['Status']) ?>">
                                    <?= ucfirst(htmlspecialchars($user['Status'])) ?>
                                </span>
                            </td>
                            <td style="font-size:.82rem; color:var(--clr-muted);">
                                <?= date('d M Y', strtotime($user['RegisteredAt'])) ?>
                            </td>
                            <td>
                                <!-- Edit -->
                                <a href="manage_users.php?action=edit&id=<?= (int)$user['UserID'] ?>"
                                   class="btn btn-info btn-sm">Edit</a>

                                <!-- Verify (only for pending) -->
                                <?php if ($user['Status'] === 'pending'): ?>
                                    <form method="POST" action="manage_users.php"
                                          style="display:inline;">
                                        <input type="hidden" name="action"  value="verify">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['UserID'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">✔ Verify</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Suspend (only for active) -->
                                <?php if ($user['Status'] === 'active'): ?>
                                    <form method="POST" action="manage_users.php"
                                          style="display:inline;">
                                        <input type="hidden" name="action"  value="suspend">
                                        <input type="hidden" name="user_id" value="<?= (int)$user['UserID'] ?>">
                                        <button type="submit" class="btn btn-accent btn-sm">⏸ Suspend</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Delete -->
                                <form method="POST" action="manage_users.php"
                                      style="display:inline;"
                                      onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                    <input type="hidden" name="action"  value="delete">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['UserID'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<footer>
    <p>&copy; <?= date('Y') ?> Pastimes ClothingStore. All rights reserved.</p>
</footer>
</body>
</html>