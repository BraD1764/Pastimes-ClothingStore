<?php
/**
 * admin_logout.php
 * Destroys the admin session and shows a goodbye page before
 * redirecting to admin_login.php.
 *
 * SAVE TO: C:\xampp\htdocs\wede2\admin_logout.php
 */

// ─── Step 1: Start session ────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ─── Step 2: Grab admin name BEFORE destroying session ───────────────────────
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// ─── Step 3: No-cache headers ─────────────────────────────────────────────────
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// ─── Step 4: Clear all session data ──────────────────────────────────────────
$_SESSION = [];

// ─── Step 5: Delete the session cookie from the browser ──────────────────────
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ─── Step 6: Destroy the session on the server ───────────────────────────────
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logged Out | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Auto-redirect to admin login after 4 seconds -->
    <meta http-equiv="refresh" content="4;url=admin_login.php">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:      #E8622A;
            --orange-dark: #c94e1e;
            --orange-lite: #fff0ea;
            --danger:      #e74c3c;
            --danger-dark: #c0392b;
            --text:        #1a1a2e;
            --muted:       #888;
            --border:      #e8e8ef;
            --white:       #ffffff;
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
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,.10);
            animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* Brand */
        .brand-icon { font-size: 2.4rem; display: block; margin-bottom: .3rem; }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            color: var(--orange);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: .1rem;
        }
        .brand-sub {
            font-size: .72rem;
            color: var(--muted);
            letter-spacing: .5px;
            margin-bottom: .5rem;
        }

        /* Admin badge */
        .admin-chip {
            display: inline-block;
            background: #fff3e0;
            color: #e65100;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: .22rem .75rem;
            border-radius: 999px;
            margin-bottom: 1.8rem;
        }

        /* Waving icon */
        .goodbye-icon {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 1rem;
            animation: wave 1s ease-in-out infinite alternate;
        }
        @keyframes wave {
            from { transform: rotate(-10deg); }
            to   { transform: rotate(10deg); }
        }

        .goodbye-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: .5rem;
        }
        .goodbye-name { color: var(--orange); }

        .goodbye-sub {
            font-size: .88rem;
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: 1.8rem;
        }

        /* Countdown */
        .countdown-wrap { margin-bottom: 1.6rem; }
        .countdown-text {
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: .5rem;
        }
        .progress-bar {
            width: 100%;
            height: 5px;
            background: #eee;
            border-radius: 99px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: var(--orange);
            border-radius: 99px;
            width: 100%;
            animation: shrink 4s linear forwards;
        }
        @keyframes shrink {
            from { width: 100%; }
            to   { width: 0%; }
        }

        /* Buttons */
        .btn {
            display: block;
            width: 100%;
            border-radius: var(--radius);
            padding: .82rem 1.8rem;
            font-size: .92rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            border: none;
            transition: background .2s, transform .15s, box-shadow .2s;
            margin-bottom: .7rem;
        }
        .btn:last-child { margin-bottom: 0; }

        .btn-admin-login {
            background: var(--orange);
            color: #fff;
            box-shadow: 0 4px 16px rgba(232,98,42,.30);
        }
        .btn-admin-login:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(232,98,42,.40);
        }

        .btn-home {
            background: var(--white);
            color: var(--orange);
            border: 2px solid var(--orange);
        }
        .btn-home:hover {
            background: var(--orange-lite);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="card">

    <span class="brand-icon">👜</span>
    <div class="brand-name">Pastimes</div>
    <div class="brand-sub">Admin Panel</div>
    <span class="admin-chip">🔐 Administrator</span>

    <span class="goodbye-icon">👋</span>

    <div class="goodbye-title">
        Goodbye, <span class="goodbye-name"><?= htmlspecialchars($adminName) ?></span>!
    </div>

    <p class="goodbye-sub">
        You have been securely logged out of the admin panel.<br>
        Your session has been completely cleared.
    </p>

    <div class="countdown-wrap">
        <div class="countdown-text">
            Redirecting to admin login in <strong id="countdown">4</strong> seconds…
        </div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>

    <a href="admin_login.php" class="btn btn-admin-login">🔐 Back to Admin Login</a>
    <a href="index.php"       class="btn btn-home">🏠 Go to Home</a>

</div>

<script>
    let seconds = 4;
    const countdownEl = document.getElementById('countdown');

    const timer = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(timer);
            // replace() prevents back-button returning to the dashboard
            window.location.replace('admin_login.php');
        }
    }, 1000);
</script>

</body>
</html>