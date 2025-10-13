/**
 * Quick Search Autocomplete Component
 * 
 * Usage:
 * <input type="text" class="form-control" 
 *        data-autocomplete="products"
 *        data-autocomplete-url="/api/search/products"
 *        data-target-field="product_id">
 */

class QuickSearch {
    constructor(element, options = {}) {
        this.input = element;
        this.options = {
            url: element.dataset.autocompleteUrl || '/api/search/products',
            minChars: 2,
            delay: 300,
            limit: 10,
            targetField: element.dataset.targetField || null,
            onSelect: options.onSelect || null,
            ...options
        };

        this.timeout = null;
        this.resultsContainer = null;
        this.selectedIndex = -1;

        this.init();
    }

    init() {
        // إنشاء container للنتائج
        this.createResultsContainer();

        // Event listeners
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('blur', () => {
            setTimeout(() => this.hideResults(), 200);
        });

        // إضافة أيقونة بحث
        this.addSearchIcon();
    }

    createResultsContainer() {
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.className = 'autocomplete-results';
        this.resultsContainer.style.cssText = `
            position: absolute;
            z-index: 1050;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 300px;
            overflow-y: auto;
            display: none;
            width: 100%;
        `;

        // إدراج بعد الـ input
        this.input.parentElement.style.position = 'relative';
        this.input.parentElement.appendChild(this.resultsContainer);
    }

    addSearchIcon() {
        // إضافة أيقونة بحث في الـ input
        this.input.style.paddingRight = '35px';
        
        const icon = document.createElement('i');
        icon.className = 'bi bi-search position-absolute';
        icon.style.cssText = `
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        `;

        const wrapper = this.input.parentElement;
        if (!wrapper.classList.contains('position-relative')) {
            wrapper.classList.add('position-relative');
        }
        wrapper.appendChild(icon);
    }

    handleInput(e) {
        const value = e.target.value.trim();

        // مسح timeout السابق
        clearTimeout(this.timeout);

        if (value.length < this.options.minChars) {
            this.hideResults();
            return;
        }

        // تأخير البحث
        this.timeout = setTimeout(() => {
            this.search(value);
        }, this.options.delay);
    }

    handleKeydown(e) {
        const items = this.resultsContainer.querySelectorAll('.autocomplete-item');

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                this.highlightItem();
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.highlightItem();
                break;

            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                    items[this.selectedIndex].click();
                }
                break;

            case 'Escape':
                this.hideResults();
                break;
        }
    }

    highlightItem() {
        const items = this.resultsContainer.querySelectorAll('.autocomplete-item');
        
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    async search(query) {
        try {
            const url = new URL(this.options.url, window.location.origin);
            url.searchParams.append('q', query);
            url.searchParams.append('limit', this.options.limit);

            // إضافة branch_id إن وُجد
            const branchField = document.querySelector('[name="branch_id"]');
            if (branchField && branchField.value) {
                url.searchParams.append('branch_id', branchField.value);
            }

            const response = await fetch(url);
            const results = await response.json();

            this.displayResults(results);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    displayResults(results) {
        this.resultsContainer.innerHTML = '';
        this.selectedIndex = -1;

        if (!results || results.length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="autocomplete-item text-muted p-3 text-center">
                    <i class="bi bi-search"></i> لا توجد نتائج
                </div>
            `;
            this.showResults();
            return;
        }

        results.forEach((item) => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.style.cssText = `
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                transition: background 0.2s;
            `;
            div.innerHTML = this.formatItem(item);

            div.addEventListener('mouseenter', () => {
                div.style.background = '#f8f9fa';
            });

            div.addEventListener('mouseleave', () => {
                if (!div.classList.contains('active')) {
                    div.style.background = 'white';
                }
            });

            div.addEventListener('click', () => {
                this.selectItem(item);
            });

            this.resultsContainer.appendChild(div);
        });

        this.showResults();
    }

    formatItem(item) {
        // Products
        if (item.sku) {
            return `
                <div>
                    <strong>${item.sku}</strong> - ${item.name}
                    ${item.brand ? `<br><small class="text-muted">${item.brand}</small>` : ''}
                    ${item.current_qty !== undefined ? 
                        `<span class="badge bg-${item.is_low_stock ? 'danger' : 'success'} float-end">
                            متوفر: ${item.current_qty}
                        </span>` : ''}
                </div>
            `;
        }

        // Customers
        if (item.code) {
            return `
                <div>
                    <strong>${item.code}</strong> - ${item.name}
                    ${item.phone ? `<br><small class="text-muted">${item.phone}</small>` : ''}
                    ${item.balance !== undefined ? 
                        `<span class="badge bg-info float-end">الرصيد: ${item.balance}</span>` : ''}
                </div>
            `;
        }

        // Default
        return `<div>${item.label || item.name}</div>`;
    }

    selectItem(item) {
        // ملء الـ input
        this.input.value = item.label || item.name;

        // ملء الحقل المخفي (ID)
        if (this.options.targetField) {
            const targetField = document.querySelector(`[name="${this.options.targetField}"]`);
            if (targetField) {
                targetField.value = item.id;
            }
        }

        // Callback
        if (this.options.onSelect) {
            this.options.onSelect(item);
        }

        // Trigger custom event
        this.input.dispatchEvent(new CustomEvent('autocomplete:select', {
            detail: item,
            bubbles: true
        }));

        this.hideResults();
    }

    showResults() {
        this.resultsContainer.style.display = 'block';
    }

    hideResults() {
        this.resultsContainer.style.display = 'none';
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-autocomplete]').forEach((element) => {
        new QuickSearch(element);
    });
});

// Export للاستخدام الخارجي
window.QuickSearch = QuickSearch;
