$(document).ready(function() {
    // Inicializar DataTable
    var tablaCotizaciones = $('#tablaCotizaciones').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "?page=cotizaciones&action=datatable",
            "type": "POST"
        },
        "columns": [
            { "data": "numero" },
            { "data": "cliente" },
            { "data": "fecha_cotizacion" },
            { "data": "total", "render": function(data) {
                return 'S/ ' + parseFloat(data).toFixed(2);
            }},
            { "data": "estado", "render": function(data) {
                const estados = {
                    'pendiente': '<span class="badge badge-warning">Pendiente</span>',
                    'aprobada': '<span class="badge badge-success">Aprobada</span>',
                    'rechazada': '<span class="badge badge-danger">Rechazada</span>',
                    'vencida': '<span class="badge badge-secondary">Vencida</span>'
                };
                return estados[data] || data;
            }},
            { "data": "fecha_vencimiento" },
            { "data": "acciones", "orderable": false }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "responsive": true,
        "autoWidth": false
    });

    // Nueva Cotización
    $('#btnNuevaCotizacion').click(function() {
        window.location.href = '?page=cotizaciones&action=nueva';
    });

    // Ver detalle
    $(document).on('click', '.btn-ver', function() {
        var id = $(this).data('id');
    window.location.href = '?page=cotizaciones&action=detalle&id=' + id;
    });

    // Editar cotización
    $(document).on('click', '.btn-editar', function() {
        var id = $(this).data('id');
    window.location.href = '?page=cotizaciones&action=editar&id=' + id;
    });

    // Eliminar cotización
    $(document).on('click', '.btn-eliminar', function() {
        var id = $(this).data('id');
        var numero = $(this).data('numero');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: `¿Desea eliminar la cotización ${numero}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                eliminarCotizacion(id);
            }
        });
    });

    // Cambiar estado
    $(document).on('click', '.btn-cambiar-estado', function() {
        var id = $(this).data('id');
        var estadoActual = $(this).data('estado');
        
        // Mostrar modal para cambiar estado
        mostrarModalEstado(id, estadoActual);
    });

    function eliminarCotizacion(id) {
        $.ajax({
            url: '?page=cotizaciones&action=eliminar&id=' + id,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    tablaCotizaciones.ajax.reload();
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
            }
        });
    }
});