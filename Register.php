<?php
/**
 * register.php
 * User registration — styled to match the Pastimes brand design.
 *
 * SAVE TO: C:\xampp\htdocs\wede2\register.php
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

$errors  = [];
$success = false;
$form    = ['full_name' => '', 'email' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim($_POST['full_name']        ?? '');
    $email     = trim($_POST['email']            ?? '');
    $username  = trim($_POST['username']         ?? '');
    $password  = $_POST['password']              ?? '';
    $confirm   = $_POST['confirm_password']      ?? '';
    $terms     = isset($_POST['terms']);

    $form = ['full_name' => $fullName, 'email' => $email, 'username' => $username];

    // Split full name into first/last
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName  = $nameParts[1] ?? '';

    if (empty($fullName))                              $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($username))                              $errors[] = "Username is required.";
    if (strlen($password) < 6)                        $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)                        $errors[] = "Passwords do not match.";
    if (!$terms)                                       $errors[] = "You must agree to the Terms & Conditions.";

    if (empty($errors)) {
        $conn = getDBConnection();
        $check = $conn->prepare("SELECT UserID FROM tblUser WHERE Email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            $hashed = md5($password);
            $ins = $conn->prepare(
                "INSERT INTO tblUser (FirstName, LastName, Email, UserPass, Phone, Address, Status)
                 VALUES (?, ?, ?, ?, '', '', 'pending')"
            );
            $ins->bind_param("ssss", $firstName, $lastName, $email, $hashed);
            if ($ins->execute()) {
                $success = true;
                $form    = ['full_name' => '', 'email' => '', 'username' => ''];
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $ins->close();
        }
        $check->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Pastimes</title>
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
            --success:     #00b894;
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
            padding: 2.4rem 2.2rem 2rem;
            width: 100%;
            max-width: 390px;
            box-shadow: 0 8px 40px rgba(0,0,0,.10);
            animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform: translateY(24px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .brand { text-align: center; margin-bottom: 1.5rem; }
        .brand-icon { font-size: 2.4rem; margin-bottom: .3rem; display: block; }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            color: var(--orange);
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .brand-sub { font-size: .75rem; color: var(--muted); margin-top: .15rem; letter-spacing: .5px; }

        .page-title { font-size: 1.15rem; font-weight: 600; color: var(--text); margin-bottom: 1.2rem; }

        .alert-danger {
            background: #fff5f5;
            border-left: 4px solid var(--error);
            color: var(--error);
            padding: .7rem 1rem;
            border-radius: 8px;
            font-size: .83rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #f0fff8;
            border-left: 4px solid var(--success);
            color: #007a63;
            padding: .9rem 1rem;
            border-radius: 8px;
            font-size: .88rem;
            margin-bottom: 1rem;
        }

        .form-group { margin-bottom: .9rem; }
        .form-group label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: .35rem;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: .72rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-size: .92rem;
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
        .form-group input::placeholder { color: #c8c8c8; }

        .hint {
            font-size: .73rem;
            color: var(--muted);
            margin-top: .25rem;
            font-style: italic;
        }

        /* ── Checkbox row ─────────────────────────────────────── */
        .checkbox-row {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: .9rem 0 1rem;
        }
        .checkbox-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--orange);
            cursor: pointer;
            flex-shrink: 0;
        }
        .checkbox-row label {
            font-size: .82rem;
            color: var(--muted);
            cursor: pointer;
            line-height: 1.4;
        }
        .checkbox-row label a {
            color: var(--orange);
            font-weight: 600;
            text-decoration: none;
        }
        .checkbox-row label a:hover { text-decoration: underline; }

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
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 18px rgba(232,98,42,.35);
        }
        .btn-submit:hover {
            background: var(--orange-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(232,98,42,.45);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: .6; cursor: not-allowed; }

        .footer-link {
            text-align: center;
            margin-top: 1.1rem;
            font-size: .84rem;
            color: var(--muted);
        }
        .footer-link a {
            color: var(--orange);
            font-weight: 700;
            text-decoration: none;
            letter-spacing: .5px;
        }
        .footer-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="card">

    <!-- Brand -->
    <div class="brand">
        <span class="brand-icon">👜</span>
        <div class="brand-name">Pastimes</div>
        <div class="brand-sub">Second-Hand Branded Clothing Marketplace</div>
    </div>

    <?php if ($success): ?>
        <div class="alert-success">
            ✅ Registration successful! Your account is <strong>pending admin verification</strong>.
            You will be able to login once approved.
        </div>
        <div class="footer-link">
            <a href="login.php">Back to LOGIN</a>
        </div>
    <?php else: ?>

        <div class="page-title">Create an account</div>

        <?php if (!empty($errors)): ?>
            <div class="alert-danger">
                <?php foreach ($errors as $e): ?>
                    <div>⚠ <?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= htmlspecialchars($form['full_name']) ?>"
                       required placeholder="John Doe">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($form['email']) ?>"
                       required placeholder="j.doe@abc.co.za">
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?= htmlspecialchars($form['username']) ?>"
                       required placeholder="johndoe">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       required placeholder="Minimum 6 characters"
                       minlength="6">
                <div class="hint">(Minimum 6 characters)</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       required placeholder="Re-enter your password">
            </div>

            <!-- Terms checkbox — matches the screenshot exactly -->
            <div class="checkbox-row">
                <input type="checkbox" id="terms" name="terms"
                       <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                <label for="terms">
                    I agree to the Pastimes
                    <a href="#">Terms &amp; Conditions</a>
                </label>
            </div>

            <button type="submit" class="btn-submit">Register</button>

        </form>

        <div class="footer-link">
            Already have an account? <a href="login.php">LOGIN</a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>