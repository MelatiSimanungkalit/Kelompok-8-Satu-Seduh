<?php

require_once __DIR__ . '/../includes/config.php';

// Cek login sederhana
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
    } else {
        $loginError = 'Username atau password salah.';
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$isLoggedIn = !empty($_SESSION['admin_id']);

// Jika sudah login, ambil statistik
$stats = [];
if ($isLoggedIn) {
    $db = getDB();
    $stats['pesanan_hari_ini'] = $db->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $stats['total_pesanan']    = $db->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
    $stats['pendapatan_hari']  = $db->query("SELECT COALESCE(SUM(total),0) FROM pesanan WHERE DATE(created_at)=CURDATE() AND status NOT IN ('cancelled')")->fetchColumn();
    $stats['reservasi_pending']= $db->query("SELECT COUNT(*) FROM reservasi WHERE status='pending'")->fetchColumn();
    $stats['komentar_pending'] = $db->query("SELECT COUNT(*) FROM komentar WHERE status='pending'")->fetchColumn();

    // Pesanan terbaru
    $pesananTerbaru = $db->query("SELECT * FROM pesanan ORDER BY created_at DESC LIMIT 10")->fetchAll();
    $reservasiList  = $db->query("SELECT * FROM reservasi ORDER BY tanggal DESC, waktu DESC LIMIT 10")->fetchAll();
    $komentarList   = $db->query("SELECT * FROM komentar ORDER BY created_at DESC LIMIT 10")->fetchAll();
}

// Update status pesanan
if ($isLoggedIn && isset($_GET['update_pesanan'])) {
    $id     = (int)$_GET['update_pesanan'];
    $status = $_GET['status'] ?? '';
    $valid  = ['pending','confirmed','preparing','ready','done','cancelled'];
    if (in_array($status, $valid)) {
        $db->prepare("UPDATE pesanan SET status=? WHERE id=?")->execute([$status, $id]);
    }
    header('Location: index.php');
    exit;
}

// Update status reservasi
if ($isLoggedIn && isset($_GET['update_reservasi'])) {
    $id     = (int)$_GET['update_reservasi'];
    $status = $_GET['status'] ?? '';
    if (in_array($status, ['pending','confirmed','cancelled'])) {
        $db->prepare("UPDATE reservasi SET status=? WHERE id=?")->execute([$status, $id]);
    }
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Satu Seduh</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:#0f0a04;color:#f5e6c8;min-height:100vh;}
.login-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#0f0a04,#1a0f05);}
.login-box{background:#1e1208;border:1px solid #5a3e1b;border-radius:16px;padding:40px;width:360px;}
.login-box h2{color:#c9a84c;margin-bottom:24px;text-align:center;font-size:1.5rem;}
.login-box .logo{display:block;text-align:center;font-size:1.8rem;font-weight:700;color:#f5e6c8;margin-bottom:4px;}
.login-box .logo span{color:#c9a84c;}
.form-g{margin-bottom:16px;}
.form-g label{display:block;font-size:0.8rem;color:#c9a84c;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;}
.form-g input{width:100%;padding:10px 14px;background:#120b03;border:1px solid #5a3e1b;border-radius:8px;color:#f5e6c8;font-size:1rem;}
.btn-login{width:100%;padding:12px;background:#c9a84c;color:#120b03;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:8px;}
.btn-login:hover{background:#e0bc5a;}
.err{color:#e55;font-size:0.85rem;margin-top:12px;text-align:center;}

/* Admin layout */
.admin-wrap{display:flex;min-height:100vh;}
.sidebar{width:220px;background:#140d04;border-right:1px solid #2a1e0a;padding:24px 0;flex-shrink:0;}
.sidebar .logo{display:block;padding:0 20px 24px;font-size:1.4rem;font-weight:700;color:#f5e6c8;border-bottom:1px solid #2a1e0a;margin-bottom:16px;}
.sidebar .logo span{color:#c9a84c;}
.sidebar a{display:block;padding:10px 20px;color:rgba(245,230,200,0.7);text-decoration:none;font-size:0.9rem;transition:.2s;}
.sidebar a:hover,.sidebar a.active{color:#c9a84c;background:rgba(201,168,76,0.08);}
.sidebar .logout{margin-top:auto;padding:16px 20px 0;border-top:1px solid #2a1e0a;}

.main{flex:1;padding:24px;overflow:auto;}
.main h1{font-size:1.4rem;color:#c9a84c;margin-bottom:20px;}

.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:#1a1007;border:1px solid #2a1e0a;border-radius:12px;padding:20px;}
.stat-card .val{font-size:2rem;font-weight:700;color:#c9a84c;}
.stat-card .lbl{font-size:0.78rem;color:rgba(245,230,200,0.5);margin-top:4px;}

table{width:100%;border-collapse:collapse;margin-bottom:28px;font-size:0.85rem;}
th{background:#1a1007;color:#c9a84c;padding:10px 12px;text-align:left;font-weight:600;}
td{padding:10px 12px;border-bottom:1px solid #1e1408;vertical-align:top;}
tr:hover td{background:rgba(201,168,76,0.04);}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.badge.pending{background:#332200;color:#f90;}
.badge.confirmed{background:#002215;color:#0f9;}
.badge.preparing{background:#001a2a;color:#39f;}
.badge.ready{background:#1a2a00;color:#9f0;}
.badge.done{background:#1a1a1a;color:#999;}
.badge.cancelled{background:#2a0000;color:#f44;}
.badge.approved{background:#002215;color:#0f9;}
.badge.spam{background:#2a0000;color:#f44;}

.sec-title{font-size:1.1rem;color:#c9a84c;margin-bottom:14px;border-bottom:1px solid #2a1e0a;padding-bottom:8px;}
select.status-sel{background:#120b03;border:1px solid #5a3e1b;color:#f5e6c8;padding:4px 8px;border-radius:6px;font-size:0.78rem;cursor:pointer;}
</style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- LOGIN -->
<div class="login-wrap">
  <div class="login-box">
    <span class="logo">satu<span>seduh</span>.</span>
    <h2>Admin Panel</h2>
    <form method="POST">
      <div class="form-g"><label>Username</label><input type="text" name="username" required autocomplete="username"></div>
      <div class="form-g"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
      <button type="submit" name="login" class="btn-login">Masuk</button>
      <?php if (isset($loginError)): ?><p class="err"><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
    </form>
    <p style="text-align:center;margin-top:16px;font-size:0.78rem;color:rgba(245,230,200,0.4);">Default: admin / password</p>
  </div>
</div>

<?php else: ?>
<!-- DASHBOARD -->
<div class="admin-wrap">
  <div class="sidebar">
    <a href="#" class="logo">satu<span>seduh</span>.</a>
    <a href="index.php" class="active">📊 Dashboard</a>
    <a href="../index.php" target="_blank">🌐 Lihat Website</a>
    <div class="logout">
      <form method="POST" style="margin:0;">
        <button type="submit" name="logout" style="background:none;border:none;color:rgba(245,230,200,0.5);cursor:pointer;font-size:0.9rem;padding:0;">🚪 Logout</button>
      </form>
    </div>
  </div>

  <div class="main">
    <h1>Dashboard — Satu Seduh ☕</h1>

    <!-- Statistik -->
    <div class="stat-grid">
      <div class="stat-card"><div class="val"><?= $stats['pesanan_hari_ini'] ?></div><div class="lbl">Pesanan Hari Ini</div></div>
      <div class="stat-card"><div class="val"><?= number_format((int)$stats['pendapatan_hari'] / 1000) ?>K</div><div class="lbl">Pendapatan Hari Ini (IDR)</div></div>
      <div class="stat-card"><div class="val"><?= $stats['total_pesanan'] ?></div><div class="lbl">Total Semua Pesanan</div></div>
      <div class="stat-card"><div class="val"><?= $stats['reservasi_pending'] ?></div><div class="lbl">Reservasi Pending</div></div>
      <div class="stat-card"><div class="val"><?= $stats['komentar_pending'] ?></div><div class="lbl">Komentar Belum Ditinjau</div></div>
    </div>

    <!-- Pesanan Terbaru -->
    <div class="sec-title">📋 Pesanan Terbaru</div>
    <table>
      <tr><th>No. Pesanan</th><th>Nama</th><th>Meja</th><th>Total</th><th>Bayar</th><th>Status</th><th>Waktu</th></tr>
      <?php foreach ($pesananTerbaru as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nomor_pesanan']) ?></td>
        <td><?= htmlspecialchars($p['nama_pemesan']) ?></td>
        <td><?= htmlspecialchars($p['nomor_meja'] ?: '-') ?></td>
        <td><?= rupiah((int)$p['total']) ?></td>
        <td><?= strtoupper($p['metode_bayar']) ?></td>
        <td>
          <select class="status-sel" onchange="location='index.php?update_pesanan=<?= $p['id'] ?>&status='+this.value">
            <?php foreach (['pending','confirmed','preparing','ready','done','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $p['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><?= date('d/m H:i', strtotime($p['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <!-- Reservasi -->
    <div class="sec-title">📅 Reservasi Terbaru</div>
    <table>
      <tr><th>Nama</th><th>WA</th><th>Ruangan</th><th>Tanggal</th><th>Waktu</th><th>Orang</th><th>Status</th></tr>
      <?php foreach ($reservasiList as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['nama']) ?></td>
        <td><?= htmlspecialchars($r['whatsapp']) ?></td>
        <td><?= htmlspecialchars($r['ruangan']) ?></td>
        <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
        <td><?= htmlspecialchars($r['waktu']) ?> · <?= htmlspecialchars($r['durasi'] ?: '-') ?></td>
        <td><?= $r['jumlah_orang'] ?: '-' ?></td>
        <td>
          <select class="status-sel" onchange="location='index.php?update_reservasi=<?= $r['id'] ?>&status='+this.value">
            <?php foreach (['pending','confirmed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $r['status']===$s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>

    <!-- Komentar -->
    <div class="sec-title">💬 Komentar Terbaru</div>
    <table>
      <tr><th>Nama</th><th>Email</th><th>Komentar</th><th>Status</th><th>Waktu</th></tr>
      <?php foreach ($komentarList as $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['nama']) ?></td>
        <td><?= htmlspecialchars($k['email'] ?: '-') ?></td>
        <td style="max-width:300px;"><?= htmlspecialchars(mb_substr($k['pesan'], 0, 100)) ?>...</td>
        <td><span class="badge <?= $k['status'] ?>"><?= ucfirst($k['status']) ?></span></td>
        <td><?= date('d/m H:i', strtotime($k['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php endif; ?>
</body>
</html>
