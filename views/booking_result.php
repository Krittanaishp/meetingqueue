<?php
$bookingId = $_GET['id'] ?? null;

if (!$bookingId) {
    echo "<div class='text-center py-20 text-red-500 font-bold'>ไม่พบรหัสการจอง</div>";
    return;
}
?>

<style>
/* ═══════════════════════════════════════
   Booking Result — Premium Receipt Design
   ═══════════════════════════════════════ */

.br-page {
    max-width: 640px;
    margin: 0 auto;
    padding: 2.5rem 1rem;
}

/* ── Status Hero ── */
.br-hero {
    text-align: center;
    margin-bottom: 2rem;
}

.br-icon-wrap {
    position: relative;
    width: 72px;
    height: 72px;
    margin: 0 auto 1.25rem;
}

.br-icon-ring {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 2.5px solid var(--border);
    opacity: 0;
    animation: br-ring-in 0.6s 0.3s ease forwards;
}

.br-icon-circle {
    position: relative;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    background: var(--surface, #f8fafc);
    color: var(--text-muted);
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 1;
}

.br-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-main);
    margin-bottom: 0.3rem;
    letter-spacing: -0.01em;
}

.br-subtitle {
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--text-muted);
    line-height: 1.5;
}

/* ── Timeline Stepper ── */
.br-timeline {
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 0;
    margin: 1.75rem auto 0;
    max-width: 420px;
}

.br-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.br-step-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    background: var(--surface, #f1f5f9);
    color: var(--text-muted);
    border: 2.5px solid var(--border);
    position: relative;
    z-index: 2;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.br-step-dot.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
}

.br-step-label {
    margin-top: 0.5rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--text-muted);
    text-align: center;
    line-height: 1.3;
    transition: color 0.3s;
    max-width: 72px;
}

.br-step-label.active {
    color: var(--primary);
    font-weight: 700;
}

/* connector line between dots */
.br-step-line {
    position: absolute;
    top: 18px;
    left: calc(50% + 18px);
    right: calc(-50% + 18px);
    height: 2.5px;
    background: var(--border);
    z-index: 1;
    border-radius: 2px;
    overflow: hidden;
}

.br-step-line-fill {
    height: 100%;
    width: 0;
    background: var(--primary);
    border-radius: 2px;
    transition: width 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.br-step-line-fill.filled {
    width: 100%;
}

.br-step:last-child .br-step-line {
    display: none;
}

/* ── Ticket Card ── */
.br-card {
    background: var(--card);
    border-radius: 1.25rem;
    border: 1px solid var(--border);
    box-shadow: 0 2px 16px rgba(0,0,0,0.04), 0 8px 32px rgba(0,0,0,0.03);
    overflow: hidden;
    opacity: 0;
    transform: translateY(16px);
    animation: br-card-in 0.6s 0.15s ease forwards;
}

.br-card-accent {
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, #60a5fa 100%);
}

.br-card-body {
    padding: 1.75rem 1.75rem 1.5rem;
}

@media (max-width: 480px) {
    .br-card-body {
        padding: 1.25rem;
    }
}

/* ── Reference ID & Badge ── */
.br-ref-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-bottom: 1.25rem;
    margin-bottom: 1.25rem;
    border-bottom: 1.5px dashed var(--border);
}

.br-ref-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.2rem;
}

.br-ref-id {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--text-main);
    font-family: 'Outfit', monospace;
    letter-spacing: 0.06em;
}

.br-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

/* ── Topic Section ── */
.br-topic-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.br-topic-label i {
    color: var(--primary);
    font-size: 0.65rem;
}

.br-topic-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.5;
    margin-bottom: 1.25rem;
}

/* ── Info Grid (Date & Room) ── */
.br-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

@media (max-width: 480px) {
    .br-info-grid {
        grid-template-columns: 1fr;
    }
}

.br-info-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    background: var(--surface, #f8fafc);
    border: 1px solid var(--border);
    border-radius: 0.875rem;
    transition: box-shadow 0.2s, border-color 0.2s;
}

.br-info-item:hover {
    border-color: rgba(37, 99, 235, 0.2);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.06);
}

.br-info-icon {
    width: 36px;
    height: 36px;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--card);
    border: 1px solid var(--border);
    color: var(--primary);
    font-size: 0.9rem;
    flex-shrink: 0;
}

.br-info-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.15rem;
}

.br-info-value {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.4;
}

.br-info-sub {
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--text-muted);
    margin-top: 0.1rem;
}

/* ── Detail Fields ── */
.br-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--border);
    margin-bottom: 1.25rem;
}

@media (max-width: 480px) {
    .br-details {
        grid-template-columns: 1fr;
        gap: 0.85rem;
    }
}

.br-detail-item {}

.br-detail-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.2rem;
}

.br-detail-label i {
    color: var(--primary);
    font-size: 0.6rem;
}

.br-detail-value {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1.4;
}

/* ── Optional Sections (Notes / Equipment) ── */
.br-note-section {
    margin-bottom: 1rem;
}

.br-note-box {
    padding: 0.85rem 1rem;
    background: var(--surface, #f8fafc);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--text-main);
    line-height: 1.6;
    white-space: pre-line;
}

/* ── Card Footer ── */
.br-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 1rem 1.75rem;
    background: var(--surface, #f8fafc);
    border-top: 1px solid var(--border);
}

@media (max-width: 480px) {
    .br-footer {
        padding: 1rem 1.25rem;
        flex-direction: column;
        align-items: stretch;
    }
}

.br-footer-tip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-muted);
}

.br-footer-tip i {
    color: var(--primary);
}

.br-footer-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.br-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    border-radius: 0.625rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.br-btn:hover {
    transform: translateY(-1px);
}

.br-btn-outline {
    background: var(--card);
    color: var(--primary);
    border: 1.5px solid var(--primary);
}

.br-btn-outline:hover {
    background: rgba(37, 99, 235, 0.05);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.12);
}

.br-btn-primary {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 2px 10px rgba(37, 99, 235, 0.25);
}

.br-btn-primary:hover {
    background: var(--primary-hover);
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
}

/* ── Animations ── */
@keyframes br-ring-in {
    0% { opacity: 0; transform: scale(0.5); }
    100% { opacity: 1; transform: scale(1); }
}

@keyframes br-card-in {
    0% { opacity: 0; transform: translateY(16px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes br-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.3); }
    50% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
}

.br-icon-circle.pulse {
    animation: br-pulse 2s ease-in-out 1;
}

/* ── Print Styles ── */
@media print {
    body * { visibility: hidden; }
    .br-card, .br-card * { visibility: visible; }
    .br-card { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; }
    aside, header, .hero-banner, .br-hero, .br-footer-actions { display: none !important; }
}
</style>

<div class="br-page">
    <!-- Status Hero -->
    <div class="br-hero">
        <div class="br-icon-wrap">
            <div class="br-icon-ring" id="statusRing"></div>
            <div class="br-icon-circle" id="statusIconCircle">
                <i class="fas fa-circle-notch fa-spin"></i>
            </div>
        </div>
        <h2 class="br-title" id="resultTitle">กำลังโหลดข้อมูล...</h2>
        <p class="br-subtitle" id="resultSubtitle">กรุณารอสักครู่</p>

        <!-- Timeline Stepper -->
        <div id="timelineContainer" class="br-timeline" style="display:none;">
            <div class="br-step">
                <div class="br-step-dot" id="step1-dot"><i class="fas fa-paper-plane"></i></div>
                <div class="br-step-line"><div class="br-step-line-fill" id="line1"></div></div>
                <span class="br-step-label" id="step1-label">ยังไม่อนุมัติ</span>
            </div>
            <div class="br-step">
                <div class="br-step-dot" id="step2-dot"><i class="fas fa-check-double"></i></div>
                <div class="br-step-line"><div class="br-step-line-fill" id="line2"></div></div>
                <span class="br-step-label" id="step2-label">อนุมัติแล้ว</span>
            </div>
            <div class="br-step">
                <div class="br-step-dot" id="step3-dot"><i class="fas fa-door-closed"></i></div>
                <div class="br-step-line"><div class="br-step-line-fill" id="line3"></div></div>
                <span class="br-step-label" id="step3-label">เสร็จสิ้นการประชุม</span>
            </div>
            <div class="br-step">
                <div class="br-step-dot" id="step4-dot"><i class="fas fa-star"></i></div>
                <span class="br-step-label" id="step4-label">ประเมินแล้ว</span>
            </div>
        </div>
    </div>

    <!-- Ticket Card -->
    <div class="br-card" id="ticketCard">
        <div class="br-card-accent" id="cardAccent"></div>
        <div class="br-card-body">

            <!-- Reference ID & Status Badge -->
            <div class="br-ref-row">
                <div>
                    <div class="br-ref-label">รหัสอ้างอิงการจอง</div>
                    <div class="br-ref-id">#<span id="displayId">...</span></div>
                </div>
                <span class="br-badge" id="displayStatus">...</span>
            </div>

            <!-- Topic -->
            <div class="br-topic-label"><i class="fas fa-quote-left"></i> หัวข้อ/เรื่องการประชุม</div>
            <div class="br-topic-value" id="displayTitle">...</div>

            <!-- Date & Room Info -->
            <div class="br-info-grid">
                <div class="br-info-item">
                    <div class="br-info-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div>
                        <div class="br-info-label">วันและเวลาที่จอง</div>
                        <div class="br-info-value" id="displayDate">...</div>
                        <div class="br-info-sub" id="displayTime">...</div>
                    </div>
                </div>
                <div class="br-info-item">
                    <div class="br-info-icon"><i class="fas fa-door-open"></i></div>
                    <div>
                        <div class="br-info-label">ห้องประชุม</div>
                        <div class="br-info-value" id="displayRoom">...</div>
                    </div>
                </div>
            </div>

            <!-- Detail Fields -->
            <div class="br-details">
                <div class="br-detail-item">
                    <div class="br-detail-label"><i class="fas fa-user"></i> ผู้จอง</div>
                    <div class="br-detail-value" id="displayUser">...</div>
                </div>
                <div class="br-detail-item">
                    <div class="br-detail-label"><i class="fas fa-sitemap"></i> หน่วยงาน</div>
                    <div class="br-detail-value" id="displayDept">...</div>
                </div>
                <div class="br-detail-item">
                    <div class="br-detail-label"><i class="fas fa-users"></i> จำนวนผู้เข้าใช้</div>
                    <div class="br-detail-value" id="displayCount">...</div>
                </div>
                <div class="br-detail-item">
                    <div class="br-detail-label"><i class="fas fa-phone"></i> เบอร์ติดต่อ</div>
                    <div class="br-detail-value" id="displayPhone">...</div>
                </div>
            </div>

            <!-- Description (Optional) -->
            <div id="descContainer" class="br-note-section" style="display:none;">
                <div class="br-topic-label"><i class="fas fa-clipboard-list"></i> หมายเหตุเพิ่มเติม</div>
                <div class="br-note-box" id="displayDesc"></div>
            </div>

            <!-- Equipment (Optional) -->
            <div id="equipContainer" class="br-note-section" style="display:none;">
                <div class="br-topic-label"><i class="fas fa-tools"></i> อุปกรณ์ที่ต้องการ</div>
                <div class="br-note-box" id="displayEquip"></div>
            </div>

        </div>

        <!-- Footer -->
        <div class="br-footer">
            <div class="br-footer-tip">
                <i class="fas fa-info-circle"></i>
                โปรดเก็บรหัสอ้างอิงไว้เพื่อใช้สำหรับติดตามสถานะการจอง
            </div>
            <div class="br-footer-actions">
                <a href="dashboard.php" class="br-btn br-btn-outline">
                    <i class="fas fa-home"></i> กลับหน้าหลัก
                </a>
                <a href="dashboard.php?view=approve_list" class="br-btn br-btn-primary">
                    <i class="fas fa-list"></i> ดูรายการทั้งหมด
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const bookingId = <?php echo json_encode($bookingId); ?>;
    try {
        const data = await MeetQueue.api.fetch(`api/bookings.php?booking_id=${bookingId}`);
        if (data.success && data.bookings && data.bookings.length > 0) {
            renderBookingResult(data.bookings[0]);
        } else {
            showError('ไม่พบข้อมูลการจองนี้ หรือคุณไม่มีสิทธิ์เข้าถึง');
        }
    } catch (err) {
        showError('เกิดข้อผิดพลาดในการดึงข้อมูล');
    }
});

function renderBookingResult(booking) {
    const iconCircle = document.getElementById('statusIconCircle');
    const iconRing = document.getElementById('statusRing');
    const title = document.getElementById('resultTitle');
    const subtitle = document.getElementById('resultSubtitle');
    const badge = document.getElementById('displayStatus');
    const accent = document.getElementById('cardAccent');

    // Status config map
    const statusConfig = {
        pending: {
            icon: 'fa-paper-plane',
            iconBg: 'var(--warning-bg)', iconColor: 'var(--warning)',
            ringColor: 'var(--warning-border)',
            badgeBg: 'var(--warning-bg)', badgeColor: 'var(--warning)', badgeBorder: 'var(--warning-border)',
            badgeIcon: 'fa-clock', badgeText: 'รออนุมัติ',
            accentGrad: 'linear-gradient(90deg, #f59e0b, #fbbf24)',
            title: 'ส่งคำขอสำเร็จ',
            subtitle: 'ระบบได้รับข้อมูลการจองของคุณแล้ว และกำลังรอการพิจารณา'
        },
        approved: {
            icon: 'fa-check-double',
            iconBg: 'var(--success-bg)', iconColor: 'var(--success)',
            ringColor: 'var(--success-border)',
            badgeBg: 'var(--success-bg)', badgeColor: 'var(--success)', badgeBorder: 'var(--success-border)',
            badgeIcon: 'fa-check-circle', badgeText: 'อนุมัติแล้ว',
            accentGrad: 'linear-gradient(90deg, #16a34a, #4ade80)',
            title: 'การจองได้รับการอนุมัติ',
            subtitle: 'ห้องประชุมพร้อมสำหรับคุณแล้ว'
        },
        rejected: {
            icon: 'fa-times',
            iconBg: 'var(--danger-bg)', iconColor: 'var(--danger)',
            ringColor: 'var(--danger-border)',
            badgeBg: 'var(--danger-bg)', badgeColor: 'var(--danger)', badgeBorder: 'var(--danger-border)',
            badgeIcon: 'fa-times-circle', badgeText: 'ไม่อนุมัติ',
            accentGrad: 'linear-gradient(90deg, #dc2626, #f87171)',
            title: 'การขอประชุมถูกปฏิเสธ',
            subtitle: 'ขออภัย ไม่สามารถอนุมัติการประชุมนี้ได้'
        },
        cancelled: {
            icon: 'fa-ban',
            iconBg: 'var(--surface, #f1f5f9)', iconColor: 'var(--text-muted)',
            ringColor: 'var(--border)',
            badgeBg: 'var(--surface, #f1f5f9)', badgeColor: 'var(--text-muted)', badgeBorder: 'var(--border)',
            badgeIcon: 'fa-ban', badgeText: 'ยกเลิก',
            accentGrad: 'linear-gradient(90deg, #94a3b8, #cbd5e1)',
            title: 'การประชุมถูกยกเลิก',
            subtitle: 'รายการนี้ได้ถูกยกเลิกแล้ว'
        },
        completed: {
            icon: 'fa-calendar-check',
            iconBg: 'var(--success-bg)', iconColor: 'var(--success)',
            ringColor: 'var(--success-border)',
            badgeBg: 'var(--success-bg)', badgeColor: 'var(--success)', badgeBorder: 'var(--success-border)',
            badgeIcon: 'fa-check-circle', badgeText: 'เสร็จสิ้น',
            accentGrad: 'linear-gradient(90deg, #16a34a, #22c55e)',
            title: 'การประชุมเสร็จสิ้น',
            subtitle: 'ขอบคุณที่ใช้บริการห้องประชุม'
        }
    };

    const cfg = statusConfig[booking.status] || statusConfig.pending;

    // Apply status styling
    iconCircle.innerHTML = `<i class="fas ${cfg.icon}"></i>`;
    iconCircle.style.background = cfg.iconBg;
    iconCircle.style.color = cfg.iconColor;
    iconCircle.classList.add('pulse');
    iconRing.style.borderColor = cfg.ringColor;
    title.textContent = cfg.title;
    subtitle.textContent = cfg.subtitle;
    accent.style.background = cfg.accentGrad;

    badge.innerHTML = `<i class="fas ${cfg.badgeIcon}"></i> ${cfg.badgeText}`;
    badge.style.cssText = `background:${cfg.badgeBg}; color:${cfg.badgeColor}; border:1.5px solid ${cfg.badgeBorder};`;

    // Populate data
    document.getElementById('displayId').textContent = String(booking.id).padStart(6, '0');
    document.getElementById('displayTitle').textContent = booking.title;
    document.getElementById('displayDate').textContent = MeetQueue.utils.formatDateLong(booking.start_time);
    document.getElementById('displayTime').textContent = `${MeetQueue.utils.formatTime(booking.start_time)} - ${MeetQueue.utils.formatTime(booking.end_time)} น.`;
    document.getElementById('displayRoom').textContent = booking.is_external ? `(ภายนอก) ${booking.external_org}` : booking.room_name;
    document.getElementById('displayUser').textContent = (booking.first_name + ' ' + (booking.last_name || '')).trim();
    document.getElementById('displayDept').textContent = booking.department_name || '-';
    document.getElementById('displayCount').textContent = booking.participants_count;
    document.getElementById('displayPhone').textContent = booking.phone;

    if (booking.description) {
        document.getElementById('descContainer').style.display = '';
        document.getElementById('displayDesc').textContent = booking.description;
    }
    if (booking.equipments) {
        document.getElementById('equipContainer').style.display = '';
        document.getElementById('displayEquip').textContent = booking.equipments;
    }

    // Timeline logic
    if (booking.status !== 'rejected' && booking.status !== 'cancelled') {
        document.getElementById('timelineContainer').style.display = '';

        const now = new Date();
        const endTime = new Date(booking.end_time);
        const isCompleted = (booking.status === 'completed') || (booking.status === 'approved' && endTime < now);

        checkEvaluationStatus(booking.id).then(isEvaluated => {
            // Step 1 always active
            activateStep(1, 0);

            if (booking.status === 'approved' || isCompleted || isEvaluated) {
                activateStep(2, 250);
                fillLine(1, 200);
            }
            if (isCompleted || isEvaluated) {
                activateStep(3, 500);
                fillLine(2, 450);
            }
            if (isEvaluated) {
                activateStep(4, 750);
                fillLine(3, 700);
            }
        });
    }

    // Reveal card
    document.getElementById('ticketCard').style.animation = 'br-card-in 0.6s 0.15s ease forwards';
}

function activateStep(n, delay) {
    setTimeout(() => {
        const dot = document.getElementById(`step${n}-dot`);
        const label = document.getElementById(`step${n}-label`);
        if (dot) dot.classList.add('active');
        if (label) label.classList.add('active');
    }, delay);
}

function fillLine(n, delay) {
    setTimeout(() => {
        const line = document.getElementById(`line${n}`);
        if (line) line.classList.add('filled');
    }, delay);
}

async function checkEvaluationStatus(bookingId) {
    try {
        const res = await fetch(`api/reviews.php?booking_id=${bookingId}`);
        const result = await res.json();
        return result.has_review;
    } catch(e) { return false; }
}

function showError(msg) {
    const iconCircle = document.getElementById('statusIconCircle');
    const iconRing = document.getElementById('statusRing');
    document.getElementById('resultTitle').textContent = 'เกิดข้อผิดพลาด';
    document.getElementById('resultTitle').style.color = 'var(--danger)';
    document.getElementById('resultSubtitle').textContent = msg;
    iconCircle.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
    iconCircle.style.background = 'var(--danger-bg)';
    iconCircle.style.color = 'var(--danger)';
    iconRing.style.borderColor = 'var(--danger-border)';
    document.getElementById('cardAccent').style.background = 'linear-gradient(90deg, #dc2626, #f87171)';
}
</script>
