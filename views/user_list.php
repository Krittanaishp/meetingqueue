<div class="flex flex-col gap-6 w-full animate-fade">
    <!-- Header Section (Borderless) -->
    <div class="flex flex-wrap items-center justify-between gap-10">
        <div class="flex-grow">
            <h2 class="text-3xl font-black text-primary leading-relaxed py-1 tracking-tight">จัดการรายชื่อผู้ใช้งาน</h2>
            <p class="text-text-muted font-bold opacity-80 mt-1">ข้อมูลรายชื่อบุคลากรในระบบ (Local Database)</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <!-- Search & Filter Bar -->
            <div class="flex items-center gap-4 bg-white px-3 py-1.5 rounded-[1rem] border border-blue-400/30 shadow-sm focus-within:border-blue-400 transition-all">
                <!-- Integrated Search Field -->
                <div class="relative flex items-center">
                    <i class="fas fa-search absolute left-4 text-[var(--secondary)] text-sm pointer-events-none"></i>
                    <input type="text" id="userSearchInput" onkeyup="filterUsers()" placeholder="ค้นหาชื่อ, รหัส, ตำแหน่ง, แผนก..." 
                        class="bg-slate-50 border border-blue-400/20 py-2 rounded-full focus:outline-none focus:border-blue-400 text-sm text-primary font-bold placeholder:text-text-muted/60 w-[260px] transition-all"
                        style="padding-left: 45px !important; padding-right: 15px !important;">
                </div>
                
                <div class="w-[1px] h-5 bg-[var(--secondary)]/20"></div>

                <!-- Filter Dropdown -->
                <div class="relative flex items-center">
                    <i class="fas fa-user-shield absolute left-2 text-[var(--secondary)] text-xs opacity-70 pointer-events-none"></i>
                    <select id="userRoleFilter" onchange="filterUsers()" class="bg-white border-none focus:outline-none text-sm text-primary font-black cursor-pointer rounded-lg"
                        style="padding-left: 32px !important; padding-right: 40px !important;">
                        <option value="all" class="bg-white text-primary">สิทธิ์ทั้งหมด</option>
                        <option value="ADMIN" class="bg-white text-primary">ADMIN</option>
                        <option value="USER" class="bg-white text-primary">USER</option>
                    </select>
                </div>
            </div>

            <a href="seed_users.php" target="_blank" class="h-[45px] min-w-[160px] px-8 bg-secondary/10 text-primary rounded-[2rem] border border-secondary/20 hover:bg-secondary/20 transition-all flex items-center justify-center gap-2 text-sm font-black shadow-sm">
                <i class="fas fa-seedling text-primary/60"></i> เติมข้อมูลทดสอบ
            </a>
            <button onclick="syncUsers()" id="syncBtn" class="h-[45px] min-w-[180px] px-8 bg-primary text-white rounded-[2rem] hover:bg-primary-hover transition-all flex items-center justify-center gap-2 text-sm font-black shadow-md">
                <i class="fas fa-sync-alt"></i> ซิงค์จาก ZK BioTime
            </button>
        </div>
    </div>

    <!-- Table Section (With Card Frame) -->
    <div class="card p-6 overflow-hidden">

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-border bg-surface">
                    <th class="p-3 font-semibold text-primary">รูปภาพ</th>
                    <th class="p-3 font-semibold text-primary">ชื่อ-นามสกุล</th>
                    <th class="p-3 font-semibold text-primary">ตำแหน่ง</th>
                    <th class="p-3 font-semibold text-primary">แผนก</th>
                    <th class="p-3 font-semibold text-primary">สิทธิ์</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <tr id="loadingRow">
                    <td colspan="6" class="p-8 text-center text-text-muted">
                        <i class="fas fa-spinner fa-spin mr-2"></i> กำลังโหลดข้อมูล...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterUsers() {
    const searchText = document.getElementById('userSearchInput').value.toLowerCase();
    const roleFilter = document.getElementById('userRoleFilter').value;
    const tableBody = document.getElementById('userTableBody');
    const rows = tableBody.getElementsByTagName('tr');

    let visibleCount = 0;

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        if (row.id === 'loadingRow' || row.id === 'noUserRow') continue;

        const fullName = row.cells[1].textContent.toLowerCase();
        const position = row.cells[2].textContent.toLowerCase();
        const department = row.cells[3].textContent.toLowerCase();
        
        // Extract correct value from select dropdown inside cell
        const roleSelect = row.cells[4].querySelector('select');
        const role = roleSelect ? roleSelect.value.toUpperCase() : '';

        const matchesSearch = fullName.includes(searchText) || 
                             position.includes(searchText) || 
                             department.includes(searchText);
                             
        const matchesRole = roleFilter === 'all' || role === roleFilter;

        if (matchesSearch && matchesRole) {
            row.classList.add('user-row-visible');
            visibleCount++;
        } else {
            row.classList.remove('user-row-visible');
            row.style.display = 'none';
        }
    }

    let noDataRow = document.getElementById('noUserRow');
    if (visibleCount === 0) {
        if (!noDataRow) {
            noDataRow = document.createElement('tr');
            noDataRow.id = 'noUserRow';
            noDataRow.innerHTML = `<td colspan="6" class="p-8 text-center text-text-muted font-bold">ไม่พบข้อมูลรายชื่อที่ตรงตามเงื่อนไข</td>`;
            tableBody.appendChild(noDataRow);
        } else {
            noDataRow.style.display = '';
        }
    } else if (noDataRow) {
        noDataRow.style.display = 'none';
    }
    
    // Refresh paginator if it exists
    if (window.userPaginator) {
        window.userPaginator.refresh();
    }
}

async function loadUsers() {
    try {
        const response = await fetch('api/users.php');
        const result = await response.json();
        
        if (result.success) {
            const tableBody = document.getElementById('userTableBody');
            if (result.data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-text-muted">ไม่พบข้อมูลผู้ใช้งาน กรุณากดปุ่มเติมข้อมูลทดสอบ</td></tr>';
                return;
            }
            
            tableBody.innerHTML = result.data.map(user => `
                <tr class="border-b border-border hover:bg-primary/5 transition-colors user-row-visible" onclick="window.location.href='dashboard.php?view=profile&id=${user.id}'" style="cursor: pointer;">
                    <td class="p-3">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.first_name)}&background=4f46e5&color=fff" 
                             class="w-10 h-10 rounded-full shadow-sm" alt="avatar">
                    </td>
                    <td class="p-3">
                        <div class="font-medium text-primary">${user.first_name} ${user.last_name || ''}</div>
                        <div class="text-xs text-text-muted">Username: ${user.username}</div>
                    </td>
                    <td class="p-3 text-sm">${user.position_name || '-'}</td>
                    <td class="p-3 text-sm">${user.dept_name || '-'}</td>
                    <td class="p-3" onclick="event.stopPropagation();">
                        <select onchange="changeUserRole(${user.id}, this.value)" 
                                class="px-3 py-1.5 rounded-xl text-xs font-black border cursor-pointer transition-all shadow-sm focus:outline-none ${user.role === 'admin' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-slate-100 text-slate-600 border-slate-200'}">
                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>ADMIN</option>
                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>USER</option>
                        </select>
                    </td>
                </tr>
            `).join('');
            
            // Initialize paginator
            if (!window.userPaginator) {
                window.userPaginator = new MeetQueuePaginator({
                    container: '#userTableBody',
                    itemSelector: 'tr',
                    pageSize: 10,
                    activeSearchClass: 'user-row-visible'
                });
            } else {
                window.userPaginator.refresh();
            }
        } else {
            alert('โหลดข้อมูลล้มเหลว: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

async function changeUserRole(userId, newRole) {
    try {
        const res = await fetch('api/users.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, role: newRole })
        });
        const result = await res.json();
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: result.message,
                showConfirmButton: false,
                timer: 1500,
                confirmButtonColor: '#2563EB'
            });
            loadUsers();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สำเร็จ',
                text: result.message,
                confirmButtonColor: '#2563EB'
            });
            loadUsers();
        }
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'ผิดพลาด',
            text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
            confirmButtonColor: '#2563EB'
        });
        loadUsers();
    }
}

async function syncUsers() {
    const btn = document.getElementById('syncBtn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> กำลังซิงค์...';
    btn.disabled = true;

    try {
        const response = await fetch('api/sync_users.php');
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            loadUsers();
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการซิงค์ข้อมูล');
    } finally {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', loadUsers);
</script>
