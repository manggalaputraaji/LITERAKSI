<?php
            if ($pageMode === 'manajemen' || $pageMode === 'manajemen_user') {
                $renderSections = [$activeSection => $adminSections[$activeSection]];
            } else {
                $renderSections = [$tab => $rows];
            }
        ?>
        <?php foreach ($renderSections as $sectionTab => $sectionRows): ?>
        <?php $tab = $sectionTab; $rows = $sectionRows; ?>
        <section class="admin-panel">
            <div class="table-head">
                <div>
                    <h2><?= [
                        'books' => 'Koleksi Buku',
                        'publishers' => 'Data Penerbit',
                        'categories' => 'Kategori Buku',
                        'users' => 'Data Pengguna',
                        'staff' => 'Data Petugas',
                        'dashboard' => 'Ringkasan perpustakaan',
                        'loans' => 'Daftar peminjaman',
                        'books' => 'Koleksi buku',
                        'publishers' => 'Data penerbit',
                        'categories' => 'Kategori buku',
                        'users' => 'Data pengguna',
                        'staff' => 'Data petugas',
                    ][$tab] ?></h2>
                    <p><?= count($rows) ?> data ditemukan</p>
                </div>
                <?php if ($tab === 'loans'): ?>
                    <div class="admin-filters">
                        <?php foreach (['semua' => 'Semua', 'dipinjam' => 'Belum kembali', 'terlambat' => 'Terlambat', 'menunggu' => 'Menunggu konfirmasi', 'kembali' => 'Dikembalikan'] as $key => $label): ?>
                            <a class="<?= $statusFilter === $key ? 'active' : '' ?>" href="admin_peminjaman.php?status=<?= $key ?>"><?= $label ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($tab === 'users'): ?>
                <details class="admin-add-user">
                    <summary>Tambah pengguna</summary>
                    <form method="post" class="admin-create-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="users">
                        <input type="hidden" name="action" value="create_user">
                        <label>NIS<input name="nis" inputmode="numeric" required></label>
                        <label>Nama<input name="nama" required></label>
                        <label>Username<input name="username" required></label>
                        <label>Email<input type="email" name="email" required></label>
                        <label>Kelas<input name="kelas"></label>
                        <label>Password<input type="password" name="password" minlength="6" required></label>
                        <button type="submit" class="btn-primary">Buat akun</button>
                    </form>
                </details>
            <?php elseif ($tab === 'books'): ?>
                <details class="admin-add-user">
                    <summary>Tambah buku</summary>
                    <form method="post" enctype="multipart/form-data" class="admin-create-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="books">
                        <input type="hidden" name="action" value="create_book">
                        <label>Judul buku<input name="judul" maxlength="200" required></label>
                        <label>Kategori
                            <select name="id_kategori">
                                <option value="">Tanpa kategori</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id_kategori']) ?>"><?= htmlspecialchars($category['kategori']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Penerbit
                            <select name="id_penerbit">
                                <option value="">Tanpa penerbit</option>
                                <?php foreach ($publishers as $publisher): ?>
                                    <option value="<?= htmlspecialchars($publisher['id_penerbit']) ?>"><?= htmlspecialchars($publisher['nama_penerbit']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Sampul buku (JPG, PNG, GIF, WebP, AVIF, BMP, ICO; maks. 5 MB)
                            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.bmp,.ico,image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/vnd.microsoft.icon,image/x-icon">
                        </label>
                        <button type="submit" class="btn-primary">Tambah buku</button>
                    </form>
                </details>
            <?php elseif ($tab === 'publishers' || $tab === 'categories'): ?>
                <details class="admin-add-user">
                    <summary>Tambah <?= $tab === 'publishers' ? 'penerbit' : 'kategori' ?></summary>
                    <form method="post" class="admin-create-form admin-create-simple">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                        <input type="hidden" name="action" value="<?= $tab === 'publishers' ? 'create_publisher' : 'create_category' ?>">
                        <label>Nama <?= $tab === 'publishers' ? 'penerbit' : 'kategori' ?>
                            <input name="<?= $tab === 'publishers' ? 'nama_penerbit' : 'kategori' ?>" maxlength="100" required>
                        </label>
                        <button type="submit" class="btn-primary">Tambah</button>
                    </form>
                </details>
            <?php elseif ($tab === 'staff'): ?>
                <details class="admin-add-user">
                    <summary>Tambah petugas</summary>
                    <form method="post" class="admin-create-form admin-create-simple">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="staff">
                        <input type="hidden" name="action" value="create_staff">
                        <label>Nama petugas<input name="nama" required></label>
                        <label>Username login<input name="username" required></label>
                        <label>Password awal<input type="password" name="password" minlength="6" required></label>
                        <button type="submit" class="btn-primary">Tambah petugas</button>
                    </form>
                </details>
            <?php endif; ?>
            <form class="admin-search" method="get" action="<?= htmlspecialchars($_SERVER['SCRIPT_NAME']) ?>">
                <?php if ($activeSection !== null): ?>
                    <input type="hidden" name="section" value="<?= htmlspecialchars($activeSection) ?>">
                <?php else: ?>
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <?php endif; ?>
                <?php if ($tab === 'loans'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
                <label for="admin-search">Cari <?= [
                    'loans' => 'peminjaman, anggota, atau judul buku',
                    'books' => 'judul, kategori, atau penerbit',
                    'publishers' => 'nama atau ID penerbit',
                    'categories' => 'nama atau ID kategori',
                    'users' => 'nama, username, email, atau NIS',
                    'staff' => 'nama, username, atau ID petugas',
                ][$tab] ?></label>
                <input id="admin-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik untuk mencari...">
                <button type="submit" class="btn-primary">Cari</button>
            </form>

            <?php if ($tab === 'users'): ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['nama']) ?></strong><small>NIS <?= htmlspecialchars((string) $row['NIS']) ?></small></td>
                                    <td colspan="3">
                                        <form class="user-edit-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="users">
                                            <input type="hidden" name="action" value="update_user">
                                            <input type="hidden" name="nis" value="<?= htmlspecialchars((string) $row['NIS']) ?>">
                                            <label><span>Nama</span><input name="nama" value="<?= htmlspecialchars($row['nama']) ?>" required></label>
                                            <label><span>Username</span><input name="username" value="<?= htmlspecialchars($row['username']) ?>" required></label>
                                            <label><span>Email</span><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required></label>
                                            <label><span>Kelas</span><input name="kelas" value="<?= htmlspecialchars($row['kelas'] ?? '') ?>"></label>
                                            <button type="submit" class="admin-action">Simpan</button>
                                        </form>
                                        <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus akun ini beserta seluruh riwayat peminjaman dan postingannya? Tindakan ini permanen.');">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="users">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="nis" value="<?= htmlspecialchars((string) $row['NIS']) ?>">
                                            <button type="submit" class="admin-action danger">Hapus pengguna dan riwayat</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr>
                                    <td colspan="4" class="admin-empty">Tidak ada pengguna yang cocok.</td>
                                </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($tab === 'books'): ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Judul</th><th>Kategori</th><th>Penerbit</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_buku']) ?></td>
                                    <td colspan="4">
                                        <form class="user-edit-form catalog-edit-form" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="books">
                                            <input type="hidden" name="action" value="update_book">
                                            <input type="hidden" name="id_buku" value="<?= htmlspecialchars($row['id_buku']) ?>">
                                            <label><span>Judul</span><input name="judul" maxlength="200" value="<?= htmlspecialchars($row['judul']) ?>" required></label>
                                            <label><span>Kategori</span>
                                                <select name="id_kategori"><option value="">Tanpa kategori</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= htmlspecialchars($category['id_kategori']) ?>" <?= $row['id_kategori'] === $category['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($category['kategori']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label><span>Penerbit</span>
                                                <select name="id_penerbit"><option value="">Tanpa penerbit</option>
                                                    <?php foreach ($publishers as $publisher): ?>
                                                        <option value="<?= htmlspecialchars($publisher['id_penerbit']) ?>" <?= $row['id_penerbit'] === $publisher['id_penerbit'] ? 'selected' : '' ?>><?= htmlspecialchars($publisher['nama_penerbit']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <label class="admin-cover-field">
                                                <span class="admin-cover-label">Sampul saat ini / pilih pengganti</span>
                                                <?php $currentCoverUrl = book_cover_url((string) $row['id_buku']); ?>
                                                <?php if ($currentCoverUrl !== null): ?>
                                                    <span class="admin-cover-preview-trigger" tabindex="0" data-cover-preview="<?= htmlspecialchars($currentCoverUrl) ?>">Arahkan untuk pratinjau</span>
                                                <?php else: ?>
                                                    <small class="admin-muted">Belum ada sampul</small>
                                                <?php endif; ?>
                                                <input class="admin-cover-input" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.bmp,.ico,image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/vnd.microsoft.icon,image/x-icon">
                                            </label>
                                            <button type="submit" class="admin-action">Simpan</button>
                                        </form>
                                        <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus buku ini?');">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="books">
                                            <input type="hidden" name="action" value="delete_book">
                                            <input type="hidden" name="id_buku" value="<?= htmlspecialchars($row['id_buku']) ?>">
                                            <button type="submit" class="admin-action danger">Hapus buku</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr><td colspan="5" class="admin-empty">Belum ada buku.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($tab === 'publishers' || $tab === 'categories'): ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Nama</th><th>Buku terkait</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row):
                                $isPublisher = $tab === 'publishers';
                                $idField = $isPublisher ? 'id_penerbit' : 'id_kategori';
                                $nameField = $isPublisher ? 'nama_penerbit' : 'kategori';
                                $rowId = $row[$idField];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($rowId) ?></td>
                                    <td colspan="3">
                                        <form class="user-edit-form admin-lookup-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                            <input type="hidden" name="action" value="<?= $isPublisher ? 'update_publisher' : 'update_category' ?>">
                                            <input type="hidden" name="<?= $idField ?>" value="<?= htmlspecialchars($rowId) ?>">
                                            <label><span>Nama</span><input name="<?= $nameField ?>" maxlength="100" value="<?= htmlspecialchars($row[$nameField]) ?>" required></label>
                                            <span class="admin-muted"><?= (int) $row['jumlah_buku'] ?> buku</span>
                                            <button type="submit" class="admin-action">Simpan</button>
                                        </form>
                                        <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus data ini?');">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                            <input type="hidden" name="action" value="<?= $isPublisher ? 'delete_publisher' : 'delete_category' ?>">
                                            <input type="hidden" name="<?= $idField ?>" value="<?= htmlspecialchars($rowId) ?>">
                                            <button type="submit" class="admin-action danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr><td colspan="4" class="admin-empty">Belum ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($tab === 'staff'): ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>ID Petugas</th><th>Nama</th><th>Login</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_petugas']) ?></td>
                                    <td colspan="3">
                                        <form class="user-edit-form catalog-edit-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="staff">
                                            <input type="hidden" name="action" value="update_staff">
                                            <input type="hidden" name="id_petugas" value="<?= htmlspecialchars($row['id_petugas']) ?>">
                                            <label><span>Nama</span><input name="nama" value="<?= htmlspecialchars($row['nama']) ?>" required></label>
                                            <label><span>Username</span><input name="username" value="<?= htmlspecialchars($row['username'] ?? '') ?>"></label>
                                            <label><span>Password baru (opsional)</span><input type="password" name="password" minlength="6" placeholder="Kosongkan jika tetap"></label>
                                            <button type="submit" class="admin-action">Simpan</button>
                                        </form>
                                        <?php if ($row['id_petugas'] !== (string) $user['id_petugas']): ?>
                                            <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus petugas ini?');">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                                <input type="hidden" name="tab" value="staff">
                                                <input type="hidden" name="action" value="delete_staff">
                                                <input type="hidden" name="id_petugas" value="<?= htmlspecialchars($row['id_petugas']) ?>">
                                                <button type="submit" class="admin-action danger">Hapus petugas</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="admin-muted">Akun yang sedang digunakan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr><td colspan="4" class="admin-empty">Belum ada petugas.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table loans-table">
                        <thead>
                            <tr>
                                <th>Peminjaman</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($row['id_peminjaman']) ?></strong><small>Detail <?= htmlspecialchars($row['id_detail']) ?></small></td>
                                    <td><strong><?= htmlspecialchars($row['nama'] ?? 'Pengguna dihapus') ?></strong><small>NIS <?= htmlspecialchars((string) $row['NIS']) ?></small></td>
                                    <td><?= htmlspecialchars($row['judul'] ?? 'Buku tidak ditemukan') ?></td>
                                    <td><small>Pinjam <?= htmlspecialchars($row['tgl_peminjam']) ?></small><small>Tempo <?= htmlspecialchars($row['tgl_jatuh_tempo']) ?></small></td>
                                    <td>
                                        <?php if ($row['tgl_kembali'] !== null): ?>
                                            <span class="status ok">Kembali <?= htmlspecialchars($row['tgl_kembali']) ?></span>
                                        <?php elseif ($row['tgl_pengajuan_kembali'] !== null): ?>
                                            <span class="status warn">Menunggu konfirmasi</span>
                                            <small>Diajukan <?= htmlspecialchars($row['tgl_pengajuan_kembali']) ?></small>
                                        <?php else: ?>
                                            <span class="status info">Belum diajukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['tgl_kembali'] === null && $row['tgl_pengajuan_kembali'] !== null): ?>
                                            <form method="post" onsubmit="return confirm('Konfirmasi bahwa buku ini sudah diterima dari anggota?');">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                                <input type="hidden" name="tab" value="loans">
                                                <input type="hidden" name="action" value="confirm_return">
                                                <input type="hidden" name="id_detail" value="<?= htmlspecialchars($row['id_detail']) ?>">
                                                <button type="submit" class="admin-action">Konfirmasi kembali</button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Tolak pengajuan ini? Buku akan tetap berstatus belum dikembalikan.');">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                                <input type="hidden" name="tab" value="loans">
                                                <input type="hidden" name="action" value="reject_return">
                                                <input type="hidden" name="id_detail" value="<?= htmlspecialchars($row['id_detail']) ?>">
                                                <button type="submit" class="admin-action danger">Tolak pengajuan</button>
                                            </form>
                                        <?php elseif ($row['tgl_kembali'] === null): ?>
                                            <span class="admin-muted">Menunggu anggota mengajukan</span>
                                        <?php else: ?>
                                            <span class="admin-muted">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?><tr>
                                    <td colspan="6" class="admin-empty">Tidak ada data peminjaman yang cocok.</td>
                                </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>