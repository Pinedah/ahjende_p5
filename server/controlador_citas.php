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

		case 'crear_columna_dinamica':
			$nombre_columna = escape($_POST['nombre_columna'], $connection);
			$posicion = isset($_POST['posicion']) ? intval($_POST['posicion']) : 999;
			
			// Verificar si la columna ya existe
			$queryCheck = "SHOW COLUMNS FROM cita LIKE '$nombre_columna'";
			$existe = ejecutarConsulta($queryCheck, $connection);
			
			if ($existe && count($existe) > 0) {
				echo respuestaError('La columna ya existe');
				break;
			}
			
			// Crear la columna en la tabla
			$queryAlter = "ALTER TABLE cita ADD COLUMN `$nombre_columna` TEXT NULL";
			
			if(mysqli_query($connection, $queryAlter)) {
				// Verificar que la columna se creó correctamente
				$queryVerify = "SHOW COLUMNS FROM cita LIKE '$nombre_columna'";
				$verificacion = ejecutarConsulta($queryVerify, $connection);
				
				if ($verificacion && count($verificacion) > 0) {
					// Guardar información de la columna dinámica (opcional)
					$queryInsert = "INSERT INTO columnas_dinamicas (nombre_columna, posicion, tipo, fecha_creacion) 
								   VALUES ('$nombre_columna', $posicion, 'text', NOW())
								   ON DUPLICATE KEY UPDATE posicion = $posicion";
					
					mysqli_query($connection, $queryInsert); // No importa si falla esta parte
					
					echo respuestaExito([
						'nombre_columna' => $nombre_columna,
						'posicion' => $posicion,
						'tipo' => 'text'
					], 'Columna dinámica creada correctamente');
				} else {
					echo respuestaError('Error: La columna no se creó correctamente');
				}
			} else {
				echo respuestaError('Error al crear columna: ' . mysqli_error($connection));
			}
		break;

		case 'eliminar_columna_dinamica':
			$nombre_columna = escape($_POST['nombre_columna'], $connection);
			
			// Verificar que la columna existe y es dinámica (no es una columna base)
			$columnasBasicas = ['id_cit', 'cit_cit', 'hor_cit', 'nom_cit', 'tel_cit', 'id_eje2'];
			
			if (in_array($nombre_columna, $columnasBasicas)) {
				echo respuestaError('No se puede eliminar una columna básica del sistema');
				break;
			}
			
			// Verificar si la columna existe
			$queryCheck = "SHOW COLUMNS FROM cita LIKE '$nombre_columna'";
			$existe = ejecutarConsulta($queryCheck, $connection);
			
			if (!$existe || count($existe) === 0) {
				echo respuestaError('La columna no existe');
				break;
			}
			
			// Eliminar la columna de la tabla
			$queryAlter = "ALTER TABLE cita DROP COLUMN `$nombre_columna`";
			
			if(mysqli_query($connection, $queryAlter)) {
				// Verificar que la columna se eliminó correctamente
				$queryVerify = "SHOW COLUMNS FROM cita LIKE '$nombre_columna'";
				$verificacion = ejecutarConsulta($queryVerify, $connection);
				
				if (!$verificacion || count($verificacion) === 0) {
					// Eliminar información de la columna dinámica
					$queryDelete = "DELETE FROM columnas_dinamicas WHERE nombre_columna = '$nombre_columna'";
					mysqli_query($connection, $queryDelete); // No importa si falla esta parte
					
					echo respuestaExito([
						'nombre_columna' => $nombre_columna
					], 'Columna dinámica eliminada correctamente');
				} else {
					echo respuestaError('Error: La columna no se eliminó correctamente');
				}
			} else {
				echo respuestaError('Error al eliminar columna: ' . mysqli_error($connection));
			}
		break;

		case 'obtener_columnas_dinamicas':
			// Obtener todas las columnas que no son las básicas
			$columnasBasicas = ['id_cit', 'cit_cit', 'hor_cit', 'nom_cit', 'tel_cit', 'id_eje2'];
			
			$queryColumnas = "SHOW COLUMNS FROM cita";
			$todasColumnas = ejecutarConsulta($queryColumnas, $connection);
			
			$columnasDinamicas = [];
			if ($todasColumnas) {
				foreach ($todasColumnas as $columna) {
					if (!in_array($columna['Field'], $columnasBasicas)) {
						$columnasDinamicas[] = [
							'nombre' => $columna['Field'],
							'tipo' => $columna['Type']
						];
					}
				}
			}
			
			echo respuestaExito($columnasDinamicas, 'Columnas dinámicas obtenidas correctamente');
		break;

		default:
			echo respuestaError('Acción no válida');
		break;
	}

	mysqli_close($connection);
	exit;
}
?>
