/**
 * Avaritia — Main JavaScript
 * Client-side interactions & utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initMobileNav();
    initUserDropdown();
    initAdminSidebar();
    initCountdownTimers();
    initScrollReveal();
    initFormValidation();
    initToastSystem();
});

/* ==================== Navbar Scroll Effect ==================== */
function initNavbar() {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
}

/* ==================== Mobile Navigation ==================== */
function initMobileNav() {
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
        toggle.classList.toggle('active');
        menu.classList.toggle('show');
        document.body.style.overflow = menu.classList.contains('show') ? 'hidden' : '';
    });

    // Close menu when clicking a link
    menu.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            toggle.classList.remove('active');
            menu.classList.remove('show');
            document.body.style.overflow = '';
        });
    });
}

/* ==================== User Dropdown ==================== */
function initUserDropdown() {
    const btn = document.getElementById('userMenuBtn');
    const dropdown = document.getElementById('userDropdown');
    if (!btn || !dropdown) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });

    document.addEventListener('click', () => {
        dropdown.classList.remove('show');
    });
}

/* ==================== Admin Sidebar Toggle ==================== */
function initAdminSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 &&
            !sidebar.contains(e.target) &&
            !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });
}

/* ==================== Countdown Timers ==================== */
function initCountdownTimers() {
    const timers = document.querySelectorAll('[data-countdown]');
    if (!timers.length) return;

    function updateTimer(el) {
        const endTime = new Date(el.dataset.countdown).getTime();
        const now = Date.now();
        const diff = endTime - now;

        if (diff <= 0) {
            el.innerHTML = '<span class="timer-segment">Ended</span>';
            return false;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        let html = '';
        if (days > 0) html += `<span class="timer-segment">${days}d</span>`;
        html += `<span class="timer-segment">${String(hours).padStart(2, '0')}h</span>`;
        html += `<span class="timer-segment">${String(minutes).padStart(2, '0')}m</span>`;
        html += `<span class="timer-segment">${String(seconds).padStart(2, '0')}s</span>`;

        el.innerHTML = html;
        return true;
    }

    timers.forEach(el => updateTimer(el));

    setInterval(() => {
        timers.forEach(el => updateTimer(el));
    }, 1000);
}

/* ==================== Scroll Reveal Animation ==================== */
function initScrollReveal() {
    const reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    reveals.forEach(el => observer.observe(el));
}

/* ==================== Form Validation ==================== */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            
            // Clear previous errors
            form.querySelectorAll('.form-error').forEach(el => el.remove());
            form.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));
            
            // Check required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    showFieldError(field, 'This field is required');
                }
            });
            
            // Check email fields
            form.querySelectorAll('input[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    valid = false;
                    showFieldError(field, 'Please enter a valid email address');
                }
            });
            
            // Check password match
            const password = form.querySelector('#password');
            const confirm = form.querySelector('#confirm_password');
            if (password && confirm && password.value !== confirm.value) {
                valid = false;
                showFieldError(confirm, 'Passwords do not match');
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
}

function showFieldError(field, message) {
    field.classList.add('error');
    const error = document.createElement('div');
    error.className = 'form-error';
    error.textContent = message;
    error.style.cssText = 'color: #f87171; font-size: 0.75rem; margin-top: 0.25rem;';
    field.parentNode.appendChild(error);
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/* ==================== Toast Notification System ==================== */
let toastContainer = null;

function initToastSystem() {
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);
}

function showToast(message, type = 'info', duration = 4000) {
    if (!toastContainer) initToastSystem();
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    const colors = {
        success: '#34d399',
        error: '#f87171',
        warning: '#fbbf24',
        info: '#60a5fa'
    };
    
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
        <span style="color: ${colors[type]}; font-size: 1.2rem; font-weight: bold;">${icons[type]}</span>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/* ==================== Utility: Format Currency ==================== */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount);
}

/* ==================== Utility: Confirm Delete ==================== */
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`);
}
