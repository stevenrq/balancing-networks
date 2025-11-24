# Guía de Ejecución - Redes de Balanceo

Esta aplicación es un sistema simple de Redes de Balanceo construido en PHP nativo con MySQL.

## Requisitos Previos

1. **PHP**: Asegúrate de tener PHP instalado (versión 7.4 o superior recomendada).
2. **MySQL**: Necesitas un servidor MySQL en ejecución.

## Configuración de la Base de Datos

1. **Crear la Base de Datos**:
   Ejecuta el script SQL incluido para crear la base de datos y las tablas necesarias.
   Puedes hacerlo desde la línea de comandos o usando una herramienta como phpMyAdmin o MySQL Workbench.

   Comando de terminal (te pedirá tu contraseña de root):

   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configurar Credenciales**:
   Abre el archivo `db.php` y edita las credenciales de conexión si son diferentes a las predeterminadas:

   ```php
   private $username = 'root'; // Tu usuario de MySQL
   private $password = '';     // Tu contraseña de MySQL
   ```

## Ejecutar la Aplicación

Puedes usar el servidor web incorporado de PHP para ejecutar la aplicación localmente.

1. Abre una terminal en la carpeta del proyecto.
2. Ejecuta el siguiente comando:

   ```bash
   php -S localhost:8000
   ```

3. Abre tu navegador y visita: [http://localhost:8000](http://localhost:8000)

## Estructura de Archivos

- `index.php`: Página principal de la aplicación.
- `api.php`: Endpoints para las peticiones AJAX.
- `db.php`: Configuración y conexión a la base de datos.
- `functions.php`: Lógica de negocio y cálculos de balanceo.
- `script.js`: Lógica del frontend.
- `style.css`: Estilos de la aplicación.
