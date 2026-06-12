<?php
$current_view = $_GET['view'] ?? '';
$user_role = $_SESSION['user_data']['role'] ?? 'user';
$is_admin_view = ($current_view === 'approve_list' || $current_view === 'requests') && ($user_role === 'admin');
// force_user_filter: regular users see only their own; admins on 'status' see all
$force_user_filter = ($current_view === 'results' || $current_view === 'status') && ($user_role !== 'admin');

$page_title = 'สถานะการประชุม';
$page_subtitle = 'ติดตามสถานะการประชุมของคุณ';
$icon = 'fa-clipboard-check';

if ($is_admin_view) {
    if ($current_view === 'requests') {
        $page_title = 'รายการขอใช้ทั้งหมด';
        $page_subtitle = 'ดูและตรวจสอบประวัติการขอใช้ห้องประชุมทั้งหมดในระบบ';
        $icon = 'fa-list-ul';
        $breadcrumb = 'รายการขอใช้';
    } else {
        $page_title = 'รายการรออนุมัติ';
        $page_subtitle = 'จัดการคำขอจองที่อยู่ระหว่างการรออนุมัติ';
        $icon = 'fa-user-check';
        $breadcrumb = 'รายการอนุมัติ';
    }
} else {
    $breadcrumb = 'สถานะการประชุม';
}
?>

<style>
.filter-panel {
    background: var(--card, white);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.filter-panel-top {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    flex-wrap: wrap;
}
.filter-panel-advanced {
    display: flex;
    border-top: 1px solid var(--border);
    padding: 1rem;
    background: var(--sidebar-bg, var(--white));
    gap: 0.75rem;
    flex-wrap: wrap;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 160px;
    flex: 1;
}
.filter-group label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0;
}
.filter-group input,
.filter-group select {
    padding: 0.55rem 0.85rem;
    font-size: 0.85rem;
    border-radius: 0.6rem;
}
.filter-group select { padding-right: 2rem; }
.filter-toggle-btn {
    display: none !important;
}
.filter-toggle-btn .filter-count {
    background: rgba(255,255,255,0.3);
    border-radius: 99px;
    padding: 0 0.4rem;
    font-size: 0.7rem;
    display: none;
}
.filter-toggle-btn.active .filter-count { display: inline; }
.filter-clear-btn {
    display: none;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.85rem;
    border-radius: 0.65rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid #fecaca;
    background: #fee2e2;
    color: #991b1b;
    transition: var(--transition);
    white-space: nowrap;
    flex-shrink: 0;
}
.filter-clear-btn:hover { background: #fecaca; }
.filter-clear-btn.visible { display: inline-flex; }
.active-filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    padding: 0 0.75rem 0.75rem;
}
.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.6rem;
    background: rgba(37,99,235,0.08);
    border: 1px solid rgba(37,99,235,0.18);
    border-radius: 99px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--primary);
}
.filter-chip button {
    all: unset;
    cursor: pointer;
    opacity: 0.5;
    width: auto;
    padding: 0;
    font-size: 0.65rem;
    line-height: 1;
}
.filter-chip button:hover { opacity: 1; }

/* Status Shortcut Buttons Style */
.status-shortcut-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1.1rem;
    border-radius: 0.65rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid var(--border);
    background: var(--card, white);
    color: var(--text-main);
    transition: all 0.2s ease;
    white-space: nowrap;
    user-select: none;
}
.status-shortcut-btn:hover {
    background: var(--sidebar-bg, #fffdf2);
    border-color: var(--secondary);
    transform: translateY(-1px);
}
.status-shortcut-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}
.status-shortcut-btn.active[data-status="pending"] {
    background: #EAE4D3;
    color: #6E4B3A;
    border-color: #D2CAB7;
    box-shadow: 0 4px 12px rgba(110, 75, 58, 0.15);
}
.status-shortcut-btn.active[data-status="approved"] {
    background: #E6F4EA;
    color: #1E8E3E;
    border-color: rgba(30, 142, 62, 0.2);
    box-shadow: 0 4px 12px rgba(30, 142, 62, 0.15);
}
.status-shortcut-btn.active[data-status="rejected"] {
    background: #FCE8E6;
    color: #D93025;
    border-color: rgba(217, 48, 37, 0.2);
    box-shadow: 0 4px 12px rgba(217, 48, 37, 0.15);
}
.status-shortcut-btn.active[data-status="cancelled"] {
    background: #f1f5f9;
    color: #475569;
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(71, 85, 105, 0.15);
}
</style>

<div class="flex flex-col gap-6 w-full animate-fade">
    <!-- Header Section (Borderless) -->
    <div class="flex items-center justify-between gap-6 px-4">
        <div class="flex items-center gap-6 relative z-10">
            <div class="w-16 h-16 rounded-[2rem] bg-primary flex items-center justify-center text-white shadow-xl shadow-primary/20">
                <i class="fas <?= $icon ?> text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black text-primary tracking-tight leading-relaxed py-2"><?= $page_title ?></h1>
                <p class="text-text-muted font-bold opacity-80 leading-normal"><?= $page_subtitle ?></p>
            </div>
        </div>
    </div>

<div class="card">
    <div class="card-header" style="background: var(--sidebar-bg, #fffdf2);">
        <div class="card-title">
            <i class="fas fa-desktop" style="color: #64748b;"></i>
            จองห้องประชุม > <?php echo $breadcrumb; ?>
        </div>
    </div>
    <div class="card-body">

        <!-- Filter Panel -->
        <div class="filter-panel">
            <!-- Top Row -->
            <div class="filter-panel-top">
                <div class="search-input" style="position: relative; flex:1; min-width:220px;">
                    <i class="fas fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                    <input type="text" id="searchInput" placeholder="ค้นหาหัวข้อ, ผู้จอง, ห้องประชุม..." style="padding-left:2.5rem;padding-top:0.55rem;padding-bottom:0.55rem;font-size:0.9rem;" autocomplete="off">
                </div>
                <!-- Status Filter Shortcuts -->
                <div class="flex items-center gap-2 flex-wrap" id="statusFilterShortcuts" style="flex-shrink:0;">
                    <button type="button" class="status-shortcut-btn active" data-status="">ทั้งหมด</button>
                    <button type="button" class="status-shortcut-btn" data-status="pending">รออนุมัติ</button>
                    <button type="button" class="status-shortcut-btn" data-status="approved">อนุมัติแล้ว</button>
                    <button type="button" class="status-shortcut-btn" data-status="rejected">ไม่อนุมัติ</button>
                    <button type="button" class="status-shortcut-btn" data-status="cancelled">ยกเลิก</button>
                </div>
                <select id="statusFilter" style="display: none;">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="approved">อนุมัติ</option>
                    <option value="pending">รออนุมัติ</option>
                    <option value="rejected">ไม่อนุมัติ</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
                <button id="filterToggleBtn" class="filter-toggle-btn" onclick="toggleAdvancedFilter()">
                    <i class="fas fa-sliders-h"></i> ตัวกรองเพิ่มเติม
                    <span class="filter-count" id="activeFilterCount">0</span>
                </button>
                <button id="filterClearBtn" class="filter-clear-btn" onclick="clearAllFilters()">
                    <i class="fas fa-times"></i> ล้างตัวกรอง
                </button>
            </div>

            <!-- Advanced Filters -->
            <div class="filter-panel-advanced" id="advancedFilterPanel">
                <div class="filter-group">
                    <label><i class="fas fa-tag" style="margin-right:0.3rem;"></i>หัวข้อการประชุม</label>
                    <input type="text" id="filterTitle" placeholder="พิมพ์หัวข้อ..." autocomplete="off">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-user" style="margin-right:0.3rem;"></i>ผู้จอง</label>
                    <input type="text" id="filterBooker" placeholder="ชื่อผู้จอง..." autocomplete="off">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-door-open" style="margin-right:0.3rem;"></i>ห้องประชุม</label>
                    <select id="filterRoom">
                        <option value="">ทุกห้อง</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-building" style="margin-right:0.3rem;"></i>ฝ่าย/งาน</label>
                    <input type="text" id="filterDept" placeholder="ฝ่าย/งาน..." autocomplete="off">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-day" style="margin-right:0.3rem;"></i>วันเริ่มต้น</label>
                    <input type="date" id="filterDateFrom" autocomplete="off">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-day" style="margin-right:0.3rem;"></i>วันสิ้นสุด</label>
                    <input type="date" id="filterDateTo" autocomplete="off">
                </div>
            </div>

            <!-- Active Filter Chips -->
            <div class="active-filter-chips" id="activeFilterChips"></div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="w-16">ลำดับ</th>
                        <th>ผู้จอง</th>
                        <th>หัวข้อการประชุม</th>
                        <th>ห้องประชุม/สถานที่</th>
                        <th>วัน-เวลา</th>
                        <th>ฝ่าย/งาน</th>
                        <th class="text-left px-10" style="text-align: left !important; padding-right: 40px !important;">สถานะ</th>
                        <?php if ($current_view === 'approve_list'): ?>
                        <th class="text-left" style="text-align: left !important;">การจัดการ</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="approveTableBody"></tbody>
            </table>
        </div>

        <!-- Mobile Card List -->
        <div id="mobileCardList" class="md:hidden space-y-4"></div>

        <div class="pagination" id="paginationContainer" style="display: none;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialStatus = urlParams.get('status');

    ['searchInput','filterTitle','filterBooker','filterDept','filterDateFrom','filterDateTo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    
    const statusEl = document.getElementById('statusFilter');
    if (statusEl) {
        statusEl.value = initialStatus ? initialStatus : '';
    }
    
    const roomEl = document.getElementById('filterRoom');
    if (roomEl) roomEl.value = '';

    loadApproveList();
    loadRoomOptions();

    // Shortcut button click listeners
    document.querySelectorAll('.status-shortcut-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.getAttribute('data-status');
            const selectEl = document.getElementById('statusFilter');
            if (selectEl) {
                selectEl.value = status;
                selectEl.dispatchEvent(new Event('change'));
            }
        });
    });

    ['searchInput','statusFilter','filterTitle','filterBooker','filterRoom','filterDept','filterDateFrom','filterDateTo']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', () => { renderTable(); updateFilterUI(); });
        });
});

async function loadRoomOptions() {
    try {
        const data = await MeetQueue.api.fetch('api/rooms.php');
        if (data.success && data.rooms) {
            const sel = document.getElementById('filterRoom');
            data.rooms.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                sel.appendChild(opt);
            });
        }
    } catch(e) {}
}

async function loadApproveList() {
    const isAdminView = <?php echo json_encode($is_admin_view); ?>;
    const forceUserFilter = <?php echo json_encode($force_user_filter); ?>;
    const view = <?php echo json_encode($current_view); ?>;
    const userId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;

    let url = 'api/bookings.php';
    if (forceUserFilter) url += `?user_id=${userId}`;

    const data = await MeetQueue.api.fetch(url);

    if (data.success) {
        MeetQueue.setState({ bookings: data.bookings });
        
        const urlParams = new URLSearchParams(window.location.search);
        if (view === 'approve_list' && !urlParams.has('status')) {
            document.getElementById('statusFilter').value = 'pending';
        }
        
        renderTable();
        updateFilterUI();
    } else {
        document.getElementById('approveTableBody').innerHTML = `<tr><td colspan="7" class="text-center text-red-500 font-bold py-10">${data.message}</td></tr>`;
    }
}

function getFilterValues() {
    return {
        search:   (document.getElementById('searchInput')?.value || '').toLowerCase().trim(),
        status:   document.getElementById('statusFilter')?.value || '',
        title:    (document.getElementById('filterTitle')?.value || '').toLowerCase().trim(),
        booker:   (document.getElementById('filterBooker')?.value || '').toLowerCase().trim(),
        room:     document.getElementById('filterRoom')?.value || '',
        dept:     (document.getElementById('filterDept')?.value || '').toLowerCase().trim(),
        dateFrom: document.getElementById('filterDateFrom')?.value || '',
        dateTo:   document.getElementById('filterDateTo')?.value || '',
    };
}

function renderTable() {
    const tableBody = document.getElementById('approveTableBody');
    const mobileCardList = document.getElementById('mobileCardList');
    const currentView = <?php echo json_encode($current_view); ?>;
    const { bookings } = MeetQueue.getState();
    const f = getFilterValues();

    const filtered = bookings.filter(b => {
        const bDate = b.start_time ? b.start_time.substring(0, 10) : '';
        const name = (b.first_name || '').toLowerCase();
        const roomName = (b.room_name || '').toLowerCase();

        if (f.search && !b.title.toLowerCase().includes(f.search) && !roomName.includes(f.search) && !name.includes(f.search)) return false;
        if (f.status && b.status !== f.status) return false;
        if (f.title && !b.title.toLowerCase().includes(f.title)) return false;
        if (f.booker && !name.includes(f.booker)) return false;
        if (f.room && String(b.room_id) !== f.room) return false;
        if (f.dept && !(b.department_name || '').toLowerCase().includes(f.dept)) return false;
        if (f.dateFrom && bDate < f.dateFrom) return false;
        if (f.dateTo && bDate > f.dateTo) return false;
        return true;
    });

    // Sort logic: 
    // - "สถานะการประชุมของฉัน" (My booking status / results view) sorts by latest transaction first (id DESC / ทำรายการล่าสุดอยู่บน)
    // - "รายการคำขอ" (Pending requests or requests view, or pending status) sorts by earliest first (id ASC / มาก่อนอยู่บน)
    // - All other lists sort by latest meeting date first (start_time DESC / วันที่ล่าสุดเสมอ)
    if (currentView === 'results') {
        filtered.sort((a, b) => b.id - a.id);
    } else if ((currentView === 'approve_list') || (currentView === 'requests') || (f.status === 'pending')) {
        filtered.sort((a, b) => a.id - b.id);
    } else {
        filtered.sort((a, b) => new Date(b.start_time) - new Date(a.start_time));
    }

    const colspan = currentView === 'approve_list' ? 8 : 7;

    if (filtered.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center py-20 font-bold text-text-muted">ไม่พบข้อมูล</td></tr>`;
        mobileCardList.innerHTML = '<div class="text-center py-20 bg-white/50 rounded-[3rem] border-2 border-dashed border-accent"><i class="fas fa-folder-open text-4xl text-accent mb-4 block"></i><p class="font-bold text-text-muted">ไม่พบข้อมูลที่ค้นหา</p></div>';
        return;
    }

    tableBody.innerHTML = filtered.map((b, i) => `
        <tr class="hover:bg-primary/5 transition-colors cursor-pointer group">
            <td class="font-bold text-text-muted" onclick="viewDetail(${b.id})">${i + 1}</td>
            <td onclick="viewDetail(${b.id})">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-accent/30 flex-shrink-0">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(b.first_name || b.emp_code)}&background=6A5243&color=fff&size=80"
                             class="w-full h-full object-cover">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-primary">${b.first_name}</span>
                    </div>
                </div>
            </td>
            <td onclick="viewDetail(${b.id})">
                <div class="font-bold text-primary">${MeetQueue.utils.escapeHtml(b.title)}</div>
                <div class="text-[0.7rem] text-text-muted italic">${b.description ? MeetQueue.utils.escapeHtml(b.description).substring(0, 30) + '...' : ''}</div>
            </td>
            <td onclick="viewDetail(${b.id})">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-accent/20 flex items-center justify-center text-primary"><i class="fas fa-door-open text-xs"></i></div>
                    <span class="font-semibold">${b.room_name || 'ภายนอก'}</span>
                </div>
            </td>
            <td onclick="viewDetail(${b.id})">
                <div class="font-bold text-primary">${MeetQueue.utils.formatDate(b.start_time)}</div>
                <div class="text-xs text-text-muted font-semibold">${MeetQueue.utils.formatTime(b.start_time)} - ${MeetQueue.utils.formatTime(b.end_time)}</div>
            </td>
            <td onclick="viewDetail(${b.id})"><span class="text-xs font-bold px-2 py-1 bg-accent/20 rounded-md text-primary">${b.department_name || '-'}</span></td>
            <td class="text-left px-10" style="text-align: left !important; padding-right: 40px !important;" onclick="viewDetail(${b.id})">
                <span class="badge badge-${MeetQueue.utils.getStatusClass(b.status)}">${MeetQueue.utils.translateStatus(b.status)}</span>
            </td>
            ${currentView === 'approve_list' ? `
            <td>
                <div class="flex justify-start gap-2">
                    ${b.status === 'pending' ? `
                        <button onclick="updateStatus(${b.id}, 'approved')" class="w-8 h-8 rounded-lg bg-green-100 text-green-600 hover:bg-green-600 hover:text-white transition-all shadow-sm" title="อนุมัติ"><i class="fas fa-check text-xs"></i></button>
                        <button onclick="updateStatus(${b.id}, 'rejected')" class="w-8 h-8 rounded-lg bg-red-100 text-red-600 hover:bg-red-600 hover:text-white transition-all shadow-sm" title="ปฏิเสธ"><i class="fas fa-times text-xs"></i></button>
                    ` : `<button onclick="viewDetail(${b.id})" class="text-xs font-bold text-text-muted hover:text-primary">เรียกดู</button>`}
                </div>
            </td>` : ''}
        </tr>
    `).join('');

    mobileCardList.innerHTML = filtered.map(b => `
        <div class="card p-5 space-y-4 hover:border-primary/50 transition-all">
            <div class="flex justify-between items-start" onclick="viewDetail(${b.id})">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden bg-accent/30 shadow-inner">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(b.first_name || b.emp_code)}&background=6A5243&color=fff&size=80"
                             class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h4 class="font-black text-primary">${b.first_name}</h4>
                        <span class="text-[0.7rem] font-bold text-text-muted uppercase tracking-wider">${b.department_name || '-'}</span>
                    </div>
                </div>
                <span class="badge badge-${MeetQueue.utils.getStatusClass(b.status)}">${MeetQueue.utils.translateStatus(b.status)}</span>
            </div>
            <div class="space-y-3 pt-2" onclick="viewDetail(${b.id})">
                <h3 class="font-black text-lg leading-tight text-primary">${MeetQueue.utils.escapeHtml(b.title)}</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex items-center gap-2 text-text-muted"><i class="fas fa-door-open text-xs w-4"></i><span class="text-xs font-bold">${b.room_name || 'ภายนอก'}</span></div>
                    <div class="flex items-center gap-2 text-text-muted"><i class="fas fa-clock text-xs w-4"></i><span class="text-xs font-bold">${MeetQueue.utils.formatTime(b.start_time)} - ${MeetQueue.utils.formatTime(b.end_time)}</span></div>
                    <div class="flex items-center gap-2 text-text-muted col-span-2"><i class="fas fa-calendar-alt text-xs w-4"></i><span class="text-xs font-bold">${MeetQueue.utils.formatDate(b.start_time)}</span></div>
                </div>
            </div>
            ${currentView === 'approve_list' && b.status === 'pending' ? `
            <div class="pt-4 flex flex-wrap gap-3">
                <button onclick="updateStatus(${b.id}, 'approved')" class="flex-grow py-3 rounded-xl bg-green-500 text-white font-black text-sm shadow-lg shadow-green-200 min-w-[100px]">อนุมัติ</button>
                <button onclick="updateStatus(${b.id}, 'rejected')" class="flex-grow py-3 rounded-xl bg-red-500 text-white font-black text-sm shadow-lg shadow-red-200 min-w-[100px]">ปฏิเสธ</button>
            </div>` : `
            <div class="pt-2 flex justify-end" onclick="viewDetail(${b.id})">
                <span class="text-[0.65rem] font-black text-secondary uppercase tracking-widest flex items-center gap-1">ดูรายละเอียด <i class="fas fa-chevron-right text-[0.5rem]"></i></span>
            </div>`}
        </div>
    `).join('');

    // Re-initialize paginators
    if (!window.approveDesktopPaginator) {
        window.approveDesktopPaginator = new MeetQueuePaginator({ container: '#approveTableBody', itemSelector: 'tr', pageSize: 10 });
    } else {
        window.approveDesktopPaginator.refresh();
    }
    
    if (!window.approveMobilePaginator) {
        window.approveMobilePaginator = new MeetQueuePaginator({ container: '#mobileCardList', itemSelector: '.card', pageSize: 10 });
    } else {
        window.approveMobilePaginator.refresh();
    }
}

function toggleAdvancedFilter() {
    const panel = document.getElementById('advancedFilterPanel');
    const btn = document.getElementById('filterToggleBtn');
    const isOpen = panel.classList.toggle('open');
    btn.classList.toggle('active', isOpen);
}

function updateFilterUI() {
    const f = getFilterValues();
    const advancedKeys = ['title','booker','room','dept','dateFrom','dateTo'];
    const labels = { title:'หัวข้อ', booker:'ผู้จอง', room:'ห้อง', dept:'ฝ่าย/งาน', dateFrom:'จาก', dateTo:'ถึง' };
    const roomSel = document.getElementById('filterRoom');

    // Sync shortcut buttons active state
    const statusVal = f.status || '';
    document.querySelectorAll('.status-shortcut-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-status') === statusVal);
    });

    let count = 0;
    const chips = [];

    advancedKeys.forEach(k => {
        if (!f[k]) return;
        count++;
        let val = f[k];
        if (k === 'room') {
            const opt = roomSel.querySelector(`option[value="${val}"]`);
            val = opt ? opt.textContent : val;
        }
        chips.push(`<span class="filter-chip">${labels[k]}: ${val}<button onclick="clearFilter('${k}')" title="ลบตัวกรอง">✕</button></span>`);
    });

    document.getElementById('activeFilterCount').textContent = count;
    document.getElementById('filterToggleBtn').classList.toggle('active', count > 0 || document.getElementById('advancedFilterPanel').classList.contains('open'));
    document.getElementById('filterClearBtn').classList.toggle('visible', count > 0 || f.search !== '' || f.status !== '');
    document.getElementById('activeFilterChips').innerHTML = chips.join('');
}

function clearFilter(key) {
    const map = { title:'filterTitle', booker:'filterBooker', room:'filterRoom', dept:'filterDept', dateFrom:'filterDateFrom', dateTo:'filterDateTo' };
    const el = document.getElementById(map[key]);
    if (el) { el.value = ''; renderTable(); updateFilterUI(); }
}

function clearAllFilters() {
    ['searchInput','filterTitle','filterBooker','filterDept','filterDateFrom','filterDateTo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('statusFilter').value = '';
    document.getElementById('filterRoom').value = '';
    renderTable();
    updateFilterUI();
}

async function updateStatus(id, status) {
    const confirmMsg = status === 'approved' ? 'ยืนยันการอนุมัติ?' : 'ยืนยันการปฏิเสธ?';
    const result = await Swal.fire({
        title: confirmMsg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'approved' ? '#22c55e' : '#ef4444',
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก'
    });

    if (result.isConfirmed) {
        MeetQueue.utils.loading(true);
        const data = await MeetQueue.api.fetch('api/bookings.php', {
            method: 'PATCH',
            body: JSON.stringify({ booking_id: id, status: status })
        });
        MeetQueue.utils.loading(false);
        if (data.success) {
            MeetQueue.utils.notify('success', 'ดำเนินการสำเร็จ');
            loadApproveList();
        } else {
            MeetQueue.utils.notify('error', 'ผิดพลาด', data.message);
        }
    }
}

function viewDetail(id) {
    window.location.href = 'dashboard.php?view=booking_result&id=' + id;
}
</script>
