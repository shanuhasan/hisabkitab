import './bootstrap';
import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

// Expose globals for Blade views
window.bootstrap = bootstrap;
window.Chart = Chart;

// Global UI helpers (Sidebar toggle, auto-dismiss alerts, confirmation dialogs)
document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle for mobile devices
    const sidebarToggle = document.getElementById('hkSidebarToggle');
    const sidebar = document.getElementById('hkSidebar');
    const backdrop = document.getElementById('hkBackdrop');

    if (sidebarToggle && sidebar && backdrop) {
        const toggleMenu = () => {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        };

        sidebarToggle.addEventListener('click', toggleMenu);
        backdrop.addEventListener('click', toggleMenu);
    }

    // Auto-dismiss standard alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach((alert) => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });
});
