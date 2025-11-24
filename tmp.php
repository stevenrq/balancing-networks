<?php
$tasks=[
    ["letra_tarea"=>"A","duracion"=>20,"precedencias"=>""],
    ["letra_tarea"=>"B","duracion"=>55,"precedencias"=>""],
    ["letra_tarea"=>"C","duracion"=>18,"precedencias"=>"A"],
    ["letra_tarea"=>"D","duracion"=>45,"precedencias"=>"A"],
    ["letra_tarea"=>"E","duracion"=>12,"precedencias"=>"B"],
    ["letra_tarea"=>"F","duracion"=>50,"precedencias"=>"B"],
    ["letra_tarea"=>"G","duracion"=>25,"precedencias"=>"C"],
    ["letra_tarea"=>"H","duracion"=>28,"precedencias"=>"D"],
    ["letra_tarea"=>"I","duracion"=>20,"precedencias"=>"E,F"],
    ["letra_tarea"=>"J","duracion"=>35,"precedencias"=>"G"],
    ["letra_tarea"=>"K","duracion"=>30,"precedencias"=>"H"],
    ["letra_tarea"=>"L","duracion"=>22,"precedencias"=>"I,J,K"],
];
require 'functions.php';
$b=new Balanceador();
foreach(["DEFAULT","SPT","MAX_SUCC_TIME","MIN_SUCC_TIME","RANDOM"] as $r){
    srand(1);
    $res=$b->calcularBalanceo(480,360,$tasks,$r);
    echo "\n-- $r --\n";
    echo "Suma tiempos: {$res['suma_tiempos']} | Nt {$res['num_teorico_estaciones']} | Nr {$res['num_estaciones']} | Eff {$res['eficiencia']}\n";
    foreach($res['estaciones'] as $est){
        $ids=array_map(fn($t)=>$t['letra'],$est['tareas']);
        echo "E{$est['id']}: ".implode(',', $ids)." (t={$est['tiempo_total']})\n";
    }
}
