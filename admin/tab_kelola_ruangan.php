<?php
if (!$isLoggedIn) exit;

// Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_ruangan') {
        $id = $_POST['id'] ?? '';
        $kategori = $_POST['kategori'] ?? '';
        $nama_ruangan = $_POST['nama_ruangan'] ?? '';
        $deskripsi = $_POST['deskripsi'] ?? '';
        $kapasitas = $_POST['kapasitas'] ?? '';
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        
        // Handle file upload
        $gambar = $_POST['gambar_lama'] ?? '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['gambar']['tmp_name'];
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $newname = 'ruangan_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../img/uploads/' . $newname;
            if (move_uploaded_file($tmp, $dest)) {
                $gambar = 'img/uploads/' . $newname;
            }
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE ruangan_reservasi SET kategori=?, nama_ruangan=?, deskripsi=?, kapasitas=?, gambar=?, aktif=? WHERE id=?");
            $stmt->execute([$kategori, $nama_ruangan, $deskripsi, $kapasitas, $gambar, $aktif, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO ruangan_reservasi (kategori, nama_ruangan, deskripsi, kapasitas, gambar, aktif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kategori, $nama_ruangan, $deskripsi, $kapasitas, $gambar, $aktif]);
        }
        header("Location: index.php?tab=kelola_ruangan");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_ruangan') {
        $id = $_POST['id'];
        $db->prepare("DELETE FROM ruangan_reservasi WHERE id=?")->execute([$id]);
        header("Location: index.php?tab=kelola_ruangan");
        exit;
    }
}

$ruanganList = $db->query("SELECT * FROM ruangan_reservasi ORDER BY id DESC")->fetchAll();
$editData = null;
if (isset($_GET['edit_id'])) {
    $stmt = $db->prepare("SELECT * FROM ruangan_reservasi WHERE id=?");
    $stmt->execute([$_GET['edit_id']]);
    $editData = $stmt->fetch();
}
?>

<div class="page-body">
  <div class="table-card">
    <div class="card-header" style="padding:24px 24px 0;">
      <div>
        <div class="card-title"><?= $editData ? 'Edit Ruangan' : 'Tambah Ruangan Baru' ?></div>
      </div>
    </div>
    <div style="padding: 24px;">
      <form method="POST" action="index.php?tab=kelola_ruangan" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_ruangan">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
        <input type="hidden" name="gambar_lama" value="<?= $editData['gambar'] ?? '' ?>">
        
        <div class="form-field">
          <label>Nama Ruangan</label>
          <input type="text" name="nama_ruangan" value="<?= htmlspecialchars($editData['nama_ruangan'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label>Kategori</label>
          <input type="text" name="kategori" value="<?= htmlspecialchars($editData['kategori'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label>Kapasitas (misal: 4-20 org)</label>
          <input type="text" name="kapasitas" value="<?= htmlspecialchars($editData['kapasitas'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label>Deskripsi</label>
          <textarea name="deskripsi" style="width:100%; background:var(--bg2); border:1px solid var(--border); color:var(--cream); padding:10px;" rows="3" required><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
        </div>
        <div class="form-field">
          <label>Foto Ruangan (Kosongkan jika tidak ingin mengubah)</label>
          <input type="file" name="gambar" accept="image/*">
          <?php if (!empty($editData['gambar'])): ?>
            <div style="margin-top:10px;"><img src="../<?= $editData['gambar'] ?>" style="max-width:150px; border-radius: 8px;"></div>
          <?php endif; ?>
        </div>
        <div class="form-field">
          <label><input type="checkbox" name="aktif" value="1" <?= (!isset($editData) || $editData['aktif']) ? 'checked' : '' ?>> Aktif (Tampil di web)</label>
        </div>
        
        <button type="submit" class="btn-login" style="width: auto; padding: 10px 20px;">Simpan</button>
        <?php if ($editData): ?>
          <a href="index.php?tab=kelola_ruangan" class="btn-link" style="margin-left: 10px;">Batal</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-card" style="margin-top: 30px;">
    <div class="card-header" style="padding:24px 24px 0;">
      <div>
        <div class="card-title">Daftar Ruangan</div>
      </div>
    </div>
    <div class="table-wrap" style="margin-top:20px;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Foto</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Kapasitas</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ruanganList as $r): ?>
          <tr>
            <td>
                <?php if ($r['gambar']): ?>
                    <img src="<?= (strpos($r['gambar'], 'http') === 0) ? $r['gambar'] : '../'.$r['gambar'] ?>" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                <?php endif; ?>
            </td>
            <td class="td-name"><?= htmlspecialchars($r['nama_ruangan']) ?></td>
            <td><?= htmlspecialchars($r['kategori']) ?></td>
            <td><?= htmlspecialchars($r['kapasitas']) ?></td>
            <td>
              <span class="badge <?= $r['aktif'] ? 'confirmed' : 'cancelled' ?>"><?= $r['aktif'] ? 'Aktif' : 'Non-Aktif' ?></span>
            </td>
            <td>
              <a href="index.php?tab=kelola_ruangan&edit_id=<?= $r['id'] ?>" class="btn-link">Edit</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus ruangan ini?');">
                  <input type="hidden" name="action" value="delete_ruangan">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn-link" style="color:var(--cancelled); border-color:var(--cancelled);">Hapus</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
