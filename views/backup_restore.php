<?php
if (!isset($_SESSION['user_data']) || ($_SESSION['user_data']['role'] ?? 'user') !== 'admin') {
    exit('Unauthorized');
}
?>

<div class="flex flex-col gap-6 w-full pb-10">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <h2 class="text-2xl font-bold text-primary flex items-center gap-2" style="flex-shrink:0;">
            <i class="fas fa-database text-secondary"></i> สำรองและกู้คืนฐานข้อมูล
        </h2>
        <button id="btnCreateBackup" class="inline-flex justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 items-center gap-2">
            <i class="fas fa-download"></i> สร้างไฟล์สำรองข้อมูลใหม่
        </button>
    </div>

    <div class="dash-card bg-white rounded-[1.5rem] p-6 shadow-sm border border-slate-100">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <i class="fas fa-history text-secondary"></i> ประวัติการสำรองข้อมูล
        </h3>
        
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse" style="min-width: 600px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border);">
                        <th style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">วันที่/เวลา (ชื่อไฟล์)</th>
                        <th style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">ขนาดไฟล์</th>
                        <th style="padding: 1rem 1.25rem; color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; text-align: right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="backupListTbody">
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-muted);">กำลังโหลดข้อมูล...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadBackups();

    document.getElementById('btnCreateBackup').addEventListener('click', function() {
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...';
        btn.disabled = true;
        btn.style.opacity = '0.7';

        fetch('api/backup_restore.php?action=backup', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.style.opacity = '1';

                if (data.success) {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#2563eb'
                    });
                    loadBackups();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message || 'ไม่สามารถสร้างไฟล์สำรองได้', 'error');
                }
            })
            .catch(err => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.style.opacity = '1';
                Swal.fire('ข้อผิดพลาด', 'ระบบทำงานผิดพลาด', 'error');
            });
    });
});

function loadBackups() {
    const tbody = document.getElementById('backupListTbody');
    fetch('api/backup_restore.php?action=list')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.files.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-muted);">ไม่พบไฟล์สำรองข้อมูล</td></tr>';
                    return;
                }
                
                let html = '';
                data.files.forEach(file => {
                    const dateObj = new Date(file.date * 1000);
                    const dateStr = dateObj.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const sizeStr = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                    
                    html += `
                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;" onmouseover="this.style.background='var(--sidebar-bg)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 1rem 1.25rem;">
                            <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.2rem;">${dateStr} น.</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${file.name}</div>
                        </td>
                        <td style="padding: 1rem 1.25rem; font-weight: 600; color: var(--text-main);">
                            ${sizeStr}
                        </td>
                        <td style="padding: 1rem 1.25rem; text-align: right;">
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button onclick="restoreBackup('${file.name}')" class="inline-flex justify-center rounded-lg bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-700 shadow-sm hover:bg-emerald-200 transition-all">
                                    <i class="fas fa-upload mr-1"></i> กู้คืน
                                </button>
                                <a href="api/backup_restore.php?action=download&filename=${file.name}" target="_blank" class="inline-flex justify-center rounded-lg bg-blue-100 px-3 py-1.5 text-xs font-bold text-blue-700 shadow-sm hover:bg-blue-200 transition-all">
                                    <i class="fas fa-download mr-1"></i> โหลด
                                </a>
                                <button onclick="deleteBackup('${file.name}')" class="inline-flex justify-center rounded-lg bg-red-100 px-3 py-1.5 text-xs font-bold text-red-700 shadow-sm hover:bg-red-200 transition-all">
                                    <i class="fas fa-trash-alt mr-1"></i> ลบ
                                </button>
                            </div>
                        </td>
                    </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-muted);">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 2rem; color: var(--text-muted);">ระบบทำงานผิดพลาด</td></tr>';
        });
}

function deleteBackup(filename) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: `คุณต้องการลบไฟล์ ${filename} ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'ใช่, ลบเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('filename', filename);

            fetch('api/backup_restore.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'ลบสำเร็จ!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadBackups();
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

function restoreBackup(filename) {
    Swal.fire({
        title: 'ยืนยันการกู้คืนฐานข้อมูล',
        html: `คุณกำลังจะกู้คืนข้อมูลจากไฟล์ <b>${filename}</b><br><br><span style="color:#dc2626;font-size:0.9em;">คำเตือน: ข้อมูลปัจจุบันทั้งหมดจะถูกแทนที่ด้วยข้อมูลจากไฟล์นี้ โปรดแน่ใจว่าคุณได้สำรองข้อมูลล่าสุดไว้แล้ว</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'ยืนยันกู้คืน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังกู้คืนข้อมูล...',
                text: 'โปรดอย่าปิดหน้าต่างนี้',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('action', 'restore');
            formData.append('filename', filename);

            fetch('api/backup_restore.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'กู้คืนสำเร็จ!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#2563eb'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            });
        }
    });
}
</script>
