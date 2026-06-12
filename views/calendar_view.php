<?php
require_once 'api/config.php';
use App\Repository\BookingRepository;

$repo = new BookingRepository();
$rooms = \App\Core\Database::getInstance()->getConnection()->query("SELECT * FROM rooms")->fetchAll();

// Get upcoming bookings using Repository
$recent_bookings = $repo->getAll(['upcoming' => true]);
$recent_bookings = array_slice($recent_bookings, 0, 9);

$pending_count = 0;
if (($_SESSION['user_data']['role'] ?? 'user') === 'admin') {
    $pending_count = \App\Core\Database::getInstance()->getConnection()->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
}
$base_link = ($_SESSION['user_data']['role'] ?? 'user') === 'admin' ? 'dashboard.php?view=requests' : 'dashboard.php?view=status';
?>

<style>
    /* Robust Layout CSS to prevent Tailwind CDN caching issues */
    .dash-grid {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        max-width: 100%;
        box-sizing: border-box;
    }
    .dash-grid > div {
        min-width: 0;
    }
    
    /* Responsive room panel horizontal scroll */
    .room-panel {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        scrollbar-color: rgba(59,130,246,0.4) transparent;
        padding-bottom: 0.5rem;
    }
    .room-panel::-webkit-scrollbar { height: 6px; }
    .room-panel::-webkit-scrollbar-track { background: transparent; }
    .room-panel::-webkit-scrollbar-thumb { background: rgba(59,130,246,0.5); border-radius: 10px; }
    .room-panel::-webkit-scrollbar-thumb:hover { background: rgba(59,130,246,0.8); }

    /* Horizontal Room Cards Layout */
    .room-card-wrapper {
        display: flex;
        flex-direction: row;
        gap: 0.75rem;
        width: max-content;
        box-sizing: border-box;
    }
    
    .room-card {
        background: white;
        border-radius: 1rem;
        padding: 1rem;
        border: 1px solid rgba(59, 130, 246, 0.2);
        width: 280px;
        flex-shrink: 0;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .room-card:hover {
        border-color: rgba(59, 130, 246, 0.6);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        transform: translateY(-2px);
    }
    .room-card.active {
        border-color: var(--primary);
        box-shadow: 0 0 0 1px var(--primary), 0 4px 12px rgba(15, 23, 42, 0.1);
        background: var(--white);
    }
    .room-card.active .room-card-icon {
        background: var(--primary);
        color: white;
    }
    .room-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
        gap: 0.5rem;
    }
    .room-card-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        flex: 1;
    }
    .room-card-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        background: linear-gradient(to bottom right, var(--border), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-weight: bold;
        font-size: 0.875rem;
        flex-shrink: 0;
    }
    .room-card-text {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .room-card-title {
        font-weight: 700;
        color: var(--primary);
        font-size: 0.875rem;
        line-height: 1.25;
        white-space: normal;
        word-break: break-word;
        margin: 0 0 0.25rem 0;
    }
    .room-card-subtitle {
        font-size: 0.65rem;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0;
    }
    .room-card-badge {
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        flex-shrink: 0;
    }
    .room-card-footer {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 500;
        flex-wrap: wrap;
    }
    
    .bottom-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
        gap: var(--space-md);
        margin-top: var(--space-md);
        min-width: 0;
</style>

<style>
    /* Make calendar days clearly clickable */
    .fc-daygrid-day-frame {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .fc-daygrid-day-frame:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    /* ── Monthly Summary Section ── */
    .monthly-summary { margin-bottom: 1.25rem; }
    .monthly-summary-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;
    }
    .monthly-summary-title {
        font-size: 0.85rem; font-weight: 700; color: var(--primary);
        display: flex; align-items: center; gap: 0.5rem;
    }
    .monthly-summary-title i { color: var(--secondary); }
    .monthly-summary-sub { font-size: 0.7rem; color: var(--text-muted); font-weight: 500; }

    .stat-cards {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.6rem;
        margin-bottom: 0.85rem;
    }
    @media (max-width: 640px) { .stat-cards { grid-template-columns: repeat(2, 1fr); } }

    .stat-card {
        border-radius: 1rem; padding: 0.85rem 1rem;
        display: flex; align-items: center; gap: 0.7rem;
        position: relative; overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    .stat-card::after {
        content: ''; position: absolute; top: -18px; right: -18px;
        width: 60px; height: 60px; border-radius: 50%;
        background: rgba(255,255,255,0.15);
    }
    .stat-card-total    { background: linear-gradient(135deg, #3B82F6, #2563EB); }
    .stat-card-approved { background: linear-gradient(135deg, #10B981, #059669); }
    .stat-card-pending  { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .stat-card-rejected { background: linear-gradient(135deg, #EF4444, #DC2626); }

    .stat-card-icon {
        width: 2.2rem; height: 2.2rem; border-radius: 0.6rem;
        background: rgba(255,255,255,0.25);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.9rem; flex-shrink: 0; color: #fff;
    }
    .stat-card-body { min-width: 0; }
    .stat-card-num {
        font-size: 1.4rem; font-weight: 900; line-height: 1;
        letter-spacing: -0.5px; color: #fff;
    }
    .stat-card-label { font-size: 0.62rem; font-weight: 600; opacity: 0.85; color: #fff; margin-top: 2px; white-space: nowrap; }

    .monthly-chart-wrap {
        background: white; border-radius: 1rem; padding: 0.85rem 1rem 0.6rem;
        border: 1px solid rgba(59,130,246,0.2);
        box-shadow: 0 2px 8px rgba(15,23,42,0.04);
    }
</style>

<!-- Monthly Summary -->
<div class="monthly-summary">
    <div class="monthly-summary-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
        <div class="monthly-summary-title" id="summaryTitleText">
            <i class="fas fa-chart-bar"></i> สรุปการจองประจำเดือน
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <!-- Segmented Control for Mode selection -->
            <div class="summary-mode-selector" style="display: flex; background: rgba(59,130,246,0.08); padding: 0.2rem; border-radius: 0.6rem; border: 1px solid rgba(59,130,246,0.15);">
                <button type="button" class="mode-btn" data-mode="day" style="border: none; background: transparent; padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 0.45rem; cursor: pointer; color: var(--text-muted); transition: all 0.2s;">รายวัน</button>
                <button type="button" class="mode-btn active" data-mode="month" style="border: none; background: var(--primary); padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 0.45rem; cursor: pointer; color: white; transition: all 0.2s;">รายเดือน</button>
                <button type="button" class="mode-btn" data-mode="year" style="border: none; background: transparent; padding: 0.35rem 0.75rem; font-size: 0.78rem; font-weight: 700; border-radius: 0.45rem; cursor: pointer; color: var(--text-muted); transition: all 0.2s;">รายปี</button>
            </div>
            <!-- Modern Selector Wrapper -->
            <div class="month-selector-wrap" style="display: flex; align-items: center; gap: 0.5rem; background: var(--sidebar-bg, #f8f9fa); padding: 0.25rem 0.5rem; border-radius: 0.75rem; border: 1px solid var(--border);">
                <button id="prevMonthBtn" class="month-nav-btn" style="border: none; background: transparent; cursor: pointer; color: var(--primary); padding: 0.25rem 0.4rem; display: flex; align-items: center; font-size: 0.8rem; transition: transform 0.2s;" onmouseover="this.style.transform='translateX(-2px)'" onmouseout="this.style.transform=''"><i class="fas fa-chevron-left"></i></button>
                
                <div class="month-picker-container" style="position: relative; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                    <i class="far fa-calendar-alt" style="color: var(--secondary); font-size: 0.85rem;"></i>
                    <span class="monthly-summary-sub" id="summaryMonthLabel" style="font-weight: 700; font-size: 0.85rem; color: var(--primary); margin: 0; padding: 0;">กำลังโหลด...</span>
                    <input type="text" id="summaryMonthPicker" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                </div>
                
                <button id="nextMonthBtn" class="month-nav-btn" style="border: none; background: transparent; cursor: pointer; color: var(--primary); padding: 0.25rem 0.4rem; display: flex; align-items: center; font-size: 0.8rem; transition: transform 0.2s;" onmouseover="this.style.transform='translateX(2px)'" onmouseout="this.style.transform=''"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
    <div class="stat-cards">
        <a href="<?= $base_link ?>" class="stat-card stat-card-total" style="text-decoration: none; cursor: pointer;">
            <div class="stat-card-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-num" id="stat-total">–</div>
                <div class="stat-card-label">รายการทั้งหมด</div>
            </div>
        </a>
        <a href="<?= $base_link ?>&status=approved" class="stat-card stat-card-approved" style="text-decoration: none; cursor: pointer;">
            <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-num" id="stat-approved">–</div>
                <div class="stat-card-label">อนุมัติแล้ว</div>
            </div>
        </a>
        <a href="<?= $base_link ?>&status=pending" class="stat-card stat-card-pending" style="text-decoration: none; cursor: pointer;">
            <div class="stat-card-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-num" id="stat-pending">–</div>
                <div class="stat-card-label">รออนุมัติ</div>
            </div>
        </a>
        <a href="<?= $base_link ?>&status=rejected" class="stat-card stat-card-rejected" style="text-decoration: none; cursor: pointer;">
            <div class="stat-card-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-card-body">
                <div class="stat-card-num" id="stat-rejected">–</div>
                <div class="stat-card-label">ปฏิเสธ</div>
            </div>
        </a>
    </div>
    <div class="monthly-chart-wrap" style="min-height: 180px;">
        <div id="monthlyBarChart"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>
<script>
(function() {
    let currentDate = new Date();
    let currentMode = 'month'; // 'day', 'month', 'year'
    let chartInstance = null;
    let summaryDatePicker = null;

    function initSummaryDatePicker() {
        if (summaryDatePicker) {
            summaryDatePicker.destroy();
        }

        summaryDatePicker = flatpickr("#summaryMonthPicker", {
            locale: "th",
            defaultDate: currentDate,
            disableMobile: true,
            formatDate: (date) => {
                const yBE = date.getFullYear() + 543;
                if (currentMode === 'day') {
                    const thaiMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
                    return date.getDate() + ' ' + thaiMonths[date.getMonth()] + ' ' + yBE;
                } else if (currentMode === 'month') {
                    const thaiFullMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
                    return thaiFullMonths[date.getMonth()] + ' ' + yBE;
                } else {
                    return 'ปี ' + yBE;
                }
            },
            onChange: (selectedDates) => {
                if (selectedDates.length > 0) {
                    currentDate = selectedDates[0];
                    updateSummaryView();
                }
            },
            onReady: function(selectedDates, dateStr, instance) {
                const yearInput = instance.currentYearElement;
                if (yearInput) {
                    const nativeInputValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
                    Object.defineProperty(yearInput, 'value', {
                        get: function() { return nativeInputValue.get.call(this); },
                        set: function(val) {
                            let newVal = parseInt(val, 10);
                            if (newVal > 1900 && newVal < 2400) { newVal += 543; }
                            nativeInputValue.set.call(this, newVal);
                        }
                    });
                    yearInput.value = instance.currentYear;

                    const origChangeYear = instance.changeYear;
                    instance.changeYear = function(year, jump, step) {
                        if (year > 2400) { year -= 543; }
                        origChangeYear.call(instance, year, jump, step);
                    };
                }
            }
        });
    }

    function updateSummaryView() {
        const y = currentDate.getFullYear();
        const mo = currentDate.getMonth();
        const d = currentDate.getDate();

        let start = '', end = '';
        const thaiFullMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        
        if (currentMode === 'day') {
            const mStr = String(mo + 1).padStart(2, '0');
            const dStr = String(d).padStart(2, '0');
            start = `${y}-${mStr}-${dStr}`;
            end   = `${y}-${mStr}-${dStr}`;
            document.getElementById('summaryMonthLabel').textContent = d + ' ' + thaiFullMonths[mo] + ' ' + (y + 543);
            document.getElementById('summaryTitleText').innerHTML = '<i class="fas fa-chart-bar"></i> สรุปการจองรายวัน';
        } else if (currentMode === 'month') {
            const mStr = String(mo + 1).padStart(2, '0');
            const lastDay = new Date(y, mo + 1, 0).getDate();
            start = `${y}-${mStr}-01`;
            end   = `${y}-${mStr}-${String(lastDay).padStart(2, '0')}`;
            document.getElementById('summaryMonthLabel').textContent = thaiFullMonths[mo] + ' ' + (y + 543);
            document.getElementById('summaryTitleText').innerHTML = '<i class="fas fa-chart-bar"></i> สรุปการจองประจำเดือน';
        } else { // 'year'
            start = `${y}-01-01`;
            end   = `${y}-12-31`;
            document.getElementById('summaryMonthLabel').textContent = 'ปี ' + (y + 543);
            document.getElementById('summaryTitleText').innerHTML = '<i class="fas fa-chart-bar"></i> สรุปการจองประจำปี';
        }

        if (summaryDatePicker) {
            summaryDatePicker.setDate(currentDate, false);
        }

        // Helper animate
        function animateCount(el, target) {
            let cur=0; const step=Math.max(1,Math.ceil(target/20));
            const id=setInterval(()=>{cur=Math.min(cur+step,target); el.textContent=cur; if(cur>=target)clearInterval(id);},40);
        }

        // Fetch data based on mode
        let fetchPromise;
        if (currentMode === 'day') {
            fetchPromise = fetch('api/bookings.php?start=' + start + ' 00:00:00&end=' + end + ' 23:59:59')
                .then(r => r.json())
                .then(data => {
                    const bookings = data.bookings || [];
                    let total=0, approved=0, pending=0, rejected=0;
                    
                    const hours = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'];
                    const approveds = Array(hours.length).fill(0);
                    const pendings = Array(hours.length).fill(0);
                    const rejecteds = Array(hours.length).fill(0);

                    bookings.forEach(b => {
                        if (b.status === 'cancelled') return;
                        total++;
                        
                        const timePart = b.start_time.split(' ')[1];
                        if (timePart) {
                            const hr = parseInt(timePart.split(':')[0], 10);
                            const bucketIdx = hr - 8;
                            if (bucketIdx >= 0 && bucketIdx < hours.length) {
                                if (b.status === 'approved' || b.status === 'completed') {
                                    approveds[bucketIdx]++;
                                    approved++;
                                } else if (b.status === 'pending') {
                                    pendings[bucketIdx]++;
                                    pending++;
                                } else if (b.status === 'rejected') {
                                    rejecteds[bucketIdx]++;
                                    rejected++;
                                }
                            }
                        }
                    });

                    return {
                        total, approved, pending, rejected,
                        categories: hours,
                        series: [
                            { name: 'อนุมัติ', data: approveds },
                            { name: 'รออนุมัติ', data: pendings },
                            { name: 'ปฏิเสธ', data: rejecteds }
                        ]
                    };
                });
        } else if (currentMode === 'month') {
            const lastDay = new Date(y, mo + 1, 0).getDate();
            fetchPromise = fetch('api/calendar_daycounts.php?start=' + start + '&end=' + end)
                .then(r => r.json())
                .then(counts => {
                    let total=0, approved=0, pending=0, rejected=0;
                    const labels=[], approveds=[], pendings=[], rejecteds=[];
                    
                    for (let d = 1; d <= lastDay; d++) {
                        const key = y+'-'+String(mo+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
                        const c = counts[key] || {total:0,approved:0,pending:0,rejected:0};
                        labels.push(d);
                        approveds.push(c.approved);
                        pendings.push(c.pending);
                        rejecteds.push(c.rejected);
                        total+=c.total; approved+=c.approved; pending+=c.pending; rejected+=c.rejected;
                    }

                    return {
                        total, approved, pending, rejected,
                        categories: labels,
                        series: [
                            { name: 'อนุมัติ', data: approveds },
                            { name: 'รออนุมัติ', data: pendings },
                            { name: 'ปฏิเสธ', data: rejecteds }
                        ]
                    };
                });
        } else { // 'year'
            fetchPromise = fetch('api/calendar_daycounts.php?start=' + start + '&end=' + end)
                .then(r => r.json())
                .then(counts => {
                    let total=0, approved=0, pending=0, rejected=0;
                    const months = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
                    const approveds = Array(12).fill(0);
                    const pendings = Array(12).fill(0);
                    const rejecteds = Array(12).fill(0);

                    Object.keys(counts).forEach(key => {
                        const parts = key.split('-');
                        if (parts.length === 3) {
                            const monthIdx = parseInt(parts[1], 10) - 1;
                            if (monthIdx >= 0 && monthIdx < 12) {
                                const c = counts[key];
                                approveds[monthIdx] += c.approved;
                                pendings[monthIdx] += c.pending;
                                rejecteds[monthIdx] += c.rejected;
                                total+=c.total; approved+=c.approved; pending+=c.pending; rejected+=c.rejected;
                            }
                        }
                    });

                    return {
                        total, approved, pending, rejected,
                        categories: months,
                        series: [
                            { name: 'อนุมัติ', data: approveds },
                            { name: 'รออนุมัติ', data: pendings },
                            { name: 'ปฏิเสธ', data: rejecteds }
                        ]
                    };
                });
        }

        fetchPromise.then(res => {
            animateCount(document.getElementById('stat-total'),    res.total);
            animateCount(document.getElementById('stat-approved'), res.approved);
            animateCount(document.getElementById('stat-pending'),  res.pending);
            animateCount(document.getElementById('stat-rejected'), res.rejected);

            // Update base link of cards dynamically
            const base_link = "?view=approve_list&from=" + start + "&to=" + end;
            document.querySelector('.stat-card-total').setAttribute('href', base_link);
            document.querySelector('.stat-card-approved').setAttribute('href', base_link + '&status=approved');
            document.querySelector('.stat-card-pending').setAttribute('href', base_link + '&status=pending');
            document.querySelector('.stat-card-rejected').setAttribute('href', base_link + '&status=rejected');

            var options = {
                series: res.series,
                chart: {
                    type: 'bar',
                    height: 160,
                    stacked: true,
                    toolbar: { show: false },
                    fontFamily: "'Outfit','Sarabun',sans-serif"
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 3,
                        columnWidth: currentMode === 'day' ? '45%' : '55%',
                        dataLabels: {
                            total: {
                                enabled: true,
                                style: {
                                    fontSize: '9px',
                                    fontWeight: 900,
                                    color: 'var(--primary)'
                                }
                            }
                        }
                    },
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '8px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    },
                    formatter: function (val) {
                        return val > 0 ? val : '';
                    }
                },
                colors: ['#10b981', '#f59e0b', '#ef4444'],
                xaxis: {
                    categories: res.categories,
                    labels: {
                        style: {
                            fontSize: '8px',
                            colors: '#64748b'
                        }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '8px',
                            colors: '#64748b'
                        }
                    }
                },
                grid: {
                    borderColor: 'rgba(59,130,246,0.1)',
                    strokeDashArray: 4,
                    padding: { top: 0, right: 0, bottom: 0, left: 0 }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '9px',
                    markers: { radius: 3 }
                },
                tooltip: {
                    theme: 'light',
                    style: {
                        fontSize: '10px'
                    }
                }
            };

            if (chartInstance) {
                chartInstance.destroy();
            }
            chartInstance = new ApexCharts(document.getElementById('monthlyBarChart'), options);
            chartInstance.render();
        }).catch(err => {
            console.error(err);
            ['stat-total','stat-approved','stat-pending','stat-rejected'].forEach(id=>{
                document.getElementById(id).textContent='0';
            });
        });
    }

    // Nav buttons
    document.getElementById('prevMonthBtn').addEventListener('click', function() {
        if (currentMode === 'day') {
            currentDate.setDate(currentDate.getDate() - 1);
        } else if (currentMode === 'month') {
            currentDate.setMonth(currentDate.getMonth() - 1);
        } else {
            currentDate.setFullYear(currentDate.getFullYear() - 1);
        }
        updateSummaryView();
    });

    document.getElementById('nextMonthBtn').addEventListener('click', function() {
        if (currentMode === 'day') {
            currentDate.setDate(currentDate.getDate() + 1);
        } else if (currentMode === 'month') {
            currentDate.setMonth(currentDate.getMonth() + 1);
        } else {
            currentDate.setFullYear(currentDate.getFullYear() + 1);
        }
        updateSummaryView();
    });

    // Mode Selector Switcher
    document.querySelectorAll('.summary-mode-selector .mode-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.summary-mode-selector .mode-btn').forEach(b => {
                b.classList.remove('active');
                b.style.color = 'var(--text-muted)';
                b.style.background = 'transparent';
            });
            this.classList.add('active');
            this.style.color = 'white';
            this.style.background = 'var(--primary)';
            
            currentMode = this.getAttribute('data-mode');
            initSummaryDatePicker();
            updateSummaryView();
        });
    });

    // Initial setups
    initSummaryDatePicker();
    updateSummaryView();
})();
</script>

<div class="dash-grid">
    
    <!-- Left Panel: Room List (3 columns) -->
    <div class="flex flex-col gap-4" style="padding-right: 0.5rem;">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h2 class="text-xl font-bold text-primary">ห้องประชุมวันนี้</h2>
                <p class="text-xs text-text-muted">สถานะของวันนี้ทั้งหมด <?= count($rooms) ?> ห้อง</p>
            </div>
            <button onclick="document.getElementById('filterContainer').classList.toggle('hidden')" class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-primary shadow-sm hover:bg-slate-200 transition-colors border border-blue-400/30 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-400">
                <i class="fas fa-sliders-h text-xs"></i>
            </button>
        </div>
        
        <div id="filterContainer" class="hidden transition-all duration-300">
            <!-- Filter Pills -->
            <div class="flex gap-1.5 overflow-x-auto pb-1 pt-1" id="statusFilterContainer">
                <button data-filter="all" class="filter-btn filter-pill filter-pill-active">ทั้งหมด</button>
                <button data-filter="ว่าง" class="filter-btn filter-pill filter-pill-inactive">ว่าง</button>
                <button data-filter="ไม่ว่าง" class="filter-btn filter-pill filter-pill-inactive">ไม่ว่าง</button>
                <button data-filter="บางส่วน" class="filter-btn filter-pill filter-pill-inactive">บางส่วน</button>
            </div>

            <!-- Search -->
            <div class="search-field" style="margin-top: 0.6rem; margin-bottom: 0.5rem;">
                <i class="fas fa-search"></i>
                <input type="text" id="roomSearchInput" placeholder="ค้นหาห้องประชุม...">
            </div>
        </div>
        
        <!-- Check Room Availability Widget -->
        <div class="dash-card shadow-sm border border-slate-200/50" style="padding: 1rem; border-radius: 1rem; background: var(--card-bg); margin-bottom: 0.5rem; transition: all 0.3s;">
            <h3 class="font-bold text-primary" style="font-size: 0.85rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.4rem;">
                <i class="fas fa-search-location text-[var(--secondary)]"></i> ตรวจสอบวันว่างของห้อง
            </h3>
            <div class="flex flex-col gap-2">
                <!-- Dropdown Select Room -->
                <select id="availabilityRoomSelect" class="w-full text-xs font-bold text-primary p-2.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-400 cursor-pointer bg-white" style="transition: all 0.2s; font-family: inherit;">
                    <option value="">-- เลือกห้องประชุม --</option>
                    <?php foreach($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Month Picker for Availability -->
                <div class="relative mt-1" id="availabilityMonthPickerContainer" style="display: none;">
                    <i class="far fa-calendar-alt text-slate-400" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.8rem; pointer-events: none;"></i>
                    <input type="text" id="availabilityMonthPicker" class="w-full text-xs font-bold text-primary p-2.5 pl-8 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white" placeholder="เลือกเดือนประจำปี พ.ศ.">
                </div>
            </div>

            <!-- Availability Results List -->
            <div id="availabilityResults" class="mt-3" style="display: none;">
                <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">วันว่างในเดือนนี้:</div>
                <div id="availabilityDaysGrid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; max-height: 200px; overflow-y: auto; padding: 2px;">
                    <!-- Days will be rendered here dynamically -->
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.65rem; justify-content: center;">
                    <div style="display: flex; align-items: center; gap: 0.25rem;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span> ว่าง</div>
                    <div style="display: flex; align-items: center; gap: 0.25rem;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span> มีการจอง</div>
                </div>
            </div>
        </div>

        <!-- Room Cards -->
        <div class="room-panel">
        <div class="room-card-wrapper">
            <?php foreach($rooms as $index => $room): 
                $statusColors = ['color: #1E8E3E; background: #E6F4EA;', 'color: #D93025; background: #FCE8E6;', 'color: #F29900; background: #FEF7E0;'];
                $statusText = ['ว่าง', 'ไม่ว่าง', 'บางส่วน'];
                $rand = $index % 3;
            ?>
            <div class="room-card relative group" data-room-id="<?= $room['id'] ?>" data-status="<?= $statusText[$rand] ?>" onclick="filterCalendarByRoom(<?= $room['id'] ?>, this)">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-300 to-[var(--primary)] opacity-0 group-hover:opacity-100 transition-opacity" style="border-radius: 1rem 0 0 1rem;"></div>
                
                <div class="room-card-header">
                    <div class="room-card-info">
                        <div class="room-card-icon text-lg font-black">
                            <?= !empty($room['room_number']) ? htmlspecialchars($room['room_number']) : '<i class="fas fa-building text-base"></i>' ?>
                        </div>
                        <div class="room-card-text">
                            <h4 class="room-card-title" title="<?= htmlspecialchars($room['name']) ?>"><?= htmlspecialchars($room['name']) ?></h4>
                            <p class="room-card-subtitle">อาคารหลัก ชั้น 1</p>
                        </div>
                    </div>
                    <span class="room-card-badge" style="<?= $statusColors[$rand] ?>">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background-color: currentColor;"></span>
                        <?= $statusText[$rand] ?>
                    </span>
                </div>
                
                <div class="room-card-footer">
                    <span style="display: flex; align-items: center; gap: 0.25rem; white-space: nowrap;">
                        <i class="fas fa-users" style="color: var(--secondary);"></i> <?= $room['capacity'] ?> คน
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.25rem; white-space: nowrap;">
                        <i class="fas fa-tv" style="color: var(--secondary);"></i> TV/Projector
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        </div><!-- /.room-panel -->
    </div>
    
    <!-- Right Panel (9 columns) -->
    <div class="flex flex-col min-w-0 w-full">
        
        <!-- Top: Calendar Section -->
        <div class="dash-card flex-grow relative overflow-hidden mb-6 w-full box-border <?= (($_SESSION['user_data']['role'] ?? 'user') === 'admin') ? 'is-admin-view' : 'is-user-view' ?>">
            <!-- Decorative accent -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-[var(--secondary)]/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
            
            <div class="flex flex-wrap justify-between items-center mb-6 relative z-10 gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-primary flex items-center gap-2 truncate">
                        <i class="fas fa-calendar-alt text-[var(--secondary)]"></i> ปฏิทินการจองรวม
                    </h2>
                    <p class="text-xs text-text-muted mt-1">อัปเดตแบบ Real-time</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                    <?php if (($_SESSION['user_data']['role'] ?? 'user') === 'admin'): ?>
                    <a href="dashboard.php?view=approve_list" class="cal-btn cal-btn-outline">
                        <i class="fas fa-clipboard-check"></i>
                        ขออนุมัติการจอง
                        <?php if($pending_count > 0): ?>
                        <span class="cal-btn-badge"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    <a href="dashboard.php?view=book" class="cal-btn cal-btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        จองห้องประชุม
                    </a>
                </div>
            </div>
            <div id="calendar" class="relative z-10"></div>
        </div>
        
        <!-- Bottom Cards -->
        <div class="grid grid-cols-1 gap-6 mt-6">
            <!-- Status / Timeline -->
            <div class="dash-card flex flex-col">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="font-bold text-primary text-lg">การประชุมที่จะถึง</h3>
                    <a href="dashboard.php?view=approve_list" class="text-xs font-bold text-[var(--secondary)] hover:text-primary transition-colors">ดูทั้งหมด</a>
                </div>
                
                <div class="flex-grow">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-y-8 gap-x-12 relative pl-6 border-l-2 border-slate-200">
                        <?php 
                        $thai_months = [
                            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
                            7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
                        ];
                        $status_map = [
                            'pending' => 'รออนุมัติ',
                            'approved' => 'อนุมัติแล้ว',
                            'rejected' => 'ปฏิเสธ',
                            'cancelled' => 'ยกเลิก',
                            'completed' => 'เสร็จสิ้น'
                        ];
                        // Show more bookings since we have more space
                        $extended_recent = array_slice($recent_bookings, 0, 9);
                        foreach($extended_recent as $i => $rb): 
                            $statusColors = [
                                'approved' => ['bg-[#10b981]', 'text-[#10b981]'],
                                'pending' => ['bg-[#f59e0b]', 'text-[#f59e0b]'],
                                'rejected' => ['bg-[#ef4444]', 'text-[#ef4444]'],
                                'completed' => ['bg-blue-600', 'text-primary']
                            ];
                            $color = $statusColors[$rb['status']] ?? ['bg-gray-400', 'text-gray-400'];
                        ?>
                        <div class="relative">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full <?= $color[0] ?> ring-4 ring-white"></div>
                            <h4 class="text-sm font-bold text-primary"><?= htmlspecialchars($rb['title']) ?></h4>
                            <p class="text-[0.7rem] font-bold text-[var(--secondary)] mt-0.5">
                                <?php
                                $time = strtotime($rb['start_time']);
                                $thai_month = $thai_months[(int)date('n', $time)] ?? '';
                                $formatted_date = date('j ', $time) . $thai_month . ' ' . (date('Y', $time) + 543);
                                ?>
                                <i class="far fa-calendar-alt mr-1"></i> <?= $formatted_date ?>
                                <i class="far fa-clock ml-2 mr-1"></i> <?= date('H:i', strtotime($rb['start_time'])) ?> น.
                            </p>
                            <p class="text-xs text-text-muted mt-0.5">
                                <?= $rb['room_name'] ?? 'ภายนอก' ?> • <?= $rb['first_name'] ?>
                            </p>
                            <p class="text-[0.65rem] font-semibold <?= $color[1] ?> mt-1 uppercase tracking-wider">
                                <?= $status_map[$rb['status']] ?? $rb['status'] ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(empty($recent_bookings)): ?>
                            <p class="text-sm text-text-muted">ไม่มีประวัติการจอง</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Compact Calendar Styling for Dashboard */
    #calendar {
        min-height: 400px;
    }
    .fc .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 700;
        color: var(--text-main);
    }
    .fc .fc-button-primary {
        background: var(--sidebar-bg);
        border: 1px solid rgba(59, 130, 246, 0.3);
        color: var(--text-muted);
        text-transform: capitalize;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 0.4rem 0.8rem;
    }
    .fc .fc-button-primary:not(:disabled):active,
    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
        color: white !important;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid {
        border-color: rgba(59, 130, 246, 0.2);
    }
    .fc-col-header-cell-cushion {
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.8rem;
        padding: 0.5rem !important;
    }
    .fc-daygrid-day-number {
        font-weight: 700;
        color: var(--primary);
    }
    
    /* Weekend / Holiday Styling */
    .fc-day-sun, .fc-day-public-holiday {
        background-color: rgba(239, 68, 68, 0.03) !important;
    }
    .fc-day-sat {
        background-color: rgba(239, 68, 68, 0.03) !important;
    }
    .fc-day-sun .fc-col-header-cell-cushion,
    .fc-day-sun .fc-daygrid-day-number,
    .fc-day-public-holiday .fc-col-header-cell-cushion,
    .fc-day-public-holiday .fc-daygrid-day-number,
    .fc-day-sat .fc-col-header-cell-cushion,
    .fc-day-sat .fc-daygrid-day-number {
        color: #ef4444 !important; /* Red for Sunday, Saturday, and Holidays */
    }

    /* Hover effect for day cells in Month view to indicate they are clickable */
    .fc-daygrid-day-frame {
        transition: background-color 0.2s ease;
        cursor: pointer;
    }
    .fc-daygrid-day-frame:hover {
        background-color: rgba(59, 130, 246, 0.15) !important;
    }

    /* Make event pills visible but click-through in admin month view so clicks go to Gantt chart */
    .is-admin-view .fc-dayGridMonth-view .fc-event,
    .is-admin-view .fc-dayGridMonth-view .fc-daygrid-more-link {
        pointer-events: none !important;
    }

    /* Ensure day events area doesn't block clicks in admin month view */
    .is-admin-view .fc-dayGridMonth-view .fc-daygrid-day-events {
        pointer-events: none;
    }
    .is-admin-view .fc-dayGridMonth-view .fc-daygrid-day-bg {
        pointer-events: none;
    }

    .fc-event {
        border: none !important;
        border-radius: 0.75rem !important;
        padding: 4px 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .fc-event-main {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Override fc-daygrid-day-top to hold total badge + date ── */
    .fc-dayGridMonth-view .fc-daygrid-day-top {
        display: flex !important;
        flex-direction: row !important;
        direction: ltr !important;
        align-items: flex-start;
        justify-content: space-between;
        padding: 3px 4px 2px;
    }
    .fc-dayGridMonth-view .fc-daygrid-day-number {
        order: 2;          /* push date to the right */
        padding: 0 !important;
        font-size: 0.8rem;
        line-height: 1.4;
    }

    /* Total badge placeholder (top-left) */
    .day-total-placeholder {
        order: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 26px;
        height: 20px;
        padding: 0 5px;
        border-radius: 5px;
        font-size: 0.7rem;
        font-weight: 800;
        background: #DBEAFE;
        color: #1D4ED8;
        line-height: 1;
        visibility: hidden;   /* hidden until counts arrive */
        pointer-events: none; /* let clicks pass through to day cell */
    }
    .day-total-placeholder.has-data {
        visibility: visible;
    }

    /* Holiday row — full width, centered, between top and bottom */
    .day-holiday-row {
        width: 100%;
        text-align: center;
        font-size: 0.6rem;
        color: #ef4444;
        font-weight: 700;
        padding: 1px 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex-shrink: 0;
        pointer-events: none; /* let clicks pass through to day cell */
    }

    /* Day-count badge layout */
    .day-counts {
        display: flex;
        flex-direction: column;
        margin-top: auto;   /* push to bottom of frame */
        pointer-events: none; /* let clicks pass through to day cell */
    }
    .day-counts-bottom {
        display: flex;
        width: 100%;
        gap: 1px;
        padding: 0 2px 3px;
    }
    .day-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: 0;
        flex: 1;
        height: 18px;
        font-size: 0.65rem;
        pointer-events: none; /* let clicks pass through to day cell */
    }
    .day-badge-approved { background: #DCFCE7; color: #166534; }
    .day-badge-pending  { background: #FEF9C3; color: #854D0E; }
    .day-badge-rejected { background: #FEE2E2; color: #991B1B; }
</style>

<!-- Event Detail Modal -->
<div id="eventModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/40 transition-opacity backdrop-blur-[2px]" id="modalBackdrop"></div>
    <div class="flex min-h-full items-center justify-center p-4 text-center" style="padding: 1.5rem;">
        <div class="relative transform overflow-hidden rounded-3xl bg-white/95 backdrop-blur-xl text-left shadow-2xl transition-all w-full max-w-2xl border border-white/60 ring-1 ring-black/5">
            <div style="padding: 2rem;">
                <div class="flex items-start justify-between border-b border-blue-600/10 pb-6 mb-6" style="padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="flex items-center gap-5" style="gap: 1.25rem;">
                        <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-200 to-blue-400 shadow-inner" style="width: 4rem; height: 4rem;">
                            <i class="fas fa-calendar-check text-primary text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold leading-tight text-primary mb-2" id="modalTitle" style="margin-bottom: 0.5rem;">รายละเอียดการจอง</h3>
                            <span id="modalStatus" class="inline-block px-3 py-1 text-xs font-bold rounded-full tracking-wide">สถานะ</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8" style="row-gap: 1.5rem; column-gap: 2rem;">
                    <div class="flex items-start gap-4" style="gap: 1rem;">
                        <div class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-200/50" style="width: 2.5rem; height: 2.5rem;">
                            <i class="fas fa-door-open text-text-muted"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-text-muted uppercase tracking-wider mb-1" style="margin-bottom: 0.25rem;">ห้องประชุม</p>
                            <p class="text-base font-semibold text-primary" id="modalRoom">N/A</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4" style="gap: 1rem;">
                        <div class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-200/50" style="width: 2.5rem; height: 2.5rem;">
                            <i class="fas fa-clock text-text-muted"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-text-muted uppercase tracking-wider mb-1" style="margin-bottom: 0.25rem;">วันและเวลา</p>
                            <p class="text-base font-semibold text-primary" id="modalTime">N/A</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4" style="gap: 1rem;">
                        <div class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-200/50" style="width: 2.5rem; height: 2.5rem;">
                            <i class="fas fa-users text-text-muted"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-text-muted uppercase tracking-wider mb-1" style="margin-bottom: 0.25rem;">จำนวนผู้เข้าร่วม</p>
                            <p class="text-base font-semibold text-primary" id="modalParticipants">N/A</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4" style="gap: 1rem;">
                        <div class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-200/50" style="width: 2.5rem; height: 2.5rem;">
                            <i class="fas fa-user-circle text-text-muted"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-text-muted uppercase tracking-wider mb-1" style="margin-bottom: 0.25rem;">ผู้จอง</p>
                            <p class="text-base font-semibold text-primary" id="modalUser">N/A</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-200/30 px-8 py-5 border-t border-blue-600/10 flex justify-between items-center" style="padding: 1.25rem 2rem;">
                <div id="modalAdminActions" class="flex gap-2 hidden">
                    <button type="button" id="deleteBookingBtn" class="inline-flex justify-center rounded-xl bg-[#FCE8E6] px-6 py-4 text-sm font-bold text-[#D93025] hover:bg-red-100 transition-all border border-[#D93025]/30 shadow-sm hover:-translate-y-0.5">ลบ</button>
                    <button type="button" id="editBookingBtn" class="inline-flex justify-center rounded-xl bg-white px-6 py-4 text-sm font-bold text-[var(--secondary)] hover:bg-slate-200 transition-all border border-blue-400/50 shadow-sm hover:-translate-y-0.5">แก้ไขเวลา</button>
                    <button type="button" id="rescheduleBtn" class="inline-flex justify-center rounded-xl bg-white px-6 py-4 text-sm font-bold text-[#D93025] hover:bg-[#FCE8E6] transition-all border-2 border-[#D93025] shadow-sm hover:-translate-y-0.5">ย้ายวัน</button>
                </div>
                <button type="button" id="closeModalBtn" class="inline-flex justify-center rounded-xl bg-blue-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5" style="padding: 0.625rem 2rem;">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="fixed inset-0 z-[60] hidden overflow-y-auto" aria-labelledby="reschedule-modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/40 transition-opacity backdrop-blur-[2px]" id="rescheduleModalBackdrop"></div>
    <div class="flex min-h-full items-center justify-center p-4 text-center" style="padding: 1.5rem;">
        <div class="relative transform overflow-hidden rounded-3xl bg-white/95 backdrop-blur-xl text-left shadow-2xl transition-all w-full max-w-2xl border border-white/60 ring-1 ring-black/5">
            <div style="padding: 2rem;">
                <div class="flex items-center gap-5 border-b border-blue-600/10 pb-6 mb-6" style="padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-200 to-blue-400 shadow-inner" style="width: 4rem; height: 4rem;">
                        <i class="fas fa-calendar-alt text-primary text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold leading-tight text-primary mb-1" style="margin-bottom: 0.25rem;">ย้ายวันประชุม</h3>
                        <p class="text-sm font-medium text-text-muted">กรุณาระบุวันที่ใหม่ที่คุณต้องการเลื่อนการประชุม</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8" style="row-gap: 1.5rem; column-gap: 2rem;">
                    <!-- Current Info -->
                    <div class="flex items-start gap-4" style="gap: 1rem;">
                        <div class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-slate-200/50" style="width: 2.5rem; height: 2.5rem;">
                            <i class="fas fa-clock text-text-muted"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-text-muted uppercase tracking-wider mb-1" style="margin-bottom: 0.25rem;">ช่วงเวลาเดิมที่จองไว้</p>
                            <p class="text-base font-semibold text-primary" id="rescheduleTimeDisplay">00:00 - 00:00 น.</p>
                        </div>
                    </div>

                    <!-- New Date Input -->
                    <div class="flex items-start gap-4" style="gap: 1rem;">
                        <div class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#FCE8E6]" style="width: 2.5rem; height: 2.5rem;">
                            <i class="fas fa-calendar-check text-[#D93025]"></i>
                        </div>
                        <div class="flex-grow">
                            <p class="text-xs font-bold text-[#D93025] uppercase tracking-wider mb-1" style="margin-bottom: 0.25rem;">เลือกวันที่ใหม่</p>
                            <div class="bg-white/50 border border-blue-400/20 rounded-2xl px-4 py-2 hover:border-[#D93025]/50 transition-all shadow-sm">
                                <input type="date" id="newMeetingDate" class="w-full bg-transparent border-none p-0 text-base font-bold text-primary focus:ring-0 outline-none cursor-pointer leading-tight">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guidance Note -->
                <div class="mt-8 p-4 rounded-xl bg-white border border-blue-400/20 flex gap-3 items-center" style="margin-top: 1rem;">
                    <i class="fas fa-info-circle text-[var(--secondary)]"></i>
                    <p class="text-xs text-text-muted font-medium leading-relaxed">
                        ระบบจะรักษาช่วงเวลาและห้องประชุมเดิมไว้ หากต้องการแก้ไขส่วนอื่นโปรดใช้เมนู "แก้ไขเวลา"
                    </p>
                </div>
            </div>

            <div class="bg-slate-200/30 px-8 py-5 border-t border-blue-600/10 flex justify-between items-center" style="padding: 1.25rem 2rem;">
                <button type="button" id="cancelRescheduleBtn" class="inline-flex justify-center rounded-xl bg-white px-6 py-2 text-sm font-bold text-text-muted hover:text-primary transition-all border border-blue-400/30 shadow-sm">ยกเลิก</button>
                <button type="button" id="confirmRescheduleBtn" class="inline-flex justify-center rounded-xl bg-blue-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5" style="padding: 0.625rem 2rem;">ยืนยันการย้ายวัน</button>
            </div>
        </div>
    </div>
</div>

<script>
    let calendarInstance = null;
    let selectedRoomFilters = [];
    let currentRoomFilter = 'all';
    let currentStatusFilter = 'all';

    function reloadCalendarDayCounts() {
        if (!calendarInstance) return;
        if (!isAdmin) return;
        if (calendarInstance.view.type !== 'dayGridMonth') return;
        
        let startStr = calendarInstance.view.activeStart.toISOString().slice(0, 10);
        let endStr = calendarInstance.view.activeEnd.toISOString().slice(0, 10);
        
        let daycountsUrl = 'api/calendar_daycounts.php?start=' + startStr + '&end=' + endStr;
        if (currentRoomFilter !== 'all') {
            daycountsUrl += '&room_id=' + currentRoomFilter;
        }
        
        // Reset placeholders/badges first
        document.querySelectorAll('.day-total-placeholder').forEach(span => {
            span.textContent = '';
            span.classList.remove('has-data');
        });
        document.querySelectorAll('.day-counts').forEach(wrap => {
            wrap.style.display = 'none';
            let bottom = wrap.querySelector('.day-counts-bottom');
            if (bottom) bottom.innerHTML = '';
        });
        
        fetch(daycountsUrl)
            .then(r => r.json())
            .then(counts => {
                // Fill total badge placeholders
                document.querySelectorAll('.day-total-placeholder').forEach(function(span) {
                    var d = span.dataset.date;
                    var c = counts[d] || null;
                    if (c && c.total > 0) {
                        span.textContent = c.total;
                        span.classList.add('has-data');
                    }
                });

                // Fill status badge rows
                document.querySelectorAll('.day-counts').forEach(function(wrap) {
                    var d = wrap.dataset.date;
                    var c = counts[d] || null;
                    if (!c || c.total === 0) {
                        wrap.style.display = 'none';
                        return;
                    }
                    var bottom = wrap.querySelector('.day-counts-bottom');
                    if (bottom) {
                        let approvedHtml = c.approved > 0 ? '<span class="day-badge day-badge-approved">' + c.approved + '</span>' : '';
                        let pendingHtml = c.pending > 0 ? '<span class="day-badge day-badge-pending">' + c.pending + '</span>' : '';
                        let rejectedHtml = c.rejected > 0 ? '<span class="day-badge day-badge-rejected">' + c.rejected + '</span>' : '';
                        bottom.innerHTML = approvedHtml + pendingHtml + rejectedHtml;
                        wrap.style.display = '';
                    }
                });
            })
            .catch(function(){});
    }

    function filterCalendarByRoom(roomId, element) {
        roomId = parseInt(roomId);
        const index = selectedRoomFilters.indexOf(roomId);
        if (index > -1) {
            // Already selected: toggle off
            selectedRoomFilters.splice(index, 1);
            element.classList.remove('active');
        } else {
            // Not selected: toggle on
            selectedRoomFilters.push(roomId);
            element.classList.add('active');
        }
        
        currentRoomFilter = selectedRoomFilters.length > 0 ? selectedRoomFilters.join(',') : 'all';
        
        if (calendarInstance) {
            calendarInstance.refetchEvents();
            calendarInstance.refetchResources();
            reloadCalendarDayCounts();
        }
    }

    function applyRoomFilters() {
        const searchTerm = document.getElementById('roomSearchInput').value.toLowerCase();
        
        document.querySelectorAll('.room-card').forEach(card => {
            const title = card.querySelector('.room-card-title').textContent.toLowerCase();
            const status = card.getAttribute('data-status');
            
            const matchesSearch = title.includes(searchTerm);
            const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;
            
            if (matchesSearch && matchesStatus) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Search Input Listener
        const searchInput = document.getElementById('roomSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', applyRoomFilters);
        }
        
        // Filter Buttons Listener
        const filterBtns = document.querySelectorAll('.filter-btn');
        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active state styling
                filterBtns.forEach(b => {
                    b.classList.remove('filter-pill-active');
                    b.classList.add('filter-pill-inactive');
                });
                this.classList.remove('filter-pill-inactive');
                this.classList.add('filter-pill-active');
                
                currentStatusFilter = this.getAttribute('data-filter');
                applyRoomFilters();
            });
        });

        // รายการวันหยุดข้าราชการ (รูปแบบ MM-DD)
        const publicHolidays = {
            '01-01': 'วันขึ้นปีใหม่',
            '04-06': 'วันจักรี',
            '04-13': 'วันสงกรานต์',
            '04-14': 'วันสงกรานต์',
            '04-15': 'วันสงกรานต์',
            '05-01': 'วันแรงงานแห่งชาติ',
            '05-04': 'วันฉัตรมงคล',
            '06-03': 'วันเฉลิมฯ พระราชินี',
            '07-28': 'วันเฉลิมฯ ร.10',
            '08-12': 'วันแม่แห่งชาติ',
            '10-13': 'วันนวมินทรฯ',
            '10-23': 'วันปิยมหาราช',
            '12-05': 'วันพ่อแห่งชาติ',
            '12-10': 'วันรัฐธรรมนูญ',
            '12-31': 'วันสิ้นปี'
        };

        const isAdmin = <?= json_encode(($_SESSION['user_data']['role'] ?? 'user') === 'admin') ?>;
        var calendarEl = document.getElementById('calendar');
        calendarInstance = new FullCalendar.Calendar(calendarEl, {
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
            initialView: 'dayGridMonth',
            locale: 'th',
            height: 'auto',
            editable: isAdmin,
            resourceAreaWidth: '20%',
            resourceAreaHeaderContent: 'ห้องประชุม',
            slotMinTime: '08:00:00',
            slotMaxTime: '18:00:00',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            slotLabelFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,resourceTimelineDay'
            },
            navLinks: true,
            navLinkDayClick: function(date, jsEvent) {
                if (typeof calendarInstance !== 'undefined' && calendarInstance) {
                    calendarInstance.changeView('resourceTimelineDay', date);
                }
            },
            dateClick: function(info) {
                if (info.view.type === 'dayGridMonth') {
                    if (typeof calendarInstance !== 'undefined' && calendarInstance) {
                        calendarInstance.changeView('resourceTimelineDay', info.date);
                    }
                }
            },
            buttonText: {
                today: 'วันนี้',
                month: 'เดือน',
                resourceTimelineDay: 'วัน'
            },
            datesSet: function(info) {
                var titleEl = document.querySelector('.fc-toolbar-title');
                if (titleEl) {
                    var d = calendarInstance.getDate();
                    if (calendarInstance.view.type === 'dayGridMonth') {
                        titleEl.textContent = d.toLocaleDateString('th-TH', { month: 'long', year: 'numeric', calendar: 'buddhist' });
                    } else {
                        titleEl.textContent = d.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric', calendar: 'buddhist' });
                    }
                }
            },
            resources: function(fetchInfo, successCallback, failureCallback) {
                fetch('api/rooms.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            let list = data.rooms;
                            if (currentRoomFilter !== 'all') {
                                let filterIds = currentRoomFilter.split(',').map(Number);
                                list = list.filter(r => filterIds.includes(Number(r.id)));
                            }
                            const resources = list.map(room => ({
                                id: room.id,
                                title: room.name,
                                capacity: room.capacity
                            }));
                            if (currentRoomFilter === 'all' || currentRoomFilter.split(',').includes('external')) {
                                resources.push({ id: 'external', title: 'ภายนอกสถานที่' });
                            }
                            successCallback(resources);
                        } else {
                            failureCallback();
                        }
                    });
            },
            dayCellClassNames: function(arg) {
                let month = String(arg.date.getMonth() + 1).padStart(2, '0');
                let day = String(arg.date.getDate()).padStart(2, '0');
                let md = month + '-' + day;
                if (publicHolidays[md]) {
                    return ['fc-day-public-holiday'];
                }
                return [];
            },
            dayCellDidMount: function(arg) {
                if (arg.view.type !== 'dayGridMonth') return;

                let month = String(arg.date.getMonth() + 1).padStart(2, '0');
                let day   = String(arg.date.getDate()).padStart(2, '0');
                let md    = month + '-' + day;
                let dateStr = arg.date.getFullYear() + '-' + String(arg.date.getMonth() + 1).padStart(2, '0') + '-' + String(arg.date.getDate()).padStart(2, '0');

                let frame = arg.el.querySelector('.fc-daygrid-day-frame');
                let top   = arg.el.querySelector('.fc-daygrid-day-top');
                if (!frame || !top) return;

                // Make frame flex-column so badges sit at the bottom
                frame.style.display = 'flex';
                frame.style.flexDirection = 'column';
                frame.style.minHeight = '80px';

                // 1) Inject total badge placeholder into the top row (left side)
                if (isAdmin) {
                    let totalSpan = document.createElement('span');
                    totalSpan.className = 'day-total-placeholder';
                    totalSpan.dataset.date = dateStr;
                    top.insertBefore(totalSpan, top.firstChild);
                }

                // 2) Holiday row (middle) — full width, centered
                let holidayRow = document.createElement('div');
                holidayRow.className = 'day-holiday-row';
                if (publicHolidays[md]) {
                    holidayRow.innerText = publicHolidays[md];
                    arg.el.classList.add('fc-day-public-holiday');
                }
                top.insertAdjacentElement('afterend', holidayRow);

                // 3) Status badges container (bottom) — start empty, filled by datesSet
                if (isAdmin) {
                    let countsDiv = document.createElement('div');
                    countsDiv.className = 'day-counts';
                    countsDiv.dataset.date = dateStr;
                    countsDiv.style.display = 'none';  /* hidden until counts arrive */
                    countsDiv.innerHTML = '<div class="day-counts-bottom"></div>';
                    frame.appendChild(countsDiv);
                }
            },
            datesSet: function(info) {
                // Update Buddhist year in toolbar title
                var titleEl = document.querySelector('.fc-toolbar-title');
                if (titleEl) {
                    var text = titleEl.innerText;
                    var newText = text.replace(/\d{4}/g, function(match) {
                        var year = parseInt(match);
                        if (year < 2500) return year + 543;
                        return year;
                    });
                    if (text !== newText) titleEl.innerText = newText;
                }

                reloadCalendarDayCounts();
            },
            events: function(fetchInfo, successCallback, failureCallback) {
                let url = 'api/calendar_events.php?start=' + fetchInfo.startStr.slice(0, 10) + '&end=' + fetchInfo.endStr.slice(0, 10);
                if (currentRoomFilter !== 'all') {
                    url += '&room_id=' + currentRoomFilter;
                }
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        successCallback(data);
                    })
                    .catch(err => failureCallback(err));
            },
            eventDrop: function(info) {
                if (!isAdmin) {
                    info.revert();
                    return;
                }
                const pad = (n) => n < 10 ? '0' + n : n;
                const toLocal = (d) => d ? d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()) : null;
                
                const newStart = toLocal(info.event.start);
                const newEnd = toLocal(info.event.end) || newStart;
                const newResource = info.event.getResources()[0];
                const roomId = newResource && newResource.id !== 'external' ? newResource.id : null;
                
                Swal.fire({
                    title: 'ยืนยันการย้ายเวลา/ห้อง?',
                    text: 'คุณต้องการเปลี่ยนการจองนี้ใช่หรือไม่',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ตกลง',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#2563EB'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('api/bookings.php', {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_time',
                                booking_id: info.event.id,
                                start_time: newStart,
                                end_time: newEnd,
                                room_id: roomId
                            })
                        }).then(res => res.json()).then(data => {
                            if (data.success) {
                                MeetQueue.utils.notify('success', 'ย้ายสำเร็จ');
                            } else {
                                MeetQueue.utils.notify('error', 'ผิดพลาด', data.message);
                                info.revert();
                            }
                        }).catch(() => {
                            MeetQueue.utils.notify('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                            info.revert();
                        });
                    } else {
                        info.revert();
                    }
                });
            },
            eventResize: function(info) {
                if (!isAdmin) {
                    info.revert();
                    return;
                }
                const pad = (n) => n < 10 ? '0' + n : n;
                const toLocal = (d) => d ? d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds()) : null;
                
                const newStart = toLocal(info.event.start);
                const newEnd = toLocal(info.event.end) || newStart;
                const newResource = info.event.getResources()[0] || null;
                const roomId = newResource && newResource.id !== 'external' ? newResource.id : (info.event.extendedProps.room_id || null);

                Swal.fire({
                    title: 'ยืนยันการยืด/หดเวลา?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'ตกลง',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#2563EB'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('api/bookings.php', {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_time',
                                booking_id: info.event.id,
                                start_time: newStart,
                                end_time: newEnd,
                                room_id: roomId
                            })
                        }).then(res => res.json()).then(data => {
                            if (!data.success) info.revert();
                        });
                    } else {
                        info.revert();
                    }
                });
            },
            eventClick: function(info) {
                const props = info.event.extendedProps;
                const start = info.event.start.toLocaleString('th-TH', { dateStyle: 'long', timeStyle: 'short', calendar: 'buddhist', hour12: false });
                const end = info.event.end ? info.event.end.toLocaleString('th-TH', { timeStyle: 'short', calendar: 'buddhist', hour12: false }) : '';
                
                document.getElementById('modalTitle').textContent = props.original_title || info.event.title;
                document.getElementById('modalRoom').textContent = props.room;
                document.getElementById('modalTime').textContent = start + (end ? ' - ' + end : '');
                document.getElementById('modalParticipants').textContent = (props.participants || 0) + ' คน';
                document.getElementById('modalUser').textContent = props.user || 'N/A';
                
                const statusBadge = document.getElementById('modalStatus');
                let statusText = 'รออนุมัติ';
                let statusColor = 'bg-[#EAE4D3] text-[#6E4B3A] border border-[#D2CAB7]';
                
                if (props.status === 'approved') {
                    statusText = 'อนุมัติแล้ว';
                    statusColor = 'bg-[#E6F4EA] text-[#1E8E3E] border border-[#1E8E3E]/20';
                } else if (props.status === 'rejected') {
                    statusText = 'ไม่อนุมัติ';
                    statusColor = 'bg-[#FCE8E6] text-[#D93025] border border-[#D93025]/20';
                } else if (props.status === 'completed') {
                    statusText = 'เสร็จสิ้น';
                    statusColor = 'bg-slate-200 text-primary border border-blue-400/40';
                }
                
                statusBadge.textContent = statusText;
                statusBadge.className = `inline-block px-3 py-1 text-xs font-bold rounded-full mb-3 ${statusColor}`;
                
                // Show admin actions if admin
                if (isAdmin) {
                    document.getElementById('modalAdminActions').classList.remove('hidden');
                    
                    document.getElementById('deleteBookingBtn').onclick = () => {
                        Swal.fire({
                            title: 'ยืนยันการลบ?',
                            text: 'คุณต้องการลบการจองนี้ใช่หรือไม่',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'ลบ',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#94a3b8'
                        }).then((res) => {
                            if (res.isConfirmed) {
                                fetch('api/bookings.php', {
                                    method: 'DELETE',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ booking_id: info.event.id })
                                }).then(r => r.json()).then(data => {
                                    if (data.success) {
                                        MeetQueue.utils.notify('success', 'ลบสำเร็จ');
                                        calendarInstance.refetchEvents();
                                        closeModal();
                                    } else {
                                        MeetQueue.utils.notify('error', 'ผิดพลาด', data.message);
                                    }
                                });
                            }
                        });
                    };

                    document.getElementById('editBookingBtn').onclick = () => {
                        const pad = (n) => n < 10 ? '0' + n : n;
                        const toLocalInput = (d) => d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                        
                        const startLocal = toLocalInput(info.event.start);
                        const endLocal = info.event.end ? toLocalInput(info.event.end) : startLocal;

                        Swal.fire({
                            title: 'แก้ไขเวลาการจอง',
                            html: `
                                <div class="flex flex-col gap-4 text-left mt-4 px-2">
                                    <div>
                                        <label class="block text-sm font-bold text-primary mb-1">เวลาเริ่ม</label>
                                        <input type="datetime-local" id="swal-start" class="w-full rounded-xl border border-blue-400/50 px-4 py-2 focus:ring-2 focus:ring-[var(--primary)] focus:border-transparent" value="${startLocal}">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-primary mb-1">เวลาสิ้นสุด</label>
                                        <input type="datetime-local" id="swal-end" class="w-full rounded-xl border border-blue-400/50 px-4 py-2 focus:ring-2 focus:ring-[var(--primary)] focus:border-transparent" value="${endLocal}">
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'บันทึก',
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#2563EB',
                            cancelButtonColor: '#94a3b8',
                            preConfirm: () => {
                                return {
                                    start: document.getElementById('swal-start').value,
                                    end: document.getElementById('swal-end').value
                                }
                            }
                        }).then((res) => {
                            if (res.isConfirmed) {
                                const newStart = res.value.start.replace('T', ' ') + ':00';
                                const newEnd = res.value.end.replace('T', ' ') + ':00';
                                const roomId = info.event.extendedProps.room_id || null;
                                fetch('api/bookings.php', {
                                    method: 'PATCH',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ action: 'update_time', booking_id: info.event.id, start_time: newStart, end_time: newEnd, room_id: roomId })
                                }).then(r => r.json()).then(data => {
                                    if (data.success) {
                                        MeetQueue.utils.notify('success', 'อัปเดตเวลาสำเร็จ');
                                        calendarInstance.refetchEvents();
                                        closeModal();
                                    } else {
                                        MeetQueue.utils.notify('error', 'ผิดพลาด', data.message);
                                    }
                                });
                            }
                        });
                    };

                    document.getElementById('rescheduleBtn').onclick = () => {
                        const event = info.event;
                        const start = event.start;
                        const end = event.end || start;
                        
                        const pad = (n) => n < 10 ? '0' + n : n;
                        const dateStr = start.getFullYear() + '-' + pad(start.getMonth()+1) + '-' + pad(start.getDate());
                        document.getElementById('newMeetingDate').value = dateStr;
                        
                        const timeStart = pad(start.getHours()) + ':' + pad(start.getMinutes());
                        const timeEnd = pad(end.getHours()) + ':' + pad(end.getMinutes());
                        document.getElementById('rescheduleTimeDisplay').textContent = timeStart + ' - ' + timeEnd + ' น.';
                        
                        document.getElementById('rescheduleModal').classList.remove('hidden');
                    };

                    document.getElementById('confirmRescheduleBtn').onclick = () => {
                        const newDate = document.getElementById('newMeetingDate').value;
                        if (!newDate) {
                            MeetQueue.utils.notify('warning', 'กรุณาเลือกวันที่');
                            return;
                        }
                        
                        const event = info.event;
                        const start = event.start;
                        const end = event.end || start;
                        
                        const pad = (n) => n < 10 ? '0' + n : n;
                        const timeStart = pad(start.getHours()) + ':' + pad(start.getMinutes()) + ':' + pad(start.getSeconds());
                        const timeEnd = pad(end.getHours()) + ':' + pad(end.getMinutes()) + ':' + pad(end.getSeconds());
                        
                        const newStart = newDate + ' ' + timeStart;
                        const newEnd = newDate + ' ' + timeEnd;
                        const roomId = event.extendedProps.room_id || null;
                        
                        fetch('api/bookings.php', {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                action: 'update_time', 
                                booking_id: event.id, 
                                start_time: newStart, 
                                end_time: newEnd, 
                                room_id: roomId 
                            })
                        }).then(r => r.json()).then(data => {
                            if (data.success) {
                                MeetQueue.utils.notify('success', 'ย้ายวันสำเร็จ');
                                calendarInstance.refetchEvents();
                                document.getElementById('rescheduleModal').classList.add('hidden');
                                closeModal();
                            } else {
                                MeetQueue.utils.notify('error', 'ผิดพลาด', data.message);
                            }
                        }).catch(() => {
                            MeetQueue.utils.notify('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
                        });
                    };

                    document.getElementById('cancelRescheduleBtn').onclick = () => {
                        document.getElementById('rescheduleModal').classList.add('hidden');
                    };

                    document.getElementById('rescheduleModalBackdrop').onclick = () => {
                        document.getElementById('rescheduleModal').classList.add('hidden');
                    };
                } else {
                    document.getElementById('modalAdminActions').classList.add('hidden');
                }
                
                document.getElementById('eventModal').classList.remove('hidden');
            }
        });
        calendarInstance.render();
        
        const closeModal = () => document.getElementById('eventModal').classList.add('hidden');
        document.getElementById('closeModalBtn').addEventListener('click', closeModal);
        document.getElementById('modalBackdrop').addEventListener('click', closeModal);

        // ── Room Availability Checker Script ──
        (function() {
            let availRoomId = '';
            let availDate = new Date();
            let availDatePicker = null;

            const roomSelect = document.getElementById('availabilityRoomSelect');
            const monthPickerCont = document.getElementById('availabilityMonthPickerContainer');
            const resultsCont = document.getElementById('availabilityResults');
            const daysGrid = document.getElementById('availabilityDaysGrid');

            if (!roomSelect) return;

            availDatePicker = flatpickr("#availabilityMonthPicker", {
                locale: "th",
                defaultDate: availDate,
                disableMobile: true,
                formatDate: (date) => {
                    const thaiFullMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
                    return thaiFullMonths[date.getMonth()] + ' ' + (date.getFullYear() + 543);
                },
                onChange: (selectedDates) => {
                    if (selectedDates.length > 0) {
                        availDate = selectedDates[0];
                        fetchRoomAvailability();
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    const yearInput = instance.currentYearElement;
                    if (yearInput) {
                        const nativeInputValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
                        Object.defineProperty(yearInput, 'value', {
                            get: function() { return nativeInputValue.get.call(this); },
                            set: function(val) {
                                let newVal = parseInt(val, 10);
                                if (newVal > 1900 && newVal < 2400) { newVal += 543; }
                                nativeInputValue.set.call(this, newVal);
                            }
                        });
                        yearInput.value = instance.currentYear;

                        const origChangeYear = instance.changeYear;
                        instance.changeYear = function(year, jump, step) {
                            if (year > 2400) { year -= 543; }
                            origChangeYear.call(instance, year, jump, step);
                        };
                    }
                }
            });

            roomSelect.addEventListener('change', function() {
                availRoomId = this.value;
                if (availRoomId) {
                    monthPickerCont.style.display = 'block';
                    resultsCont.style.display = 'block';
                    fetchRoomAvailability();
                } else {
                    monthPickerCont.style.display = 'none';
                    resultsCont.style.display = 'none';
                }
            });

            function fetchRoomAvailability() {
                if (!availRoomId) return;

                const y = availDate.getFullYear();
                const mo = availDate.getMonth();
                const mStr = String(mo + 1).padStart(2, '0');
                const lastDay = new Date(y, mo + 1, 0).getDate();
                
                const start = `${y}-${mStr}-01`;
                const end   = `${y}-${mStr}-${String(lastDay).padStart(2, '0')}`;

                daysGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; font-size: 0.7rem; color: var(--text-muted); padding: 1rem;">กำลังตรวจสอบ...</div>';

                fetch(`api/calendar_daycounts.php?start=${start}&end=${end}&room_id=${availRoomId}`)
                    .then(r => r.json())
                    .then(counts => {
                        daysGrid.innerHTML = '';
                        
                        // Add header for days of week
                        const dayLabels = ['อา','จ','อ','พ','พฤ','ศ','ส'];
                        dayLabels.forEach(lbl => {
                            const el = document.createElement('div');
                            el.textContent = lbl;
                            el.style.cssText = 'text-align: center; font-size: 0.65rem; font-weight: 800; color: var(--text-muted); padding: 2px 0;';
                            daysGrid.appendChild(el);
                        });

                        // Empty spaces before first day
                        const firstDayIndex = new Date(y, mo, 1).getDay();
                        for (let i = 0; i < firstDayIndex; i++) {
                            const empty = document.createElement('div');
                            daysGrid.appendChild(empty);
                        }

                        // Render day buttons
                        for (let d = 1; d <= lastDay; d++) {
                            const key = `${y}-${mStr}-${String(d).padStart(2, '0')}`;
                            const c = counts[key] || { total: 0 };
                            
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.textContent = d;
                            
                            let bg = '#e6f4ea';
                            let color = '#1e8e3e';
                            let title = `วันที่ ${d}: ว่าง (ไม่มีการจอง)`;
                            
                            if (c.total > 0) {
                                bg = '#fce8e6';
                                color = '#d93025';
                                title = `วันที่ ${d}: มีการจองแล้ว ${c.total} รายการ`;
                            }

                            btn.style.cssText = `
                                border: none;
                                background: ${bg};
                                color: ${color};
                                font-size: 0.7rem;
                                font-weight: 700;
                                border-radius: 4px;
                                padding: 6px 0;
                                cursor: pointer;
                                text-align: center;
                                transition: all 0.15s;
                            `;
                            btn.title = title;

                            btn.addEventListener('mouseover', () => {
                                btn.style.filter = 'brightness(0.9)';
                                btn.style.transform = 'scale(1.05)';
                            });
                            btn.addEventListener('mouseout', () => {
                                btn.style.filter = 'none';
                                btn.style.transform = 'none';
                            });

                            btn.addEventListener('click', () => {
                                if (typeof calendarInstance !== 'undefined' && calendarInstance) {
                                    calendarInstance.gotoDate(key);
                                    MeetQueue.utils.notify('info', `วันที่ ${d}`, `ระบบนำท่านไปยังปฏิทินของวันที่ ${d} เรียบร้อยแล้ว`);
                                }
                            });

                            daysGrid.appendChild(btn);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        daysGrid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; font-size: 0.7rem; color: var(--danger); padding: 1rem;">เกิดข้อผิดพลาด</div>';
                    });
            }
        })();
    });
</script>
