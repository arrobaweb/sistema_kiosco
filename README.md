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
