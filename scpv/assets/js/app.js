// JavaScript principal para SCPV
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicialización
    initializeApp();
    
    function initializeApp() {
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Setup CSRF token for AJAX requests
        setupCSRFToken();
        
        // Setup form validation
        setupFormValidation();
        
        // Setup sidebar toggle for mobile
        setupSidebarToggle();
        
        // Setup data tables
        setupDataTables();
        
        // Setup confirm dialogs
        setupConfirmDialogs();
        
        // Auto-save functionality
        setupAutoSave();
    }
    
    // CSRF Token setup
    function setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            // Setup for jQuery AJAX (if using jQuery)
            if (typeof $ !== 'undefined') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': token.getAttribute('content')
                    }
                });
            }
            
            // Setup for Fetch API
            window.csrfToken = token.getAttribute('content');
        }
    }
    
    // Form validation
    function setupFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
        
        // Real-time validation
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    }
    
    function validateField(field) {
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        
        if (field.checkValidity()) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            if (feedback) feedback.style.display = 'none';
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            if (feedback) feedback.style.display = 'block';
        }
    }
    
    // Sidebar toggle for mobile
    function setupSidebarToggle() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            });
        }
    }
    
    // Data tables setup
    function setupDataTables() {
        const tables = document.querySelectorAll('.data-table');
        
        tables.forEach(table => {
            // Add basic sorting functionality
            const headers = table.querySelectorAll('th[data-sortable]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    sortTable(table, this);
                });
            });
            
            // Add search functionality if search input exists
            const searchInput = document.querySelector(`#search-${table.id}`);
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterTable(table, this.value);
                });
            }
        });
    }
    
    function sortTable(table, header) {
        const column = Array.from(header.parentNode.children).indexOf(header);
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAscending = !header.classList.contains('sort-asc');
        
        // Remove previous sort classes
        header.parentNode.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        // Add current sort class
        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        
        // Sort rows
        rows.sort((a, b) => {
            const aValue = a.cells[column].textContent.trim();
            const bValue = b.cells[column].textContent.trim();
            
            if (isAscending) {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });
        
        // Reorder rows in DOM
        rows.forEach(row => tbody.appendChild(row));
    }
    
    function filterTable(table, searchTerm) {
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm.toLowerCase())) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Confirm dialogs
    function setupConfirmDialogs() {
        const confirmButtons = document.querySelectorAll('[data-confirm]');
        
        confirmButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                const message = this.getAttribute('data-confirm');
                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }
    
    // Auto-save functionality
    function setupAutoSave() {
        const autoSaveForms = document.querySelectorAll('[data-autosave]');
        
        autoSaveForms.forEach(form => {
            const inputs = form.querySelectorAll('input, textarea, select');
            let timeout;
            
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        saveFormData(form);
                    }, 2000); // Save after 2 seconds of inactivity
                });
            });
        });
    }
    
    function saveFormData(form) {
        const formData = new FormData(form);
        const autoSaveUrl = form.getAttribute('data-autosave');
        
        fetch(autoSaveUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Guardado automáticamente', 'success');
            }
        })
        .catch(error => {
            console.error('Error en auto-guardado:', error);
        });
    }
    
    // Utility functions
    window.showToast = function(message, type = 'info', duration = 3000) {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    };
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
    
    window.showLoading = function(element) {
        const originalText = element.innerHTML;
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...';
        element.disabled = true;
        
        return function() {
            element.innerHTML = originalText;
            element.disabled = false;
        };
    };
    
    window.formatCurrency = function(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(amount);
    };
    
    window.formatDate = function(date) {
        return new Intl.DateTimeFormat('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    };
    
    // AJAX helper
    window.ajaxRequest = function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.csrfToken
            }
        };
        
        return fetch(url, { ...defaults, ...options })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    };
    
    // Export functions for global use
    window.SCPV = {
        showToast,
        showLoading,
        formatCurrency,
        formatDate,
        ajaxRequest,
        validateField
    };
});

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', function(event) {
    console.error('Error no manejado:', event.reason);
    window.SCPV.showToast('Ha ocurrido un error inesperado', 'danger');
});

// Handle global errors
window.addEventListener('error', function(event) {
    console.error('Error:', event.error);
    if (event.error.message.includes('fetch')) {
        window.SCPV.showToast('Error de conexión', 'warning');
    }
});