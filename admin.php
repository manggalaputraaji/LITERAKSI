<?php
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'peminjaman', 'manajemen', 'manajemen_user'];
$route = is_string($page) && in_array($page, $allowedPages, true) ? $page : '';

?>
<?php if ($route === 'dashboard') { ?>
<?php
$adminPageTab = 'dashboard';
require __DIR__ . '/adminlogic.php';
    $dashboardStats = $pdo->query('SELECT
        (SELECT COUNT(*) FROM buku) AS total_books,
        (SELECT COUNT(*) FROM `user`) AS total_members,
        (SELECT COUNT(*) FROM detail_peminjaman WHERE tgl_kembali IS NULL) AS active_loans,
        (SELECT COUNT(*) FROM detail_peminjaman d
            JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
            WHERE d.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()) AS overdue_loans,
        (SELECT COUNT(*) FROM detail_peminjaman
            WHERE tgl_kembali IS NULL AND tgl_pengajuan_kembali IS NOT NULL) AS pending_returns,
        (SELECT COUNT(*) FROM penerbit) AS total_publishers')->fetch();
    $periodFilter = $_GET['period'] ?? 'month';
    if (!in_array($periodFilter, ['week', 'month', 'year'], true)) {
        $periodFilter = 'month';
    }
    $today = new DateTimeImmutable('today');
    $currentMonth = new DateTimeImmutable('first day of this month');
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    if ($periodFilter === 'week') {
        $chartStart = (new DateTimeImmutable('monday this week'))->modify('-11 weeks');
        $chartEnd = $chartStart->modify('+12 weeks');
        $periodStep = '+1 week';
        $bucketFormat = '%x-%v';
        $bucketKey = static fn(DateTimeImmutable $date): string => $date->format('o-W');
        $bucketLabel = static fn(DateTimeImmutable $date): string => $date->format('d/m');
    } elseif ($periodFilter === 'year') {
        $chartStart = $today->setDate((int) $today->format('Y') - 4, 1, 1);
        $chartEnd = $today->setDate((int) $today->format('Y') + 1, 1, 1);
        $periodStep = '+1 year';
        $bucketFormat = '%Y';
        $bucketKey = static fn(DateTimeImmutable $date): string => $date->format('Y');
        $bucketLabel = static fn(DateTimeImmutable $date): string => $date->format('Y');
    } else {
        $chartStart = $currentMonth->modify('-11 months');
        $chartEnd = $currentMonth->modify('+1 month');
        $periodStep = '+1 month';
        $bucketFormat = '%Y-%m';
        $bucketKey = static fn(DateTimeImmutable $date): string => $date->format('Y-m');
        $bucketLabel = static fn(DateTimeImmutable $date): string => $monthNames[(int) $date->format('n') - 1];
    }
    $monthlyLoansQuery = $pdo->prepare('SELECT DATE_FORMAT(p.tgl_peminjam, ?) AS periode,
            COUNT(d.id_detail) AS jumlah, \'loan\' AS tipe
        FROM peminjaman p
        JOIN detail_peminjaman d ON d.id_peminjaman = p.id_peminjaman
        WHERE p.tgl_peminjam >= ? AND p.tgl_peminjam < ?
        GROUP BY DATE_FORMAT(p.tgl_peminjam, ?)
        UNION ALL
        SELECT DATE_FORMAT(d.tgl_kembali, ?) AS periode,
            COUNT(*) AS jumlah, \'return\' AS tipe
        FROM detail_peminjaman d
        WHERE d.tgl_kembali >= ? AND d.tgl_kembali < ?
        GROUP BY DATE_FORMAT(d.tgl_kembali, ?)');
    $chartStartDate = $chartStart->format('Y-m-d');
    $chartEndDate = $chartEnd->format('Y-m-d');
    $monthlyLoansQuery->execute([
        $bucketFormat, $chartStartDate, $chartEndDate, $bucketFormat,
        $bucketFormat, $chartStartDate, $chartEndDate, $bucketFormat,
    ]);
    $monthlySeries = ['loan' => [], 'return' => []];
    foreach ($monthlyLoansQuery->fetchAll() as $monthRow) {
        $monthlySeries[$monthRow['tipe']][$monthRow['periode']] = (int) $monthRow['jumlah'];
    }
    $loanChart = [];
    for ($period = $chartStart; $period < $chartEnd; $period = $period->modify($periodStep)) {
        $periodKey = $bucketKey($period);
        $loanChart[] = [
            'label' => $bucketLabel($period),
            'loans' => $monthlySeries['loan'][$periodKey] ?? 0,
            'returns' => $monthlySeries['return'][$periodKey] ?? 0,
        ];
    }
    $warningsQuery = $pdo->prepare('SELECT DATE_FORMAT(p.tgl_jatuh_tempo, ?) AS periode,
            COUNT(*) AS jumlah, \'overdue\' AS tipe
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE d.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()
            AND p.tgl_jatuh_tempo >= ? AND p.tgl_jatuh_tempo < ?
        GROUP BY DATE_FORMAT(p.tgl_jatuh_tempo, ?)
        UNION ALL
        SELECT DATE_FORMAT(d.tgl_pengajuan_kembali, ?) AS periode,
            COUNT(*) AS jumlah, \'pending\' AS tipe
        FROM detail_peminjaman d
        WHERE d.tgl_kembali IS NULL AND d.tgl_pengajuan_kembali IS NOT NULL
            AND d.tgl_pengajuan_kembali >= ? AND d.tgl_pengajuan_kembali < ?
        GROUP BY DATE_FORMAT(d.tgl_pengajuan_kembali, ?)');
    $warningsQuery->execute([
        $bucketFormat, $chartStartDate, $chartEndDate, $bucketFormat,
        $bucketFormat, $chartStartDate, $chartEndDate, $bucketFormat,
    ]);
    $warningSeries = ['overdue' => [], 'pending' => []];
    foreach ($warningsQuery->fetchAll() as $warningRow) {
        $warningSeries[$warningRow['tipe']][$warningRow['periode']] = (int) $warningRow['jumlah'];
    }
    $warningChart = [];
    for ($period = $chartStart; $period < $chartEnd; $period = $period->modify($periodStep)) {
        $periodKey = $bucketKey($period);
        $warningChart[] = [
            'label' => $bucketLabel($period),
            'overdue' => $warningSeries['overdue'][$periodKey] ?? 0,
            'pending' => $warningSeries['pending'][$periodKey] ?? 0,
        ];
    }
    $chartLinePath = static function (array $points): string {
        $path = 'M ' . $points[0]['x'] . ' ' . $points[0]['y'];
        for ($index = 1, $length = count($points); $index < $length; $index++) {
            $previous = $points[$index - 1];
            $current = $points[$index];
            $middle = ($previous['x'] + $current['x']) / 2;
            $path .= ' C ' . $middle . ' ' . $previous['y']
                . ', ' . $middle . ' ' . $current['y']
                . ', ' . $current['x'] . ' ' . $current['y'];
        }
        return $path;
    };
    $buildChart = static function (array $periods, array $series) use ($chartLinePath): array {
        $width = 1120;
        $left = 24;
        $right = $width - 24;
        $top = 18;
        $bottom = 154;
        $max = max(1, ...array_merge(...array_map(
            static fn(array $definition): array => array_column($periods, $definition['key']),
            $series
        )));
        $xPositions = [];
        foreach ($periods as $index => $period) {
            $xPositions[] = count($periods) === 1
                ? ($left + $right) / 2
                : $left + $index * (($right - $left) / (count($periods) - 1));
        }
        $lines = [];
        foreach ($series as $definition) {
            $points = [];
            foreach ($periods as $index => $period) {
                $y = $bottom - ($period[$definition['key']] / $max) * ($bottom - $top);
                $points[] = ['x' => $xPositions[$index], 'y' => $y];
            }
            $line = $chartLinePath($points);
            $lines[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'tone' => $definition['tone'],
                'points' => $points,
                'line' => $line,
                'area' => $line . ' L ' . $points[count($points) - 1]['x'] . ' ' . $bottom
                    . ' L ' . $points[0]['x'] . ' ' . $bottom . ' Z',
            ];
        }
        return [
            'width' => $width,
            'periods' => $periods,
            'xPositions' => $xPositions,
            'lines' => $lines,
        ];
    };
    $charts = [
        'lending' => [
            'title' => 'Peminjaman dan pengembalian',
            'series' => [
                ['key' => 'loans', 'label' => 'Peminjaman', 'tone' => 'loan'],
                ['key' => 'returns', 'label' => 'Pengembalian', 'tone' => 'return'],
            ],
            'data' => $buildChart($loanChart, [
                ['key' => 'loans', 'label' => 'Peminjaman', 'tone' => 'loan'],
                ['key' => 'returns', 'label' => 'Pengembalian', 'tone' => 'return'],
            ]),
        ],
        'warnings' => [
            'title' => 'Peringatan perpustakaan',
            'series' => [
                ['key' => 'overdue', 'label' => 'Terlambat', 'tone' => 'overdue'],
                ['key' => 'pending', 'label' => 'Menunggu konfirmasi', 'tone' => 'pending'],
            ],
            'data' => $buildChart($warningChart, [
                ['key' => 'overdue', 'label' => 'Terlambat', 'tone' => 'overdue'],
                ['key' => 'pending', 'label' => 'Menunggu konfirmasi', 'tone' => 'pending'],
            ]),
        ],
    ];
    $recentLoans = $pdo->query('SELECT d.tgl_kembali, d.tgl_pengajuan_kembali,
            p.id_peminjaman, p.tgl_peminjam, p.tgl_jatuh_tempo, s.nama, b.judul,
            (d.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()) AS is_overdue
        FROM detail_peminjaman d
        JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        LEFT JOIN `user` s ON s.NIS = p.NIS
        LEFT JOIN buku b ON b.id_buku = d.id_buku
        ORDER BY p.tgl_peminjam DESC, p.id_peminjaman DESC, d.id_detail DESC
        LIMIT 6')->fetchAll();
?>
<?php admin_page_header('admin_dashboard', 'Dashboard Admin'); ?>

            <section class="admin-dashboard">
                <div class="admin-welcome">
                    <div>
                        <p class="admin-eyebrow">RINGKASAN PERPUSTAKAAN</p>
                        <h2>Selamat datang, <?= htmlspecialchars($user['nama']) ?>!</h2>
                        <p>Pantau koleksi, pengguna, dan aktivitas perpustakaan dari satu tempat.</p>
                    </div>
                    <a class="admin-dashboard-cta" href="admin.php?page=peminjaman">Kelola peminjaman <span aria-hidden="true">&rarr;</span></a>
                </div>

                <section class="admin-dashboard-section" aria-labelledby="admin-priority-title">
                    <div class="admin-section-heading">
                        <h2 class="admin-section-title" id="admin-priority-title">Perlu perhatian</h2>
                        <p>Periksa pengembalian terlambat dan permintaan yang menunggu.</p>
                    </div>
                    <div class="admin-warning-grid">
                        <?php foreach ([
                            ['label' => 'Terlambat dikembalikan', 'value' => $dashboardStats['overdue_loans'], 'icon' => 'alert', 'href' => 'admin.php?page=peminjaman&status=terlambat', 'tone' => 'red'],
                            ['label' => 'Menunggu konfirmasi', 'value' => $dashboardStats['pending_returns'], 'icon' => 'clock', 'href' => 'admin.php?page=peminjaman&status=menunggu', 'tone' => 'amber'],
                        ] as $warning): ?>
                            <a class="admin-overview-card admin-warning-card <?= $warning['tone'] ?>" href="<?= $warning['href'] ?>">
                                <span class="admin-overview-icon <?= $warning['tone'] ?>"><?= icon($warning['icon'], 21) ?></span>
                                <span class="admin-overview-value"><?= number_format((int) $warning['value']) ?></span>
                                <span class="admin-overview-label"><?= htmlspecialchars($warning['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-dashboard-section" aria-labelledby="admin-overview-title">
                    <div class="admin-section-heading">
                        <h2 class="admin-section-title" id="admin-overview-title">Ringkasan perpustakaan</h2>
                        <p>Pilih kartu untuk langsung membuka koleksi, pengguna, atau peminjaman.</p>
                    </div>
                    <div class="admin-overview-grid">
                        <?php foreach ([
                            ['label' => 'Total koleksi buku', 'value' => $dashboardStats['total_books'], 'icon' => 'book', 'href' => 'admin.php?page=manajemen', 'tone' => 'green'],
                            ['label' => 'Pengguna terdaftar', 'value' => $dashboardStats['total_members'], 'icon' => 'user', 'href' => 'admin.php?page=manajemen_user', 'tone' => 'blue'],
                            ['label' => 'Sedang dipinjam', 'value' => $dashboardStats['active_loans'], 'icon' => 'loans', 'href' => 'admin.php?page=peminjaman&status=dipinjam', 'tone' => 'purple'],
                            ['label' => 'Penerbit terdaftar', 'value' => $dashboardStats['total_publishers'], 'icon' => 'book', 'href' => 'admin.php?page=manajemen&section=publishers', 'tone' => 'teal'],
                        ] as $stat): ?>
                            <a class="admin-overview-card" href="<?= $stat['href'] ?>">
                                <span class="admin-overview-icon <?= $stat['tone'] ?>"><?= icon($stat['icon'], 21) ?></span>
                                <span class="admin-overview-value"><?= number_format((int) $stat['value']) ?></span>
                                <span class="admin-overview-label"><?= htmlspecialchars($stat['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="admin-dashboard-section" aria-labelledby="admin-activity-title">
                    <form class="admin-chart-filter" method="get" action="admin.php">
                        <input type="hidden" name="page" value="dashboard">
                        <label for="chart-period">Tampilkan periode</label>
                        <select id="chart-period" name="period" onchange="this.form.submit()">
                            <?php foreach (['week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun'] as $periodValue => $periodLabel): ?>
                                <option value="<?= $periodValue ?>" <?= $periodFilter === $periodValue ? 'selected' : '' ?>><?= $periodLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php foreach ($charts as $chartName => $chart): ?>
                        <?php
                        $hasChartData = false;
                        $labelStep = max(1, (int) ceil(count($chart['data']['periods']) / 12));
                        foreach ($chart['data']['periods'] as $period) {
                            foreach ($chart['series'] as $series) {
                                if ((int) $period[$series['key']] > 0) {
                                    $hasChartData = true;
                                    break 2;
                                }
                            }
                        }
                        ?>
                        <section class="admin-panel admin-chart-panel" data-chart="<?= htmlspecialchars($chartName) ?>">
                            <h3 class="admin-chart-title"><?= htmlspecialchars($chart['title']) ?></h3>
                            <?php if ($hasChartData): ?>
                                <div class="admin-chart-scroll">
                                    <svg class="admin-loan-chart"
                                         viewBox="0 0 <?= $chart['data']['width'] ?> 232"
                                         preserveAspectRatio="xMidYMid meet"
                                         role="img" aria-label="<?= htmlspecialchars($chart['title']) ?>">
                                    <defs>
                                        <?php foreach ($chart['series'] as $series): ?>
                                            <?php
                                            $chartColors = [
                                                'loan' => ['#6257d9', '#5148bd'],
                                                'return' => ['#a79cf0', '#8173d7'],
                                                'overdue' => ['#e58d82', '#cf655b'],
                                                'pending' => ['#e3bd70', '#bf913e'],
                                            ];
                                            [$fillColor, $lineColor] = $chartColors[$series['tone']];
                                            ?>
                                            <linearGradient id="<?= $chartName ?>-<?= $series['tone'] ?>-fill" x1="0" x2="0" y1="0" y2="1">
                                                <stop offset="0%" stop-color="<?= $fillColor ?>" stop-opacity=".48"/>
                                                <stop offset="100%" stop-color="<?= $fillColor ?>" stop-opacity=".08"/>
                                            </linearGradient>
                                        <?php endforeach; ?>
                                    </defs>
                                    <?php foreach ([18, 52, 86, 120, 154] as $gridY): ?>
                                        <line x1="24" y1="<?= $gridY ?>" x2="<?= $chart['data']['width'] - 24 ?>" y2="<?= $gridY ?>" class="admin-chart-gridline"/>
                                    <?php endforeach; ?>
                                    <?php foreach ($chart['data']['lines'] as $line): ?>
                                        <path d="<?= $line['area'] ?>" fill="url(#<?= $chartName ?>-<?= $line['tone'] ?>-fill)"/>
                                    <?php endforeach; ?>
                                    <?php foreach ($chart['data']['lines'] as $line): ?>
                                        <path d="<?= $line['line'] ?>" class="admin-chart-line <?= $line['tone'] ?>"/>
                                    <?php endforeach; ?>
                                    <?php foreach ($chart['data']['periods'] as $index => $period): ?>
                                        <?php
                                        $hitStart = $index === 0
                                            ? 24
                                            : ($chart['data']['xPositions'][$index - 1] + $chart['data']['xPositions'][$index]) / 2;
                                        $hitEnd = $index === count($chart['data']['periods']) - 1
                                            ? $chart['data']['width'] - 24
                                            : ($chart['data']['xPositions'][$index] + $chart['data']['xPositions'][$index + 1]) / 2;
                                        $firstSeries = $chart['series'][0];
                                        $secondSeries = $chart['series'][1];
                                        ?>
                                        <g class="admin-chart-hitbox" tabindex="0" role="img"
                                           aria-label="<?= htmlspecialchars($period['label'] . ': ' . $firstSeries['label'] . ' ' . $period[$firstSeries['key']] . ', ' . $secondSeries['label'] . ' ' . $period[$secondSeries['key']]) ?>"
                                           data-month="<?= htmlspecialchars($period['label']) ?>"
                                           data-first-label="<?= htmlspecialchars($firstSeries['label']) ?>"
                                           data-first-value="<?= (int) $period[$firstSeries['key']] ?>"
                                           data-first-tone="<?= htmlspecialchars($firstSeries['tone']) ?>"
                                           data-second-label="<?= htmlspecialchars($secondSeries['label']) ?>"
                                           data-second-value="<?= (int) $period[$secondSeries['key']] ?>"
                                           data-second-tone="<?= htmlspecialchars($secondSeries['tone']) ?>">
                                            <rect x="<?= $hitStart ?>" y="18" width="<?= max(1, $hitEnd - $hitStart) ?>" height="136"/>
                                        </g>
                                    <?php endforeach; ?>
                                    <?php foreach ($chart['data']['periods'] as $index => $period): ?>
                                        <?php if ($index % $labelStep === 0 || $index === count($chart['data']['periods']) - 1): ?>
                                            <text x="<?= $chart['data']['xPositions'][$index] ?>" y="178" class="admin-chart-month"><?= htmlspecialchars($period['label']) ?></text>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php foreach ($chart['series'] as $index => $series): ?>
                                        <?php $legendX = 28 + $index * 190; ?>
                                        <rect x="<?= $legendX ?>" y="205" width="10" height="10" rx="1" class="admin-chart-legend-swatch <?= $series['tone'] ?>"/>
                                        <text x="<?= $legendX + 17 ?>" y="214" class="admin-chart-legend-label"><?= htmlspecialchars($series['label']) ?></text>
                                    <?php endforeach; ?>
                                    </svg>
                                </div>
                                <div class="admin-chart-tooltip" role="status" aria-live="polite" hidden>
                                    <strong class="admin-chart-tooltip-month"></strong>
                                    <span class="first"><i></i><b></b><b class="admin-chart-tooltip-first"></b></span>
                                    <span class="second"><i></i><b></b><b class="admin-chart-tooltip-second"></b></span>
                                </div>
                            <?php else: ?>
                                <p class="admin-chart-empty" role="status">Belum ada aktivitas pada periode ini. Coba pilih periode lain.</p>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </section>

                <section class="admin-panel admin-recent-panel">
                    <div class="table-head">
                        <div>
                            <h2>Aktivitas peminjaman terbaru</h2>
                            <p>Enam peminjaman terakhir di perpustakaan</p>
                        </div>
                        <a href="admin.php?page=peminjaman">Lihat semua</a>
                    </div>
                    <div class="table-wrap admin-table-wrap">
                        <table class="admin-table recent-loans-table">
                            <thead><tr><th>Buku</th><th>Anggota</th><th>Tanggal pinjam</th><th>Jatuh tempo</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentLoans as $loan):
                                    $loanStatus = $loan['tgl_kembali'] !== null
                                        ? ['ok', 'Dikembalikan']
                                        : ($loan['tgl_pengajuan_kembali'] !== null
                                            ? ['warn', 'Menunggu konfirmasi']
                                            : ((bool) $loan['is_overdue']
                                                ? ['danger', 'Terlambat']
                                                : ['info', 'Dipinjam']));
                                ?>
                                    <tr>
                                        <td data-label="Buku"><strong><?= htmlspecialchars($loan['judul'] ?? 'Buku tidak ditemukan') ?></strong><small>#<?= htmlspecialchars((string) $loan['id_peminjaman']) ?></small></td>
                                        <td data-label="Anggota"><?= htmlspecialchars($loan['nama'] ?? 'Anggota tidak ditemukan') ?></td>
                                        <td data-label="Tanggal pinjam"><?= htmlspecialchars($loan['tgl_peminjam']) ?></td>
                                        <td data-label="Jatuh tempo"><?= htmlspecialchars($loan['tgl_jatuh_tempo']) ?></td>
                                        <td data-label="Status"><span class="status <?= $loanStatus[0] ?>"><?= $loanStatus[1] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$recentLoans): ?>
                                    <tr><td colspan="5" class="admin-empty">Belum ada aktivitas peminjaman.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
<?php  admin_page_footer(); ?>
<?php } elseif ($route === 'peminjaman') { ?>
<?php
$adminPageTab = 'loans';
require __DIR__ . '/adminlogic.php';
    $sql = 'SELECT d.id_detail, d.tgl_kembali, d.tgl_pengajuan_kembali,
                   p.id_peminjaman, p.NIS, p.tgl_peminjam,
				   p.tgl_jatuh_tempo, s.nama, b.judul
			FROM detail_peminjaman d
			JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
			LEFT JOIN `user` s ON s.NIS = p.NIS
			LEFT JOIN buku b ON b.id_buku = d.id_buku';
    $conditions = [];
    $params = [];
    if ($statusFilter === 'dipinjam') {
        $conditions[] = 'd.tgl_kembali IS NULL';
    } elseif ($statusFilter === 'terlambat') {
        $conditions[] = 'd.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()';
    } elseif ($statusFilter === 'menunggu') {
        $conditions[] = 'd.tgl_kembali IS NULL AND d.tgl_pengajuan_kembali IS NOT NULL';
    } elseif ($statusFilter === 'kembali') {
        $conditions[] = 'd.tgl_kembali IS NOT NULL';
    }
    if ($search !== '') {
        $conditions[] = '(p.id_peminjaman LIKE ? OR p.NIS LIKE ? OR s.nama LIKE ? OR b.judul LIKE ?)';
        $needle = '%' . $search . '%';
        array_push($params, $needle, $needle, $needle, $needle);
    }
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $validDate = static fn($date): bool => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    if ($validDate($dateFrom)) {
        $conditions[] = 'p.tgl_peminjam >= ?';
        $params[] = $dateFrom;
    } else { $dateFrom = ''; }
    if ($validDate($dateTo)) {
        $conditions[] = 'p.tgl_peminjam <= ?';
        $params[] = $dateTo;
    } else { $dateTo = ''; }
    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sortFilter = $_GET['sort'] ?? 'due_soon';
    $sortOptions = [
        'due_soon' => '(d.tgl_pengajuan_kembali IS NOT NULL) DESC, (d.tgl_kembali IS NULL) DESC, p.tgl_jatuh_tempo ASC, p.id_peminjaman DESC',
        'newest' => 'p.tgl_peminjam DESC, p.id_peminjaman DESC',
        'oldest' => 'p.tgl_peminjam ASC, p.id_peminjaman ASC',
        'due_latest' => 'p.tgl_jatuh_tempo DESC, p.id_peminjaman DESC',
        'member' => 's.nama ASC, p.id_peminjaman DESC',
        'book' => 'b.judul ASC, p.id_peminjaman DESC',
    ];
    if (!is_string($sortFilter) || !isset($sortOptions[$sortFilter])) { $sortFilter = 'due_soon'; }
    $sql .= ' ORDER BY ' . $sortOptions[$sortFilter];
    $query = $pdo->prepare($sql);
    $query->execute($params);
    $rows = $query->fetchAll();
?>
<?php admin_page_header('admin_peminjaman', 'Peminjaman'); require __DIR__ . '/adminkelola.php';  admin_page_footer(); ?>
<?php } elseif ($route === 'manajemen') { ?>
<?php
$adminPageTab = 'manajemen';
require __DIR__ . '/adminlogic.php';
    $adminSections = [];
    $booksSql = 'SELECT b.id_buku, b.judul, pn.nama_penulis AS penulis, b.sinopsis, b.stok,
            b.id_kategori, b.id_penerbit, b.id_penulis, k.kategori, p.nama_penerbit,
            COALESCE(inventory.dipinjam, 0) AS stok_dipinjam,
            GREATEST(CAST(b.stok AS SIGNED) - COALESCE(inventory.dipinjam, 0), 0) AS stok_tersedia
        FROM buku b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        LEFT JOIN penerbit p ON p.id_penerbit = b.id_penerbit
        LEFT JOIN penulis pn ON pn.id_penulis = b.id_penulis
        LEFT JOIN (
            SELECT id_buku, COUNT(*) AS dipinjam
            FROM detail_peminjaman
            WHERE tgl_kembali IS NULL
            GROUP BY id_buku
        ) inventory ON inventory.id_buku = b.id_buku';
    $booksParams = [];
    if ($search !== '') {
        $booksSql .= ' WHERE b.id_buku LIKE ? OR b.judul LIKE ? OR pn.nama_penulis LIKE ?
            OR k.kategori LIKE ? OR p.nama_penerbit LIKE ?';
        $needle = '%' . $search . '%';
        $booksParams = [$needle, $needle, $needle, $needle, $needle];
    }
    $booksSql .= ' ORDER BY b.judul';
    $query = $pdo->prepare($booksSql);
    $query->execute($booksParams);
    $adminSections['books'] = $query->fetchAll();
    $inventoryStats = $pdo->query('SELECT COUNT(*) AS judul,
            COALESCE(SUM(b.stok), 0) AS total_stok,
            COALESCE(SUM(COALESCE(inventory.dipinjam, 0)), 0) AS sedang_dipinjam,
            COALESCE(SUM(GREATEST(CAST(b.stok AS SIGNED) - COALESCE(inventory.dipinjam, 0), 0)), 0) AS tersedia
        FROM buku b
        LEFT JOIN (
            SELECT id_buku, COUNT(*) AS dipinjam
            FROM detail_peminjaman
            WHERE tgl_kembali IS NULL
            GROUP BY id_buku
        ) inventory ON inventory.id_buku = b.id_buku')->fetch();

    foreach (['publishers', 'categories', 'authors'] as $section) {
        if ($section === 'publishers') {
            $sectionSql = 'SELECT p.id_penerbit, p.nama_penerbit, COUNT(b.id_buku) AS jumlah_buku
                FROM penerbit p LEFT JOIN buku b ON b.id_penerbit = p.id_penerbit';
            $sectionSql .= $search !== '' ? ' WHERE p.id_penerbit LIKE ? OR p.nama_penerbit LIKE ?' : '';
            $sectionSql .= ' GROUP BY p.id_penerbit, p.nama_penerbit ORDER BY p.nama_penerbit';
        } elseif ($section === 'categories') {
            $sectionSql = 'SELECT k.id_kategori, k.kategori, COUNT(b.id_buku) AS jumlah_buku
                FROM kategori k LEFT JOIN buku b ON b.id_kategori = k.id_kategori';
            $sectionSql .= $search !== '' ? ' WHERE k.id_kategori LIKE ? OR k.kategori LIKE ?' : '';
            $sectionSql .= ' GROUP BY k.id_kategori, k.kategori ORDER BY k.kategori';
        } else {
            $sectionSql = 'SELECT pn.id_penulis, pn.nama_penulis, COUNT(b.id_buku) AS jumlah_buku
                FROM penulis pn LEFT JOIN buku b ON b.id_penulis = pn.id_penulis';
            $sectionSql .= $search !== '' ? ' WHERE pn.id_penulis LIKE ? OR pn.nama_penulis LIKE ?' : '';
            $sectionSql .= ' GROUP BY pn.id_penulis, pn.nama_penulis ORDER BY pn.nama_penulis';
        }
        $query = $pdo->prepare($sectionSql);
        $query->execute($search !== '' ? [$needle, $needle] : []);
        $adminSections[$section] = $query->fetchAll();
    }
    $categories = $pdo->query('SELECT id_kategori, kategori FROM kategori ORDER BY kategori')->fetchAll();
    $publishers = $pdo->query('SELECT id_penerbit, nama_penerbit FROM penerbit ORDER BY nama_penerbit')->fetchAll();
    $authors = $pdo->query('SELECT id_penulis, nama_penulis FROM penulis ORDER BY nama_penulis')->fetchAll();
$rows = $adminSections[$activeSection];
?>
<?php admin_page_header('manajemen', 'Manajemen Perpustakaan', $sectionOptions['manajemen'], $activeSection); require __DIR__ . '/adminkelola.php';  admin_page_footer(); ?>
<?php } elseif ($route === 'manajemen_user') { ?>
<?php
$adminPageTab = 'manajemen_user';
require __DIR__ . '/adminlogic.php';
    $usersSql = 'SELECT NIS, nama, username, email, kelas FROM `user`';
    $usersParams = [];
    if ($search !== '') {
        $usersSql .= ' WHERE NIS LIKE ? OR nama LIKE ? OR username LIKE ? OR email LIKE ?';
        $needle = '%' . $search . '%';
        $usersParams = [$needle, $needle, $needle, $needle];
    }
    $usersSql .= ' ORDER BY nama ASC';
    $query = $pdo->prepare($usersSql);
    $query->execute($usersParams);
    $adminSections['users'] = $query->fetchAll();

    $staffSql = 'SELECT id_petugas, nama, username FROM `admin`';
    $staffParams = [];
    if ($search !== '') {
        $staffSql .= ' WHERE id_petugas LIKE ? OR nama LIKE ? OR username LIKE ?';
        $needle = '%' . $search . '%';
        $staffParams = [$needle, $needle, $needle];
    }
    $staffSql .= ' ORDER BY nama';
    $query = $pdo->prepare($staffSql);
    $query->execute($staffParams);
    $adminSections['staff'] = $query->fetchAll();
$rows = $adminSections[$activeSection];
?>
<?php admin_page_header('manajemen_user', 'Manajemen Pengguna', $sectionOptions['manajemen_user'], $activeSection); require __DIR__ . '/adminkelola.php';  admin_page_footer(); ?>
<?php } else { ?>
<!DOCTYPE html><html lang="id"><meta charset="UTF-8"><title>Halaman tidak ditemukan</title><body><p>Halaman tidak ditemukan.</p></body></html>
<?php } ?>
