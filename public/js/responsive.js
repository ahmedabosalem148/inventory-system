/**
 * Mobile Navigation Handler
 * 
 * Handles:
 * - Sidebar toggle on mobile
 * - Overlay backdrop
 * - Swipe gestures
 * - Responsive menu
 */

class MobileNavigation {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.overlay = null;
        this.isOpen = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        
        this.init();
    }

    init() {
        // إنشاء Overlay
        this.createOverlay();
        
        // إنشاء Toggle Button
        this.createToggleButton();
        
        // Event Listeners
        this.attachEventListeners();
        
        // Swipe Gestures
        if (window.innerWidth <= 767) {
            this.enableSwipeGestures();
        }
        
        // Window Resize Handler
        window.addEventListener('resize', () => this.handleResize());
    }

    createOverlay() {
        this.overlay = document.createElement('div');
        this.overlay.className = 'sidebar-overlay';
        document.body.appendChild(this.overlay);
    }

    createToggleButton() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'navbar-toggler';
        toggleBtn.type = 'button';
        toggleBtn.setAttribute('aria-label', 'Toggle navigation');
        toggleBtn.innerHTML = `
            <span class="navbar-toggler-icon">
                <i class="bi bi-list" style="font-size: 1.5rem;"></i>
            </span>
        `;

        toggleBtn.addEventListener('click', () => this.toggle());

        // إدراج في بداية الـ navbar
        navbar.insertBefore(toggleBtn, navbar.firstChild);
    }

    attachEventListeners() {
        // Click على Overlay لإغلاق Sidebar
        this.overlay?.addEventListener('click', () => this.close());

        // Close button داخل Sidebar
        const closeBtn = document.createElement('button');
        closeBtn.className = 'btn btn-link position-absolute top-0 end-0 m-2';
        closeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
        closeBtn.addEventListener('click', () => this.close());
        
        if (this.sidebar) {
            this.sidebar.insertBefore(closeBtn, this.sidebar.firstChild);
        }

        // ESC key لإغلاق Sidebar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }

    enableSwipeGestures() {
        document.addEventListener('touchstart', (e) => {
            this.touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        }, { passive: true });
    }

    handleSwipe() {
        const swipeThreshold = 100;
        const diff = this.touchEndX - this.touchStartX;

        // RTL: Swipe من اليمين لليسار = فتح
        if (document.dir === 'rtl') {
            if (diff < -swipeThreshold && !this.isOpen) {
                this.open();
            } else if (diff > swipeThreshold && this.isOpen) {
                this.close();
            }
        }
        // LTR: Swipe من اليسار لليمين = فتح
        else {
            if (diff > swipeThreshold && !this.isOpen) {
                this.open();
            } else if (diff < -swipeThreshold && this.isOpen) {
                this.close();
            }
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.add('show');
        this.overlay?.classList.add('show');
        document.body.style.overflow = 'hidden';
        this.isOpen = true;

        // Accessibility
        this.sidebar.setAttribute('aria-hidden', 'false');
        
        // Event
        this.sidebar.dispatchEvent(new CustomEvent('sidebar:open'));
    }

    close() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.remove('show');
        this.overlay?.classList.remove('show');
        document.body.style.overflow = '';
        this.isOpen = false;

        // Accessibility
        this.sidebar.setAttribute('aria-hidden', 'true');
        
        // Event
        this.sidebar.dispatchEvent(new CustomEvent('sidebar:close'));
    }

    handleResize() {
        // إغلاق Sidebar تلقائياً على Desktop
        if (window.innerWidth > 767 && this.isOpen) {
            this.close();
        }
    }
}

/**
 * Responsive Table Handler
 * Converts tables to card-based layout on mobile
 */
class ResponsiveTable {
    constructor(table) {
        this.table = table;
        this.init();
    }

    init() {
        if (window.innerWidth <= 767) {
            this.convertToCards();
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth <= 767) {
                this.convertToCards();
            }
        });
    }

    convertToCards() {
        this.table.classList.add('table-mobile-cards');

        // إضافة data-label لكل td
        const headers = Array.from(this.table.querySelectorAll('thead th')).map(th => th.textContent.trim());

        this.table.querySelectorAll('tbody tr').forEach(row => {
            row.querySelectorAll('td').forEach((td, index) => {
                if (headers[index]) {
                    td.setAttribute('data-label', headers[index]);
                }
            });
        });
    }
}

/**
 * Responsive Form Handler
 * Auto-adjusts form layouts on mobile
 */
class ResponsiveForm {
    constructor(form) {
        this.form = form;
        this.init();
    }

    init() {
        if (window.innerWidth <= 767) {
            this.optimizeForMobile();
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth <= 767) {
                this.optimizeForMobile();
            }
        });
    }

    optimizeForMobile() {
        // Stack button groups
        this.form.querySelectorAll('.btn-group').forEach(group => {
            group.classList.add('d-flex', 'flex-column', 'gap-2');
        });

        // Full width buttons
        this.form.querySelectorAll('.d-flex.gap-2').forEach(group => {
            if (group.querySelector('.btn')) {
                group.classList.add('stack-mobile');
            }
        });

        // Collapse filter panels by default on mobile
        this.form.querySelectorAll('.collapse').forEach(collapse => {
            if (!collapse.classList.contains('show')) {
                // Keep collapsed
            }
        });
    }
}

/**
 * Touch-friendly Dropdowns
 */
class TouchDropdown {
    constructor() {
        this.init();
    }

    init() {
        if (window.innerWidth <= 767) {
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const menu = toggle.nextElementSibling;
                    if (menu && menu.classList.contains('dropdown-menu')) {
                        menu.classList.toggle('show');
                    }
                });
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });
        }
    }
}

/**
 * Auto-initialize on DOM ready
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Mobile Navigation
    if (window.innerWidth <= 767) {
        new MobileNavigation();
    }

    // Initialize Responsive Tables
    document.querySelectorAll('.table-responsive table').forEach(table => {
        new ResponsiveTable(table);
    });

    // Initialize Responsive Forms
    document.querySelectorAll('form').forEach(form => {
        new ResponsiveForm(form);
    });

    // Initialize Touch Dropdowns
    new TouchDropdown();

    // Viewport Height Fix للـ Mobile (100vh issue)
    const setViewportHeight = () => {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    };

    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
    window.addEventListener('orientationchange', setViewportHeight);
});

/**
 * Prevent iOS zoom on input focus
 */
if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
    document.addEventListener('DOMContentLoaded', () => {
        const viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.setAttribute('content', 
                'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no'
            );
        }
    });
}

/**
 * Export classes
 */
window.MobileNavigation = MobileNavigation;
window.ResponsiveTable = ResponsiveTable;
window.ResponsiveForm = ResponsiveForm;
