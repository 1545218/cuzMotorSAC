// Funciones comunes para el sistema CruzMotor SAC
const CruzMotor = {
    // Configuración común para DataTables
    datatableConfig: {
        "processing": true,
        "serverSide": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "responsive": true,
        "autoWidth": false
    },

    // Inicializar DataTable con configuración personalizada
    initDataTable: function(selector, config = {}) {
        return $(selector).DataTable({
            ...this.datatableConfig,
            ...config
        });
    },

    // Mostrar mensaje con SweetAlert2
    showAlert: function(type, title, text) {
        return Swal.fire({
            icon: type,
            title: title,
            text: text,
            confirmButtonText: 'Aceptar'
        });
    },

    // Confirmar acción con SweetAlert2
    confirmAction: function(title, text, confirmText = 'Sí, continuar') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar'
        });
    },

    // Validar formularios
    validateForm: function(formSelector) {
        const form = $(formSelector);
        let isValid = true;
        
        form.find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        return isValid;
    },

    // Cargar contenido por AJAX
    loadContent: function(url, container, callback = null) {
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $(container).html(response);
                if (callback) callback(response);
            },
            error: function() {
                CruzMotor.showAlert('error', 'Error', 'No se pudo cargar el contenido');
            }
        });
    },

    // Formatear números con separadores de miles
    formatNumber: function(number) {
        return new Intl.NumberFormat('es-PE').format(number);
    },

    // Formatear moneda
    formatCurrency: function(amount) {
        return 'S/ ' + this.formatNumber(parseFloat(amount).toFixed(2));
    },

    // Mostrar errores de validación en formularios
    mostrarErrores: function(errores) {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        for (var campo in errores) {
            $(`#${campo}`).addClass('is-invalid');
            $(`#${campo}`).next('.invalid-feedback').text(errores[campo]);
        }
    }
};

// Funciones globales para compatibilidad
window.CruzMotor = CruzMotor;

// Propagar CSRF token en todas las peticiones AJAX de jQuery
$(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    if (csrfToken) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-Token': csrfToken
            }
        });
    }

    // Wrapper de fetch que incluye CSRF en cabecera y en body si es POST/PUT/DELETE
    window.csrfFetch = function(url, options = {}) {
        options = options || {};
        options.headers = options.headers || {};
        if (csrfToken) {
            options.headers['X-CSRF-Token'] = csrfToken;
        }

        // Si es POST y body es FormData no tocar, si es JSON asegurar content-type
        if (options.method && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(options.method.toUpperCase())) {
            if (!(options.body instanceof FormData) && !options.headers['Content-Type']) {
                options.headers['Content-Type'] = 'application/json';
                if (options.body && typeof options.body !== 'string') {
                    options.body = JSON.stringify(options.body);
                }
            }
        }

        return fetch(url, options);
    };
});