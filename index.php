<!DOCTYPE html>
<html lang="es">
<!-- index.php - Estructura Visual de la Aplicación -->
<!-- Este archivo define qué elementos se ven en la pantalla: botones, tablas, gráficas, etc. -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balanceo de Líneas de Producción</title>
    <link rel="stylesheet" href="style.css">
    <!-- Fuente Google Fonts para estética moderna -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="dashboard-wrapper">
        <!-- Barra Lateral (Menú de Navegación) -->
        <nav class="sidebar">
            <div class="brand">
                <div class="logo-icon">⚡</div>
                <span>Red de Balanceo</span>
            </div>
            <div class="nav-items">
                <button class="nav-item active">
                    <span class="icon">📊</span> Dashboard
                </button>
                <button id="btn-help" class="nav-item">
                    <span class="icon">❓</span> Ayuda
                </button>
            </div>
            <div class="nav-footer">
                <p>v2.0.0</p>
            </div>
        </nav>

        <!-- Contenido Principal -->
        <main class="main-content">
            <header class="top-bar">
                <h1>Panel de Control</h1>
                <div class="actions">
                    <button id="btn-new-scenario" class="btn btn-outline">Nuevo Escenario</button>
                    <button id="btn-load-example" class="btn btn-outline">Cargar Ejemplo</button>
                </div>
            </header>

            <!-- Cuadrícula de Configuración (Entrada de Datos) -->
            <div class="config-grid">
                
                <!-- Tarjeta 1: Parámetros Generales -->
                <!-- Aquí el usuario define las reglas del juego: cuánto tiempo hay y cuánto hay que producir. -->
                <section class="card params-card">
                    <div class="card-header">
                        <h3><span class="icon">⚙️</span> Configuración</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="tiempo_turno">Tiempo Disponible (min/día)</label>
                            <input type="number" id="tiempo_turno" placeholder="Ej: 480">
                            <span class="error-msg hidden" id="err-tiempo">Requerido</span>
                        </div>
                        <div class="form-group">
                            <label for="demanda">Demanda (unidades/día)</label>
                            <input type="number" id="demanda" placeholder="Ej: 400">
                            <span class="error-msg hidden" id="err-demanda">Requerido</span>
                        </div>
                        <div class="form-group full-width">
                            <label for="regla-select">Regla de Prioridad</label>
                            <!-- La regla decide qué tarea va primero si hay varias opciones posibles -->
                            <div class="select-wrapper">
                                <select id="regla-select" class="modern-select">
                                    <option value="DEFAULT">RPW (Peso Posicional)</option>
                                    <option value="SPT">SPT (Menor Duración)</option>
                                    <option value="MAX_SUCC_TIME">Mayor Tiempo Sucesores</option>
                                    <option value="MIN_SUCC_TIME">Menor Tiempo Sucesores</option>
                                    <option value="RANDOM">Aleatorio</option>
                                </select>
                                <!-- Radios ocultos para compatibilidad con JS existente -->
                                <div class="hidden-radios" style="display:none;">
                                    <input type="radio" name="regla" value="DEFAULT" checked>
                                    <input type="radio" name="regla" value="SPT">
                                    <input type="radio" name="regla" value="MAX_SUCC_TIME">
                                    <input type="radio" name="regla" value="MIN_SUCC_TIME">
                                    <input type="radio" name="regla" value="RANDOM">
                                </div>
                            </div>
                        </div>
                        <button id="btn-random" class="btn btn-ghost full-width">🎲 Generar Aleatorio</button>
                    </div>
                </section>

                <!-- Tarjeta 2: Lista de Tareas -->
                <!-- Aquí se ingresan los pasos individuales para fabricar el producto. -->
                <section class="card tasks-card">
                    <div class="card-header">
                        <h3><span class="icon">📋</span> Lista de Tareas</h3>
                        <div class="card-actions">
                            <button id="btn-add-row" class="btn btn-sm btn-primary">+ Tarea</button>
                            <button id="btn-clear-rows" class="btn btn-sm btn-danger">Limpiar</button>
                        </div>
                    </div>
                    <div class="card-body table-wrapper">
                        <table id="tasks-table" class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Duración (s)</th>
                                    <th>Precedencias (¿Qué va antes?)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se agregan aquí con Javascript -->
                            </tbody>
                        </table>
                        <div id="table-errors" class="error-banner hidden"></div>
                    </div>
                    <div class="card-footer">
                        <button id="btn-calculate" class="btn btn-primary btn-block btn-lg">
                            CALCULAR BALANCEO
                        </button>
                        <div id="loading-indicator" class="loading-bar hidden">
                            <div class="bar"></div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Sección de Resultados (Inicialmente oculta) -->
            <section id="results-container" class="results-section hidden">
                <div class="section-divider">
                    <span>Resultados</span>
                </div>
                
                <!-- Métricas Clave (KPIs) -->
                <div class="metrics-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-data">
                            <span class="stat-label">Takt Time</span>
                            <span class="stat-value" id="res-takt">0 s</span>
                        </div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-icon">∑</div>
                        <div class="stat-data">
                            <span class="stat-label">Suma Tiempos</span>
                            <span class="stat-value" id="res-sum">0 s</span>
                        </div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">🏭</div>
                        <div class="stat-data">
                            <span class="stat-label">Estaciones (Teórico)</span>
                            <span class="stat-value" id="res-nt">0</span>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-data">
                            <span class="stat-label">Estaciones (Real)</span>
                            <span class="stat-value" id="res-nreal">0</span>
                        </div>
                    </div>
                    <div class="stat-card accent">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-data">
                            <span class="stat-label">Eficiencia</span>
                            <span class="stat-value" id="res-efficiency">0%</span>
                        </div>
                    </div>
                </div>

                <div class="viz-grid">
                    <!-- Visualización de Estaciones (Cajitas) -->
                    <div class="card stations-viz-card">
                        <div class="card-header">
                            <h3>Distribución de Estaciones</h3>
                        </div>
                        <div class="card-body">
                            <div id="stations-grid" class="stations-masonry"></div>
                        </div>
                    </div>

                    <!-- Grafo de Dependencias (Diagrama de Red) -->
                    <div class="card graph-card">
                        <div class="card-header">
                            <h3>Grafo de Precedencias</h3>
                        </div>
                        <div class="card-body graph-wrapper">
                            <div id="graph-container"></div>
                        </div>
                    </div>
                </div>

                <!-- Tabla Detallada Paso a Paso -->
                <div class="card detailed-card">
                    <div class="card-header">
                        <h3>Detalle Paso a Paso</h3>
                        <button id="btn-toggle-steps" class="btn btn-sm btn-outline">Ver Log Algoritmo</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="detailed-table" class="modern-table striped">
                                <thead>
                                    <tr>
                                        <th>Estación</th>
                                        <th>Tarea</th>
                                        <th>Duración</th>
                                        <th>Restante</th>
                                        <th>Candidatos</th>
                                        <th>Criterios</th>
                                        <th>Decisión</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="steps-content" class="steps-log hidden"></div>
                    </div>
                </div>
                
                <div class="results-actions">
                    <button id="btn-clear-results" class="btn btn-text">Limpiar Resultados</button>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal de Ayuda (Ventana emergente) -->
    <div id="help-modal" class="modal-overlay hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Guía de Uso</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <p><strong>Configura:</strong> Define el tiempo disponible y la demanda diaria.</p>
                </div>
                <div class="step-item">
                    <div class="step-num">2</div>
                    <p><strong>Añade Tareas:</strong> Ingresa ID, duración y precedencias (separadas por coma).</p>
                </div>
                <div class="step-item">
                    <div class="step-num">3</div>
                    <p><strong>Calcula:</strong> El sistema optimizará la línea según la regla elegida.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Script de compatibilidad para el nuevo selector de reglas
        document.getElementById('regla-select').addEventListener('change', function() {
            const val = this.value;
            const radios = document.getElementsByName('regla');
            radios.forEach(r => {
                if(r.value === val) r.checked = true;
            });
        });
    </script>
</body>
</html>
