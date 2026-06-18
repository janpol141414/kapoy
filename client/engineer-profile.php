<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect('/client/engineers.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$engineer = $engineerModel->getById($id);

if (!$engineer) redirect('/client/engineers.php');

$reviews = $engineerModel->getReviews($id);
$availableSlots = $engineerModel->getAvailableSlots($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($engineer['name']) ?> - Engineer Profile | LandSurvey</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/engineers.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ── Facebook-style Engineer Profile ── */
    .ep-wrapper { max-width: 100%; margin: 0; }

    /* Cover photo */
    .ep-cover {
        height: 220px;
        border-radius: 16px 16px 0 0;
        background: linear-gradient(135deg, #0f2540 0%, #1a3c5e 45%, #2d6a9f 75%, #4facfe 100%);
        position: relative; overflow: hidden;
        animation: fadeSlideDown 0.5s ease both;
    }
    @keyframes fadeSlideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .ep-cover::before {
        content: '';
        position: absolute; inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    /* Profile card — overlaps cover */
    .ep-profile-card {
        background: #fff;
        border-radius: 0 0 16px 16px;
        padding: 0 24px 20px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        border: 1px solid #f1f5f9;
        border-top: none;
        margin-bottom: 16px;
        animation: fadeSlideUp 0.5s ease 0.1s both;
    }
    @keyframes fadeSlideUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Avatar row */
    .ep-avatar-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: -48px;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .ep-avatar-wrap { position: relative; flex-shrink: 0; }
    .ep-avatar {
        width: 100px; height: 100px;
        border-radius: 50%; object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        display: block; background: #f1f5f9;
    }
    .ep-avail-ring {
        position: absolute; bottom: 5px; right: 5px;
        width: 18px; height: 18px;
        border-radius: 50%; border: 3px solid #fff;
    }
    .ep-avail-ring.available { background: #10b981; }
    .ep-avail-ring.busy      { background: #f59e0b; }
    .ep-avail-ring.offline   { background: #9ca3af; }

    .ep-action-btns {
        display: flex; gap: 8px; align-items: center;
        padding-bottom: 6px; flex-wrap: wrap;
    }
    .ep-btn-book {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 20px;
        background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
        color: #fff; border: none; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; text-decoration: none; white-space: nowrap;
    }
    .ep-btn-book:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(26,60,94,0.35); }
    .ep-btn-book.disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; transform: none; box-shadow: none; }
    .ep-btn-msg {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 20px;
        background: #f1f5f9; color: #374151;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; text-decoration: none; white-space: nowrap;
    }
    .ep-btn-msg:hover { background: #e2e8f0; border-color: #1a3c5e; color: #1a3c5e; }

    .ep-info-block { margin-bottom: 14px; }
    .ep-name-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 3px; }
    .ep-name { font-size: 22px; font-weight: 900; color: #1a1a2e; }
    .ep-verified {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; background: #dbeafe; color: #1e40af;
        border-radius: 20px; font-size: 11px; font-weight: 700;
    }
    .ep-spec { font-size: 14px; color: #6b7280; margin-bottom: 8px; }
    .ep-meta-row { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12px; color: #6b7280; }
    .ep-meta-row span { display: flex; align-items: center; gap: 4px; }
    .ep-meta-row i { color: #9ca3af; font-size: 11px; }

    /* Stats strip */
    .ep-stats-strip {
        display: flex; gap: 0;
        border-top: 1px solid #f1f5f9; padding-top: 14px;
        flex-wrap: wrap;
    }
    .ep-stat { flex: 1; min-width: 80px; text-align: center; padding: 0 12px; border-right: 1px solid #f1f5f9; }
    .ep-stat:last-child { border-right: none; }
    .ep-stat-val { display: block; font-size: 20px; font-weight: 900; color: #1a1a2e; }
    .ep-stat-lbl { display: block; font-size: 10px; color: #9ca3af; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    .ep-stars-inline { display: flex; gap: 2px; justify-content: center; margin-bottom: 2px; }
    .ep-stars-inline i { font-size: 12px; color: #d1d5db; }
    .ep-stars-inline i.filled { color: #f59e0b; }

    /* Two-column body */
    .ep-body { display: grid; grid-template-columns: 1fr 280px; gap: 16px; }

    /* Cards */
    .ep-card {
        background: #fff; border-radius: 14px; padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #f1f5f9; margin-bottom: 14px;
        animation: fadeSlideUp 0.4s ease both;
    }
    .ep-card-title {
        font-size: 14px; font-weight: 800; color: #1a1a2e;
        margin-bottom: 14px; display: flex; align-items: center; gap: 7px;
        padding-bottom: 10px; border-bottom: 1px solid #f8fafc;
    }
    .ep-card-title i { color: #2d6a9f; }
    .ep-card p { font-size: 14px; color: #6b7280; line-height: 1.7; }

    .ep-skills { display: flex; flex-wrap: wrap; gap: 7px; }
    .ep-skill { padding: 4px 12px; background: #f0f4ff; color: #4338ca; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .ep-certs { display: flex; flex-direction: column; gap: 9px; }
    .ep-cert { display: flex; align-items: center; gap: 9px; font-size: 13px; color: #374151; }
    .ep-cert i { color: #f59e0b; }

    .ep-detail-list { display: flex; flex-direction: column; }
    .ep-detail-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }
    .ep-detail-row:last-child { border-bottom: none; }
    .ep-detail-lbl { color: #9ca3af; font-weight: 500; }
    .ep-detail-val { color: #1a1a2e; font-weight: 600; text-align: right; }
    .ep-avail-available { color: #10b981; }
    .ep-avail-busy      { color: #f59e0b; }
    .ep-avail-offline   { color: #9ca3af; }

    .ep-rate-card {
        background: linear-gradient(135deg, #0f2540, #1a3c5e);
        border-radius: 14px; padding: 18px; text-align: center; margin-bottom: 14px; color: #fff;
    }
    .ep-rate-label { font-size: 10px; color: rgba(255,255,255,0.65); text-transform: uppercase; letter-spacing: 1px; }
    .ep-rate-val { font-size: 30px; font-weight: 900; color: #fff; margin: 3px 0; }
    .ep-rate-sub { font-size: 11px; color: rgba(255,255,255,0.6); }

    /* Tabs */
    .ep-tabs {
        display: flex; gap: 0; background: #fff; border-radius: 12px;
        border: 1px solid #f1f5f9; padding: 4px; margin-bottom: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow-x: auto;
        animation: fadeSlideUp 0.4s ease 0.15s both;
    }
    .ep-tab {
        flex: 1; padding: 9px 14px; background: none; border: none;
        font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; border-radius: 8px; transition: all 0.2s; white-space: nowrap; min-width: 80px;
    }
    .ep-tab.active { background: #1a3c5e; color: #fff; }
    .ep-tab:hover:not(.active) { background: #f0f7ff; color: #1a3c5e; }

    /* Services */
    .ep-services { display: flex; flex-direction: column; gap: 10px; }
    .ep-svc-item {
        display: flex; align-items: center; gap: 12px; padding: 12px 14px;
        background: #f8fafc; border-radius: 10px; border: 1px solid #f1f5f9; transition: all 0.2s;
    }
    .ep-svc-item:hover { background: #f0f7ff; border-color: #bfdbfe; }
    .ep-svc-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg,#1a3c5e,#2d6a9f); display: flex; align-items: center; justify-content: center; font-size: 16px; color: #fff; flex-shrink: 0; }
    .ep-svc-info { flex: 1; }
    .ep-svc-info h4 { font-size: 13px; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
    .ep-svc-info span { font-size: 11px; color: #9ca3af; }
    .ep-svc-price { font-size: 14px; font-weight: 800; color: #1a3c5e; margin-right: 10px; }
    .ep-btn-svc { padding: 6px 14px; background: linear-gradient(135deg,#1a3c5e,#2d6a9f); color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; text-decoration: none; white-space: nowrap; }
    .ep-btn-svc:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(26,60,94,0.3); }

    /* Slots */
    .ep-slots { display: grid; grid-template-columns: repeat(auto-fill,minmax(160px,1fr)); gap: 10px; }
    .ep-slot { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; text-align: center; transition: all 0.2s; }
    .ep-slot:hover { border-color: #2d6a9f; background: #f0f7ff; }
    .ep-slot-day  { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
    .ep-slot-num  { font-size: 26px; font-weight: 900; color: #1a1a2e; line-height: 1; }
    .ep-slot-mon  { font-size: 11px; color: #6b7280; margin-bottom: 7px; }
    .ep-slot-time { font-size: 11px; color: #6b7280; margin-bottom: 7px; display: flex; align-items: center; justify-content: center; gap: 4px; }
    .ep-slot-badge { display: inline-block; padding: 2px 9px; background: #dbeafe; color: #1e40af; border-radius: 20px; font-size: 10px; font-weight: 600; margin-bottom: 9px; }
    .ep-btn-slot { display: block; width: 100%; padding: 7px; background: linear-gradient(135deg,#1a3c5e,#2d6a9f); color: #fff; border: none; border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .ep-btn-slot:hover { transform: translateY(-1px); }

    /* Reviews */
    .ep-reviews-summary { display: flex; align-items: center; gap: 20px; padding: 14px; background: #f8fafc; border-radius: 10px; margin-bottom: 16px; }
    .ep-big-score { font-size: 52px; font-weight: 900; color: #1a1a2e; line-height: 1; }
    .ep-big-stars { display: flex; gap: 3px; margin: 3px 0; }
    .ep-big-stars i { font-size: 16px; color: #d1d5db; }
    .ep-big-stars i.filled { color: #f59e0b; }
    .ep-big-count { font-size: 12px; color: #9ca3af; }
    .ep-review-card { background: #fff; border-radius: 10px; padding: 16px; margin-bottom: 10px; border: 1px solid #f1f5f9; box-shadow: 0 1px 5px rgba(0,0,0,0.04); }
    .ep-review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 9px; }
    .ep-review-header img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; }
    .ep-review-meta strong { display: block; font-size: 13px; color: #1a1a2e; }
    .ep-review-stars { display: flex; gap: 2px; margin-top: 2px; }
    .ep-review-stars i { font-size: 11px; color: #d1d5db; }
    .ep-review-stars i.filled { color: #f59e0b; }
    .ep-review-date { margin-left: auto; font-size: 11px; color: #9ca3af; }
    .ep-review-text { font-size: 13px; color: #6b7280; line-height: 1.6; }

    /* Dark mode */
    body.dark-mode .ep-profile-card,
    body.dark-mode .ep-card,
    body.dark-mode .ep-tabs { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .ep-name { color: #f1f5f9 !important; }
    body.dark-mode .ep-spec,
    body.dark-mode .ep-meta-row { color: #94a3b8 !important; }
    body.dark-mode .ep-stat-val { color: #f1f5f9 !important; }
    body.dark-mode .ep-stats-strip,
    body.dark-mode .ep-stat { border-color: #334155 !important; }
    body.dark-mode .ep-card-title { color: #f1f5f9 !important; border-color: #334155 !important; }
    body.dark-mode .ep-card p { color: #94a3b8 !important; }
    body.dark-mode .ep-detail-lbl { color: #64748b !important; }
    body.dark-mode .ep-detail-val { color: #f1f5f9 !important; }
    body.dark-mode .ep-detail-row { border-color: #334155 !important; }
    body.dark-mode .ep-svc-item { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .ep-svc-info h4 { color: #f1f5f9 !important; }
    body.dark-mode .ep-svc-price { color: #60a5fa !important; }
    body.dark-mode .ep-slot { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .ep-slot-num { color: #f1f5f9 !important; }
    body.dark-mode .ep-slot-mon,
    body.dark-mode .ep-slot-time { color: #94a3b8 !important; }
    body.dark-mode .ep-reviews-summary { background: #0f172a !important; }
    body.dark-mode .ep-big-score { color: #f1f5f9 !important; }
    body.dark-mode .ep-review-card { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .ep-review-meta strong { color: #f1f5f9 !important; }
    body.dark-mode .ep-review-text { color: #94a3b8 !important; }
    body.dark-mode .ep-btn-msg { background: #334155 !important; border-color: #475569 !important; color: #f1f5f9 !important; }
    body.dark-mode .ep-tab:hover:not(.active) { background: #334155 !important; color: #60a5fa !important; }
    body.dark-mode .ep-skill { background: #1e3a5f !important; color: #93c5fd !important; }
    body.dark-mode .ep-cert { color: #cbd5e1 !important; }

    @media (max-width: 900px) {
        .ep-body { grid-template-columns: 1fr; }
        .ep-cover { height: 140px; }
        .ep-avatar { width: 80px; height: 80px; }
        .ep-name { font-size: 18px; }
        .ep-stat { padding: 0 8px; }
    }
        margin-bottom: 20px;
    }

    /* Avatar row */
    .ep-avatar-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-top: -52px;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .ep-avatar-wrap {
        position: relative; flex-shrink: 0;
    }
    .ep-avatar {
        width: 110px; height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        display: block;
        background: #f1f5f9;
    }
    .ep-avail-ring {
        position: absolute; bottom: 6px; right: 6px;
        width: 20px; height: 20px;
        border-radius: 50%; border: 3px solid #fff;
    }
    .ep-avail-ring.available { background: #10b981; }
    .ep-avail-ring.busy      { background: #f59e0b; }
    .ep-avail-ring.offline   { background: #9ca3af; }

    /* Action buttons beside avatar */
    .ep-action-btns {
        display: flex; gap: 10px; align-items: center;
        padding-bottom: 8px; flex-wrap: wrap;
    }
    .ep-btn-book {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px;
        background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
        color: #fff; border: none; border-radius: 10px;
        font-size: 14px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; text-decoration: none; white-space: nowrap;
    }
    .ep-btn-book:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,60,94,0.35); }
    .ep-btn-book.disabled { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; transform: none; box-shadow: none; }
    .ep-btn-msg {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 22px;
        background: #f1f5f9; color: #374151;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 14px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; text-decoration: none; white-space: nowrap;
    }
    .ep-btn-msg:hover { background: #e2e8f0; border-color: #1a3c5e; color: #1a3c5e; }

    /* Name + info block */
    .ep-info-block { margin-bottom: 16px; }
    .ep-name-row {
        display: flex; align-items: center; gap: 10px;
        flex-wrap: wrap; margin-bottom: 4px;
    }
    .ep-name { font-size: 24px; font-weight: 900; color: #1a1a2e; }
    .ep-verified {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px;
        background: #dbeafe; color: #1e40af;
        border-radius: 20px; font-size: 11px; font-weight: 700;
    }
    .ep-spec { font-size: 15px; color: #6b7280; margin-bottom: 10px; }
    .ep-meta-row {
        display: flex; flex-wrap: wrap; gap: 16px;
        font-size: 13px; color: #6b7280;
    }
    .ep-meta-row span { display: flex; align-items: center; gap: 5px; }
    .ep-meta-row i { color: #9ca3af; font-size: 12px; }

    /* Stats strip */
    .ep-stats-strip {
        display: flex; gap: 0;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        flex-wrap: wrap;
    }
    .ep-stat {
        flex: 1; min-width: 100px;
        text-align: center; padding: 0 16px;
        border-right: 1px solid #f1f5f9;
    }
    .ep-stat:last-child { border-right: none; }
    .ep-stat-val { display: block; font-size: 22px; font-weight: 900; color: #1a1a2e; }
    .ep-stat-lbl { display: block; font-size: 11px; color: #9ca3af; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
    .ep-stars-inline { display: flex; gap: 2px; justify-content: center; margin-bottom: 2px; }
    .ep-stars-inline i { font-size: 13px; color: #d1d5db; }
    .ep-stars-inline i.filled { color: #f59e0b; }

    /* Two-column layout */
    .ep-body { display: grid; grid-template-columns: 1fr 300px; gap: 20px; }

    /* Cards */
    .ep-card {
        background: #fff;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f1f5f9;
        margin-bottom: 16px;
    }
    .ep-card-title {
        font-size: 15px; font-weight: 800; color: #1a1a2e;
        margin-bottom: 16px;
        display: flex; align-items: center; gap: 8px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f8fafc;
    }
    .ep-card-title i { color: #2d6a9f; }
    .ep-card p { font-size: 14px; color: #6b7280; line-height: 1.7; }

    /* Skills */
    .ep-skills { display: flex; flex-wrap: wrap; gap: 8px; }
    .ep-skill {
        padding: 5px 14px;
        background: #f0f4ff; color: #4338ca;
        border-radius: 20px; font-size: 12px; font-weight: 600;
    }

    /* Certs */
    .ep-certs { display: flex; flex-direction: column; gap: 10px; }
    .ep-cert { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #374151; }
    .ep-cert i { color: #f59e0b; }

    /* Detail list */
    .ep-detail-list { display: flex; flex-direction: column; }
    .ep-detail-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px solid #f8fafc;
        font-size: 13px;
    }
    .ep-detail-row:last-child { border-bottom: none; }
    .ep-detail-lbl { color: #9ca3af; font-weight: 500; }
    .ep-detail-val { color: #1a1a2e; font-weight: 600; text-align: right; }
    .ep-avail-available { color: #10b981; }
    .ep-avail-busy      { color: #f59e0b; }
    .ep-avail-offline   { color: #9ca3af; }

    /* Rate card */
    .ep-rate-card {
        background: linear-gradient(135deg, #0f2540, #1a3c5e);
        border-radius: 14px; padding: 20px;
        text-align: center; margin-bottom: 16px;
        color: #fff;
    }
    .ep-rate-label { font-size: 11px; color: rgba(255,255,255,0.65); text-transform: uppercase; letter-spacing: 1px; }
    .ep-rate-val { font-size: 32px; font-weight: 900; color: #fff; margin: 4px 0; }
    .ep-rate-sub { font-size: 12px; color: rgba(255,255,255,0.6); }

    /* Tabs */
    .ep-tabs {
        display: flex; gap: 0;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        padding: 4px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow-x: auto;
    }
    .ep-tab {
        flex: 1; padding: 10px 16px;
        background: none; border: none;
        font-size: 13px; font-weight: 600; color: #6b7280;
        cursor: pointer; border-radius: 8px;
        transition: all 0.2s; white-space: nowrap;
        min-width: 80px;
    }
    .ep-tab.active { background: #1a3c5e; color: #fff; }
    .ep-tab:hover:not(.active) { background: #f0f7ff; color: #1a3c5e; }

    /* Services list */
    .ep-services { display: flex; flex-direction: column; gap: 12px; }
    .ep-svc-item {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px;
        background: #f8fafc; border-radius: 12px;
        border: 1px solid #f1f5f9; transition: all 0.2s;
    }
    .ep-svc-item:hover { background: #f0f7ff; border-color: #bfdbfe; }
    .ep-svc-icon {
        width: 44px; height: 44px; border-radius: 10px;
        background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: #fff; flex-shrink: 0;
    }
    .ep-svc-info { flex: 1; }
    .ep-svc-info h4 { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
    .ep-svc-info span { font-size: 12px; color: #9ca3af; }
    .ep-svc-price { font-size: 15px; font-weight: 800; color: #1a3c5e; margin-right: 12px; }
    .ep-btn-svc {
        padding: 7px 16px;
        background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
        color: #fff; border: none; border-radius: 8px;
        font-size: 12px; font-weight: 700; cursor: pointer;
        transition: all 0.2s; text-decoration: none; white-space: nowrap;
    }
    .ep-btn-svc:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(26,60,94,0.3); }

    /* Slots */
    .ep-slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: 12px; }
    .ep-slot {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 16px; text-align: center;
        transition: all 0.2s;
    }
    .ep-slot:hover { border-color: #2d6a9f; background: #f0f7ff; }
    .ep-slot-day  { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
    .ep-slot-num  { font-size: 28px; font-weight: 900; color: #1a1a2e; line-height: 1; }
    .ep-slot-mon  { font-size: 12px; color: #6b7280; margin-bottom: 8px; }
    .ep-slot-time { font-size: 12px; color: #6b7280; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 4px; }
    .ep-slot-badge { display: inline-block; padding: 3px 10px; background: #dbeafe; color: #1e40af; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 10px; }
    .ep-btn-slot {
        display: block; width: 100%; padding: 8px;
        background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
        color: #fff; border: none; border-radius: 8px;
        font-size: 12px; font-weight: 600; cursor: pointer;
        transition: all 0.2s; text-decoration: none;
    }
    .ep-btn-slot:hover { transform: translateY(-1px); }

    /* Reviews */
    .ep-reviews-summary {
        display: flex; align-items: center; gap: 24px;
        padding: 16px; background: #f8fafc; border-radius: 12px;
        margin-bottom: 20px;
    }
    .ep-big-score { font-size: 56px; font-weight: 900; color: #1a1a2e; line-height: 1; }
    .ep-big-stars { display: flex; gap: 3px; margin: 4px 0; }
    .ep-big-stars i { font-size: 18px; color: #d1d5db; }
    .ep-big-stars i.filled { color: #f59e0b; }
    .ep-big-count { font-size: 13px; color: #9ca3af; }
    .ep-review-card {
        background: #fff; border-radius: 12px; padding: 18px;
        margin-bottom: 12px; border: 1px solid #f1f5f9;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
    }
    .ep-review-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
    .ep-review-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .ep-review-meta strong { display: block; font-size: 14px; color: #1a1a2e; }
    .ep-review-stars { display: flex; gap: 2px; margin-top: 2px; }
    .ep-review-stars i { font-size: 12px; color: #d1d5db; }
    .ep-review-stars i.filled { color: #f59e0b; }
    .ep-review-date { margin-left: auto; font-size: 12px; color: #9ca3af; }
    .ep-review-text { font-size: 14px; color: #6b7280; line-height: 1.6; }

    /* Dark mode */
    body.dark-mode .ep-profile-card,
    body.dark-mode .ep-card,
    body.dark-mode .ep-tabs { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .ep-name { color: #f1f5f9 !important; }
    body.dark-mode .ep-spec { color: #94a3b8 !important; }
    body.dark-mode .ep-meta-row { color: #94a3b8 !important; }
    body.dark-mode .ep-stat-val { color: #f1f5f9 !important; }
    body.dark-mode .ep-stats-strip { border-color: #334155 !important; }
    body.dark-mode .ep-stat { border-color: #334155 !important; }
    body.dark-mode .ep-card-title { color: #f1f5f9 !important; border-color: #334155 !important; }
    body.dark-mode .ep-card p { color: #94a3b8 !important; }
    body.dark-mode .ep-detail-lbl { color: #64748b !important; }
    body.dark-mode .ep-detail-val { color: #f1f5f9 !important; }
    body.dark-mode .ep-detail-row { border-color: #334155 !important; }
    body.dark-mode .ep-svc-item { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .ep-svc-info h4 { color: #f1f5f9 !important; }
    body.dark-mode .ep-slot { background: #0f172a !important; border-color: #334155 !important; }
    body.dark-mode .ep-slot-num { color: #f1f5f9 !important; }
    body.dark-mode .ep-reviews-summary { background: #0f172a !important; }
    body.dark-mode .ep-big-score { color: #f1f5f9 !important; }
    body.dark-mode .ep-review-card { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .ep-review-meta strong { color: #f1f5f9 !important; }
    body.dark-mode .ep-review-text { color: #94a3b8 !important; }
    body.dark-mode .ep-btn-msg { background: #334155 !important; border-color: #475569 !important; color: #f1f5f9 !important; }
    body.dark-mode .ep-tab:hover:not(.active) { background: #334155 !important; color: #60a5fa !important; }

    @media (max-width: 900px) {
        .ep-body { grid-template-columns: 1fr; }
        .ep-cover { height: 160px; }
        .ep-avatar { width: 80px; height: 80px; }
        .ep-name { font-size: 20px; }
        .ep-stats-strip { gap: 0; }
        .ep-stat { padding: 0 10px; }
    }
    </style>
</head>
<body class="app-body">

<?php include '../includes/header.php'; ?>

<div class="app-layout">
    <?php include '../includes/sidebar_client.php'; ?>

    <main class="main-content">
        <div class="back-nav">
            <a href="engineers.php"><i class="fas fa-arrow-left"></i> Back to Engineers</a>
        </div>

        <div class="ep-wrapper">

            <!-- Cover -->
            <div class="ep-cover"></div>

            <!-- Profile Card -->
            <div class="ep-profile-card">
                <div class="ep-avatar-row">
                    <div class="ep-avatar-wrap">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $engineer['profile_photo'] ?? 'default_avatar.png' ?>"
                             alt="<?= htmlspecialchars($engineer['name']) ?>" class="ep-avatar"
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <span class="ep-avail-ring <?= $engineer['availability_status'] ?>"></span>
                    </div>
                    <div class="ep-action-btns">
                        <?php if ($engineer['availability_status'] === 'available'): ?>
                        <a href="book-appointment.php?engineer_id=<?= $engineer['id'] ?>" class="ep-btn-book">
                            <i class="fas fa-calendar-plus"></i> Book Appointment
                        </a>
                        <?php else: ?>
                        <button class="ep-btn-book disabled" disabled><i class="fas fa-clock"></i> Currently Unavailable</button>
                        <?php endif; ?>
                        <a href="messages.php?contact=<?= $engineer['user_id'] ?>" class="ep-btn-msg">
                            <i class="fas fa-comment"></i> Message
                        </a>
                    </div>
                </div>
                <div class="ep-info-block">
                    <div class="ep-name-row">
                        <span class="ep-name"><?= htmlspecialchars($engineer['name']) ?></span>
                        <span class="ep-verified"><i class="fas fa-check-circle"></i> PRC Licensed</span>
                        <span class="availability-status-badge <?= $engineer['availability_status'] ?>">
                            <i class="fas fa-circle" style="font-size:8px"></i> <?= ucfirst($engineer['availability_status']) ?>
                        </span>
                    </div>
                    <p class="ep-spec"><?= htmlspecialchars($engineer['specialization'] ?? 'Geodetic Engineer') ?></p>
                    <div class="ep-meta-row">
                        <?php if ($engineer['company_name']): ?><span><i class="fas fa-building"></i> <?= htmlspecialchars($engineer['company_name']) ?></span><?php endif; ?>
                        <span><i class="fas fa-briefcase"></i> <?= $engineer['experience_years'] ?> years experience</span>
                        <span><i class="fas fa-id-card"></i> <?= htmlspecialchars($engineer['license_number'] ?? 'PRC Licensed') ?></span>
                    </div>
                </div>
                <div class="ep-stats-strip">
                    <div class="ep-stat">
                        <div class="ep-stars-inline"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?= $i<=round($engineer['rating'])?'filled':'' ?>"></i><?php endfor; ?></div>
                        <span class="ep-stat-val"><?= number_format($engineer['rating'],1) ?></span>
                        <span class="ep-stat-lbl">Rating</span>
                    </div>
                    <div class="ep-stat"><span class="ep-stat-val"><?= $engineer['total_reviews'] ?></span><span class="ep-stat-lbl">Reviews</span></div>
                    <div class="ep-stat"><span class="ep-stat-val"><?= $engineer['experience_years'] ?></span><span class="ep-stat-lbl">Yrs Exp.</span></div>
                    <div class="ep-stat"><span class="ep-stat-val">₱<?= number_format($engineer['hourly_rate'],0) ?></span><span class="ep-stat-lbl">Per Hour</span></div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="ep-tabs">
                <button class="ep-tab active"  onclick="epTab('about',this)"><i class="fas fa-user" style="margin-right:5px"></i>About</button>
                <button class="ep-tab" onclick="epTab('services',this)"><i class="fas fa-map" style="margin-right:5px"></i>Services</button>
                <button class="ep-tab" onclick="epTab('availability',this)"><i class="fas fa-calendar" style="margin-right:5px"></i>Availability</button>
                <button class="ep-tab" onclick="epTab('reviews',this)"><i class="fas fa-star" style="margin-right:5px"></i>Reviews (<?= count($reviews) ?>)</button>
            </div>


            <!-- About Tab -->
            <div class="ep-tab-content active" id="ep-about">
                <div class="ep-body">
                    <div>
                        <div class="ep-card">
                            <div class="ep-card-title"><i class="fas fa-user"></i> About</div>
                            <p><?= nl2br(htmlspecialchars($engineer['bio'] ?? $engineer['user_bio'] ?? 'No bio available.')) ?></p>
                        </div>
                        <div class="ep-card">
                            <div class="ep-card-title"><i class="fas fa-tools"></i> Skills & Expertise</div>
                            <div class="ep-skills">
                                <?php if ($engineer['skills']): foreach (explode(',', $engineer['skills']) as $skill): ?>
                                <span class="ep-skill"><?= htmlspecialchars(trim($skill)) ?></span>
                                <?php endforeach; else: ?><p style="color:#9ca3af;font-size:14px">No skills listed.</p><?php endif; ?>
                            </div>
                        </div>
                        <div class="ep-card">
                            <div class="ep-card-title"><i class="fas fa-certificate"></i> Certifications</div>
                            <?php if ($engineer['certifications']): ?>
                            <div class="ep-certs"><?php foreach (explode(',', $engineer['certifications']) as $cert): ?>
                                <div class="ep-cert"><i class="fas fa-award"></i><span><?= htmlspecialchars(trim($cert)) ?></span></div>
                            <?php endforeach; ?></div>
                            <?php else: ?><p style="color:#9ca3af;font-size:14px">No certifications listed.</p><?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <div class="ep-rate-card">
                            <div class="ep-rate-label">Hourly Rate</div>
                            <div class="ep-rate-val">₱<?= number_format($engineer['hourly_rate'],0) ?></div>
                            <div class="ep-rate-sub">per hour · negotiable</div>
                        </div>
                        <div class="ep-card">
                            <div class="ep-card-title"><i class="fas fa-info-circle"></i> Details</div>
                            <div class="ep-detail-list">
                                <div class="ep-detail-row"><span class="ep-detail-lbl">License No.</span><span class="ep-detail-val"><?= htmlspecialchars($engineer['license_number'] ?? 'N/A') ?></span></div>
                                <div class="ep-detail-row"><span class="ep-detail-lbl">Experience</span><span class="ep-detail-val"><?= $engineer['experience_years'] ?> years</span></div>
                                <div class="ep-detail-row"><span class="ep-detail-lbl">Company</span><span class="ep-detail-val"><?= htmlspecialchars($engineer['company_name'] ?? 'Independent') ?></span></div>
                                <div class="ep-detail-row"><span class="ep-detail-lbl">Status</span><span class="ep-detail-val ep-avail-<?= $engineer['availability_status'] ?>"><?= ucfirst($engineer['availability_status']) ?></span></div>
                                <div class="ep-detail-row"><span class="ep-detail-lbl">Member Since</span><span class="ep-detail-val"><?= date('M Y', strtotime($engineer['member_since'])) ?></span></div>
                            </div>
                        </div>
                        <?php if ($engineer['company_name'] && !empty($engineer['company_address'])): ?>
                        <div class="ep-card">
                            <div class="ep-card-title"><i class="fas fa-building"></i> Company</div>
                            <strong style="font-size:14px;color:#1a1a2e"><?= htmlspecialchars($engineer['company_name']) ?></strong>
                            <p style="margin-top:6px;font-size:13px"><i class="fas fa-map-marker-alt" style="color:#9ca3af;margin-right:5px"></i><?= htmlspecialchars($engineer['company_address']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Services Tab -->
            <div class="ep-tab-content" id="ep-services" style="display:none">
                <div class="ep-card">
                    <div class="ep-card-title"><i class="fas fa-map"></i> Services Offered</div>
                    <div class="ep-services">
                        <?php foreach ([
                            ['Boundary Survey','₱5,000+','fa-border-all','3–5 days'],
                            ['Topographic Survey','₱8,000+','fa-mountain','5–7 days'],
                            ['Construction Layout','₱6,000+','fa-building','2–3 days'],
                            ['As-Built Survey','₱7,000+','fa-drafting-compass','4–6 days'],
                        ] as [$n,$p,$ic,$d]): ?>
                        <div class="ep-svc-item">
                            <div class="ep-svc-icon"><i class="fas <?= $ic ?>"></i></div>
                            <div class="ep-svc-info"><h4><?= $n ?></h4><span><?= $d ?></span></div>
                            <span class="ep-svc-price"><?= $p ?></span>
                            <a href="book-appointment.php?engineer_id=<?= $engineer['id'] ?>&service=<?= urlencode($n) ?>" class="ep-btn-svc">Book</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Availability Tab -->
            <div class="ep-tab-content" id="ep-availability" style="display:none">
                <div class="ep-card">
                    <div class="ep-card-title"><i class="fas fa-calendar-alt"></i> Available Slots</div>
                    <?php if (empty($availableSlots)): ?>
                    <div class="empty-state small"><i class="fas fa-calendar-times"></i><p>No available slots at the moment</p></div>
                    <?php else: ?>
                    <div class="ep-slots">
                        <?php foreach ($availableSlots as $slot): ?>
                        <div class="ep-slot">
                            <div class="ep-slot-day"><?= date('D', strtotime($slot['date'])) ?></div>
                            <div class="ep-slot-num"><?= date('d', strtotime($slot['date'])) ?></div>
                            <div class="ep-slot-mon"><?= date('M Y', strtotime($slot['date'])) ?></div>
                            <div class="ep-slot-time"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($slot['start_time'])) ?> – <?= date('h:i A', strtotime($slot['end_time'])) ?></div>
                            <div class="ep-slot-badge"><?= ucfirst(str_replace('_',' ',$slot['slot_type'])) ?></div>
                            <a href="book-appointment.php?engineer_id=<?= $engineer['id'] ?>&date=<?= $slot['date'] ?>" class="ep-btn-slot">Book This Slot</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews Tab -->
            <div class="ep-tab-content" id="ep-reviews" style="display:none">
                <div class="ep-card">
                    <div class="ep-card-title"><i class="fas fa-star"></i> Client Reviews</div>
                    <div class="ep-reviews-summary">
                        <div>
                            <div class="ep-big-score"><?= number_format($engineer['rating'],1) ?></div>
                            <div class="ep-big-stars"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?= $i<=round($engineer['rating'])?'filled':'' ?>"></i><?php endfor; ?></div>
                            <div class="ep-big-count"><?= $engineer['total_reviews'] ?> reviews</div>
                        </div>
                    </div>
                    <?php if (empty($reviews)): ?>
                    <div class="empty-state small"><i class="fas fa-star"></i><p>No reviews yet</p></div>
                    <?php else: foreach ($reviews as $review): ?>
                    <div class="ep-review-card">
                        <div class="ep-review-header">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $review['client_photo'] ?? 'default_avatar.png' ?>" alt=""
                                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <div class="ep-review-meta">
                                <strong><?= htmlspecialchars($review['client_name']) ?></strong>
                                <div class="ep-review-stars"><?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star <?= $i<=$review['rating']?'filled':'' ?>"></i><?php endfor; ?></div>
                            </div>
                            <span class="ep-review-date"><?= date('M d, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                        <p class="ep-review-text"><?= htmlspecialchars($review['comment']) ?></p>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div><!-- /ep-wrapper -->
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
function epTab(name, btn) {
    document.querySelectorAll('.ep-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.ep-tab-content').forEach(c => c.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('ep-' + name).style.display = 'block';
}
</script>
</body>
</html>
