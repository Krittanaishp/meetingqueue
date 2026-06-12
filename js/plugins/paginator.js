class MeetQueuePaginator {
    constructor(options) {
        this.container = document.querySelector(options.container);
        if (!this.container) return;
        
        this.itemSelector = options.itemSelector;
        this.pageSize = options.pageSize || 10;
        this.currentPage = 1;
        this.paginationContainer = null;
        this.activeSearchClass = options.activeSearchClass || ''; // If items are filtered by search, only count visible ones
        
        this.init();
    }
    
    init() {
        // Create pagination wrapper if not exists
        let targetParent = this.container.parentNode;
        let targetSibling = this.container.nextSibling;
        
        // If container is tbody, put pagination after the table element
        if (this.container.tagName.toLowerCase() === 'tbody' || this.container.tagName.toLowerCase() === 'thead') {
            targetParent = targetParent.parentNode;
            targetSibling = this.container.parentNode.nextSibling;
        }
        
        let existingWrapper = targetParent.querySelector('.mq-pagination-wrapper');
        if (!existingWrapper) {
            existingWrapper = document.createElement('div');
            existingWrapper.className = 'mq-pagination-wrapper flex flex-wrap justify-between items-center gap-4 mt-6 pt-4 border-t border-[#EBE6DA]/50 no-print';
            targetParent.insertBefore(existingWrapper, targetSibling);
        }
        this.paginationContainer = existingWrapper;
        this.render();
    }
    
    getItems() {
        let items = Array.from(this.container.querySelectorAll(this.itemSelector));
        // If there's an active search filter, only paginate items that are NOT display: none
        if (this.activeSearchClass) {
            items = items.filter(item => item.classList.contains(this.activeSearchClass) || item.style.display !== 'none');
        }
        return items;
    }
    
    render() {
        const items = this.getItems();
        const totalItems = items.length;
        const totalPages = Math.ceil(totalItems / this.pageSize);
        
        if (this.currentPage > totalPages) this.currentPage = Math.max(1, totalPages);
        
        // Hide all, show only current page
        items.forEach((item, index) => {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            
            // For tables, we use table-row, for grids we use block/flex
            const displayType = item.tagName.toLowerCase() === 'tr' ? 'table-row' : 'flex';
            
            if (index >= start && index < end) {
                item.style.display = displayType;
                item.classList.add('mq-visible-page');
            } else {
                item.style.display = 'none';
                item.classList.remove('mq-visible-page');
            }
        });
        
        this.renderControls(totalItems, totalPages);
    }
    
    renderControls(totalItems, totalPages) {
        if (totalItems === 0) {
            this.paginationContainer.innerHTML = '';
            return;
        }
        
        const startItem = ((this.currentPage - 1) * this.pageSize) + 1;
        const endItem = Math.min(this.currentPage * this.pageSize, totalItems);
        
        let html = `
            <div class="text-sm font-bold text-[#A79A8B]">
                แสดง ${startItem} ถึง ${endItem} จาก ${totalItems} รายการ
            </div>
            <div class="flex items-center gap-2">
        `;
        
        // Prev Button
        html += `
            <button onclick="this.paginator.goToPage(${this.currentPage - 1})" 
                    class="w-9 h-9 flex items-center justify-center rounded-xl font-bold transition-all ${this.currentPage === 1 ? 'bg-[#F3F0E6] text-[#D4B59D] cursor-not-allowed' : 'bg-white text-[#6A5243] hover:bg-[#6A5243] hover:text-white shadow-sm border border-[#EBE6DA]'}"
                    ${this.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Page Numbers
        for (let i = 1; i <= totalPages; i++) {
            // Simple logic: show first, last, and +-1 of current
            if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                const isActive = i === this.currentPage;
                html += `
                    <button onclick="this.paginator.goToPage(${i})" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-bold transition-all ${isActive ? 'bg-[#D4B59D] text-white shadow-md' : 'bg-white text-[#6A5243] hover:bg-[#F3F0E6] border border-[#EBE6DA]'}">
                        ${i}
                    </button>
                `;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                html += `<span class="px-1 text-[#A79A8B]">...</span>`;
            }
        }
        
        // Next Button
        html += `
            <button onclick="this.paginator.goToPage(${this.currentPage + 1})" 
                    class="w-9 h-9 flex items-center justify-center rounded-xl font-bold transition-all ${this.currentPage === totalPages ? 'bg-[#F3F0E6] text-[#D4B59D] cursor-not-allowed' : 'bg-white text-[#6A5243] hover:bg-[#6A5243] hover:text-white shadow-sm border border-[#EBE6DA]'}"
                    ${this.currentPage === totalPages ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <select onchange="this.paginator.changePageSize(this.value)" class="ml-4 pl-3 pr-8 py-1.5 rounded-xl border border-[#D4B59D]/30 focus:outline-none focus:border-[#D4B59D] bg-white text-[#6A5243] text-sm font-bold shadow-sm cursor-pointer appearance-none">
                <option value="6" ${this.pageSize === 6 ? 'selected' : ''}>6 / หน้า</option>
                <option value="10" ${this.pageSize === 10 ? 'selected' : ''}>10 / หน้า</option>
                <option value="25" ${this.pageSize === 25 ? 'selected' : ''}>25 / หน้า</option>
                <option value="50" ${this.pageSize === 50 ? 'selected' : ''}>50 / หน้า</option>
            </select>
        </div>`;
        
        this.paginationContainer.innerHTML = html;
        
        // Attach paginator reference to buttons for onclick
        const buttons = this.paginationContainer.querySelectorAll('button, select');
        buttons.forEach(btn => btn.paginator = this);
    }
    
    goToPage(page) {
        this.currentPage = page;
        this.render();
    }
    
    changePageSize(size) {
        this.pageSize = parseInt(size);
        this.currentPage = 1;
        this.render();
    }
    
    refresh() {
        // Called after filtering
        this.currentPage = 1;
        this.render();
    }
}
window.MeetQueuePaginator = MeetQueuePaginator;
