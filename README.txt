# AutoCar Madrid - Micro CMS básico

## Archivos
- `autocar_madrid_microcms.html` → landing conectada a JSON y PHP
- `autos.json` → catálogo editable
- `admin.php` → panel básico
- `procesar.php` → guarda catálogo y leads
- `leads.csv` → registro de contactos

## Uso
1. Sube todo a la misma carpeta del hosting.
2. Abre `admin.php` y edita el catálogo.
3. La landing lee `autos.json` al cargar.
4. Los formularios guardan en `leads.csv` y redirigen a WhatsApp.

## Nota
Asegúrate de que tu hosting ejecute PHP en esta carpeta.
