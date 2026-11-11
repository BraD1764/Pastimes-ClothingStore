<?php
/**
 * logout.php
 * Logs the user out by destroying the session completely,
 * then shows a goodbye page before redirecting to login.php.
 *
 * Works correctly whether called from:
 *   - clothes.php  (root-level link: href="logout.php")
 *   - index.php    (root-level link: href="logout.php")
 *   - any root-level page
 *
 * SAVE TO: C:\xampp\htdocs\wede2\logout.php
 *
 * Author: ClothingStore Team
 * Date:   2025
 */

// ─── Step 1: Start session so we can read and then destroy it ─────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ─── Step 2: Grab user name BEFORE destroying session ─────────────────────────
// Once we call session_destroy() this data is gone — capture it first
$userName = $_SESSION['user_name'] ?? 'User';

// ─── Step 3: Prevent browser from caching this page ───────────────────────────
// This is the key fix: without these headers the browser may serve a cached
// version of clothes.php even after logout, bypassing the session guard
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// ─── Step 4: Clear all session variables ──────────────────────────────────────
$_SESSION = [];

// ─── Step 5: Destroy the session cookie from the browser ──────────────────────
// This removes the PHPSESSID cookie so the browser cannot reuse the old session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',                  // Empty value
        time() - 42000,      // Expire in the past — forces browser to delete it
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ─── Step 6: Destroy the session on the server ────────────────────────────────
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Auto-redirect to login after 4 seconds -->
    <meta http-equiv="refresh" content="4;url=login.php">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:      #E8622A;
            --orange-dark: #c94e1e;
            --orange-lite: #fff0ea;
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
            max-width: 400px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,.10);
            animation: slideUp .45s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes slideUp {
            from { opacity:0; transform:translateY(24px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .brand-icon { font-size: 2.6rem; display: block; margin-bottom: .4rem; }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            color: var(--orange);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: .15rem;
        }
        .brand-sub {
            font-size: .75rem;
            color: var(--muted);
            letter-spacing: .5px;
            margin-bottom: 1.8rem;
        }

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
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 1.8rem;
        }

        .countdown-wrap { margin-bottom: 1.5rem; }
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

        .btn-login {
            display: inline-block;
            width: 100%;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            padding: .82rem 1.8rem;
            font-size: .95rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(232,98,42,.32);
            margin-bottom: .7rem;
        }
        .btn-login:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(232,98,42,.42);
        }

        .btn-home {
            display: inline-block;
            width: 100%;
            background: #fff;
            color: var(--orange);
            border: 2px solid var(--orange);
            border-radius: var(--radius);
            padding: .8rem 1.8rem;
            font-size: .95rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, transform .1s;
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
    <div class="brand-sub">Second-Hand Branded Clothing Marketplace</div>

    <span class="goodbye-icon">👋</span>

    <div class="goodbye-title">
        Goodbye, <span class="goodbye-name"><?= htmlspecialchars($userName) ?></span>!
    </div>

    <p class="goodbye-sub">
        You have been successfully logged out.<br>
        Thank you for shopping with Pastimes.
    </p>

    <div class="countdown-wrap">
        <div class="countdown-text">
            Redirecting to login in <strong id="countdown">4</strong> seconds…
        </div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>

    <a href="login.php" class="btn-login">Login Again</a>
    <a href="index.php" class="btn-home">Go to Home</a>

</div>

<script>
    // Countdown from 4 to 0 then redirect
    let seconds = 4;
    const countdownEl = document.getElementById('countdown');

    const timer = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.replace('login.php'); // replace() prevents back-button returning here
        }
    }, 1000);
</script>

</body>
</html>