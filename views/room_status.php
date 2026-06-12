<?php
require_once 'api/config.php';
use App\Repository\RoomRepository;
use App\Repository\BookingRepository;

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$roomRepo = new RoomRepository();
$bookingRepo = new BookingRepository();

$rooms = $roomRepo->getAll();
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

$today_bookings = $bookingRepo->getAll([
    'start' => $today_start,
    'end' => $today_end,
    'status' => 'approved'
]);

// Group bookings by room
$room_schedules = [];
foreach ($rooms as $room) {
    $room_schedules[$room['id']] = [];
}

foreach ($today_bookings as $b) {
    if ($b['room_id']) {
        $room_schedules[$b['room_id']][] = $b;
    }
}

$current_time = time();
?>

<!-- Usage Stats Popup -->
<div id="usageStatsModal" style="display:none;position:fixed;inset:0;z-index:999;background:rgba(15,23,42,0.25);backdrop-filter:blur(4px);" onclick="if(event.target===this)closeUsageStats()">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:1.5rem;padding:2rem;min-width:340px;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(15,23,42,0.12);max-height:80vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <h3 style="font-size:1.1rem;font-weight:800;color:var(--primary);margin:0;display:flex;align-items:center;gap:0.5rem;">
                <i class="fas fa-chart-bar" style="color:var(--secondary);"></i> สถิติการใช้งานวันนี้
            </h3>
            <button onclick="closeUsageStats()" style="border:none;background:var(--sidebar-bg);width:32px;height:32px;border-radius:50%;cursor:pointer;color:var(--text-muted);font-size:0.85rem;">✕</button>
        </div>
        <div id="usageStatsContent" style="display:flex;flex-direction:column;gap:0.75rem;">
            <div style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:0.85rem;">กำลังโหลด...</div>
        </div>
    </div>
</div>

<div class="flex flex-col gap-6 w-full pb-10">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <!-- Title -->
        <h2 class="text-2xl font-bold text-primary flex items-center gap-2" style="flex-shrink:0;">
            <i class="fas fa-desktop text-secondary"></i> สถานะห้องประชุมปัจจุบัน
        </h2>

        <!-- Room Search Dropdown (center, fills space) -->
        <div style="flex:1;min-width:200px;max-width:420px;position:relative;" id="roomSearchWrap">
            <div style="display:flex;align-items:center;background:white;border:1.5px solid var(--border);border-radius:0.875rem;padding:0 0.85rem;transition:all 0.2s;" id="roomSearchBox">
                <i class="fas fa-search" style="color:var(--text-muted);font-size:0.82rem;flex-shrink:0;"></i>
                <input type="text" id="roomSearchInput" placeholder="ค้นหาห้องประชุม..." autocomplete="off"
                    style="flex:1;border:none;background:transparent;padding:0.6rem 0.6rem;font-size:0.88rem;font-weight:600;color:var(--text-main);font-family:inherit;outline:none;"
                    onfocus="document.getElementById('roomSearchBox').style.borderColor='var(--secondary)';openRoomDropdown()"
                    onblur="setTimeout(()=>{document.getElementById('roomSearchBox').style.borderColor='var(--border)';closeRoomDropdown()},150)">
                <button id="roomSearchClearBtn" onclick="clearRoomSearch()" style="display:none;border:none;background:var(--border);border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:0.6rem;color:var(--text-muted);align-items:center;justify-content:center;flex-shrink:0;">✕</button>
            </div>
            <div id="roomDropdownList" style="display:none;position:absolute;top:calc(100%+5px);left:0;right:0;background:white;border:1.5px solid var(--secondary);border-radius:0.75rem;box-shadow:0 8px 24px rgba(15,23,42,0.08);z-index:100;max-height:220px;overflow-y:auto;scrollbar-width:thin;">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Clock Button -->
        <button onclick="openUsageStats()" title="คลิกเพื่อดูสถิติการใช้งาน" style="
            display:flex;align-items:center;gap:0.5rem;
            background:white;border:1.5px solid var(--border);border-radius:0.875rem;
            padding:0.55rem 1rem;cursor:pointer;transition:all 0.2s;flex-shrink:0;
            box-shadow:0 2px 8px rgba(15,23,42,0.04);
        " onmouseover="this.style.borderColor='var(--secondary)';this.style.background='var(--sidebar-bg)'" onmouseout="this.style.borderColor='var(--border)';this.style.background='white'">
            <i class="fas fa-clock" style="color:var(--secondary);font-size:0.85rem;"></i>
            <span id="current-time-display" style="font-size:1rem;font-weight:800;color:var(--primary);font-variant-numeric:tabular-nums;min-width:72px;display:inline-block;text-align:center;">--:--:--</span>
            <i class="fas fa-chart-bar" style="color:var(--text-muted);font-size:0.75rem;"></i>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" style="align-items: stretch;">
        <?php foreach ($rooms as $room):
            $schedules = $room_schedules[$room['id']] ?? [];
            usort($schedules, function($a, $b) {
                return strtotime($a['start_time']) - strtotime($b['start_time']);
            });

            $current_meeting = null;
            $next_meeting    = null;

            foreach ($schedules as $s) {
                $start = strtotime($s['start_time']);
                $end   = strtotime($s['end_time']);
                if ($current_time >= $start && $current_time <= $end) {
                    $current_meeting = $s;
                    break;
                } elseif ($current_time < $start && !$next_meeting) {
                    $next_meeting = $s;
                }
            }

            $is_active  = $current_meeting !== null;
            $has_next   = $next_meeting !== null;

            if ($is_active) {
                $status_label  = 'กำลังประชุมอยู่';
                $status_color  = '#dc2626'; // Red
                $status_bg     = '#fee2e2';
                $status_border = '#fca5a5';
                $inner_bg      = '#fff5f5';
                $status_icon   = '🔴';
            } elseif ($has_next) {
                $status_label  = 'การประชุมที่กำลังจะมาถึง';
                $status_color  = '#d97706'; // Yellow/Orange
                $status_bg     = '#fef3c7';
                $status_border = '#fde68a';
                $inner_bg      = '#fffbeb';
                $status_icon   = '🟡';
            } else {
                $status_label  = 'ห้องว่าง';
                $status_color  = '#16a34a'; // Green
                $status_bg     = '#dcfce7';
                $status_border = '#bbf7d0';
                $inner_bg      = '#f0fdf4';
                $status_icon   = '🟢';
            }
        ?>
        <div data-room-id="<?= $room['id'] ?>" class="room-status-card <?= $is_active ? 'status-active' : ($has_next ? 'status-upcoming' : 'status-available') ?>" style="
            --status-color: <?= $status_color ?>;
            --status-bg: <?= $status_bg ?>;
            --status-border: <?= $status_border ?>;
            --inner-bg: <?= $inner_bg ?>;
            display: flex;
            flex-direction: column;
            border-radius: 1.5rem;
            border: 2px solid var(--status-border);
            background: var(--card, #ffffff);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            overflow: hidden;
            transition: box-shadow 0.2s, border-color 0.2s;
        " onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.04)'">

            <!-- Room Name Header — fixed height, text clamped -->
            <div style="
                padding: 0.6rem 1.25rem 0.75rem;
                min-height: 80px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-bottom: 1px solid var(--status-border);
                background: var(--inner-bg);
                opacity: 0.9;
                overflow: visible;
            ">
                <h3 style="
                    font-size: 0.88rem;
                    font-weight: 800;
                    color: var(--text-main, var(--text-main));
                    text-align: center;
                    line-height: 2;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                    margin: 0;
                    width: 100%;
                    padding-top: 0.35rem;
                "><?= htmlspecialchars($room['name']) ?></h3>
            </div>

            <!-- Content Area — fixed height -->
            <div style="
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                background: var(--inner-bg);
                min-height: 130px;
                text-align: center;
            ">
                <?php if ($is_active): ?>
                    <div style="display:flex;align-items:center;justify-content:center;gap:5px;font-size:0.85rem;font-weight:800;color:var(--status-color);margin-bottom:0.4rem;text-align:center;">
                        <span style="width:7px;height:7px;border-radius:50%;background:var(--status-color);animation:pulse 1.5s infinite;display:inline-block;flex-shrink:0;"></span>
                        <span style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars($current_meeting['title']) ?></span>
                    </div>
                    <p style="font-size:0.75rem;font-weight:700;color:var(--text-main, var(--text-main));margin:0 0 0.2rem;text-align:center;">
                        <?= htmlspecialchars(trim($current_meeting['first_name'] . ' ' . $current_meeting['last_name'])) ?>
                    </p>
                    <p style="font-size:0.7rem;font-weight:600;color:var(--text-muted, var(--text-muted));margin:0 0 0.75rem;text-align:center;">
                        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($current_meeting['phone'] ?: '-') ?>
                    </p>
                    <div style="font-size:1.3rem;font-weight:900;color:var(--status-color);letter-spacing:0.02em;text-align:center;">
                        <?= date('H:i', strtotime($current_meeting['start_time'])) ?> น. - <?= date('H:i', strtotime($current_meeting['end_time'])) ?> น.
                    </div>
                <?php elseif ($has_next): ?>
                    <div style="font-size:0.85rem;font-weight:800;color:var(--status-color);margin-bottom:0.4rem;text-align:center;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($next_meeting['title']) ?>
                    </div>
                    <p style="font-size:0.75rem;font-weight:700;color:var(--text-main, var(--text-main));margin:0 0 0.2rem;text-align:center;">
                        <?= htmlspecialchars(trim($next_meeting['first_name'] . ' ' . $next_meeting['last_name'])) ?>
                    </p>
                    <p style="font-size:0.7rem;font-weight:600;color:var(--text-muted, var(--text-muted));margin:0 0 0.75rem;text-align:center;">
                        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($next_meeting['phone'] ?: '-') ?>
                    </p>
                    <div style="font-size:1.3rem;font-weight:900;color:var(--status-color);letter-spacing:0.02em;text-align:center;">
                        <?= date('H:i', strtotime($next_meeting['start_time'])) ?> น. - <?= date('H:i', strtotime($next_meeting['end_time'])) ?> น.
                    </div>
                <?php else: ?>
                    <div style="opacity:0.3;display:flex;flex-direction:column;align-items:center;gap:0.4rem;">
                        <i class="fas fa-calendar-check" style="font-size:2rem;color:var(--text-main, var(--text-main));"></i>
                        <p style="font-size:0.8rem;font-weight:700;color:var(--text-main, var(--text-main));margin:0;">ไม่มีการประชุม</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Status Badge — always at bottom, fixed height -->
            <div style="
                padding: 0.65rem 1rem;
                text-align: center;
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: var(--status-color);
                background: var(--status-bg);
                border-top: 1px solid var(--status-border);
            ">
                <?= $status_icon . ' ' . $status_label ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Sync server time with client time
const serverTimeAtLoad = <?= time() * 1000 ?>;
const clientTimeAtLoad = new Date().getTime();
const serverOffset = serverTimeAtLoad - clientTimeAtLoad;

function updateCountdowns() {
    const timers = document.querySelectorAll('.countdown-timer');
    const now = new Date().getTime() + serverOffset;
    
    const clockDisplay = document.getElementById('current-time-display');
    if (clockDisplay) {
        clockDisplay.innerText = new Date(now).toLocaleTimeString('th-TH', { 
            hour: '2-digit', minute: '2-digit' 
        }) + ' น.';
    }

    timers.forEach(timer => {
        // Replace space with T for ISO format to ensure cross-browser compatibility
        const targetStr = timer.dataset.target.replace(' ', 'T');
        const targetDate = new Date(targetStr).getTime();
        const distance = targetDate - now;
        
        if (distance < 0) {
            timer.innerHTML = "00:00";
            if (timer.dataset.reloaded !== "true") {
                timer.dataset.reloaded = "true";
                setTimeout(() => location.reload(), 3000);
            }
            return;
        }
        
        const hours = Math.floor(distance / (1000 * 60 * 60));
        const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        const hStr = hours.toString().padStart(2, '0');
        const mStr = mins.toString().padStart(2, '0');
        const sStr = seconds.toString().padStart(2, '0');
        
        timer.innerHTML = `${hStr}:${mStr}`;
    });
}

setInterval(updateCountdowns, 1000);
updateCountdowns();

// ── Room Search Dropdown ──────────────────────────────────────────────────
const roomData = <?php
    $roomList = array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name']], $rooms);
    echo json_encode($roomList);
?>;

function openRoomDropdown() {
    renderRoomDropdown(document.getElementById('roomSearchInput').value);
    document.getElementById('roomDropdownList').style.display = 'block';
}

function closeRoomDropdown() {
    document.getElementById('roomDropdownList').style.display = 'none';
}

function renderRoomDropdown(query) {
    const list = document.getElementById('roomDropdownList');
    const q = query.toLowerCase().trim();
    const matches = q ? roomData.filter(r => r.name.toLowerCase().includes(q)) : roomData;

    if (matches.length === 0) {
        list.innerHTML = '<div style="padding:0.75rem 1rem;font-size:0.82rem;color:var(--text-muted);font-weight:600;text-align:center;">ไม่พบห้องประชุม</div>';
        return;
    }

    list.innerHTML = matches.map(r => {
        const hi = q ? r.name.replace(new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi'), '<b style="color:var(--primary);">$1</b>') : r.name;
        return `<div onmousedown="scrollToRoom(${r.id})" style="padding:0.6rem 1rem;font-size:0.85rem;font-weight:600;color:var(--text-main);cursor:pointer;transition:background 0.15s;display:flex;align-items:center;gap:0.5rem;"
            onmouseover="this.style.background='var(--sidebar-bg)'" onmouseout="this.style.background=''"
        ><i class="fas fa-door-open" style="color:var(--secondary);font-size:0.75rem;flex-shrink:0;"></i><span>${hi}</span></div>`;
    }).join('');
}

function scrollToRoom(id) {
    const card = document.querySelector(`[data-room-id="${id}"]`);
    if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.style.outline = '3px solid var(--secondary)';
        card.style.transition = 'outline 0.3s';
        setTimeout(() => card.style.outline = '', 2000);
    }
    document.getElementById('roomSearchInput').value = roomData.find(r => r.id == id)?.name || '';
    document.getElementById('roomSearchClearBtn').style.display = 'flex';
    closeRoomDropdown();
}

function clearRoomSearch() {
    document.getElementById('roomSearchInput').value = '';
    document.getElementById('roomSearchClearBtn').style.display = 'none';
    document.getElementById('roomDropdownList').style.display = 'none';
}

document.getElementById('roomSearchInput').addEventListener('input', function() {
    const v = this.value;
    document.getElementById('roomSearchClearBtn').style.display = v ? 'flex' : 'none';
    renderRoomDropdown(v);
    document.getElementById('roomDropdownList').style.display = 'block';
});

// ── Usage Stats Popup ─────────────────────────────────────────────────────
function closeUsageStats() {
    document.getElementById('usageStatsModal').style.display = 'none';
}

async function openUsageStats() {
    document.getElementById('usageStatsModal').style.display = 'block';
    const el = document.getElementById('usageStatsContent');
    el.innerHTML = '<div style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:0.85rem;">กำลังโหลด...</div>';

    try {
        const res = await fetch('api/bookings.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        const today = new Date().toISOString().slice(0, 10);
        const todayBookings = data.bookings.filter(b => b.start_time && b.start_time.startsWith(today));
        const total = todayBookings.length;
        const pending = todayBookings.filter(b => b.status === 'pending').length;
        const approved = todayBookings.filter(b => b.status === 'approved').length;
        const rejected = todayBookings.filter(b => b.status === 'rejected').length;

        // Top rooms today
        const roomCount = {};
        todayBookings.forEach(b => { if (b.room_name) roomCount[b.room_name] = (roomCount[b.room_name] || 0) + 1; });
        const topRooms = Object.entries(roomCount).sort((a,b) => b[1]-a[1]).slice(0, 3);

        const statBox = (icon, label, val, color) => `
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;background:var(--sidebar-bg);border-radius:0.75rem;border:1px solid var(--border);">
                <div style="width:36px;height:36px;border-radius:0.6rem;background:${color}20;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="${icon}" style="color:${color};font-size:0.9rem;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:0.68rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.05em;">${label}</div>
                    <div style="font-size:1.1rem;font-weight:900;color:var(--primary);">${val}</div>
                </div>
            </div>`;

        el.innerHTML = `
            ${statBox('fas fa-calendar-check','การจองวันนี้ทั้งหมด', total + ' รายการ', '#2563eb')}
            ${statBox('fas fa-clock','รออนุมัติ', pending + ' รายการ', '#d97706')}
            ${statBox('fas fa-check-circle','อนุมัติแล้ว', approved + ' รายการ', '#16a34a')}
            ${statBox('fas fa-times-circle','ไม่อนุมัติ', rejected + ' รายการ', '#dc2626')}
            ${topRooms.length ? `
            <div style="margin-top:0.5rem;padding-top:0.75rem;border-top:1px solid var(--border);">
                <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.5rem;">ห้องที่ใช้งานบ่อยวันนี้</div>
                ${topRooms.map(([name, cnt], i) => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0.75rem;background:${i===0?'var(--primary-soft)':'transparent'};border-radius:0.5rem;margin-bottom:3px;">
                    <span style="font-size:0.82rem;font-weight:600;color:var(--text-main);">${name}</span>
                    <span style="font-size:0.75rem;font-weight:800;color:var(--primary);background:var(--border);padding:0.15rem 0.5rem;border-radius:99px;">${cnt} ครั้ง</span>
                </div>`).join('')}
            </div>` : ''}
            <div style="text-align:center;font-size:0.68rem;color:var(--text-muted);opacity:0.6;margin-top:0.5rem;">ข้อมูล ณ วันที่ ${new Date().toLocaleDateString('th-TH',{year:'numeric',month:'long',day:'numeric'})}</div>
        `;
    } catch(e) {
        el.innerHTML = `<div style="text-align:center;padding:1.5rem;color:#dc2626;font-size:0.85rem;">โหลดข้อมูลไม่สำเร็จ</div>`;
    }
}

// Global window function bindings
window.openUsageStats = openUsageStats;
window.closeUsageStats = closeUsageStats;
window.clearRoomSearch = clearRoomSearch;
window.scrollToRoom = scrollToRoom;
window.openRoomDropdown = openRoomDropdown;
window.closeRoomDropdown = closeRoomDropdown;
</script>
