# Redes de Balanceo de Líneas

Aplicación web en PHP nativo + MySQL que calcula la distribución de tareas en estaciones bajo distintas reglas de prioridad (RPW, SPT, mayor/menor tiempo de sucesores y aleatorio), mostrando métricas, grafo de precedencias y paso a paso del algoritmo.

## Requisitos

- PHP 7.4+ (CLI y servidor embebido).
- MySQL (o MariaDB) activo.
- Navegador moderno.

## Instalación rápida

1) Clona o descarga el repositorio.  
2) Crea la base de datos y tablas:

   ```bash
   mysql -u root -p < database.sql
   ```

3) Ajusta credenciales en `db.php` si difieren de las predeterminadas:

   ```php
   private $username = 'root';
   private $password = '';
   ```

## Ejecutar la app

Desde la raíz del proyecto:

```bash
php -S localhost:8000
```

Abre <http://localhost:8000> en el navegador.

## Uso básico

1) Ingresa Tiempo Disponible (min/día) y Demanda (unidades/día).  
2) Añade tareas con duración y precedencias (separadas por coma).  
3) Elige la regla de prioridad:
   - RPW (DEFAULT): peso posicional = duración + suma tiempos sucesores.
   - SPT: menor duración.
   - MAX_SUCC_TIME: mayor suma de tiempos de sucesores.
   - MIN_SUCC_TIME: menor suma de tiempos de sucesores.
   - RANDOM: desempate aleatorio.
4) Pulsa **Calcular Balanceo**.  
5) Revisa KPIs, tarjetas de estaciones, grafo de precedencias (pan/zoom) y el detalle paso a paso.

### Datos de ejemplo

Botón **Cargar Ejemplo** usa:

```text
Tiempo turno: 480 min, Demanda: 360 uds (Takt 80 s)
Tareas: A20, B55, C18(A), D45(A), E12(B), F50(B), G25(C),
        H28(D), I20(E,F), J35(G), K30(H), L22(I,J,K)
```

## Tests de reglas

Hay un script CLI que valida todas las reglas con el dataset de ejemplo, comprobando precedencias, ocupación de estaciones, KPIs y layouts esperados.

```bash
php tests.php
```

El reporte muestra: Takt, Suma tiempos, Nt, Nr, Eficiencia y el layout por estación para RPW, SPT, MAX_SUCC_TIME, MIN_SUCC_TIME y RANDOM (semilla fija) + variabilidad de RANDOM con otra semilla.

## Estructura de carpetas

- `index.php` UI principal (Bootstrap).
- `script.js` lógica frontend, render de resultados y grafo (SVG pan/zoom).
- `style.css` estilos.
- `api.php` endpoint JSON.
- `functions.php` algoritmo de balanceo y validaciones.
- `db.php` conexión MySQL.
- `tests.php` pruebas de reglas de prioridad.

## Notas útiles

- El estado de la tabla se guarda en `localStorage`; usa “Nuevo escenario” para limpiar o “Cargar Ejemplo” para reponer el dataset base.  
- El grafo soporta arrastrar y zoom; botones para fit/reset.  
- Takt Time = (tiempo disponible en segundos) / demanda. Ninguna tarea puede exceder el takt; si ocurre, el backend lanza error.  
- Precedencias se validan en frontend y backend para evitar ciclos o tareas imposibles.

## Problemas comunes

- Si ves KPIs distintos a los tests, asegúrate de cargar el ejemplo o de que tu tabla coincida con el dataset.  
- Error de conexión: revisa credenciales en `db.php` o que MySQL esté activo.  
- Errores 400 desde `api.php`: suelen ser JSON inválido, demanda <= 0 o tareas vacías.  
