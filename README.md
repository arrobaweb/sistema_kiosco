# Sistema Kiosco - Esqueleto

Este repositorio es un esqueleto básico para un sistema de ventas para un kiosco usando PHP y MySQL (XAMPP).

Pasos rápidos de instalación:

1. Asegurate que XAMPP esté instalado y Apache+MySQL estén corriendo.
2. Crear la base de datos en MySQL Workbench: `CREATE DATABASE sistema_kiosco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Importar `migrations/init.sql` en la base de datos `sistema_kiosco`.
4. Colocar esta carpeta en `c:\xampp\htdocs\sistema` (si no está ya)
5. Abrir en el navegador: `http://localhost/sistema/public/`

Siguientes pasos recomendados:

- Añadir autenticación de usuarios y gestión de sesiones.
- Formulario para crear/editar productos.
- Mejorar UI y agregar impresión de ticket.
- Validaciones y manejo de errores más robusto.

---

## Ejecutar tests (PHPUnit) ✅

1. Instala dependencias: `composer install`.
2. Asegúrate de que la base de datos esté creada e importadas las migraciones (`migrations/init.sql`, y si corresponde `migrations/add_cost_to_products.sql` y `migrations/add_purchases_and_suppliers.sql`).
3. Configura la conexión en `config/db.php` (constantes `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. Ejecuta los tests:
   - `vendor/bin/phpunit` (o `vendor\bin\phpunit` en Windows PowerShell).
   - Para ejecutar un archivo concreto: `vendor/bin/phpunit tests/PurchaseProcessorTest.php`.

Notas útiles:

- Durante las pruebas, algunos scripts web (p. ej. `public/export_purchases.php`) emiten cabeceras o llaman a `exit()`. Para facilitar la inclusión de esos scripts en los tests, la suite define la constante `PHPUNIT_RUNNING` en `tests/bootstrap.php`. Por eso verás pequeñas adaptaciones en `src/Auth.php` y `public/export_purchases.php` — esto permite que PHPUnit incluya estas páginas sin terminar el proceso o requerir un entorno HTTP completo.
- Asegúrate de tener la extensión `zip` habilitada en PHP y `git`/`7-zip` disponibles si usas Composer en Windows.

Si quieres, puedo añadir un `Makefile` o scripts `composer` (ej.: `composer test`) para simplificar estos pasos.

---

### Scripts de conveniencia

- `composer test` — ejecuta la suite de PHPUnit.
- `php migrations/run.php` — ejecuta en orden todas las migraciones SQL que estén en `migrations/`.

Ejemplos:

- `composer test` — corre los tests.
- `php migrations/run.php` — aplica las migraciones a la base de datos configurada en `config/db.php`.
