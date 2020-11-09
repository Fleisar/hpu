<?php
require "minfo.php";

class stur
{
    private $queue = [];
    public $mi = null;
    public $additional = [];
    public function __construct(array $preregistered_methods = [], array $additional = [])
    {
        $this->mi = new minfo(array_merge($preregistered_methods,[
            "name" => "stur",
            "title" => "StUR",
            "description" => "Server to User Response",
            "version" => 1.0,
            "fail" => [
                "Unexpected error.",
                "Using reserved key in constructor.",
                "Unable to find module."
            ]
        ]));
        $this->additional = array_merge([
            "debug_time" => time()
        ], $additional);
    }
    public function send(bool $state, int $code, string $method, array $additional = [])
    {
        $this->add($state, $code, $method, $additional);
        $this->close();
    }
    public function add(bool $state, int $code, string $method, array $additional = [])
    {
        $mtd = $this->mi->get($method);
        if($mtd == false) {
            $this->send(false, 2, "stur", ["module" => $method]);
        }
        $this->queue[sizeof($this->queue)] = $this->constructor(
            $state,
            $code,
            isset($mtd[$state?"success":"fail"][$code])?
                $mtd[$state?"success":"fail"][$code]:
                $mtd[$state?"success":"fail"][0],
            array_diff_key($mtd,["success"=>[],"fail"=>[]]),
            $additional,
            $this->additional
        );
    }
    public function close()
    {
        header("Content-Type: application/json");
        if(sizeof($this->queue) < 2){
            echo json_encode($this->queue[0]);
        } else {
            echo json_encode($this->queue);
        }
        exit;
    }
    private function constructor(bool $sate, int $code, string $description, array $module, array $output, array $debug)
    {
        $reserved_keys = ["state","code","description","module"];
        $all_keys = array_keys(array_merge($output, $debug));
        foreach($reserved_keys as $key){
            if(key_exists($key, $all_keys))
                $this->send(false, 1, "stur", [
                    "reserved_keys" => $reserved_keys,
                    "using_keys" => $all_keys,
                    "error_in" => $key
                ]);
        }
        return array_merge([
            "state" => $sate,
            "code" => $code,
            "description" => $description,
            "module" => $module
        ], $output, $debug);
    }
}