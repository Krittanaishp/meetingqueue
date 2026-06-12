<?php
if (($_SESSION['user_data']['role'] ?? 'user') !== 'admin') {
    exit('Unauthorized access');
}

$page_title = 'จัดการข้อมูลการประชุม';
$page_subtitle = 'หน้าจอสำหรับผู้ดูแลระบบ เพื่อบริหารจัดการข้อมูลการจองห้องประชุมทั้งหมด';
?>

<div class="flex flex-col gap-6 w-full animate-fade">
    <style>
        .filter-panel {
            background: var(--card, white);
            border-radius: 1rem;
            border: 1px solid var(--border, rgba(15, 23, 42, 0.15));
            box-shadow: 0 2px 8px -2px rgba(var(--shadow-rgb, 106, 82, 67), 0.05);
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
            display: none;
            border-top: 1px solid var(--border, rgba(15, 23, 42, 0.1));
            padding: 1rem;
            background: var(--sidebar-bg, var(--white));
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .filter-panel-advanced.open { display: flex; }
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
            color: var(--text-muted, var(--text-muted));
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.55rem 0.85rem;
            font-size: 0.85rem;
            border-radius: 0.6rem;
            border: 1px solid var(--border, rgba(15, 23, 42, 0.15));
            background: var(--card, white);
            color: var(--text-main, var(--primary));
        }
        .filter-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1rem;
            border-radius: 0.65rem;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--border, rgba(15, 23, 42, 0.15));
            background: var(--card, white);
            color: var(--text-main, var(--primary));
            transition: all 0.2s;
            white-space: nowrap;
        }
        .filter-toggle-btn:hover { background: var(--sidebar-bg, var(--border)); border-color: var(--secondary, var(--secondary)); }
        .filter-toggle-btn.active { background: var(--primary, var(--primary)); color: white; border-color: var(--primary, var(--primary)); }
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
            transition: all 0.2s;
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
            background: rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(15, 23, 42, 0.18);
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--primary, var(--primary));
        }
        .filter-chip button {
            all: unset;
            cursor: pointer;
            opacity: 0.5;
            font-size: 0.65rem;
        }
    </style>
    <!-- Header Section (Borderless) -->
    <div class="flex flex-wrap items-center justify-between gap-6 px-4">
        <div class="flex items-center gap-6 relative z-10">
            <div class="w-18 h-18 rounded-[2rem] bg-primary flex items-center justify-center text-white shadow-xl shadow-primary/20">
                <i class="fas fa-tasks text-2xl"></i>
            </div>
            <div class="flex-grow">
                <h1 class="text-3xl font-black text-primary leading-relaxed py-1 tracking-tight"><?= $page_title ?></h1>
                <p class="text-text-muted font-bold opacity-80"><?= $page_subtitle ?></p>
            </div>
        </div>
        <div class="flex flex-wrap gap-4 sm:gap-6">
            <a href="dashboard.php?view=trash_management" class="min-w-[150px] h-[50px] px-8 bg-red-50 text-red-600 rounded-[2rem] text-sm font-black flex items-center justify-center gap-1.5 hover:bg-red-100 hover:shadow-lg hover:shadow-red-200/50 transition-all border border-red-200/60 shadow-sm">
                <i class="fas fa-trash-alt"></i> ดูถังขยะ
            </a>
            <button onclick="exportData('excel')" class="min-w-[150px] h-[50px] px-8 bg-green-500 text-white rounded-[2rem] text-sm font-black flex items-center justify-center gap-1.5 hover:bg-green-600 hover:shadow-lg hover:shadow-green-200/60 transition-all shadow-md">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-[1rem] shadow-sm border border-accent/30 overflow-hidden">
        <div class="p-6">
            <!-- Filter Panel (Exact same design as meeting status) -->
            <div class="filter-panel">
                <!-- Top Row -->
                <div class="filter-panel-top">
                    <div class="search-input" style="position: relative; flex:1; min-width:220px;">
                        <i class="fas fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);pointer-events:none;"></i>
                        <input type="text" id="adminSearch" placeholder="ค้นหาหัวข้อ, ผู้จอง, ห้องประชุม..." style="padding-left:2.5rem;padding-top:0.55rem;padding-bottom:0.55rem;font-size:0.9rem;" autocomplete="off">
                    </div>
                    <select id="adminStatusFilter" style="min-width:150px;padding-top:0.55rem;padding-bottom:0.55rem;font-size:0.9rem;">
                        <option value="">สถานะทั้งหมด</option>
                        <option value="pending">รออนุมัติ</option>
                        <option value="approved">อนุมัติแล้ว</option>
                        <option value="rejected">ไม่อนุมัติ</option>
                        <option value="completed">เสร็จสิ้น</option>
                        <option value="cancelled">ยกเลิก</option>
                    </select>
                    <button id="filterToggleBtn" class="filter-toggle-btn" onclick="toggleAdvancedFilter()">
                        <i class="fas fa-sliders-h"></i> ตัวกรองเพิ่มเติม
                        <span class="filter-count" id="activeFilterCount">0</span>
                    </button>
                    <button id="filterClearBtn" class="filter-clear-btn" onclick="resetFilters()">
                        <i class="fas fa-times"></i> ล้างตัวกรอง
                    </button>
                </div>

                <!-- Advanced Filters -->
                <div class="filter-panel-advanced" id="advancedFilterPanel">
                    <div class="filter-group">
                        <label><i class="fas fa-user-tie mr-2"></i>ชื่อผู้จอง</label>
                        <input type="text" id="filterBooker" placeholder="ชื่อผู้จอง..." autocomplete="off">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-door-open mr-2"></i>ห้องประชุม</label>
                        <select id="filterRoom">
                            <option value="">ทุกห้อง</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt mr-2"></i>ตั้งแต่วันที่</label>
                        <input type="date" id="adminDateFrom">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-check mr-2"></i>ถึงวันที่</label>
                        <input type="date" id="adminDateTo">
                    </div>
                </div>

                <!-- Active Filter Chips -->
                <div class="active-filter-chips" id="activeFilterChips"></div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-accent/5">
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">ID</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">การประชุม</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">ผู้จอง / ฝ่าย</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">วัน-เวลา</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">สถานะ</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="adminBookingTable" class="divide-y divide-accent/10">
                    <!-- Data will be loaded here -->
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-4 opacity-30">
                                <i class="fas fa-circle-notch fa-spin text-4xl"></i>
                                <p class="font-bold">กำลังโหลดข้อมูล...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Stats -->
        <div class="bg-accent/5 border-t border-accent/10 flex justify-between items-center" style="padding: 0.5rem 0.5rem;">
            <div id="tableStats" class="text-xs font-bold text-text-muted">
                แสดงทั้งหมด 0 รายการ
            </div>
            <div id="pagination" class="flex gap-2"></div>
        </div>
    </div>
</div>

<script>
    let allBookings = [];
    let filteredBookings = [];
    let displayLimit = 10;

    document.addEventListener('DOMContentLoaded', () => {
        // Wait for MeetQueue to be ready before initializing
        if (typeof MeetQueue !== 'undefined' && MeetQueue.api) {
            initAdminView();
        } else {
            let attempts = 0;
            const checkReady = setInterval(() => {
                attempts++;
                if (typeof MeetQueue !== 'undefined' && MeetQueue.api) {
                    clearInterval(checkReady);
                    initAdminView();
                } else if (attempts > 50) { // 5 seconds timeout
                    clearInterval(checkReady);
                    const tableBody = document.getElementById('adminBookingTable');
                    if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-red-500 font-bold">ระบบขัดข้อง: ไม่สามารถโหลดสคริปต์หลักได้</td></tr>';
                }
            }, 100);
        }
    });

    async function initAdminView() {
        try {
            // Load data first as it's the most critical
            await loadData();
            // Then load room options in the background
            loadRoomOptions();
            
            // Setup listeners after initial data load
            const filterIds = ['adminSearch', 'adminStatusFilter', 'filterBooker', 'filterRoom', 'adminDateFrom', 'adminDateTo'];
            filterIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', () => {
                        applyFilters();
                        updateFilterUI();
                    });
                }
            });
        } catch (err) {
            console.error("Init failed:", err);
            const tableBody = document.getElementById('adminBookingTable');
            if (tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-red-500 font-bold">เกิดข้อผิดพลาด: ${err.message || 'การเชื่อมต่อขัดข้อง'}</td></tr>`;
        }
    }

    async function loadRoomOptions() {
        try {
            const data = await MeetQueue.api.fetch('api/rooms.php');
            if (data.success && data.rooms) {
                const sel = document.getElementById('filterRoom');
                if (sel) {
                    sel.innerHTML = '<option value="">ทุกห้อง</option>';
                    data.rooms.forEach(r => {
                        const opt = document.createElement('option');
                        opt.value = r.id;
                        opt.textContent = r.name;
                        sel.appendChild(opt);
                    });
                }
            }
        } catch(e) {
            console.warn('Room options failed to load', e);
        }
    }

    async function loadData() {
        const tableBody = document.getElementById('adminBookingTable');
        try {
            const data = await MeetQueue.api.fetch('api/bookings.php');
            
            if (data.success) {
                // Sort by ID Ascending (Oldest to Newest)
                allBookings = (data.bookings || []).sort((a, b) => parseInt(a.id) - parseInt(b.id));
                applyFilters();
                updateFilterUI();
            } else {
                if (tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-red-500 font-bold">${data.message || 'ไม่สามารถโหลดข้อมูลได้'}</td></tr>`;
            }
        } catch(e) {
            console.error('Fetch error:', e);
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-red-500 font-bold">ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้ (Network/Auth Error)</td></tr>';
            throw e;
        }
    }

    function toggleAdvancedFilter() {
        const panel = document.getElementById('advancedFilterPanel');
        const btn = document.getElementById('filterToggleBtn');
        const isOpen = panel.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
    }

    function applyFilters() {
        const search = document.getElementById('adminSearch')?.value.toLowerCase() || '';
        const status = document.getElementById('adminStatusFilter')?.value || '';
        const booker = document.getElementById('filterBooker')?.value.toLowerCase() || '';
        const room = document.getElementById('filterRoom')?.value || '';
        const dateFrom = document.getElementById('adminDateFrom')?.value || '';
        const dateTo = document.getElementById('adminDateTo')?.value || '';

        filteredBookings = allBookings.filter(b => {
            const bDate = b.start_time ? b.start_time.substring(0, 10) : '';
            const name = (b.first_name || '').toLowerCase();
            const roomName = (b.room_name || '').toLowerCase();

            const matchesSearch = !search || b.title.toLowerCase().includes(search) || 
                                 name.includes(search) || 
                                 roomName.includes(search);
            const matchesStatus = !status || b.status === status;
            const matchesBooker = !booker || name.includes(booker);
            const matchesRoom = !room || String(b.room_id) === room;
            const matchesDateFrom = !dateFrom || bDate >= dateFrom;
            const matchesDateTo = !dateTo || bDate <= dateTo;
            
            return matchesSearch && matchesStatus && matchesBooker && matchesRoom && matchesDateFrom && matchesDateTo;
        });

        displayLimit = 10; // Reset limit when filtering
        renderTable();
    }

    function updateFilterUI() {
        const booker = document.getElementById('filterBooker')?.value || '';
        const room = document.getElementById('filterRoom')?.value || '';
        const dateFrom = document.getElementById('adminDateFrom')?.value || '';
        const dateTo = document.getElementById('adminDateTo')?.value || '';
        const search = document.getElementById('adminSearch')?.value || '';
        const status = document.getElementById('adminStatusFilter')?.value || '';

        const advancedKeys = {
            booker: { label: 'ผู้จอง', val: booker },
            room: { label: 'ห้อง', val: room },
            dateFrom: { label: 'ตั้งแต่', val: dateFrom },
            dateTo: { label: 'ถึง', val: dateTo }
        };

        const chips = [];
        let count = 0;

        for (const [key, item] of Object.entries(advancedKeys)) {
            if (item.val) {
                count++;
                let displayVal = item.val;
                if (key === 'room') {
                    const opt = document.querySelector(`#filterRoom option[value="${item.val}"]`);
                    displayVal = opt ? opt.textContent : item.val;
                }
                chips.push(`<span class="filter-chip">${item.label}: ${displayVal} <button onclick="clearFilter('${key}')">✕</button></span>`);
            }
        }

        const countEl = document.getElementById('activeFilterCount');
        if (countEl) countEl.textContent = count;
        
        const clearBtn = document.getElementById('filterClearBtn');
        if (clearBtn) clearBtn.classList.toggle('visible', count > 0 || search !== '' || status !== '');
        
        const chipsEl = document.getElementById('activeFilterChips');
        if (chipsEl) chipsEl.innerHTML = chips.join('');
    }

    function clearFilter(key) {
        const idMap = {
            booker: 'filterBooker',
            room: 'filterRoom',
            dateFrom: 'adminDateFrom',
            dateTo: 'adminDateTo'
        };
        const el = document.getElementById(idMap[key]);
        if (el) {
            el.value = '';
            applyFilters();
            updateFilterUI();
        }
    }

    function renderTable() {
        const tbody = document.getElementById('adminBookingTable');
        const stats = document.getElementById('tableStats');
        const pagination = document.getElementById('pagination');
        
        if (filteredBookings.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center font-bold text-text-muted">ไม่พบข้อมูลที่ต้องการ</td></tr>`;
            stats.innerText = `แสดงทั้งหมด 0 รายการ`;
            if (pagination) pagination.innerHTML = '';
            
            if (window.adminPaginator) {
                window.adminPaginator.refresh();
            }
            return;
        }

        tbody.innerHTML = filteredBookings.map(b => {
            const statusClass = getStatusStyles(b.status);
            return `
                <tr class="hover:bg-primary/5 transition-colors group">
                    <td class="px-6 py-4 text-xs font-bold text-text-muted">#${b.id}</td>
                    <td class="px-6 py-4">
                        <div class="font-black text-primary leading-tight mb-1">${b.title}</div>
                        <div class="flex items-center gap-2 text-[0.65rem] font-bold text-secondary uppercase">
                            <i class="fas fa-door-open"></i> ${b.room_name || 'ภายนอก'}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg overflow-hidden bg-accent/30 shadow-inner flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(b.first_name || b.emp_code)}&background=6A5243&color=fff&size=80" 
                                     class="w-full h-full object-cover">
                            </div>
                            <div>
                                <div class="text-xs font-bold text-primary">${(b.first_name + ' ' + (b.last_name || '')).trim()}</div>
                                <div class="text-[0.65rem] font-bold text-text-muted">${b.department_name || '-'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs font-bold text-primary">${formatDate(b.start_time)}</div>
                        <div class="text-[0.65rem] font-bold text-text-muted">${formatTime(b.start_time)} - ${formatTime(b.end_time)}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-[0.6rem] font-black uppercase tracking-widest ${statusClass.bg} ${statusClass.text} border ${statusClass.border}">
                            ${translateStatus(b.status)}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-start gap-2">
                            <button onclick="viewDetail(${b.id})" class="w-8 h-8 rounded-lg bg-accent/20 text-primary hover:bg-primary hover:text-white transition-all shadow-sm" title="ดูรายละเอียด"><i class="fas fa-eye text-xs"></i></button>
                            <button onclick="deleteBooking(${b.id})" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm" title="ลบการจอง"><i class="fas fa-trash text-xs"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        stats.innerText = `ข้อมูลถูกโหลดสำเร็จ (${filteredBookings.length} รายการ)`;

        if (!window.adminPaginator) {
            window.adminPaginator = new MeetQueuePaginator({
                container: '#adminBookingTable',
                itemSelector: 'tr',
                pageSize: 10
            });
        } else {
            window.adminPaginator.refresh();
        }
        
        if (pagination) {
            pagination.innerHTML = ''; // Clear old pagination div since paginator creates its own
        }
    }

    function resetFilters() {
        ['adminSearch', 'adminStatusFilter', 'filterBooker', 'filterRoom', 'adminDateFrom', 'adminDateTo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        applyFilters();
        updateFilterUI();
    }

    function getStatusStyles(status) {
        switch(status) {
            case 'approved': return { bg: 'bg-green-50', text: 'text-green-600', border: 'border-green-200' };
            case 'pending': return { bg: 'bg-orange-50', text: 'text-orange-600', border: 'border-orange-200' };
            case 'rejected': return { bg: 'bg-red-50', text: 'text-red-600', border: 'border-red-200' };
            case 'completed': return { bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200' };
            case 'cancelled': return { bg: 'bg-gray-100', text: 'text-gray-400', border: 'border-gray-300' };
            default: return { bg: 'bg-gray-50', text: 'text-gray-600', border: 'border-gray-200' };
        }
    }

    function translateStatus(status) {
        const trans = {
            'pending': 'รออนุมัติ',
            'approved': 'อนุมัติแล้ว',
            'rejected': 'ไม่อนุมัติ',
            'completed': 'เสร็จสิ้น',
            'cancelled': 'ยกเลิก'
        };
        return trans[status] || status;
    }

    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit', calendar: 'buddhist' });
    }

    function formatTime(dateStr) {
        return new Date(dateStr).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
    }

    function viewDetail(id) {
        window.location.href = 'dashboard.php?view=booking_result&id=' + id;
    }

    async function deleteBooking(id) {
        const result = await Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "ข้อมูลการจองนี้จะถูกลบออกจากระบบอย่างถาวร",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ยืนยันลบ',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            const response = await fetch('api/bookings.php', {
                method: 'DELETE',
                body: JSON.stringify({ booking_id: id })
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('ลบข้อมูลแล้ว!', '', 'success');
                loadData();
            } else {
                Swal.fire('ผิดพลาด!', data.message, 'error');
            }
        }
    }

    function exportData(type) {
        if (type !== 'excel') return;
        
        // Get current filters to export matching data
        const search = document.getElementById('adminSearch')?.value || '';
        const status = document.getElementById('adminStatusFilter')?.value || '';
        const booker = document.getElementById('filterBooker')?.value || '';
        const room = document.getElementById('filterRoom')?.value || '';
        const from = document.getElementById('adminDateFrom')?.value || '';
        const to = document.getElementById('adminDateTo')?.value || '';

        // Build query string
        const params = new URLSearchParams({
            search: search,
            status: status,
            booker: booker,
            room: room,
            from: from,
            to: to
        });

        Swal.fire({
            title: 'กำลังเตรียมข้อมูล',
            text: 'ระบบกำลังสร้างไฟล์ Excel กรุณารอสักครู่...',
            icon: 'info',
            timer: 2000,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                // Trigger download
                window.location.href = `api/export_excel.php?${params.toString()}`;
            }
        });
    }
</script>
