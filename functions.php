<?php
// functions.php - Lógica de Negocio y Algoritmo de Balanceo
// Este archivo contiene el "cerebro" de la aplicación. Aquí es donde se toman las decisiones
// sobre cómo organizar las tareas en las estaciones de trabajo.

class Balanceador
{

    /**
     * Calcula el balanceo de línea.
     * 
     * Este es el proceso principal. Su objetivo es agrupar las tareas en "Estaciones de Trabajo"
     * de manera que el trabajo fluya suavemente sin cuellos de botella.
     * 
     * @param int $tiempoDisponibleMinutos - Cuánto tiempo trabajamos al día (ej. 480 minutos = 8 horas).
     * @param int $demandaUnidades - Cuántos productos necesitamos fabricar al día.
     * @param array $listaTareas - La lista de pasos necesarios para fabricar el producto.
     * @param string $regla - La estrategia para decidir qué tarea va primero (ej. la más larga, la que tiene más seguidores, etc.).
     */
    public function calcularBalanceo($tiempoDisponibleMinutos, $demandaUnidades, $listaTareas, $regla = 'DEFAULT')
    {

        // Paso A: Calcular el "Ritmo de la Línea" (Takt Time)
        // El Takt Time es el tiempo máximo que podemos gastar en una unidad para cumplir con la demanda.
        // Si nos tardamos más que esto en una estación, no llegaremos a la meta diaria.
        $tiempoDisponibleSegundos = $tiempoDisponibleMinutos * 60;
        if ($demandaUnidades <= 0) {
            throw new Exception("La demanda debe ser mayor a 0 para poder calcular el ritmo de producción.");
        }
        $taktTime = $tiempoDisponibleSegundos / $demandaUnidades;

        // Paso B: Preparar y organizar la información de las tareas
        // Convertimos la lista simple en un formato más útil, calculando quién depende de quién.
        $tareasProcesadas = $this->prepararTareas($listaTareas);

        // --- VALIDACIONES DE SEGURIDAD ---
        
        // 1. Verificar que ninguna tarea sea imposible de hacer
        // Si una sola tarea toma más tiempo que el Takt Time, es imposible cumplir la demanda
        // con una sola línea simple. Se necesitarían estaciones paralelas (no cubierto aquí).
        foreach ($tareasProcesadas as $t) {
            if ($t['duracion'] > $taktTime) {
                throw new Exception("Problema: La tarea '{$t['letra']}' toma {$t['duracion']}s, pero el ritmo necesario es de {$taktTime}s. Esta tarea es un cuello de botella insalvable.");
            }
        }

        // 2. Verificar que no haya círculos viciosos (Ciclos)
        // Ejemplo: Si A depende de B, y B depende de A, nunca podremos empezar.
        if ($this->detectarCiclos($tareasProcesadas)) {
             throw new Exception("Error de Lógica: Hay un ciclo en las dependencias (ej. A espera a B, y B espera a A). Revisa el orden de las tareas.");
        }

        // --------------------------------

        // Paso C: Asignar tareas a las estaciones (El Algoritmo)
        // Vamos llenando "cajas" (estaciones) con tareas hasta que se llenen (lleguen al Takt Time)
        // o hasta que no podamos meter nada más por las reglas de precedencia.
        $estaciones = [];
        $tareasPorAsignar = $tareasProcesadas;
        $numeroEstacion = 1;
        $pasoAPasoLog = []; // Guardamos el historial para mostrarlo al usuario después

        // Mientras queden tareas sin asignar...
        while (count($tareasPorAsignar) > 0) {
            // Creamos una nueva estación vacía
            $estacionActual = [
                'id' => $numeroEstacion,
                'tareas' => [],
                'tiempo_total' => 0,
                'tiempo_ocioso' => 0
            ];

            $tiempoRestanteEstacion = $taktTime; // Al principio, tenemos todo el tiempo del turno disponible
            $huboAsignacion = true;
            $pasosEstacion = []; // Registro de decisiones para esta estación

            // Intentamos llenar la estación actual tanto como sea posible
            while ($huboAsignacion && count($tareasPorAsignar) > 0) {
                $huboAsignacion = false;

                // 1. Buscar Candidatos: ¿Qué tareas PODEMOS hacer ahora?
                // Deben cumplirse dos condiciones:
                // a) Todas sus tareas previas (precedencias) ya están hechas.
                // b) Caben en el tiempo que le queda a esta estación.
                $candidatos = [];
                foreach ($tareasPorAsignar as $key => $tarea) {
                    if ($this->precedenciasCumplidas($tarea['precedencias'], $tareasPorAsignar)) {
                        if ($tarea['duracion'] <= $tiempoRestanteEstacion) {
                            $candidatos[] = $tarea;
                        }
                    }
                }

                // Guardamos información para el reporte "Paso a Paso"
                $pasoLog = [
                    'tiempo_restante' => $tiempoRestanteEstacion,
                    'candidatos' => array_map(function ($t) {
                        return [
                            'id' => $t['letra'],
                            'duracion' => $t['duracion'],
                            'sucesores' => $t['num_sucesores'],
                            'tiempo_sucesores' => $t['suma_tiempos_sucesores']
                        ];
                    }, $candidatos),
                    'seleccionada' => null,
                    'criterio' => $regla
                ];

                // 2. Elegir la mejor tarea (Desempate)
                // Si hay varias opciones, usamos la "Regla" elegida por el usuario para decidir.
                if (!empty($candidatos)) {
                    usort($candidatos, function ($a, $b) use ($regla) {
                        switch ($regla) {
                            case 'SPT': // Preferir las tareas más cortas (Shortest Processing Time)
                                return $a['duracion'] - $b['duracion'];

                            case 'MAX_SUCC_TIME': // Preferir tareas que desbloquean mucho trabajo futuro
                                return $b['suma_tiempos_sucesores'] - $a['suma_tiempos_sucesores'];

                            case 'MIN_SUCC_TIME': // Preferir tareas que desbloquean poco trabajo futuro
                                return $a['suma_tiempos_sucesores'] - $b['suma_tiempos_sucesores'];

                            case 'RANDOM': // Elegir al azar (útil para comparar)
                                return rand(-1, 1);

                            case 'DEFAULT': // RPW (Ranked Positional Weight)
                                // Esta es la regla estándar: combina duración y trabajo futuro.
                                // Prioriza tareas largas que además son críticas para otras.
                                $rpwA = $a['duracion'] + $a['suma_tiempos_sucesores'];
                                $rpwB = $b['duracion'] + $b['suma_tiempos_sucesores'];
                                return $rpwB - $rpwA;
                        }
                    });

                    // 3. Asignar la ganadora
                    $mejorTarea = $candidatos[0];

                    // La metemos en la estación
                    $estacionActual['tareas'][] = $mejorTarea;
                    $estacionActual['tiempo_total'] += $mejorTarea['duracion'];
                    $tiempoRestanteEstacion -= $mejorTarea['duracion'];

                    // La quitamos de la lista de pendientes
                    unset($tareasPorAsignar[$mejorTarea['id_interno']]);
                    $huboAsignacion = true;

                    // Registramos la decisión
                    $pasoLog['seleccionada'] = $mejorTarea['letra'];
                    $pasosEstacion[] = $pasoLog;
                }
            }

            // Cerramos la estación
            // El tiempo ocioso es el tiempo que sobra y no se pudo usar (ineficiencia)
            $estacionActual['tiempo_ocioso'] = $taktTime - $estacionActual['tiempo_total'];
            $estaciones[] = $estacionActual;

            $pasoAPasoLog[] = [
                'estacion_id' => $numeroEstacion,
                'pasos' => $pasosEstacion
            ];

            $numeroEstacion++;
        }

        // Paso D: Calcular Estadísticas Finales
        $sumaTiemposTareas = 0;
        foreach ($tareasProcesadas as $t) {
            $sumaTiemposTareas += $t['duracion'];
        }

        $numEstacionesReales = count($estaciones);
        
        // Eficiencia: ¿Qué porcentaje del tiempo pagado se usa realmente produciendo?
        $eficiencia = 0;
        if ($numEstacionesReales > 0 && $taktTime > 0) {
            $eficiencia = ($sumaTiemposTareas / ($numEstacionesReales * $taktTime)) * 100;
        }

        // Número teórico mínimo de estaciones (si la eficiencia fuera 100%)
        $nt = ($taktTime > 0) ? ceil($sumaTiemposTareas / $taktTime) : 0;

        return [
            'takt_time' => $taktTime,
            'suma_tiempos' => $sumaTiemposTareas,
            'num_teorico_estaciones' => $nt,
            'num_estaciones' => $numEstacionesReales,
            'eficiencia' => round($eficiencia, 2),
            'estaciones' => $estaciones,
            'paso_a_paso' => $pasoAPasoLog,
            'regla_usada' => $regla
        ];
    }

    // Prepara los datos crudos para que sean fáciles de procesar
    private function prepararTareas($tareasRaw)
    {
        $tareasMap = [];
        // Primero, limpiamos y estructuramos cada tarea
        foreach ($tareasRaw as $t) {
            $precedencias = [];
            if (!empty($t['precedencias'])) {
                $parts = explode(',', $t['precedencias']);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '')
                        $precedencias[] = $p;
                }
            }

            $tareasMap[$t['letra_tarea']] = [
                'id_interno' => $t['letra_tarea'],
                'letra' => $t['letra_tarea'],
                'duracion' => (int) $t['duracion'],
                'precedencias' => $precedencias,
                'sucesores_directos' => [],
                'num_sucesores' => 0,
                'suma_tiempos_sucesores' => 0
            ];
        }

        // Construimos el mapa de "Sucesores" (quién depende de mí)
        // Esto es necesario para calcular reglas como RPW
        foreach ($tareasMap as $letra => $data) {
            foreach ($data['precedencias'] as $padre) {
                if (isset($tareasMap[$padre])) {
                    $tareasMap[$padre]['sucesores_directos'][] = $letra;
                }
            }
        }

        // Calculamos métricas avanzadas para cada tarea (para las reglas de prioridad)
        foreach ($tareasMap as $letra => &$data) {
            $todosSucesores = [];
            $this->obtenerTodosSucesores($letra, $tareasMap, $todosSucesores);
            $data['num_sucesores'] = count($todosSucesores);

            $sumaTiempo = 0;
            foreach ($todosSucesores as $sucesorLetra) {
                if (isset($tareasMap[$sucesorLetra])) {
                    $sumaTiempo += $tareasMap[$sucesorLetra]['duracion'];
                }
            }
            $data['suma_tiempos_sucesores'] = $sumaTiempo;
        }

        return $tareasMap;
    }

    // Función auxiliar recursiva para encontrar TODOS los descendientes de una tarea
    private function obtenerTodosSucesores($letra, $tareasMap, &$acumulador)
    {
        if (!isset($tareasMap[$letra]))
            return;

        foreach ($tareasMap[$letra]['sucesores_directos'] as $hijo) {
            if (!in_array($hijo, $acumulador)) {
                $acumulador[] = $hijo;
                $this->obtenerTodosSucesores($hijo, $tareasMap, $acumulador);
            }
        }
    }

    // Verifica si una tarea ya se puede hacer (si todas sus dependencias están listas)
    // $tareasPendientes contiene las tareas que AÚN NO se han asignado.
    // Si una precedencia requerida está en $tareasPendientes, significa que no está hecha.
    private function precedenciasCumplidas($precedenciasRequeridas, $tareasPendientes)
    {
        if (empty($precedenciasRequeridas))
            return true;
        foreach ($precedenciasRequeridas as $req) {
            if (isset($tareasPendientes[$req])) {
                return false; // Falta un requisito
            }
        }
        return true; // Todo listo
    }

    // Detecta si hay bucles infinitos en las dependencias
    private function detectarCiclos($tareasMap) {
        $visitado = []; // 0: no visitado, 1: visitando actualmente, 2: ya visitado y seguro
        $pilaRecursion = [];

        foreach ($tareasMap as $id => $tarea) {
            $visitado[$id] = 0;
            $pilaRecursion[$id] = false;
        }

        foreach ($tareasMap as $id => $tarea) {
            if ($visitado[$id] == 0) {
                if ($this->esCiclico($id, $tareasMap, $visitado, $pilaRecursion)) {
                    return true;
                }
            }
        }
        return false;
    }

    // Algoritmo de búsqueda en profundidad (DFS) para encontrar ciclos
    private function esCiclico($id, $tareasMap, &$visitado, &$pilaRecursion) {
        $visitado[$id] = 1; // Marcamos que estamos revisando este camino
        $pilaRecursion[$id] = true;

        // Revisamos a los "padres" (precedencias). Si siguiendo a los padres volvemos a nosotros mismos, hay un ciclo.
        foreach ($tareasMap[$id]['precedencias'] as $padreId) {
            if (isset($tareasMap[$padreId])) {
                if ($visitado[$padreId] == 0) {
                    if ($this->esCiclico($padreId, $tareasMap, $visitado, $pilaRecursion)) {
                        return true;
                    }
                } else if ($pilaRecursion[$padreId]) {
                    // ¡Encontramos un nodo que ya estamos visitando en este mismo camino! Es un ciclo.
                    return true;
                }
            }
        }

        $pilaRecursion[$id] = false; // Ya terminamos con este nodo
        $visitado[$id] = 2; // Marcado como seguro
        return false;
    }
}
