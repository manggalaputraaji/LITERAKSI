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
            </div>
            <?php if ($tab === 'loans'): ?>
                <section class="admin-loan-controls" aria-label="Filter, ekspor, dan pemindai Data Matrix peminjaman">
                    <div class="admin-loan-filter-panel">
                        <div class="admin-loan-control-heading">
                            <h3>Filter peminjaman</h3>
                        </div>
                        <div class="admin-filters" aria-label="Filter status">
                        <?php foreach (['semua' => 'Semua', 'pengambilan' => 'Menunggu pengambilan', 'dipinjam' => 'Belum kembali', 'terlambat' => 'Terlambat', 'menunggu' => 'Menunggu pengembalian', 'kembali' => 'Dikembalikan'] as $key => $label): ?>
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
                            <button class="btn-primary" type="submit">Terapkan filter</button>
                            <a class="btn-secondary" href="admin.php?page=peminjaman&status=<?= rawurlencode($statusFilter) ?>">Reset</a>
                        </form>
                    </div>
                    <div class="admin-loan-right-tools">
                    <div class="admin-loan-export-tools">
                    <form class="admin-loan-export-panel" method="get" action="assets/logic/laporan_peminjaman.php">
                        <?php if ($dateFrom !== ''): ?><input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"><?php endif; ?>
                        <?php if ($dateTo !== ''): ?><input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"><?php endif; ?>
                        <?php if ($statusFilter !== 'semua'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
                        <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                        <label id="loan-export-label" for="loan-export-format">Export to</label>
                        <div class="admin-loan-export-actions">
                            <div class="admin-loan-export-select">
                                <select id="loan-export-format" name="format">
                                    <option value="xlsx">Excel (.xlsx)</option>
                                    <option value="pdf">PDF (.pdf)</option>
                                    <option value="docx">Word (.docx)</option>
                                </select>
                                <div class="admin-loan-export-custom" hidden>
                                    <button class="admin-loan-export-toggle" type="button" aria-labelledby="loan-export-label loan-export-selected" aria-haspopup="menu" aria-expanded="false" aria-controls="loan-export-menu">
                                        <span class="admin-loan-format-icon admin-loan-format-icon-xlsx" data-selected-icon aria-hidden="true"><?= icon('file', 18) ?><small>X</small></span>
                                        <span id="loan-export-selected" data-selected-label>Excel (.xlsx)</span>
                                        <span class="admin-loan-export-chevron"><?= icon('chevron-down', 16) ?></span>
                                    </button>
                                    <div class="admin-loan-export-menu" id="loan-export-menu" role="menu" aria-label="Pilih format ekspor" hidden>
                                        <button type="button" role="menuitem" data-format="xlsx" data-label="Excel (.xlsx)">
                                            <span class="admin-loan-format-icon admin-loan-format-icon-xlsx" aria-hidden="true"><?= icon('file', 18) ?><small>X</small></span>
                                            <span>Excel (.xlsx)</span>
                                        </button>
                                        <button type="button" role="menuitem" data-format="pdf" data-label="PDF (.pdf)">
                                            <span class="admin-loan-format-icon admin-loan-format-icon-pdf" aria-hidden="true"><?= icon('file', 18) ?><small>P</small></span>
                                            <span>PDF (.pdf)</span>
                                        </button>
                                        <button type="button" role="menuitem" data-format="docx" data-label="Word (.docx)">
                                            <span class="admin-loan-format-icon admin-loan-format-icon-docx" aria-hidden="true"><?= icon('file', 18) ?><small>W</small></span>
                                            <span>Word (.docx)</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button class="btn-primary" type="submit">Unduh</button>
                        </div>
                    </form>
                    <button class="admin-loan-print-btn" type="button"><?= icon('printer', 17) ?><span>Print</span></button>
                    <script>
                        (() => {
                            const panel = document.querySelector('.admin-loan-export-panel');
                            if (!panel) return;
                            const printButton = document.querySelector('.admin-loan-print-btn');
                            if (printButton) printButton.addEventListener('click', () => window.print());
                            const select = panel.querySelector('#loan-export-format');
                            const dropdown = panel.querySelector('.admin-loan-export-custom');
                            const toggle = panel.querySelector('.admin-loan-export-toggle');
                            const menu = panel.querySelector('.admin-loan-export-menu');
                            const selectedIcon = panel.querySelector('[data-selected-icon]');
                            const selectedLabel = panel.querySelector('[data-selected-label]');
                            if (!select || !dropdown || !toggle || !menu || !selectedIcon || !selectedLabel) return;

                            const closeMenu = () => {
                                menu.hidden = true;
                                toggle.setAttribute('aria-expanded', 'false');
                            };
                            const updateSelection = () => {
                                const option = menu.querySelector(`[data-format="${select.value}"]`);
                                if (!option) return;
                                const iconNode = option.querySelector('.admin-loan-format-icon');
                                if (iconNode) {
                                    selectedIcon.className = iconNode.className;
                                    selectedIcon.replaceChildren(...Array.from(iconNode.childNodes, (node) => node.cloneNode(true)));
                                }
                                selectedLabel.textContent = option.dataset.label || option.textContent.trim();
                                menu.querySelectorAll('[data-format]').forEach((item) => {
                                    item.setAttribute('aria-current', item === option ? 'true' : 'false');
                                });
                            };

                            dropdown.hidden = false;
                            select.hidden = true;
                            updateSelection();
                            toggle.addEventListener('click', () => {
                                menu.hidden = !menu.hidden;
                                toggle.setAttribute('aria-expanded', String(!menu.hidden));
                            });
                            menu.addEventListener('click', (event) => {
                                const option = event.target.closest('button[data-format]');
                                if (!option) return;
                                select.value = option.dataset.format;
                                updateSelection();
                                closeMenu();
                                toggle.focus();
                            });
                            dropdown.addEventListener('keydown', (event) => {
                                if (event.key === 'Escape') {
                                    closeMenu();
                                    toggle.focus();
                                }
                            });
                            document.addEventListener('click', (event) => {
                                if (!dropdown.contains(event.target)) closeMenu();
                            });
                        })();
                    </script>
                    </div>
                    <div class="admin-loan-camera-panel" data-data-matrix-scanner role="group" aria-label="Pemindai Data Matrix">
                        <div class="admin-loan-camera-controls">
                            <button class="admin-action admin-loan-camera-button" type="button" data-scanner-start aria-label="Mulai pemindai" title="Mulai pemindai">Scan</button>
                            <button class="admin-action danger admin-loan-camera-button" type="button" data-scanner-stop aria-label="Hentikan pemindai" title="Hentikan pemindai" hidden>Stop</button>
                        </div>
                        <video class="data-matrix-camera" playsinline muted aria-label="Pratinjau kamera pemindai" hidden></video>
                        <p class="data-matrix-scanner-status" data-scanner-status role="status" aria-live="polite"></p>
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                    </div>
                    </div>
                </section>
                <script src="assets/js/data-matrix-scanner.bundle.js?v=<?= filemtime(APP_ROOT . '/assets/js/data-matrix-scanner.bundle.js') ?>" defer></script>
            <?php endif; ?>
            <?php if ($tab === 'users'): ?>
                <details class="admin-add-user admin-add-book admin-add-account">
                    <summary>Tambah pengguna</summary>
                    <form method="post" class="admin-book-form admin-account-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="users">
                        <input type="hidden" name="action" value="create_user">
                        <label>NIS
                            <input name="nis" inputmode="numeric" required>
                        </label>
                        <label>Nama
                            <input name="nama" required>
                        </label>
                        <label>Username
                            <input name="username" required>
                        </label>
                        <label>Email (opsional)
                            <input type="email" name="email">
                        </label>
                        <label>Kelas
                            <input name="kelas">
                        </label>
                        <label>Password
                            <input type="password" name="password" minlength="6" required>
                        </label>
                        <div class="admin-book-form-actions">
                            <button type="submit" class="btn-primary">Buat akun</button>
                        </div>
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
                    <form method="post" enctype="multipart/form-data" class="admin-book-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="books">
                        <input type="hidden" name="action" value="create_book">
                        <label>Nomor Buku<input name="id_buku" maxlength="30" required></label>
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
                            <textarea name="sinopsis" maxlength="60000" rows="4"></textarea>
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
                <details class="admin-add-user admin-add-book admin-add-account">
                    <summary>Tambah petugas</summary>
                    <form method="post" class="admin-book-form admin-account-form">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                        <input type="hidden" name="tab" value="staff">
                        <input type="hidden" name="action" value="create_staff">
                        <label>Nama admin
                            <input name="nama" required>
                        </label>
                        <label>Username login
                            <input name="username" required>
                        </label>
                        <label>Password awal
                            <input type="password" name="password" minlength="6" required>
                        </label>
                        <div class="admin-book-form-actions">
                            <button type="submit" class="btn-primary">Tambah admin</button>
                        </div>
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
                <input id="admin-search" type="search" name="q" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-primary">Cari</button>
            </form>

            <?php if ($tab === 'users'): ?>
                <div class="admin-book-list admin-account-list">
                    <?php foreach ($rows as $row): ?>
                        <article class="admin-book-card admin-account-card">
                            <div class="admin-account-heading">
                                <h3>
                                    <?= htmlspecialchars($row['nama']) ?>
                                    <?php if (!empty($row['kelas'])): ?><span><?= htmlspecialchars($row['kelas']) ?></span><?php endif; ?>
                                </h3>
                                <form class="admin-account-delete-top" method="post" onsubmit="return confirm('Hapus akun ini beserta seluruh riwayat peminjamannya? Tindakan ini permanen.');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="tab" value="users">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="nis" value="<?= htmlspecialchars((string) $row['NIS']) ?>">
                                    <button type="submit" class="admin-action danger admin-account-delete-button">Hapus</button>
                                </form>
                            </div>
                            <div class="admin-account-fields">
                                <div><span>NIS</span><strong><?= htmlspecialchars((string) $row['NIS']) ?></strong></div>
                                <div><span>USERNAME</span><strong><?= htmlspecialchars($row['username']) ?></strong></div>
                                <div><span>E-MAIL</span><strong><?= htmlspecialchars((string) ($row['email'] ?: 'Belum diisi')) ?></strong></div>
                            </div>
                            <details class="admin-book-edit admin-account-edit">
                                <summary>Edit Pengguna</summary>
                                <form class="admin-book-form admin-account-form" method="post">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="tab" value="users">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="nis" value="<?= htmlspecialchars((string) $row['NIS']) ?>">
                                    <label>NIS
                                        <input value="<?= htmlspecialchars((string) $row['NIS']) ?>" disabled>
                                    </label>
                                    <label>Nama
                                        <input name="nama" value="<?= htmlspecialchars($row['nama']) ?>" required>
                                    </label>
                                    <label>Username
                                        <input name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
                                    </label>
                                    <label>Email
                                        <input type="email" name="email" value="<?= htmlspecialchars((string) ($row['email'] ?? '')) ?>">
                                    </label>
                                    <label>Kelas
                                        <input name="kelas" value="<?= htmlspecialchars((string) ($row['kelas'] ?? '')) ?>">
                                    </label>
                                    <div class="admin-book-form-actions">
                                        <button type="submit" class="btn-primary">Simpan perubahan</button>
                                    </div>
                                </form>
                            </details>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><p class="admin-empty admin-panel">Tidak ada pengguna yang cocok.</p><?php endif; ?>
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
                                        <textarea name="sinopsis" maxlength="60000" rows="4"><?= htmlspecialchars($row['sinopsis'] ?? '') ?></textarea>
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
                    <?php if (!$rows): ?><p class="admin-empty admin-panel">Belum ada buku yang cocok.</p><?php endif; ?>
                </div>
            <?php elseif ($tab === 'publishers' || $tab === 'categories' || $tab === 'authors'): ?>
                <?php
                    $entityFields = [
                        'publishers' => ['id_penerbit', 'nama_penerbit', 'update_publisher', 'delete_publisher', 'Penerbit'],
                        'categories' => ['id_kategori', 'kategori', 'update_category', 'delete_category', 'Kategori'],
                        'authors' => ['id_penulis', 'nama_penulis', 'update_author', 'delete_author', 'Penulis'],
                    ];
                    [$idField, $nameField, $updateAction, $deleteAction, $entityLabel] = $entityFields[$tab];
                    $nameMaxLength = $tab === 'authors' ? 150 : 100;
                ?>
                <div class="admin-book-list admin-lookup-list">
                    <?php foreach ($rows as $row): ?>
                        <?php $rowId = $row[$idField]; ?>
                        <article class="admin-book-card admin-lookup-card">
                            <div class="admin-lookup-header">
                                <div class="admin-lookup-identity">
                                    <p class="admin-book-id">ID <?= htmlspecialchars($rowId) ?></p>
                                    <h3><?= htmlspecialchars($row[$nameField]) ?></h3>
                                </div>
                                <div class="admin-lookup-count">
                                    <strong><?= number_format((int) $row['jumlah_buku']) ?></strong>
                                    <span>buku terkait</span>
                                </div>
                            </div>
                            <details class="admin-book-edit admin-lookup-edit">
                                <summary>Edit <?= htmlspecialchars($entityLabel) ?></summary>
                                <div class="admin-lookup-edit-controls">
                                    <form class="admin-lookup-update-form" method="post">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                        <input type="hidden" name="action" value="<?= $updateAction ?>">
                                        <input type="hidden" name="<?= $idField ?>" value="<?= htmlspecialchars($rowId) ?>">
                                        <label>Nama <?= htmlspecialchars($entityLabel) ?>
                                            <input name="<?= $nameField ?>" maxlength="<?= $nameMaxLength ?>" value="<?= htmlspecialchars($row[$nameField]) ?>" required>
                                        </label>
                                        <button type="submit" class="admin-action">Simpan</button>
                                    </form>
                                    <form class="admin-lookup-delete-form" method="post" onsubmit="return confirm('Hapus data ini? Data yang masih digunakan oleh buku tidak dapat dihapus.');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                        <input type="hidden" name="action" value="<?= $deleteAction ?>">
                                        <input type="hidden" name="<?= $idField ?>" value="<?= htmlspecialchars($rowId) ?>">
                                        <button type="submit" class="admin-action danger">Hapus</button>
                                    </form>
                                </div>
                            </details>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><p class="admin-empty admin-panel">Belum ada data <?= strtolower($entityLabel) ?>.</p><?php endif; ?>
                </div>
            <?php elseif ($tab === 'staff'): ?>
                <div class="admin-book-list admin-account-list">
                    <?php foreach ($rows as $row): ?>
                        <?php $isCurrentStaff = $row['id_petugas'] === (string) $user['id_petugas']; ?>
                        <article class="admin-book-card admin-account-card">
                            <div class="admin-account-heading">
                                <h3><?= htmlspecialchars($row['nama']) ?> <span>ADMIN</span></h3>
                                <?php if (!$isCurrentStaff): ?>
                                    <form class="admin-account-delete-top" method="post" onsubmit="return confirm('Hapus akun admin ini?');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                        <input type="hidden" name="tab" value="staff">
                                        <input type="hidden" name="action" value="delete_staff">
                                        <input type="hidden" name="id_petugas" value="<?= htmlspecialchars($row['id_petugas']) ?>">
                                        <button type="submit" class="admin-action danger admin-account-delete-button">Hapus</button>
                                    </form>
                                <?php else: ?>
                                    <span class="admin-account-self">Akun yang sedang digunakan</span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-account-fields">
                                <div><span>ID ADMIN</span><strong><?= htmlspecialchars($row['id_petugas']) ?></strong></div>
                                <div><span>USERNAME</span><strong><?= htmlspecialchars((string) ($row['username'] ?: 'Belum diatur')) ?></strong></div>
                            </div>
                            <details class="admin-book-edit admin-account-edit">
                                <summary>Edit Admin</summary>
                                <form class="admin-book-form admin-account-form" method="post">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                    <input type="hidden" name="tab" value="staff">
                                    <input type="hidden" name="action" value="update_staff">
                                    <input type="hidden" name="id_petugas" value="<?= htmlspecialchars($row['id_petugas']) ?>">
                                    <label>ID Admin
                                        <input value="<?= htmlspecialchars($row['id_petugas']) ?>" disabled>
                                    </label>
                                    <label>Nama admin
                                        <input name="nama" value="<?= htmlspecialchars($row['nama']) ?>" required>
                                    </label>
                                    <label>Username login
                                        <input name="username" value="<?= htmlspecialchars((string) ($row['username'] ?? '')) ?>" placeholder="Username">
                                    </label>
                                    <label>Password baru (opsional)
                                        <input type="password" name="password" minlength="6">
                                    </label>
                                    <div class="admin-book-form-actions">
                                        <button type="submit" class="btn-primary">Simpan perubahan</button>
                                    </div>
                                </form>
                            </details>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><p class="admin-empty admin-panel">Belum ada akun admin.</p><?php endif; ?>
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
                                    <td data-label="Tanggal">
                                        <small><?= $row['status'] === 'menunggu_pengambilan' ? 'Diajukan' : 'Pinjam' ?> <?= htmlspecialchars($row['tgl_peminjam']) ?></small>
                                        <?php if ($row['status'] === 'menunggu_pengambilan' && $row['reservation_expires_at'] !== null): ?>
                                            <small>Ambil sebelum <?= htmlspecialchars($row['reservation_expires_at']) ?></small>
                                        <?php elseif ($row['status'] === 'dipinjam'): ?>
                                            <small>Tempo <?= htmlspecialchars($row['tgl_jatuh_tempo']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Status">
                                        <?php if ($row['status'] === 'menunggu_pengambilan'): ?>
                                            <span class="status warn">Menunggu pengambilan</span>
                                        <?php elseif ($row['status'] === 'dibatalkan'): ?>
                                            <span class="status danger">Permintaan kedaluwarsa</span>
                                        <?php elseif ($row['tgl_kembali'] !== null): ?>
                                            <span class="status ok">Kembali <?= htmlspecialchars($row['tgl_kembali']) ?></span>
                                        <?php elseif ($row['tgl_pengajuan_kembali'] !== null): ?>
                                            <span class="status warn">Menunggu konfirmasi</span>
                                            <small>Diajukan <?= htmlspecialchars($row['tgl_pengajuan_kembali']) ?></small>
                                        <?php else: ?>
                                            <span class="status info">Belum diajukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Aksi" class="admin-actions-cell">
                                        <?php if ($row['status'] === 'menunggu_pengambilan'): ?>
                                            <form method="post" onsubmit="return confirm('Konfirmasi bahwa buku sudah diserahkan kepada anggota?');">
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['admin_csrf']) ?>">
                                                <input type="hidden" name="tab" value="loans">
                                                <input type="hidden" name="action" value="confirm_pickup">
                                                <input type="hidden" name="id_detail" value="<?= htmlspecialchars($row['id_detail']) ?>">
                                                <button type="submit" class="admin-action">Konfirmasi manual</button>
                                            </form>
                                        <?php elseif ($row['status'] === 'dipinjam' && $row['tgl_kembali'] === null && $row['tgl_pengajuan_kembali'] !== null): ?>
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
                                        <?php elseif ($row['status'] === 'dipinjam' && $row['tgl_kembali'] === null): ?>
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
