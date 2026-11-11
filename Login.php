<?php
/**
 * login.php
 * User login page — styled to match the Pastimes brand design.
 *
 * SAVE TO: C:\xampp\htdocs\wede2\login.php
 *
 * Author: ClothingStore Team
 * Date:   2025
 */

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: clothes.php');
    exit;
}

require_once __DIR__ . '/includes/DBConn.php';

$error       = '';
$stickyEmail = '';

// ── Order-placed confirmation (redirected here from checkout.php) ───────────
$orderPlaced     = isset($_GET['order_placed']) && $_GET['order_placed'] === '1';
$orderNumDisplay = $orderPlaced ? htmlspecialchars($_GET['order_num'] ?? '') : '';
$sessionIdDisplay= $orderPlaced ? htmlspecialchars($_GET['session_id'] ?? '') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $stickyEmail = $email;

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $conn        = getDBConnection();
        $hashedInput = md5($password);

        $stmt = $conn->prepare(
            "SELECT UserID, FirstName, LastName, Email, UserPass,
                    Phone, Address, Status, RegisteredAt
             FROM tblUser WHERE Email = ? LIMIT 1"
        );

        if (!$stmt) {
            $error = "System error. Please try again later.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result(
                $userID, $firstName, $lastName, $userEmail,
                $userPass, $phone, $address, $status, $registeredAt
            );
            $found = $stmt->fetch();

            if (!$found) {
                $error = "No account found with that email. Please register.";
            } elseif ($userPass !== $hashedInput) {
                $error = "Incorrect password. Please try again.";
            } elseif ($status === 'pending') {
                $error = "Your account is pending admin verification.";
            } elseif ($status === 'suspended') {
                $error = "Your account has been suspended.";
            } else {
                $user = [
                    'UserID'    => $userID,   'FirstName' => $firstName,
                    'LastName'  => $lastName,  'Email'     => $userEmail,
                    'Phone'     => $phone,     'Address'   => $address,
                    'Status'    => $status,    'RegisteredAt' => $registeredAt,
                ];
                $_SESSION['user_id']   = $user['UserID'];
                $_SESSION['user_name'] = $user['FirstName'] . ' ' . $user['LastName'];
                $_SESSION['user_email']= $user['Email'];
                $_SESSION['user_data'] = $user;

                $stmt->close();
                $conn->close();
                header('Location: clothes.php');
                exit;
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
    <title>Login | Pastimes</title>
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
            --bg:          #f0f2f8;
            --error:       #d63031;
            --success:     #2f9e44;
            --success-bg:  #f0fff4;
            --success-border: #9ae6b4;
            --radius:      14px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            /* Subtle radial bg like the screenshot */
            background: radial-gradient(ellipse at 60% 0%, #dde3f7 0%, #f0f2f8 55%),
                        radial-gradient(ellipse at 10% 100%, #e8ddf7 0%, transparent 50%);
        }

        /* ── Card ─────────────────────────────────────────────── */
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
            from { opacity:0; transform: translateY(24px); }
            to   { opacity:1; transform: translateY(0);    }
        }

        /* ── Logo / brand ─────────────────────────────────────── */
        .brand {
            text-align: center;
            margin-bottom: 1.6rem;
        }
        .brand-icon {
            font-size: 2.4rem;
            margin-bottom: .3rem;
            display: block;
        }
        .brand-name {
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

        /* ── Page title ───────────────────────────────────────── */
        .page-title {
            font-family: 'DM Sans', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.4rem;
        }

        /* ── Error alert ──────────────────────────────────────── */
        .alert {
            background: #fff5f5;
            border-left: 4px solid var(--error);
            color: var(--error);
            padding: .7rem 1rem;
            border-radius: 8px;
            font-size: .85rem;
            margin-bottom: 1.1rem;
        }

        /* ── Order-placed success alert ─────────────────────────── */
        .alert-success-box {
            background: var(--success-bg);
            border-left: 4px solid var(--success);
            color: #216c34;
            padding: .9rem 1rem;
            border-radius: 8px;
            font-size: .85rem;
            margin-bottom: 1.1rem;
            line-height: 1.5;
        }
        .alert-success-box .success-heading {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: .4rem;
            font-size: .9rem;
        }
        .alert-success-box .ref-row {
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            font-size: .78rem;
            padding: .15rem 0;
        }
        .alert-success-box .ref-label {
            color: #4a8a5e;
            font-weight: 600;
        }
        .alert-success-box .ref-value {
            font-family: monospace;
            font-weight: 700;
            color: #216c34;
            word-break: break-all;
            text-align: right;
        }

        /* ── Form groups ──────────────────────────────────────── */
        .form-group {
            margin-bottom: 1rem;
        }
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

        /* ── Submit button ────────────────────────────────────── */
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
            margin-top: .5rem;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(232,98,42,.35);
        }
        .btn-submit:hover {
            background: var(--orange-dark);
            box-shadow: 0 6px 24px rgba(232,98,42,.45);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); }

        /* ── Footer links ─────────────────────────────────────── */
        .footer-link {
            text-align: center;
            margin-top: 1.1rem;
            font-size: .84rem;
            color: var(--muted);
        }
        .footer-link a {
            color: var(--orange);
            font-weight: 600;
            text-decoration: none;
        }
        .footer-link a:hover { text-decoration: underline; }

        .divider {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin: 1.2rem 0;
            color: var(--border);
            font-size: .78rem;
            color: var(--muted);
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .admin-link {
            display: block;
            text-align: center;
            font-size: .82rem;
            color: var(--muted);
            text-decoration: none;
            padding: .5rem;
            border-radius: 8px;
            transition: background .2s, color .2s;
        }
        .admin-link:hover {
            background: var(--orange-lite);
            color: var(--orange);
        }
    </style>
</head>
<body>

<div class="card">

    <!-- Brand logo -->
    <div class="brand">
        <span class="brand-icon">👜</span>
        <div class="brand-name">Pastimes</div>
        <div class="brand-sub">Second-Hand Branded Clothing Marketplace</div>
    </div>

    <div class="page-title">Login to your account</div>

    <!-- Order-placed success banner (redirected from checkout.php) -->
    <?php if ($orderPlaced): ?>
        <div class="alert-success-box">
            <div class="success-heading">🎉 Order placed successfully!</div>
            <div>Thank you for shopping with Pastimes. You've been logged out for security — please log in again to track your order.</div>
            <?php if ($orderNumDisplay): ?>
                <div class="ref-row">
                    <span class="ref-label">Order Number:</span>
                    <span class="ref-value"><?= $orderNumDisplay ?></span>
                </div>
            <?php endif; ?>
            <?php if ($sessionIdDisplay): ?>
                <div class="ref-row">
                    <span class="ref-label">Session ID:</span>
                    <span class="ref-value"><?= $sessionIdDisplay ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Error alert (sticky form) -->
    <?php if (!empty($error)): ?>
        <div class="alert">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Login form -->
    <form method="POST" action="login.php">

        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($stickyEmail) ?>"
                required
                placeholder="j.doe@abc.co.za"
                autocomplete="username"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                placeholder="Minimum 6 characters"
                autocomplete="current-password"
            >
        </div>

        <button type="submit" class="btn-submit">Login</button>

    </form>

    <div class="footer-link" style="margin-top:1.3rem;">
        Don't have an account? <a href="register.php">REGISTER</a>
    </div>

    <div class="divider">or</div>

    <a href="admin_login.php" class="admin-link">
        🔐 Administrator Login →
    </a>

</div>

</body>
</html>