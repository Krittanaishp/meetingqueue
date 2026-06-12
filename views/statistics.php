<?php
require_once 'api/config.php';
use App\Repository\BookingRepository;

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$repo = new BookingRepository();
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$booker = $_GET['booker'] ?? '';
$roomId = $_GET['room_id'] ?? '';

// Get all rooms first for the dropdown filter and Gantt chart grouping
$db = \App\Core\Database::getInstance()->getConnection();
$rooms = $db->query("SELECT id, name FROM rooms ORDER BY id")->fetchAll();

// Fetch stats filtered by date range, booker, and room
$roomStats = $repo->getRoomUsageStats($startDate, $endDate, $booker, $roomId);
$deptStats = $repo->getDepartmentStats($startDate, $endDate, $booker, $roomId);

// Prepare data for Room Usage (Count)
$roomLabels = [];
$roomCounts = [];
$roomHours = [];
foreach ($roomStats as $stat) {
    $roomLabels[] = $stat['name'];
    $roomCounts[] = (int)$stat['total_bookings'];
    $roomHours[] = round((float)$stat['total_hours'], 1);
}

// Prepare data for Department Stats
$deptLabels = [];
$deptCounts = [];
foreach ($deptStats as $stat) {
    $deptLabels[] = $stat['department_name'];
    $deptCounts[] = (int)$stat['total_bookings'];
}

// Prepare data for Equipment Stats (Filtered by day)
$dayBookings = $repo->getAll([
    'start' => $startDate . ' 00:00:00',
    'end' => $endDate . ' 23:59:59',
    'exclude_status' => 'cancelled',
    'search' => $booker,
    'room_id' => $roomId
]);

$eqStats = [
    'โปรเจกเตอร์' => 0,
    'ทีวี' => 0,
    'คอมพิวเตอร์' => 0,
    'ไมโครโฟน' => 0
];

foreach ($dayBookings as $b) {
    $desc = strtolower($b['description'] ?? '');
    foreach ($eqStats as $key => &$count) {
        if (strpos($desc, $key) !== false || strpos($b['title'] ?? '', $key) !== false) {
            $count++;
        }
    }
}
$eqLabels = array_keys($eqStats);
$eqCounts = array_values($eqStats);

// Prepare data for Daily Gantt Chart (Selected Date)
$todayBookings = array_filter($dayBookings, function($b) {
    return $b['status'] !== 'rejected' && $b['status'] !== 'cancelled';
});

// Group by room
$roomsForGantt = [];
foreach ($rooms as $room) {
    $roomsForGantt[$room['name']] = [];
}

// Convert times to decimal hours
foreach ($todayBookings as $b) {
    $roomName = $b['room_name'] ?? 'ภายนอก';
    if (!isset($roomsForGantt[$roomName])) {
        $roomsForGantt[$roomName] = [];
    }
    
    $startStr = date('H:i', strtotime($b['start_time']));
    $endStr = date('H:i', strtotime($b['end_time']));
    
    $startParts = explode(':', $startStr);
    $endParts = explode(':', $endStr);
    
    $startVal = (int)$startParts[0] + ((int)$startParts[1] / 60);
    $endVal = (int)$endParts[0] + ((int)$endParts[1] / 60);
    
    $roomsForGantt[$roomName][] = [
        'title' => $b['title'],
        'start' => $startVal,
        'end' => $endVal,
        'startStr' => $startStr,
        'endStr' => $endStr,
        'status' => $b['status']
    ];
}

$startHour = 8;
$endHour = 18;
$totalHours = $endHour - $startHour;

// Format display date
$thai_months = [
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
?>

<style>
    /* Prevent the Christian year from showing when selected/highlighted */
    .numInput.cur-year::selection {
        background: transparent !important;
        color: transparent !important;
    }
    .numInput.cur-year::-moz-selection {
        background: transparent !important;
        color: transparent !important;
    }
    .be-year-display {
        user-select: none;
    }

    /* Advanced Filter Styles */
    .filter-panel {
        background: white;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        margin-bottom: 0.5rem;
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

<div class="w-full flex flex-col gap-6 animate-fade">
    <!-- Header Section -->
    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-6 pb-2">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 rounded-3xl bg-blue-600 flex items-center justify-center text-white shadow-xl shadow-blue-600/20 border-4 border-white">
                <i class="fas fa-chart-pie text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-primary leading-relaxed py-1 tracking-tight">สถิติการใช้งานห้องประชุม</h2>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="w-2 h-10 rounded-full bg-[var(--secondary)]"></span>
                    <p class="text-sm font-bold text-text-muted">วิเคราะห์และติดตามข้อมูลการจองห้องประจำวัน</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls (No Print) -->
    <form method="GET" action="dashboard.php" class="w-full no-print">
        <input type="hidden" name="view" value="statistics">
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
                    <button type="button" onclick="window.location.href='?view=statistics'" class="filter-toggle-btn" style="background:#4b5563;">
                        <i class="fas fa-history"></i> วันนี้
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-[1.5rem] p-8 shadow-sm border border-slate-200/60 flex items-center gap-6 transition-all hover:shadow-md hover:border-blue-400/40">
            <div class="w-16 h-16 rounded-2xl bg-[var(--secondary)]/15 flex items-center justify-center text-primary text-2xl shadow-inner">
                <i class="fas fa-handshake"></i>
            </div>
            <div>
                <div class="text-[0.65rem] font-black text-text-muted uppercase tracking-widest mb-1">จำนวนการจองประจำวัน</div>
                <div class="text-3xl font-black text-primary leading-none"><?= array_sum($roomCounts) ?> <span class="text-sm font-bold text-text-muted ml-1">ครั้ง</span></div>
            </div>
        </div>
        
        <div class="bg-white rounded-[1.5rem] p-8 shadow-sm border border-slate-200/60 flex items-center gap-6 transition-all hover:shadow-md hover:border-blue-400/40">
            <div class="w-16 h-16 rounded-2xl bg-[var(--secondary)]/15 flex items-center justify-center text-primary text-2xl shadow-inner">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="text-[0.65rem] font-black text-text-muted uppercase tracking-widest mb-1">เวลาใช้งานรวมประจำวัน</div>
                <div class="text-3xl font-black text-primary leading-none"><?= array_sum($roomHours) ?> <span class="text-sm font-bold text-text-muted ml-1">ชั่วโมง</span></div>
            </div>
        </div>

        <div class="bg-white rounded-[1.5rem] p-8 shadow-sm border border-slate-200/60 flex items-center gap-6 transition-all hover:shadow-md hover:border-blue-400/40">
            <div class="w-16 h-16 rounded-2xl bg-[var(--secondary)]/15 flex items-center justify-center text-primary text-2xl shadow-inner">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <div class="text-[0.65rem] font-black text-text-muted uppercase tracking-widest mb-1">หน่วยงานที่ใช้งาน</div>
                <div class="text-3xl font-black text-primary leading-none"><?= count($deptCounts) ?> <span class="text-sm font-bold text-text-muted ml-1">แผนก</span></div>
            </div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Chart 0: Daily Gantt Chart -->
        <div class="bg-white rounded-[1.5rem] p-8 shadow-sm border border-slate-200/60 lg:col-span-2 overflow-hidden">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
                <div class="flex items-center gap-4 p-2 pl-0">
                    <div class="w-12 h-14 rounded-2xl bg-[var(--secondary)]/10 flex items-center justify-center text-[var(--secondary)] text-xl shadow-sm border border-blue-400/20">
                        <i class="fas fa-stream"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-primary leading-none">ตารางการใช้ห้องประชุม</h3>
                        <p class="text-[0.65rem] font-bold text-text-muted mt-1.5 flex items-center gap-1">
                            <i class="far fa-calendar-alt opacity-50"></i> <?= $displayDate ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-8 px-5 py-3 bg-slate-50 rounded-[1.25rem] border border-slate-200/60 shadow-inner-light">
                    <div class="flex items-center gap-2.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-[#10b981] shadow-sm border border-white"></span>
                        <span class="text-[0.9rem] font-black text-primary uppercase tracking-wider">อนุมัติแล้ว</span>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-[#f59e0b] shadow-sm border border-white"></span>
                        <span class="text-[0.9rem] font-black text-primary uppercase tracking-wider">รออนุมัติ</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar select-none">
                <div id="ganttTimeline" class="relative pb-6" style="--sidebar-w: 240px; min-w: calc(var(--sidebar-w) + 700px);">
                    <!-- Sidebar Header & Resizer -->
                    <div class="absolute left-0 top-0 z-30 h-10 border-b border-r border-slate-200/60 bg-white rounded-tl-xl flex items-center px-6" style="width: var(--sidebar-w);">
                        <span class="text-[0.65rem] font-black text-text-muted uppercase tracking-widest flex items-center gap-2">
                            <i class="fas fa-door-open opacity-50"></i> รายชื่อห้องประชุม
                        </span>
                    </div>
                    
                    <!-- Draggable Handle -->
                    <div id="sidebarResizer" class="absolute top-0 bottom-0 z-40 w-1.5 cursor-col-resize hover:bg-[var(--secondary)]/40 transition-colors group" style="left: calc(var(--sidebar-w) - 3px);">
                        <div class="absolute inset-y-0 left-1/2 w-px bg-slate-200 group-hover:bg-[var(--secondary)]"></div>
                    </div>

                    <!-- Timeline Header -->
                    <div class="flex border-b border-slate-200 pb-3 mb-6" style="margin-left: var(--sidebar-w);">
                        <?php for($h = $startHour; $h <= $endHour; $h++): ?>
                            <div class="flex-1 text-center relative">
                                <span class="text-[0.7rem] font-black text-text-muted"><?= sprintf('%02d:00', $h) ?></span>
                                <div class="absolute top-full left-1/2 w-px h-2 bg-slate-200/60"></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Current Time Line Indicator -->
                    <?php 
                        $nowHour = (int)date('H');
                        $nowMin = (int)date('i');
                        $nowDecimal = $nowHour + ($nowMin / 60);
                        if ($nowDecimal >= $startHour && $nowDecimal <= $endHour):
                            $nowPct = (($nowDecimal - $startHour) / $totalHours) * 100;
                    ?>
                        <div class="absolute top-0 bottom-0 z-10 pointer-events-none" style="left: calc(var(--sidebar-w) + (100% - var(--sidebar-w)) * <?= $nowPct / 100 ?>);">
                            <div class="w-px h-full bg-red-400 opacity-50 dashed-line"></div>
                            <div class="absolute -top-1 -left-1 w-2 h-2 rounded-full bg-red-500 shadow-md"></div>
                        </div>
                    <?php endif; ?>

                    <!-- Rooms Rows -->
                    <div class="space-y-3">
                        <?php foreach($roomsForGantt as $room => $bookings): ?>
                        <div class="flex items-center group">
                            <div class="font-bold text-primary text-sm group-hover:text-[var(--secondary)] flex items-center gap-3 pr-6 overflow-hidden" title="<?= $room ?>" style="width: var(--sidebar-w); flex-shrink: 0;">
                                <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-text-muted group-hover:bg-[var(--secondary)]/20 group-hover:text-[var(--secondary)] transition-all flex-shrink-0">
                                    <i class="fas fa-door-open text-[0.8rem]"></i>
                                </div>
                                <span class="flex-1 sidebar-text-target truncate"><?= $room ?></span>
                            </div>
                            <div class="flex-1 h-12 bg-slate-50 rounded-xl relative border border-slate-200/30 group-hover:bg-white group-hover:border-blue-400/40 transition-all shadow-inner-light">
                                <!-- Grid Lines -->
                                <div class="absolute inset-0 flex pointer-events-none">
                                    <?php for($h = $startHour; $h < $endHour; $h++): ?>
                                        <div class="flex-1 border-l border-slate-200/20 h-full"></div>
                                    <?php endfor; ?>
                                    <div class="border-l border-slate-200/20 h-full"></div>
                                </div>
                                
                                <?php foreach($bookings as $b): 
                                    $s = max($startHour, $b['start']);
                                    $e = min($endHour, $b['end']);
                                    if ($e <= $s) continue;
                                    
                                    $leftPct = (($s - $startHour) / $totalHours) * 100;
                                    $widthPct = (($e - $s) / $totalHours) * 100;
                                    
                                    $statusColors = [
                                        'approved' => 'bg-[#10b981] border-[#059669]',
                                        'pending' => 'bg-[#f59e0b] border-[#d97706]',
                                        'completed' => 'bg-blue-600 border-blue-800',
                                    ];
                                    $colorClass = $statusColors[$b['status']] ?? 'bg-[var(--secondary)] border-[#C2A38A]';
                                ?>
                                <div class="gantt-bar absolute top-2 bottom-2 rounded-lg border flex items-center px-3 shadow-sm hover:shadow-md hover:z-20 hover:-translate-y-0.5 transition-all cursor-pointer <?= $colorClass ?>" 
                                     style="left: <?= $leftPct ?>%; width: <?= $widthPct ?>%;">
                                    <span class="text-white text-[0.65rem] font-bold truncate">
                                        <?= htmlspecialchars($b['title']) ?>
                                    </span>
                                    
                                    <!-- Tooltip Card (Premium) -->
                                    <div class="gantt-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-3 hidden w-64 bg-white/95 backdrop-blur-md text-primary p-5 rounded-[1.5rem] shadow-2xl border border-blue-400/30 z-30 pointer-events-none scale-95 opacity-0 transition-all duration-300">
                                        <div class="font-black mb-3 text-sm leading-tight"><?= htmlspecialchars($b['title']) ?></div>
                                        <div class="space-y-2">
                                            <div class="text-text-muted font-bold text-[0.7rem] flex items-center gap-2">
                                                <i class="far fa-clock text-[var(--secondary)]"></i> <?= $b['startStr'] ?> - <?= $b['endStr'] ?>
                                            </div>
                                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200/50">
                                                <div class="inline-block px-3 py-1 bg-slate-50 rounded-full border border-slate-200 text-[0.6rem] uppercase font-black tracking-widest text-text-muted">
                                                    <?= $b['status'] ?>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- triangle -->
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-[10px] border-transparent border-t-white/95"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($roomsForGantt)): ?>
                            <div class="text-center py-12 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200 text-text-muted font-bold">
                                <i class="fas fa-inbox text-3xl mb-3 block opacity-20"></i>
                                ไม่พบข้อมูลการใช้งานในวันนี้
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart 1: Room Usage Count (Bar) -->
        <div class="bg-white rounded-[1.5rem] p-10 shadow-sm border border-slate-200/60 transition-all hover:shadow-md overflow-hidden">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 rounded-xl bg-[var(--secondary)]/10 flex items-center justify-center text-[var(--secondary)]">
                    <i class="fas fa-chart-column"></i>
                </div>
                <h3 class="text-lg font-black text-primary">ความถี่การใช้งานรายห้อง</h3>
            </div>
            <div class="relative h-[320px]">
                <canvas id="roomCountChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Room Usage Hours (Doughnut) -->
        <div class="bg-white rounded-[1.5rem] p-10 shadow-sm border border-slate-200/60 transition-all hover:shadow-md overflow-hidden">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 rounded-xl bg-[var(--secondary)]/10 flex items-center justify-center text-[var(--secondary)]">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h3 class="text-lg font-black text-primary">สัดส่วนเวลาการใช้งานรายห้อง</h3>
            </div>
            <div class="relative h-[320px]">
                <canvas id="roomHoursChart"></canvas>
            </div>
        </div>

        <!-- Chart 3: Department Usage (Bar) -->
        <div class="bg-white rounded-[1.5rem] p-10 shadow-sm border border-slate-200/60 transition-all hover:shadow-md overflow-hidden">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 rounded-xl bg-[var(--secondary)]/10 flex items-center justify-center text-[var(--secondary)]">
                    <i class="fas fa-building-user"></i>
                </div>
                <h3 class="text-lg font-black text-primary">สถิติการใช้งานแยกตามหน่วยงาน</h3>
            </div>
            <div class="relative h-[320px]">
                <canvas id="deptChart"></canvas>
            </div>
        </div>

        <!-- Chart 4: Equipment Usage (Bar) -->
        <div class="bg-white rounded-[1.5rem] p-10 shadow-sm border border-slate-200/60 transition-all hover:shadow-md overflow-hidden">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 rounded-xl bg-[var(--secondary)]/10 flex items-center justify-center text-[var(--secondary)]">
                    <i class="fas fa-box-archive"></i>
                </div>
                <h3 class="text-lg font-black text-primary">สถิติการยืมอุปกรณ์</h3>
            </div>
            <div class="relative h-[320px]">
                <canvas id="equipmentChart"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
/* CSS to handle tooltip hover correctly without clipping */
.gantt-bar:hover .gantt-tooltip { 
    display: block; 
    animation: slideUp 0.3s forwards;
}

@keyframes slideUp {
    from { transform: translate(-50%, 10px); opacity: 0; scale: 0.95; }
    to { transform: translate(-50%, 0); opacity: 1; scale: 1; }
}

.dashed-line {
    background-image: linear-gradient(to bottom, #f87171 50%, transparent 50%);
    background-size: 1px 10px;
    background-repeat: repeat-y;
}

.shadow-inner-light {
    box-shadow: inset 0 2px 4px 0 rgba(15, 23, 42, 0.02);
}

.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: var(--sidebar-bg);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--secondary);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleAdvancedFilter() {
    const panel = document.querySelector('.filter-panel-advanced');
    const btn = document.querySelector('.filter-toggle-btn');
    const isOpen = panel.classList.toggle('open');
    btn.classList.toggle('active', isOpen);
}

document.addEventListener('DOMContentLoaded', function() {
    // Open filter panel if filters are active
    const filterCount = parseInt(document.querySelector('.filter-count')?.textContent || 0);
    if(filterCount > 0) {
        document.querySelector('.filter-panel-advanced')?.classList.add('open');
        document.querySelector('.filter-toggle-btn')?.classList.add('active');
    }

    // Shared Colors (Professional Blue)
    const professionalColors = [
        '#2563EB', '#3B82F6', '#60A5FA', '#93C5FD', '#1D4ED8', '#1E40AF', '#64748B', '#94A3B8'
    ];

    // 1. Room Count Chart (Bar)
    const ctxRoomCount = document.getElementById('roomCountChart').getContext('2d');
    
    // Process labels for wrapping (multi-line) like in reports view
    const rawLabels = <?= json_encode($roomLabels) ?>;
    const multiLineLabels = rawLabels.map(label => {
        if (label.includes('(')) {
            return label.split('(').map((s, i) => i === 0 ? s.trim() : '(' + s.trim());
        }
        if (label.length > 15) {
            return [label.substring(0, 15), label.substring(15)];
        }
        return label;
    });

    new Chart(ctxRoomCount, {
        type: 'bar',
        data: {
            labels: multiLineLabels,
            datasets: [{
                label: 'จำนวนครั้งที่ใช้งาน',
                data: <?= json_encode($roomCounts) ?>,
                backgroundColor: '#3B82F6',
                hoverBackgroundColor: '#2563EB',
                borderColor: '#2563EB',
                borderWidth: 1,
                borderRadius: 8,
                barThickness: 'flex',
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { bottom: 20 }
            },
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 13, weight: 'bold', family: 'Sarabun, Outfit' },
                    bodyFont: { size: 12, family: 'Sarabun, Outfit' },
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: false
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { 
                        stepSize: 1,
                        font: { family: 'Outfit', weight: 'bold', size: 11 }, 
                        color: 'var(--text-muted)' 
                    }
                },
                x: { 
                    grid: { display: false },
                    ticks: { 
                        font: { family: 'Sarabun, Outfit', size: 9, weight: 'bold' }, 
                        color: 'var(--text-muted)',
                        maxRotation: 0,
                        minRotation: 0
                    } 
                }
            }
        }
    });

    // 2. Room Hours Chart (Doughnut)
    const ctxRoomHours = document.getElementById('roomHoursChart').getContext('2d');
    new Chart(ctxRoomHours, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($roomLabels) ?>,
            datasets: [{
                data: <?= json_encode($roomHours) ?>,
                backgroundColor: professionalColors.slice(0, <?= count($roomLabels) ?>),
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 30
            },
            plugins: {
                legend: { 
                    position: 'right', 
                    labels: { 
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 15,
                        font: { family: 'Outfit, Sarabun', size: 10, weight: 'bold' },
                        color: 'var(--text-main)'
                    } 
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    cornerRadius: 12,
                    bodyFont: { family: 'Outfit' }
                }
            },
            cutout: '75%'
        }
    });

    function closeUsageStats() {
        document.getElementById('usageStatsModal').style.display = 'none';
    }

    // Draggable Sidebar Implementation
    (function() {
        let isResizing = false;
        const resizer = document.getElementById('sidebarResizer');
        const timeline = document.getElementById('ganttTimeline');

        if (!resizer) return;

        resizer.addEventListener('mousedown', function(e) {
            isResizing = true;
            document.body.style.cursor = 'col-resize';
            document.body.classList.add('select-none');
        });

        document.addEventListener('mousemove', function(e) {
            if (!isResizing) return;
            
            const rect = timeline.getBoundingClientRect();
            let newWidth = e.clientX - rect.left;
            
            // Constraints
            if (newWidth < 150) newWidth = 150;
            if (newWidth > 600) newWidth = 600;
            
            timeline.style.setProperty('--sidebar-w', newWidth + 'px');
            
            // Dynamic truncation handling
            const textElements = document.querySelectorAll('.sidebar-text-target');
            textElements.forEach(span => {
                if (newWidth > 400) {
                    span.classList.remove('truncate');
                    span.style.whiteSpace = 'normal';
                } else {
                    span.classList.add('truncate');
                    span.style.whiteSpace = 'nowrap';
                }
            });
        });

        document.addEventListener('mouseup', function() {
            if (isResizing) {
                isResizing = false;
                document.body.style.cursor = 'default';
                document.body.classList.remove('select-none');
            }
        });
    })();

    // 3. Department Chart (Horizontal Bar)
    const ctxDept = document.getElementById('deptChart').getContext('2d');
    new Chart(ctxDept, {
        type: 'bar',
        data: {
            labels: <?= json_encode($deptLabels) ?>,
            datasets: [{
                label: 'จำนวนครั้งที่ใช้งาน',
                data: <?= json_encode($deptCounts) ?>,
                backgroundColor: '#60A5FA',
                hoverBackgroundColor: '#2563EB',
                borderRadius: 8,
                barThickness: 20
            }]
        },
        options: {
            indexAxis: 'y', // Makes it horizontal
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    cornerRadius: 12
                }
            },
            scales: {
                x: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { font: { family: 'Outfit', weight: 'bold' }, color: '#94A3B8' }
                },
                y: { 
                    grid: { display: false },
                    ticks: { font: { family: 'Outfit, Sarabun', size: 11, weight: 'bold' }, color: 'var(--text-main)' }
                }
            }
        }
    });

    // 4. Equipment Chart (Bar)
    const ctxEq = document.getElementById('equipmentChart').getContext('2d');
    new Chart(ctxEq, {
        type: 'bar',
        data: {
            labels: <?= json_encode($eqLabels) ?>,
            datasets: [{
                label: 'จำนวนครั้งที่ถูกยืม',
                data: <?= json_encode($eqCounts) ?>,
                backgroundColor: ['#2563EB', '#3B82F6', '#60A5FA', '#93C5FD'],
                borderRadius: 12,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    cornerRadius: 12
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(148, 163, 184, 0.15)' },
                    ticks: { font: { family: 'Outfit', weight: 'bold' }, color: '#94A3B8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Outfit, Sarabun', weight: 'bold' }, color: 'var(--text-main)' }
                }
            }
        }
    });
});
</script>
