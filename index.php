<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Práctica 5 - Sistema de Citas</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="handsontable/handsontable.full.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Handsontable JS -->
    <script src="handsontable/handsontable.full.min.js"></script>
    
    <style>
        .horario-column {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        .filter-section {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .search-section {
            background-color: #f1f3f4;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        /* Estilos para mejorar la visualización de grupos horarios */
        .ht_master .wtHolder .wtTable tbody tr:nth-child(4n+1) td {
            border-top: 2px solid #007bff !important;
        }
        .ht_master .wtHolder .wtTable tbody tr:nth-child(4n+2) td,
        .ht_master .wtHolder .wtTable tbody tr:nth-child(4n+3) td {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1 class="text-center mb-4">Práctica 5 - Sistema de Citas</h1>
        
        <div class="card">
            <div class="card-header">
                <h4>Sistema de Citas</h4>
            </div>
            <div class="card-body">
                
                <!-- Buscador de citas -->
                <div class="search-section">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="buscador-citas"><strong>Buscar Citas:</strong></label>
                            <input type="text" id="buscador-citas" class="form-control" placeholder="Buscar por nombre, teléfono o ejecutivo...">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary" onclick="buscarCitas()">Buscar</button>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-secondary" onclick="limpiarBusqueda()">Actualizar tabla</button>
                        </div>
                    </div>
                </div>
                
                <!-- Filtro de fecha -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha-filtro"><strong>Filtro por Fecha:</strong></label>
                            <input type="date" id="fecha-filtro" class="form-control" value="">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-info" onclick="cargarCitas()">Filtrar</button>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-success" onclick="agregarNuevaCita()">Agregar Cita</button>
                        </div>
                    </div>
                </div>

                <!-- Contenedor Handsontable -->
                <div id="tabla-citas" style="width: 100%; height: 600px;"></div>
                
            </div>
        </div>
    </div>

    <script>
        // =====================================
        // CONFIGURACIÓN DE COLUMNAS
        // =====================================
        
        // Configuración fija de columnas
        var columnasConfig = [
            { key: 'horario', header: 'HORARIO', type: 'text', readOnly: true, className: 'horario-column', width: 150 },
            { key: 'id_cit', header: 'ID', type: 'text', readOnly: true, width: 60 },
            { key: 'cit_cit', header: 'FECHA', type: 'date', dateFormat: 'YYYY-MM-DD', width: 120 },
            { key: 'hor_cit', header: 'HORA', type: 'time', timeFormat: 'HH:mm', width: 100 },
            { key: 'nom_cit', header: 'NOMBRE', type: 'text', width: 200 },
            { key: 'tel_cit', header: 'TELÉFONO', type: 'text', width: 150 },
            { key: 'id_eje2', header: 'EJECUTIVO', type: 'dropdown', source: [], width: 180 }
        ];
        
        // Variables globales
        var hot = null;
        var ejecutivos = [];
        var ejecutivosDropdown = [];
        var modoFiltroFecha = true; // true = filtro por fecha, false = búsqueda
        var citasPorRango = 4; // Número de citas por rango horario (2 en blanco + 2 para citas)
        var filaEditandose = null; // Fila que se está editando actualmente
        var datosPendientes = {}; // Datos pendientes de guardar para la fila actual
        
        // =====================================
        // INICIALIZACIÓN
        // =====================================
        
        $(document).ready(function() {
            var fechaHoy = new Date().toISOString().split('T')[0];
            $('#fecha-filtro').val(fechaHoy);
            
            cargarEjecutivos().then(function() {
                inicializarTabla();
                cargarCitas();
            }).catch(function(error) {
                console.error('Error en inicialización:', error);
                inicializarTabla();
                cargarCitas();
            });
        });
        
        // =====================================
        // FUNCIONES DE CONFIGURACIÓN
        // =====================================
        
        function generarHeaders() {
            return columnasConfig.map(function(col) { return col.header; });
        }
        
        function generarColumnas() {
            return columnasConfig.map(function(col) {
                var columna = {
                    type: col.type,
                    width: col.width || 120
                };
                
                if (col.readOnly) columna.readOnly = col.readOnly;
                if (col.className) columna.className = col.className;
                if (col.dateFormat) columna.dateFormat = col.dateFormat;
                if (col.timeFormat) columna.timeFormat = col.timeFormat;
                if (col.type === 'dropdown') {
                    columna.source = col.key === 'id_eje2' ? ejecutivosDropdown : col.source;
                    columna.strict = false;
                }
                
                return columna;
            });
        }
        
        function obtenerCampo(columnIndex) {
            return columnasConfig[columnIndex] ? columnasConfig[columnIndex].key : null;
        }
        
        function obtenerIndiceColumna(campo) {
            for (var i = 0; i < columnasConfig.length; i++) {
                if (columnasConfig[i].key === campo) return i;
            }
            return -1;
        }
        
        // =====================================
        // FUNCIONES DE EJECUTIVOS
        // =====================================
        
        function cargarEjecutivos() {
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: 'server/controlador_citas.php',
                    type: 'POST',
                    data: { action: 'obtener_ejecutivos' },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            ejecutivos = response.data;
                            ejecutivosDropdown = response.data.map(function(eje) {
                                return eje.nom_eje;
                            });
                            
                            // Actualizar configuración de columna dropdown
                            var colEjecutivo = columnasConfig.find(function(col) { return col.key === 'id_eje2'; });
                            if (colEjecutivo) {
                                colEjecutivo.source = ejecutivosDropdown;
                            }
                            
                            console.log('Ejecutivos cargados:', ejecutivos);
                            resolve();
                        } else {
                            reject('Error al cargar ejecutivos');
                        }
                    },
                    error: function() {
                        reject('Error de conexión');
                    }
                });
            });
        }
        
        function obtenerIdEjecutivo(nombreEjecutivo) {
            var ejecutivo = ejecutivos.find(function(eje) {
                return eje.nom_eje === nombreEjecutivo;
            });
            return ejecutivo ? ejecutivo.id_eje : null;
        }
        
        function obtenerNombreEjecutivo(idEjecutivo) {
            var ejecutivo = ejecutivos.find(function(eje) {
                return eje.id_eje == idEjecutivo;
            });
            return ejecutivo ? ejecutivo.nom_eje : '';
        }

        // =====================================
        // TABLA DINÁMICA
        // =====================================
        
        function inicializarTabla() {
            console.log('Inicializando tabla Handsontable...');
            console.log('Configuración de columnas:', columnasConfig);
            
            var container = document.getElementById('tabla-citas');
            var datosBase = generarHorariosFijos();
            
            hot = new Handsontable(container, {
                data: datosBase,
                colHeaders: generarHeaders(),
                columns: generarColumnas(),
                rowHeaders: true,
                height: 600,
                licenseKey: 'non-commercial-and-evaluation',
                contextMenu: true,
                stretchH: 'all',
                
                // Evento para manejar cambios
                afterChange: function(changes, source) {
                    if (changes && source !== 'loadData') {
                        changes.forEach(function([row, prop, oldValue, newValue]) {
                            if (newValue !== oldValue && prop > 0) {
                                manejarCambioEnFila(row, prop, newValue, oldValue);
                            }
                        });
                    }
                },
                
                // Evento cuando se selecciona una celda diferente
                afterSelection: function(row, column, row2, column2, preventScrolling, selectionLayerLevel) {
                    if (filaEditandose !== null && filaEditandose !== row) {
                        // El usuario cambió de fila, guardar cambios pendientes
                        guardarCambiosPendientes();
                    }
                },
                
                // Evento antes de perder el foco
                beforeOnCellMouseDown: function(event, coords, TD) {
                    if (filaEditandose !== null && filaEditandose !== coords.row) {
                        // El usuario hizo clic en otra fila, guardar cambios pendientes
                        guardarCambiosPendientes();
                    }
                },
                
                // Evento antes de validación
                beforeChange: function(changes, source) {
                    if (source === 'edit') {
                        changes.forEach(function([row, prop, oldValue, newValue]) {
                            var campo = obtenerCampo(prop);
                            if (campo === 'id_eje2' && newValue) {
                                var idEjecutivo = obtenerIdEjecutivo(newValue);
                                if (idEjecutivo) {
                                    changes[0][3] = idEjecutivo;
                                }
                            }
                        });
                    }
                },
                
                // Evento antes de eliminar fila - capturar IDs de citas
                beforeRemoveRow: function(index, amount, physicalRows, source) {
                    if (source !== 'loadData') {
                        var idCitIndex = obtenerIndiceColumna('id_cit');
                        var citasAEliminar = [];
                        
                        // Capturar los IDs de las citas antes de que se eliminen
                        physicalRows.forEach(function(rowIndex) {
                            var rowData = hot.getSourceDataAtRow(rowIndex);
                            if (rowData && rowData[idCitIndex]) {
                                citasAEliminar.push(rowData[idCitIndex]);
                                console.log('BEFORE REMOVE ROW: Capturando cita con ID:', rowData[idCitIndex]);
                            }
                        });
                        
                        // Guardar los IDs para procesarlos después
                        hot._citasAEliminar = citasAEliminar;
                    }
                },
                
                // Evento después de eliminar fila - eliminar de base de datos
                afterRemoveRow: function(index, amount, physicalRows, source) {
                    if (source !== 'loadData' && hot._citasAEliminar) {
                        // Procesar las citas capturadas en beforeRemoveRow
                        hot._citasAEliminar.forEach(function(id_cit) {
                            console.log('AFTER REMOVE ROW: Eliminando cita con ID:', id_cit);
                            eliminarCitaBaseDatos(id_cit);
                        });
                        
                        // Limpiar la variable temporal
                        delete hot._citasAEliminar;
                    }
                },
                
                // Renderer personalizado para grupos de horarios
                afterRenderer: function(TD, row, col, prop, value, cellProperties) {
                    var campo = obtenerCampo(col);
                    
                    // Renderer para ejecutivos
                    if (campo === 'id_eje2' && value) {
                        var nombreEjecutivo = obtenerNombreEjecutivo(value);
                        if (nombreEjecutivo) {
                            TD.innerHTML = nombreEjecutivo;
                        }
                    }
                    
                    // Estilo para filas de grupo horario
                    var esInicioGrupo = row % citasPorRango === 0;
                    if (esInicioGrupo && col === 0) {
                        TD.style.borderTop = '3px solid #007bff';
                        TD.style.fontWeight = 'bold';
                    }
                    
                    // Resaltar celdas vacías reservadas
                    var posicionEnGrupo = row % citasPorRango;
                    if (posicionEnGrupo >= 2 && !value && col > 0) {
                        TD.style.backgroundColor = '#ffffff';
                        TD.style.border = '1px dashed #cccccc';
                    }
                }
            });
        }

        function agregarNuevaColumna() {
            // Agregar columna al final de la tabla usando el método más compatible
            console.log('Agregando nueva columna al final de la tabla...');
            
            try {
                // Intentar con insert_col_end primero
                hot.alter('insert_col_end');
            } catch (e) {
                console.log('insert_col_end no disponible, intentando con insert_col_start...');
                try {
                    // Si no funciona, intentar con insert_col_start al final
                    hot.alter('insert_col_start', hot.countCols());
                } catch (e2) {
                    console.log('insert_col_start no disponible, usando método manual...');
                    // Método manual: agregar columna a la configuración y recargar
                    agregarColumnaDinamicaManual();
                }
            }
        }
        
        function agregarColumnaDinamicaManual() {
            console.log('Agregando columna usando método manual...');
            
            var timestamp = Date.now();
            var randomSuffix = Math.floor(Math.random() * 1000);
            var nombreColumna = 'col_dinamica_' + timestamp + '_' + randomSuffix;
            var headerColumna = 'Nueva Columna ' + (columnasConfig.length + 1);
            
            console.log('Intentando crear columna:', nombreColumna);
            console.log('URL del servidor:', 'server/controlador_citas.php');
            
            // Crear la columna en la base de datos primero
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: {
                    action: 'crear_columna_dinamica',
                    nombre_columna: nombreColumna,
                    posicion: columnasConfig.length
                },
                dataType: 'json',
                timeout: 10000, // 10 segundos de timeout
                beforeSend: function() {
                    console.log('Enviando request al servidor...');
                },
                success: function(response) {
                    console.log('Respuesta del servidor recibida:', response);
                    
                    if(response && response.success) {
                        console.log('Columna creada en BD:', response.data.nombre_columna);
                        
                        // Agregar a la configuración local
                        var nuevaColumna = {
                            key: response.data.nombre_columna,
                            header: headerColumna,
                            type: 'text',
                            width: 150
                        };
                        
                        columnasConfig.push(nuevaColumna);
                        
                        // Actualizar la tabla
                        actualizarConfiguracionTabla();
                        
                        // Recargar datos
                        setTimeout(function() {
                            if (modoFiltroFecha) {
                                cargarCitas();
                            } else {
                                buscarCitas();
                            }
                        }, 300);
                        
                    } else {
                        var errorMsg = response && response.message ? response.message : 'Respuesta inválida del servidor';
                        console.error('Error del servidor:', errorMsg);
                        alert('Error al crear columna: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX completo:', {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        readyState: xhr.readyState,
                        statusText: xhr.statusText
                    });
                    
                    var errorMessage = 'Error de conexión al crear columna.\n';
                    errorMessage += 'Status: ' + status + '\n';
                    errorMessage += 'Error: ' + error + '\n';
                    
                    if (xhr.responseText) {
                        errorMessage += 'Respuesta del servidor: ' + xhr.responseText.substring(0, 200);
                    }
                    
                    alert(errorMessage);
                    console.log('Verificando si el archivo del servidor existe...');
                    
                    // Verificar si el archivo del controlador existe
                    $.ajax({
                        url: 'server/controlador_citas.php',
                        type: 'HEAD',
                        success: function() {
                            console.log('✓ El archivo controlador_citas.php existe y es accesible');
                        },
                        error: function() {
                            console.error('✗ El archivo controlador_citas.php NO es accesible');
                            alert('Error: No se puede acceder al archivo server/controlador_citas.php');
                        }
                    });
                }
            });
        }
        
        function eliminarColumnaDinamicaManual(colIndex, nombreColumna) {
            console.log('Eliminando columna dinámica:', nombreColumna, 'en índice:', colIndex);
            
            if (confirm('¿Está seguro de que desea eliminar esta columna? Esta acción no se puede deshacer.')) {
                // Eliminar de la base de datos
                $.ajax({
                    url: 'server/controlador_citas.php',
                    type: 'POST',
                    data: {
                        action: 'eliminar_columna_dinamica',
                        nombre_columna: nombreColumna
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            console.log('Columna eliminada de BD:', nombreColumna);
                            
                            // Eliminar de la configuración local
                            columnasConfig = columnasConfig.filter(function(col) {
                                return col.key !== nombreColumna;
                            });
                            
                            // Actualizar la tabla
                            actualizarConfiguracionTabla();
                            
                            // Recargar datos
                            setTimeout(function() {
                                if (modoFiltroFecha) {
                                    cargarCitas();
                                } else {
                                    buscarCitas();
                                }
                            }, 300);
                            
                        } else {
                            alert('Error al eliminar columna: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX al eliminar columna:', error);
                        alert('Error de conexión al eliminar columna');
                    }
                });
            }
        }
        
        function actualizarConfiguracionTabla() {
            // Actualizar headers
            hot.updateSettings({
                colHeaders: generarHeaders(),
                columns: generarColumnas()
            });
        }
        
        function generarHorariosFijos() {
            var horarios = [];
            for (var h = 8; h <= 20; h++) {
                var inicio = h < 10 ? '0' + h + ':00' : h + ':00';
                var fin = (h + 1) < 10 ? '0' + (h + 1) + ':00' : (h + 1) + ':00';
                var rango = inicio + ' - ' + fin;
                
                // Crear múltiples filas para cada rango horario
                for (var i = 0; i < citasPorRango; i++) {
                    var fila = new Array(columnasConfig.length).fill('');
                    // Solo mostrar el rango en la primera fila de cada grupo
                    fila[0] = i === 0 ? rango : '';
                    horarios.push(fila);
                }
            }
            return horarios;
        }
        
        function manejarCambioEnFila(row, column, newValue, oldValue) {
            var campo = obtenerCampo(column);
            var idCitIndex = obtenerIndiceColumna('id_cit');
            var id_cit = hot.getDataAtCell(row, idCitIndex);
            
            // Establecer la fila que se está editando
            if (filaEditandose === null || filaEditandose !== row) {
                filaEditandose = row;
                datosPendientes = {}; // Limpiar datos pendientes al cambiar de fila
            }
            
            // Guardar el cambio en los datos pendientes
            datosPendientes[campo] = newValue;
            
            console.log('Cambio detectado en fila', row, '- Campo:', campo, '- Valor:', newValue);
            console.log('Datos pendientes para fila', row, ':', datosPendientes);
        }
        
        function guardarCambiosPendientes() {
            if (filaEditandose === null || Object.keys(datosPendientes).length === 0) {
                return;
            }
            
            var idCitIndex = obtenerIndiceColumna('id_cit');
            var id_cit = hot.getDataAtCell(filaEditandose, idCitIndex);
            
            console.log('Guardando cambios pendientes para fila', filaEditandose, '- ID:', id_cit);
            console.log('Datos a guardar:', datosPendientes);
            
            if (!id_cit) {
                // Nueva cita - crear con todos los datos pendientes
                crearNuevaCitaCompleta(filaEditandose, datosPendientes);
            } else {
                // Cita existente - actualizar campos modificados
                actualizarCitaCompleta(filaEditandose, id_cit, datosPendientes);
            }
            
            // Limpiar estado
            filaEditandose = null;
            datosPendientes = {};
        }
        
        function obtenerRangoHorario(fila) {
            // Calcular el rango horario basado en la fila
            var grupoHorario = Math.floor(fila / citasPorRango);
            var hora = grupoHorario + 8;
            
            if (hora >= 8 && hora <= 20) {
                var inicio = hora < 10 ? '0' + hora + ':00' : hora + ':00';
                var fin = (hora + 1) < 10 ? '0' + (hora + 1) + ':00' : (hora + 1) + ':00';
                return inicio + ' - ' + fin;
            }
            return '';
        }
        
        function crearNuevaCitaCompleta(row, datosPendientes) {
            // Recopilar datos de la fila para crear nueva cita
            var rowData = hot.getDataAtRow(row);
            
            // Usar fecha del filtro como valor por defecto si no hay fecha especificada
            var fechaIndex = obtenerIndiceColumna('cit_cit');
            var fecha = datosPendientes['cit_cit'] || rowData[fechaIndex] || $('#fecha-filtro').val() || new Date().toISOString().split('T')[0];
            
            // Generar hora basada en el rango horario si no se especifica
            var horaIndex = obtenerIndiceColumna('hor_cit');
            var hora = datosPendientes['hor_cit'] || rowData[horaIndex];
            if (!hora) {
                var rangoHorario = obtenerRangoHorario(row);
                if (rangoHorario) {
                    hora = rangoHorario.split(' - ')[0] + ':00';
                } else {
                    hora = '09:00:00';
                }
            }
            
            // Asegurar que la hora tenga el formato correcto
            if (hora && hora.length <= 5) {
                hora = hora + ':00';
            }
            
            // Preparar datos dinámicamente basado en la configuración de columnas
            var datos = { action: 'guardar_cita' };
            
            // Combinar datos pendientes con datos de la fila
            columnasConfig.forEach(function(col, index) {
                if (col.key !== 'horario' && col.key !== 'nom_eje') { // Excluir columnas virtuales
                    var valor = datosPendientes[col.key] || rowData[index] || '';
                    
                    // Solo agregar valores no vacíos para permitir NULL en la BD
                    if (valor !== '') {
                        datos[col.key] = valor;
                    }
                }
            });
            
            // Siempre incluir fecha y hora por defecto para evitar problemas
            datos['cit_cit'] = fecha;
            datos['hor_cit'] = hora;
            
            // Validar que al menos haya algún dato significativo para crear la cita
            if (!datos.nom_cit && !datos.tel_cit && !datos.id_eje2) {
                console.log('No hay datos suficientes para crear la cita');
                return;
            }
            
            console.log('Enviando datos para nueva cita completa:', datos);
            
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: datos,
                dataType: 'json',
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    if(response.success) {
                        // Actualizar el ID en la tabla
                        var idCitIndex = obtenerIndiceColumna('id_cit');
                        hot.setDataAtCell(row, idCitIndex, response.data.id);
                        console.log('Nueva cita creada con ID:', response.data.id);
                    } else {
                        alert('Error al crear cita: ' + response.message);
                        cargarCitas();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('Error de conexión al crear cita. Revise la consola para más detalles.');
                    cargarCitas();
                }
            });
        }
        
        function actualizarCitaCompleta(row, id_cit, datosPendientes) {
            // Actualizar todos los campos modificados de una vez
            var actualizaciones = [];
            
            Object.keys(datosPendientes).forEach(function(campo) {
                var valor = datosPendientes[campo];
                actualizaciones.push({
                    campo: campo,
                    valor: valor
                });
            });
            
            if (actualizaciones.length === 0) {
                return;
            }
            
            console.log('Actualizando cita', id_cit, 'con cambios:', actualizaciones);
            
            // Procesar actualizaciones una por una
            var procesarActualizacion = function(index) {
                if (index >= actualizaciones.length) {
                    console.log('Todas las actualizaciones completadas');
                    return;
                }
                
                var update = actualizaciones[index];
                
                $.ajax({
                    url: 'server/controlador_citas.php',
                    type: 'POST',
                    data: {
                        action: 'actualizar_cita',
                        campo: update.campo,
                        valor: update.valor,
                        id_cit: id_cit
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            console.log('Campo', update.campo, 'actualizado correctamente');
                            // Procesar siguiente actualización
                            procesarActualizacion(index + 1);
                        } else {
                            alert('Error al actualizar campo ' + update.campo + ': ' + response.message);
                            cargarCitas();
                        }
                    },
                    error: function() {
                        alert('Error de conexión al actualizar campo ' + update.campo);
                        cargarCitas();
                    }
                });
            };
            
            // Iniciar proceso de actualizaciones
            procesarActualizacion(0);
        }
        
        function cargarConfiguracionColumnas() {
            return new Promise(function(resolve, reject) {
                // Cargar columnas dinámicas desde la base de datos
                $.ajax({
                    url: 'server/controlador_citas.php',
                    type: 'POST',
                    data: { action: 'obtener_columnas_dinamicas' },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success && response.data) {
                            response.data.forEach(function(columna) {
                                var nuevaColumna = {
                                    key: columna.nombre,
                                    header: columna.nombre.replace('col_dinamica_', 'Columna ').replace(/_\d+_\d+$/, ''),
                                    type: 'text',
                                    width: 150
                                };
                                columnasConfig.push(nuevaColumna);
                            });
                            console.log('Columnas dinámicas cargadas desde BD:', response.data);
                        }
                        resolve();
                    },
                    error: function() {
                        console.error('Error al cargar columnas dinámicas desde BD');
                        resolve(); // No fallar la inicialización
                    }
                });
            });
        }
        
        function eliminarCitaBaseDatos(id_cit) {
            if (!id_cit) {
                console.log('No hay ID de cita para eliminar');
                return;
            }
            
            console.log('Eliminando cita con ID:', id_cit);
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: {
                    action: 'eliminar_cita',
                    id_cit: id_cit
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        console.log('Cita eliminada correctamente de la base de datos');
                    } else {
                        alert('Error al eliminar cita: ' + response.message);
                        cargarCitas();
                    }
                },
                error: function() {
                    alert('Error de conexión al eliminar cita');
                    cargarCitas();
                }
            });
        }
        
        // =====================================
        // CARGA Y BÚSQUEDA DE DATOS
        // =====================================
        
        function cargarCitas() {
            modoFiltroFecha = true;
            var fecha = $('#fecha-filtro').val();
            
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: { 
                    action: 'obtener_citas',
                    fecha_filtro: fecha
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        mostrarCitasEnTabla(response.data, true);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al servidor');
                }
            });
        }
        
        function buscarCitas() {
            var termino = $('#buscador-citas').val().trim();
            if (!termino) {
                //alert('Ingrese un término de búsqueda');
                limpiarBusqueda();
                return;
            }
            
            modoFiltroFecha = false;
            
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: { 
                    action: 'obtener_citas'
                    // Sin fecha_filtro para obtener todas las citas
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Filtrar localmente por el término de búsqueda
                        var citasFiltradas = response.data.filter(function(cita) {
                            return (cita.nom_cit && cita.nom_cit.toLowerCase().includes(termino.toLowerCase())) ||
                                   (cita.tel_cit && cita.tel_cit.includes(termino)) ||
                                   (cita.nom_eje && cita.nom_eje.toLowerCase().includes(termino.toLowerCase()));
                        });
                        
                        mostrarCitasEnTabla(citasFiltradas, false);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al servidor');
                }
            });
        }
        
        function limpiarBusqueda() {
            $('#buscador-citas').val('');
            cargarCitas();
        }
        
        function mostrarCitasEnTabla(citas, usarHorariosFijos) {
            var datos;
            
            if (usarHorariosFijos) {
                // Modo normal con horarios fijos expandidos
                datos = generarHorariosFijos();
                
                // Agrupar citas por hora para distribución
                var citasPorHora = {};
                citas.forEach(function(cita) {
                    var hora = parseInt(cita.hor_cit.split(':')[0]);
                    if (!citasPorHora[hora]) {
                        citasPorHora[hora] = [];
                    }
                    citasPorHora[hora].push(cita);
                });
                
                // Distribuir citas en las filas correspondientes
                Object.keys(citasPorHora).forEach(function(hora) {
                    var horaNum = parseInt(hora);
                    var indiceGrupoInicio = (horaNum - 8) * citasPorRango;
                    
                    if (indiceGrupoInicio >= 0 && indiceGrupoInicio < datos.length) {
                        citasPorHora[hora].forEach(function(cita, index) {
                            var indiceFila = indiceGrupoInicio + index;
                            if (indiceFila < datos.length) {
                                // Mantener el rango de horario solo en la primera fila del grupo
                                var rangoHorario = datos[indiceFila][0];
                                datos[indiceFila] = mapearCitaAFila(cita, rangoHorario);
                            }
                        });
                    }
                });
            } else {
                // Modo búsqueda - mostrar solo resultados
                datos = citas.map(function(cita) {
                    return mapearCitaAFila(cita, '');
                });
            }
            
            hot.loadData(datos);
        }
        
        function mapearCitaAFila(cita, horario) {
            var fila = new Array(columnasConfig.length).fill('');
            
            columnasConfig.forEach(function(col, index) {
                if (col.key === 'horario') {
                    fila[index] = horario;
                } else if (cita.hasOwnProperty(col.key)) {
                    // Mapear directamente desde los datos de la cita
                    fila[index] = cita[col.key] || '';
                }
            });
            
            return fila;
        }
        
        // =====================================
        // FUNCIÓN LEGACY PARA AGREGAR CITA
        // =====================================
        
        function agregarNuevaCita() {
            var nombre = prompt('Nombre del cliente:');
            if (!nombre) return;
            
            var telefono = prompt('Teléfono del cliente:');
            if (!telefono) return;
            
            var hora = prompt('Hora de la cita (formato 24h, ej: 14:30):', '09:00');
            if (!hora) return;
            
            var dia = prompt('Fecha de la cita (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
            if (!dia) return;
            
            // Mostrar ejecutivos disponibles
            var listaEjecutivos = 'Ejecutivos disponibles:\n';
            ejecutivos.forEach(function(eje) {
                listaEjecutivos += 'ID: ' + eje.id_eje + ' - ' + eje.nom_eje + '\n';
            });
            
            alert(listaEjecutivos);
            var ejecutivoId = prompt('Ingrese el ID del ejecutivo que desea asignar:');
            if (!ejecutivoId) return;
            
            var ejecutivoSeleccionado = ejecutivos.find(function(eje) {
                return eje.id_eje == ejecutivoId;
            });
            
            if (!ejecutivoSeleccionado) {
                alert('ID de ejecutivo no válido. Verifique la lista.');
                return;
            }
            
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dia)) {
                alert('Formato de fecha incorrecto. Use YYYY-MM-DD (ej: 2025-07-03)');
                return;
            }
            
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: {
                    action: 'guardar_cita',
                    cit_cit: dia,
                    hor_cit: hora + ':00',
                    nom_cit: nombre,
                    tel_cit: telefono,
                    id_eje2: ejecutivoId
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        alert('Cita guardada correctamente para el ' + dia + '\nEjecutivo asignado: ' + ejecutivoSeleccionado.nom_eje);
                        if (modoFiltroFecha) {
                            cargarCitas();
                        } else {
                            buscarCitas();
                        }
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error de conexión al guardar cita');
                }
            });
        }
        
        // =====================================
        // FUNCIÓN DE DEBUG
        // =====================================
        
        function debugearEstado() {
            console.log('=== ESTADO ACTUAL DEL SISTEMA ===');
            console.log('Columnas en configuración:', columnasConfig.length);
            console.log('Configuración de columnas:', columnasConfig);
            console.log('Columnas en tabla Handsontable:', hot ? hot.countCols() : 'No inicializada');
            console.log('Headers actuales:', hot ? hot.getColHeader() : 'No inicializada');
            console.log('Ejecutivos cargados:', ejecutivos.length);
            console.log('Modo filtro fecha:', modoFiltroFecha);
            console.log('==================================');
            
            // Prueba de conexión al servidor
            $.ajax({
                url: 'test_connection.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Prueba de conexión:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Error de conexión al servidor:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                }
            });
            
            // Mostrar también información de las columnas dinámicas desde el backend
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: { action: 'obtener_columnas_dinamicas' },
                dataType: 'json',
                success: function(response) {
                    console.log('Columnas dinámicas en BD:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener columnas dinámicas desde BD:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                }
            });
        }
        
        function probarConexion() {
            console.log('=== PROBANDO CONEXIÓN AL SERVIDOR ===');
            
            // Probar conexión al controlador
            $.ajax({
                url: 'server/controlador_citas.php',
                type: 'POST',
                data: { action: 'test_conexion' },
                dataType: 'json',
                timeout: 5000,
                success: function(response) {
                    console.log('✓ Controlador respondió correctamente:', response);
                    alert('Conexión al controlador: OK\nTimestamp: ' + response.data.timestamp);
                },
                error: function(xhr, status, error) {
                    console.error('✗ Error en controlador:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    alert('Error en controlador:\nStatus: ' + status + '\nError: ' + error);
                }
            });
            
            // Probar archivo de prueba
            $.ajax({
                url: 'test_connection.php',
                type: 'GET',
                dataType: 'json',
                timeout: 5000,
                success: function(response) {
                    console.log('✓ Test de conexión respondió:', response);
                },
                error: function(xhr, status, error) {
                    console.error('✗ Error en test de conexión:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                }
            });
        }
        
        // =====================================
        // EVENTOS Y INTERACTIVIDAD
        // =====================================
        
        $('#fecha-filtro').change(function() {
            cargarCitas();
        });
        
        $('#buscador-citas').keypress(function(e) {
            if (e.which === 13) {
                buscarCitas();
            }
        });
        
        // Guardar cambios pendientes cuando se hace clic fuera de la tabla
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#tabla-citas').length) {
                guardarCambiosPendientes();
            }
        });
        
        // Guardar cambios pendientes al presionar Enter
        $(document).on('keydown', function(e) {
            if (e.which === 13) { // Enter
                guardarCambiosPendientes();
            }
        });
        
    </script>
</body>
</html>
