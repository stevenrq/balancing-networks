<!DOCTYPE html>
<html lang="es">
<!-- index.php - Estructura Visual de la Aplicación -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balanceo de Líneas de Producción</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Fuente Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white p-3" style="width: 260px; min-height: 100vh;">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-lightning-fill text-white"></i>
                </div>
                <span class="fs-5 fw-bold">Red de Balanceo</span>
            </div>
            <div class="nav flex-column nav-pills">
                <button class="nav-link active text-white mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-fill"></i> Dashboard
                </button>
                <button id="btn-help" class="nav-link text-white-50 d-flex align-items-center gap-2">
                    <i class="bi bi-question-circle-fill"></i> Ayuda
                </button>
            </div>
            <div class="mt-auto text-center text-white-50 small pt-4">
                <p>v2.0.0</p>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="w-100 overflow-auto" style="height: 100vh;">
            <div class="container-fluid p-4">
                <header class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 fw-bold text-dark mb-0">Panel de Control</h1>
                    <div class="d-flex gap-2">
                        <button id="btn-new-scenario" class="btn btn-outline-secondary">Nuevo Escenario</button>
                        <button id="btn-load-example" class="btn btn-outline-secondary">Cargar Ejemplo</button>
                    </div>
                </header>

                <!-- Configuración Grid -->
                <div class="row g-4 mb-4">
                    <!-- Tarjeta 1: Parámetros Generales -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm h-100 border-0">
                            <div class="card-header bg-white py-3">
                                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-gear-fill text-secondary"></i> Configuración
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="tiempo_turno" class="form-label fw-semibold text-secondary small">Tiempo Disponible (min/día)</label>
                                    <input type="number" class="form-control" id="tiempo_turno" placeholder="Ej: 480">
                                    <div class="invalid-feedback d-none" id="err-tiempo">Requerido</div>
                                </div>
                                <div class="mb-3">
                                    <label for="demanda" class="form-label fw-semibold text-secondary small">Demanda (unidades/día)</label>
                                    <input type="number" class="form-control" id="demanda" placeholder="Ej: 400">
                                    <div class="invalid-feedback d-none" id="err-demanda">Requerido</div>
                                </div>
                                <div class="mb-3">
                                    <label for="regla-select" class="form-label fw-semibold text-secondary small">Regla de Prioridad</label>
                                    <select id="regla-select" class="form-select">
                                        <option value="DEFAULT">RPW (Peso Posicional)</option>
                                        <option value="SPT">SPT (Menor Duración)</option>
                                        <option value="MAX_SUCC_TIME">Mayor Tiempo Sucesores</option>
                                        <option value="MIN_SUCC_TIME">Menor Tiempo Sucesores</option>
                                        <option value="RANDOM">Aleatorio</option>
                                    </select>
                                    <!-- Radios ocultos para compatibilidad JS -->
                                    <div class="d-none">
                                        <input type="radio" name="regla" value="DEFAULT" checked>
                                        <input type="radio" name="regla" value="SPT">
                                        <input type="radio" name="regla" value="MAX_SUCC_TIME">
                                        <input type="radio" name="regla" value="MIN_SUCC_TIME">
                                        <input type="radio" name="regla" value="RANDOM">
                                    </div>
                                </div>
                                <button id="btn-random" class="btn btn-link w-100 text-decoration-none">🎲 Generar Aleatorio</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Lista de Tareas -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm h-100 border-0">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-list-task text-secondary"></i> Lista de Tareas
                                </h5>
                                <div>
                                    <button id="btn-add-row" class="btn btn-sm btn-primary">+ Tarea</button>
                                    <button id="btn-clear-rows" class="btn btn-sm btn-danger">Limpiar</button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table id="tasks-table" class="table table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th>ID</th>
                                                <th>Duración (s)</th>
                                                <th>Precedencias</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Filas JS -->
                                        </tbody>
                                    </table>
                                </div>
                                <div id="table-errors" class="alert alert-danger m-3 d-none"></div>
                            </div>
                            <div class="card-footer bg-light p-3">
                                <button id="btn-calculate" class="btn btn-primary w-100 btn-lg fw-bold">
                                    CALCULAR BALANCEO
                                </button>
                                <div id="loading-indicator" class="progress mt-3 d-none" style="height: 4px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Resultados -->
                <section id="results-container" class="d-none">
                    <div class="d-flex align-items-center my-4 text-secondary">
                        <hr class="flex-grow-1">
                        <span class="px-3 fw-bold">Resultados</span>
                        <hr class="flex-grow-1">
                    </div>

                    <!-- KPIs -->
                    <div class="row g-3 mb-4">
                        <div class="col-md">
                            <div class="card border-0 shadow-sm border-bottom border-4 border-primary h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary">
                                        <i class="bi bi-stopwatch fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase text-secondary fw-bold small">Takt Time</div>
                                        <div class="fs-4 fw-bold text-dark" id="res-takt">0 s</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card border-0 shadow-sm border-bottom border-4 border-info h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-info bg-opacity-10 p-3 text-info">
                                        <i class="bi bi-calculator fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase text-secondary fw-bold small">Suma Tiempos</div>
                                        <div class="fs-4 fw-bold text-dark" id="res-sum">0 s</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card border-0 shadow-sm border-bottom border-4 border-warning h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-warning bg-opacity-10 p-3 text-warning">
                                        <i class="bi bi-building fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase text-secondary fw-bold small">Est. Teóricas</div>
                                        <div class="fs-4 fw-bold text-dark" id="res-nt">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card border-0 shadow-sm border-bottom border-4 border-success h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success">
                                        <i class="bi bi-check-circle fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase text-secondary fw-bold small">Est. Reales</div>
                                        <div class="fs-4 fw-bold text-dark" id="res-nreal">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="card border-0 shadow-sm border-bottom border-4 border-purple h-100" style="border-color: #a855f7 !important;">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <div class="rounded-3 p-3" style="background-color: #f3e8ff; color: #a855f7;">
                                        <i class="bi bi-lightning-charge fs-4"></i>
                                    </div>
                                    <div>
                                        <div class="text-uppercase text-secondary fw-bold small">Eficiencia</div>
                                        <div class="fs-4 fw-bold text-dark" id="res-efficiency">0%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Visualización Estaciones -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title mb-0">Distribución de Estaciones</h5>
                                </div>
                                <div class="card-body bg-light">
                                    <div id="stations-grid" class="d-grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Grafo -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title mb-0">Grafo de Precedencias</h5>
                                </div>
                                <div class="card-body p-0 overflow-auto d-flex justify-content-center bg-light rounded-bottom" id="graph-wrapper">
                                    <div id="graph-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla Detallada -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Detalle Paso a Paso</h5>
                            <button id="btn-toggle-steps" class="btn btn-sm btn-outline-primary">Ver Log Algoritmo</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="detailed-table" class="table table-striped table-hover mb-0 small">
                                    <thead class="table-light">
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
                            <div id="steps-content" class="p-3 bg-light border-top d-none" style="max-height: 300px; overflow-y: auto;"></div>
                        </div>
                    </div>

                    <div class="text-end mb-5">
                        <button id="btn-clear-results" class="btn btn-link text-danger text-decoration-none">Limpiar Resultados</button>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Modal Ayuda Bootstrap -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">Guía de Uso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex gap-3 mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-weight: bold;">1</div>
                        <p class="mb-0"><strong>Configura:</strong> Define el tiempo disponible y la demanda diaria.</p>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-weight: bold;">2</div>
                        <p class="mb-0"><strong>Añade Tareas:</strong> Ingresa ID, duración y precedencias (separadas por coma).</p>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-weight: bold;">3</div>
                        <p class="mb-0"><strong>Calcula:</strong> El sistema optimizará la línea según la regla elegida.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    <script>
        // Compatibilidad para selector de reglas
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

