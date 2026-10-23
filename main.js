/**
 * Campus Canteen Management System - Main JavaScript
 * Animations, Charts, Interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Preloader
    setTimeout(() => {
        const preloader = document.getElementById('preloader');
        if (preloader) {
            preloader.classList.add('hidden');
        }
    }, 1800);

    // Sidebar toggle
    window.toggleSidebar = function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar && overlay) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
    };

    // Notifications dropdown
    window.toggleNotifications = function() {
        const dropdown = document.getElementById('notificationDropdown');
        const profileDropdown = document.getElementById('profileDropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
            if (profileDropdown) profileDropdown.classList.remove('show');
        }
    };

    // Profile dropdown
    window.toggleProfile = function() {
        const dropdown = document.getElementById('profileDropdown');
        const notifDropdown = document.getElementById('notificationDropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
            if (notifDropdown) notifDropdown.classList.remove('show');
        }
    };

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        const notifWrapper = document.querySelector('.notification-wrapper');
        const profileWrapper = document.querySelector('.profile-wrapper');
        
        if (notifWrapper && !notifWrapper.contains(e.target)) {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }
        
        if (profileWrapper && !profileWrapper.contains(e.target)) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }
    });

    // Password toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(30px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Tab system
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            const tabContainer = this.closest('.tabs-container');
            
            tabContainer.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            tabContainer.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            const panel = document.getElementById(tabId);
            if (panel) panel.classList.add('active');
        });
    });

    // Modal system
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Initialize charts if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        initCharts();
    }

    // Animate elements on scroll
    animateOnScroll();

    // Counter animation
    animateCounters();

    // Table row animation
    animateTableRows();

    // Confirm delete
    window.confirmDelete = function(message) {
        return confirm(message || 'Are you sure you want to delete this item?');
    };

    // Print function
    window.printInvoice = function() {
        window.print();
    };

    // Export to PDF
    window.exportToPDF = function(elementId, filename) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        if (typeof jspdf !== 'undefined') {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.html(element, {
                callback: function(doc) {
                    doc.save(filename || 'document.pdf');
                },
                x: 10,
                y: 10,
                width: 190,
                windowWidth: 800
            });
        } else {
            window.print();
        }
    };

    // Global search
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('input', debounce(function() {
            const query = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }, 300));
    }

    // Form validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--danger)';
                    field.addEventListener('input', function() {
                        this.style.borderColor = '';
                    }, { once: true });
                }
            });
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
});

// Initialize all charts
function initCharts() {
    // Sales Overview Chart
    const salesChartEl = document.getElementById('salesChart');
    if (salesChartEl && window.salesChartData) {
        new Chart(salesChartEl, {
            type: 'line',
            data: {
                labels: window.salesChartData.labels,
                datasets: [{
                    label: 'Sales (¥)',
                    data: window.salesChartData.sales,
                    borderColor: '#e63946',
                    backgroundColor: 'rgba(230, 57, 70, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#e63946',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                                callback: function(value) {
                                    return '¥' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    // Orders by Status Doughnut Chart
    const orderStatusChartEl = document.getElementById('orderStatusChart');
    if (orderStatusChartEl && window.orderStatusData) {
        new Chart(orderStatusChartEl, {
            type: 'doughnut',
            data: {
                labels: window.orderStatusData.labels,
                datasets: [{
                    data: window.orderStatusData.values,
                    backgroundColor: ['#2ecc71', '#3498db', '#f39c12', '#e74c3c'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 2000
                }
            }
        });
    }

    // Sales by Category Bar Chart
    const categoryChartEl = document.getElementById('categoryChart');
    if (categoryChartEl && window.categoryChartData) {
        new Chart(categoryChartEl, {
            type: 'bar',
            data: {
                labels: window.categoryChartData.labels,
                datasets: [{
                    label: 'Sales (¥)',
                    data: window.categoryChartData.values,
                    backgroundColor: [
                        'rgba(230, 57, 70, 0.8)',
                        'rgba(52, 152, 219, 0.8)',
                        'rgba(46, 204, 113, 0.8)',
                        'rgba(243, 156, 18, 0.8)',
                        'rgba(155, 89, 182, 0.8)',
                        'rgba(26, 53, 87, 0.8)'
                    ],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    // Daily Orders Chart
    const dailyOrdersChartEl = document.getElementById('dailyOrdersChart');
    if (dailyOrdersChartEl && window.dailyOrdersData) {
        new Chart(dailyOrdersChartEl, {
            type: 'line',
            data: {
                labels: window.dailyOrdersData.labels,
                datasets: [{
                    label: 'Orders',
                    data: window.dailyOrdersData.values,
                    borderColor: '#2ecc71',
                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2ecc71',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }

    // Payment Method Chart
    const paymentChartEl = document.getElementById('paymentChart');
    if (paymentChartEl && window.paymentData) {
        new Chart(paymentChartEl, {
            type: 'doughnut',
            data: {
                labels: window.paymentData.labels,
                datasets: [{
                    data: window.paymentData.values,
                    backgroundColor: ['#3498db', '#2ecc71', '#f39c12'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 2000
                }
            }
        });
    }

    // Inventory Chart
    const inventoryChartEl = document.getElementById('inventoryChart');
    if (inventoryChartEl && window.inventoryChartData) {
        new Chart(inventoryChartEl, {
            type: 'bar',
            data: {
                labels: window.inventoryChartData.labels,
                datasets: [
                    {
                        label: 'Stock In',
                        data: window.inventoryChartData.stockIn,
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'Stock Out',
                        data: window.inventoryChartData.stockOut,
                        backgroundColor: 'rgba(230, 57, 70, 0.8)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { padding: 15, font: { size: 12 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeOutQuart'
                }
            }
        });
    }
}

// Animate elements on scroll
function animateOnScroll() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.stat-card, .card, .menu-card').forEach(el => {
        observer.observe(el);
    });
}

// Counter animation
function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseInt(counter.dataset.counter);
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;

        const updateCounter = () => {
            current += step;
            if (current < target) {
                counter.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(counter);
                }
            });
        });

        observer.observe(counter);
    });
}

// Table row stagger animation
function animateTableRows() {
    const rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        row.style.transition = `all 0.3s ease ${index * 0.05}s`;
        
        setTimeout(() => {
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, 100);
    });
}

// Debounce utility
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Confirm action with custom modal
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Dynamic clock
function updateClock() {
    const clockElements = document.querySelectorAll('.live-clock');
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    clockElements.forEach(el => el.textContent = timeString);
}

if (document.querySelectorAll('.live-clock').length > 0) {
    setInterval(updateClock, 1000);
    updateClock();
}

// Smooth scroll to top
window.scrollToTop = function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Handle form beforeunload
window.addEventListener('beforeunload', function(e) {
    const forms = document.querySelectorAll('form[data-dirty]');
    if (forms.length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Mark form as dirty on input
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('input', function() {
        this.dataset.dirty = 'true';
    });
});
