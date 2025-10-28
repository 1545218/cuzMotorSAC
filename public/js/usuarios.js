$(document).ready(function() {
    // Inicializar DataTable usando función común
    var tablaUsuarios = CruzMotor.initDataTable('#tablaUsuarios', {
        "ajax": {
            "url": "?page=usuarios&action=datatable",
            "type": "POST"
        },
        "columns": [
            { "data": "id" },
            { "data": "username" },
            { "data": "nombre_completo" },
            { "data": "email" },
            { "data": "rol", "render": function(data) {
                const roles = {
                    'admin': '<span class="badge badge-danger">Administrador</span>',
                    'vendedor': '<span class="badge badge-primary">Vendedor</span>',
                    'mecanico': '<span class="badge badge-info">Mecánico</span>'
                };
                return roles[data] || data;
            }},
            { "data": "estado", "render": function(data) {
                return data == 1 ? 
                    '<span class="badge badge-success">Activo</span>' : 
                    '<span class="badge badge-secondary">Inactivo</span>';
            }},
            { "data": "ultimo_acceso" },
            { "data": "acciones", "orderable": false }
        ]
    });

    // Nuevo Usuario
    $('#btnNuevoUsuario').click(function() {
        cargarFormularioUsuario('nuevo');
    });

    // Editar Usuario
    $(document).on('click', '.btn-editar', function() {
        var id = $(this).data('id');
        cargarFormularioUsuario('editar', id);
    });

    // Eliminar Usuario
    $(document).on('click', '.btn-eliminar', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: `¿Desea eliminar al usuario "${nombre}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                eliminarUsuario(id);
            }
        });
    });

    // Submit del formulario
    $(document).on('submit', '#formUsuario', function(e) {
        e.preventDefault();
        
    var formData = new FormData(this);
    var isEdit = formData.get('id') ? true : false;
    var url = isEdit ? '?page=usuarios&action=actualizar' : '?page=usuarios&action=crear';
        
        // Validaciones
        if (!validarFormulario()) {
            return false;
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#formUsuario button[type="submit"]').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            },
            success: function(response) {
                if (response.success) {
                    $('#modalUsuario').modal('hide');
                    tablaUsuarios.ajax.reload();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message,
                        timer: 2000
                    });
                } else {
                    CruzMotor.mostrarErrores(response.errors);
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al procesar la solicitud'
                });
            },
            complete: function() {
                $('#formUsuario button[type="submit"]').prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Guardar');
            }
        });
    });

    // Funciones auxiliares
    function cargarFormularioUsuario(accion, id = null) {
    var url = accion === 'nuevo' ? '?page=usuarios&action=form' : '?page=usuarios&action=form&id=' + id;
        var titulo = accion === 'nuevo' ? 'Nuevo Usuario' : 'Editar Usuario';
        
        $('#modalUsuarioTitle').text(titulo);
        
        $.get(url, function(data) {
            $('#modalUsuarioContent').html(data);
            $('#modalUsuario').modal('show');
        }).fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar el formulario'
            });
        });
    }

    function eliminarUsuario(id) {
        $.ajax({
            url: '?page=usuarios&action=eliminar&id=' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]') .attr('content')
            },
            success: function(response) {
                if (response.success) {
                    tablaUsuarios.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: response.message,
                        timer: 2000
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar el usuario'
                });
            }
        });
    }

    function validarFormulario() {
        var valido = true;
        
        // Limpiar errores previos
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Validar campos requeridos
        $('#formUsuario [required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').text('Este campo es obligatorio');
                valido = false;
            }
        });
        
        // Validar confirmación de contraseña
        var password = $('#password').val();
        var passwordConfirm = $('#password_confirm').val();
        
        if (password && password !== passwordConfirm) {
            $('#password_confirm').addClass('is-invalid');
            $('#password_confirm').next('.invalid-feedback').text('Las contraseñas no coinciden');
            valido = false;
        }
        
        // Validar email
        var email = $('#email').val();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
            $('#email').next('.invalid-feedback').text('Ingrese un email válido');
            valido = false;
        }
        
        return valido;
    }

    // Cambio de estado
    $(document).on('click', '.btn-toggle-estado', function() {
        var id = $(this).data('id');
        var estado = $(this).data('estado');
        var nuevoEstado = estado == 1 ? 0 : 1;
        
        $.ajax({
            url: '?page=usuarios&action=cambiar-estado&id=' + id,
            type: 'POST',
            data: {
                estado: nuevoEstado,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    tablaUsuarios.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        });
    });
});