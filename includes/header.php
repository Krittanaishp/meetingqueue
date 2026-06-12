<?php
$user = $_SESSION['user_data'];
$photo_url = getUserPhotoUrl($user, 80);
$fallback_avatar = getUserPhotoUrl($user, 80);
$view = $_GET['view'] ?? 'calendar';
$titles = [
    'calendar' => 'หน้าหลัก > ปฏิทิน',
    'book' => 'หน้าหลัก > จองห้องประชุม',
    'results' => 'หน้าหลัก > สถานะการจอง',
    'reports' => 'หน้าหลัก > รายงานการใช้',
    'requests' => 'จองห้องประชุม > รายการขอใช้',
    'approve_list' => 'จองห้องประชุม > รายการอนุมัติ',
    'external' => 'หน้าหลัก > บันทึกประชุมภายนอก',
    'rooms' => 'หน้าหลัก > ข้อมูลห้องประชุม'
];
?>
<header>
    <div class="header-left">
        <div class="header-brand">
            <i class="fas fa-hospital-user"></i>
            <div class="header-brand-text">
                <span class="header-brand-title">จองห้องประชุม</span>
                <span class="header-brand-sub">โรงพยาบาลพาน</span>
            </div>
        </div>
        <div class="breadcrumb">
            <?php
            // Build clickable breadcrumb
            $breadcrumb_map = [
                'calendar'         => ['หน้าหลัก' => 'calendar'],
                'book'             => ['หน้าหลัก' => 'calendar', 'จองห้องประชุม' => 'book'],
                'results'          => ['หน้าหลัก' => 'calendar', 'สถานะการจอง' => 'results'],
                'status'           => ['หน้าหลัก' => 'calendar', 'สถานะการประชุม' => 'status'],
                'reports'          => ['หน้าหลัก' => 'calendar', 'รายงานการใช้' => 'reports'],
                'requests'         => ['จองห้องประชุม' => 'book', 'รายการขอใช้' => 'requests'],
                'approve_list'     => ['จองห้องประชุม' => 'book', 'รายการอนุมัติ' => 'approve_list'],
                'external'         => ['หน้าหลัก' => 'calendar', 'บันทึกประชุมภายนอก' => 'external'],
                'rooms'            => ['หน้าหลัก' => 'calendar', 'ข้อมูลห้องประชุม' => 'rooms'],
                'room_status'      => ['หน้าหลัก' => 'calendar', 'สถานะห้องประชุม' => 'room_status'],
                'history'          => ['หน้าหลัก' => 'calendar', 'ประวัติการประชุม' => 'history'],
                'statistics'       => ['หน้าหลัก' => 'calendar', 'สถิติการใช้งาน' => 'statistics'],
                'users'            => ['หน้าหลัก' => 'calendar', 'ข้อมูลผู้ใช้งาน' => 'users'],
                'admin_management' => ['หน้าหลัก' => 'calendar', 'จัดการการประชุม' => 'admin_management'],
                'backup_restore'   => ['หน้าหลัก' => 'calendar', 'สำรอง/กู้คืนฐานข้อมูล' => 'backup_restore'],
                'trash_management' => ['หน้าหลัก' => 'calendar', 'ถังขยะ' => 'trash_management'],
                'booking_result'   => ['หน้าหลัก' => 'calendar', 'รายละเอียดการจอง' => null],
                'profile'          => ['หน้าหลัก' => 'calendar', 'ข้อมูลส่วนตัว' => 'profile'],
            ];
            $crumbs = $breadcrumb_map[$view] ?? ['หน้าหลัก' => 'calendar'];
            $crumb_parts = [];
            $is_last = false;
            $crumb_keys = array_keys($crumbs);
            foreach ($crumbs as $label => $target_view) {
                $is_last = ($label === end($crumb_keys));
                if (!$is_last && $target_view) {
                    $crumb_parts[] = '<a href="dashboard.php?view=' . htmlspecialchars($target_view) . '" class="breadcrumb-link">' . htmlspecialchars($label) . '</a>';
                } else {
                    $crumb_parts[] = '<span>' . htmlspecialchars($label) . '</span>';
                }
            }
            echo implode(' <i class="fas fa-chevron-right" style="font-size:0.55rem;opacity:0.5;margin:0 0.1rem;"></i> ', $crumb_parts);
            ?>
        </div>
    </div>
    <div class="header-right">
        <!-- User Profile -->
        <div class="header-profile" id="headerProfile">
            <img src="<?php echo $photo_url; ?>" onerror="this.src='<?php echo $fallback_avatar; ?>'" alt="Avatar" class="header-avatar">
            <div class="header-profile-info">
                <span class="header-profile-name"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''))); ?></span>
                <span class="header-profile-role"><?php echo htmlspecialchars($user['position_name'] ?? 'บุคลากร'); ?></span>
            </div>
        </div>
        <!-- Hamburger Menu -->
        <button id="hamburgerMenuBtn" class="hamburger-btn" aria-label="เปิดเมนู">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>
</header>

<!-- Popup Menu Overlay -->
<div id="popupMenuOverlay" class="popup-menu-overlay"></div>

<!-- Popup Menu -->
<div id="popupMenu" class="popup-menu">
    <div class="popup-menu-header">
        <div class="popup-menu-profile">
            <img src="<?php echo $photo_url; ?>" onerror="this.src='<?php echo $fallback_avatar; ?>'" alt="Avatar" class="popup-menu-avatar">
            <div>
                <div class="popup-menu-name"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''))); ?></div>
                <div class="popup-menu-role"><?php echo htmlspecialchars($user['position_name'] ?? 'บุคลากร'); ?></div>
            </div>
        </div>
        <button id="popupMenuClose" class="popup-menu-close" aria-label="ปิดเมนู"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="popup-menu-body">
        <div class="popup-menu-section">
            <div class="popup-menu-label">เมนูจองห้องประชุม</div>
            <nav class="popup-nav">
                <a href="dashboard.php?view=calendar" class="popup-nav-link <?php echo (!isset($_GET['view']) || $_GET['view'] == 'calendar') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-days"></i><span>ปฏิทิน</span>
                </a>
                <a href="dashboard.php?view=book" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'book' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-plus"></i><span>จองห้องประชุม</span>
                </a>
                <a href="dashboard.php?view=results" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'results' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i><span>สถานะการประชุมของฉัน</span>
                </a>
                <a href="dashboard.php?view=room_status" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'room_status' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i><span>สถานะการประชุม</span>
                </a>
                <a href="dashboard.php?view=statistics" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'statistics' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i><span>สถิติการใช้งาน</span>
                </a>
                <a href="dashboard.php?view=reports" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i><span>รายงานการใช้ห้องประชุม</span>
                </a>
                <a href="dashboard.php?view=history" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i><span>ประวัติการประชุม</span>
                </a>
            </nav>
        </div>

        <?php if (($user['role'] ?? 'user') == 'admin'): ?>
        <div class="popup-menu-section">
            <div class="popup-menu-label">ผู้ดูแลระบบ</div>
            <nav class="popup-nav">
                <a href="dashboard.php?view=admin_management" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'admin_management' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i><span>จัดการการประชุม</span>
                </a>
                <a href="dashboard.php?view=trash_management" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'trash_management' ? 'active' : ''; ?>">
                    <i class="fas fa-trash-alt"></i><span>ถังขยะ</span>
                </a>
                <a href="dashboard.php?view=backup_restore" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'backup_restore' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i><span>สำรอง/กู้คืนฐานข้อมูล</span>
                </a>
                <a href="dashboard.php?view=requests" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'requests' ? 'active' : ''; ?>">
                    <i class="fas fa-list-ul"></i><span>รายการขอใช้</span>
                </a>
                <a href="dashboard.php?view=approve_list" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'approve_list' ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i><span>รายการอนุมัติ</span>
                </a>
                <a href="dashboard.php?view=external" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'external' ? 'active' : ''; ?>">
                    <i class="fas fa-file-signature"></i><span>บันทึกประชุมภายนอก</span>
                </a>
                <a href="dashboard.php?view=rooms" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'rooms' ? 'active' : ''; ?>">
                    <i class="fas fa-door-open"></i><span>ข้อมูลห้องประชุม</span>
                </a>
                <a href="dashboard.php?view=users" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users-gear"></i><span>ข้อมูลผู้ใช้งาน</span>
                </a>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Account Section -->
        <div class="popup-menu-section border-t border-[var(--secondary)]/10 pt-4 mt-2">
            <nav class="popup-nav">
                <a href="dashboard.php?view=profile" class="popup-nav-link <?php echo ($_GET['view'] ?? '') == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i><span>ข้อมูลส่วนตัว (Profile)</span>
                </a>
            </nav>
        </div>
    </div>
    
    <div class="popup-menu-footer">
        <div style="display: flex; gap: 0.5rem;">
            <button id="themeToggleBtn" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; flex: 1; padding: 0.65rem; border-radius: 0.75rem; background: var(--bg); color: var(--text-main); font-weight: 700; font-size: 0.82rem; cursor: pointer; border: 1px solid var(--border); transition: all 0.2s;">
                <i class="fas fa-moon"></i> โหมดสี
            </button>
            <a href="api/logout.php" class="popup-logout-btn" style="flex: 1;">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </div>
    </div>
</div>
