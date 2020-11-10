<?php
$test_results = [];
test_0();
test_1();
test_results();
// test mi > create and compare
function test_0(){
    global $_mi, $test_results;
    $test_module = [
        "name" => "Test Method",
        "version" => 1.0
    ];
    $_mi->create($test_module);
    array_push($test_results, [
        "name" => "Test MI module",
        "state" => ($_mi->get($test_module["name"])["name"] == $test_module["name"])
    ]);
}
// test sql > connection and check tables
function test_1(){
    global $sql, $test_results, $_CONFIG;
    array_push($test_results, [
        "name" => "Test SQL connection",
        "state" => ($sql !== false)
    ]);
    $table_results = [];
    $need_repair = false;
    foreach ($_CONFIG["db"] as $name => $table){
        $result = $sql->sql->query("SELECT * FROM `".$_CONFIG["sql"]["database"]."`.`".$table."` LIMIT 1");
        if ($result == false) $need_repair = true;
        array_push($table_results, ["name"=>$name,"state"=>($result!==false)]);
    }
    array_push($test_results, [
        "name" => "Test SQL tables",
        "tables" => $table_results
    ]);
    if ($need_repair) repair_test1($table_results);
}
function repair_test1($results){
    global $sql, $test_results, $_CONFIG;
    $repair_results = [];
    $db = $_CONFIG["sql"]["database"];
    foreach ($results as $table)
         if (!$table["state"]) {
             $sql->sql->query("CREATE TABLE IF NOT EXISTS `${db}`.`".$_CONFIG["db"][$table["name"]]."` (
             ".join(",", $_CONFIG["db_constructor"][$table["name"]])."
             )");
             $result = $sql->sql->query("SELECT * FROM `${db}`.`".$_CONFIG["db"][$table["name"]]."` LIMIT 1");
             array_push($repair_results, ["name"=>$table["name"],"state"=>($result!==false)]);
         }
    array_push($test_results, [
        "name" => "Repairing SQL tables",
        "tables" => $repair_results
    ]);
}
function test_results(){
    global $test_results;
    echo json_encode($test_results);
    exit;
}
