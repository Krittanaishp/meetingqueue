<?php
require_once 'api/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

try {
    $pdo = getLocalDB();
    $usersStmt = $pdo->query("SELECT username, first_name, last_name FROM users ORDER BY first_name");
    $users = $usersStmt->fetchAll();
    
    foreach ($users as &$u) {
        if (isset($u['first_name'])) $u['first_name'] = cleanName($u['first_name']);
        if (isset($u['last_name'])) $u['last_name'] = cleanName($u['last_name']);
    }
} catch (Exception $e) {
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบจองห้องประชุม รพ.พาน</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Tailwind CSS v4 -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Outfit', 'Sarabun', sans-serif;
            
            /* Professional Blue Palette */
            --color-primary: #2563EB;
            --color-primary-hover: #1D4ED8;
            --color-secondary: #3B82F6;
            --color-accent: #EFF6FF;
            
            --color-background: #F1F5F9;
            --color-surface: #FFFFFF;
            --color-surface-muted: #E2E8F0;
            
            --color-text-main: #0F172A;
            --color-text-muted: #64748B;
            --color-border: #E2E8F0;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-background);
            color: var(--color-text-main);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden bg-[url('assets/images/poster1.png')] bg-cover bg-center bg-no-repeat before:content-[''] before:absolute before:inset-0 before:bg-white/80 before:backdrop-blur-md">
    
    <div class="w-full max-w-[min(420px,95vw)] bg-white/90 backdrop-blur-xl p-6 sm:p-10 rounded-[2.5rem] shadow-xl shadow-primary/10 border border-border relative z-10">
        
        <div class="flex items-center justify-center gap-3 mb-8">
            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                <i class="fas fa-calendar-check text-2xl"></i>
            </div>
            <div class="flex flex-col leading-tight">
                <span class="text-2xl font-bold text-primary tracking-tight">ระบบจองห้องประชุม</span>
                <span class="text-sm font-medium text-secondary">โรงพยาบาลพาน</span>
            </div>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-text-main mb-2">ยินดีต้อนรับกลับมา</h1>
            <p class="text-text-muted text-sm">เข้าสู่ระบบเพื่อจัดการห้องประชุม</p>
        </div>
        
        <form id="loginForm" class="space-y-5">
            <div>
                <label for="username" class="block text-sm font-medium text-text-main mb-1.5">ชื่อผู้ใช้งาน (ชื่อจริง)</label>
                <select id="username" required class="w-full">
                    <option value="">-- เลือกหรือพิมพ์ค้นหาชื่อ --</option>
                    <?php foreach($users as $u): ?>
                        <option value="<?= htmlspecialchars($u['username']) ?>"><?= htmlspecialchars($u['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-text-main mb-1.5">รหัสผ่าน</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-text-muted">
                        <i class="fas fa-lock"></i>
                    </div>
                    <input type="password" id="password" placeholder="••••••••" required
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-border bg-white focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all">
                </div>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl font-medium shadow-md shadow-primary/20 transition-all flex items-center justify-center gap-2 group">
                <span>เข้าสู่ระบบ</span>
                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameSelect = document.getElementById('username');
            if (usernameSelect) {
                new Choices(usernameSelect, {
                    searchEnabled: true,
                    searchPlaceholderValue: 'พิมพ์เพื่อค้นหา...',
                    placeholder: true,
                    itemSelectText: 'กดเพื่อเลือก',
                    noResultsText: 'ไม่พบชื่อที่ค้นหา',
                    shouldSort: false
                });
            }
        });

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username) {
                Swal.fire({
                    icon: 'warning',
                    title: 'แจ้งเตือน',
                    text: 'กรุณาเลือกชื่อผู้ใช้งาน',
                    confirmButtonColor: '#2563EB'
                });
                return;
            }

            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...';
            btn.disabled = true;

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    localStorage.setItem('user', JSON.stringify(result.user));
                    window.location.href = 'dashboard.php';
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เข้าสู่ระบบไม่สำเร็จ',
                        text: result.message || 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง',
                        confirmButtonColor: '#2563EB'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
                    confirmButtonColor: '#2563EB'
                });
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
