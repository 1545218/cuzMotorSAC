<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Navegación breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="?page=dashboard" class="text-decoration-none">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="?page=clientes" class="text-decoration-none">
                            <i class="fas fa-users"></i> Clientes
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= $data['action'] === 'create' ? 'Registrar Cliente' : 'Editar Cliente' ?>
                    </li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary mb-0">
                    <i class="fas fa-<?= $data['action'] === 'create' ? 'plus' : 'edit' ?> me-2"></i>
                    <?= $data['action'] === 'create' ? 'Registrar Cliente' : 'Editar Cliente' ?>
                </h2>
                <a href="?page=clientes" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        Información del Cliente
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Prefer explicit form_action provided by controller, fall back to legacy behavior.
                    $formAction = isset($data['form_action']) ? $data['form_action'] : (
                        ($data['action'] === 'create') ? '?page=clientes&action=store' : '?page=clientes&action=update'
                    );
                    ?>
                    <form method="POST" action="<?= $formAction ?>" id="clienteForm">
                        <?php
                        $formErrors = $data['form_errors'] ?? [];
                        $old = $data['old_input'] ?? [];
                        if ($data['action'] === 'edit' && $data['cliente']): ?>
                            <input type="hidden" name="id" value="<?= $data['cliente']['id_cliente'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">

                        <div class="row">
                            <!-- Información Personal -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-user me-2"></i>Información Personal
                                </h6>

                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nombre *
                                    </label>
                                    <input type="text"
                                        class="form-control <?= isset($formErrors['nombre']) ? 'is-invalid' : '' ?>"
                                        id="nombre"
                                        name="nombre"
                                        value="<?= htmlspecialchars($old['nombre'] ?? $data['cliente']['nombre'] ?? '') ?>"
                                        maxlength="100"
                                        required>
                                    <?php if (isset($formErrors['nombre'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($formErrors['nombre']) ?></div>
                                    <?php else: ?>
                                        <div class="form-text">Mximo 100 caracteres</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="apellido" class="form-label">
                                        <i class="fas fa-user me-1"></i>Apellido (opcional)
                                    </label>
                                    <input type="text"
                                        class="form-control <?= isset($formErrors['apellido']) ? 'is-invalid' : '' ?>"
                                        id="apellido"
                                        name="apellido"
                                        value="<?= htmlspecialchars($old['apellido'] ?? $data['cliente']['apellido'] ?? '') ?>"
                                        maxlength="100">
                                    <?php if (isset($formErrors['apellido'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($formErrors['apellido']) ?></div>
                                    <?php else: ?>
                                        <div class="form-text">Mximo 100 caracteres</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email
                                    </label>
                                    <input type="email"
                                        class="form-control <?= isset($formErrors['email']) ? 'is-invalid' : '' ?>"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars($old['email'] ?? $data['cliente']['email'] ?? '') ?>"
                                        placeholder="cliente@ejemplo.com">
                                    <?php if (isset($formErrors['email'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($formErrors['email']) ?></div>
                                    <?php else: ?>
                                        <div class="form-text">Opcional - Formato vlido de email</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Teléfono
                                    </label>
                                    <input type="tel"
                                        class="form-control <?= isset($formErrors['telefono']) ? 'is-invalid' : '' ?>"
                                        id="telefono"
                                        name="telefono"
                                        value="<?= htmlspecialchars($old['telefono'] ?? $data['cliente']['telefono'] ?? '') ?>"
                                        maxlength="20"
                                        placeholder="999-999-999">
                                    <?php if (isset($formErrors['telefono'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($formErrors['telefono']) ?></div>
                                    <?php else: ?>
                                        <div class="form-text">Opcional - Mximo 20 caracteres</div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="distrito" class="form-label">Distrito</label>
                                    <input type="text" class="form-control" id="distrito" name="distrito" value="<?= htmlspecialchars($old['distrito'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="provincia" class="form-label">Provincia</label>
                                    <input type="text" class="form-control" id="provincia" name="provincia" value="<?= htmlspecialchars($old['provincia'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="departamento" class="form-label">Departamento</label>
                                    <input type="text" class="form-control" id="departamento" name="departamento" value="<?= htmlspecialchars($old['departamento'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($old['fecha_nacimiento'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Documentación -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-id-card me-2"></i>Documentación
                                </h6>

                                <div class="mb-3">
                                    <label for="tipo_documento" class="form-label">
                                        <i class="fas fa-id-badge me-1"></i>Tipo de Documento *
                                    </label>
                                    <select class="form-select <?= isset($formErrors['tipo_documento']) ? 'is-invalid' : '' ?>" id="tipo_documento" name="tipo_documento" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="DNI" <?= ($old['tipo_documento'] ?? ($data['cliente']['tipo_documento'] ?? '')) === 'DNI' ? 'selected' : '' ?>>
                                            DNI - Documento Nacional de Identidad
                                        </option>
                                        <option value="RUC" <?= ($old['tipo_documento'] ?? ($data['cliente']['tipo_documento'] ?? '')) === 'RUC' ? 'selected' : '' ?>>
                                            RUC - Registro Único de Contribuyentes


                                    </select>
                                    <?php if (isset($formErrors['tipo_documento'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($formErrors['tipo_documento']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="numero_documento" class="form-label">
                                        <i class="fas fa-hashtag me-1"></i>Número de Documento *
                                    </label>
                                    <div class="input-group">
                                        <input type="text"
                                            class="form-control <?= isset($formErrors['numero_documento']) ? 'is-invalid' : '' ?>"
                                            id="numero_documento"
                                            name="numero_documento"
                                            value="<?= htmlspecialchars($old['numero_documento'] ?? $data['cliente']['numero_documento'] ?? '') ?>"
                                            required>
                                        <button type="button" class="btn btn-info" onclick="buscarDatosDocumento()">Buscar</button>
                                    </div>
                                    <?php if (isset($formErrors['numero_documento'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($formErrors['numero_documento']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>Dirección
                                    </label>
                                    <textarea class="form-control <?= isset($formErrors['direccion']) ? 'is-invalid' : '' ?>"
                                        id="direccion"
                                        name="direccion"
                                        rows="3"
                                        maxlength="255"
                                        placeholder="Direccin completa del cliente..."><?= htmlspecialchars($old['direccion'] ?? $data['cliente']['direccion'] ?? '') ?></textarea>
                                    <?php if (isset($formErrors['direccion'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($formErrors['direccion']) ?></div>
                                    <?php else: ?>
                                        <div class="form-text">Opcional - Mximo 255 caracteres</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="?page=clientes" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?= $data['action'] === 'create' ? 'Registrar Cliente' : 'Actualizar Cliente' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoDocumento = document.getElementById('tipo_documento');
        const numeroDocumento = document.getElementById('numero_documento');
        const documentoHelp = document.getElementById('documento_help');

        // Configuración de validaciones por tipo de documento
        const documentConfig = {
            'DNI': {
                pattern: /^\d{8}$/,
                placeholder: '12345678',
                help: 'DNI debe tener exactamente 8 dígitos',
                maxlength: 8
            },
            'RUC': {
                pattern: /^\d{11}$/,
                placeholder: '20123456789',
                help: 'RUC debe tener exactamente 11 dígitos',
                maxlength: 11
            }

        };


        function updateDocumentValidation() {
            if (!window.tipoDocumento || !window.numeroDocumento || !window.documentoHelp) return;
            const tipo = tipoDocumento.value;
            if (tipo && documentConfig[tipo]) {
                const config = documentConfig[tipo];
                if (numeroDocumento) {
                    numeroDocumento.placeholder = config.placeholder;
                    numeroDocumento.maxLength = config.maxlength;
                }
                if (documentoHelp) {
                    documentoHelp.textContent = config.help;
                    documentoHelp.className = 'form-text text-info';
                }
            } else {
                if (numeroDocumento) {
                    numeroDocumento.placeholder = '';
                    numeroDocumento.maxLength = '';
                }
                if (documentoHelp) {
                    documentoHelp.textContent = 'Selecciona un tipo de documento para ver el formato requerido';
                    documentoHelp.className = 'form-text';
                }
            }
        }

        function validateDocument() {
            const tipo = tipoDocumento.value;
            const numero = numeroDocumento.value.trim().toUpperCase();

            if (tipo && numero && documentConfig[tipo]) {
                const isValid = documentConfig[tipo].pattern.test(numero);

                if (isValid) {
                    numeroDocumento.classList.remove('is-invalid');
                    numeroDocumento.classList.add('is-valid');
                    return true;
                } else {
                    numeroDocumento.classList.remove('is-valid');
                    numeroDocumento.classList.add('is-invalid');
                    return false;
                }
            } else {
                numeroDocumento.classList.remove('is-valid', 'is-invalid');
                return true; // No validar si no hay tipo seleccionado
            }
        }

        // Event listeners
        tipoDocumento.addEventListener('change', function() {
            updateDocumentValidation();
            numeroDocumento.value = ''; // Limpiar campo al cambiar tipo
            numeroDocumento.classList.remove('is-valid', 'is-invalid');
        });

        numeroDocumento.addEventListener('input', function() {
            // Para pasaporte, convertir a mayúsculas
            if (tipoDocumento.value === 'PASAPORTE') {
                this.value = this.value.toUpperCase();
            }
            validateDocument();
        });

        numeroDocumento.addEventListener('blur', validateDocument);

        // Validación del formulario
        document.getElementById('clienteForm').addEventListener('submit', function(e) {
            let isValid = true;

            // Validar campos requeridos (apellido es opcional)
            const requiredFields = ['nombre', 'tipo_documento', 'numero_documento'];
            requiredFields.forEach(function(fieldName) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            // Validar documento
            if (!validateDocument()) {
                isValid = false;
            }

            // Validar email si está presente
            const email = document.getElementById('email');
            if (email.value.trim() && !email.checkValidity()) {
                email.classList.add('is-invalid');
                isValid = false;
            } else {
                email.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, corrige los errores en el formulario antes de continuar.');
            }
        });

        // Inicializar validación en carga
        updateDocumentValidation();

        // Limpiar alertas automáticamente
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });

    function buscarDatosDocumento() {
        var tipo = document.getElementById('tipo_documento').value;
        var numero = document.getElementById('numero_documento').value;
        if (!numero || !tipo) return;
        var formData = new FormData();
        formData.append('tipo', tipo);
        formData.append('numero', numero);
        var url = '/CruzMotorSAC/public/api/consulta_documento.php';
        fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                var contentType = response.headers.get('content-type') || '';
                if (!response.ok) {
                    return response.text().then(function(text) {
                        throw new Error('Error en el servidor al consultar documento');
                    });
                }
                if (contentType.indexOf('application/json') === -1) {
                    return response.text().then(function(text) {
                        throw new Error('Respuesta inválida del servidor');
                    });
                }
                return response.json();
            })
            .then(function(data) {
                var el = document.getElementById('documento_fetch_error');
                if (el) el.remove();
                if (data.nombres || data.razonSocial || data.nombre_completo) {
                    // Para DNI
                    if (tipo === 'DNI') {
                        var nombreCompleto = '';
                        if (data.nombre_completo) {
                            nombreCompleto = data.nombre_completo;
                        } else if (data.nombres) {
                            nombreCompleto = data.nombres + ' ' + (data.apellidoPaterno || data.apellido_paterno || '') + ' ' + (data.apellidoMaterno || data.apellido_materno || '');
                        }
                        if (nombreCompleto.trim()) {
                            var nombreInput = document.getElementById('nombre');
                            if (nombreInput) nombreInput.value = nombreCompleto.trim();
                        }
                    }
                    // Para RUC
                    if (tipo === 'RUC') {
                        var razon = data.razonSocial || data.razon_social || data.nombre_o_razon_social || '';
                        var direccion = data.direccion || data.direccion_completa || '';
                        var nombreInput = document.getElementById('nombre');
                        var direccionInput = document.getElementById('direccion');
                        if (nombreInput && razon) nombreInput.value = razon;
                        if (direccionInput && direccion) direccionInput.value = direccion;
                    }
                } else if (data.message) {
                    mostrarErrorDocumento(data.message);
                } else {
                    mostrarErrorDocumento('No se encontraron datos para el documento ingresado.');
                }
            })
            .catch(function(err) {
                mostrarErrorDocumento('No se pudieron obtener datos automáticamente. Intenta de nuevo o completa manualmente.');
            });
    }

    function mostrarErrorDocumento(msg) {
        var el = document.getElementById('documento_fetch_error');
        var numDoc = document.getElementById('numero_documento');
        if (!numDoc) return; // No hacer nada si el campo no existe
        if (!el) {
            el = document.createElement('div');
            el.id = 'documento_fetch_error';
            el.className = 'text-danger small mt-1';
            var parent = numDoc.closest('.mb-3') || numDoc.parentNode;
            if (parent) parent.appendChild(el);
        }
        if (el) el.textContent = msg;
    }
</script>

</div>
</div>