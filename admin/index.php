<?php
// ============================================================
//  SATU SEDUH — Admin Dashboard (Redesigned)
//  Akses: admin/index.php
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// ── LOGIN ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_nama'] = $user['nama'];
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Username atau password salah.';
    }
}

// ── LOGOUT ───────────────────────────────────────────────────
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$isLoggedIn = !empty($_SESSION['admin_id']);

// ── STATISTIK & DATA ─────────────────────────────────────────
$stats = [];
$pesananTerbaru = [];
$reservasiList  = [];
$komentarList   = [];
$revenueChart   = [];

if ($isLoggedIn) {
    $db = getDB();

    $stats['pesanan_hari_ini']  = $db->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $stats['total_pesanan']     = $db->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
    $stats['pendapatan_hari']   = $db->query("SELECT COALESCE(SUM(total),0) FROM pesanan WHERE DATE(created_at)=CURDATE() AND status NOT IN ('cancelled')")->fetchColumn();
    $stats['pendapatan_bulan']  = $db->query("SELECT COALESCE(SUM(total),0) FROM pesanan WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) AND status NOT IN ('cancelled')")->fetchColumn();
    $stats['reservasi_pending'] = $db->query("SELECT COUNT(*) FROM reservasi WHERE status='pending'")->fetchColumn();
    $stats['komentar_pending']  = $db->query("SELECT COUNT(*) FROM komentar WHERE status='pending'")->fetchColumn();
    $stats['total_menu']        = $db->query("SELECT COUNT(*) FROM menu WHERE aktif=1")->fetchColumn();
    $stats['reservasi_hari']    = $db->query("SELECT COUNT(*) FROM reservasi WHERE tanggal=CURDATE()")->fetchColumn();

    // Revenue 7 hari terakhir untuk chart
    $revenueRows = $db->query("
        SELECT DATE(created_at) as tgl, COALESCE(SUM(total),0) as total
        FROM pesanan
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          AND status NOT IN ('cancelled')
        GROUP BY DATE(created_at)
        ORDER BY tgl ASC
    ")->fetchAll();
    // Isi 7 hari dengan 0 kalau tidak ada data
    for ($i = 6; $i >= 0; $i--) {
        $tgl = date('Y-m-d', strtotime("-$i days"));
        $revenueChart[$tgl] = 0;
    }
    foreach ($revenueRows as $row) {
        $revenueChart[$row['tgl']] = (int)$row['total'];
    }

    $pesananTerbaru = $db->query("SELECT * FROM pesanan ORDER BY created_at DESC LIMIT 15")->fetchAll();
    $reservasiList  = $db->query("SELECT * FROM reservasi ORDER BY tanggal DESC, waktu DESC LIMIT 15")->fetchAll();
    $komentarList   = $db->query("SELECT * FROM komentar ORDER BY created_at DESC LIMIT 15")->fetchAll();

    // Status distribusi pesanan
    $statusDist = $db->query("SELECT status, COUNT(*) as jml FROM pesanan GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
}

// ── UPDATE STATUS PESANAN ────────────────────────────────────
if ($isLoggedIn && isset($_GET['update_pesanan'])) {
    $id     = (int)$_GET['update_pesanan'];
    $status = $_GET['status'] ?? '';
    $valid  = ['pending','confirmed','preparing','ready','done','cancelled'];
    if (in_array($status, $valid)) {
        $db->prepare("UPDATE pesanan SET status=? WHERE id=?")->execute([$status, $id]);
    }
    header('Location: index.php?tab=pesanan');
    exit;
}

// ── UPDATE STATUS RESERVASI ───────────────────────────────────
if ($isLoggedIn && isset($_GET['update_reservasi'])) {
    $id     = (int)$_GET['update_reservasi'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['pending','confirmed','cancelled'])) {
        $db->prepare("UPDATE reservasi SET status=? WHERE id=?")->execute([$status, $id]);
    }
    header('Location: index.php?tab=reservasi');
    exit;
}

// ── UPDATE STATUS KOMENTAR ────────────────────────────────────
if ($isLoggedIn && isset($_GET['update_komentar'])) {
    $id     = (int)$_GET['update_komentar'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['pending','approved','spam'])) {
        $db->prepare("UPDATE komentar SET status=? WHERE id=?")->execute([$status, $id]);
    }
    header('Location: index.php?tab=komentar');
    exit;
}

// ── ACTIVE TAB ────────────────────────────────────────────────
$activeTab = $_GET['tab'] ?? 'dashboard';

// ── REVENUE CHART DATA ────────────────────────────────────────
$chartLabels = [];
$chartData   = [];
if ($isLoggedIn) {
    foreach ($revenueChart as $tgl => $total) {
        $chartLabels[] = date('d M', strtotime($tgl));
        $chartData[]   = $total;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — Satu Seduh</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ═══════════════════════════════════════════
   SATU SEDUH ADMIN — CSS
   ═══════════════════════════════════════════ */

:root {
  /* Brand Colors */
  --gold:    #d4a55a;
  --gold2:   #e8c07a;
  --gold3:   #f0d090;
  --gold-dim: rgba(212,165,90,0.15);
  --gold-glow: rgba(212,165,90,0.08);

  /* Dark Palette */
  --bg:      #080502;
  --bg1:     #0d0805;
  --bg2:     #120c06;
  --bg3:     #181209;
  --surface: #1c1409;
  --surface2:#22180c;
  --card:    #1e1609;

  /* Text */
  --cream:   #f0e6d3;
  --cream2:  #c8b899;
  --muted:   #7a6545;
  --muted2:  #4a3d28;

  /* Borders */
  --border:  rgba(212,165,90,0.12);
  --border2: rgba(212,165,90,0.25);
  --border3: rgba(212,165,90,0.4);

  /* Status */
  --pending:   #f59e0b;
  --confirmed: #10b981;
  --preparing: #3b82f6;
  --ready:     #8b5cf6;
  --done:      #6b7280;
  --cancelled: #ef4444;

  /* Spacing */
  --sidebar-w: 260px;
  --header-h:  70px;
  --radius:    12px;
  --radius-lg: 18px;
  --radius-sm: 8px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--cream);
  min-height: 100vh;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: var(--bg1); }
::-webkit-scrollbar-thumb { background: var(--muted2); border-radius: 2px; }
::-webkit-scrollbar-thumb:hover { background: var(--gold); }

/* ═══════════════════════════════════════════
   LOGIN PAGE — Split Layout
   ═══════════════════════════════════════════ */
.login-page {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
  overflow: hidden;
}

/* ── Left panel: coffee image ── */
.login-left {
  position: relative;
  overflow: hidden;
  background: var(--bg1);
}

.login-left-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  filter: brightness(0.6) saturate(0.85);
}

.login-left-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(135deg, rgba(8,5,2,0.55) 0%, rgba(8,5,2,0.2) 60%, rgba(8,5,2,0.7) 100%),
    linear-gradient(to right, rgba(8,5,2,0.3) 0%, transparent 50%);
}

.login-left-content {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 52px 48px;
}

.login-left-logo {
  font-family: 'Cormorant Garamond', serif;
  font-size: 2.8rem;
  font-weight: 700;
  font-style: italic;
  color: var(--cream);
  letter-spacing: -1px;
  line-height: 1;
  margin-bottom: 16px;
}
.login-left-logo span { color: var(--gold); }

.login-left-desc {
  font-size: 0.82rem;
  color: rgba(240,230,211,0.6);
  line-height: 1.7;
  max-width: 280px;
  letter-spacing: 0.01em;
}

.login-left-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 28px;
  background: rgba(212,165,90,0.12);
  border: 1px solid rgba(212,165,90,0.25);
  border-radius: 100px;
  padding: 6px 14px;
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--gold);
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

.login-left-badge::before {
  content: '';
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--gold);
  flex-shrink: 0;
}

/* ── Right panel: form ── */
.login-right {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  padding: 60px 56px;
  overflow: hidden;
}

.login-right::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 70% 50% at 50% 0%, rgba(212,165,90,0.05) 0%, transparent 65%),
    radial-gradient(ellipse 50% 40% at 100% 100%, rgba(212,165,90,0.03) 0%, transparent 60%);
  pointer-events: none;
}

.login-right-inner {
  position: relative;
  width: 100%;
  max-width: 380px;
}

.login-form-header {
  margin-bottom: 40px;
}

.login-form-welcome {
  font-family: 'Cormorant Garamond', serif;
  font-size: 2.4rem;
  font-weight: 700;
  color: var(--cream);
  letter-spacing: -0.5px;
  line-height: 1.1;
  margin-bottom: 10px;
}

.login-form-welcome em {
  font-style: italic;
  color: var(--gold);
}

.login-form-subtitle {
  font-size: 0.82rem;
  color: var(--muted);
  line-height: 1.6;
}

/* ── Separator ── */
.login-separator {
  width: 36px;
  height: 1px;
  background: linear-gradient(to right, var(--gold), transparent);
  margin: 20px 0;
}

/* ── Form fields ── */
.form-field {
  margin-bottom: 18px;
}

.form-field label {
  display: block;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 9px;
}

.form-field-wrap {
  position: relative;
}

.form-field-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted2);
  display: flex;
  align-items: center;
  pointer-events: none;
  transition: color 0.2s;
}

.form-field-wrap:focus-within .form-field-icon {
  color: var(--gold);
}

.form-field input {
  width: 100%;
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 13px 16px 13px 42px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.88rem;
  color: var(--cream);
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.form-field input:focus {
  border-color: var(--border3);
  background: var(--bg3);
  box-shadow: 0 0 0 3px rgba(212,165,90,0.08);
}
.form-field input::placeholder { color: var(--muted2); }

/* Password toggle */
.pw-toggle {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--muted2);
  cursor: pointer;
  display: flex;
  align-items: center;
  padding: 4px;
  transition: color 0.2s;
}
.pw-toggle:hover { color: var(--gold); }

/* Remember & forgot row */
.login-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 4px 0 24px;
}

.login-remember {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  color: var(--muted);
  cursor: pointer;
  user-select: none;
}

.login-remember input[type="checkbox"] {
  width: 14px;
  height: 14px;
  accent-color: var(--gold);
  cursor: pointer;
  margin: 0;
}

/* ── Login button ── */
.btn-login {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold2) 100%);
  border: none;
  border-radius: var(--radius-sm);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.88rem;
  font-weight: 700;
  color: #0a0600;
  cursor: pointer;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  position: relative;
  overflow: hidden;
  transition: box-shadow 0.25s, transform 0.15s;
}

.btn-login::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
  opacity: 0;
  transition: opacity 0.2s;
}

.btn-login:hover {
  box-shadow: 0 8px 28px rgba(212,165,90,0.35);
  transform: translateY(-1px);
}
.btn-login:hover::after { opacity: 1; }
.btn-login:active { transform: translateY(0) scale(0.99); box-shadow: none; }

/* ── Error state ── */
.login-error {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.25);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  font-size: 0.8rem;
  color: #fca5a5;
  margin-top: 16px;
}

.login-hint {
  text-align: center;
  margin-top: 24px;
  font-size: 0.7rem;
  color: var(--muted2);
  letter-spacing: 0.03em;
}

.login-copyright {
  position: absolute;
  bottom: 28px;
  left: 0;
  right: 0;
  text-align: center;
  font-size: 0.68rem;
  color: var(--muted2);
  letter-spacing: 0.04em;
}

/* ── Responsive: stack on mobile ── */
@media (max-width: 768px) {
  .login-page { grid-template-columns: 1fr; }
  .login-left { display: none; }
  .login-right { padding: 48px 28px; }
}

/* ═══════════════════════════════════════════
   ADMIN LAYOUT
   ═══════════════════════════════════════════ */
.admin-layout {
  display: flex;
  min-height: 100vh;
}

/* ── SIDEBAR ── */
.sidebar {
  width: var(--sidebar-w);
  background: var(--bg1);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  z-index: 100;
  overflow-y: auto;
}

.sidebar-brand {
  padding: 28px 24px 20px;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}

.sidebar-brand .wordmark {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.7rem;
  font-weight: 700;
  font-style: italic;
  color: var(--cream);
  display: block;
}
.sidebar-brand .wordmark span { color: var(--gold); }
.sidebar-brand .sub {
  font-size: 0.65rem;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--muted);
  display: block;
  margin-top: 3px;
}

.sidebar-nav {
  padding: 20px 12px;
  flex: 1;
}

.nav-section-label {
  font-size: 0.62rem;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--muted2);
  padding: 0 12px;
  margin-bottom: 8px;
  margin-top: 20px;
}
.nav-section-label:first-child { margin-top: 0; }

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  color: var(--cream2);
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 400;
  transition: all 0.15s;
  margin-bottom: 2px;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}
.nav-item:hover {
  background: var(--gold-dim);
  color: var(--gold2);
}
.nav-item.active {
  background: var(--gold-dim);
  color: var(--gold);
  font-weight: 500;
}
.nav-item .nav-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  opacity: 0.7;
}
.nav-item.active .nav-icon { opacity: 1; }
.nav-item .badge-count {
  margin-left: auto;
  background: var(--gold);
  color: #0a0600;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 20px;
  line-height: 1.4;
}

.sidebar-footer {
  padding: 16px 12px;
  border-top: 1px solid var(--border);
  flex-shrink: 0;
}

.admin-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  margin-bottom: 8px;
}
.admin-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold2));
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Cormorant Garamond', serif;
  font-size: 1rem;
  font-weight: 700;
  color: #0a0600;
  flex-shrink: 0;
}
.admin-name {
  font-size: 0.82rem;
  font-weight: 500;
  color: var(--cream);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.admin-role {
  font-size: 0.68rem;
  color: var(--muted);
}

/* ── MAIN CONTENT ── */
.main-content {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ── TOP HEADER ── */
.top-header {
  height: var(--header-h);
  background: var(--bg1);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.page-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.35rem;
  font-weight: 600;
  font-style: italic;
  color: var(--cream);
}
.page-subtitle {
  font-size: 0.72rem;
  color: var(--muted);
  margin-top: 1px;
  font-family: 'DM Sans', sans-serif;
  font-style: normal;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-time {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.78rem;
  color: var(--muted);
  background: var(--bg2);
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
}

.header-badge {
  position: relative;
}
.header-badge-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  background: var(--bg2);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: border-color 0.2s;
  color: var(--cream2);
}
.header-badge-icon:hover { border-color: var(--border2); color: var(--gold); }
.header-badge-dot {
  position: absolute;
  top: -3px; right: -3px;
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--gold);
  border: 2px solid var(--bg1);
}

.btn-logout {
  display: flex;
  align-items: center;
  gap: 7px;
  background: none;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 7px 14px;
  color: var(--muted);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-logout:hover {
  border-color: rgba(239,68,68,0.4);
  color: #fca5a5;
  background: rgba(239,68,68,0.06);
}

/* ── PAGE BODY ── */
.page-body {
  padding: 32px;
  flex: 1;
}

/* ── STAT CARDS ── */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 28px;
}

.stat-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px 24px;
  position: relative;
  overflow: hidden;
  transition: border-color 0.2s, transform 0.15s;
}
.stat-card:hover {
  border-color: var(--border2);
  transform: translateY(-1px);
}
.stat-card::after {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 80px; height: 80px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(212,165,90,0.06), transparent 70%);
  transform: translate(30%, -30%);
}

.stat-icon {
  width: 36px; height: 36px;
  border-radius: var(--radius-sm);
  background: var(--gold-dim);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16px;
  color: var(--gold);
}
.stat-val {
  font-family: 'Cormorant Garamond', serif;
  font-size: 2rem;
  font-weight: 700;
  color: var(--cream);
  line-height: 1;
  margin-bottom: 6px;
}
.stat-label {
  font-size: 0.72rem;
  color: var(--muted);
  font-weight: 400;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.stat-sub {
  font-size: 0.7rem;
  color: var(--gold);
  margin-top: 8px;
  font-weight: 500;
}

/* ── CHART + SIDEBAR SECTION ── */
.dashboard-middle {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  margin-bottom: 28px;
}

.chart-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.card-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--cream);
  letter-spacing: 0.02em;
}
.card-subtitle {
  font-size: 0.7rem;
  color: var(--muted);
  margin-top: 2px;
}
.card-badge {
  font-size: 0.68rem;
  font-weight: 600;
  background: var(--gold-dim);
  color: var(--gold);
  padding: 3px 10px;
  border-radius: 20px;
  border: 1px solid rgba(212,165,90,0.2);
}

.chart-wrap {
  height: 200px;
  position: relative;
}

/* ── STATUS DIST CARD ── */
.status-dist-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 24px;
}

.status-dist-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.status-dist-item:last-child { margin-bottom: 0; }
.status-dist-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.8rem;
  color: var(--cream2);
}
.status-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}
.status-dist-bar-wrap {
  flex: 1;
  margin: 0 12px;
  height: 4px;
  background: var(--bg2);
  border-radius: 2px;
  overflow: hidden;
}
.status-dist-bar {
  height: 100%;
  border-radius: 2px;
  transition: width 0.8s ease;
}
.status-dist-num {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.75rem;
  color: var(--muted);
  min-width: 28px;
  text-align: right;
}

/* ── TABLES ── */
.table-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 20px;
}

.table-wrap {
  overflow-x: auto;
}

table.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.data-table thead th {
  background: var(--surface);
  padding: 13px 16px;
  text-align: left;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--muted);
  white-space: nowrap;
  border-bottom: 1px solid var(--border);
}

.data-table tbody tr {
  border-bottom: 1px solid rgba(212,165,90,0.06);
  transition: background 0.1s;
}
.data-table tbody tr:last-child { border-bottom: none; }
.data-table tbody tr:hover { background: var(--gold-glow); }

.data-table tbody td {
  padding: 12px 16px;
  color: var(--cream2);
  vertical-align: middle;
}

.td-mono {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.75rem;
  color: var(--gold);
}

.td-name {
  font-weight: 500;
  color: var(--cream);
}

/* ── BADGES ── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.badge::before {
  content: '';
  width: 5px; height: 5px;
  border-radius: 50%;
  background: currentColor;
  opacity: 0.7;
}
.badge.pending    { background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2); }
.badge.confirmed  { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
.badge.preparing  { background: rgba(59,130,246,0.12); color: #60a5fa; border: 1px solid rgba(59,130,246,0.2); }
.badge.ready      { background: rgba(139,92,246,0.12); color: #a78bfa; border: 1px solid rgba(139,92,246,0.2); }
.badge.done       { background: rgba(107,114,128,0.12); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2); }
.badge.cancelled  { background: rgba(239,68,68,0.12);  color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
.badge.approved   { background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
.badge.spam       { background: rgba(239,68,68,0.12);  color: #f87171; border: 1px solid rgba(239,68,68,0.2); }

/* ── SELECT STATUS ── */
.status-select {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 5px 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.75rem;
  color: var(--cream);
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%237a6545'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 8px center;
  padding-right: 26px;
}
.status-select:hover, .status-select:focus { border-color: var(--border2); }

/* ── TAB BUTTONS ── */
.tab-bar {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid var(--border);
  padding: 0 32px;
  background: var(--bg1);
  overflow-x: auto;
}
.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 18px;
  border: none;
  background: none;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.82rem;
  font-weight: 500;
  color: var(--muted);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.15s;
  white-space: nowrap;
  text-decoration: none;
}
.tab-btn:hover { color: var(--cream2); }
.tab-btn.active {
  color: var(--gold);
  border-bottom-color: var(--gold);
}
.tab-count {
  background: var(--gold-dim);
  color: var(--gold);
  font-size: 0.64rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 20px;
}

/* ── EMPTY STATE ── */
.empty-state {
  text-align: center;
  padding: 48px 24px;
}
.empty-icon {
  width: 48px; height: 48px;
  margin: 0 auto 16px;
  color: var(--muted2);
}
.empty-text {
  font-size: 0.85rem;
  color: var(--muted);
}

/* ── KOMENTAR CARD ── */
.komentar-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px;
  margin-bottom: 12px;
  transition: border-color 0.2s;
}
.komentar-card:hover { border-color: var(--border2); }
.komentar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  gap: 12px;
}
.komentar-name {
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--cream);
}
.komentar-email {
  font-size: 0.72rem;
  color: var(--muted);
  margin-top: 1px;
}
.komentar-text {
  font-size: 0.82rem;
  color: var(--cream2);
  line-height: 1.6;
  border-left: 2px solid var(--border2);
  padding-left: 12px;
  margin-bottom: 12px;
}
.komentar-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.komentar-time {
  font-size: 0.7rem;
  color: var(--muted);
  font-family: 'JetBrains Mono', monospace;
}

/* ── LIVE INDICATOR ── */
.live-dot {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.68rem;
  color: #34d399;
  font-weight: 500;
}
.live-dot::before {
  content: '';
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #34d399;
  box-shadow: 0 0 6px #34d399;
  animation: pulse-live 2s infinite;
}
@keyframes pulse-live {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

/* ── ICON SVGs (inline) ── */
.icon { display: inline-flex; flex-shrink: 0; }

/* ── RUPIAH ── */
.rupiah-val {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.8rem;
  color: var(--gold);
}

/* ── LINK EXTERNAL ── */
.btn-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: none;
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 5px 12px;
  font-size: 0.75rem;
  color: var(--cream2);
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
  font-family: 'DM Sans', sans-serif;
}
.btn-link:hover {
  border-color: var(--gold);
  color: var(--gold);
}

/* ── RESPONSIVE ── */
@media (max-width: 1200px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .dashboard-middle { grid-template-columns: 1fr; }
}
@media (max-width: 900px) {
  :root { --sidebar-w: 0px; }
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); width: 260px; }
  .main-content { margin-left: 0; }
}

/* ── FADE-IN ANIMATION ── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fadeUp 0.35s ease both; }
.fade-up:nth-child(2) { animation-delay: 0.05s; }
.fade-up:nth-child(3) { animation-delay: 0.10s; }
.fade-up:nth-child(4) { animation-delay: 0.15s; }
.fade-up:nth-child(5) { animation-delay: 0.20s; }
.fade-up:nth-child(6) { animation-delay: 0.25s; }
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ═══════════════════════════════════════════
     LOGIN PAGE — Split Layout
     ═══════════════════════════════════════════ -->
<div class="login-page">

  <!-- LEFT: Coffee image panel -->
  <div class="login-left">
    <img
      class="login-left-img"
      src="../img/menu/coffee-beans.svg"
      alt="Coffee Beans"
      onerror="this.style.display='none'"
    >
    <!-- Fallback: dark gradient background -->
    <div style="position:absolute;inset:0;background:linear-gradient(145deg,#1a0e04 0%,#0d0502 50%,#080300 100%);"></div>
    <!-- Decorative coffee rings SVG -->
    <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.07;" viewBox="0 0 600 700" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
      <circle cx="300" cy="350" r="220" fill="none" stroke="#d4a55a" stroke-width="1.5"/>
      <circle cx="300" cy="350" r="160" fill="none" stroke="#d4a55a" stroke-width="1"/>
      <circle cx="300" cy="350" r="100" fill="none" stroke="#d4a55a" stroke-width="0.8"/>
      <circle cx="300" cy="350" r="50"  fill="none" stroke="#d4a55a" stroke-width="0.6"/>
      <circle cx="80"  cy="120" r="80"  fill="none" stroke="#d4a55a" stroke-width="0.5"/>
      <circle cx="520" cy="580" r="110" fill="none" stroke="#d4a55a" stroke-width="0.5"/>
      <!-- coffee bean shapes -->
      <ellipse cx="300" cy="350" rx="28" ry="44" fill="rgba(212,165,90,0.18)" transform="rotate(25 300 350)"/>
      <line x1="300" y1="306" x2="300" y2="394" stroke="#d4a55a" stroke-width="0.8" transform="rotate(25 300 350)" opacity="0.3"/>
      <ellipse cx="200" cy="200" rx="18" ry="28" fill="rgba(212,165,90,0.1)" transform="rotate(-15 200 200)"/>
      <line x1="200" y1="172" x2="200" y2="228" stroke="#d4a55a" stroke-width="0.6" transform="rotate(-15 200 200)" opacity="0.2"/>
      <ellipse cx="420" cy="480" rx="22" ry="34" fill="rgba(212,165,90,0.12)" transform="rotate(40 420 480)"/>
      <line x1="420" y1="446" x2="420" y2="514" stroke="#d4a55a" stroke-width="0.6" transform="rotate(40 420 480)" opacity="0.25"/>
      <ellipse cx="130" cy="500" rx="16" ry="26" fill="rgba(212,165,90,0.08)" transform="rotate(-30 130 500)"/>
      <ellipse cx="480" cy="150" rx="20" ry="32" fill="rgba(212,165,90,0.1)" transform="rotate(60 480 150)"/>
    </svg>
    <div class="login-left-overlay"></div>
    <div class="login-left-content">
      <div class="login-left-logo">satu<span>seduh</span>.</div>
      <p class="login-left-desc">Platform manajemen bisnis kopi Anda. Kelola pesanan, reservasi, dan menu dengan mudah.</p>
      <span class="login-left-badge">Admin Dashboard</span>
    </div>
  </div>

  <!-- RIGHT: Login form -->
  <div class="login-right">
    <div class="login-right-inner">
      <div class="login-form-header">
        <h1 class="login-form-welcome">Welcome<br><em>Back!</em></h1>
        <p class="login-form-subtitle">Silakan login untuk melanjutkan ke dashboard.</p>
        <div class="login-separator"></div>
      </div>

      <form method="POST" autocomplete="off">
        <!-- Username -->
        <div class="form-field">
          <label for="username">Username</label>
          <div class="form-field-wrap">
            <span class="form-field-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
            </span>
            <input
              type="text"
              id="username"
              name="username"
              required
              autocomplete="username"
              placeholder="Masukkan username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            >
          </div>
        </div>

        <!-- Password -->
        <div class="form-field">
          <label for="password">Password</label>
          <div class="form-field-wrap">
            <span class="form-field-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input
              type="password"
              id="password"
              name="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
            >
            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
              <svg id="eyeShow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg id="eyeHide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Remember me -->
        <div class="login-meta">
          <label class="login-remember">
            <input type="checkbox" name="remember" id="remember">
            <span>Ingat saya</span>
          </label>
        </div>

        <button type="submit" name="login" class="btn-login">Login</button>

        <?php if (isset($loginError)): ?>
          <div class="login-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
              <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= htmlspecialchars($loginError) ?>
          </div>
        <?php endif; ?>
      </form>

      <p class="login-hint">Default: admin / password</p>
    </div>

    <p class="login-copyright">© <?= date('Y') ?> Satu Seduh. All rights reserved.</p>
  </div>
</div>

<script>
// Password toggle
(function(){
  const toggle = document.getElementById('pwToggle');
  const input  = document.getElementById('password');
  const show   = document.getElementById('eyeShow');
  const hide   = document.getElementById('eyeHide');
  if (!toggle || !input) return;
  toggle.addEventListener('click', function() {
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    show.style.display = isPass ? 'none' : '';
    hide.style.display = isPass ? ''     : 'none';
  });
})();
</script>

<?php else: ?>
<!-- ═══════════════════════════════════════════
     ADMIN DASHBOARD
     ═══════════════════════════════════════════ -->
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="wordmark">satu<span>seduh</span>.</span>
      <span class="sub">Admin Panel</span>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Utama</div>

      <a href="index.php?tab=dashboard" class="nav-item <?= $activeTab==='dashboard' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="2" y="2" width="6" height="6" rx="1.5"/><rect x="12" y="2" width="6" height="6" rx="1.5"/>
          <rect x="2" y="12" width="6" height="6" rx="1.5"/><rect x="12" y="12" width="6" height="6" rx="1.5"/>
        </svg>
        Dashboard
      </a>

      <div class="nav-section-label">Kelola</div>

      <a href="index.php?tab=pesanan" class="nav-item <?= $activeTab==='pesanan' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
          <path d="M3 3h14l-1.5 9H4.5L3 3z"/><circle cx="8" cy="17" r="1.2"/><circle cx="14" cy="17" r="1.2"/>
        </svg>
        Pesanan
        <?php if ($stats['pesanan_hari_ini'] > 0): ?>
          <span class="badge-count"><?= $stats['pesanan_hari_ini'] ?></span>
        <?php endif; ?>
      </a>

      <a href="index.php?tab=reservasi" class="nav-item <?= $activeTab==='reservasi' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="3" y="4" width="14" height="13" rx="2"/><path d="M7 2v4M13 2v4M3 9h14"/>
        </svg>
        Reservasi
        <?php if ($stats['reservasi_pending'] > 0): ?>
          <span class="badge-count"><?= $stats['reservasi_pending'] ?></span>
        <?php endif; ?>
      </a>

      <a href="index.php?tab=komentar" class="nav-item <?= $activeTab==='komentar' ? 'active' : '' ?>">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
          <path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H6l-4 3V4z"/>
        </svg>
        Komentar
        <?php if ($stats['komentar_pending'] > 0): ?>
          <span class="badge-count"><?= $stats['komentar_pending'] ?></span>
        <?php endif; ?>
      </a>

      <div class="nav-section-label">Lainnya</div>

      <a href="../index.php" target="_blank" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
          <circle cx="10" cy="10" r="8"/><path d="M2 10h16M10 2a14 14 0 010 16M10 2a14 14 0 000 16"/>
        </svg>
        Lihat Website
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-info">
        <div class="admin-avatar"><?= mb_substr($_SESSION['admin_nama'] ?? 'A', 0, 1) ?></div>
        <div>
          <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></div>
          <div class="admin-role">Administrator</div>
        </div>
      </div>
      <form method="POST" style="margin:0">
        <button type="submit" name="logout" class="nav-item" style="margin-bottom:0;color:var(--muted);">
          <svg class="nav-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M13 15l4-4-4-4M17 11H7M10 3H5a2 2 0 00-2 2v10a2 2 0 002 2h5"/>
          </svg>
          Keluar
        </button>
      </form>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main-content">

    <!-- TOP HEADER -->
    <header class="top-header">
      <div>
        <?php
        $titles = [
          'dashboard' => ['Dashboard', 'Ringkasan aktivitas hari ini'],
          'pesanan'   => ['Manajemen Pesanan', 'Kelola semua pesanan masuk'],
          'reservasi' => ['Manajemen Reservasi', 'Kelola booking meja & ruangan'],
          'komentar'  => ['Manajemen Komentar', 'Moderasi ulasan pelanggan'],
        ];
        $t = $titles[$activeTab] ?? $titles['dashboard'];
        ?>
        <div class="page-title"><?= $t[0] ?></div>
        <div class="page-subtitle"><?= $t[1] ?></div>
      </div>
      <div class="header-right">
        <div class="live-dot">Live</div>
        <div class="header-time" id="clock">--:--:--</div>
        <?php if ($stats['komentar_pending'] > 0 || $stats['reservasi_pending'] > 0): ?>
        <div class="header-badge">
          <div class="header-badge-icon">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M10 2a6 6 0 016 6c0 3.5 1.5 5 1.5 5H2.5S4 11.5 4 8a6 6 0 016-6zM7.5 15a2.5 2.5 0 005 0"/>
            </svg>
          </div>
          <div class="header-badge-dot"></div>
        </div>
        <?php endif; ?>
        <form method="POST" style="margin:0">
          <button type="submit" name="logout" class="btn-logout">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M13 15l4-4-4-4M17 11H7M10 3H5a2 2 0 00-2 2v10a2 2 0 002 2h5"/>
            </svg>
            Keluar
          </button>
        </form>
      </div>
    </header>

    <?php if ($activeTab === 'dashboard'): ?>
    <!-- ═══════════════════════
         DASHBOARD TAB
         ═══════════════════════ -->
    <div class="page-body">

      <!-- Stat Cards -->
      <div class="stat-grid">
        <div class="stat-card fade-up">
          <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M3 3h14l-1.5 9H4.5L3 3z"/><circle cx="8" cy="17" r="1.2"/><circle cx="14" cy="17" r="1.2"/>
            </svg>
          </div>
          <div class="stat-val"><?= $stats['pesanan_hari_ini'] ?></div>
          <div class="stat-label">Pesanan Hari Ini</div>
          <div class="stat-sub">Total: <?= $stats['total_pesanan'] ?> pesanan</div>
        </div>

        <div class="stat-card fade-up">
          <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="10" cy="10" r="8"/><path d="M10 6v4l2.5 2.5"/>
            </svg>
          </div>
          <div class="stat-val"><?= number_format((int)$stats['pendapatan_hari']/1000) ?>K</div>
          <div class="stat-label">Pendapatan Hari Ini</div>
          <div class="stat-sub">Bulan ini: Rp <?= number_format((int)$stats['pendapatan_bulan']/1000) ?>K</div>
        </div>

        <div class="stat-card fade-up">
          <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <rect x="3" y="4" width="14" height="13" rx="2"/><path d="M7 2v4M13 2v4M3 9h14"/>
            </svg>
          </div>
          <div class="stat-val"><?= $stats['reservasi_pending'] ?></div>
          <div class="stat-label">Reservasi Pending</div>
          <div class="stat-sub">Hari ini: <?= $stats['reservasi_hari'] ?> reservasi</div>
        </div>

        <div class="stat-card fade-up">
          <div class="stat-icon">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M2 4a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H6l-4 3V4z"/>
            </svg>
          </div>
          <div class="stat-val"><?= $stats['komentar_pending'] ?></div>
          <div class="stat-label">Komentar Belum Ditinjau</div>
          <div class="stat-sub">Menu aktif: <?= $stats['total_menu'] ?> item</div>
        </div>
      </div>

      <!-- Chart + Status Dist -->
      <div class="dashboard-middle">
        <div class="chart-card fade-up">
          <div class="card-header">
            <div>
              <div class="card-title">Revenue 7 Hari Terakhir</div>
              <div class="card-subtitle">Pendapatan dalam IDR</div>
            </div>
            <span class="card-badge">7 Hari</span>
          </div>
          <div class="chart-wrap">
            <canvas id="revenueChart"></canvas>
          </div>
        </div>

        <div class="status-dist-card fade-up">
          <div class="card-header">
            <div>
              <div class="card-title">Status Pesanan</div>
              <div class="card-subtitle">Distribusi semua pesanan</div>
            </div>
          </div>
          <?php
          $statusColors = [
            'pending'   => '#fbbf24',
            'confirmed' => '#34d399',
            'preparing' => '#60a5fa',
            'ready'     => '#a78bfa',
            'done'      => '#9ca3af',
            'cancelled' => '#f87171',
          ];
          $statusLabels = [
            'pending'   => 'Pending',
            'confirmed' => 'Confirmed',
            'preparing' => 'Preparing',
            'ready'     => 'Ready',
            'done'      => 'Selesai',
            'cancelled' => 'Dibatalkan',
          ];
          $totalPesanan = max(1, (int)$stats['total_pesanan']);
          foreach ($statusColors as $status => $color):
            $jml = (int)($statusDist[$status] ?? 0);
            $pct = round($jml / $totalPesanan * 100);
          ?>
          <div class="status-dist-item">
            <div class="status-dist-label">
              <span class="status-dot" style="background:<?= $color ?>"></span>
              <?= $statusLabels[$status] ?>
            </div>
            <div class="status-dist-bar-wrap">
              <div class="status-dist-bar" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
            </div>
            <span class="status-dist-num"><?= $jml ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent Pesanan -->
      <div class="table-card fade-up">
        <div class="card-header" style="padding:20px 24px 0;">
          <div>
            <div class="card-title">Pesanan Terbaru</div>
            <div class="card-subtitle">10 pesanan terakhir</div>
          </div>
          <a href="index.php?tab=pesanan" class="btn-link">
            Lihat Semua
            <svg width="12" height="12" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M13 7l3 3-3 3"/></svg>
          </a>
        </div>
        <div class="table-wrap" style="margin-top:16px;">
          <table class="data-table">
            <thead>
              <tr>
                <th>No. Pesanan</th>
                <th>Nama</th>
                <th>Meja</th>
                <th>Total</th>
                <th>Bayar</th>
                <th>Status</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($pesananTerbaru, 0, 10) as $p): ?>
              <tr>
                <td class="td-mono"><?= htmlspecialchars($p['nomor_pesanan']) ?></td>
                <td class="td-name"><?= htmlspecialchars($p['nama_pemesan']) ?></td>
                <td><?= htmlspecialchars($p['nomor_meja'] ?: '—') ?></td>
                <td class="rupiah-val"><?= rupiah((int)$p['total']) ?></td>
                <td style="text-transform:uppercase;font-size:0.75rem;"><?= htmlspecialchars($p['metode_bayar']) ?></td>
                <td><span class="badge <?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td style="font-size:0.75rem;color:var(--muted);"><?= date('d/m H:i', strtotime($p['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($pesananTerbaru)): ?>
              <tr><td colspan="7"><div class="empty-state"><div class="empty-text">Belum ada pesanan</div></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body dashboard -->

    <?php elseif ($activeTab === 'pesanan'): ?>
    <!-- ═══════════════════════
         PESANAN TAB
         ═══════════════════════ -->
    <div class="page-body">
      <div class="table-card">
        <div class="card-header" style="padding:24px 24px 0;">
          <div>
            <div class="card-title">Semua Pesanan</div>
            <div class="card-subtitle"><?= count($pesananTerbaru) ?> pesanan terbaru ditampilkan</div>
          </div>
          <span class="live-dot">Auto-refresh aktif</span>
        </div>
        <div class="table-wrap" style="margin-top:20px;">
          <table class="data-table">
            <thead>
              <tr>
                <th>No. Pesanan</th>
                <th>Nama Pemesan</th>
                <th>No. Telepon</th>
                <th>Meja</th>
                <th>Subtotal</th>
                <th>Total</th>
                <th>Metode Bayar</th>
                <th>Status</th>
                <th>Catatan</th>
                <th>Waktu</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pesananTerbaru as $p): ?>
              <tr>
                <td class="td-mono"><?= htmlspecialchars($p['nomor_pesanan']) ?></td>
                <td class="td-name"><?= htmlspecialchars($p['nama_pemesan']) ?></td>
                <td style="font-size:0.78rem;"><?= htmlspecialchars($p['no_telepon'] ?: '—') ?></td>
                <td><?= htmlspecialchars($p['nomor_meja'] ?: '—') ?></td>
                <td class="rupiah-val"><?= rupiah((int)$p['subtotal']) ?></td>
                <td class="rupiah-val"><?= rupiah((int)$p['total']) ?></td>
                <td style="text-transform:uppercase;font-size:0.75rem;"><?= htmlspecialchars($p['metode_bayar']) ?></td>
                <td>
                  <select class="status-select"
                    onchange="location='index.php?tab=pesanan&update_pesanan=<?= $p['id'] ?>&status='+this.value">
                    <?php foreach (['pending','confirmed','preparing','ready','done','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $p['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td style="max-width:180px;font-size:0.75rem;color:var(--muted);">
                  <?= htmlspecialchars(mb_substr($p['catatan'] ?: '—', 0, 60)) ?>
                </td>
                <td style="font-size:0.75rem;color:var(--muted);white-space:nowrap;">
                  <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($pesananTerbaru)): ?>
              <tr><td colspan="10">
                <div class="empty-state">
                  <svg class="empty-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M8 8h32l-4 24H12L8 8z"/><circle cx="19" cy="41" r="3"/><circle cx="33" cy="41" r="3"/>
                  </svg>
                  <div class="empty-text">Belum ada pesanan yang masuk</div>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php elseif ($activeTab === 'reservasi'): ?>
    <!-- ═══════════════════════
         RESERVASI TAB
         ═══════════════════════ -->
    <div class="page-body">
      <div class="table-card">
        <div class="card-header" style="padding:24px 24px 0;">
          <div>
            <div class="card-title">Semua Reservasi</div>
            <div class="card-subtitle"><?= count($reservasiList) ?> reservasi ditampilkan</div>
          </div>
          <?php if ($stats['reservasi_pending'] > 0): ?>
            <span class="badge pending"><?= $stats['reservasi_pending'] ?> Pending</span>
          <?php endif; ?>
        </div>
        <div class="table-wrap" style="margin-top:20px;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>WhatsApp</th>
                <th>Ruangan</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Durasi</th>
                <th>Jumlah Orang</th>
                <th>Catatan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservasiList as $r): ?>
              <tr>
                <td class="td-name"><?= htmlspecialchars($r['nama']) ?></td>
                <td>
                  <a href="https://wa.me/<?= preg_replace('/\D/', '', $r['whatsapp']) ?>"
                     target="_blank" class="btn-link" style="font-size:0.72rem;">
                    <?= htmlspecialchars($r['whatsapp']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($r['ruangan']) ?></td>
                <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;"><?= htmlspecialchars($r['waktu']) ?></td>
                <td style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($r['durasi'] ?: '—') ?></td>
                <td style="text-align:center;"><?= $r['jumlah_orang'] ?: '—' ?></td>
                <td style="max-width:160px;font-size:0.75rem;color:var(--muted);">
                  <?= htmlspecialchars(mb_substr($r['catatan'] ?? '—', 0, 60)) ?>
                </td>
                <td>
                  <select class="status-select"
                    onchange="location='index.php?tab=reservasi&update_reservasi=<?= $r['id'] ?>&status='+this.value">
                    <?php foreach (['pending','confirmed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $r['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($reservasiList)): ?>
              <tr><td colspan="9">
                <div class="empty-state">
                  <svg class="empty-icon" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="6" y="10" width="36" height="32" rx="4"/><path d="M16 6v8M32 6v8M6 22h36"/>
                  </svg>
                  <div class="empty-text">Belum ada reservasi</div>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php elseif ($activeTab === 'komentar'): ?>
    <!-- ═══════════════════════
         KOMENTAR TAB
         ═══════════════════════ -->
    <div class="page-body">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
          <div style="font-size:0.85rem;font-weight:600;color:var(--cream);">Semua Komentar</div>
          <div style="font-size:0.72rem;color:var(--muted);margin-top:2px;"><?= count($komentarList) ?> komentar ditampilkan</div>
        </div>
        <?php if ($stats['komentar_pending'] > 0): ?>
          <span class="badge pending"><?= $stats['komentar_pending'] ?> Menunggu Review</span>
        <?php endif; ?>
      </div>

      <?php if (empty($komentarList)): ?>
        <div class="komentar-card" style="text-align:center;padding:48px;">
          <div class="empty-text">Belum ada komentar</div>
        </div>
      <?php endif; ?>

      <?php foreach ($komentarList as $k): ?>
      <div class="komentar-card fade-up">
        <div class="komentar-header">
          <div>
            <div class="komentar-name"><?= htmlspecialchars($k['nama']) ?></div>
            <div class="komentar-email">
              <?= htmlspecialchars($k['email'] ?: '—') ?>
              <?php if ($k['no_hp'] ?? false): ?>
                · <?= htmlspecialchars($k['no_hp']) ?>
              <?php endif; ?>
            </div>
          </div>
          <span class="badge <?= $k['status'] ?>"><?= ucfirst($k['status']) ?></span>
        </div>
        <div class="komentar-text"><?= htmlspecialchars($k['pesan']) ?></div>
        <div class="komentar-footer">
          <span class="komentar-time"><?= date('d/m/Y H:i', strtotime($k['created_at'])) ?></span>
          <div style="display:flex;gap:8px;align-items:center;">
            <span style="font-size:0.72rem;color:var(--muted);">Ubah status:</span>
            <select class="status-select"
              onchange="location='index.php?tab=komentar&update_komentar=<?= $k['id'] ?>&status='+this.value">
              <?php foreach (['pending','approved','spam'] as $s): ?>
              <option value="<?= $s ?>" <?= $k['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

  </div><!-- /main-content -->
</div><!-- /admin-layout -->

<script>
// ── CLOCK ──
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2,'0');
  const m = String(now.getMinutes()).padStart(2,'0');
  const s = String(now.getSeconds()).padStart(2,'0');
  const el = document.getElementById('clock');
  if (el) el.textContent = `${h}:${m}:${s}`;
}
updateClock();
setInterval(updateClock, 1000);

// ── REVENUE CHART ──
<?php if ($activeTab === 'dashboard'): ?>
const ctx = document.getElementById('revenueChart');
if (ctx) {
  const chartLabels = <?= json_encode(array_values($chartLabels)) ?>;
  const chartData   = <?= json_encode(array_values($chartData)) ?>;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartLabels,
      datasets: [{
        label: 'Revenue (IDR)',
        data: chartData,
        borderColor: '#d4a55a',
        backgroundColor: 'rgba(212,165,90,0.08)',
        borderWidth: 2,
        pointBackgroundColor: '#d4a55a',
        pointBorderColor: '#120c06',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.4,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1c1409',
          borderColor: 'rgba(212,165,90,0.25)',
          borderWidth: 1,
          titleColor: '#d4a55a',
          bodyColor: '#c8b899',
          padding: 12,
          callbacks: {
            label: ctx => 'IDR ' + ctx.parsed.y.toLocaleString('id-ID')
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(212,165,90,0.06)', drawBorder: false },
          ticks: { color: '#7a6545', font: { size: 11, family: 'DM Sans' } }
        },
        y: {
          grid: { color: 'rgba(212,165,90,0.06)', drawBorder: false },
          ticks: {
            color: '#7a6545',
            font: { size: 11, family: 'JetBrains Mono' },
            callback: v => v >= 1000 ? (v/1000).toFixed(0)+'K' : v
          }
        }
      }
    }
  });
}
<?php endif; ?>

// ── AUTO REFRESH PESANAN ──
<?php if ($activeTab === 'pesanan'): ?>
setTimeout(() => location.reload(), 60000);
<?php endif; ?>
</script>

<?php endif; // isLoggedIn ?>
</body>
</html>
