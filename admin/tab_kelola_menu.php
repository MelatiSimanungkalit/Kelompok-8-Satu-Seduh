<?php
if (!$isLoggedIn) exit;

// Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_menu') {
        $id = $_POST['id'] ?? '';
        $kategori = $_POST['kategori'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $harga = $_POST['harga'] ?? 0;
        $deskripsi = $_POST['deskripsi'] ?? '';
        $badge = $_POST['badge'] ?? '';
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        
        // Handle file upload
        $gambar = $_POST['gambar_lama'] ?? '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['gambar']['tmp_name'];
            $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $newname = 'menu_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../img/uploads/' . $newname;
            if (move_uploaded_file($tmp, $dest)) {
                $gambar = 'img/uploads/' . $newname;
            }
        }

        if ($id) {
            $stmt = $db->prepare("UPDATE menu SET kategori=?, nama=?, harga=?, deskripsi=?, badge=?, gambar=?, aktif=? WHERE id=?");
            $stmt->execute([$kategori, $nama, $harga, $deskripsi, $badge, $gambar, $aktif, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO menu (kategori, nama, harga, deskripsi, badge, gambar, aktif) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kategori, $nama, $harga, $deskripsi, $badge, $gambar, $aktif]);
        }
        header("Location: index.php?tab=kelola_menu");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete_menu') {
        $id = $_POST['id'];
        $db->prepare("DELETE FROM menu WHERE id=?")->execute([$id]);
        header("Location: index.php?tab=kelola_menu");
        exit;
    }
}

$menuList = $db->query("SELECT * FROM menu ORDER BY kategori, id ASC")->fetchAll();
$editData = null;
if (isset($_GET['edit_id'])) {
    $stmt = $db->prepare("SELECT * FROM menu WHERE id=?");
    $stmt->execute([$_GET['edit_id']]);
    $editData = $stmt->fetch();
}
?>

<div class="page-body">
  <div class="table-card">
    <div class="card-header" style="padding:24px 24px 0;">
      <div>
        <div class="card-title"><?= $editData ? 'Edit Menu' : 'Tambah Menu Baru' ?></div>
      </div>
    </div>
    <div style="padding: 24px;">
      <form method="POST" action="index.php?tab=kelola_menu" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_menu">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
        <input type="hidden" name="gambar_lama" value="<?= $editData['gambar'] ?? '' ?>">
        
        <div class="form-field">
          <label>Kategori (misal: signature, espresso, milkbased, mocktail)</label>
          <input type="text" name="kategori" value="<?= htmlspecialchars($editData['kategori'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label>Nama Menu</label>
          <input type="text" name="nama" value="<?= htmlspecialchars($editData['nama'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label>Harga (Hanya Angka, misal: 25000)</label>
          <input type="number" name="harga" value="<?= htmlspecialchars($editData['harga'] ?? '') ?>" required>
        </div>
        <div class="form-field">
          <label>Deskripsi</label>
          <textarea name="deskripsi" style="width:100%; background:var(--bg2); border:1px solid var(--border); color:var(--cream); padding:10px;" rows="3"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
        </div>
        <div class="form-field">
          <label>Badge (misal: Rekomendasi, Best Seller)</label>
          <input type="text" name="badge" value="<?= htmlspecialchars($editData['badge'] ?? '') ?>">
        </div>
        <div class="form-field">
          <label>Foto Menu (Kosongkan jika tidak ingin mengubah)</label>
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
          <a href="index.php?tab=kelola_menu" class="btn-link" style="margin-left: 10px;">Batal</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-card" style="margin-top: 30px;">
    <div class="card-header" style="padding:24px 24px 0;">
      <div>
        <div class="card-title">Daftar Menu</div>
      </div>
    </div>
    <div class="table-wrap" style="margin-top:20px;">
      <table class="data-table">
        <thead>
          <tr>
            <th>Foto</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Harga</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menuList as $m): ?>
          <tr>
            <td>
                <?php if ($m['gambar']): ?>
                    <img src="<?= (strpos($m['gambar'], 'http') === 0) ? $m['gambar'] : '../'.$m['gambar'] ?>" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                <?php endif; ?>
            </td>
            <td class="td-name"><?= htmlspecialchars($m['nama']) ?></td>
            <td><?= htmlspecialchars($m['kategori']) ?></td>
            <td class="rupiah-val"><?= number_format($m['harga'], 0, ',', '.') ?></td>
            <td>
              <span class="badge <?= $m['aktif'] ? 'confirmed' : 'cancelled' ?>"><?= $m['aktif'] ? 'Aktif' : 'Non-Aktif' ?></span>
            </td>
            <td>
              <a href="index.php?tab=kelola_menu&edit_id=<?= $m['id'] ?>" class="btn-link">Edit</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus menu ini?');">
                  <input type="hidden" name="action" value="delete_menu">
                  <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
