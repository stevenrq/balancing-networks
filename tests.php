<?php
// tests.php - Validación de reglas de prioridad del balanceador
// Ejecuta el algoritmo con EXAMPLE_DATA y comprueba: precedencias, tiempos,
// unicidad de tareas, y que cada regla aplique su criterio de prioridad.

require 'functions.php';

const EXAMPLE_TURN_TIME_MIN = 480;
const EXAMPLE_DEMAND = 360;
const EXAMPLE_DATA = [
    ['letra_tarea' => 'A', 'duracion' => 20, 'precedencias' => ''],
    ['letra_tarea' => 'B', 'duracion' => 55, 'precedencias' => ''],
    ['letra_tarea' => 'C', 'duracion' => 18, 'precedencias' => 'A'],
    ['letra_tarea' => 'D', 'duracion' => 45, 'precedencias' => 'A'],
    ['letra_tarea' => 'E', 'duracion' => 12, 'precedencias' => 'B'],
    ['letra_tarea' => 'F', 'duracion' => 50, 'precedencias' => 'B'],
    ['letra_tarea' => 'G', 'duracion' => 25, 'precedencias' => 'C'],
    ['letra_tarea' => 'H', 'duracion' => 28, 'precedencias' => 'D'],
    ['letra_tarea' => 'I', 'duracion' => 20, 'precedencias' => 'E,F'],
    ['letra_tarea' => 'J', 'duracion' => 35, 'precedencias' => 'G'],
    ['letra_tarea' => 'K', 'duracion' => 30, 'precedencias' => 'H'],
    ['letra_tarea' => 'L', 'duracion' => 22, 'precedencias' => 'I,J,K'],
];

// Expectativas conocidas para EXAMPLE_DATA (semilla fija en RANDOM)
$expectedLayouts = [
    'DEFAULT' => [
        ['A', 'B'],
        ['D', 'C', 'E'],
        ['F', 'G'],
        ['H', 'J'],
        ['K', 'I', 'L'],
    ],
    'SPT' => [
        ['A', 'C', 'G'],
        ['J', 'D'],
        ['H', 'K'],
        ['B', 'E'],
        ['F', 'I'],
        ['L'],
    ],
    'MAX_SUCC_TIME' => [
        ['A', 'B'],
        ['C', 'D', 'E'],
        ['G', 'H'],
        ['F', 'I'],
        ['J', 'K'],
        ['L'],
    ],
    'MIN_SUCC_TIME' => [
        ['B', 'E'],
        ['F', 'I'],
        ['A', 'D'],
        ['H', 'K', 'C'],
        ['G', 'J'],
        ['L'],
    ],
    'RANDOM' => [ // semilla = 1
        ['A', 'D'],
        ['C', 'G', 'H'],
        ['K', 'J'],
        ['B', 'E'],
        ['F', 'I'],
        ['L'],
    ],
];

$rules = [
    ['id' => 'DEFAULT', 'label' => 'RPW', 'priority' => 'rpw', 'seed' => null, 'expectedStations' => 5],
    ['id' => 'SPT', 'priority' => 'min_dur', 'seed' => null, 'expectedStations' => 6],
    ['id' => 'MAX_SUCC_TIME', 'priority' => 'max_succ_time', 'seed' => null, 'expectedStations' => 6],
    ['id' => 'MIN_SUCC_TIME', 'priority' => 'min_succ_time', 'seed' => null, 'expectedStations' => 6],
    ['id' => 'RANDOM', 'priority' => 'random', 'seed' => 1, 'expectedStations' => 6],
];

$balanceador = new Balanceador();
$takt = (EXAMPLE_TURN_TIME_MIN * 60) / EXAMPLE_DEMAND;
$results = [];

foreach ($rules as $ruleMeta) {
    $ruleId = $ruleMeta['id'];
    $ruleLabel = isset($ruleMeta['label']) ? $ruleMeta['label'] : $ruleId;
    if ($ruleMeta['seed'] !== null) {
        srand($ruleMeta['seed']);
    }
    $res = $balanceador->calcularBalanceo(EXAMPLE_TURN_TIME_MIN, EXAMPLE_DEMAND, EXAMPLE_DATA, $ruleId);
    $ruleReport = [];

    $ruleReport[] = assertCondition(
        uniqueAssignments($res, EXAMPLE_DATA),
        'Todas las tareas se asignan exactamente una vez.'
    );

    $ruleReport[] = assertCondition(
        precedencesRespected($res),
        'Las precedencias se respetan (cada predecesora fue programada antes que su sucesor).'
    );

    $ruleReport[] = assertCondition(
        stationsWithinTakt($res, $takt),
        'Ninguna estación supera el Takt Time.'
    );

    $ruleReport[] = assertCondition(
        validatePriorityRule($res, $ruleMeta['priority']),
        'La selección en cada paso cumple el criterio de prioridad de la regla.'
    );

    if (isset($expectedLayouts[$ruleId])) {
        $ruleReport[] = assertCondition(
            matchesLayout($res, $expectedLayouts[$ruleId]),
            'La secuencia de estaciones coincide con el layout esperado para EXAMPLE_DATA.'
        );
    }

    $results[] = [
        'rule' => $ruleId,
        'label' => $ruleLabel,
        'stations' => $res['num_estaciones'],
        'expectedStations' => $ruleMeta['expectedStations'],
        'layout' => extractLayout($res),
        'logs' => $ruleReport,
        'metrics' => [
            'takt' => $res['takt_time'],
            'sum' => $res['suma_tiempos'],
            'nt' => $res['num_teorico_estaciones'],
            'nr' => $res['num_estaciones'],
            'eff' => $res['eficiencia'],
        ],
    ];
}

// Chequeo adicional: la regla RANDOM produce al menos un layout distinto con otra semilla
$randomVariance = compareRandomSeeds($balanceador);

printReport($results, $takt, $randomVariance);

// ----------------- Helpers ----------------- //

function extractLayout($result)
{
    $layout = [];
    foreach ($result['estaciones'] as $est) {
        $layout[] = array_map(function ($t) {
            return $t['letra'];
        }, $est['tareas']);
    }
    return $layout;
}

function assertCondition($cond, $message)
{
    return [
        'ok' => (bool) $cond,
        'message' => $message,
    ];
}

function uniqueAssignments($result, $sourceData)
{
    $expectedCount = count($sourceData);
    $seen = [];
    foreach ($result['estaciones'] as $est) {
        foreach ($est['tareas'] as $task) {
            $seen[] = $task['letra'];
        }
    }
    return count($seen) === $expectedCount && count(array_unique($seen)) === $expectedCount;
}

function precedencesRespected($result)
{
    $order = [];
    foreach ($result['estaciones'] as $est) {
        foreach ($est['tareas'] as $task) {
            $order[] = $task['letra'];
        }
    }
    $pos = array_flip($order);

    foreach ($result['estaciones'] as $est) {
        foreach ($est['tareas'] as $task) {
            foreach ($task['precedencias'] as $pred) {
                if (!isset($pos[$pred]) || $pos[$pred] > $pos[$task['letra']]) {
                    return false;
                }
            }
        }
    }
    return true;
}

function stationsWithinTakt($result, $takt)
{
    foreach ($result['estaciones'] as $est) {
        if ($est['tiempo_total'] > $takt + 1e-6) {
            return false;
        }
    }
    return true;
}

function validatePriorityRule($result, $mode)
{
    if ($mode === 'random') {
        // Validamos que la tarea seleccionada siempre era un candidato válido
        foreach ($result['paso_a_paso'] as $estLog) {
            foreach ($estLog['pasos'] as $step) {
                $candidates = array_column($step['candidatos'], 'id');
                if (!in_array($step['seleccionada'], $candidates, true)) {
                    return false;
                }
            }
        }
        return true;
    }

    foreach ($result['paso_a_paso'] as $estLog) {
        foreach ($estLog['pasos'] as $step) {
            if (empty($step['candidatos'])) {
                continue;
            }
            switch ($mode) {
                case 'min_dur':
                    $metric = 'duracion';
                    $target = min(array_column($step['candidatos'], $metric));
                    break;
                case 'max_succ_time':
                    $metric = 'tiempo_sucesores';
                    $target = max(array_column($step['candidatos'], $metric));
                    break;
        case 'min_succ_time':
            $metric = 'tiempo_sucesores';
            $target = min(array_column($step['candidatos'], $metric));
            break;
        case 'rpw':
            $metric = null;
            $target = max(array_map(function ($c) {
                return $c['duracion'] + $c['tiempo_sucesores'];
            }, $step['candidatos']));
            break;
        default:
            return false;
        }

        foreach ($step['candidatos'] as $cand) {
            if ($cand['id'] === $step['seleccionada']) {
                if ($metric !== null && $cand[$metric] === $target) {
                    continue 2; // siguiente paso
                }
                if ($metric === null && ($cand['duracion'] + $cand['tiempo_sucesores']) === $target) {
                    continue 2;
                }
            }
        }
        return false; // no se encontró coincidencia con la regla
    }
}
    return true;
}

function matchesLayout($result, $expectedLayout)
{
    return extractLayout($result) === $expectedLayout;
}

function compareRandomSeeds($balanceador)
{
    srand(1);
    $layout1 = extractLayout($balanceador->calcularBalanceo(EXAMPLE_TURN_TIME_MIN, EXAMPLE_DEMAND, EXAMPLE_DATA, 'RANDOM'));
    srand(2);
    $layout2 = extractLayout($balanceador->calcularBalanceo(EXAMPLE_TURN_TIME_MIN, EXAMPLE_DEMAND, EXAMPLE_DATA, 'RANDOM'));
    return $layout1 !== $layout2;
}

function printReport($results, $takt, $randomVariance)
{
    echo "=== Pruebas de reglas de prioridad (EXAMPLE_DATA) ===\n";
    echo "Takt Time: {$takt}s\n\n";

    foreach ($results as $res) {
        $m = $res['metrics'];
        $label = isset($res['label']) ? $res['label'] : $res['rule'];
        echo "- Regla {$label} ({$res['rule']}) (Estaciones: {$res['stations']})\n";
        echo "  KPIs -> Takt: {$m['takt']}s | Suma Tiempos: {$m['sum']}s | Nt: {$m['nt']} | Nr: {$m['nr']} | Eficiencia: {$m['eff']}%\n";
        foreach ($res['logs'] as $log) {
            $prefix = $log['ok'] ? "  [OK] " : " [FALLO] ";
            echo $prefix . $log['message'] . "\n";
        }
        echo "  Layout observado: " . formatLayout($res['layout']) . "\n";
        echo "  Estaciones esperadas: {$res['expectedStations']} | Total estaciones = " . ($res['stations'] === $res['expectedStations'] ? 'OK' : 'DESVIO') . "\n\n";
    }

    echo "Chequeo de variabilidad RANDOM (semillas distintas generan layouts distintos): " . ($randomVariance ? "OK" : "FALLO") . "\n";
}

function formatLayout($layout)
{
    $parts = [];
    foreach ($layout as $i => $tasks) {
        $parts[] = 'E' . ($i + 1) . '[' . implode(',', $tasks) . ']';
    }
    return implode(' | ', $parts);
}
