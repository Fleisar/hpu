<?php

//require "sandbox.php";
//exit;

require "stur.php";
require "SQLbb.php";
require "config.php";

session_start();
$api = new stur();
$_mi = $api->mi;
$sql = new SQLbb(
    $_CONFIG["sql"]["ip"],
    $_CONFIG["sql"]["user"],
    $_CONFIG["sql"]["password"],
    $_CONFIG["sql"]["database"],
    $_CONFIG["sql"]["port"]
);
$path = path();
$module = explode("/", $path)[0];
$get = array_slice(explode("/", $path."/"), 1);
$json = json_decode(file_get_contents("php://input"));

$_mi->create([
    "name" => "main",
    "description" => "REST Emulator",
    "version" => 1.0,
    "success" => [
        "Successful."
    ],
    "fail" => [
        "Unexpected error.",
        "This module doesn't exists."
    ]
]);

if(!file_exists($module.".php") || array_search($module, $_CONFIG["module_blacklist"]) !== false)
    $api->send(false,1, "main", ["method"=>$module]);
require $module.".php";

function path(){
    $del = $_SERVER["SCRIPT_FILENAME"];
    $pic = explode("/", $_SERVER["REQUEST_URI"]);
    $other = explode($pic[1], $del)[sizeof(explode($pic[1], $del))-1];
    $step = 0;
    while($del != $other){
        $other = explode($pic[1+$step], $del)[sizeof(explode($pic[1+$step], $del))-1];
        $step++;
    }
    return join("/", array_slice($pic, $step));
}
