<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Message.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db           = (new Database())->getConnection();
$messageModel = new Message($db);
$engineerModel= new Engineer($db);
$engineer     = $engineerModel->getByUserId($_SESSION['user_id']);

// Clients who have appointments with this engineer
$clientsStmt = $db->prepare(
    "SELECT DISTINCT u.id, u.name, u.profile_photo, 'client' as role
     FROM appointments a
     JOIN users u ON a.client_id = u.id
     WHERE a.engineer_id = :eid
     ORDER BY u.name"
);
$clientsStmt->execute([':eid' => $engineer['id'] ?? 0]);
$myClients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

$contacts = $messageModel->getContacts($_SESSION['user_id']);

$contactId   = intval($_GET['contact'] ?? 0);
$conversation = [];
$contactUser  = null;

if ($contactId) {
    $conversation = $messageModel->getConversation($_SESSION['user_id'], $contactId);
    $messageModel->markAsRead($contactId, $_SESSION['user_id']);
    $stmt = $db->prepare("SELECT id, name, profile_photo, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $contactId]);
    $contactUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

$grouped = [];
foreach ($conversation as $msg) {
    $day = date('Y-m-d', strtotime($msg['created_at']));
    $grouped[$day][] = $msg;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Messages – Engineer | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/messages.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content no-padding">
<div class="messages-layout">

    <!-- LEFT PANEL -->
    <div class="contacts-panel" id="contactsPanel">
        <div class="contacts-header">
            <h3><i class="fas fa-comments" style="color:#2d6a9f;margin-right:6px"></i>Messages</h3>
            <button class="btn-new-chat" id="newChatBtn" title="New conversation">
                <i class="fas fa-edit"></i>
            </button>
        </div>
        <div class="contacts-search">
            <i class="fas fa-search"></i>
            <input type="text" id="contactSearch" placeholder="Search conversations…">
        </div>

        <!-- New Chat Drawer -->
        <div class="new-chat-drawer" id="newChatDrawer">
            <div class="new-chat-drawer-header">
                <span>Message a Client</span>
                <button onclick="closeNewChat()"><i class="fas fa-times"></i></button>
            </div>
            <div class="new-chat-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="clientSearch" placeholder="Search clients…" oninput="filterClients(this.value)">
            </div>
            <div class="new-chat-list" id="clientList">
                <?php if (empty($myClients)): ?>
                <div style="padding:24px;text-align:center;color:#8696a0;font-size:13px">
                    No clients with appointments yet.
                </div>
                <?php else: foreach ($myClients as $cl): ?>
                <a href="messages.php?contact=<?= $cl['id'] ?>" class="new-chat-eng-item">
                    <div class="new-chat-eng-avatar">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $cl['profile_photo'] ?? 'default_avatar.png' ?>"
                             alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                    </div>
                    <div class="new-chat-eng-info">
                        <strong><?= htmlspecialchars($cl['name']) ?></strong>
                        <span>Client</span>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Contacts List -->
        <div class="contacts-list" id="contactsList">
            <?php if (empty($contacts)): ?>
            <div class="contacts-empty">
                <div class="contacts-empty-icon"><i class="fas fa-comment-slash"></i></div>
                <p>No conversations yet</p>
            </div>
            <?php else: foreach ($contacts as $c): ?>
            <a href="messages.php?contact=<?= $c['contact_id'] ?>"
               class="contact-item <?= $contactId == $c['contact_id'] ? 'active' : '' ?>"
               data-name="<?= htmlspecialchars(strtolower($c['contact_name'])) ?>">
                <div class="contact-avatar">
                    <img src="<?= UPLOADS_URL ?>/profiles/<?= $c['contact_photo'] ?? 'default_avatar.png' ?>"
                         alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                    <?php if ($c['unread_count'] > 0): ?><span class="unread-dot"></span><?php endif; ?>
                </div>
                <div class="contact-info">
                    <div class="contact-name-row">
                        <strong><?= htmlspecialchars($c['contact_name']) ?></strong>
                        <span class="contact-time"><?= $c['last_message_time'] ? date('h:i A', strtotime($c['last_message_time'])) : '' ?></span>
                    </div>
                    <div class="contact-preview-row">
                        <p class="contact-last-msg"><?= htmlspecialchars(mb_substr($c['last_message'] ?? 'Start a conversation', 0, 38)) ?>…</p>
                        <?php if ($c['unread_count'] > 0): ?>
                        <span class="unread-badge"><?= $c['unread_count'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="chat-area" id="chatArea">
        <?php if (!$contactId || !$contactUser): ?>
        <div class="chat-welcome">
            <div class="chat-welcome-inner">
                <div class="chat-welcome-icon"><i class="fas fa-comments"></i></div>
                <h2>Client Messages</h2>
                <p>Communicate with your clients directly.<br>Select a conversation or start a new one.</p>
                <button class="btn-primary" onclick="openNewChat()">
                    <i class="fas fa-plus"></i> New Message
                </button>
            </div>
        </div>
        <?php else: ?>

        <!-- Chat Header -->
        <div class="chat-header">
            <button class="chat-back-btn" onclick="history.back()"><i class="fas fa-arrow-left"></i></button>
            <div class="chat-header-avatar">
                <img src="<?= UPLOADS_URL ?>/profiles/<?= $contactUser['profile_photo'] ?? 'default_avatar.png' ?>"
                     alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
            </div>
            <div class="chat-header-info">
                <strong><?= htmlspecialchars($contactUser['name']) ?></strong>
                <span><?= ucfirst($contactUser['role']) ?></span>
            </div>
            <div class="chat-header-actions">
                <a href="<?= BASE_URL ?>/client/track-status.php" class="chat-action-btn" title="View Appointments">
                    <i class="fas fa-calendar-alt"></i>
                </a>
            </div>
        </div>

        <!-- Messages -->
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
                $isSent = ($msg['sender_id'] == $_SESSION['user_id']); ?>
            <div class="message-wrapper <?= $isSent ? 'sent' : 'received' ?>" data-id="<?= $msg['id'] ?>">
                <?php if (!$isSent): ?>
                <img src="<?= UPLOADS_URL ?>/profiles/<?= $msg['sender_photo'] ?? 'default_avatar.png' ?>"
                     alt="" class="msg-avatar" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                <?php endif; ?>
                <div class="message-bubble-wrapper">
                    <?php if ($msg['is_ai_reply']): ?><div class="ai-badge"><i class="fas fa-robot"></i> AI Reply</div><?php endif; ?>
                    <div class="message-bubble"><p><?= nl2br(htmlspecialchars($msg['message'])) ?></p></div>
                    <div class="msg-meta">
                        <span class="msg-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                        <?php if ($isSent): ?><i class="fas fa-check-double msg-tick"></i><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; endforeach; ?>
            <?php endif; ?>
            <div class="typing-indicator" id="typingIndicator" style="display:none">
                <img src="<?= UPLOADS_URL ?>/profiles/<?= $contactUser['profile_photo'] ?? 'default_avatar.png' ?>"
                     alt="" class="msg-avatar" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                <div class="typing-bubble"><span></span><span></span><span></span></div>
            </div>
        </div>

        <!-- Input -->
        <div class="chat-input-area">
            <!-- Attachment preview strip -->
            <div class="attachment-preview" id="attachmentPreview" style="display:none"></div>

            <div class="chat-input-wrapper">
                <button class="chat-emoji-btn" id="emojiBtn"><i class="far fa-smile"></i></button>

                <!-- File attach -->
                <input type="file" id="fileInput" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" style="display:none" onchange="handleFileSelect(this)">
                <button class="chat-attach-btn" title="Attach file / image" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-paperclip"></i>
                </button>

                <textarea id="messageInput" placeholder="Type a message…" rows="1"
                          oninput="autoResize(this); toggleSendVoice(this.value)"
                          onkeydown="handleKey(event)"></textarea>

                <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                <button class="chat-voice-btn" id="voiceBtn" title="Voice message"
                        onmousedown="startRecording()" onmouseup="stopRecording()"
                        ontouchstart="startRecording()" ontouchend="stopRecording()">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>

            <div class="emoji-picker" id="emojiPicker">
                <?php foreach (['😊','😂','👍','❤️','🙏','✅','📍','📅','🔧','⭐','👋','💬','📞','🗺️','🏗️','📐','📏','🌟','💪','🎉'] as $e): ?>
                <button onclick="insertEmoji('<?= $e ?>')"><?= $e ?></button>
                <?php endforeach; ?>
            </div>

            <div class="recording-indicator" id="recordingIndicator" style="display:none">
                <div class="recording-dot"></div>
                <span>Recording… <span id="recordingTimer">0:00</span></span>
                <button onclick="cancelRecording()" class="recording-cancel"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</main></div>

<script>
const CURRENT_USER_ID = <?= $_SESSION['user_id'] ?>;
const CONTACT_ID      = <?= $contactId ?>;
const BASE_URL        = '<?= BASE_URL ?>';
let lastMessageId     = <?= !empty($conversation) ? (int)end($conversation)['id'] : 0 ?>;
let pendingFiles      = [];

function autoResize(el){ el.style.height='auto'; el.style.height=Math.min(el.scrollHeight,120)+'px'; }
function handleKey(e){ if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); sendMessage(); } }

function toggleSendVoice(val){
    const s=document.getElementById('sendBtn'), v=document.getElementById('voiceBtn');
    if(!s||!v) return;
    if(val.trim().length>0||pendingFiles.length>0){ s.style.display='flex'; v.style.display='none'; }
    else{ s.style.display='none'; v.style.display='flex'; }
}

function sendMessage(){
    const inp=document.getElementById('messageInput');
    const text=inp.value.trim();
    if(pendingFiles.length>0){ sendFiles(); return; }
    if(!text||!CONTACT_ID) return;
    inp.value=''; inp.style.height='auto'; toggleSendVoice('');
    appendMessage(text,true,null);
    fetch(BASE_URL+'/api/messages.php',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({action:'send',receiver_id:CONTACT_ID,message:text})})
    .then(r=>r.json()).then(data=>{ if(data.success) lastMessageId=data.id; });
}

function appendMessage(text,isSent,id,isAI=false){
    const c=document.getElementById('chatMessages'); if(!c) return;
    const w=document.createElement('div');
    w.className='message-wrapper '+(isSent?'sent':'received');
    if(id) w.dataset.id=id;
    const tick=isSent?'<i class="fas fa-check-double msg-tick"></i>':'';
    const ai=isAI?'<div class="ai-badge"><i class="fas fa-robot"></i> AI Reply</div>':'';
    w.innerHTML=`<div class="message-bubble-wrapper">${ai}<div class="message-bubble"><p>${escHtml(text).replace(/\n/g,'<br>')}</p></div><div class="msg-meta"><span class="msg-time">${timeNow()}</span>${tick}</div></div>`;
    c.appendChild(w); c.scrollTop=c.scrollHeight;
}

function appendMediaMessage(html,isSent){
    const c=document.getElementById('chatMessages'); if(!c) return;
    const w=document.createElement('div');
    w.className='message-wrapper '+(isSent?'sent':'received');
    const tick=isSent?'<i class="fas fa-check-double msg-tick"></i>':'';
    w.innerHTML=`<div class="message-bubble-wrapper"><div class="message-bubble media-bubble">${html}</div><div class="msg-meta"><span class="msg-time">${timeNow()}</span>${tick}</div></div>`;
    c.appendChild(w); c.scrollTop=c.scrollHeight;
}

function escHtml(s){ return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function timeNow(){ const d=new Date(),h=d.getHours(),m=d.getMinutes(),ap=h>=12?'PM':'AM'; return (h%12||12)+':'+(m<10?'0':'')+m+' '+ap; }
function scrollBottom(){ const c=document.getElementById('chatMessages'); if(c) c.scrollTop=c.scrollHeight; }

/* Poll */
if(CONTACT_ID) setInterval(()=>{
    fetch(BASE_URL+'/api/messages.php?action=poll&contact_id='+CONTACT_ID+'&last_id='+lastMessageId)
    .then(r=>r.json()).then(data=>{
        if(!data.messages||!data.messages.length) return;
        data.messages.forEach(msg=>{
            if(parseInt(msg.sender_id)!==CURRENT_USER_ID){
                if(msg.message.startsWith('[IMAGE]')) appendMediaMessage(`<img src="${BASE_URL}/uploads/messages/${msg.message.replace('[IMAGE]','')}" class="msg-image" onclick="openLightbox(this.src)">`,false);
                else if(msg.message.startsWith('[FILE]')){ const p=msg.message.replace('[FILE]','').split('|'); appendMediaMessage(`<a href="${BASE_URL}/uploads/messages/${p[0]}" target="_blank" class="msg-file"><i class="fas fa-file"></i> ${escHtml(p[1]||p[0])}</a>`,false); }
                else if(msg.message.startsWith('[VOICE]')) appendMediaMessage(`<div class="msg-voice"><i class="fas fa-microphone"></i><audio controls src="${BASE_URL}/uploads/messages/${msg.message.replace('[VOICE]','')}"></audio></div>`,false);
                else appendMessage(msg.message,false,msg.id,msg.is_ai_reply==1);
                lastMessageId=msg.id;
            }
        });
    }).catch(()=>{});
},3000);

/* File attachments */
function handleFileSelect(input){
    const files=Array.from(input.files); if(!files.length) return;
    pendingFiles=files;
    const preview=document.getElementById('attachmentPreview');
    preview.style.display='flex'; preview.innerHTML='';
    files.forEach((file,i)=>{
        const item=document.createElement('div'); item.className='attach-preview-item';
        if(file.type.startsWith('image/')){
            const reader=new FileReader();
            reader.onload=e=>{ item.innerHTML=`<img src="${e.target.result}" alt="${escHtml(file.name)}"><button onclick="removePendingFile(${i})"><i class="fas fa-times"></i></button>`; };
            reader.readAsDataURL(file);
        } else {
            item.innerHTML=`<div class="attach-file-icon"><i class="fas fa-file"></i></div><span>${escHtml(file.name.substring(0,20))}</span><button onclick="removePendingFile(${i})"><i class="fas fa-times"></i></button>`;
        }
        preview.appendChild(item);
    });
    toggleSendVoice('x'); input.value='';
}
function removePendingFile(i){ pendingFiles.splice(i,1); if(!pendingFiles.length){ document.getElementById('attachmentPreview').style.display='none'; toggleSendVoice(''); } }
function sendFiles(){
    if(!pendingFiles.length||!CONTACT_ID) return;
    pendingFiles.forEach(file=>{
        const fd=new FormData(); fd.append('action','send_file'); fd.append('receiver_id',CONTACT_ID); fd.append('file',file);
        if(file.type.startsWith('image/')){ const r=new FileReader(); r.onload=e=>appendMediaMessage(`<img src="${e.target.result}" class="msg-image" onclick="openLightbox(this.src)">`,true); r.readAsDataURL(file); }
        else appendMediaMessage(`<div class="msg-file"><i class="fas fa-file"></i> ${escHtml(file.name)}</div>`,true);
        fetch(BASE_URL+'/api/messages.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) lastMessageId=d.id; }).catch(()=>{});
    });
    pendingFiles=[]; document.getElementById('attachmentPreview').style.display='none'; toggleSendVoice('');
}
function openLightbox(src){ const lb=document.createElement('div'); lb.className='msg-lightbox'; lb.innerHTML=`<div class="msg-lightbox-inner"><img src="${src}"><button onclick="this.closest('.msg-lightbox').remove()"><i class="fas fa-times"></i></button></div>`; lb.addEventListener('click',e=>{ if(e.target===lb) lb.remove(); }); document.body.appendChild(lb); }

/* Voice */
let mediaRecorder=null,audioChunks=[],recordingTimer=null,recordingSeconds=0;
function startRecording(){
    if(!navigator.mediaDevices){ alert('Microphone access required.'); return; }
    navigator.mediaDevices.getUserMedia({audio:true}).then(stream=>{
        audioChunks=[]; mediaRecorder=new MediaRecorder(stream);
        mediaRecorder.ondataavailable=e=>audioChunks.push(e.data);
        mediaRecorder.onstop=()=>{ stream.getTracks().forEach(t=>t.stop()); sendVoiceMessage(new Blob(audioChunks,{type:'audio/webm'})); };
        mediaRecorder.start();
        document.getElementById('recordingIndicator').style.display='flex';
        document.getElementById('voiceBtn').classList.add('recording');
        recordingSeconds=0;
        recordingTimer=setInterval(()=>{ recordingSeconds++; const m=Math.floor(recordingSeconds/60),s=recordingSeconds%60; document.getElementById('recordingTimer').textContent=m+':'+(s<10?'0':'')+s; },1000);
    }).catch(()=>alert('Could not access microphone.'));
}
function stopRecording(){ if(mediaRecorder&&mediaRecorder.state==='recording'){ mediaRecorder.stop(); clearInterval(recordingTimer); document.getElementById('recordingIndicator').style.display='none'; document.getElementById('voiceBtn').classList.remove('recording'); } }
function cancelRecording(){ if(mediaRecorder&&mediaRecorder.state==='recording'){ mediaRecorder.ondataavailable=null; mediaRecorder.onstop=null; mediaRecorder.stop(); } clearInterval(recordingTimer); audioChunks=[]; document.getElementById('recordingIndicator').style.display='none'; document.getElementById('voiceBtn').classList.remove('recording'); }
function sendVoiceMessage(blob){
    if(!CONTACT_ID) return;
    const fd=new FormData(); fd.append('action','send_file'); fd.append('receiver_id',CONTACT_ID); fd.append('file',blob,'voice_'+Date.now()+'.webm'); fd.append('is_voice','1');
    const url=URL.createObjectURL(blob);
    appendMediaMessage(`<div class="msg-voice"><i class="fas fa-microphone"></i><audio controls src="${url}"></audio></div>`,true);
    fetch(BASE_URL+'/api/messages.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.success) lastMessageId=d.id; }).catch(()=>{});
}

/* Emoji */
document.getElementById('emojiBtn')?.addEventListener('click',e=>{ e.stopPropagation(); document.getElementById('emojiPicker').classList.toggle('show'); });
function insertEmoji(e){ document.getElementById('messageInput').value+=e; toggleSendVoice(document.getElementById('messageInput').value); document.getElementById('emojiPicker').classList.remove('show'); }
document.addEventListener('click',e=>{ if(!e.target.closest('#emojiBtn')&&!e.target.closest('#emojiPicker')) document.getElementById('emojiPicker')?.classList.remove('show'); });

/* Drawers */
function openNewChat(){ document.getElementById('newChatDrawer').classList.add('show'); }
function closeNewChat(){ document.getElementById('newChatDrawer').classList.remove('show'); }
document.getElementById('newChatBtn')?.addEventListener('click',openNewChat);
function filterClients(q){ q=q.toLowerCase(); document.querySelectorAll('.new-chat-eng-item').forEach(el=>{ el.style.display=el.querySelector('strong').textContent.toLowerCase().includes(q)?'flex':'none'; }); }
document.getElementById('contactSearch')?.addEventListener('input',function(){ const q=this.value.toLowerCase(); document.querySelectorAll('.contact-item').forEach(el=>{ el.style.display=el.dataset.name?.includes(q)?'flex':'none'; }); });

scrollBottom();
toggleSendVoice('');
</script>
</body></html>
