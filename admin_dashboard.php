<?php
$adminPageTab = 'dashboard';
require __DIR__ . '/admin_shared.php';
    $dashboardStats = $pdo->query('SELECT
        (SELECT COUNT(*) FROM buku) AS total_books,
        (SELECT COUNT(*) FROM siswa) AS total_members,
        (SELECT COUNT(*) FROM detail_peminjaman WHERE tgl_kembali IS NULL) AS active_loans,
        (SELECT COUNT(*) FROM detail_peminjaman d
            JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
            WHERE d.tgl_kembali IS NULL AND p.tgl_jatuh_tempo < CURDATE()) AS overdue_loans,
        (SELECT COUNT(*) FROM detail_peminjaman
            WHERE tgl_kembali IS NULL AND tgl_pengajuan_kembali IS NOT NULL) AS pending_returns,
        (SELECT COUNT(*) FROM penerbit) AS total_publishers')->fetch();
    $periodFilter = $_GET['period'] ?? 'month';
    if (!in_array($periodFilter, ['week', 'month', 'year', 'all'], true)) {
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
    } elseif ($periodFilter === 'all') {
        $firstLoanDate = $pdo->query('SELECT MIN(tgl_peminjam) FROM peminjaman')->fetchColumn();
        $chartStart = $firstLoanDate
            ? (new DateTimeImmutable($firstLoanDate))->setDate((int) (new DateTimeImmutable($firstLoanDate))->format('Y'), 1, 1)
            : $today->setDate((int) $today->format('Y'), 1, 1);
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
        $width = max(720, 48 + (count($periods) - 1) * 96);
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
        LEFT JOIN siswa s ON s.NIS = p.NIS
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
                    <a class="admin-dashboard-cta" href="admin_peminjaman.php">Kelola peminjaman <span aria-hidden="true">&rarr;</span></a>
                </div>

                <form class="admin-chart-filter" method="get" action="admin_dashboard.php">
                    <label for="chart-period">Periode grafik</label>
                    <select id="chart-period" name="period" onchange="this.form.submit()">
                        <?php foreach (['week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun', 'all' => 'Semua'] as $periodValue => $periodLabel): ?>
                            <option value="<?= $periodValue ?>" <?= $periodFilter === $periodValue ? 'selected' : '' ?>><?= $periodLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php foreach ($charts as $chartName => $chart): ?>
                    <section class="admin-panel admin-chart-panel" data-chart="<?= htmlspecialchars($chartName) ?>">
                        <h2 class="admin-chart-title"><?= htmlspecialchars($chart['title']) ?></h2>
                        <div class="admin-chart-scroll">
                            <svg class="admin-loan-chart" viewBox="0 0 <?= $chart['data']['width'] ?> 232"
                                 preserveAspectRatio="none"
                                 role="img" aria-label="<?= htmlspecialchars($chart['title']) ?>">
                                <defs>
                                    <?php foreach ($chart['series'] as $series): ?>
                                        <?php
                                        $chartColors = [
                                            'loan' => ['#62bdd2', '#59b5d0'],
                                            'return' => ['#d47bb4', '#cf75ad'],
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
                                    <text x="<?= $chart['data']['xPositions'][$index] ?>" y="178" class="admin-chart-month"><?= htmlspecialchars($period['label']) ?></text>
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
                    </section>
                <?php endforeach; ?>

                <div class="admin-warning-grid">
                    <?php foreach ([
                        ['label' => 'Terlambat dikembalikan', 'value' => $dashboardStats['overdue_loans'], 'icon' => 'alert', 'href' => 'admin_peminjaman.php?status=terlambat', 'tone' => 'red'],
                        ['label' => 'Menunggu konfirmasi', 'value' => $dashboardStats['pending_returns'], 'icon' => 'clock', 'href' => 'admin_peminjaman.php?status=menunggu', 'tone' => 'amber'],
                    ] as $warning): ?>
                        <a class="admin-overview-card admin-warning-card <?= $warning['tone'] ?>" href="<?= $warning['href'] ?>">
                            <span class="admin-overview-icon <?= $warning['tone'] ?>"><?= icon($warning['icon'], 21) ?></span>
                            <span class="admin-overview-value"><?= number_format((int) $warning['value']) ?></span>
                            <span class="admin-overview-label"><?= htmlspecialchars($warning['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="admin-overview-grid">
                    <?php foreach ([
                        ['label' => 'Total koleksi buku', 'value' => $dashboardStats['total_books'], 'icon' => 'book', 'href' => 'manajemen.php', 'tone' => 'green'],
                        ['label' => 'Pengguna terdaftar', 'value' => $dashboardStats['total_members'], 'icon' => 'user', 'href' => 'manajemen_user.php', 'tone' => 'blue'],
                        ['label' => 'Sedang dipinjam', 'value' => $dashboardStats['active_loans'], 'icon' => 'loans', 'href' => 'admin_peminjaman.php?status=dipinjam', 'tone' => 'purple'],
                        ['label' => 'Penerbit terdaftar', 'value' => $dashboardStats['total_publishers'], 'icon' => 'book', 'href' => 'manajemen.php?section=publishers', 'tone' => 'teal'],
                    ] as $stat): ?>
                        <a class="admin-overview-card" href="<?= $stat['href'] ?>">
                            <span class="admin-overview-icon <?= $stat['tone'] ?>"><?= icon($stat['icon'], 21) ?></span>
                            <span class="admin-overview-value"><?= number_format((int) $stat['value']) ?></span>
                            <span class="admin-overview-label"><?= htmlspecialchars($stat['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <section class="admin-panel admin-recent-panel">
                    <div class="table-head">
                        <div>
                            <h2>Aktivitas peminjaman terbaru</h2>
                            <p>Enam peminjaman terakhir di perpustakaan</p>
                        </div>
                        <a href="admin_peminjaman.php">Lihat semua</a>
                    </div>
                    <div class="table-wrap admin-table-wrap">
                        <table class="admin-table">
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
                                        <td><strong><?= htmlspecialchars($loan['judul'] ?? 'Buku tidak ditemukan') ?></strong><small>#<?= htmlspecialchars((string) $loan['id_peminjaman']) ?></small></td>
                                        <td><?= htmlspecialchars($loan['nama'] ?? 'Anggota tidak ditemukan') ?></td>
                                        <td><?= htmlspecialchars($loan['tgl_peminjam']) ?></td>
                                        <td><?= htmlspecialchars($loan['tgl_jatuh_tempo']) ?></td>
                                        <td><span class="status <?= $loanStatus[0] ?>"><?= $loanStatus[1] ?></span></td>
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
<?php require __DIR__ . '/admin_interaksi.php'; admin_page_footer(); ?>
