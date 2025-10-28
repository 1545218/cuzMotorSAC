    <!-- Cerrar wrappers abiertos en header -->
    </div> <!-- /.content-wrapper -->
    </div> <!-- /.main-content -->

    <!-- Footer -->
    <footer class="bg-light border-top mt-auto">
        <div class="container-fluid">
            <div class="row py-3">
                <div class="col-md-6">
                    <small class="text-muted">
                        &copy; <?= date('Y') ?> <?= COMPANY_NAME ?>. Todos los derechos reservados.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Sistema de Inventario v<?= APP_VERSION ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery PRIMERO -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Script principal del sistema -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad del sidebar móvil
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            } // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    try {
                        bsAlert.close();
                    } catch (e) {
                        // Alert ya fue cerrado manualmente
                    }
                }, 5000);
            });

            // Inicializar tooltips de Bootstrap
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Confirmación para acciones destructivas
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-delete') ||
                e.target.closest('.btn-delete') ||
                e.target.classList.contains('confirm-delete')) {
                if (!confirm('¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.')) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Funciones utilitarias globales
        window.formatNumber = function(num, decimals = 2) {
            return new Intl.NumberFormat('es-PE', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(num);
        };

        window.formatCurrency = function(amount) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN'
            }).format(amount);
        };

        window.formatDate = function(date) {
            return new Intl.DateTimeFormat('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).format(new Date(date));
        };

        // Función para mostrar notificaciones toast
        window.showToast = function(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            const toastId = 'toast-' + Date.now();
            const toastHTML = `
                <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0" 
                     role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : (type === 'success' ? 'check-circle' : 'info-circle')} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                                data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>`;

            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement);
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        };

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1050';
            document.body.appendChild(container);
            return container;
        }
    </script>

    <!-- Scripts específicos de la página -->
    <?php if (isset($scripts) && is_array($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Captura de errores JS y envío al servidor -->
    <script>
        (function() {
            const endpoint = '<?= BASE_PATH ?>/api/log_js.php';

            function send(payload) {
                try {
                    // Enviar con sendBeacon cuando sea posible (no bloqueante)
                    const body = JSON.stringify(payload);
                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(endpoint, body);
                        return;
                    }

                    fetch(endpoint, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body
                    }).catch(function() {
                        // No hacer nada en caso de fallo
                    });
                } catch (e) {
                    // silence
                }
            }

            // Capturar errores sincrónicos
            window.addEventListener('error', function(event) {
                const payload = {
                    level: 'error',
                    message: event.message || 'Script error',
                    file: event.filename || null,
                    line: event.lineno || null,
                    col: event.colno || null,
                    stack: (event.error && event.error.stack) ? event.error.stack : null,
                    url: location.href
                };
                send(payload);
            });

            // Capturar promesas rechazadas sin catch
            window.addEventListener('unhandledrejection', function(event) {
                const reason = event.reason || {};
                const payload = {
                    level: 'error',
                    message: reason.message || (typeof reason === 'string' ? reason : 'Unhandled rejection'),
                    stack: reason.stack || null,
                    url: location.href
                };
                send(payload);
            });

            // Interceptar console.error para enviar al servidor (opcional)
            const originalConsoleError = console.error;
            console.error = function() {
                try {
                    const args = Array.from(arguments).map(function(a) {
                        try {
                            return (typeof a === 'object') ? JSON.stringify(a) : String(a);
                        } catch (e) {
                            return String(a);
                        }
                    }).join(' ');
                    send({
                        level: 'error',
                        message: args,
                        url: location.href
                    });
                } catch (e) {}
                originalConsoleError.apply(console, arguments);
            };
        })();
    </script>

    </body>

    </html>