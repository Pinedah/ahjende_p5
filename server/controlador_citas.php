<?php
// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log de debugging
file_put_contents('debug.log', '[' . date('Y-m-d H:i:s') . '] REQUEST recibido: ' . print_r($_POST, true) . "\n", FILE_APPEND);

include '../inc/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// Validar que action esté presente
	if (!isset($_POST['action'])) {
		echo respuestaError('Acción no especificada');
		exit;
	}
	
	$action = escape($_POST['action'], $connection);
	
	switch($action) {

		case 'test_conexion':
			echo respuestaExito(['timestamp' => date('Y-m-d H:i:s')], 'Controlador funcionando correctamente');
		break;

		case 'obtener_citas':
			$fecha_filtro = isset($_POST['fecha_filtro']) ? escape($_POST['fecha_filtro'], $connection) : null;
			
			// Obtener todas las columnas de la tabla cita
			$queryColumnas = "SHOW COLUMNS FROM cita";
			$columnas = ejecutarConsulta($queryColumnas, $connection);
			
			$camposSelect = [];
			if ($columnas) {
				foreach ($columnas as $columna) {
					$camposSelect[] = 'c.' . $columna['Field'];
				}
			}
			
			// Agregar el campo del ejecutivo
			$camposSelect[] = 'e.nom_eje';
			$selectFields = implode(', ', $camposSelect);
			
			if ($fecha_filtro) {
				// Filtro por fecha específica
				$query = "SELECT $selectFields 
						 FROM cita c
						 LEFT JOIN ejecutivo e ON c.id_eje2 = e.id_eje
						 WHERE c.cit_cit = '$fecha_filtro'
						 ORDER BY c.hor_cit ASC";
			} else {
				// Todas las citas para búsqueda
				$query = "SELECT $selectFields 
						 FROM cita c
						 LEFT JOIN ejecutivo e ON c.id_eje2 = e.id_eje
						 ORDER BY c.cit_cit DESC, c.hor_cit ASC";
			}

			$datos = ejecutarConsulta($query, $connection);

			if($datos !== false) {
				echo respuestaExito($datos, 'Citas obtenidas correctamente');
			} else {
				echo respuestaError('Error al consultar citas: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'obtener_ejecutivos':
			$query = "SELECT id_eje, nom_eje FROM ejecutivo ORDER BY nom_eje ASC";
			$datos = ejecutarConsulta($query, $connection);

			if($datos !== false) {
				echo respuestaExito($datos, 'Ejecutivos obtenidos correctamente');
			} else {
				echo respuestaError('Error al consultar ejecutivos: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'guardar_cita':
			// Obtener estructura de la tabla para inserción dinámica
			$estructuraQuery = "DESCRIBE cita";
			$columnas = ejecutarConsulta($estructuraQuery, $connection);
			
			if($columnas === false) {
				echo respuestaError('Error al obtener estructura de tabla');
				break;
			}
			
			$camposInsertar = [];
			$valoresInsertar = [];
			
			foreach($columnas as $columna) {
				$nombreCol = $columna['Field'];
				
				// Saltar campo auto-increment
				if($nombreCol === 'id_cit') continue;
				
				if(isset($_POST[$nombreCol]) && $_POST[$nombreCol] !== '') {
					$valor = escape($_POST[$nombreCol], $connection);
					$camposInsertar[] = $nombreCol;
					$valoresInsertar[] = "'$valor'";
				} else {
					// Solo permitir NULL si la columna lo acepta
					if($columna['Null'] === 'YES') {
						$camposInsertar[] = $nombreCol;
						$valoresInsertar[] = "NULL";
					} else {
						// Para campos que no permiten NULL, usar valores por defecto
						$camposInsertar[] = $nombreCol;
						if(strpos($columna['Type'], 'int') !== false) {
							$valoresInsertar[] = "0";
						} else if(strpos($columna['Type'], 'date') !== false) {
							$valoresInsertar[] = "CURDATE()";
						} else if(strpos($columna['Type'], 'time') !== false) {
							$valoresInsertar[] = "CURTIME()";
						} else {
							$valoresInsertar[] = "''";
						}
					}
				}
			}
			
			if(empty($camposInsertar)) {
				echo respuestaError('No hay campos válidos para insertar');
				break;
			}
			
			$query = "INSERT INTO cita (" . implode(', ', $camposInsertar) . ") VALUES (" . implode(', ', $valoresInsertar) . ")";
			
			if(mysqli_query($connection, $query)) {
				echo respuestaExito(['id' => mysqli_insert_id($connection)], 'Cita guardada correctamente');
			} else {
				echo respuestaError('Error al guardar cita: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'actualizar_cita':
			$campo = escape($_POST['campo'], $connection);
			$valor = $_POST['valor']; // No escapar aún
			$id_cit = escape($_POST['id_cit'], $connection);
			
			// Verificar que la columna existe en la tabla (permite columnas dinámicas)
			$queryCheck = "SHOW COLUMNS FROM cita WHERE Field = '$campo'";
			$existe = ejecutarConsulta($queryCheck, $connection);
			
			if (!$existe || empty($existe)) {
				echo respuestaError('Campo no válido para actualización');
				break;
			}
			
			$columnaInfo = $existe[0];
			
			// Manejar valores vacíos según las restricciones de la columna
			if ($valor === '' || $valor === null) {
				if ($columnaInfo['Null'] === 'YES') {
					$valorSQL = 'NULL';
				} else {
					// Para campos que no permiten NULL, usar valores por defecto
					if (strpos($columnaInfo['Type'], 'int') !== false) {
						$valorSQL = "0";
					} else {
						$valorSQL = "''";
					}
				}
			} else {
				$valorEscapado = escape($valor, $connection);
				$valorSQL = "'$valorEscapado'";
			}
			
			$query = "UPDATE cita SET $campo = $valorSQL WHERE id_cit = '$id_cit'";
			
			if(mysqli_query($connection, $query)) {
				echo respuestaExito(null, 'Cita actualizada correctamente');
			} else {
				echo respuestaError('Error al actualizar cita: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'eliminar_cita':
			$id_cit = escape($_POST['id_cit'], $connection);
			
			if (!$id_cit) {
				echo respuestaError('ID de cita no proporcionado');
				break;
			}
			
			$query = "DELETE FROM cita WHERE id_cit = '$id_cit'";
			
			if(mysqli_query($connection, $query)) {
				if(mysqli_affected_rows($connection) > 0) {
					echo respuestaExito(['id_eliminado' => $id_cit], 'Cita eliminada correctamente');
				} else {
					echo respuestaError('No se encontró la cita a eliminar');
				}
			} else {
				echo respuestaError('Error al eliminar cita: ' . mysqli_error($connection) . ' Query: ' . $query);
			}
		break;

		case 'obtener_estructura_tabla':
			$nombreTabla = 'cita';
			$query = "SHOW COLUMNS FROM $nombreTabla";
			$columnas = ejecutarConsulta($query, $connection);
			
			if($columnas !== false) {
				// Procesar columnas para el frontend
				$columnasConfig = [];
				
				foreach($columnas as $columna) {
					$campo = $columna['Field'];
					$tipo = $columna['Type'];
					
					// Configuración por defecto
					$config = [
						'key' => $campo,
						'header' => strtoupper(str_replace('_', ' ', $campo)),
						'type' => 'text',
						'width' => 120
					];
					
					// Configuraciones específicas por campo
					switch($campo) {
						case 'id_cit':
							$config['header'] = 'ID';
							$config['readOnly'] = true;
							$config['width'] = 60;
							break;
						case 'cit_cit':
							$config['header'] = 'FECHA';
							$config['type'] = 'date';
							$config['dateFormat'] = 'YYYY-MM-DD';
							$config['width'] = 120;
							break;
						case 'hor_cit':
							$config['header'] = 'HORA';
							$config['type'] = 'time';
							$config['timeFormat'] = 'HH:mm';
							$config['width'] = 100;
							break;
						case 'nom_cit':
							$config['header'] = 'NOMBRE';
							$config['width'] = 200;
							break;
						case 'tel_cit':
							$config['header'] = 'TELÉFONO';
							$config['width'] = 150;
							break;
						case 'id_eje2':
							$config['header'] = 'EJECUTIVO';
							$config['type'] = 'dropdown';
							$config['width'] = 180;
							break;
						default:
							// Detectar tipo automáticamente
							if(strpos($tipo, 'date') !== false) {
								$config['type'] = 'date';
								$config['dateFormat'] = 'YYYY-MM-DD';
							} elseif(strpos($tipo, 'time') !== false) {
								$config['type'] = 'time';
								$config['timeFormat'] = 'HH:mm';
							} elseif(strpos($tipo, 'int') !== false) {
								$config['type'] = 'numeric';
							} elseif(strpos($tipo, 'decimal') !== false || strpos($tipo, 'float') !== false) {
								$config['type'] = 'numeric';
							}
							break;
					}
					
					$columnasConfig[] = $config;
				}
				
				// Agregar columna de horario al inicio
				array_unshift($columnasConfig, [
					'key' => 'horario',
					'header' => 'HORARIO',
					'type' => 'text',
					'readOnly' => true,
					'className' => 'horario-column',
					'width' => 150
				]);
				
				echo respuestaExito($columnasConfig, 'Estructura de tabla obtenida correctamente');
			} else {
				echo respuestaError('Error al obtener estructura de tabla: ' . mysqli_error($connection));
			}
		break;

		case 'crear_nueva_columna':
			$nombreTabla = 'cita';
			$nombreNuevaColumna = isset($_POST['nombre_columna']) ? escape($_POST['nombre_columna'], $connection) : '';
			$tipoColumna = isset($_POST['tipo_columna']) ? escape($_POST['tipo_columna'], $connection) : 'VARCHAR(100)';
			
			if(empty($nombreNuevaColumna)) {
				echo respuestaError("Nombre de columna no proporcionado");
				break;
			}
			
			// Validar que el nombre no contenga caracteres especiales
			if(!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $nombreNuevaColumna)) {
				echo respuestaError("Nombre de columna no válido. Use solo letras, números y guiones bajos.");
				break;
			}
			
			$query = "ALTER TABLE $nombreTabla ADD COLUMN $nombreNuevaColumna $tipoColumna";

			if(mysqli_query($connection, $query)){
				echo respuestaExito([
					'nombre_columna' => $nombreNuevaColumna,
					'tipo_columna' => $tipoColumna
				], "Columna '$nombreNuevaColumna' agregada correctamente");
			}else{
				echo respuestaError("Error al crear la nueva columna: " . mysqli_error($connection));
			}
		break;


		default:
			echo respuestaError('Acción no válida');
		break;
	}

	mysqli_close($connection);
	exit;
}
?>
