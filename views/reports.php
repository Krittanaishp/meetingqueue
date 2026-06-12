<?php
require_once 'api/config.php';
use App\Repository\BookingRepository;

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$repo = new BookingRepository();
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$booker = $_GET['booker'] ?? '';
$roomId = $_GET['room_id'] ?? '';

$report_data = $repo->getAll([
    'start' => $startDate . ' 00:00:00',
    'end' => $endDate . ' 23:59:59',
    'exclude_status' => 'cancelled',
    'search' => $booker,
    'room_id' => $roomId
]);

$db = \App\Core\Database::getInstance()->getConnection();
$rooms = $db->query("SELECT id, name FROM rooms ORDER BY id")->fetchAll();

// Thai months mapping
$thai_months = [
    '01' => 'มกราคม', '02' => 'กุมภาพันธ์', '03' => 'มีนาคม', '04' => 'เมษายน',
    '05' => 'พฤษภาคม', '06' => 'มิถุนายน', '07' => 'กรกฎาคม', '08' => 'สิงหาคม',
    '09' => 'กันยายน', '10' => 'ตุลาคม', '11' => 'พฤศจิกายน', '12' => 'ธันวาคม'
];
$thai_months_short = [
    '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
    '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
    '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
];

$timeStart = strtotime($startDate);
$displayStartDate = 'วันที่ ' . date('j', $timeStart) . ' ' . $thai_months[date('m', $timeStart)] . ' ' . (date('Y', $timeStart) + 543);
$displayDate = $displayStartDate;
if ($startDate !== $endDate) {
    $timeEnd = strtotime($endDate);
    $displayEndDate = 'วันที่ ' . date('j', $timeEnd) . ' ' . $thai_months[date('m', $timeEnd)] . ' ' . (date('Y', $timeEnd) + 543);
    $displayDate = $displayStartDate . ' ถึง' . $displayEndDate;
}

// Calculate Room Usage Stats
$roomStats = [];
foreach($report_data as $row) {
    $rname = $row['room_name'] ?? 'ภายนอกสถานที่';
    if(!isset($roomStats[$rname])) $roomStats[$rname] = 0;
    $roomStats[$rname]++;
}
arsort($roomStats);
$chartLabels = json_encode(array_keys($roomStats));
$chartData = json_encode(array_values($roomStats));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Report Styling */
    .report-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2rem;
        border: 1px solid rgba(59, 130, 246, 0.3);
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
    }
    .report-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .report-title {
        font-size: 1.25rem;
        font-weight: bold;
        color: var(--text-main, #000);
        margin-bottom: 0.25rem;
    }
    .report-subtitle {
        color: var(--text-main, #000);
        font-size: 1rem;
        font-weight: bold;
    }
    
    .report-table-wrapper {
        overflow-x: auto;
    }
    .report-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.85rem;
    }
    .report-table th, .report-table td {
        border: 1px solid var(--border, #000);
        padding: 0.85rem 1.2rem;
        color: var(--text-main, #000);
    }
    .report-table th {
        text-align: center;
        font-weight: bold;
        background-color: var(--sidebar-bg, #f8f9fa);
    }
    .report-table tr:hover td {
        background-color: rgba(59,130,246,0.03);
    }
    .report-table tfoot th {
        background-color: var(--sidebar-bg, var(--border));
        color: var(--primary, var(--primary));
        padding: 1rem 1.5rem;
        font-weight: 700;
        border-top: 2px solid var(--secondary, var(--secondary));
    }
    
    /* PDF Print Styles */
    @media print {
        @page {
            size: A4 landscape;
            margin: 0.8cm;
        }
        
        /* Override global hidden visibility from style.css */
        body * {
            visibility: hidden !important;
            font-family: 'THSarabunNew', 'Sarabun', sans-serif !important;
            background: transparent !important;
            color: black !important;
        }
        
        #printable-report, #printable-report * {
            visibility: visible !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            font-family: 'THSarabunNew', 'Sarabun', sans-serif !important;
            color: black !important;
        }
        
        #printable-report {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            background: white !important;
            color: black !important;
            font-family: 'THSarabunNew', 'Sarabun', sans-serif !important;
        }

        aside, header, .no-print, .no-print * {
            display: none !important;
            visibility: hidden !important;
        }
        
        .report-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            width: 100% !important;
            background: white !important;
        }
        
        .report-table {
            width: 100% !important;
            border: 1px solid #000 !important;
            border-collapse: collapse !important;
        }
        
        .report-table th, .report-table td {
            border: 1px solid #000 !important;
            color: black !important;
            background: white !important;
            padding: 4px 6px !important;
            font-size: 13pt !important;
            line-height: 1.25 !important;
        }
        
        .report-table th {
            background-color: #f2f2f2 !important;
            font-weight: bold !important;
            text-align: center !important;
        }
        .report-title {
            color: black !important;
            font-size: 18pt !important;
            font-weight: bold !important;
            text-align: center !important;
            margin-bottom: 5px !important;
        }
        .report-subtitle {
            color: black !important;
            font-size: 14pt !important;
            text-align: center !important;
            margin-bottom: 15px !important;
        }
    }
    
    /* Advanced Filter Styles */
    .filter-panel {
        background: white;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        margin-bottom: 1.5rem;
        padding: 1.5rem;
    }
    .filter-panel-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .filter-panel-advanced {
        display: none;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
        padding: 1.25rem;
        background: var(--white);
        border-radius: 1.25rem;
        border: 1px solid var(--border);
    }
    .filter-panel-advanced.open {
        display: grid;
    }
    @media (min-width: 640px) {
        .filter-panel-advanced {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 768px) {
        .filter-panel-advanced {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .filter-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        margin-bottom: 0;
    }
    .filter-group label i {
        color: var(--secondary);
    }
    .filter-group input,
    .filter-group select {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        border-radius: 0.75rem;
        border: 1px solid var(--border);
        background: white;
        color: var(--primary);
        outline: none;
        transition: all 0.2s;
        width: 100%;
    }
    .filter-group input:focus,
    .filter-group select:focus {
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .filter-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        background: #2563EB;
        color: white;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    .filter-toggle-btn:hover {
        background: #1D4ED8;
        transform: translateY(-1px);
    }
    .filter-toggle-btn:active {
        transform: translateY(0);
    }
    .filter-toggle-btn .filter-count {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 99px;
        padding: 0.15rem 0.5rem;
        font-size: 0.75rem;
    }
</style>

<div class="flex flex-col gap-6 w-full">
    
    <!-- Controls (No Print) -->
    <div class="flex flex-wrap justify-between items-center gap-4 no-print">
        <h2 class="text-2xl font-bold text-primary flex items-center gap-2 w-full mb-2">
            <i class="fas fa-chart-line text-[var(--secondary)]"></i> รายงานการใช้ห้องประชุม
        </h2>
        
        <form method="GET" action="dashboard.php" class="w-full">
            <input type="hidden" name="view" value="reports">
            <div class="filter-panel">
                <div class="filter-panel-top">
                    <?php 
                        $filterCount = 0;
                        if(!empty($booker)) $filterCount++;
                        if(!empty($roomId)) $filterCount++;
                    ?>
                    <button type="button" class="filter-toggle-btn <?= $filterCount > 0 ? 'active' : '' ?>" onclick="toggleAdvancedFilter()">
                        <i class="fas fa-sliders-h"></i> ตัวกรองเพิ่มเติม
                        <span class="filter-count"><?= $filterCount ?></span>
                    </button>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="filter-toggle-btn" style="background:#059669;">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                        <button type="button" onclick="window.print()" class="filter-toggle-btn" style="background:#2563EB;">
                            <i class="fas fa-file-pdf"></i> ออกรายงาน PDF
                        </button>
                    </div>
                </div>
                
                <div class="filter-panel-advanced">
                    <div class="filter-group">
                        <label><i class="fas fa-user-tie"></i>ชื่อผู้จอง</label>
                        <input type="text" name="booker" placeholder="ชื่อผู้จอง..." value="<?= htmlspecialchars($booker) ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-door-open"></i>ห้องประชุม</label>
                        <select name="room_id">
                            <option value="">ทุกห้อง</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?= $room['id'] ?>" <?= $roomId == $room['id'] ? 'selected' : '' ?>><?= htmlspecialchars($room['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i>ตั้งแต่วันที่</label>
                        <input type="date" name="start_date" value="<?= $startDate ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-check"></i>ถึงวันที่</label>
                        <input type="date" name="end_date" value="<?= $endDate ?>">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Printable Report Container -->
    <div id="printable-report" class="report-card">
        <div class="report-header">
            <h1 class="report-title">รายงานการใช้ห้องประชุม</h1>
            <p class="report-subtitle">ประจำ<?= $displayDate ?></p>
        </div>
        
        <!-- Chart Section -->
        <?php if(count($roomStats) > 0): ?>
        <div class="mb-8 p-6 no-print">
            <h3 class="text-lg font-bold text-primary mb-4 text-center">สถิติการใช้งานห้องประชุม (ครั้ง)</h3>
            <div style="height: 350px; position: relative; margin: 0 auto; max-width: 1100px;">
                <canvas id="roomUsageChart"></canvas>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('roomUsageChart').getContext('2d');
            
            // Process labels for wrapping (multi-line)
            const rawLabels = <?= $chartLabels ?>;
            const multiLineLabels = rawLabels.map(label => {
                // Split by parenthesis if present, or at space
                if (label.includes('(')) {
                    return label.split('(').map((s, i) => i === 0 ? s.trim() : '(' + s.trim());
                }
                if (label.length > 20) {
                    return [label.substring(0, 20), label.substring(20)];
                }
                return label;
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: multiLineLabels,
                    datasets: [{
                        label: 'จำนวนครั้งที่ใช้งาน',
                        data: <?= $chartData ?>,
                        backgroundColor: '#3B82F6',
                        borderColor: '#2563EB',
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { bottom: 40 }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 12,
                            titleFont: { family: 'Sarabun, Outfit', size: 14 },
                            bodyFont: { family: 'Sarabun, Outfit', size: 13 }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { stepSize: 1, font: { family: 'Outfit', size: 12, weight: 'bold' } },
                            grid: { color: 'rgba(148, 163, 184, 0.15)' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: 'Sarabun, Outfit', size: 12, weight: 'bold' },
                                color: 'var(--text-main)',
                                maxRotation: 0,
                                minRotation: 0,
                                padding: 10
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php endif; ?>
        
        <div class="report-table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th style="width: 80px;">วันที่ใช้ห้อง</th>
                        <th style="width: 120px;">ช่วงเวลา</th>
                        <th style="width: 200px;">ห้องประชุม</th>
                        <th>กิจกรรม</th>
                        <th style="width: 60px;">จำนวน<br>(คน)</th>
                        <th style="width: 150px;">หน่วยงาน</th>
                        <th style="width: 100px;">ผู้จอง</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody">
                    <?php if (count($report_data) > 0): ?>
                        <?php foreach($report_data as $index => $row): 
                            $timeStart = strtotime($row['start_time']);
                            $date = date('j', $timeStart) . ' ' . $thai_months_short[date('m', $timeStart)] . ' ' . substr(date('Y', $timeStart) + 543, 2, 2);
                            $time_range = date('H:i', strtotime($row['start_time'])) . '-' . date('H:i', strtotime($row['end_time'])) . ' น.';
                            $room = $row['room_name'] ?? 'ภายนอกสถานที่';
                            if ($row['location']) {
                                $room .= '<br><span style="font-size: 0.75rem;">(' . htmlspecialchars($row['location']) . ')</span>';
                            }
                            $dept = $row['department_name'] ?: $row['user_dept'];
                            $user_name = $row['first_name'] . ' ' . $row['last_name'];
                        ?>
                        <tr>
                            <td style="text-align: center;"><?= $index + 1 ?></td>
                            <td style="text-align: center; white-space: nowrap;"><?= $date ?></td>
                            <td style="text-align: center; white-space: nowrap;"><?= $time_range ?></td>
                            <td><?= $room ?></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td style="text-align: center;"><?= $row['participants_count'] ?></td>
                            <td style="text-align: center;"><?= htmlspecialchars($dept ?? '-') ?></td>
                            <td style="text-align: center;"><?= htmlspecialchars($user_name) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">ไม่พบข้อมูลการใช้งานในช่วงเวลานี้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: right; margin-top: 1rem; font-size: 0.75rem;">
            <?= date('d') ?> <?= $thai_months[date('m')] ?> <?= date('Y') + 543 ?>
        </div>
    </div>
</div>

<script>
    function toggleAdvancedFilter() {
        const panel = document.querySelector('.filter-panel-advanced');
        const btn = document.querySelector('.filter-toggle-btn');
        const isOpen = panel.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        const filterCount = parseInt(document.querySelector('.filter-count').textContent);
        if(filterCount > 0) {
            document.querySelector('.filter-panel-advanced').classList.add('open');
            document.querySelector('.filter-toggle-btn').classList.add('active');
        }
        
        // Init pagination for the report table
        if (document.getElementById('reportTableBody')) {
            new MeetQueuePaginator({
                container: '#reportTableBody',
                itemSelector: 'tr',
                pageSize: 10
            });
        }
    });
</script>
