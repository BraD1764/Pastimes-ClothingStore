<?php
/**
 * admin_login.php
 * SAVE TO: C:\xampp\htdocs\wede2\admin_login.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in — go straight to dashboard (same folder)
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

// includes/ is in the same folder as this file
require_once __DIR__ . '/includes/DBconn.php';

$error       = '';
$stickyEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $adminEmail  = trim($_POST['admin_email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $stickyEmail = $adminEmail;

    if (empty($adminEmail) || empty($password)) {
        $error = "Both email and password are required.";
    } else {

        $conn = getDBConnection();

        if (!$conn) {
            die("Database connection failed.");
        }

        $adminID      = null;
        $adminName    = '';
        $adminEmailDB = '';
        $adminPass    = '';

        $hashedInput = md5($password);

        $sql = "
            SELECT AdminID, AdminName, AdminEmail, AdminPass
            FROM tblAdmin
            WHERE AdminEmail = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "System error. Please try again later.";
        } else {

            $stmt->bind_param("s", $adminEmail);

            if (!$stmt->execute()) {
                $error = "System error. Please try again.";
            } else {

                $stmt->bind_result($adminID, $adminName, $adminEmailDB, $adminPass);

                if ($stmt->fetch()) {
                    if ($adminPass !== $hashedInput) {
                        $error = "Incorrect password. Please try again.";
                    } else {
                        $_SESSION['admin_id']    = $adminID;
                        $_SESSION['admin_name']  = $adminName;
                        $_SESSION['admin_email'] = $adminEmailDB;

                        $stmt->close();
                        $conn->close();

                        // dashboard is in the same root folder
                        header('Location: admin_dashboard.php');
                        exit;
                    }
                } else {
                    $error = "No administrator account found with that email.";
                }
            }

            $stmt->close();
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:      #E8622A;
            --orange-dark: #c94e1e;
            --orange-lite: #fff0ea;
            --text:        #1a1a2e;
            --muted:       #888;
            --border:      #e8e8e8;
            --white:       #ffffff;
            --error:       #d63031;
            --radius:      14px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: radial-gradient(ellipse at 60% 0%, #dde3f7 0%, #f0f2f8 55%),
                        radial-gradient(ellipse at 10% 100%, #e8ddf7 0%, transparent 50%);
        }

        .card {
            background: var(--white);
            border-radius: 28px;
            padding: 2.6rem 2.2rem 2.2rem;
            width: 100%;
            max-width: 390px;
            box-shadow: 0 8px 40px rgba(0,0,0,.10);
            animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .brand { text-align:center; margin-bottom:1.5rem; }
        .brand-icon  { font-size:2.4rem; display:block; margin-bottom:.3rem; }
        .brand-name  {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            color: var(--orange);
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .brand-sub {
            font-size: .75rem;
            color: var(--muted);
            margin-top: .15rem;
            letter-spacing: .5px;
        }

        .admin-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            background: #fff3ee;
            border: 1.5px solid #ffd4c2;
            border-radius: 10px;
            padding: .55rem 1rem;
            margin-bottom: 1.3rem;
            font-size: .82rem;
            font-weight: 700;
            color: var(--orange);
            letter-spacing: .5px;
        }

        .page-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.3rem;
        }

        .alert-danger {
            background: #fff5f5;
            border-left: 4px solid var(--error);
            color: var(--error);
            padding: .7rem 1rem;
            border-radius: 8px;
            font-size: .85rem;
            margin-bottom: 1.1rem;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: .38rem;
        }
        .form-group input {
            width: 100%;
            padding: .75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-size: .93rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: #fafafa;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }
        .form-group input:focus {
            border-color: var(--orange);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(232,98,42,.12);
        }
        .form-group input::placeholder { color: #c0c0c0; }

        .btn-submit {
            width: 100%;
            padding: .85rem;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: .95rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            cursor: pointer;
            margin-top: .4rem;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(232,98,42,.35);
        }
        .btn-submit:hover {
            background: var(--orange-dark);
            box-shadow: 0 6px 24px rgba(232,98,42,.45);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); }

        .divider {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin: 1.2rem 0 1rem;
            font-size: .78rem;
            color: var(--muted);
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .footer-link {
            text-align: center;
            font-size: .84rem;
            color: var(--muted);
        }
        .footer-link a {
            color: var(--orange);
            font-weight: 600;
            text-decoration: none;
        }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">

    <div class="brand">
        <span class="brand-icon">👜</span>
        <div class="brand-name">Pastimes</div>
        <div class="brand-sub">Second-Hand Branded Clothing Marketplace</div>
    </div>

    <div class="admin-badge">🔐 Administrator Portal</div>

    <div class="page-title">Admin Login</div>

    <?php if (!empty($error)): ?>
        <div class="alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php">

        <div class="form-group">
            <label for="admin_email">Admin Email Address</label>
            <input type="email" id="admin_email" name="admin_email"
                   value="<?= htmlspecialchars($stickyEmail) ?>"
                   required placeholder="admin@clothingstore.co.za"
                   autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   required placeholder="••••••••"
                   autocomplete="current-password">
        </div>

        <button type="submit" class="btn-submit">Login as Administrator</button>

    </form>

    <div class="divider">or</div>

    <div class="footer-link">
        <!-- Login.php is in the same root folder -->
        <a href="Login.php">← Back to User Login</a>
    </div>

</div>

</body>
</html>