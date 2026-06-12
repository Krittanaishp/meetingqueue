<div class="hero-banner" style="background-image: url('assets/images/poster1.png'); background-position: center; max-width: 900px; margin: 0 auto 2rem;">
    <div class="hero-content">
        <h2 style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">บันทึกประชุมภายนอก</h2>
        <p style="opacity: 0.9; text-shadow: 0 1px 2px rgba(0,0,0,0.5);">บันทึกข้อมูลการประชุมนอกสถานที่ของบุคลากร</p>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header" style="background: #fffdf2;">
        <div class="card-title">
            <i class="fas fa-desktop" style="color: #64748b;"></i>
            บันทึกประชุมภายนอก
        </div>
    </div>
    <div class="card-body" style="padding: 2.5rem;">
        <form id="externalMeetingForm">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>ห้องประชุม <span style="color: var(--danger);">*</span></label>
                    <select id="ext_room_id" required>
                        <option value="">เลือกรายการ</option>
                        <option value="external">ภายนอกหน่วยงาน</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>หน่วยงาน <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="external_org" placeholder="หน่วยงาน" required>
                </div>
            </div>

            <div class="form-group">
                <label>กิจกรรม <span style="color: var(--danger);">*</span></label>
                <input type="text" id="ext_title" placeholder="เรื่อง" required>
            </div>

            <div class="filter-bar" style="margin-bottom: 1.5rem;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>วันที่ <span style="color: var(--danger);">*</span></label>
                    <input type="date" id="ext_date" required>
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>เริ่มเวลา</label>
                    <input type="time" id="ext_start_time" value="00:00" required>
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>ถึงเวลา</label>
                    <input type="time" id="ext_end_time" value="00:00" required>
                </div>
            </div>

            <div class="form-group" style="max-width: 200px;">
                <label>จำนวนผู้เข้าประชุม(คน) <span style="color: var(--danger);">*</span></label>
                <input type="number" id="ext_participants_count" placeholder="คน" required>
            </div>

            <div class="form-group">
                <label>หมายเหตุ</label>
                <textarea id="ext_description" rows="3" placeholder="หมายเหตุ"></textarea>
            </div>

            <div class="form-group" style="max-width: 200px;">
                <label>หมายเลขโทรศัพท์ <span style="color: var(--danger);">*</span></label>
                <input type="text" id="ext_phone" placeholder="หมายเลขโทรศัพท์" required>
            </div>

            <div class="form-group">
                <label>ไฟล์แนบ <span style="font-size: 0.75rem; color: var(--text-muted);">(ประกาศ, กำหนดการ ฯลฯ)</span></label>
                <input type="file" id="ext_attachment" style="padding: 0.4rem;">
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2.5rem;">
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const today = new Date().toLocaleDateString('en-CA');
        document.getElementById('ext_date').value = today;
    });

    document.getElementById('externalMeetingForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        try {
            const fd = new FormData();
            fd.append('is_external', 1);
            fd.append('external_org', document.getElementById('external_org').value);
            fd.append('title', document.getElementById('ext_title').value);
            
            // Format dates
            const date = document.getElementById('ext_date').value;
            const startTime = document.getElementById('ext_start_time').value;
            const endTime = document.getElementById('ext_end_time').value;
            fd.append('start_time', `${date} ${startTime}:00`);
            fd.append('end_time', `${date} ${endTime}:00`);
            
            fd.append('participants_count', document.getElementById('ext_participants_count').value);
            fd.append('description', document.getElementById('ext_description').value);
            fd.append('phone', document.getElementById('ext_phone').value);
            
            const file = document.getElementById('ext_attachment').files[0];
            if (file) {
                fd.append('attachment', file);
            }

            const res = await fetch('api/bookings.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            
            if (json.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกข้อมูลสำเร็จ!',
                    text: 'บันทึกการประชุมภายนอกเรียบร้อยแล้ว',
                    confirmButtonColor: '#2563EB',
                    confirmButtonText: 'ตกลง',
                    background: '#fff',
                    customClass: { popup: 'rounded-[3rem]', confirmButton: 'rounded-2xl px-10 py-4 font-black' }
                }).then(() => {
                    window.location.href = 'dashboard.php?view=booking_result&id=' + json.id;
                });
            } else {
                throw new Error(json.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        } catch (err) {
            Swal.fire({ 
                icon: 'error', 
                title: 'ไม่สำเร็จ', 
                text: err.message, 
                confirmButtonColor: '#ef4444' 
            });
        }
    });
</script>
