<?php
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');

$archivoJson = __DIR__ . '/autos.json';
$archivoLeads = __DIR__ . '/leads.csv';

function clean_text($value): string {
    return trim((string)$value);
}

function post(string $key, string $default = ''): string {
    return clean_text($_POST[$key] ?? $default);
}

function load_json_array(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_json_array(string $path, array $data): void {
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('No se pudo codificar JSON.');
    }
    if (file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo escribir el archivo temporal.');
    }
    if (!rename($tmp, $path)) {
        throw new RuntimeException('No se pudo reemplazar autos.json.');
    }
}

function save_lead_csv(string $path, array $row): void {
    $isNew = !file_exists($path);
    $fp = fopen($path, 'a');
    if (!$fp) {
        throw new RuntimeException('No se pudo abrir leads.csv.');
    }
    if ($isNew) {
        fputcsv($fp, ['fecha','nombre','telefono','email','interes','mensaje','origen']);
    }
    fputcsv($fp, $row);
    fclose($fp);
}

try {
    $tipo = post('tipo');

    if ($tipo === 'actualizar_catalogo') {
        $autos = $_POST['autos'] ?? [];
        if (!is_array($autos)) {
            $autos = [];
        }

        $limpios = [];
        $nextId = 1;

        foreach ($autos as $auto) {
            if (!is_array($auto)) {
                continue;
            }

            $marca = clean_text($auto['marca'] ?? '');
            $modelo = clean_text($auto['modelo'] ?? '');
            $imagen = clean_text($auto['imagen'] ?? '');

            if ($marca === '' && $modelo === '' && $imagen === '') {
                continue;
            }

            $id = clean_text($auto['id'] ?? '');
            $limpios[] = [
                'id' => $id !== '' ? (int)$id : $nextId,
                'marca' => $marca,
                'modelo' => $modelo,
                'anio' => clean_text($auto['anio'] ?? ''),
                'precio' => clean_text($auto['precio'] ?? ''),
                'imagen' => $imagen,
                'kilometraje' => clean_text($auto['kilometraje'] ?? ''),
                'combustible' => clean_text($auto['combustible'] ?? ''),
                'transmision' => clean_text($auto['transmision'] ?? ''),
                'potencia' => clean_text($auto['potencia'] ?? ''),
                'summary' => clean_text($auto['summary'] ?? ''),
                'description' => clean_text($auto['description'] ?? ''),
                'estado' => clean_text($auto['estado'] ?? 'Disponible'),
            ];
            $nextId++;
        }

        save_json_array($archivoJson, $limpios);
        header('Location: admin.php?status=ok');
        exit;
    }

    if ($tipo === 'nuevo_lead') {
        $fecha = date('Y-m-d H:i:s');
        $nombre = post('nombre');
        $telefono = post('phone', post('telefono'));
        $email = post('email');
        $interes = post('interest', post('interes'));
        $mensaje = post('message', post('mensaje'));
        $origen = post('origen', 'web');

        save_lead_csv($archivoLeads, [$fecha, $nombre, $telefono, $email, $interes, $mensaje, $origen]);

        $texto = rawurlencode("Hola, soy {$nombre}. Teléfono: {$telefono}. Email: {$email}. Motivo: {$interes}. Detalles: " . ($mensaje !== '' ? $mensaje : 'Quiero recibir más información.'));
        $redirect = "https://wa.me/34600000000?text={$texto}";

        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'redirect' => $redirect], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header("Location: {$redirect}");
        exit;
    }

    http_response_code(400);
    echo 'Solicitud no válida.';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error al procesar la solicitud.';
}
