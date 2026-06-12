<?php
if (($_SESSION['user_data']['role'] ?? 'user') !== 'admin') {
    exit('Unauthorized access');
}

$page_title = 'ถังขยะ';
$page_subtitle = 'รายการที่ถูกลบชั่วคราว คุณสามารถกู้คืนหรือลบทิ้งถาวรได้ที่นี่';
?>

<div class="flex flex-col gap-6 w-full animate-fade">
    <!-- Header Section (Borderless) -->
    <div class="flex items-center justify-between gap-6 px-4">
        <div class="flex items-center gap-6 relative z-10">
            <div class="w-17.5 h-17.5 rounded-[2rem] bg-red-500 flex items-center justify-center text-white shadow-xl shadow-red-500/20">
                <i class="fas fa-trash-alt text-2xl"></i>
            </div>
            <div class="flex-grow">
                <h1 class="text-3xl font-black text-primary leading-relaxed py-1 tracking-tight"><?= $page_title ?></h1>
                <p class="text-text-muted font-bold opacity-80"><?= $page_subtitle ?></p>
            </div>
        </div>
        <a href="dashboard.php?view=admin_management" class="min-w-[160px] h-[45px] px-8 bg-primary text-white rounded-[2rem] text-sm font-black flex items-center justify-center gap-2 hover:bg-primary/90 hover:shadow-lg transition-all shadow-md">
            <i class="fas fa-arrow-left"></i> กลับไปหน้าจัดการ
        </a>
    </div>

    <!-- Trash Table Card -->
    <div class="bg-white rounded-[1rem] shadow-sm border border-accent/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-accent/5">
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">ID</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">การประชุม</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">ผู้จอง</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">วันที่ลบ</th>
                        <th class="px-6 py-4 text-[0.65rem] font-black text-text-muted uppercase tracking-widest border-b border-accent/10">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="trashTableBody" class="divide-y divide-accent/10">
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-4 opacity-30">
                                <i class="fas fa-circle-notch fa-spin text-4xl"></i>
                                <p class="font-bold">กำลังโหลดถังขยะ...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div style="padding-left: 10px !important; padding-top: 10px !important; padding-bottom: 10px !important;" class="bg-accent/5 border-t border-accent/10 text-xs font-bold text-text-muted leading-relaxed" id="trashStats">
            มีรายการในถังขยะทั้งหมด 0 รายการ
        </div>
    </div>
</div>

<script>
    async function loadTrash() {
        const response = await fetch('api/bookings.php?only_trashed=1');
        const data = await response.json();
        const tbody = document.getElementById('trashTableBody');
        const stats = document.getElementById('trashStats');

        if (data.success && data.bookings.length > 0) {
            tbody.innerHTML = data.bookings.map(b => `
                <tr class="hover:bg-red-50/30 transition-colors">
                    <td class="px-6 py-4 text-xs font-bold text-text-muted">#${b.id}</td>
                    <td class="px-6 py-4">
                        <div class="font-black text-primary leading-tight mb-1">${b.title}</div>
                        <div class="text-[0.65rem] font-bold text-text-muted italic">${b.room_name || 'ภายนอก'}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs font-bold text-primary">${b.first_name}</div>
                        <div class="text-[0.65rem] font-bold text-text-muted">${b.department_name || '-'}</div>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-red-500">
                        ${new Date(b.deleted_at).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short', calendar: 'buddhist' })}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-start gap-3">
                            <button onclick="restoreBooking(${b.id})" class="min-w-[100px] h-[40px] px-6 rounded-full bg-green-50 text-green-600 font-black text-xs flex items-center justify-center gap-2 hover:bg-green-600 hover:text-white transition-all shadow-sm border border-green-200/50">
                                <i class="fas fa-undo"></i> กู้คืน
                            </button>
                            <button onclick="permanentDelete(${b.id})" class="min-w-[100px] h-[40px] px-6 rounded-full bg-red-50 text-red-600 font-black text-xs flex items-center justify-center gap-2 hover:bg-red-600 hover:text-white transition-all shadow-sm border border-red-200/50">
                                <i class="fas fa-times"></i> ลบทิ้งถาวร
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            stats.innerText = `มีรายการในถังขยะทั้งหมด ${data.bookings.length} รายการ`;
            
            // Initialize or refresh paginator
            if (!window.trashPaginator) {
                window.trashPaginator = new MeetQueuePaginator({
                    container: '#trashTableBody',
                    itemSelector: 'tr',
                    pageSize: 10
                });
            } else {
                window.trashPaginator.refresh();
            }
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-20 text-center flex flex-col items-center gap-4">
                <i class="fas fa-trash-restore text-4xl text-accent"></i>
                <p class="font-bold text-text-muted">ถังขยะว่างเปล่า</p>
            </td></tr>`;
            stats.innerText = `มีรายการในถังขยะทั้งหมด 0 รายการ`;
        }
    }


    async function restoreBooking(id) {
        const result = await Swal.fire({
            title: 'กู้คืนการจอง?',
            text: "รายการนี้จะกลับไปแสดงในหน้าจัดการปกติ",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'กู้คืน',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            const response = await fetch('api/bookings.php', {
                method: 'PATCH',
                body: JSON.stringify({ booking_id: id, action: 'restore' })
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('สำเร็จ!', data.message, 'success');
                loadTrash();
            }
        }
    }

    async function permanentDelete(id) {
        const result = await Swal.fire({
            title: 'ลบถาวร?',
            text: "คุณจะไม่สามารถกู้คืนข้อมูลนี้ได้อีกต่อไป!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ยืนยันลบถาวร',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            const response = await fetch('api/bookings.php', {
                method: 'DELETE',
                body: JSON.stringify({ booking_id: id, permanent: true })
            });
            const data = await response.json();
            if (data.success) {
                Swal.fire('ลบแล้ว!', data.message, 'success');
                loadTrash();
            }
        }
    }

    loadTrash();
</script>
