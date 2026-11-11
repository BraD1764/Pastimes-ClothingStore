<?php
/**
 * index.php
 * Home / landing page for the Pastimes ClothingStore application.
 * SAVE TO: C:\xampp\htdocs\wede2\index.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Pastimes</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            --bg:          #eceef8;
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
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            color: var(--orange);
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
        }
        .nav-links { display: flex; align-items: center; gap: .8rem; }
        .nav-link {
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text);
            padding: .4rem .85rem;
            border-radius: 8px;
            transition: background .2s, color .2s;
        }
        .nav-link:hover { background: var(--orange-lite); color: var(--orange); }
        .nav-link-orange {
            background: var(--orange);
            color: #fff;
            border-radius: 8px;
            padding: .4rem .95rem;
            box-shadow: 0 3px 10px rgba(232,98,42,.28);
            transition: background .2s;
        }
        .nav-link-orange:hover { background: var(--orange-dark); color: #fff; }

        /* ── Logged-in bar ───────────────────────────────────── */
        .user-bar {
            background: var(--orange);
            color: #fff;
            padding: .5rem 2rem;
            font-size: .83rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-bar a { color: #fff; font-weight: 700; text-decoration: none; }
        .user-bar a:hover { text-decoration: underline; }

        /* ── Page wrapper ────────────────────────────────────── */
        .page {
            max-width: 980px;
            margin: 2.5rem auto;
            padding: 0 1.2rem;
        }

        /* ── Hero card ───────────────────────────────────────── */
        .hero {
            background: #fff;
            border-radius: 22px;
            padding: 3.5rem 2.5rem;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.07);
            margin-bottom: 1.4rem;
            animation: fadeUp .5s cubic-bezier(.22,.68,0,1.2) both;
        }

        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .hero-icon { font-size: 3rem; display: block; margin-bottom: .6rem; }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            color: var(--text);
            margin-bottom: .6rem;
        }
        .hero h1 span { color: var(--orange); }

        .hero p {
            color: var(--muted);
            font-size: 1rem;
            max-width: 520px;
            margin: 0 auto 2rem;
            line-height: 1.7;
        }

        .hero-btns { display: flex; gap: .8rem; justify-content: center; flex-wrap: wrap; }

        .btn-primary {
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            padding: .78rem 1.8rem;
            font-size: .95rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: .8px;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(232,98,42,.32);
            display: inline-block;
        }
        .btn-primary:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(232,98,42,.42);
        }

        .btn-outline {
            background: #fff;
            color: var(--orange);
            border: 2px solid var(--orange);
            border-radius: var(--radius);
            padding: .76rem 1.8rem;
            font-size: .95rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: .8px;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, color .2s, transform .1s;
            display: inline-block;
        }
        .btn-outline:hover {
            background: var(--orange-lite);
            transform: translateY(-2px);
        }

        /* ── Feature cards grid ──────────────────────────────── */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.4rem;
        }

        .feature-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.8rem 1.4rem;
            text-align: center;
            box-shadow: 0 2px 14px rgba(0,0,0,.06);
            border: 1.5px solid var(--border);
            transition: transform .2s, box-shadow .2s;
            animation: fadeUp .5s cubic-bezier(.22,.68,0,1.2) both;
        }
        .feature-card:nth-child(2) { animation-delay: .08s; }
        .feature-card:nth-child(3) { animation-delay: .16s; }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(0,0,0,.10);
        }

        .feature-icon { font-size: 2.4rem; display: block; margin-bottom: .7rem; }

        .feature-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: .5rem;
        }

        .feature-card p {
            font-size: .85rem;
            color: var(--muted);
            line-height: 1.65;
        }

        /* ── Stats bar ───────────────────────────────────────── */
        .stats-bar {
            background: #fff;
            border-radius: 18px;
            padding: 1.4rem 2rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            box-shadow: 0 2px 14px rgba(0,0,0,.06);
            border: 1.5px solid var(--border);
            text-align: center;
            animation: fadeUp .55s cubic-bezier(.22,.68,0,1.2) .2s both;
        }

        .stat-item + .stat-item {
            border-left: 1px solid var(--border);
        }

        .stat-num {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--orange);
            line-height: 1;
            margin-bottom: .3rem;
        }

        .stat-lbl {
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* ── Footer ──────────────────────────────────────────── */
        footer {
            text-align: center;
            padding: 1.8rem;
            font-size: .82rem;
            color: var(--muted);
            border-top: 1px solid var(--border);
            margin-top: 2.5rem;
            background: #fff;
        }

        @media (max-width: 600px) {
            .hero h1 { font-size: 1.7rem; }
            .stats-bar { grid-template-columns: 1fr; }
            .stat-item + .stat-item { border-left: none; border-top: 1px solid var(--border); }
            nav { padding: 0 1rem; }
        }
    </style>
</head>
<body>

<!-- ── Navbar ─────────────────────────────────────────────────── -->
<nav>
    <a class="nav-brand" href="index.php">👜 Pastimes</a>
    <div class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="Clothes.php"  class="nav-link">Shop</a>
            <a href="logout.php"   class="nav-link-orange nav-link">Logout</a>
        <?php else: ?>
            <a href="Login.php"    class="nav-link">Login</a>
            <a href="Register.php" class="nav-link-orange nav-link">Register</a>
            <!-- ✅ FIX: admin_login.php is in root wede2/ — same folder as index.php -->
            <a href="admin_login.php" class="nav-link">Admin</a>
        <?php endif; ?>
    </div>
</nav>

<!-- ── Logged-in user bar ─────────────────────────────────────── -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="user-bar">
    <span>👋 Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
    <a href="logout.php">Logout</a>
</div>
<?php endif; ?>

<!-- ── Page content ───────────────────────────────────────────── -->
<div class="page">

    <!-- Hero section -->
    <div class="hero">
        <span class="hero-icon">👜</span>
        <h1>Welcome to <span>Pastimes</span></h1>
        <p>
            Buy and sell premium second-hand branded clothing.
            Quality fashion at affordable prices —
            sustainably sourced, community driven.
        </p>
        <div class="hero-btns">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="Register.php" class="btn-primary">Get Started</a>
                <a href="Login.php"    class="btn-outline">Login</a>
            <?php else: ?>
                <a href="Clothes.php" class="btn-primary">Browse Items</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feature highlight cards -->
    <div class="features">
        <div class="feature-card">
            <span class="feature-icon">👗</span>
            <h3>Curated Fashion</h3>
            <p>Every item is quality-checked by our admin team before it is listed in the store.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🔒</span>
            <h3>Secure Login</h3>
            <p>Your data is protected with hashed passwords and PHP session management.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">✅</span>
            <h3>Verified Users</h3>
            <p>Admins verify every new registration before access to the shop is granted.</p>
        </div>
    </div>

    <!-- Stats bar -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-num">500+</div>
            <div class="stat-lbl">Items Listed</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">1.2k</div>
            <div class="stat-lbl">Happy Customers</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">100%</div>
            <div class="stat-lbl">Verified Sellers</div>
        </div>
    </div>

</div>

<!-- ── Footer ─────────────────────────────────────────────────── -->
<footer>
    <p>&copy; <?= date('Y') ?> Pastimes ClothingStore. All rights reserved.</p>
</footer>

</body>
</html>