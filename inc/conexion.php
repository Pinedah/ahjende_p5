<?php
	// Función para cargar variables de entorno desde .env
	function loadEnv($path) {
		if (!file_exists($path)) {
			die('Error: Archivo .env no encontrado en ' . $path);
		}
		
		$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			if (strpos(trim($line), '#') === 0) {
				continue; // Ignorar comentarios
			}
			
			list($name, $value) = explode('=', $line, 2);
			$name = trim($name);
			$value = trim($value);
			
			if (!array_key_exists($name, $_ENV)) {
				putenv(sprintf('%s=%s', $name, $value));
				$_ENV[$name] = $value;
				$_SERVER[$name] = $value;
			}
		}
	}

	// Cargar variables de entorno
	$envPath = __DIR__ . '/../../.env';
	if (file_exists($envPath)) {
		loadEnv($envPath);
		$host = getenv('DB_HOST');
		$user = getenv('DB_USER');
		$pass = getenv('DB_PASS');
		$database = getenv('DB_NAME');
		$charset = getenv('DB_CHARSET');
	} else {
		// error al no encontrar el archivo .env
		die('Error: Archivo .env no encontrado. Por favor, crea un archivo .env en la raíz del proyecto.');
	}
	
	$connection = mysqli_connect($host, $user, $pass, $database);
	if (!$connection) {
		die('Error de conexión: ' . mysqli_connect_error());
	}
	mysqli_set_charset($connection, $charset);

	// Función para ejecutar consultas y obtener datos
	function ejecutarConsulta($query, $connection) {
		$result = mysqli_query($connection, $query);
		if (!$result) return false;
		$datos = [];
		while($row = mysqli_fetch_assoc($result)) {
			$datos[] = $row;
		}
		return $datos;
	}

	// Función para escape de datos (prevención SQL Injection)
	function escape($valor, $connection) {
		return mysqli_real_escape_string($connection, $valor);
	}

	// Respuesta exitosa estándar
	function respuestaExito($data = null, $message = 'OK') {
		return json_encode([
			'success' => true,
			'data' => $data,
			'message' => $message
		], JSON_UNESCAPED_UNICODE);
	}

	// Respuesta de error estándar
	function respuestaError($message = 'Error', $code = 400) {
		return json_encode([
			'success' => false,
			'message' => $message,
			'code' => $code
		], JSON_UNESCAPED_UNICODE);
	}
?>
