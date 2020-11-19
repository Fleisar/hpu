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
switch($_SERVER["REQUEST_METHOD"]){
    case "GET": //{to,start_from,end_on,limit
        if(!isset($head["username_AX"]) || !isset($head["session_AX"]))
            $api->send(false,6,"messages");
        if(!userAX($head["username_AX"], $head["session_AX"]))
            $api->send(false,3,"messages");
        if(!isset($head["to"]) || !isset($head["start_from"]))
            $api->send(false,2,"messages");
        $start = (int) $head["start_from"];
        $from = $sql->sql->real_escape_string($head["username_AX"]);
        $to = $sql->sql->real_escape_string($head["to"]);
        $table = $sql->sql->real_escape_string($_CONFIG["db"]["messages"]);
        if(isset($head["end_on"])){
            $end = (int) $head["end_on"];
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
        while($message = $selected->fetch_assoc()){
            $message["sent"] = toUnix($message["sent"]);
            $messages[sizeof($messages)] = $message;
        }
        $api->send(true,0,"messages",["messages"=>$messages]);
        break;
    case "PUT": //{to,text}
        if(!isset($json["username_AX"]) || !isset($json["session_AX"]))
            $api->send(false,6,"messages");
        if(!userAX($json["username_AX"], $json["session_AX"]))
            $api->send(false,3,"messages");
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
