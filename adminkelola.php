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
            <div class="table-head<?= $tab === 'loans' ? ' admin-loans-head' : '' ?>">
                <div>
                    <h2><?= [
                        'dashboard' => 'Ringkasan perpustakaan',
                        'loans' => 'Daftar peminjaman',
                        'books' => 'Koleksi buku',
                        'publishers' => 'Data penerbit',
                        'categories' => 'Kategori buku',
                        'authors' => 'Data penulis',
                        'users' => 'Data pengguna',
                        'staff' => 'Data petugas',
                    ][$tab] ?></h2>
                    <p><?= count($rows) ?> data ditemukan</p>
                </div>
                <?php if ($tab === 'loans'): ?>
                    <div class="admin-loan-toolbar">
                    <div class="admin-filters">
                        <?php foreach (['semua' => 'Semua', 'dipinjam' => 'Belum kembali', 'terlambat' => 'Terlambat', 'menunggu' => 'Menunggu konfirmasi', 'kembali' => 'Dikembalikan'] as $key => $label): ?>
                            <a class="<?= $statusFilter === $key ? 'active' : '' ?>" href="admin.php?<?= htmlspecialchars(http_build_query(['page' => 'peminjaman', 'status' => $key, 'q' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'sort' => $sortFilter])) ?>"><?= $label ?></a>
                        <?php endforeach; ?>
                    </div>
                    <form class="admin-loan-tools" method="get" action="admin.php">
                    <input type="hidden" name="page" value="peminjaman">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                    <label>Dari tanggal<input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"></label>
                    <label>Sampai tanggal<input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"></label>
                    <label>Urutkan
                        <select name="sort">
                            <?php foreach (['due_soon' => 'Jatuh tempo terdekat', 'newest' => 'Peminjaman terbaru', 'oldest' => 'Peminjaman terlama', 'due_latest' => 'Jatuh tempo terjauh', 'member' => 'Nama anggota', 'book' => 'Judul buku'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $sortFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn-primary" type="submit">Terapkan</button>
                    <a class="btn-secondary" href="admin.php?page=peminjaman&status=<?= rawurlencode($statusFilter) ?>">Reset</a>
                    </form>
                    <div class="admin-live-datetime" aria-label="Tanggal dan waktu saat ini">
                        <span id="admin-current-date"><?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('d/m/Y')) ?></span>
                        <span id="admin-current-date-iso"><?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y/m/d')) ?></span>
                        <time id="admin-current-time" datetime="<?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format(DateTimeInterface::ATOM)) ?>"><?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('H:i:s')) ?> WIB</time>
                    </div>
                    <script>
                        (() => {
                            const dateNode = document.getElementById('admin-current-date');
                            const isoDateNode = document.getElementById('admin-current-date-iso');
                            const timeNode = document.getElementById('admin-current-time');
                            if (!dateNode || !isoDateNode || !timeNode) return;
                            const dateFormat = new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
                            });
                            const timeFormat = new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
                            });
                            const isoDateFormat = new Intl.DateTimeFormat('en-CA', {
                                timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit'
                            });
                            const updateClock = () => {
                                const now = new Date();
                                dateNode.textContent = dateFormat.format(now);
                                const dateParts = Object.fromEntries(isoDateFormat.formatToParts(now).map(({ type, value }) => [type, value]));
                                isoDateNode.textContent = `${dateParts.year}/${dateParts.month}/${dateParts.day}`;
                                timeNode.textContent = `${timeFormat.format(now)} WIB`;
                                timeNode.dateTime = now.toISOString();
                            };
                            updateClock();
                            window.setInterval(updateClock, 1000);
                        })();
                    </script>
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
                        <label>Email<input type="email" name="email" placeholder="Opsional"></label>
                        <label>Kelas<input name="kelas"></label>
                        <label>Password<input type="password" name="password" minlength="6" required></label>
                        <button type="submit" class="btn-primary">Buat akun</button>
                    </form>
                </details>
            <?php elseif ($tab === 'books'): ?>
                <div class="admin-inventory-summary" aria-label="Ringkasan koleksi">
                    <?php foreach ([
                        ['value' => $inventoryStats['judul'], 'label' => 'Judul buku'],
                        ['value' => $inventoryStats['total_stok'], 'label' => 'Total buku'],
                        ['value' => $inventoryStats['sedang_dipinjam'], 'label' => 'Sedang dipinjam'],
                        ['value' => $inventoryStats['tersedia'], 'label' => 'Siap dipinjam'],
                    ] as $inventoryStat): ?>
                        <div class="admin-inventory-stat">
                            <strong><?= number_format((int) $inventoryStat['value']) ?></strong>
                            <span><?= htmlspecialchars($inventoryStat['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <details class="admin-add-user admin-add-book">
                    <summary>Tambah buku baru</summary>
                    <p class="admin-form-hint">Isi identitas buku, jumlah buku, dan informasi yang akan dilihat anggota.</p>
                    <form method="post" enctype="multipart/form-data" class="admin-book-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="books">
                        <input type="hidden" name="action" value="create_book">
                        <label>Nomor Buku<input name="id_buku" maxlength="30" placeholder="Contoh: BKB0000011" required></label>
                        <label class="admin-field-wide">Judul buku<input name="judul" maxlength="200" required></label>
                        <label>Penulis
                            <select name="id_penulis">
                                <option value="">Pilih penulis</option>
                                <?php foreach ($authors as $author): ?>
                                    <option value="<?= htmlspecialchars($author['id_penulis']) ?>"><?= htmlspecialchars($author['nama_penulis']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Jumlah buku
                            <input type="number" name="stok" min="0" max="100000" value="1" required>
                        </label>
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
                        <label class="admin-field-wide">Sinopsis
                            <textarea name="sinopsis" maxlength="60000" rows="4" placeholder="Ceritakan isi buku secara singkat..."></textarea>
                        </label>
                        <label class="admin-field-wide">Sampul buku (opsional, maksimal 5 MB)
                            <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.bmp,.ico,image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/vnd.microsoft.icon,image/x-icon">
                        </label>
                        <div class="admin-book-form-actions">
                            <button type="submit" class="btn-primary">Simpan buku</button>
                        </div>
                    </form>
                </details>
            <?php elseif ($tab === 'publishers' || $tab === 'categories' || $tab === 'authors'): ?>
                <details class="admin-add-user">
                    <summary>Tambah <?= ['publishers' => 'penerbit', 'categories' => 'kategori', 'authors' => 'penulis'][$tab] ?></summary>
                    <form method="post" class="admin-create-form admin-create-simple">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                        <input type="hidden" name="action" value="<?= ['publishers' => 'create_publisher', 'categories' => 'create_category', 'authors' => 'create_author'][$tab] ?>">
                        <label>Nama <?= ['publishers' => 'penerbit', 'categories' => 'kategori', 'authors' => 'penulis'][$tab] ?>
                            <input name="<?= ['publishers' => 'nama_penerbit', 'categories' => 'kategori', 'authors' => 'nama_penulis'][$tab] ?>" maxlength="<?= $tab === 'authors' ? '150' : '100' ?>" required>
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
            <form class="admin-search" method="get" action="admin.php">
                <input type="hidden" name="page" value="<?= htmlspecialchars((string) ($_GET['page'] ?? 'dashboard')) ?>">
                <?php if ($activeSection !== null): ?>
                    <input type="hidden" name="section" value="<?= htmlspecialchars($activeSection) ?>">
                <?php else: ?>
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <?php endif; ?>
                <?php if ($tab === 'loans'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
                <?php if ($tab === 'loans'): ?>
                    <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                    <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sortFilter) ?>">
                <?php endif; ?>
                <label for="admin-search">Cari <?= [
                    'loans' => 'peminjaman, anggota, atau judul buku',
                    'books' => 'judul, penulis, kategori, atau penerbit',
                    'publishers' => 'nama atau ID penerbit',
                    'categories' => 'nama atau ID kategori',
                    'authors' => 'nama atau ID penulis',
                    'users' => 'nama, username, email, atau NIS',
                    'staff' => 'nama, username, atau ID petugas',
                ][$tab] ?></label>
                <input id="admin-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik untuk mencari...">
                <button type="submit" class="btn-primary">Cari</button>
            </form>

            <?php if ($tab === 'users'): ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table users-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $rowIndex => $row):
                                $updateFormId = 'update-user-' . $rowIndex;
                                ?>
                                <tr>
                                    <td data-label="Pengguna">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" name="nama" aria-label="Nama pengguna" value="<?= htmlspecialchars($row['nama']) ?>" required>
                                        <small>NIS <?= htmlspecialchars((string) $row['NIS']) ?></small>
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" name="kelas" aria-label="Kelas" value="<?= htmlspecialchars($row['kelas'] ?? '') ?>" placeholder="Kelas">
                                    </td>
                                    <td data-label="Username">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" name="username" aria-label="Username" value="<?= htmlspecialchars($row['username']) ?>" required>
                                    </td>
                                    <td data-label="Email">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" type="email" name="email" aria-label="Email" value="<?= htmlspecialchars((string) ($row['email'] ?? '')) ?>">
                                    </td>
                                    <td data-label="Aksi" class="admin-actions-cell">
                                        <form id="<?= $updateFormId ?>" class="admin-row-update-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="users">
                                            <input type="hidden" name="action" value="update_user">
                                            <input type="hidden" name="nis" value="<?= htmlspecialchars((string) $row['NIS']) ?>">
                                            <button type="submit" class="admin-action">Simpan</button>
                                        </form>
                                        <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus akun ini beserta seluruh riwayat peminjamannya? Tindakan ini permanen.');">
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
                <div class="admin-book-list">
                    <?php foreach ($rows as $row): ?>
                        <?php $currentCoverUrl = book_cover_url((string) $row['id_buku']); ?>
                        <article class="admin-book-card">
                            <div class="admin-book-card-header">
                                <?php if ($currentCoverUrl !== null): ?>
                                    <img class="admin-book-cover" src="<?= htmlspecialchars($currentCoverUrl) ?>" alt="Sampul buku <?= htmlspecialchars($row['judul']) ?>">
                                <?php else: ?>
                                    <div class="admin-book-cover admin-book-cover-empty" aria-hidden="true"><?= icon('book', 26) ?></div>
                                <?php endif; ?>
                                <div class="admin-book-identity">
                                    <p class="admin-book-id">ID <?= htmlspecialchars($row['id_buku']) ?></p>
                                    <h3><?= htmlspecialchars($row['judul']) ?></h3>
                                    <p><?= htmlspecialchars($row['penulis'] ?: 'Penulis belum diisi') ?> · <?= htmlspecialchars($row['kategori'] ?: 'Tanpa kategori') ?></p>
                                    <?php $adminAvailable = max(0, (int) $row['stok_tersedia']); $adminTotal = max(0, (int) $row['stok']); ?>
                                    <div class="admin-stock-summary <?= $adminAvailable > 0 ? 'available' : 'unavailable' ?>">
                                        <strong><?= number_format($adminAvailable) ?></strong>
                                        <span><?= $adminAvailable > 0 ? 'buku tersedia' : 'stok habis' ?></span>
                                        <div class="stock-meter" aria-hidden="true"><span style="width: <?= $adminTotal > 0 ? min(100, (int) round($adminAvailable / $adminTotal * 100)) : 0 ?>%"></span></div>
                                    </div>
                                </div>
                                <div class="admin-book-stock" aria-label="Stok buku">
                                    <span><strong><?= number_format((int) $row['stok_tersedia']) ?></strong>Buku tersedia</span>
                                    <span><strong><?= number_format((int) $row['stok_dipinjam']) ?></strong>Dipinjam</span>
                                    <span><strong><?= number_format((int) $row['stok']) ?></strong>Total</span>
                                </div>
                            </div>
                            <details class="admin-book-edit">
                                <summary>Edit identitas dan stok</summary>
                                <form class="admin-book-form" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="tab" value="books">
                                    <input type="hidden" name="action" value="update_book">
                                    <input type="hidden" name="id_buku" value="<?= htmlspecialchars($row['id_buku']) ?>">
                                    <label class="admin-field-wide">Judul buku
                                        <input name="judul" maxlength="200" value="<?= htmlspecialchars($row['judul']) ?>" required>
                                    </label>
                                    <label>Penulis
                                        <select name="id_penulis">
                                            <option value="">Pilih penulis</option>
                                            <?php foreach ($authors as $author): ?>
                                                <option value="<?= htmlspecialchars($author['id_penulis']) ?>" <?= $row['id_penulis'] === $author['id_penulis'] ? 'selected' : '' ?>><?= htmlspecialchars($author['nama_penulis']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>Stok total
                                        <input type="number" name="stok" min="<?= (int) $row['stok_dipinjam'] ?>" max="100000" value="<?= (int) $row['stok'] ?>" required>
                                        <small><?= (int) $row['stok_dipinjam'] ?> buku sedang dipinjam</small>
                                    </label>
                                    <label>Kategori
                                        <select name="id_kategori"><option value="">Tanpa kategori</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= htmlspecialchars($category['id_kategori']) ?>" <?= $row['id_kategori'] === $category['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($category['kategori']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>Penerbit
                                        <select name="id_penerbit"><option value="">Tanpa penerbit</option>
                                            <?php foreach ($publishers as $publisher): ?>
                                                <option value="<?= htmlspecialchars($publisher['id_penerbit']) ?>" <?= $row['id_penerbit'] === $publisher['id_penerbit'] ? 'selected' : '' ?>><?= htmlspecialchars($publisher['nama_penerbit']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="admin-field-wide">Sinopsis
                                        <textarea name="sinopsis" maxlength="60000" rows="4" placeholder="Belum ada sinopsis"><?= htmlspecialchars($row['sinopsis'] ?? '') ?></textarea>
                                    </label>
                                    <label class="admin-field-wide">Ganti sampul (opsional, maksimal 5 MB)
                                        <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.bmp,.ico,image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/vnd.microsoft.icon,image/x-icon">
                                    </label>
                                    <div class="admin-book-form-actions">
                                        <button type="submit" class="btn-primary">Simpan perubahan</button>
                                    </div>
                                </form>
                                <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus buku ini? Buku yang memiliki riwayat peminjaman tidak dapat dihapus.');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="tab" value="books">
                                    <input type="hidden" name="action" value="delete_book">
                                    <input type="hidden" name="id_buku" value="<?= htmlspecialchars($row['id_buku']) ?>">
                                    <button type="submit" class="admin-action danger">Hapus buku</button>
                                </form>
                            </details>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><p class="admin-empty admin-panel">Belum ada buku yang cocok. Tambahkan buku pertama atau ubah pencarian.</p><?php endif; ?>
                </div>
            <?php elseif ($tab === 'publishers' || $tab === 'categories' || $tab === 'authors'): ?>
                <div class="table-wrap admin-table-wrap">
                    <table class="admin-table lookup-table">
                        <thead><tr><th>ID</th><th>Nama</th><th>Buku terkait</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $rowIndex => $row):
                                $entityFields = [
                                    'publishers' => ['id_penerbit', 'nama_penerbit', 'update_publisher', 'delete_publisher'],
                                    'categories' => ['id_kategori', 'kategori', 'update_category', 'delete_category'],
                                    'authors' => ['id_penulis', 'nama_penulis', 'update_author', 'delete_author'],
                                ];
                                [$idField, $nameField, $updateAction, $deleteAction] = $entityFields[$tab];
                                $rowId = $row[$idField];
                                $updateFormId = 'update-entity-' . $rowIndex;
                            ?>
                                <tr>
                                    <td data-label="ID"><?= htmlspecialchars($rowId) ?></td>
                                    <td data-label="Nama">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" name="<?= $nameField ?>" aria-label="Nama" maxlength="<?= $tab === 'authors' ? '150' : '100' ?>" value="<?= htmlspecialchars($row[$nameField]) ?>" required>
                                    </td>
                                    <td data-label="Buku terkait"><?= (int) $row['jumlah_buku'] ?> buku</td>
                                    <td data-label="Aksi" class="admin-actions-cell">
                                        <form id="<?= $updateFormId ?>" class="admin-row-update-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                            <input type="hidden" name="action" value="<?= $updateAction ?>">
                                            <input type="hidden" name="<?= $idField ?>" value="<?= htmlspecialchars($rowId) ?>">
                                            <button type="submit" class="admin-action">Simpan</button>
                                        </form>
                                        <form class="user-delete-form" method="post" onsubmit="return confirm('Hapus data ini?');">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                            <input type="hidden" name="action" value="<?= $deleteAction ?>">
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
                    <table class="admin-table staff-table">
                        <thead><tr><th>ID Petugas</th><th>Nama</th><th>Login</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $rowIndex => $row):
                                $updateFormId = 'update-staff-' . $rowIndex;
                                ?>
                                <tr>
                                    <td data-label="ID Petugas"><?= htmlspecialchars($row['id_petugas']) ?></td>
                                    <td data-label="Nama">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" name="nama" aria-label="Nama petugas" value="<?= htmlspecialchars($row['nama']) ?>" required>
                                    </td>
                                    <td data-label="Login" class="admin-login-cell">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" name="username" aria-label="Username" value="<?= htmlspecialchars($row['username'] ?? '') ?>" placeholder="Username">
                                        <input class="admin-table-input" form="<?= $updateFormId ?>" type="password" name="password" aria-label="Password baru (opsional)" minlength="6" placeholder="Password baru (opsional)">
                                    </td>
                                    <td data-label="Aksi" class="admin-actions-cell">
                                        <form id="<?= $updateFormId ?>" class="admin-row-update-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                            <input type="hidden" name="tab" value="staff">
                                            <input type="hidden" name="action" value="update_staff">
                                            <input type="hidden" name="id_petugas" value="<?= htmlspecialchars($row['id_petugas']) ?>">
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
                                    <td data-label="Peminjaman"><strong>#<?= htmlspecialchars($row['id_peminjaman']) ?></strong><small>Detail <?= htmlspecialchars($row['id_detail']) ?></small></td>
                                    <td data-label="Anggota"><strong><?= htmlspecialchars($row['nama'] ?? 'Pengguna dihapus') ?></strong><small>NIS <?= htmlspecialchars((string) $row['NIS']) ?></small></td>
                                    <td data-label="Buku"><?= htmlspecialchars($row['judul'] ?? 'Buku tidak ditemukan') ?></td>
                                    <td data-label="Tanggal"><small>Pinjam <?= htmlspecialchars($row['tgl_peminjam']) ?></small><small>Tempo <?= htmlspecialchars($row['tgl_jatuh_tempo']) ?></small></td>
                                    <td data-label="Status">
                                        <?php if ($row['tgl_kembali'] !== null): ?>
                                            <span class="status ok">Kembali <?= htmlspecialchars($row['tgl_kembali']) ?></span>
                                        <?php elseif ($row['tgl_pengajuan_kembali'] !== null): ?>
                                            <span class="status warn">Menunggu konfirmasi</span>
                                            <small>Diajukan <?= htmlspecialchars($row['tgl_pengajuan_kembali']) ?></small>
                                        <?php else: ?>
                                            <span class="status info">Belum diajukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Aksi" class="admin-actions-cell">
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
