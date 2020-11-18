<?php
$_mi->create([
    "name" => "messages",
    "version" => 1.0,
    "success" => [
        "Success."
    ],
    "fail" => [
        "Unexpected error.",
        "Unsupported method.",
        "Too few or too more arguments.",
        "Unable to log in.",
        "Receiver not found.",
        "Too short or too long message.",
        "AX failed."
    ]
]);
if(!isset($json["username_AX"]) || !isset($json["session_AX"]))
    $api->send(false,6,"messages");
if(!userAX($json["username_AX"], $json["session_AX"]))
    $api->send(false,3,"messages");
switch($_SERVER["REQUEST_METHOD"]){
    case "GET": //{to,start_from,end_on,limit
        if(!isset($json["to"]) || !isset($json["start_from"]))
            $api->send(false,2,"messages");
        $start = (int) $json["start_from"];
        $from = $sql->sql->real_escape_string($json["username_AX"]);
        $to = $sql->sql->real_escape_string($json["to"]);
        $table = $sql->sql->real_escape_string($_CONFIG["db"]["messages"]);
        if(isset($json["end_on"])){
            $end = (int) $json["end_on"];
            $selected = $sql->sql->query("
                SELECT * 
                FROM `${table}` 
                WHERE 
                    (`from`='${from}' OR `to`='${from}') AND 
                    (`from`='${to}' OR `to`='${to}') AND 
                    TIMESTAMP(sent) BETWEEN from_unixtime(${start}) AND from_unixtime(${end})
            ");
        }else{
            $selected = $sql->sql->query("
                SELECT * 
                FROM `${table}` 
                WHERE 
                    (`from`='${from}' OR `to`='${from}') AND 
                    (`from`='${to}' OR `to`='${to}') AND 
                    TIMESTAMP(sent) > from_unixtime(${start})
            ");
        }
        $messages = [];
        while($message = $selected->fetch_assoc()) $messages[sizeof($messages)] = $message;
        $api->send(true,0,"messages",["messages"=>$messages]);
        break;
    case "PUT": //{to,text}
        $find = $sql->select($_CONFIG["db"]["users"],["username"=>$json["to"]]);
        if(!isset($json["to"]) || !isset($json["text"]))
            $api->send(false,2,"messages");
        if($find->num_rows == 0)
            $api->send(false,4,"messages");
        if(strlen($json["text"]) == 0 || strlen($json["text"]) > 300)
            $api->send(false,5,"messages");
        $sql->insert($_CONFIG["db"]["messages"],[
            "from" => $json["username_AX"],
            "to" => $json["to"],
            "content" => $sql->sql->real_escape_string($json["text"])
        ]);
        $api->send(true,0,"messages");
        break;
    default:
        $api->send(false,1,"messages");
        break;
}
