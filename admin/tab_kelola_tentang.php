<?php
// tab_kelola_tentang.php

$tentangDataFile = __DIR__ . '/../data/tentang.json';

// Buat folder jika belum ada
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0777, true);
}

// Default data
$tentangData = [
    'kisah_kami' => 'Kisah Kami',
    'judul' => 'Lebih dari Sekadar <em>Secangkir Kopi</em>',
    'paragraf1' => 'Di Satu Seduh, setiap cangkir kopi menyimpan kisah dan kehangatan. Lahir dari semangat menghadirkan pengalaman ngopi otentik dengan biji kopi terbaik Nusantara — dari Aceh, Toraja, hingga Flores.',
    'paragraf2' => 'Kami tidak hanya menyajikan kopi, tapi juga ruang untuk bercerita, berkreasi, dan berkolaborasi. Dengan fasilitas meeting, co-working, dan acara privat — Satu Seduh adalah rumah kedua Anda.',
    'foto' => 'tentang-kami.jpg'
];

if (file_exists($tentangDataFile)) {
    $tentangData = json_decode(file_get_contents($tentangDataFile), true) ?: $tentangData;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tentang'])) {
    $tentangData['kisah_kami'] = $_POST['kisah_kami'] ?? '';
    $tentangData['judul'] = $_POST['judul'] ?? '';
    $tentangData['paragraf1'] = $_POST['paragraf1'] ?? '';
    $tentangData['paragraf2'] = $_POST['paragraf2'] ?? '';

    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['foto']['tmp_name'];
        $fileName = time() . '_' . basename($_FILES['foto']['name']);
        $targetFile = __DIR__ . '/../' . $fileName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $tentangData['foto'] = $fileName;
        } else {
            $msg = "Gagal mengunggah foto.";
        }
    }

    file_put_contents($tentangDataFile, json_encode($tentangData, JSON_PRETTY_PRINT));
    if (!$msg) $msg = "Data Tentang Kami berhasil diperbarui.";
}
?>

<div class="page-body">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Kelola Konten "Tentang Kami"</h3>
            <p class="card-subtitle">Perbarui teks dan foto yang muncul di halaman depan.</p>
        </div>

        <?php if ($msg): ?>
        <div style="padding:15px; background:rgba(16,185,129,0.1); color:#10b981; border:1px solid #10b981; border-radius:8px; margin-bottom:20px;">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="max-width: 600px;">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; color:var(--cream);">Label Kisah</label>
                <input type="text" name="kisah_kami" value="<?= htmlspecialchars($tentangData['kisah_kami']) ?>" required style="width:100%; padding:10px; background:var(--bg2); border:1px solid var(--border); color:var(--cream); border-radius:8px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; color:var(--cream);">Judul (Boleh pakai tag &lt;em&gt; untuk teks miring / emas)</label>
                <input type="text" name="judul" value="<?= htmlspecialchars($tentangData['judul']) ?>" required style="width:100%; padding:10px; background:var(--bg2); border:1px solid var(--border); color:var(--cream); border-radius:8px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; color:var(--cream);">Paragraf 1</label>
                <textarea name="paragraf1" rows="4" required style="width:100%; padding:10px; background:var(--bg2); border:1px solid var(--border); color:var(--cream); border-radius:8px;"><?= htmlspecialchars($tentangData['paragraf1']) ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; color:var(--cream);">Paragraf 2</label>
                <textarea name="paragraf2" rows="4" required style="width:100%; padding:10px; background:var(--bg2); border:1px solid var(--border); color:var(--cream); border-radius:8px;"><?= htmlspecialchars($tentangData['paragraf2']) ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:5px; color:var(--cream);">Foto Tentang</label>
                <div style="margin-bottom: 10px;">
                    <img src="../<?= htmlspecialchars($tentangData['foto']) ?>" alt="Foto Tentang" style="max-width: 200px; border-radius: 8px;">
                </div>
                <input type="file" name="foto" accept="image/*" style="width:100%; padding:10px; background:var(--bg2); border:1px solid var(--border); color:var(--cream); border-radius:8px;">
                <small style="color:var(--muted); display:block; margin-top:5px;">Kosongkan jika tidak ingin mengubah foto.</small>
            </div>

            <button type="submit" name="update_tentang" class="btn-primary" style="padding:10px 20px; background:var(--gold); color:#000; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">Simpan Perubahan</button>
        </form>
    </div>
</div>
