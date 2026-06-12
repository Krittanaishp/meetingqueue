/**
 * MeetQueue Modern Core
 * Using Module Pattern for better organization
 */
const MeetQueue = (() => {
    // Private State
    let _state = {
        bookings: [],
        rooms: [],
        currentUser: null
    };

    // Shared Utilities
    const utils = {
        // Normalize dateStr: if year > 2400, it was stored as Buddhist Era — subtract 543
        _normalizeDateStr: (dateStr) => {
            if (!dateStr) return dateStr;
            const yearMatch = dateStr.match(/^(\d{4})/);
            if (yearMatch && parseInt(yearMatch[1], 10) > 2400) {
                const ceYear = parseInt(yearMatch[1], 10) - 543;
                return dateStr.replace(/^\d{4}/, ceYear.toString());
            }
            return dateStr;
        },
        formatDate: (dateStr) => {
            if (!dateStr) return '-';
            dateStr = utils._normalizeDateStr(dateStr);
            const d = new Date(dateStr + (dateStr.length === 10 ? 'T00:00:00' : ''));
            if (isNaN(d)) return dateStr;
            // th-TH uses Buddhist Era (พ.ศ.) — year is CE+543
            return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric', calendar: 'buddhist' });
        },
        formatDateLong: (dateStr) => {
            if (!dateStr) return '-';
            dateStr = utils._normalizeDateStr(dateStr);
            const d = new Date(dateStr + (dateStr.length === 10 ? 'T00:00:00' : ''));
            if (isNaN(d)) return dateStr;
            return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric', calendar: 'buddhist' });
        },
        formatTime: (dateStr) => {
            if (!dateStr) return '-';
            return dateStr.split(' ')[1]?.substring(0, 5) || dateStr.substring(0, 5);
        },
        translateStatus: (status) => {
            const trans = { 
                'pending': 'รออนุมัติ', 
                'approved': 'อนุมัติแล้ว', 
                'rejected': 'ไม่อนุมัติ', 
                'cancelled': 'ยกเลิก', 
                'completed': 'เสร็จสิ้น' 
            };
            return trans[status] || status;
        },
        getStatusClass: (status) => {
            const classes = { 
                'pending': 'warning', 
                'approved': 'success', 
                'rejected': 'danger', 
                'cancelled': 'primary', 
                'completed': 'primary' 
            };
            return classes[status] || 'primary';
        },
        escapeHtml: (unsafe) => {
            if (!unsafe) return '';
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        },
        notify: (icon, title, text) => {
            return Swal.fire({
                icon, title, text,
                confirmButtonColor: '#6A5243',
                background: '#fff',
                customClass: { popup: 'rounded-[2rem]', confirmButton: 'rounded-xl px-8 py-3' }
            });
        },
        loading: (show = true, text = 'กำลังโหลด...') => {
            if (show) {
                Swal.fire({
                    title: text,
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            } else {
                Swal.close();
            }
        }
    };

    // UI Management
    const ui = {
        toggleMenu: () => {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const isOpen = sidebar?.classList.toggle('open');
            overlay?.classList.toggle('show');
            document.body.style.overflow = isOpen ? 'hidden' : '';
        },
        initDatePickers: () => {
            if (typeof flatpickr !== 'undefined') {
                const valueFormat = "Y-m-d"; // Value stored in hidden input (MUST stay CE)
                const displayFormat = "d/m/Y"; // Shown to user (will be converted to BE)

                flatpickr('input[type="date"]', {
                    locale: "th",
                    dateFormat: valueFormat,
                    altInput: true,
                    altFormat: displayFormat,
                    disableMobile: true,
                    formatDate: (date, format, locale) => {
                        let str = flatpickr.formatDate(date, format);
                        // ONLY apply Buddhist Era to DISPLAY format, NOT to value format
                        if (format === displayFormat && format.indexOf('Y') !== -1) {
                            str = str.replace(date.getFullYear().toString(), (date.getFullYear() + 543).toString());
                        }
                        return str;
                    },
                    onReady: function(selectedDates, dateStr, instance) {
                        const yearInput = instance.currentYearElement;
                        if (yearInput) {
                            // Intercept DOM value setter to show BE year in calendar header
                            const nativeInputValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
                            Object.defineProperty(yearInput, 'value', {
                                get: function() {
                                    return nativeInputValue.get.call(this);
                                },
                                set: function(val) {
                                    let newVal = parseInt(val, 10);
                                    if (newVal > 1900 && newVal < 2400) {
                                        newVal += 543;
                                    }
                                    nativeInputValue.set.call(this, newVal);
                                }
                            });

                            yearInput.value = instance.currentYear;

                            // Intercept changeYear to handle user typing BE year
                            const origChangeYear = instance.changeYear;
                            instance.changeYear = function(year, jump, step) {
                                if (year > 2400) {
                                    year -= 543;
                                }
                                origChangeYear.call(instance, year, jump, step);
                            };
                        }
                    }
                });
            }
        }
    };

    // API Wrapper
    const api = {
        fetch: async (url, options = {}) => {
            try {
                const res = await fetch(url, options);
                let data;
                try {
                    data = await res.json();
                } catch (e) {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    throw e;
                }
                
                if (!res.ok) {
                    return { success: false, message: data.message || `Error ${res.status}` };
                }
                return data;
            } catch (err) {
                console.error('API Error:', err);
                return { success: false, message: 'การเชื่อมต่อผิดพลาด: ' + err.message };
            }
        }
    };

    // Public Methods
    return {
        utils,
        ui,
        api,
        setState: (newState) => { _state = { ..._state, ...newState }; },
        getState: () => _state
    };
})();

// Global init
document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('ready');
    
    // Hamburger Menu Toggle
    const hamburgerBtn = document.getElementById('hamburgerMenuBtn');
    const popupMenu = document.getElementById('popupMenu');
    const popupOverlay = document.getElementById('popupMenuOverlay');
    const popupClose = document.getElementById('popupMenuClose');

    function togglePopupMenu() {
        const isOpen = popupMenu?.classList.toggle('open');
        hamburgerBtn?.classList.toggle('active', isOpen);
        popupOverlay?.classList.toggle('show', isOpen);
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
    function closePopupMenu() {
        popupMenu?.classList.remove('open');
        hamburgerBtn?.classList.remove('active');
        popupOverlay?.classList.remove('show');
        document.body.style.overflow = '';
    }

    hamburgerBtn?.addEventListener('click', togglePopupMenu);
    popupOverlay?.addEventListener('click', closePopupMenu);
    popupClose?.addEventListener('click', closePopupMenu);
    
    // Initialize custom date pickers
    MeetQueue.ui.initDatePickers();

    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const htmlElement = document.documentElement;
    
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    htmlElement.setAttribute('data-theme', savedTheme);
    
    function updateThemeUI(theme) {
        if (!themeToggleBtn) return;
        let iconClass = 'fas fa-palette';
        let textLabel = 'ธีมเอิร์ธโทน';
        
        if (theme === 'dark') {
            iconClass = 'fas fa-moon';
            textLabel = 'ธีมดาร์กไนท์';
        } else if (theme === 'white') {
            iconClass = 'fas fa-circle';
            textLabel = 'ธีมสีขาวคลีน';
        }
        themeToggleBtn.innerHTML = `<i class="${iconClass}"></i> ${textLabel}`;
    }

    if (themeToggleBtn) {
        updateThemeUI(savedTheme);
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
            let newTheme = 'light';
            if (currentTheme === 'light') {
                newTheme = 'dark';
            } else if (currentTheme === 'dark') {
                newTheme = 'white';
            } else {
                newTheme = 'light';
            }
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        });
    }
});
