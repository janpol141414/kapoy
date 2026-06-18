<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';

if (!isLoggedIn()) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$notifModel = new Notification($db);
$unreadNotifs = $notifModel->getUnreadCount($_SESSION['user_id']);
$notifications = $notifModel->getByUserId($_SESSION['user_id'], 5);

$role = getCurrentUserRole();
$dashboardUrl = BASE_URL . '/' . $role . '/dashboard.php';
?>
<!-- Enhancements: dark mode, animations, sidebar collapse, etc. -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/enhancements.css">
<header class="app-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <a href="<?= $dashboardUrl ?>" class="header-brand">
            <div class="brand-icon-sm"><i class="fas fa-map-marked-alt"></i></div>
            <span>LandSurvey</span>
        </a>
    </div>

    <div class="header-search">
        <div class="search-wrapper search-history-wrap" id="globalSearchWrap">
            <i class="fas fa-search" id="globalSearchIcon"></i>
            <input type="text" placeholder="Search engineers, services, appointments..." id="globalSearch" autocomplete="off">
            <div class="global-search-results" id="globalSearchResults"></div>
        </div>
    </div>

    <div class="header-right">

        <!-- Dark Mode Toggle -->
        <button class="dark-mode-toggle" id="darkModeToggle" title="Toggle Dark/Light Mode">
            <i class="fas fa-moon" id="darkModeIcon"></i>
        </button>

        <!-- Language Selector -->
        <div class="lang-selector" id="langSelector">
            <button class="lang-btn" onclick="toggleLang()" id="langBtn">
                <span class="lang-flag" id="currentFlag">🇵🇭</span>
                <span id="currentLang">EN</span>
                <i class="fas fa-chevron-down" style="font-size:10px"></i>
            </button>
            <div class="lang-dropdown" id="langDropdown">
                <div class="lang-option active" onclick="setLang('en','🇵🇭','EN')"><span class="flag">🇵🇭</span> English</div>
                <div class="lang-option" onclick="setLang('fil','🇵🇭','FIL')"><span class="flag">🇵🇭</span> Filipino</div>
                <div class="lang-option" onclick="setLang('es','🇪🇸','ES')"><span class="flag">🇪🇸</span> Español</div>
                <div class="lang-option" onclick="setLang('zh','🇨🇳','中文')"><span class="flag">🇨🇳</span> 中文</div>
                <div class="lang-option" onclick="setLang('ja','🇯🇵','日本語')"><span class="flag">🇯🇵</span> 日本語</div>
                <div class="lang-option" onclick="setLang('ko','🇰🇷','한국어')"><span class="flag">🇰🇷</span> 한국어</div>
                <div class="lang-option" onclick="setLang('ar','🇸🇦','العربية')"><span class="flag">🇸🇦</span> العربية</div>
            </div>
        </div>

        <!-- FAQ Button -->
        <div style="position:relative" id="faqDropdownWrap">
            <button class="faq-btn" onclick="toggleFaq()" title="Frequently Asked Questions">
                <i class="fas fa-question-circle"></i>
            </button>
            <div class="faq-dropdown" id="faqDropdown">
                <div class="faq-header">
                    <i class="fas fa-question-circle"></i>
                    <div><h4>Frequently Asked Questions</h4><p>Quick answers to common questions</p></div>
                </div>
                <div class="faq-list">
                    <?php
                    $faqs = [
                        ['How do I book an appointment?','Go to "Book Appointment" in the sidebar. Select an engineer, choose your service, pick an available date (shown in green), and confirm your booking.'],
                        ['How do I track my survey status?','Click "Track Status" in the sidebar. Select your appointment to see real-time progress updates from your engineer.'],
                        ['What payment methods are accepted?','We accept GCash, Bank Transfer, Credit Card, PayPal, and Cash. Upload your proof of payment in the Payments section.'],
                        ['How long does a survey take?','It depends on the service: Boundary Survey (3–5 days), Topographic Survey (5–7 days), Construction Layout (2–3 days). Your engineer will confirm the timeline.'],
                        ['Can I cancel or reschedule?','Contact your engineer via Messages or reach our support team. Cancellations must be made at least 24 hours before the appointment.'],
                        ['How are engineers verified?','All engineers on our platform hold a valid PRC license. We verify credentials before approving any engineer profile.'],
                        ['How do I submit feedback?','After your survey is completed, go to "Feedback" in the sidebar to rate your engineer and leave a review.'],
                        ['What if I have a complaint?','Use the Messages feature to contact your engineer directly, or reach our support team via the Contact section.'],
                    ];
                    foreach ($faqs as $faq): ?>
                    <div class="faq-item" onclick="toggleFaqItem(this)">
                        <div class="faq-question"><?= htmlspecialchars($faq[0]) ?><i class="fas fa-chevron-down"></i></div>
                        <div class="faq-answer"><?= htmlspecialchars($faq[1]) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="faq-footer">
                    <a href="<?= BASE_URL ?>/<?= $role ?>/messages.php"><i class="fas fa-headset"></i> Still need help? Contact Support</a>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="header-notif" id="notifDropdown">
            <button class="icon-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotifs > 0): ?>
                <span class="badge"><?= $unreadNotifs ?></span>
                <?php endif; ?>
            </button>
            <div class="notif-panel" id="notifPanel">
                <div class="notif-header">
                    <h4>Notifications</h4>
                    <a href="#" onclick="markAllRead()">Mark all read</a>
                </div>
                <div class="notif-list">
                    <?php if (empty($notifications)): ?>
                    <div class="notif-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications</p>
                    </div>
                    <?php else: ?>
                    <?php
                    $notifIcons = [
                        'appointment' => ['icon' => 'fa-calendar-check', 'color' => 'appointment'],
                        'payment'     => ['icon' => 'fa-credit-card',    'color' => 'payment'],
                        'message'     => ['icon' => 'fa-comment-dots',   'color' => 'message'],
                        'status'      => ['icon' => 'fa-tasks',          'color' => 'status'],
                        'system'      => ['icon' => 'fa-bell',           'color' => 'system'],
                    ];
                    foreach ($notifications as $notif):
                        $ic = $notifIcons[$notif['type']] ?? $notifIcons['system'];
                        $link = !empty($notif['link']) ? $notif['link'] : '#';
                    ?>
                    <a href="<?= htmlspecialchars($link) ?>"
                       class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>"
                       onclick="markOneRead(<?= $notif['id'] ?>, this)">
                        <div class="notif-icon <?= $ic['color'] ?>">
                            <i class="fas <?= $ic['icon'] ?>"></i>
                        </div>
                        <div class="notif-content">
                            <p class="notif-title"><?= htmlspecialchars($notif['title']) ?></p>
                            <p class="notif-msg"><?= htmlspecialchars(substr($notif['message'], 0, 65)) ?>...</p>
                            <span class="notif-time">
                                <i class="fas fa-clock" style="font-size:10px;margin-right:3px"></i>
                                <?= date('M d, h:i A', strtotime($notif['created_at'])) ?>
                            </span>
                        </div>
                        <?php if (!$notif['is_read']): ?>
                        <span class="notif-unread-dot"></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="notif-footer">
                    <a href="<?= BASE_URL ?>/<?= $role ?>/notifications.php">
                        <i class="fas fa-list" style="margin-right:5px"></i>View all notifications
                    </a>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="header-user" id="userDropdown">
            <button class="user-btn" onclick="toggleUserMenu()">
                <img src="<?= UPLOADS_URL ?>/profiles/<?= $_SESSION['profile_photo'] ?? 'default_avatar.png' ?>" 
                     alt="Profile" class="user-avatar"
                     onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    <span class="user-role"><?= ucfirst($role) ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="user-menu" id="userMenu">
                <a href="<?= BASE_URL ?>/<?= $role ?>/profile.php">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="<?= BASE_URL ?>/<?= $role ?>/settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <div class="menu-divider"></div>
                <a href="<?= BASE_URL ?>/auth/logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
    </div>
</header>

<script>
/* ── Notification helpers ── */
function toggleNotifications() {
    const panel = document.getElementById('notifPanel');
    panel.classList.toggle('show');
    document.getElementById('userMenu').classList.remove('show');
}

function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('show');
    document.getElementById('notifPanel').classList.remove('show');
}

function markAllRead() {
    fetch('<?= BASE_URL ?>/api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_all_read'})
    }).then(() => {
        document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        document.querySelectorAll('.notif-unread-dot').forEach(el => el.remove());
        const badge = document.querySelector('.header-notif .badge');
        if (badge) badge.remove();
    });
    return false;
}

function markOneRead(id, el) {
    fetch('<?= BASE_URL ?>/api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_read', id: id})
    });
    el.classList.remove('unread');
    const dot = el.querySelector('.notif-unread-dot');
    if (dot) dot.remove();
    // update badge count
    const badge = document.querySelector('.header-notif .badge');
    if (badge) {
        const n = parseInt(badge.textContent) - 1;
        if (n <= 0) badge.remove(); else badge.textContent = n;
    }
}

/* ── Facebook-style toast popup ── */
let _lastNotifId = <?= !empty($notifications) ? (int)$notifications[0]['id'] : 0 ?>;

function showToast(title, msg, type) {
    const icons = {
        appointment: 'fa-calendar-check',
        payment: 'fa-credit-card',
        message: 'fa-comment-dots',
        status: 'fa-tasks',
        system: 'fa-bell'
    };
    const colors = {
        appointment: '#1e40af', payment: '#065f46',
        message: '#5b21b6', status: '#92400e', system: '#374151'
    };
    const icon  = icons[type]  || 'fa-bell';
    const color = colors[type] || '#374151';

    const toast = document.createElement('div');
    toast.className = 'notif-toast';
    toast.innerHTML = `
        <div class="notif-toast-icon" style="background:${color}20;color:${color}">
            <i class="fas ${icon}"></i>
        </div>
        <div class="notif-toast-body">
            <strong>${title}</strong>
            <p>${msg}</p>
        </div>
        <button class="notif-toast-close" onclick="this.closest('.notif-toast').remove()">
            <i class="fas fa-times"></i>
        </button>`;
    document.body.appendChild(toast);
    // animate in
    requestAnimationFrame(() => toast.classList.add('show'));
    // auto-dismiss after 5 s
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 5000);
}

function pollNewNotifications() {
    fetch('<?= BASE_URL ?>/api/notifications.php?action=poll_new&last_id=' + _lastNotifId)
        .then(r => r.json())
        .then(data => {
            if (!data.notifications || !data.notifications.length) return;
            data.notifications.forEach(n => {
                if (n.id > _lastNotifId) {
                    _lastNotifId = n.id;
                    showToast(n.title, n.message.substring(0, 80), n.type);
                    // bump badge
                    let badge = document.querySelector('.header-notif .badge');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.textContent = '1';
                        document.querySelector('.header-notif .icon-btn').appendChild(badge);
                    } else {
                        badge.textContent = parseInt(badge.textContent) + 1;
                    }
                }
            });
        })
        .catch(() => {});
}

// Poll every 20 seconds
setInterval(pollNewNotifications, 20000);

/* ── Close on outside click ── */
document.addEventListener('click', function(e) {
    if (!e.target.closest('#notifDropdown')) {
        document.getElementById('notifPanel').classList.remove('show');
    }
    if (!e.target.closest('#userDropdown')) {
        document.getElementById('userMenu').classList.remove('show');
    }
    if (!e.target.closest('#langSelector')) {
        document.getElementById('langDropdown')?.classList.remove('show');
    }
    if (!e.target.closest('#faqDropdownWrap')) {
        document.getElementById('faqDropdown')?.classList.remove('show');
    }
    if (!e.target.closest('.search-history-wrap')) {
        document.querySelectorAll('.search-history-dropdown').forEach(d => d.classList.remove('show'));
    }
});

/* ── Dark Mode ── */
(function() {
    const saved = localStorage.getItem('geosurvey_dark');
    if (saved === '1') {
        document.body.classList.add('dark-mode');
        const icon = document.getElementById('darkModeIcon');
        if (icon) { icon.classList.replace('fa-moon', 'fa-sun'); }
    }
})();

document.getElementById('darkModeToggle')?.addEventListener('click', function() {
    const isDark = document.body.classList.toggle('dark-mode');
    const icon = document.getElementById('darkModeIcon');
    if (isDark) {
        icon.classList.replace('fa-moon', 'fa-sun');
        localStorage.setItem('geosurvey_dark', '1');
    } else {
        icon.classList.replace('fa-sun', 'fa-moon');
        localStorage.setItem('geosurvey_dark', '0');
    }
});

/* ── Language Selector ── */
(function() {
    const saved = JSON.parse(localStorage.getItem('geosurvey_lang') || '{"code":"en","flag":"🇵🇭","label":"EN"}');
    document.getElementById('currentFlag').textContent = saved.flag;
    document.getElementById('currentLang').textContent = saved.label;
    document.querySelectorAll('.lang-option').forEach(el => {
        el.classList.toggle('active', el.textContent.trim().startsWith(saved.label));
    });
})();

function toggleLang() {
    document.getElementById('langDropdown').classList.toggle('show');
    document.getElementById('notifPanel').classList.remove('show');
    document.getElementById('userMenu').classList.remove('show');
    document.getElementById('faqDropdown')?.classList.remove('show');
}

function setLang(code, flag, label) {
    document.getElementById('currentFlag').textContent = flag;
    document.getElementById('currentLang').textContent = label;
    document.querySelectorAll('.lang-option').forEach(el => el.classList.remove('active'));
    event.currentTarget.classList.add('active');
    localStorage.setItem('geosurvey_lang', JSON.stringify({code, flag, label}));
    document.getElementById('langDropdown').classList.remove('show');
    // Apply lang attribute for accessibility
    document.documentElement.lang = code;
    // Apply translation
    applyTranslation(code);
}

/* ── Translation Engine ── */
const _translations = {
    fil: {
        'Dashboard':'Dashboard','My Profile':'Aking Profile','Browse Engineers':'Mag-browse ng mga Inhinyero',
        'Companies':'Mga Kumpanya','Book Appointment':'Mag-book ng Appointment','Track Status':'Subaybayan ang Status',
        'Payments':'Mga Bayad','Feedback':'Feedback','Messages':'Mga Mensahe','Sign Out':'Mag-sign Out',
        'Appointments':'Mga Appointment','My Schedule':'Aking Iskedyul','Update Progress':'I-update ang Progreso',
        'Dashboard':'Dashboard','Overview':'Pangkalahatang-tanaw','Management':'Pamamahala','Directory':'Direktoryo',
        'Schedules':'Mga Iskedyul','Engineers':'Mga Inhinyero','Users':'Mga Gumagamit',
        'Notifications':'Mga Abiso','Settings':'Mga Setting','Search engineers, services...':'Maghanap ng mga inhinyero, serbisyo...',
        'Mark all read':'Markahan lahat bilang nabasa','View all notifications':'Tingnan ang lahat ng abiso',
        'My Profile':'Aking Profile','Sign Out':'Mag-sign Out'
    },
    es: {
        'Dashboard':'Panel','My Profile':'Mi Perfil','Browse Engineers':'Buscar Ingenieros',
        'Companies':'Empresas','Book Appointment':'Reservar Cita','Track Status':'Seguir Estado',
        'Payments':'Pagos','Feedback':'Comentarios','Messages':'Mensajes','Sign Out':'Cerrar Sesión',
        'Appointments':'Citas','My Schedule':'Mi Horario','Update Progress':'Actualizar Progreso',
        'Schedules':'Horarios','Engineers':'Ingenieros','Users':'Usuarios',
        'Notifications':'Notificaciones','Settings':'Configuración','Search engineers, services...':'Buscar ingenieros, servicios...',
        'Mark all read':'Marcar todo como leído','View all notifications':'Ver todas las notificaciones',
        'My Profile':'Mi Perfil','Sign Out':'Cerrar Sesión'
    },
    zh: {
        'Dashboard':'仪表板','My Profile':'我的资料','Browse Engineers':'浏览工程师',
        'Companies':'公司','Book Appointment':'预约','Track Status':'跟踪状态',
        'Payments':'付款','Feedback':'反馈','Messages':'消息','Sign Out':'退出登录',
        'Appointments':'预约','My Schedule':'我的日程','Update Progress':'更新进度',
        'Schedules':'日程','Engineers':'工程师','Users':'用户',
        'Notifications':'通知','Settings':'设置','Search engineers, services...':'搜索工程师、服务...',
        'Mark all read':'全部标为已读','View all notifications':'查看所有通知',
        'My Profile':'我的资料','Sign Out':'退出登录'
    },
    ja: {
        'Dashboard':'ダッシュボード','My Profile':'マイプロフィール','Browse Engineers':'エンジニアを探す',
        'Companies':'会社','Book Appointment':'予約','Track Status':'状況追跡',
        'Payments':'支払い','Feedback':'フィードバック','Messages':'メッセージ','Sign Out':'サインアウト',
        'Appointments':'予約','My Schedule':'スケジュール','Update Progress':'進捗更新',
        'Schedules':'スケジュール','Engineers':'エンジニア','Users':'ユーザー',
        'Notifications':'通知','Settings':'設定','Search engineers, services...':'エンジニア・サービスを検索...',
        'Mark all read':'すべて既読にする','View all notifications':'すべての通知を見る',
        'My Profile':'マイプロフィール','Sign Out':'サインアウト'
    },
    ko: {
        'Dashboard':'대시보드','My Profile':'내 프로필','Browse Engineers':'엔지니어 찾기',
        'Companies':'회사','Book Appointment':'예약','Track Status':'상태 추적',
        'Payments':'결제','Feedback':'피드백','Messages':'메시지','Sign Out':'로그아웃',
        'Appointments':'예약','My Schedule':'내 일정','Update Progress':'진행 상황 업데이트',
        'Schedules':'일정','Engineers':'엔지니어','Users':'사용자',
        'Notifications':'알림','Settings':'설정','Search engineers, services...':'엔지니어, 서비스 검색...',
        'Mark all read':'모두 읽음으로 표시','View all notifications':'모든 알림 보기',
        'My Profile':'내 프로필','Sign Out':'로그아웃'
    },
    ar: {
        'Dashboard':'لوحة التحكم','My Profile':'ملفي الشخصي','Browse Engineers':'تصفح المهندسين',
        'Companies':'الشركات','Book Appointment':'حجز موعد','Track Status':'تتبع الحالة',
        'Payments':'المدفوعات','Feedback':'التغذية الراجعة','Messages':'الرسائل','Sign Out':'تسجيل الخروج',
        'Appointments':'المواعيد','My Schedule':'جدولي','Update Progress':'تحديث التقدم',
        'Schedules':'الجداول','Engineers':'المهندسون','Users':'المستخدمون',
        'Notifications':'الإشعارات','Settings':'الإعدادات','Search engineers, services...':'ابحث عن مهندسين، خدمات...',
        'Mark all read':'تحديد الكل كمقروء','View all notifications':'عرض جميع الإشعارات',
        'My Profile':'ملفي الشخصي','Sign Out':'تسجيل الخروج'
    }
};

function applyTranslation(code) {
    if (code === 'en') {
        // Restore original — reload page
        location.reload();
        return;
    }
    const dict = _translations[code];
    if (!dict) return;

    // Translate all text nodes that match dictionary keys
    function translateNode(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            const trimmed = node.textContent.trim();
            if (dict[trimmed]) node.textContent = node.textContent.replace(trimmed, dict[trimmed]);
        } else if (node.nodeType === Node.ELEMENT_NODE &&
                   !['SCRIPT','STYLE','INPUT','TEXTAREA'].includes(node.tagName)) {
            node.childNodes.forEach(translateNode);
        }
    }

    // Translate sidebar nav
    document.querySelectorAll('.nav-item span, .nav-section-title, .sidebar-name').forEach(el => {
        const key = el.textContent.trim();
        if (dict[key]) el.textContent = dict[key];
    });

    // Translate page headers
    document.querySelectorAll('.page-header h1, .page-header p, .card-header h3').forEach(el => {
        const key = el.textContent.trim();
        if (dict[key]) el.textContent = dict[key];
    });

    // Translate notification panel
    document.querySelectorAll('.notif-header h4, .notif-header a, .notif-footer a').forEach(el => {
        const key = el.textContent.trim();
        if (dict[key]) el.textContent = dict[key];
    });

    // Translate placeholders
    document.querySelectorAll('input[placeholder]').forEach(el => {
        const key = el.placeholder.trim();
        if (dict[key]) el.placeholder = dict[key];
    });

    // RTL for Arabic
    document.documentElement.dir = (code === 'ar') ? 'rtl' : 'ltr';
}

// Apply saved language on load
(function() {
    const saved = JSON.parse(localStorage.getItem('geosurvey_lang') || '{"code":"en","flag":"🇵🇭","label":"EN"}');
    if (saved.code && saved.code !== 'en') {
        document.addEventListener('DOMContentLoaded', () => applyTranslation(saved.code));
    }
    if (saved.code === 'ar') document.documentElement.dir = 'rtl';
})();

/* ── FAQ Panel ── */
function toggleFaq() {
    document.getElementById('faqDropdown').classList.toggle('show');
    document.getElementById('notifPanel').classList.remove('show');
    document.getElementById('userMenu').classList.remove('show');
    document.getElementById('langDropdown')?.classList.remove('show');
}

function toggleFaqItem(el) {
    const isOpen = el.classList.contains('open');
    document.querySelectorAll('.faq-item.open').forEach(item => item.classList.remove('open'));
    if (!isOpen) el.classList.add('open');
}

/* ── Sidebar Toggle — hamburger button ── */
(function initSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    // Restore saved state on desktop
    if (window.innerWidth > 900 && localStorage.getItem('geosurvey_sidebar') === '1') {
        sidebar.classList.add('collapsed');
        document.body.classList.add('sidebar-collapsed');
    }

    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        if (window.innerWidth <= 900) {
            // ── MOBILE: slide in/out with overlay ──
            var isOpen = sidebar.classList.toggle('mobile-open');

            var overlay = document.getElementById('sidebarOverlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'sidebarOverlay';
                overlay.className = 'sidebar-overlay';
                overlay.addEventListener('click', closeMobileSidebar);
                document.body.appendChild(overlay);
            }
            if (isOpen) {
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        } else {
            // ── DESKTOP: collapse/expand (push layout) ──
            var isCollapsed = sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed', isCollapsed);
            localStorage.setItem('geosurvey_sidebar', isCollapsed ? '1' : '0');
        }
    });

    // Close mobile sidebar when a nav item is clicked
    document.querySelectorAll('.nav-item').forEach(function (item) {
        item.addEventListener('click', function () {
            if (window.innerWidth <= 900) closeMobileSidebar();
        });
    });

    // Handle window resize
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            closeMobileSidebar();
            var saved = localStorage.getItem('geosurvey_sidebar');
            if (saved === '1') {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
            }
        }
    });
})();

function closeMobileSidebar() {
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebarOverlay');
    if (sidebar)  sidebar.classList.remove('mobile-open');
    if (overlay)  overlay.classList.remove('show');
    document.body.style.overflow = '';
}

/* ── Global Search History ── */
function initSearchHistory(inputId, historyKey) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const wrap = input.closest('.search-history-wrap') || input.parentElement;
    let dropdown = wrap.querySelector('.search-history-dropdown');

    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'search-history-dropdown';
        wrap.appendChild(dropdown);
    }

    function getHistory() {
        return JSON.parse(localStorage.getItem(historyKey) || '[]');
    }
    function saveHistory(q) {
        let h = getHistory().filter(x => x !== q);
        h.unshift(q);
        h = h.slice(0, 8);
        localStorage.setItem(historyKey, JSON.stringify(h));
    }
    function renderHistory() {
        const h = getHistory();
        if (!h.length) { dropdown.classList.remove('show'); return; }
        dropdown.innerHTML = `
            <div class="search-history-header">
                <span><i class="fas fa-history" style="margin-right:4px"></i>Recent Searches</span>
                <button onclick="clearHistory('${historyKey}', this)">Clear all</button>
            </div>
            ${h.map((q,i) => `
            <div class="search-history-item" onclick="useHistory('${inputId}','${q.replace(/'/g,"\\'")}')">
                <i class="fas fa-clock"></i>
                <span>${q}</span>
                <button class="remove-history" onclick="event.stopPropagation();removeHistory('${historyKey}',${i})">
                    <i class="fas fa-times"></i>
                </button>
            </div>`).join('')}`;
        dropdown.classList.add('show');
    }

    input.addEventListener('focus', renderHistory);
    input.addEventListener('input', function() {
        if (!this.value.trim()) renderHistory(); else dropdown.classList.remove('show');
    });
    input.closest('form')?.addEventListener('submit', function() {
        if (input.value.trim()) saveHistory(input.value.trim());
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) saveHistory(this.value.trim());
    });
}

window.clearHistory = function(key, btn) {
    localStorage.removeItem(key);
    btn.closest('.search-history-dropdown').classList.remove('show');
};
window.removeHistory = function(key, idx) {
    let h = JSON.parse(localStorage.getItem(key) || '[]');
    h.splice(idx, 1);
    localStorage.setItem(key, JSON.stringify(h));
    document.querySelectorAll('.search-history-dropdown').forEach(d => {
        if (d.closest('[data-history-key="' + key + '"]')) d.classList.remove('show');
    });
};
window.useHistory = function(inputId, q) {
    const input = document.getElementById(inputId);
    if (input) { input.value = q; input.closest('form')?.submit(); }
};

// Init global search history
initSearchHistory('globalSearch', 'geosurvey_search_global');

/* ── Real-time Sync Engine ── */
(function() {
    var BASE      = '<?= BASE_URL ?>';
    var ROLE      = '<?= $role ?>';
    var lastSync  = new Date().toISOString().slice(0,19).replace('T',' ');
    var syncTimer = null;

    function doSync() {
        fetch(BASE + '/api/sync.php?since=' + encodeURIComponent(lastSync))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) return;

                // Update notification badge
                updateBadge('.header-notif .badge', '.header-notif .icon-btn', data.unread_notifications);

                // Update message badge in sidebar
                var msgBadge = document.querySelector('.nav-item[href*="messages"] .nav-badge');
                if (data.unread_messages > 0) {
                    if (!msgBadge) {
                        var msgLink = document.querySelector('.nav-item[href*="messages"]');
                        if (msgLink) {
                            var b = document.createElement('span');
                            b.className = 'nav-badge';
                            b.textContent = data.unread_messages;
                            msgLink.appendChild(b);
                        }
                    } else {
                        msgBadge.textContent = data.unread_messages;
                    }
                } else if (msgBadge) {
                    msgBadge.remove();
                }

                // Show toast for each update type
                if (data.updates && data.updates.length > 0) {
                    data.updates.forEach(function(upd) {
                        showSyncToast(upd.type, upd.message);
                    });

                    // Refresh stat numbers on dashboard without full reload
                    refreshDashboardStats(data.updates);
                }

                // Schedule changes → refresh booking calendar if open
                if (data.schedule_changes > 0 && typeof loadCalendarWithSlots === 'function') {
                    loadCalendarWithSlots();
                }

                lastSync = data.timestamp;
            })
            .catch(function() {}); // silent fail — no network disruption
    }

    function updateBadge(badgeSel, parentSel, count) {
        var badge  = document.querySelector(badgeSel);
        var parent = document.querySelector(parentSel);
        if (count > 0) {
            if (!badge && parent) {
                badge = document.createElement('span');
                badge.className = 'badge';
                parent.appendChild(badge);
            }
            if (badge) badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    }

    function showSyncToast(type, message) {
        var icons = {
            appointments:  'fa-calendar-check',
            payments:      'fa-credit-card',
            progress:      'fa-tasks',
            feedback:      'fa-star',
            status_changes:'fa-sync-alt',
            users:         'fa-user-plus',
            messages:      'fa-comment-dots',
        };
        var colors = {
            appointments:  '#1e40af',
            payments:      '#065f46',
            progress:      '#5b21b6',
            feedback:      '#92400e',
            status_changes:'#1a3c5e',
            users:         '#065f46',
            messages:      '#5b21b6',
        };
        var icon  = icons[type]  || 'fa-bell';
        var color = colors[type] || '#374151';

        var toast = document.createElement('div');
        toast.className = 'notif-toast';
        toast.innerHTML =
            '<div class="notif-toast-icon" style="background:' + color + '20;color:' + color + '">' +
                '<i class="fas ' + icon + '"></i>' +
            '</div>' +
            '<div class="notif-toast-body">' +
                '<strong>Live Update</strong>' +
                '<p>' + message + '</p>' +
            '</div>' +
            '<button class="notif-toast-close" onclick="this.closest(\'.notif-toast\').remove()">' +
                '<i class="fas fa-times"></i>' +
            '</button>';
        document.body.appendChild(toast);
        requestAnimationFrame(function() { toast.classList.add('show'); });
        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 400);
        }, 4000);
    }

    function refreshDashboardStats(updates) {
        // If we're on a dashboard page, update stat numbers via AJAX
        var statValues = document.querySelectorAll('.stat-value, .eng-stat-value, .admin-kpi-value');
        if (!statValues.length) return;

        // Fetch fresh stats
        fetch(BASE + '/api/sync.php?action=stats')
            .then(function(r) { return r.json(); })
            .catch(function() {});
        // The page will show updated numbers on next full load or manual refresh
        // For live stat updates, we add a subtle "refresh" indicator
        updates.forEach(function(upd) {
            var indicator = document.querySelector('[data-sync-type="' + upd.type + '"]');
            if (indicator) {
                indicator.style.animation = 'none';
                indicator.offsetHeight; // reflow
                indicator.style.animation = 'pulse 0.6s ease';
            }
        });
    }

    // Start polling every 15 seconds
    syncTimer = setInterval(doSync, 15000);

    // Also sync when tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) doSync();
    });

    // Initial sync after 3 seconds
    setTimeout(doSync, 3000);
})();

/* ── Global Live Search ── */
(function() {
    var input    = document.getElementById('globalSearch');
    var results  = document.getElementById('globalSearchResults');
    var icon     = document.getElementById('globalSearchIcon');
    var debounce = null;
    var BASE     = '<?= BASE_URL ?>';

    if (!input || !results) return;

    input.addEventListener('input', function() {
        var q = this.value.trim();

        // Show spinner while typing
        icon.className = q.length > 0 ? 'fas fa-spinner fa-spin' : 'fas fa-search';

        clearTimeout(debounce);

        if (q.length === 0) {
            hideResults();
            icon.className = 'fas fa-search';
            return;
        }

        debounce = setTimeout(function() {
            fetchResults(q);
        }, 220); // 220ms debounce — fast but not spammy
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { hideResults(); input.blur(); }
        if (e.key === 'Enter' && this.value.trim()) {
            saveHistory('geosurvey_search_global', this.value.trim());
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#globalSearchWrap')) hideResults();
    });

    function fetchResults(q) {
        fetch(BASE + '/api/search.php?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                icon.className = 'fas fa-search';
                renderResults(data, q);
            })
            .catch(function() {
                icon.className = 'fas fa-search';
                hideResults();
            });
    }

    function renderResults(data, q) {
        if (!data.success || !data.results.length) {
            results.innerHTML =
                '<div class="gsr-empty">' +
                    '<i class="fas fa-search-minus"></i>' +
                    '<p>No results for "<strong>' + escHtml(q) + '</strong>"</p>' +
                '</div>';
            results.classList.add('show');
            return;
        }

        // Group by type
        var groups = {};
        var typeLabels = {
            engineer:    '👷 Engineers',
            company:     '🏢 Companies',
            service:     '🗺️ Services',
            appointment: '📅 Appointments',
            user:        '👤 Users',
        };

        data.results.forEach(function(item) {
            if (!groups[item.type]) groups[item.type] = [];
            groups[item.type].push(item);
        });

        var html = '';
        Object.keys(groups).forEach(function(type) {
            html += '<div class="gsr-group-label">' + (typeLabels[type] || type) + '</div>';
            groups[type].forEach(function(item) {
                var photoHtml = item.photo
                    ? '<img src="' + BASE + '/uploads/' + (item.is_company ? 'companies' : 'profiles') + '/' + escHtml(item.photo) + '" ' +
                      'onerror="this.style.display=\'none\';this.nextSibling.style.display=\'flex\'" ' +
                      'class="gsr-photo">' +
                      '<div class="gsr-icon-fallback" style="background:' + item.color + ';display:none"><i class="fas ' + item.icon + '"></i></div>'
                    : '<div class="gsr-icon-fallback" style="background:' + item.color + '"><i class="fas ' + item.icon + '"></i></div>';

                // Highlight matching text
                var title    = highlight(escHtml(item.title), q);
                var subtitle = escHtml(item.subtitle || '');

                html +=
                    '<a href="' + item.url + '" class="gsr-item" onclick="saveHistory(\'geosurvey_search_global\',\'' + escHtml(item.title).replace(/'/g,"\\'") + '\')">' +
                        '<div class="gsr-thumb">' + photoHtml + '</div>' +
                        '<div class="gsr-info">' +
                            '<span class="gsr-title">' + title + '</span>' +
                            '<span class="gsr-sub">' + subtitle + '</span>' +
                        '</div>' +
                        '<span class="gsr-meta">' + escHtml(item.meta || '') + '</span>' +
                    '</a>';
            });
        });

        html += '<div class="gsr-footer">Showing ' + data.count + ' result' + (data.count !== 1 ? 's' : '') + ' for "<strong>' + escHtml(q) + '</strong>"</div>';

        results.innerHTML = html;
        results.classList.add('show');
    }

    function hideResults() {
        results.classList.remove('show');
        results.innerHTML = '';
    }

    function highlight(text, q) {
        if (!q) return text;
        var re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(re, '<mark class="gsr-highlight">$1</mark>');
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    window.saveHistory = function(key, q) {
        if (!q) return;
        var h = JSON.parse(localStorage.getItem(key) || '[]').filter(function(x){ return x !== q; });
        h.unshift(q);
        localStorage.setItem(key, JSON.stringify(h.slice(0, 8)));
    };
})();
</script>
