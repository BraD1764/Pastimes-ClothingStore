<?php
/**
 * messages.php
 * Buyer↔Seller chat for a specific clothing item.
 *
 * URL params:
 *   ?item_id=<ClothesID>   — which item the conversation is about
 *   ?seller=<username>     — seller display name (optional, falls back to item data)
 *
 * DB table required — run this once in phpMyAdmin:
 *
 *   CREATE TABLE IF NOT EXISTS tblMessages (
 *       MessageID   INT AUTO_INCREMENT PRIMARY KEY,
 *       ClothesID   INT          NOT NULL,
 *       SenderID    INT          NOT NULL,
 *       SenderName  VARCHAR(120) NOT NULL,
 *       Message     TEXT         NOT NULL,
 *       SentAt      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *       IsRead      TINYINT(1)   NOT NULL DEFAULT 0,
 *       INDEX idx_clothes (ClothesID),
 *       INDEX idx_sender  (SenderID)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * SAVE TO: C:\xampp\htdocs\wede2\messages.php
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/DBConn.php';
$conn = getDBConnection();

$itemId    = (int)($_GET['item_id'] ?? 0);
$userId    = (int)$_SESSION['user_id'];
$userName  = $_SESSION['user_name'] ?? 'You';

// ── Ensure messages table exists ─────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS tblMessages (
    MessageID   INT AUTO_INCREMENT PRIMARY KEY,
    ClothesID   INT          NOT NULL,
    SenderID    INT          NOT NULL,
    SenderName  VARCHAR(120) NOT NULL,
    Message     TEXT         NOT NULL,
    SentAt      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    IsRead      TINYINT(1)   NOT NULL DEFAULT 0,
    INDEX idx_clothes (ClothesID),
    INDEX idx_sender  (SenderID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Handle AJAX: send a message ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $msg = trim($_POST['message'] ?? '');
    if ($itemId > 0 && $msg !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO tblMessages (ClothesID, SenderID, SenderName, Message) VALUES (?,?,?,?)"
        );
        $stmt->bind_param('iiss', $itemId, $userId, $userName, $msg);
        $stmt->execute();
        $newId = $conn->insert_id;

        // Return JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'success'    => true,
            'messageId'  => $newId,
            'senderName' => $userName,
            'message'    => htmlspecialchars($msg),
            'sentAt'     => date('g:i A'),
            'isSelf'     => true,
        ]);
        $conn->close();
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    $conn->close();
    exit;
}

// ── Handle AJAX: poll for new messages ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['poll_after'])) {
    $afterId = (int)$_GET['poll_after'];
    $stmt = $conn->prepare(
        "SELECT MessageID, SenderID, SenderName, Message,
                DATE_FORMAT(SentAt,'%l:%i %p') AS SentAt
         FROM tblMessages
         WHERE ClothesID = ? AND MessageID > ?
         ORDER BY SentAt ASC"
    );
    $stmt->bind_param('ii', $itemId, $afterId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Mark own flag
    foreach ($rows as &$r) {
        $r['isSelf'] = ((int)$r['SenderID'] === $userId);
        $r['message'] = htmlspecialchars($r['Message']);
        unset($r['Message']);
    }

    header('Content-Type: application/json');
    echo json_encode($rows);
    $conn->close();
    exit;
}

// ── Fetch item info ───────────────────────────────────────────────────────────
$item = null;
if ($itemId > 0) {
    $stmt = $conn->prepare(
        "SELECT ClothesID, ItemName, Category, Size, SellPrice, ImageFile FROM tblClothes WHERE ClothesID = ?"
    );
    $stmt->bind_param('i', $itemId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
}

if (!$item) {
    // Invalid item — redirect back
    header('Location: clothes.php');
    exit;
}

// ── Load existing messages ────────────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT MessageID, SenderID, SenderName, Message,
            DATE_FORMAT(SentAt,'%l:%i %p') AS SentAt
     FROM tblMessages
     WHERE ClothesID = ?
     ORDER BY SentAt ASC"
);
$stmt->bind_param('i', $itemId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$lastId    = !empty($messages) ? (int)end($messages)['MessageID'] : 0;
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

$conn->close();

// Emoji map
$categoryEmoji = [
    'Jackets' => '🧥','Dresses' => '👗','Pants' => '👖',
    'Tops'    => '👕','Shoes'   => '👟','Accessories' => '👜',
];
$emoji = $categoryEmoji[$item['Category']] ?? '👕';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Pastimes</title>
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
            --bg:          #f0f2f8;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #e8eaf6 0%, #f0e6f6 50%, #e6f0ff 100%);
            min-height: 100vh;
            color: var(--text);
        }

        /* ── Rainbow bar ─────────────────────────────────────── */
        .rainbow-bar {
            height: 4px;
            background: linear-gradient(90deg,
                #ff6b6b 0%,#ffa94d 15%,#ffd43b 30%,
                #69db7c 45%,#4dabf7 60%,#748ffc 75%,
                #da77f2 90%,#ff6b6b 100%);
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
        }
        .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            font-size: .88rem;
        }
        .cart-btn {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            color: var(--text);
            text-decoration: none;
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
            width: 18px; height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nav-user { font-weight: 600; color: var(--text); }

        /* ── Page layout ─────────────────────────────────────── */
        .page {
            max-width: 760px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            animation: fadeUp .4s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* ── Item summary strip ──────────────────────────────── */
        .item-strip {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 1rem 1.4rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
        }
        .item-strip .emoji { font-size: 2.4rem; }
        .item-strip .info  { flex: 1; }
        .item-strip .name  { font-weight: 700; font-size: 1rem; }
        .item-strip .meta  { font-size: .78rem; color: var(--muted); margin-top: .1rem; }
        .item-strip .price { font-weight: 700; color: var(--orange); font-size: 1.2rem; white-space: nowrap; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            padding: .3rem .6rem;
            border-radius: 8px;
            transition: background .15s, color .15s;
        }
        .back-link:hover { background: var(--orange-lite); color: var(--orange); }

        /* ── Chat window ─────────────────────────────────────── */
        .chat-window {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 24px rgba(0,0,0,.07);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 520px;
        }

        /* Header */
        .chat-header {
            background: linear-gradient(90deg, var(--orange) 0%, #f07a3a 100%);
            color: #fff;
            padding: 1rem 1.4rem;
            display: flex;
            align-items: center;
            gap: .7rem;
            flex-shrink: 0;
        }
        .chat-header .dot {
            width: 10px; height: 10px;
            background: #fff;
            border-radius: 50%;
            opacity: .85;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%,100% { opacity:.85; transform:scale(1); }
            50%      { opacity:.4;  transform:scale(1.3); }
        }
        .chat-header .title {
            font-weight: 700;
            font-size: .98rem;
            flex: 1;
        }
        .chat-header .close-btn {
            background: rgba(255,255,255,.2);
            border: none;
            color: #fff;
            width: 28px; height: 28px;
            border-radius: 50%;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background .2s;
        }
        .chat-header .close-btn:hover { background: rgba(255,255,255,.35); }

        /* Message list */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.2rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: .6rem;
            scroll-behavior: smooth;
        }
        .chat-messages::-webkit-scrollbar { width: 4px; }
        .chat-messages::-webkit-scrollbar-track { background: transparent; }
        .chat-messages::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }

        /* Empty state */
        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            gap: .5rem;
            font-size: .88rem;
        }
        .chat-empty .icon { font-size: 2.5rem; }

        /* Message bubble row */
        .msg-row {
            display: flex;
            flex-direction: column;
            max-width: 72%;
            animation: msgIn .25s cubic-bezier(.22,.68,0,1.2) both;
        }
        @keyframes msgIn {
            from { opacity:0; transform:translateY(8px) scale(.97); }
            to   { opacity:1; transform:translateY(0)   scale(1); }
        }

        /* Received (left) */
        .msg-row.received { align-self: flex-start; align-items: flex-start; }
        .msg-row.received .bubble {
            background: #f0f2f8;
            color: var(--text);
            border-radius: 4px 18px 18px 18px;
        }

        /* Sent (right) */
        .msg-row.sent { align-self: flex-end; align-items: flex-end; }
        .msg-row.sent .bubble {
            background: linear-gradient(135deg, var(--orange) 0%, #f07a3a 100%);
            color: #fff;
            border-radius: 18px 18px 4px 18px;
        }

        .bubble {
            padding: .65rem 1rem;
            font-size: .88rem;
            line-height: 1.5;
            word-break: break-word;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }

        .msg-meta {
            font-size: .68rem;
            color: var(--muted);
            margin-top: .25rem;
            padding: 0 .3rem;
        }
        .msg-row.sent .msg-meta { text-align: right; }

        /* Typing indicator */
        .typing-indicator {
            align-self: flex-start;
            background: #f0f2f8;
            border-radius: 4px 18px 18px 18px;
            padding: .6rem 1rem;
            display: none;
        }
        .typing-indicator span {
            display: inline-block;
            width: 6px; height: 6px;
            background: var(--muted);
            border-radius: 50%;
            margin: 0 1px;
            animation: bounce .9s infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: .15s; }
        .typing-indicator span:nth-child(3) { animation-delay: .3s; }
        @keyframes bounce {
            0%,60%,100% { transform:translateY(0); }
            30%          { transform:translateY(-5px); }
        }

        /* ── Chat input bar ──────────────────────────────────── */
        .chat-input-bar {
            border-top: 1px solid var(--border);
            padding: .9rem 1.2rem;
            display: flex;
            gap: .7rem;
            align-items: center;
            flex-shrink: 0;
            background: var(--white);
        }
        .chat-input {
            flex: 1;
            background: #f5f5fa;
            border: 1.5px solid var(--border);
            border-radius: 999px;
            padding: .65rem 1.2rem;
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            resize: none;
        }
        .chat-input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(232,98,42,.12);
        }
        .chat-input::placeholder { color: #bbb; }

        .btn-send {
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .65rem 1.3rem;
            font-size: .88rem;
            font-weight: 700;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .1s;
            white-space: nowrap;
        }
        .btn-send:hover  { background: var(--orange-dark); transform: translateY(-1px); }
        .btn-send:active { transform: translateY(0); }
        .btn-send:disabled { opacity: .5; cursor: not-allowed; transform: none; }

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

        @media (max-width: 600px) {
            .page { padding: 0 .8rem; margin: 1.2rem auto; }
            .chat-window { height: 460px; }
            nav { padding: 0 1rem; }
        }
    </style>
</head>
<body>

<div class="rainbow-bar"></div>

<!-- ── Navbar ───────────────────────────────────────────────────── -->
<nav>
    <a class="nav-brand" href="index.php">Pastimes</a>
    <div class="nav-right">
        <a href="cart.php" class="cart-btn">
            🛒 Cart
            <?php if ($cartCount > 0): ?>
                <span class="cart-count"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
        <span class="nav-user">👤 <?= htmlspecialchars(explode(' ',$userName)[0]) ?></span>
        <a href="logout.php" style="font-size:.82rem;color:var(--muted);text-decoration:none;font-weight:600;">Logout</a>
    </div>
</nav>

<!-- ── Page ─────────────────────────────────────────────────────── -->
<div class="page">

    <!-- Back link -->
    <a class="back-link" href="clothes.php">← Back to Shop</a>
    <br><br>

    <!-- Item strip -->
    <div class="item-strip">
        <span class="emoji"><?= $emoji ?></span>
        <div class="info">
            <div class="name"><?= htmlspecialchars($item['ItemName']) ?></div>
            <div class="meta"><?= htmlspecialchars($item['Category']) ?> · Size <?= htmlspecialchars($item['Size']) ?></div>
        </div>
        <div class="price">R<?= number_format((float)$item['SellPrice'], 0) ?></div>
    </div>

    <!-- Chat window -->
    <div class="chat-window">

        <!-- Header -->
        <div class="chat-header">
            <span class="dot"></span>
            <span class="title">💬 Chat with Seller — <?= htmlspecialchars($item['ItemName']) ?></span>
            <a href="clothes.php" class="close-btn" title="Close">✕</a>
        </div>

        <!-- Messages -->
        <div class="chat-messages" id="chatMessages">

            <?php if (empty($messages)): ?>
                <div class="chat-empty" id="emptyState">
                    <span class="icon">💬</span>
                    <span>No messages yet — say hello!</span>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $m):
                    $isSelf = ((int)$m['SenderID'] === $userId);
                ?>
                <div class="msg-row <?= $isSelf ? 'sent' : 'received' ?>">
                    <div class="bubble"><?= htmlspecialchars($m['Message']) ?></div>
                    <div class="msg-meta">
                        <?= $isSelf ? 'You' : htmlspecialchars($m['SenderName']) ?>
                        · <?= htmlspecialchars($m['SentAt']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Typing dots (shown while sending) -->
            <div class="typing-indicator" id="typingIndicator">
                <span></span><span></span><span></span>
            </div>
        </div>

        <!-- Input bar -->
        <div class="chat-input-bar">
            <input
                type="text"
                class="chat-input"
                id="msgInput"
                placeholder="Type your message..."
                autocomplete="off"
                maxlength="1000"
            >
            <button class="btn-send" id="sendBtn" onclick="sendMessage()">Send</button>
        </div>
    </div>

</div>

<footer>
    <p>&copy; <?= date('Y') ?> Pastimes ClothingStore. All rights reserved.</p>
</footer>

<script>
    const ITEM_ID   = <?= $itemId ?>;
    let   lastMsgId = <?= $lastId ?>;

    const chatEl    = document.getElementById('chatMessages');
    const inputEl   = document.getElementById('msgInput');
    const sendBtn   = document.getElementById('sendBtn');
    const typingEl  = document.getElementById('typingIndicator');
    const emptyEl   = document.getElementById('emptyState');

    // Scroll to bottom of chat
    function scrollToBottom() {
        chatEl.scrollTop = chatEl.scrollHeight;
    }
    scrollToBottom();

    // Build a message bubble element
    function buildBubble(msg, sentAt, isSelf, senderName) {
        const row = document.createElement('div');
        row.className = 'msg-row ' + (isSelf ? 'sent' : 'received');

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.textContent = msg;

        const meta = document.createElement('div');
        meta.className = 'msg-meta';
        meta.textContent = (isSelf ? 'You' : senderName) + ' · ' + sentAt;

        row.appendChild(bubble);
        row.appendChild(meta);
        return row;
    }

    // Send a message
    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text) { inputEl.focus(); return; }

        inputEl.value = '';
        sendBtn.disabled = true;

        // Hide empty state
        if (emptyEl) emptyEl.style.display = 'none';

        // Show typing indicator
        typingEl.style.display = 'block';
        chatEl.insertBefore(typingEl, null); // move to end
        scrollToBottom();

        try {
            const fd = new FormData();
            fd.append('send_message', '1');
            fd.append('message', text);

            const resp = await fetch('messages.php?item_id=' + ITEM_ID, {
                method: 'POST',
                body: fd
            });
            const data = await resp.json();

            typingEl.style.display = 'none';

            if (data.success) {
                const now = new Date();
                const timeStr = now.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit', hour12:true });
                const el = buildBubble(text, timeStr, true, 'You');
                chatEl.insertBefore(el, typingEl);
                lastMsgId = data.messageId;
                scrollToBottom();
            }
        } catch (e) {
            typingEl.style.display = 'none';
            alert('Message failed. Please try again.');
        } finally {
            sendBtn.disabled = false;
            inputEl.focus();
        }
    }

    // Send on Enter (Shift+Enter = newline if textarea, but input = send)
    inputEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Poll for new messages every 3 seconds
    async function pollMessages() {
        try {
            const resp = await fetch(
                'messages.php?item_id=' + ITEM_ID + '&poll_after=' + lastMsgId,
                { method: 'GET' }
            );
            const rows = await resp.json();

            if (Array.isArray(rows) && rows.length > 0) {
                if (emptyEl) emptyEl.style.display = 'none';
                rows.forEach(r => {
                    // Don't add messages we sent ourselves (already added optimistically)
                    if (!r.isSelf) {
                        const el = buildBubble(r.message, r.SentAt, false, r.SenderName);
                        chatEl.insertBefore(el, typingEl);
                        scrollToBottom();
                    }
                    if (r.MessageID > lastMsgId) lastMsgId = r.MessageID;
                });
            }
        } catch (e) {
            // silent fail — just try again next poll
        }
    }

    // Start polling
    setInterval(pollMessages, 3000);
</script>

</body>
</html>