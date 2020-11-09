<?php


class minfo
{
    public $m = [];
    private $def = [
        "version" => 1.0,
        "success" => ["Successful."],
        "fail" => ["Unexpected error."]
    ];
    /*
     * $preregistered_methods = [
     *  {
     *      name => string,
     *      version => float, (optional, default = 1.0)
     *      success => array<string>, (optional, default = ["Successful."])
     *      fail => array<string>, (optional, default = ["Unexpected error."])
     *      additional => *object (optional, default = [])
     *  }, ...
     * ]
     * * - will add in api response via array_merge with main response
     */
    public function __construct(
        array $preregistered_method = []
    )
    {
        if($preregistered_method != [])
        {
            if(!isset($preregistered_method["name"])) $this->thrown_error("fatal", "Name of method cannot be null!");
            $new_method = array_merge($this->def, $preregistered_method);
            $this->m[$preregistered_method["name"]] = $new_method;
        }
        return $this->m;
    }
    public function get(
        string $method
    )
    {
        if (!isset($this->m[$method])) return false;
        return $this->m[$method];
    }
    public function create(
        array $method
    )
    {
        $def = [
            "success" => ["Successful."],
            "fail" => ["Unexpected error."]
        ];
        if(!isset($method["name"])) $this->thrown_error("fatal", "Name of method cannot be null!");
        $this->m[$method["name"]] = array_merge($def, $method);
        return $this->m;
    }
    public function remove(
        string $method
    )
    {
        if (!isset($this->m[$method])) return false;
        unset($this->m[$method]);
        return true;
    }
    private function thrown_error(string $type, string $text)
    {
        $text .= " ".json_encode(debug_backtrace());
        switch($type)
        {
            case "warning":
                echo "[Warning][minfo.php] ".$text;
                break;
            case "fatal":
            default:
                echo "[Fatal][minfo.php] ".$text;
                exit;
        }
    }
}