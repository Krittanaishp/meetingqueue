<?php
require_once 'api/config.php';
use App\Repository\BookingRepository;

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$repo = new BookingRepository();
$user = $_SESSION['user_data'];

$filters = [];
if ($user['role'] !== 'admin') {
    $filters['user_id'] = $_SESSION['user_id'];
}

$bookings = $repo->getAll($filters);

$past_bookings = array_filter($bookings ?: [], function($b) {
    $end_time = $b['end_time'] ?? null;
    return in_array($b['status'] ?? '', ['approved', 'completed']) && $end_time && strtotime($end_time) < time();
});

$db = \App\Core\Database::getInstance()->getConnection();
$reviews_stmt = $db->prepare("SELECT booking_id, rating, comment FROM meeting_reviews WHERE user_id = ?");
$reviews_stmt->execute([$_SESSION['user_id']]);
$user_reviews = [];
foreach ($reviews_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $user_reviews[$r['booking_id']] = $r;
}

$thai_months = [
    '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
    '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
    '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
];

function formatThaiDate($datetime, $months) {
    $time = strtotime($datetime);
    $d = date('j', $time);
    $m = $months[date('m', $time)];
    $y = (date('Y', $time) + 543) % 100;
    $t = date('H:i', $time);
    return "$d $m $y เวลา $t น.";
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.3/viewer.min.css">
<style>
    .history-card { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease; }
    .history-card:hover { transform: translateY(-5px); }
    .image-actions { opacity: 0; transition: opacity 0.3s ease; }
    .history-card:hover .image-actions { opacity: 1; }
    .viewer-toolbar > ul > li { background-color: rgba(15, 23, 42, 0.5) !important; }
</style>

<div class="flex flex-col gap-6 w-full animate-fade">
    <div class="flex flex-wrap justify-between items-center gap-4">
        <h2 class="text-2xl font-bold text-primary flex items-center gap-2">
            <i class="fas fa-history text-[var(--secondary)]"></i> ประวัติการประชุม & แบบประเมิน
        </h2>
        <div class="relative w-full sm:w-64">
            <input type="text" id="searchHistory" onkeyup="filterHistory()" placeholder="ค้นหาการประชุม..." 
                   class="w-full pr-4 py-2.5 rounded-xl border border-blue-400/30 focus:outline-none focus:border-blue-400 bg-white text-primary shadow-sm transition-all" style="padding-left: 2.75rem;">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-text-muted"></i>
        </div>
    </div>

    <?php if (count($past_bookings) > 0): ?>
        <div id="historyGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($past_bookings as $booking): 
                $room_id = $booking['room_id'] ?? 1;
                $images = [];
                if (!empty($booking['image_list'])) {
                    $parts = explode('|', $booking['image_list']);
                    foreach ($parts as $p) {
                        list($img_id, $img_path) = explode(':', $p, 2);
                        $images[] = ['id' => $img_id, 'path' => $img_path];
                    }
                }
                
                $has_custom_images = count($images) > 0;
                $display_image = $has_custom_images ? $images[0]['path'] : 'assets/images/' . (($room_id % 2 == 0) ? 'room2.png' : 'room1.png');
                $can_manage = ($user['role'] === 'admin' || $booking['user_id'] == $_SESSION['user_id']);
            ?>
            <div class="history-card bg-white rounded-3xl overflow-hidden shadow-sm border border-slate-200/60 hover:shadow-xl transition-all flex flex-col group"
                 data-search="<?= htmlspecialchars(strtolower($booking['title'] . ' ' . ($booking['room_name'] ?? 'ภายนอกสถานที่') . ' ' . formatThaiDate($booking['start_time'], $thai_months))) ?>">
                
                <div class="relative h-56 overflow-hidden bg-slate-50">
                    <img src="<?= $display_image ?>" alt="บรรยากาศห้องประชุม" 
                         class="w-full h-full object-cover cursor-zoom-in group-hover:scale-105 transition-transform duration-700"
                         onclick="openGallery(<?= $booking['id'] ?>)">
                    
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-full text-[10px] font-bold text-primary shadow-sm border border-blue-400/20 z-10">
                        <i class="fas fa-check-circle text-green-500 mr-1"></i> เสร็จสิ้น
                    </div>

                    <div class="absolute bottom-4 right-4 bg-black/50 backdrop-blur-sm px-2 py-1 rounded-lg text-[10px] font-bold text-white z-10 flex items-center gap-1.5">
                        <i class="fas fa-images"></i> <?= count($images) ?: 0 ?> ภาพ
                    </div>

                    <?php if ($can_manage): ?>
                    <div class="image-actions absolute top-4 right-4 flex flex-col gap-2 z-10">
                        <button onclick="triggerUpload(<?= $booking['id'] ?>)" class="w-9 h-9 rounded-full bg-white/90 backdrop-blur-md text-primary shadow-lg flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all border border-white/50" title="เพิ่มรูปภาพ">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                        <?php if ($has_custom_images): ?>
                        <button onclick="manageImages(<?= $booking['id'] ?>, <?= htmlspecialchars(json_encode($images)) ?>)" class="w-9 h-9 rounded-full bg-[var(--secondary)]/90 backdrop-blur-md text-white shadow-lg flex items-center justify-center hover:bg-blue-600 transition-all border border-white/50" title="จัดการรูปภาพ">
                            <i class="fas fa-tasks text-sm"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent pointer-events-none"></div>
                    
                    <!-- Gallery Data (Hidden) -->
                    <div id="gallery-data-<?= $booking['id'] ?>" class="hidden">
                        <?php if ($has_custom_images): ?>
                            <?php foreach($images as $img): ?>
                                <img src="<?= $img['path'] ?>" data-id="<?= $img['id'] ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="<?= $display_image ?>">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="p-6 flex flex-col flex-grow">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-[11px] font-bold text-text-muted tracking-wider uppercase flex items-center gap-1.5 px-2.5 py-1 bg-white rounded-md border border-slate-200/50">
                            <i class="far fa-calendar-alt text-[var(--secondary)]"></i>
                            <?= formatThaiDate($booking['start_time'], $thai_months) ?>
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-primary">
                            <i class="fas fa-users text-[var(--secondary)]"></i> <?= $booking['participants_count'] ?> คน
                        </div>
                    </div>
                    
                    <h3 class="text-xl font-black text-primary mb-3 line-clamp-2 leading-tight" title="<?= htmlspecialchars($booking['title']) ?>">
                        <?= htmlspecialchars($booking['title']) ?>
                    </h3>
                    
                    <div class="flex items-start gap-2.5 mb-6 text-sm text-primary/80">
                        <i class="fas fa-map-marker-alt mt-1 text-[var(--secondary)]"></i>
                        <span class="font-medium"><?= $booking['room_name'] ?? 'ภายนอกสถานที่' ?></span>
                    </div>

                    <div class="mt-auto pt-5 border-t border-slate-200/50">
                        <button class="w-full px-4 py-3 rounded-2xl <?= ($user_reviews[$booking['id']] ?? null) ? 'bg-[var(--secondary)] text-white' : 'bg-slate-50 text-primary' ?> text-sm font-black hover:shadow-lg hover:-translate-y-0.5 active:scale-95 transition-all flex items-center justify-center gap-2" 
                                onclick='reviewMeeting(<?= $booking['id'] ?>, <?= json_encode($user_reviews[$booking['id']] ?? null) ?>)'>
                            <i class="fas fa-star"></i> 
                            <?= ($user_reviews[$booking['id']] ?? null) ? 'ดู/แก้ไขการประเมิน' : 'ทำแบบประเมินความพึงพอใจ' ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl p-16 text-center border border-slate-200/50 shadow-sm">
            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-[var(--secondary)]"><i class="fas fa-box-open text-4xl"></i></div>
            <h3 class="text-2xl font-black text-primary mb-3">ยังไม่มีประวัติการประชุม</h3>
            <p class="text-text-muted max-w-sm mx-auto font-medium">เมื่อการประชุมเสร็จสิ้นแล้ว ข้อมูลและประวัติของคุณจะแสดงที่นี่</p>
        </div>
    <?php endif; ?>
</div>

<input type="file" id="meetingImageInput" accept="image/*" multiple class="hidden" onchange="handleImageUpload(this)">

<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.3/viewer.min.js"></script>
<script>
let currentBookingId = null;

function triggerUpload(id) {
    currentBookingId = id;
    document.getElementById('meetingImageInput').click();
}

async function handleImageUpload(input) {
    if (!input.files.length || !currentBookingId) return;

    const formData = new FormData();
    formData.append('booking_id', currentBookingId);
    for (let i = 0; i < input.files.length; i++) {
        formData.append('images[]', input.files[i]);
    }

    MeetQueue.utils.loading(true, 'กำลังอัปโหลดรูปภาพ...');
    try {
        const res = await fetch('api/meeting_images.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: `อัปโหลดเรียบร้อยแล้ว ${input.files.length} รูป`, confirmButtonColor: '#2563EB' }).then(() => window.location.reload());
        } else throw new Error(data.message);
    } catch (err) { Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: err.message, confirmButtonColor: '#2563EB' }); }
    finally { MeetQueue.utils.loading(false); input.value = ''; }
}

function openGallery(bookingId) {
    const container = document.getElementById(`gallery-data-${bookingId}`);
    const viewer = new Viewer(container, {
        navbar: true, toolbar: true,
        hidden: () => viewer.destroy()
    });
    viewer.show();
}

async function manageImages(bookingId, images) {
    let html = '<div class="grid grid-cols-3 gap-3 p-2">';
    images.forEach(img => {
        html += `
            <div class="relative group aspect-square rounded-lg overflow-hidden border border-gray-100">
                <img src="${img.path}" class="w-full h-full object-cover">
                <button onclick="confirmDeleteImage(${bookingId}, ${img.id})" class="absolute inset-0 bg-red-500/80 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';

    Swal.fire({
        title: 'จัดการรูปภาพการประชุม',
        html: html,
        showConfirmButton: false,
        showCloseButton: true,
        customClass: { popup: 'rounded-3xl' }
    });
}

window.confirmDeleteImage = async function(bookingId, imageId) {
    const result = await Swal.fire({
        title: 'ลบรูปภาพนี้?',
        text: "ไม่สามารถเรียกคืนรูปภาพที่ลบไปแล้วได้",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'ลบทันที',
        cancelButtonText: 'ยกเลิก'
    });

    if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('image_id', imageId);
        formData.append('action', 'delete');

        try {
            const res = await fetch('api/meeting_images.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1000, showConfirmButton: false }).then(() => window.location.reload());
            } else throw new Error(data.message);
        } catch (err) { Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: err.message }); }
    }
}

function reviewMeeting(id, existingReview) {
    const defaultRating = existingReview ? existingReview.rating : 0;
    const defaultComment = existingReview && existingReview.comment ? MeetQueue.utils.escapeHtml(existingReview.comment) : '';
    Swal.fire({
        title: 'แบบประเมินหลังการประชุม',
        html: `
            <div class="text-left mb-4">
                <label class="block text-sm font-black text-primary mb-3">ระดับความพึงพอใจ</label>
                <div class="flex gap-3 text-3xl text-gray-200 cursor-pointer justify-center my-6" id="star-rating">
                    <i class="fas fa-star hover:text-yellow-400 transition-colors" onclick="setRating(1)"></i>
                    <i class="fas fa-star hover:text-yellow-400 transition-colors" onclick="setRating(2)"></i>
                    <i class="fas fa-star hover:text-yellow-400 transition-colors" onclick="setRating(3)"></i>
                    <i class="fas fa-star hover:text-yellow-400 transition-colors" onclick="setRating(4)"></i>
                    <i class="fas fa-star hover:text-yellow-400 transition-colors" onclick="setRating(5)"></i>
                </div>
                <input type="hidden" id="rating-value" value="${defaultRating}">
                <label class="block text-sm font-black text-primary mb-3 mt-6">ข้อเสนอแนะเพิ่มเติม</label>
                <textarea id="review-comment" class="w-full border border-blue-400/30 rounded-2xl p-4 focus:outline-none focus:border-blue-400 bg-white text-primary text-sm shadow-inner" rows="4" placeholder="บอกเราหน่อยว่าควรปรับปรุงอะไรบ้าง...">${defaultComment}</textarea>
            </div>
        `,
        didOpen: () => { if (defaultRating > 0) setRating(defaultRating); },
        showCancelButton: true, confirmButtonText: 'บันทึกความพึงพอใจ', cancelButtonText: 'ไว้ทีหลัง',
        confirmButtonColor: '#2563EB', cancelButtonColor: '#94a3b8',
        customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl px-6 py-2.5 font-bold', cancelButton: 'rounded-xl px-6 py-2.5 font-bold' },
        preConfirm: () => {
            const rating = document.getElementById('rating-value').value;
            const comment = document.getElementById('review-comment').value;
            if (rating == 0) { Swal.showValidationMessage('กรุณาให้คะแนนความพึงพอใจ'); return false; }
            return { booking_id: id, rating, comment };
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            MeetQueue.utils.loading(true, 'กำลังบันทึก...');
            try {
                const res = await fetch('api/reviews.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(result.value) });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', text: 'ขอบคุณที่สละเวลาประเมินความพึงพอใจให้กับเรา', confirmButtonColor: '#2563EB', customClass: { popup: 'rounded-3xl' } }).then(() => window.location.reload());
                } else throw new Error(data.message || 'เกิดข้อผิดพลาด');
            } catch (err) { Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: err.message, confirmButtonColor: '#2563EB' }); }
            finally { MeetQueue.utils.loading(false); }
        }
    });
}

window.setRating = function(rating) {
    document.getElementById('rating-value').value = rating;
    const stars = document.getElementById('star-rating').children;
    for (let i = 0; i < stars.length; i++) {
        if (i < rating) { stars[i].classList.remove('text-gray-200'); stars[i].classList.add('text-yellow-400'); }
        else { stars[i].classList.add('text-gray-200'); stars[i].classList.remove('text-yellow-400'); }
    }
};

let historyPaginator;

document.addEventListener('DOMContentLoaded', () => {
    historyPaginator = new MeetQueuePaginator({
        container: '#historyGrid',
        itemSelector: '.history-card',
        pageSize: 6,
        activeSearchClass: 'matches-search' // We will toggle this class in filterHistory
    });
});

function filterHistory() {
    let input = document.getElementById('searchHistory').value.toLowerCase();
    let cards = document.querySelectorAll('.history-card');
    cards.forEach(card => {
        let searchText = card.getAttribute('data-search') || '';
        if (searchText.includes(input)) {
            card.classList.add('matches-search');
            card.style.display = 'flex';
        } else {
            card.classList.remove('matches-search');
            card.style.display = 'none';
        }
    });
    
    if (historyPaginator) {
        historyPaginator.refresh();
    }
}
</script>
