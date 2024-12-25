/**
 * Admin functionality for Attribute Login Access
 */
class ATTRLA_Admin {
    /**
     * Initialize the admin functionality
     */
    constructor() {
        this.initializeEvents();
        this.initializeTabs();
        this.initializeDataTables();
        this.initializeTooltips();
        this.setupAjaxHandlers();
    }

    /**
     * Initialize event listeners
     */
    initializeEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeConfirmActions();
            this.initializeStatusUpdates();
        });
    }

    /**
     * Initialize tab navigation
     */
    initializeTabs() {
        const tabContainers = document.querySelectorAll('.attrla-tabs');
        
        tabContainers.forEach(container => {
            const tabs = container.querySelectorAll('.attrla-tab');
            const panels = container.querySelectorAll('.attrla-tab-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Remove active class from all tabs and panels
                    tabs.forEach(t => t.classList.remove('active'));
                    panels.forEach(p => p.classList.remove('active'));

                    // Add active class to clicked tab and panel
                    tab.classList.add('active');
                    const panel = container.querySelector(tab.getAttribute('href'));
                    if (panel) {
                        panel.classList.add('active');
                    }

                    // Store active tab in session
                    sessionStorage.setItem('attrlaActiveTab', tab.getAttribute('href'));
                });
            });

            // Restore active tab from session
            const activeTab = sessionStorage.getItem('attrlaActiveTab');
            if (activeTab) {
                const tab = container.querySelector(`[href="${activeTab}"]`);
                if (tab) {
                    tab.click();
                }
            } else {
                // Activate first tab by default
                const firstTab = tabs[0];
                if (firstTab) {
                    firstTab.click();
                }
            }
        });
    }

    /**
     * Initialize DataTables for log viewing
     */
    initializeDataTables() {
        const tables = document.querySelectorAll('.attrla-datatable');
        
        tables.forEach(table => {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable !== 'undefined') {
                jQuery(table).DataTable({
                    order: [[0, 'desc']], // Sort by first column (usually date) descending
                    pageLength: 25,
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf'],
                    language: {
                        search: attrlaAdmin.i18n.search,
                        lengthMenu: attrlaAdmin.i18n.lengthMenu,
                        info: attrlaAdmin.i18n.info
                    }
                });
            }
        });
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltips = document.querySelectorAll('.attrla-tooltip');
        
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', (e) => {
                const content = tooltip.getAttribute('data-tooltip');
                if (!content) return;

                const tip = document.createElement('div');
                tip.className = 'attrla-tooltip-content';
                tip.textContent = content;
                document.body.appendChild(tip);

                const rect = tooltip.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                tip.style.left = `${rect.left + (rect.width - tip.offsetWidth) / 2}px`;
                tip.style.top = `${rect.top + scrollTop - tip.offsetHeight - 10}px`;

                tooltip.addEventListener('mouseleave', () => {
                    tip.remove();
                }, { once: true });
            });
        });
    }

    /**
     * Setup AJAX handlers
     */
    setupAjaxHandlers() {
        // Settings form submission
        const settingsForm = document.querySelector('#attrla-settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleSettingsSubmit(settingsForm);
            });
        }

        // IP management actions
        document.querySelectorAll('.attrla-ip-action').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.handleIpAction(button);
            });
        });

        // Log management actions
        const clearLogsButton = document.querySelector('#attrla-clear-logs');
        if (clearLogsButton) {
            clearLogsButton.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.handleClearLogs();
            });
        }
    }

    /**
     * Initialize confirmation actions
     */
    initializeConfirmActions() {
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', (e) => {
                const message = element.getAttribute('data-confirm');
                if (!message || !confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    /**
     * Initialize status updates
     */
    initializeStatusUpdates() {
        const refreshInterval = 30000; // 30 seconds
        setInterval(() => this.updateStatuses(), refreshInterval);
    }

    /**
     * Handle settings form submission
     */
    async handleSettingsSubmit(form) {
        const submitButton = form.querySelector('[type="submit"]');
        submitButton.disabled = true;
        form.classList.add('attrla-loading');

        try {
            const formData = new FormData(form);
            formData.append('action', 'attrla_save_settings');
            formData.append('security', attrlaAdmin.nonce);

            const response = await fetch(attrlaAdmin.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotice(data.message, 'success');
            } else {
                this.showNotice(data.message || attrlaAdmin.i18n.saveError, 'error');
            }
        } catch (error) {
            console.error('Settings save error:', error);
            this.showNotice(attrlaAdmin.i18n.saveError, 'error');
        } finally {
            submitButton.disabled = false;
            form.classList.remove('attrla-loading');
        }
    }

    /**
     * Handle IP management actions
     */
    async handleIpAction(button) {
        const action = button.getAttribute('data-action');
        const ip = button.getAttribute('data-ip');
        
        if (!action || !ip) return;

        button.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('action', 'attrla_ip_action');
            formData.append('security', attrlaAdmin.nonce);
            formData.append('ip_action', action);
            formData.append('ip', ip);

            const response = await fetch(attrlaAdmin.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotice(data.message, 'success');
                if (data.reload) {
                    window.location.reload();
                }
            } else {
                this.showNotice(data.message, 'error');
            }
        } catch (error) {
            console.error('IP action error:', error);
            this.showNotice(attrlaAdmin.i18n.actionError, 'error');
        } finally {
            button.disabled = false;
        }
    }

    /**
     * Handle clearing logs
     */
    async handleClearLogs() {
        if (!confirm(attrlaAdmin.i18n.confirmClearLogs)) return;

        const button = document.querySelector('#attrla-clear-logs');
        if (!button) return;

        button.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('action', 'attrla_clear_logs');
            formData.append('security', attrlaAdmin.nonce);

            const response = await fetch(attrlaAdmin.ajaxurl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                this.showNotice(data.message, 'success');
                window.location.reload();
            } else {
                this.showNotice(data.message, 'error');
            }
        } catch (error) {
            console.error('Clear logs error:', error);
            this.showNotice(attrlaAdmin.i18n.clearLogsError, 'error');
        } finally {
            button.disabled = false;
        }
    }

    /**
     * Show admin notice
     */
    showNotice(message, type = 'info') {
        const notice = document.createElement('div');
        notice.className = `attrla-notice attrla-notice-${type}`;
        notice.textContent = message;

        const container = document.querySelector('.attrla-notices');
        if (container) {
            container.appendChild(notice);
            
            // Remove notice after 5 seconds
            setTimeout(() => {
                notice.remove();
            }, 5000);
        }
    }

    /**
     * Update statuses via AJAX
     */
    async updateStatuses() {
        try {
            const response = await fetch(attrlaAdmin.ajaxurl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'attrla_get_statuses',
                    security: attrlaAdmin.nonce
                }),
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success && data.statuses) {
                this.updateStatusElements(data.statuses);
            }
        } catch (error) {
            console.error('Status update error:', error);
        }
    }

    /**
     * Update status elements in the DOM
     */
    updateStatusElements(statuses) {
        Object.entries(statuses).forEach(([key, value]) => {
            const element = document.querySelector(`[data-status="${key}"]`);
            if (element) {
                element.textContent = value;
            }
        });
    }
}

// Initialize admin functionality
document.addEventListener('DOMContentLoaded', () => {
    window.ATTRLA = new ATTRLA_Admin();
});