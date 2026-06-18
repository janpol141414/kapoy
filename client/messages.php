<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Message.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db           = (new Database())->getConnection();
$messageModel = new Message($db);
$engineerModel= new Engineer($db);

// All engineers for "New Chat" panel
$engineers = $engineerModel->getAll();

// Existing conversations
$contacts = $messageModel->getContacts($_SESSION['user_id']);

// Active conversation
$contactId   = intval($_GET['contact'] ?? 0);
$conversation = [];
$contactUser  = null;

if ($contactId) {
    $conversation = $messageModel->getConversation($_SESSION['user_id'], $contactId);
    $messageModel->markAsRead($contactId, $_SESSION['user_id']);

    $stmt = $db->prepare("SELECT u.id, u.name, u.profile_photo, u.role,
                                 e.availability_status, e.specialization
                          FROM users u
                          LEFT JOIN engineers e ON u.id = e.user_id
                          WHERE u.id = :id LIMIT 1");
    $stmt->execute([':id' => $contactId]);
    $contactUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Group messages by date
$grouped = [];
foreach ($conversation as $msg) {
    $day = date('Y-m-d', strtotime($msg['created_at']));
    $grouped[$day][] = $msg;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages – GeoSurvey Portal</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/messages.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>

<div class="app-layout">
    <?php include '../includes/sidebar_client.php'; ?>

    <main class="main-content no-padding">
        <div class="messages-layout">

            <!-- ═══════════════════════════════════════
                 LEFT PANEL — Contacts
            ═══════════════════════════════════════ -->
            <div class="contacts-panel" id="contactsPanel">

                <!-- Header -->
                <div class="contacts-header">
                    <h3><i class="fas fa-comments" style="color:#2d6a9f;margin-right:6px"></i>Messages</h3>
                    <button class="btn-new-chat" id="newChatBtn" title="New conversation">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>

                <!-- Search -->
                <div class="contacts-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="contactSearch" placeholder="Search conversations…">
                </div>

                <!-- New Chat Drawer -->
                <div class="new-chat-drawer" id="newChatDrawer">
                    <div class="new-chat-drawer-header">
                        <span>Start a new conversation</span>
                        <button onclick="closeNewChat()"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="new-chat-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="engSearch" placeholder="Search engineers…" oninput="filterEngineers(this.value)">
                    </div>
                    <div class="new-chat-list" id="engList">
                        <?php foreach ($engineers as $eng): ?>
                        <a href="messages.php?contact=<?= $eng['user_id'] ?>" class="new-chat-eng-item">
                            <div class="new-chat-eng-avatar">
                                <img src="<?= UPLOADS_URL ?>/profiles/<?= $eng['profile_photo'] ?? 'default_avatar.png' ?>"
                                     alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                                <span class="status-ring <?= $eng['availability_status'] ?>"></span>
                            </div>
                            <div class="new-chat-eng-info">
                                <strong><?= htmlspecialchars($eng['name']) ?></strong>
                                <span><?= htmlspecialchars($eng['specialization'] ?? 'Engineer') ?></span>
                            </div>
                            <span class="avail-pill <?= $eng['availability_status'] ?>">
                                <?= ucfirst($eng['availability_status']) ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Conversation List -->
                <div class="contacts-list" id="contactsList">
                    <?php if (empty($contacts)): ?>
                    <div class="contacts-empty">
                        <div class="contacts-empty-icon"><i class="fas fa-comment-slash"></i></div>
                        <p>No conversations yet</p>
                        <button class="btn-start-chat" onclick="openNewChat()">
                            <i class="fas fa-plus"></i> Message an Engineer
                        </button>
                    </div>
                    <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                    <a href="messages.php?contact=<?= $c['contact_id'] ?>"
                       class="contact-item <?= $contactId == $c['contact_id'] ? 'active' : '' ?>"
                       data-name="<?= htmlspecialchars(strtolower($c['contact_name'])) ?>">
                        <div class="contact-avatar">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $c['contact_photo'] ?? 'default_avatar.png' ?>"
                                 alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <?php if ($c['unread_count'] > 0): ?>
                            <span class="unread-dot"></span>
                            <?php endif; ?>
                        </div>
                        <div class="contact-info">
                            <div class="contact-name-row">
                                <strong><?= htmlspecialchars($c['contact_name']) ?></strong>
                                <span class="contact-time">
                                    <?= $c['last_message_time']
                                        ? date('h:i A', strtotime($c['last_message_time']))
                                        : '' ?>
                                </span>
                            </div>
                            <div class="contact-preview-row">
                                <p class="contact-last-msg">
                                    <?= htmlspecialchars(mb_substr($c['last_message'] ?? 'Start a conversation', 0, 38)) ?>…
                                </p>
                                <?php if ($c['unread_count'] > 0): ?>
                                <span class="unread-badge"><?= $c['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div><!-- /contacts-panel -->

            <!-- ═══════════════════════════════════════
                 RIGHT PANEL — Chat Area
            ═══════════════════════════════════════ -->
            <div class="chat-area" id="chatArea">

                <?php if (!$contactId || !$contactUser): ?>
                <!-- Empty state -->
                <div class="chat-welcome">
                    <div class="chat-welcome-inner">
                        <div class="chat-welcome-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h2>Your Messages</h2>
                        <p>Send and receive messages from licensed engineers.<br>Click a conversation or start a new one.</p>
                        <button class="btn-primary" onclick="openNewChat()">
                            <i class="fas fa-plus"></i> New Conversation
                        </button>
                    </div>
                </div>

                <?php else: ?>
                <!-- ── Chat Header ── -->
                <div class="chat-header">
                    <button class="chat-back-btn" id="chatBackBtn" onclick="history.back()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="chat-header-avatar">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $contactUser['profile_photo'] ?? 'default_avatar.png' ?>"
                             alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <?php
                        $avail = $contactUser['availability_status'] ?? 'offline';
                        ?>
                        <span class="chat-online-dot <?= $avail ?>"></span>
                    </div>
                    <div class="chat-header-info">
                        <strong><?= htmlspecialchars($contactUser['name']) ?></strong>
                        <span>
                            <?php if ($avail === 'available'): ?>
                            <i class="fas fa-circle" style="color:#10b981;font-size:8px"></i> Online
                            <?php elseif ($avail === 'busy'): ?>
                            <i class="fas fa-circle" style="color:#f59e0b;font-size:8px"></i> Busy
                            <?php else: ?>
                            <i class="fas fa-circle" style="color:#9ca3af;font-size:8px"></i> Offline
                            <?php endif; ?>
                            &nbsp;·&nbsp; <?= htmlspecialchars($contactUser['specialization'] ?? ucfirst($contactUser['role'])) ?>
                        </span>
                    </div>
                    <div class="chat-header-actions">
                        <a href="engineer-profile.php?id=<?= $contactId ?>"
                           class="chat-action-btn" title="View Profile">
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <a href="book-appointment.php?engineer_id=<?= $contactId ?>"
                           class="chat-action-btn" title="Book Appointment">
                            <i class="fas fa-calendar-plus"></i>
                        </a>
                    </div>
                </div>

                <!-- Pinned message banner -->
                <div class="pinned-message-banner" id="pinnedBanner" style="display:none" onclick="scrollToPinned()">
                    <i class="fas fa-thumbtack"></i>
                    <div><strong>Pinned:</strong> <span id="pinnedText"></span></div>
                    <button class="unpin-btn" onclick="event.stopPropagation();unpinMessage()"><i class="fas fa-times"></i></button>
                </div>

                <!-- ── Messages ── -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($grouped)): ?>
                    <div class="chat-empty-conv">
                        <div class="chat-empty-avatar">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $contactUser['profile_photo'] ?? 'default_avatar.png' ?>"
                                 alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        </div>
                        <strong><?= htmlspecialchars($contactUser['name']) ?></strong>
                        <p>Say hello! This is the beginning of your conversation.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($grouped as $day => $msgs): ?>
                    <div class="date-divider">
                        <span><?= date('F d, Y', strtotime($day)) === date('F d, Y') ? 'Today' : (date('F d, Y', strtotime($day)) === date('F d, Y', strtotime('-1 day')) ? 'Yesterday' : date('F d, Y', strtotime($day))) ?></span>
                    </div>
                    <?php foreach ($msgs as $msg):
                        $isSent = ($msg['sender_id'] == $_SESSION['user_id']);
                        $msgText = htmlspecialchars($msg['message'], ENT_QUOTES);
                        $msgPreview = htmlspecialchars(substr($msg['message'], 0, 60), ENT_QUOTES);
                    ?>
                    <div class="message-wrapper <?= $isSent ? 'sent' : 'received' ?>" data-id="<?= $msg['id'] ?>" data-text="<?= $msgText ?>">
                        <?php if (!$isSent): ?>
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $msg['sender_photo'] ?? 'default_avatar.png' ?>"
                             alt="" class="msg-avatar"
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <?php endif; ?>
                        <div class="message-bubble-wrapper">
                            <?php if ($msg['is_ai_reply']): ?>
                            <div class="ai-badge"><i class="fas fa-robot"></i> AI Reply</div>
                            <?php endif; ?>
                            <div class="message-bubble">
                                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                            </div>
                            <div class="msg-meta">
                                <span class="msg-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                                <?php if ($isSent): ?>
                                <i class="fas fa-check-double msg-tick"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Action toolbar (shown on hover) — no reactions -->
                        <div class="message-actions-toolbar">
                            <button class="msg-action-btn" title="Copy" onclick="copyMessage('<?= $msgText ?>')"><i class="fas fa-copy"></i></button>
                            <button class="msg-action-btn" title="Forward" onclick="openForwardModal(<?= $msg['id'] ?>,'<?= $msgText ?>')"><i class="fas fa-share"></i></button>
                            <button class="msg-action-btn pin-btn" title="Pin" onclick="pinMessage(<?= $msg['id'] ?>,this,'<?= $msgPreview ?>')"><i class="fas fa-thumbtack"></i></button>
                            <?php if ($isSent): ?>
                            <button class="msg-action-btn delete-btn" title="Delete" onclick="confirmDelete(<?= $msg['id'] ?>,this)"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Typing indicator (hidden by default) -->
                    <div class="typing-indicator" id="typingIndicator" style="display:none">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $contactUser['profile_photo'] ?? 'default_avatar.png' ?>"
                             alt="" class="msg-avatar"
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <div class="typing-bubble">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>

                <!-- ── Input Bar ── -->
                <div class="chat-input-area">
                    <!-- Attachment preview strip -->
                    <div class="attachment-preview" id="attachmentPreview" style="display:none"></div>

                    <div class="chat-input-wrapper">
                        <!-- Emoji -->
                        <button class="chat-emoji-btn" id="emojiBtn" title="Emoji">
                            <i class="far fa-smile"></i>
                        </button>

                        <!-- Attach file (hidden input) -->
                        <input type="file" id="fileInput" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" style="display:none" onchange="handleFileSelect(this)">
                        <button class="chat-attach-btn" title="Attach file / image" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-paperclip"></i>
                        </button>

                        <!-- Text input -->
                        <textarea id="messageInput"
                                  placeholder="Type a message…"
                                  rows="1"
                                  oninput="autoResize(this); toggleSendVoice(this.value)"
                                  onkeydown="handleKey(event)"></textarea>

                        <!-- Send button (shown when text exists) -->
                        <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()" title="Send">
                            <i class="fas fa-paper-plane"></i>
                        </button>

                        <!-- Voice button (shown when input is empty) -->
                        <button class="chat-voice-btn" id="voiceBtn" title="Voice message" onmousedown="startRecording()" onmouseup="stopRecording()" ontouchstart="startRecording()" ontouchend="stopRecording()">
                            <i class="fas fa-microphone"></i>
                        </button>
                    </div>

                    <!-- Emoji picker -->
                    <div class="emoji-picker" id="emojiPicker">
                        <?php
                        $emojis = ['😊','😂','👍','❤️','🙏','✅','📍','📅','🔧','⭐','👋','💬','📞','🗺️','🏗️','📐','📏','🌟','💪','🎉','😎','🤝','👏','🔥','💯','📋','🏠','🌏','⚡','🎯'];
                        foreach ($emojis as $e): ?>
                        <button onclick="insertEmoji('<?= $e ?>')"><?= $e ?></button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Recording indicator -->
                    <div class="recording-indicator" id="recordingIndicator" style="display:none">
                        <div class="recording-dot"></div>
                        <span>Recording… <span id="recordingTimer">0:00</span></span>
                        <button onclick="cancelRecording()" class="recording-cancel"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /chat-area -->
        </div><!-- /messages-layout -->
    </main>
</div>

<script>
const CURRENT_USER_ID = <?= $_SESSION['user_id'] ?>;
const CONTACT_ID      = <?= $contactId ?>;
const BASE_URL        = '<?= BASE_URL ?>';
let lastMessageId     = <?= !empty($conversation) ? (int)end($conversation)['id'] : 0 ?>;
let pollTimer         = null;

/* ── Auto-resize textarea ── */
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

/* ── Toggle send/voice button based on input ── */
function toggleSendVoice(val) {
    const sendBtn  = document.getElementById('sendBtn');
    const voiceBtn = document.getElementById('voiceBtn');
    if (!sendBtn || !voiceBtn) return;
    if (val.trim().length > 0 || pendingFiles.length > 0) {
        sendBtn.style.display  = 'flex';
        voiceBtn.style.display = 'none';
    } else {
        sendBtn.style.display  = 'none';
        voiceBtn.style.display = 'flex';
    }
}

/* ── Enter to send, Shift+Enter for newline ── */
function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

/* ── Send text message ── */
function sendMessage() {
    const input = document.getElementById('messageInput');
    const text  = input.value.trim();

    // If files are pending, send them first
    if (pendingFiles.length > 0) {
        sendFiles();
        return;
    }

    if (!text || !CONTACT_ID) return;

    input.value = '';
    input.style.height = 'auto';
    toggleSendVoice('');

    appendMessage(text, true, null);

    fetch(BASE_URL + '/api/messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'send', receiver_id: CONTACT_ID, message: text})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            lastMessageId = data.id;
            if (data.ai_reply) {
                showTyping();
                setTimeout(() => {
                    hideTyping();
                    appendMessage(data.ai_reply, false, data.ai_id, true);
                    lastMessageId = data.ai_id;
                }, 1200);
            }
        }
    });
}

/* ── Append a text message bubble ── */
function appendMessage(text, isSent, id, isAI = false) {
    const container = document.getElementById('chatMessages');
    if (!container) return;

    const wrap = document.createElement('div');
    wrap.className = 'message-wrapper ' + (isSent ? 'sent' : 'received');
    if (id) wrap.dataset.id = id;

    const tick     = isSent ? '<i class="fas fa-check-double msg-tick"></i>' : '';
    const aiBadge  = isAI   ? '<div class="ai-badge"><i class="fas fa-robot"></i> AI Reply</div>' : '';

    wrap.innerHTML = `
        <div class="message-bubble-wrapper">
            ${aiBadge}
            <div class="message-bubble"><p>${escHtml(text).replace(/\n/g,'<br>')}</p></div>
            <div class="msg-meta"><span class="msg-time">${timeNow()}</span>${tick}</div>
        </div>`;
    container.appendChild(wrap);
    container.scrollTop = container.scrollHeight;
}

/* ── Append a media/file bubble ── */
function appendMediaMessage(html, isSent) {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    const wrap = document.createElement('div');
    wrap.className = 'message-wrapper ' + (isSent ? 'sent' : 'received');
    const tick = isSent ? '<i class="fas fa-check-double msg-tick"></i>' : '';
    wrap.innerHTML = `<div class="message-bubble-wrapper"><div class="message-bubble media-bubble">${html}</div><div class="msg-meta"><span class="msg-time">${timeNow()}</span>${tick}</div></div>`;
    container.appendChild(wrap);
    container.scrollTop = container.scrollHeight;
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function timeNow() {
    const d = new Date(), h = d.getHours(), m = d.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
    return (h % 12 || 12) + ':' + String(m).padStart(2,'0') + ' ' + ap;
}

/* ── Typing indicator ── */
function showTyping() { const t = document.getElementById('typingIndicator'); if (t) { t.style.display = 'flex'; scrollBottom(); } }
function hideTyping()  { const t = document.getElementById('typingIndicator'); if (t) t.style.display = 'none'; }
function scrollBottom(){ const c = document.getElementById('chatMessages'); if (c) c.scrollTop = c.scrollHeight; }

/* ── Poll for new messages ── */
function pollMessages() {
    if (!CONTACT_ID) return;
    fetch(BASE_URL + '/api/messages.php?action=poll&contact_id=' + CONTACT_ID + '&last_id=' + lastMessageId)
        .then(r => r.json())
        .then(data => {
            if (!data.messages || !data.messages.length) return;
            data.messages.forEach(msg => {
                if (parseInt(msg.sender_id) !== CURRENT_USER_ID) {
                    if (msg.message.startsWith('[IMAGE]')) {
                        appendMediaMessage(`<img src="${BASE_URL}/uploads/messages/${msg.message.replace('[IMAGE]','')}" class="msg-image" onclick="openLightbox(this.src)">`, false);
                    } else if (msg.message.startsWith('[FILE]')) {
                        const parts = msg.message.replace('[FILE]','').split('|');
                        appendMediaMessage(`<a href="${BASE_URL}/uploads/messages/${parts[0]}" target="_blank" class="msg-file"><i class="fas fa-file"></i> ${escHtml(parts[1] || parts[0])}</a>`, false);
                    } else if (msg.message.startsWith('[VOICE]')) {
                        appendMediaMessage(`<div class="msg-voice"><i class="fas fa-microphone"></i><audio controls src="${BASE_URL}/uploads/messages/${msg.message.replace('[VOICE]','')}"></audio></div>`, false);
                    } else {
                        appendMessage(msg.message, false, msg.id, msg.is_ai_reply == 1);
                    }
                    lastMessageId = msg.id;
                }
            });
        })
        .catch(() => {});
}
if (CONTACT_ID) pollTimer = setInterval(pollMessages, 3000);

/* ══════════════════════════════════════════
   FILE / IMAGE ATTACHMENTS
   ══════════════════════════════════════════ */
let pendingFiles = [];

function handleFileSelect(input) {
    const files = Array.from(input.files);
    if (!files.length) return;
    pendingFiles = files;

    const preview = document.getElementById('attachmentPreview');
    preview.style.display = 'flex';
    preview.innerHTML = '';

    files.forEach((file, i) => {
        const item = document.createElement('div');
        item.className = 'attach-preview-item';

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                item.innerHTML = `<img src="${e.target.result}" alt="${escHtml(file.name)}">
                    <button onclick="removePendingFile(${i})"><i class="fas fa-times"></i></button>`;
            };
            reader.readAsDataURL(file);
        } else {
            item.innerHTML = `<div class="attach-file-icon"><i class="fas fa-file"></i></div>
                <span>${escHtml(file.name.substring(0,20))}</span>
                <button onclick="removePendingFile(${i})"><i class="fas fa-times"></i></button>`;
        }
        preview.appendChild(item);
    });

    toggleSendVoice('x'); // show send button
    input.value = ''; // reset so same file can be re-selected
}

function removePendingFile(i) {
    pendingFiles.splice(i, 1);
    if (pendingFiles.length === 0) {
        document.getElementById('attachmentPreview').style.display = 'none';
        toggleSendVoice('');
    } else {
        handleFileSelect({ files: pendingFiles }); // re-render
    }
}

function sendFiles() {
    if (!pendingFiles.length || !CONTACT_ID) return;

    pendingFiles.forEach(file => {
        const formData = new FormData();
        formData.append('action', 'send_file');
        formData.append('receiver_id', CONTACT_ID);
        formData.append('file', file);

        // Optimistic preview
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => appendMediaMessage(`<img src="${e.target.result}" class="msg-image" onclick="openLightbox(this.src)">`, true);
            reader.readAsDataURL(file);
        } else {
            appendMediaMessage(`<div class="msg-file"><i class="fas fa-file"></i> ${escHtml(file.name)}</div>`, true);
        }

        fetch(BASE_URL + '/api/messages.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => { if (data.success) lastMessageId = data.id; })
            .catch(() => {});
    });

    pendingFiles = [];
    document.getElementById('attachmentPreview').style.display = 'none';
    toggleSendVoice('');
}

/* ── Image lightbox ── */
function openLightbox(src) {
    const lb = document.createElement('div');
    lb.className = 'msg-lightbox';
    lb.innerHTML = `<div class="msg-lightbox-inner"><img src="${src}"><button onclick="this.closest('.msg-lightbox').remove()"><i class="fas fa-times"></i></button></div>`;
    lb.addEventListener('click', e => { if (e.target === lb) lb.remove(); });
    document.body.appendChild(lb);
}

/* ══════════════════════════════════════════
   VOICE MESSAGES
   ══════════════════════════════════════════ */
let mediaRecorder = null;
let audioChunks   = [];
let recordingTimer = null;
let recordingSeconds = 0;

function startRecording() {
    if (!navigator.mediaDevices) {
        alert('Voice messages require microphone access. Please allow microphone permission.');
        return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            audioChunks = [];
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
            mediaRecorder.onstop = () => {
                stream.getTracks().forEach(t => t.stop());
                const blob = new Blob(audioChunks, { type: 'audio/webm' });
                sendVoiceMessage(blob);
            };
            mediaRecorder.start();

            // Show recording UI
            document.getElementById('recordingIndicator').style.display = 'flex';
            document.getElementById('voiceBtn').classList.add('recording');
            recordingSeconds = 0;
            recordingTimer = setInterval(() => {
                recordingSeconds++;
                const m = Math.floor(recordingSeconds / 60);
                const s = recordingSeconds % 60;
                document.getElementById('recordingTimer').textContent = m + ':' + String(s).padStart(2,'0');
            }, 1000);
        })
        .catch(() => alert('Could not access microphone. Please check your browser permissions.'));
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        clearInterval(recordingTimer);
        document.getElementById('recordingIndicator').style.display = 'none';
        document.getElementById('voiceBtn').classList.remove('recording');
    }
}

function cancelRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.ondataavailable = null;
        mediaRecorder.onstop = null;
        mediaRecorder.stop();
    }
    clearInterval(recordingTimer);
    audioChunks = [];
    document.getElementById('recordingIndicator').style.display = 'none';
    document.getElementById('voiceBtn').classList.remove('recording');
}

function sendVoiceMessage(blob) {
    if (!CONTACT_ID) return;
    const formData = new FormData();
    formData.append('action', 'send_file');
    formData.append('receiver_id', CONTACT_ID);
    formData.append('file', blob, 'voice_' + Date.now() + '.webm');
    formData.append('is_voice', '1');

    // Optimistic UI
    const url = URL.createObjectURL(blob);
    appendMediaMessage(`<div class="msg-voice"><i class="fas fa-microphone"></i><audio controls src="${url}"></audio></div>`, true);

    fetch(BASE_URL + '/api/messages.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (data.success) lastMessageId = data.id; })
        .catch(() => {});
}

/* ── Emoji picker ── */
document.getElementById('emojiBtn')?.addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('emojiPicker').classList.toggle('show');
});
function insertEmoji(e) {
    const inp = document.getElementById('messageInput');
    inp.value += e;
    inp.focus();
    toggleSendVoice(inp.value);
    document.getElementById('emojiPicker').classList.remove('show');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('#emojiBtn') && !e.target.closest('#emojiPicker')) {
        document.getElementById('emojiPicker')?.classList.remove('show');
    }
});

/* ── New chat drawer ── */
function openNewChat() { document.getElementById('newChatDrawer').classList.add('show'); document.getElementById('engSearch')?.focus(); }
function closeNewChat() { document.getElementById('newChatDrawer').classList.remove('show'); }
document.getElementById('newChatBtn')?.addEventListener('click', openNewChat);
function filterEngineers(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.new-chat-eng-item').forEach(el => {
        el.style.display = el.querySelector('strong').textContent.toLowerCase().includes(q) ? 'flex' : 'none';
    });
}

/* ── Contact search ── */
document.getElementById('contactSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.contact-item').forEach(el => {
        el.style.display = el.dataset.name?.includes(q) ? 'flex' : 'none';
    });
});

/* ── Init ── */
scrollBottom();
toggleSendVoice(''); // show voice button initially

/* ══════════════════════════════════════════
   MESSAGE ACTIONS — React, Copy, Forward, Pin, Delete
   ══════════════════════════════════════════ */

// ── Reaction picker ──────────────────────
function toggleReactionPicker(msgId, btn) {
    // Close all other pickers first
    document.querySelectorAll('.emoji-reaction-picker.show').forEach(p => {
        if (p.id !== 'rp-' + msgId) p.classList.remove('show');
    });
    document.getElementById('rp-' + msgId)?.classList.toggle('show');
    event.stopPropagation();
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.react-btn') && !e.target.closest('.emoji-reaction-picker')) {
        document.querySelectorAll('.emoji-reaction-picker.show').forEach(p => p.classList.remove('show'));
    }
});

// ── Add reaction ─────────────────────────
const _reactions = {}; // msgId -> {emoji: count}

function addReaction(msgId, emoji) {
    document.getElementById('rp-' + msgId)?.classList.remove('show');

    if (!_reactions[msgId]) _reactions[msgId] = {};
    _reactions[msgId][emoji] = (_reactions[msgId][emoji] || 0) + 1;

    renderReactions(msgId);
}

function renderReactions(msgId) {
    const container = document.getElementById('reactions-' + msgId);
    if (!container) return;
    const reacts = _reactions[msgId] || {};
    container.innerHTML = Object.entries(reacts)
        .map(([em, cnt]) => `<span class="reaction-chip" onclick="addReaction(${msgId},'${em}')">
            ${em} <span class="reaction-count">${cnt}</span>
        </span>`).join('');
}

// ── Copy message ─────────────────────────
function copyMessage(text) {
    // Decode HTML entities
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showCopyToast('Message copied!');
}

function showCopyToast(msg) {
    let toast = document.getElementById('copyToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'copyToast';
        toast.className = 'copy-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2000);
}

// ── Forward message ───────────────────────
let _forwardText = '';

function openForwardModal(msgId, text) {
    _forwardText = text;
    let modal = document.getElementById('forwardModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'forwardModal';
        modal.className = 'forward-modal-overlay';
        modal.innerHTML = `
            <div class="forward-modal">
                <div class="forward-modal-header">
                    <h4><i class="fas fa-share" style="margin-right:8px;color:#1a3c5e"></i>Forward to…</h4>
                    <button onclick="document.getElementById('forwardModal').remove()"><i class="fas fa-times"></i></button>
                </div>
                <div class="forward-contact-list" id="forwardContactList"></div>
            </div>`;
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    }

    // Populate with existing contacts
    const list = document.getElementById('forwardContactList');
    list.innerHTML = '';
    document.querySelectorAll('.contact-item').forEach(item => {
        const href = item.getAttribute('href');
        const contactId = href?.split('contact=')[1];
        const name = item.querySelector('.contact-name-row strong')?.textContent || 'Contact';
        const img  = item.querySelector('img')?.src || '';
        if (!contactId) return;

        const div = document.createElement('div');
        div.className = 'forward-contact-item';
        div.innerHTML = `<img src="${img}" onerror="this.src='${BASE_URL}/assets/images/default_avatar.png'">
            <div><strong>${escHtml(name)}</strong><span>Tap to forward</span></div>`;
        div.addEventListener('click', () => {
            forwardTo(contactId, _forwardText);
            document.getElementById('forwardModal')?.remove();
        });
        list.appendChild(div);
    });

    if (!list.children.length) {
        list.innerHTML = '<div style="padding:24px;text-align:center;color:#8696a0;font-size:13px">No conversations to forward to.</div>';
    }
}

function forwardTo(receiverId, text) {
    const fwdText = '↪ Forwarded: ' + text;
    fetch(BASE_URL + '/api/messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'send', receiver_id: parseInt(receiverId), message: fwdText})
    }).then(() => showCopyToast('Message forwarded!'));
}

// ── Pin message ───────────────────────────
let _pinnedMsgId = null;

function pinMessage(msgId, btn, preview) {
    // Unpin previous
    if (_pinnedMsgId) {
        document.querySelector(`.message-wrapper[data-id="${_pinnedMsgId}"]`)?.classList.remove('pinned');
    }

    if (_pinnedMsgId === msgId) {
        // Toggle off
        _pinnedMsgId = null;
        document.getElementById('pinnedBanner').style.display = 'none';
        btn.classList.remove('pinned');
        return;
    }

    _pinnedMsgId = msgId;
    document.querySelector(`.message-wrapper[data-id="${msgId}"]`)?.classList.add('pinned');
    btn.classList.add('pinned');

    const banner = document.getElementById('pinnedBanner');
    document.getElementById('pinnedText').textContent = preview + (preview.length >= 60 ? '…' : '');
    banner.style.display = 'flex';
}

function unpinMessage() {
    if (_pinnedMsgId) {
        document.querySelector(`.message-wrapper[data-id="${_pinnedMsgId}"]`)?.classList.remove('pinned');
        document.querySelector(`.pin-btn.pinned`)?.classList.remove('pinned');
        _pinnedMsgId = null;
    }
    document.getElementById('pinnedBanner').style.display = 'none';
}

function scrollToPinned() {
    if (!_pinnedMsgId) return;
    const el = document.querySelector(`.message-wrapper[data-id="${_pinnedMsgId}"]`);
    if (el) {
        el.scrollIntoView({behavior: 'smooth', block: 'center'});
        el.style.outline = '2px solid #1a3c5e';
        setTimeout(() => el.style.outline = '', 1500);
    }
}

// ── Delete message ────────────────────────
function confirmDelete(msgId, btn) {
    const overlay = document.createElement('div');
    overlay.className = 'delete-confirm-overlay';
    overlay.innerHTML = `
        <div class="delete-confirm-box">
            <div style="font-size:36px;margin-bottom:12px">🗑️</div>
            <h4>Delete Message?</h4>
            <p>This message will be removed from your view. The other person may still see it.</p>
            <div class="delete-confirm-actions">
                <button class="btn-delete-cancel" onclick="this.closest('.delete-confirm-overlay').remove()">Cancel</button>
                <button class="btn-delete-confirm" onclick="doDelete(${msgId}, this)">Delete</button>
            </div>
        </div>`;
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
}

function doDelete(msgId, btn) {
    btn.closest('.delete-confirm-overlay').remove();
    const wrapper = document.querySelector(`.message-wrapper[data-id="${msgId}"]`);
    if (!wrapper) return;

    // Visual: replace bubble with deleted placeholder
    const bubble = wrapper.querySelector('.message-bubble');
    if (bubble) {
        bubble.innerHTML = '<p class="message-deleted"><i class="fas fa-ban"></i> You deleted this message</p>';
        bubble.style.background = '#f1f5f9';
    }
    wrapper.querySelector('.message-actions-toolbar')?.remove();

    // API call to mark deleted (soft delete)
    fetch(BASE_URL + '/api/messages.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete_message', message_id: msgId})
    }).catch(() => {});
}
</script>
</body>
</html>
