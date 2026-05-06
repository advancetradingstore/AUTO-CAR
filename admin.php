<?php
declare(strict_types=1);

$archivoJson = __DIR__ . '/autos.json';
$archivoLeads = __DIR__ . '/leads.csv';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function readJsonArray(string $path): array {
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

$autos = readJsonArray($archivoJson);
if (!$autos) {
    $autos = [[
        'id' => 1,
        'marca' => '',
        'modelo' => '',
        'anio' => '',
        'precio' => '',
        'imagen' => '',
        'kilometraje' => '',
        'combustible' => '',
        'transmision' => '',
        'potencia' => '',
        'summary' => '',
        'description' => '',
        'estado' => 'Disponible',
    ]];
}

$leads = [];
if (file_exists($archivoLeads) && ($fp = fopen($archivoLeads, 'r')) !== false) {
    while (($row = fgetcsv($fp)) !== false) {
        if (!$row || (isset($row[0]) && $row[0] === 'fecha')) {
            continue;
        }
        $leads[] = $row;
    }
    fclose($fp);
    $leads = array_reverse($leads);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel básico | AutoCar Madrid</title>
    <style>
        :root { --bg:#0b0b0b; --panel:#121212; --panel2:#171717; --line:#2a2a2a; --text:#f2f2f2; --muted:#a3a3a3; --accent:#d8d8d8; --ok:#2ecc71; }
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,Arial,sans-serif;background:linear-gradient(180deg,#080808,#111);color:var(--text)}
        .wrap{max-width:1280px;margin:0 auto;padding:24px}
        .top{display:flex;justify-content:space-between;gap:16px;align-items:flex-end;flex-wrap:wrap;margin-bottom:20px}
        h1{margin:0;font-size:28px}
        p{margin:0;color:var(--muted);line-height:1.5}
        .grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}
        .card{background:rgba(18,18,18,.96);border:1px solid var(--line);border-radius:20px;padding:18px;box-shadow:0 20px 60px rgba(0,0,0,.35)}
        .card h2{margin:0 0 14px;font-size:18px}
        .note{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:999px;padding:8px 12px;color:var(--muted);font-size:12px}
        .success{margin:0 0 14px;padding:12px 14px;border-radius:14px;background:rgba(46,204,113,.12);border:1px solid rgba(46,204,113,.25);color:#dff7e9}
        .table-wrap{overflow:auto;border-radius:16px;border:1px solid var(--line)}
        table{width:100%;border-collapse:collapse;min-width:1080px;background:#0f0f0f}
        th,td{border-bottom:1px solid var(--line);padding:10px;vertical-align:top}
        th{position:sticky;top:0;background:#151515;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#cfcfcf}
        td input, td textarea{width:100%;background:#0b0b0b;border:1px solid #2c2c2c;color:var(--text);border-radius:10px;padding:10px;font:inherit}
        td textarea{min-height:74px;resize:vertical}
        .row-actions{display:flex;gap:8px;flex-wrap:wrap}
        .btn{border:0;border-radius:999px;padding:12px 16px;font-weight:700;cursor:pointer}
        .btn-save{background:var(--accent);color:#000}
        .btn-add{background:#222;color:#fff;border:1px solid #333}
        .btn-remove{background:#3a1515;color:#ffd7d7;border:1px solid #5b2222}
        .lead-table{width:100%;border-collapse:collapse}
        .lead-table th,.lead-table td{padding:10px;border-bottom:1px solid var(--line);font-size:14px}
        .lead-table th{background:#151515}
        .muted{color:var(--muted)}
        .footer-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
        @media (max-width: 980px){ .grid{grid-template-columns:1fr} table{min-width:900px} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <div>
            <h1>Panel básico de edición</h1>
            <p>Edita catálogo, imágenes y textos. Los cambios se guardan en <code>autos.json</code> y se reflejan al recargar la landing.</p>
        </div>
        <div class="note">Sin MySQL · JSON + PHP</div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'ok'): ?>
        <div class="success">Cambios guardados correctamente.</div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>Catálogo de coches</h2>

            <form action="procesar.php" method="post">
                <input type="hidden" name="tipo" value="actualizar_catalogo">
                <div class="table-wrap">
                    <table id="autos-table">
                        <thead>
                            <tr>
                                <th style="width:70px">ID</th>
                                <th style="width:140px">Marca</th>
                                <th style="width:140px">Modelo</th>
                                <th style="width:100px">Año</th>
                                <th style="width:120px">Precio</th>
                                <th style="width:220px">Imagen</th>
                                <th style="width:120px">Km</th>
                                <th style="width:120px">Comb.</th>
                                <th style="width:120px">Trans.</th>
                                <th style="width:110px">Potencia</th>
                                <th style="width:180px">Resumen</th>
                                <th style="width:220px">Descripción</th>
                                <th style="width:120px">Estado</th>
                                <th style="width:120px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="autos-tbody">
                            <?php foreach ($autos as $i => $auto): ?>
                            <tr>
                                <td><input type="text" name="autos[<?= $i ?>][id]" value="<?= e((string)($auto['id'] ?? $i + 1)) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][marca]" value="<?= e((string)($auto['marca'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][modelo]" value="<?= e((string)($auto['modelo'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][anio]" value="<?= e((string)($auto['anio'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][precio]" value="<?= e((string)($auto['precio'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][imagen]" value="<?= e((string)($auto['imagen'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][kilometraje]" value="<?= e((string)($auto['kilometraje'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][combustible]" value="<?= e((string)($auto['combustible'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][transmision]" value="<?= e((string)($auto['transmision'] ?? '')) ?>"></td>
                                <td><input type="text" name="autos[<?= $i ?>][potencia]" value="<?= e((string)($auto['potencia'] ?? '')) ?>"></td>
                                <td><textarea name="autos[<?= $i ?>][summary]"><?= e((string)($auto['summary'] ?? '')) ?></textarea></td>
                                <td><textarea name="autos[<?= $i ?>][description]"><?= e((string)($auto['description'] ?? '')) ?></textarea></td>
                                <td><input type="text" name="autos[<?= $i ?>][estado]" value="<?= e((string)($auto['estado'] ?? 'Disponible')) ?>"></td>
                                <td>
                                    <div class="row-actions">
                                        <button class="btn btn-remove" type="button" onclick="removeRow(this)">Quitar</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="footer-actions">
                    <button class="btn btn-add" type="button" onclick="addRow()">Añadir coche</button>
                    <button class="btn btn-save" type="submit">Guardar catálogo</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Leads y turnos guardados</h2>
            <p class="muted" style="margin-bottom:14px">Se muestran los registros almacenados en <code>leads.csv</code>.</p>
            <div style="overflow:auto;border-radius:16px;border:1px solid var(--line)">
                <table class="lead-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Interés</th>
                            <th>Mensaje</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$leads): ?>
                        <tr><td colspan="7" class="muted">Todavía no hay leads guardados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?= e((string)($lead[0] ?? '')) ?></td>
                                <td><?= e((string)($lead[1] ?? '')) ?></td>
                                <td><?= e((string)($lead[2] ?? '')) ?></td>
                                <td><?= e((string)($lead[3] ?? '')) ?></td>
                                <td><?= e((string)($lead[4] ?? '')) ?></td>
                                <td><?= e((string)($lead[5] ?? '')) ?></td>
                                <td><?= e((string)($lead[6] ?? 'web')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function addRow() {
    const tbody = document.getElementById('autos-tbody');
    const index = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="autos[${index}][id]" value=""></td>
        <td><input type="text" name="autos[${index}][marca]" value=""></td>
        <td><input type="text" name="autos[${index}][modelo]" value=""></td>
        <td><input type="text" name="autos[${index}][anio]" value=""></td>
        <td><input type="text" name="autos[${index}][precio]" value=""></td>
        <td><input type="text" name="autos[${index}][imagen]" value=""></td>
        <td><input type="text" name="autos[${index}][kilometraje]" value=""></td>
        <td><input type="text" name="autos[${index}][combustible]" value=""></td>
        <td><input type="text" name="autos[${index}][transmision]" value=""></td>
        <td><input type="text" name="autos[${index}][potencia]" value=""></td>
        <td><textarea name="autos[${index}][summary]"></textarea></td>
        <td><textarea name="autos[${index}][description]"></textarea></td>
        <td><input type="text" name="autos[${index}][estado]" value="Disponible"></td>
        <td><div class="row-actions"><button class="btn btn-remove" type="button" onclick="removeRow(this)">Quitar</button></div></td>
    `;
    tbody.appendChild(tr);
}

function removeRow(btn) {
    const tr = btn.closest('tr');
    if (tr) tr.remove();
}
</script>
</body>
</html>
