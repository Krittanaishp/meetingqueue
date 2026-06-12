<?php
require_once 'api/config.php';
use App\Repository\RoomRepository;

if (!isset($_SESSION['user_id']) || $_SESSION['user_data']['role'] !== 'admin') {
    exit('Unauthorized');
}

$repo = new RoomRepository();
$rooms = $repo->getAll();
?>

<div class="flex flex-col gap-6 w-full animate-fade">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-primary flex items-center gap-2">
                <i class="fas fa-door-open text-[var(--secondary)]"></i> ระบบจัดการห้องประชุม
            </h2>
            <p class="text-text-muted text-sm mt-1">จัดการข้อมูล เพิ่ม แก้ไข และลบห้องประชุมในระบบ</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 ml-auto">
            <!-- Search & Filter Bar -->
            <div class="flex items-center gap-4 bg-white px-3 py-1.5 rounded-[1rem] border border-blue-400/30 shadow-sm focus-within:border-blue-400 transition-all">
                <!-- Integrated Search Field -->
                <div class="relative flex items-center">
                    <i class="fas fa-search absolute left-4 text-[var(--secondary)] text-sm pointer-events-none"></i>
                    <input type="text" id="roomSearchInput" onkeyup="filterRooms()" placeholder="ค้นหาชื่อห้อง หรือ สถานที่..." 
                        class="bg-slate-50 border border-blue-400/20 py-2 rounded-full focus:outline-none focus:border-blue-400 text-sm text-primary font-bold placeholder:text-text-muted/60 w-[240px] transition-all"
                        style="padding-left: 45px !important; padding-right: 15px !important;">
                </div>
                
                <div class="w-[1px] h-5 bg-[var(--secondary)]/20"></div>

                <!-- Filter Dropdown -->
                <div class="relative flex items-center">
                    <i class="fas fa-filter absolute left-2 text-[var(--secondary)] text-xs opacity-70 pointer-events-none"></i>
                    <select id="roomStatusFilter" onchange="filterRooms()" class="bg-transparent border-none focus:outline-none text-sm text-primary font-black cursor-pointer"
                        style="padding-left: 32px !important; padding-right: 50px !important;">
                        <option value="all">สถานะทั้งหมด</option>
                        <option value="available">พร้อมใช้งาน</option>
                        <option value="maintenance">ปิดปรับปรุง</option>
                    </select>
                </div>
            </div>

            <button onclick="openRoomModal()" class="min-w-[180px] h-[45px] px-8 bg-blue-600 text-white rounded-[2rem] text-sm font-black flex items-center justify-center gap-2 hover:bg-blue-800 hover:shadow-lg transition-all shadow-md">
                <i class="fas fa-plus"></i> เพิ่มห้องประชุมใหม่
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="p-4 text-text-muted font-bold text-sm w-16">#</th>
                        <th class="p-4 text-text-muted font-bold text-sm">ชื่อห้องประชุม</th>
                        <th class="p-4 text-text-muted font-bold text-sm">สถานที่ตั้ง</th>
                        <th class="p-4 text-text-muted font-bold text-sm">ความจุ (คน)</th>
                        <th class="p-4 text-text-muted font-bold text-sm">สถานะ</th>
                        <th class="p-4 text-text-muted font-bold text-sm">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="roomsTableBody" class="divide-y divide-[var(--border)]">
                    <?php if(count($rooms) > 0): ?>
                        <?php foreach($rooms as $index => $room): ?>
                        <tr class="border-b border-slate-200 hover:bg-white transition-colors group">
                            <td class="p-4 text-text-muted"><?= $index + 1 ?></td>
                            <td class="p-4 font-bold text-primary">
                                <?= htmlspecialchars($room['name']) ?>
                            </td>
                            <td class="p-4 text-primary">
                                <?= htmlspecialchars($room['location'] ?: '-') ?>
                            </td>
                            <td class="p-4 text-primary font-bold">
                                <?= htmlspecialchars($room['capacity']) ?>
                            </td>
                            <td class="p-4">
                                <div class="flex justify-start">
                                    <?php if($room['status'] === 'available'): ?>
                                        <span class="min-w-[110px] h-[30px] px-5 py-4 bg-green-50 text-green-600 rounded-full text-xs font-black border border-green-200/50 flex items-center justify-center gap-1 shadow-sm">
                                            <i class="fas fa-check-circle text-[10px]"></i> พร้อมใช้งาน
                                        </span>
                                    <?php else: ?>
                                        <span class="min-w-[110px] h-[30px] px-5 py-4 bg-red-50 text-red-600 rounded-full text-xs font-black border border-red-200/50 flex items-center justify-center gap-1 shadow-sm">
                                            <i class="fas fa-tools text-[10px]"></i> ปิดปรับปรุง
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex justify-start gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="editRoom(<?= htmlspecialchars(json_encode($room), ENT_QUOTES, 'UTF-8') ?>)" class="w-8 h-8 rounded-lg bg-slate-50 text-[var(--secondary)] hover:bg-[var(--secondary)] hover:text-white flex items-center justify-center transition-colors" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES, 'UTF-8') ?>')" class="w-8 h-8 rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors" title="ลบ">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-text-muted">ไม่พบข้อมูลห้องประชุม</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openRoomModal(room = null) {
    const isEdit = room !== null;
    Swal.fire({
        title: isEdit ? 'แก้ไขห้องประชุม' : 'เพิ่มห้องประชุมใหม่',
        width: '500px',
        padding: '2rem',
        html: `
            <div class="text-left mt-4" style="padding: 10px 5px;">
                <div style="margin-bottom: 28px;">
                    <label class="text-sm font-black text-primary ml-1" style="display: block; margin-bottom: 8px;">ชื่อห้องประชุม <span class="text-red-500">*</span></label>
                    <input type="text" id="room-name" class="w-full px-5 py-3.5 rounded-2xl border border-blue-400/30 focus:outline-none focus:border-blue-400 bg-slate-50 text-primary font-bold" placeholder="เช่น ห้องประชุมเอื้องผึ้ง" value="${isEdit ? escapeHtml(room.name) : ''}">
                </div>
                <div style="margin-bottom: 28px;">
                    <label class="text-sm font-black text-primary ml-1" style="display: block; margin-bottom: 8px;">สถานที่ตั้ง</label>
                    <input type="text" id="room-location" class="w-full px-5 py-3.5 rounded-2xl border border-blue-400/30 focus:outline-none focus:border-blue-400 bg-slate-50 text-primary font-bold" placeholder="เช่น อาคาร 1 ชั้น 2" value="${isEdit ? escapeHtml(room.location || '') : ''}">
                </div>
                <div class="grid grid-cols-2 gap-8" style="margin-bottom: 10px;">
                    <div>
                        <label class="text-sm font-black text-primary ml-1" style="display: block; margin-bottom: 8px;">ความจุ (คน) <span class="text-red-500">*</span></label>
                        <input type="number" id="room-capacity" class="w-full px-5 py-3.5 rounded-2xl border border-blue-400/30 focus:outline-none focus:border-blue-400 bg-slate-50 text-primary font-bold text-center" value="${isEdit ? room.capacity : '10'}">
                    </div>
                    <div>
                        <label class="text-sm font-black text-primary ml-1" style="display: block; margin-bottom: 8px;">สถานะ</label>
                        <select id="room-status" class="w-full px-5 py-3.5 rounded-2xl border border-blue-400/30 focus:outline-none focus:border-blue-400 bg-slate-50 text-primary font-bold">
                            <option value="available" ${isEdit && room.status === 'available' ? 'selected' : ''}>พร้อมใช้งาน</option>
                            <option value="maintenance" ${isEdit && room.status === 'maintenance' ? 'selected' : ''}>ปิดปรับปรุง</option>
                        </select>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#2563EB',
        cancelButtonColor: '#94a3b8',
        preConfirm: () => {
            const name = document.getElementById('room-name').value.trim();
            const location = document.getElementById('room-location').value.trim();
            const capacity = document.getElementById('room-capacity').value;
            const status = document.getElementById('room-status').value;

            if (!name || !capacity) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
                return false;
            }

            return { name, location, capacity, status, id: isEdit ? room.id : null };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            saveRoom(result.value, isEdit ? 'PATCH' : 'POST');
        }
    });
}

function editRoom(room) {
    openRoomModal(room);
}

function deleteRoom(id, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบห้อง "${name}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            saveRoom({ id: id }, 'DELETE');
        }
    });
}

async function saveRoom(data, method) {
    MeetQueue.utils.loading(true);
    try {
        const response = await fetch('api/rooms.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        MeetQueue.utils.loading(false);
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: 'ดำเนินการเสร็จสิ้น',
                confirmButtonColor: '#2563EB'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(result.message || 'เกิดข้อผิดพลาด');
        }
    } catch (err) {
        MeetQueue.utils.loading(false);
        Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาด',
            text: err.message,
            confirmButtonColor: '#2563EB'
        });
    }
}

function filterRooms() {
    const searchText = document.getElementById('roomSearchInput').value.toLowerCase();
    const statusFilter = document.getElementById('roomStatusFilter').value;
    const tableBody = document.getElementById('roomsTableBody');
    const rows = tableBody.getElementsByTagName('tr');

    let visibleCount = 0;

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        if (row.cells.length < 5) continue; // Skip no data row

        const roomName = row.cells[1].textContent.toLowerCase();
        const location = row.cells[2].textContent.toLowerCase();
        const capacity = row.cells[3].textContent.toLowerCase();
        const statusBadge = row.cells[4].textContent.toLowerCase();
        
        // Determine status from badge text
        let status = 'all';
        if (statusBadge.includes('พร้อมใช้งาน')) status = 'available';
        else if (statusBadge.includes('ปิดปรับปรุง')) status = 'maintenance';

        const matchesSearch = roomName.includes(searchText) || location.includes(searchText) || capacity.includes(searchText);
        const matchesStatus = statusFilter === 'all' || status === statusFilter;

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    }

    // Handle "No data" message if all filtered out
    let noDataRow = document.getElementById('noDataRow');
    if (visibleCount === 0) {
        if (!noDataRow) {
            noDataRow = document.createElement('tr');
            noDataRow.id = 'noDataRow';
            noDataRow.innerHTML = `<td colspan="6" class="p-8 text-center text-text-muted">ไม่พบข้อมูลที่ตรงตามเงื่อนไข</td>`;
            tableBody.appendChild(noDataRow);
        } else {
            noDataRow.style.display = '';
        }
    } else if (noDataRow) {
        noDataRow.style.display = 'none';
    }
    
    // Refresh paginator
    if (window.roomPaginator) {
        window.roomPaginator.refresh();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('roomsTableBody')) {
        window.roomPaginator = new MeetQueuePaginator({
            container: '#roomsTableBody',
            itemSelector: 'tr:not(#noDataRow)',
            pageSize: 10
        });
    }
});

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
</script>
