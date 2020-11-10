<?php
$_mi->create([
    "name" => "user",
    "version" => 1.0,
    "success" => [
        "Successful."
    ],
    "fail" => [
        "Unexpected error.",
        "Too few or too more arguments.",
        "User not found.",
        "This username is already taken.",
        "Input arguments are invalid.",
        "This method is not supported.",
        "This action is not supported.",
        "Old password is invalid.",
        "Password is invalid."
    ]
]);

switch ($_SERVER["REQUEST_METHOD"])
{
    case "GET":
        if(sizeof($get) !== 2)
            $api->send(false,1,"user",["number_of_arguments"=>sizeof($get)]);
        $user = $get[0];
        $search_results = $sql->select(
            $_CONFIG["db"]["users"],["id"=>$user,"username"=>$user],
            "|", "`id`,`username`,`created`"
        );
        if($search_results->num_rows > 0){
            $api->send(true,0,"user",["user"=>$search_results->fetch_assoc()]);
        }else{
            $api->send(false,2,"user",["search_user"=>$user]);
        }
        break;
    case "PUT":
        if(!isset($json["username"]) || !isset($json["password"]))
            $api->send(false,1,"user");
        if(
            !preg_match("/\w{3,16}/", $json["username"]) ||
            !preg_match("/\w{8}/", $json["password"])
        )
            $api->send(false,4,"user");
        $user_search = $sql->select($_CONFIG["db"]["users"],["username"=>$json["username"]]);
        if($user_search->num_rows !== 0)
            $api->send(false,3,"user");
        $user = $sql->insert(
            $_CONFIG["db"]["users"],
            ["username"=>$json["username"],"password"=>md5($json["password"])]
        );
        $api->send(true,0,"user",[
            "user" => [
                "id" => $sql->sql->insert_id,
                "username" => $sql->sql->real_escape_string($json["username"])
            ]
        ]);
        break;
    case "POST":
        if(sizeof($get) !== 3)
            $api->send(false,1,"user",["number_of_arguments"=>sizeof($get)]);
        $user = $get[0];
        $action = $get[1];
        $search_results = $sql->select(
            $_CONFIG["db"]["users"],["id"=>$user,"username"=>$user],
            "|", "`id`,`username`,`created`"
        );
        if($search_results->num_rows == 0)
            $api->send(false,2,"user",["search_user"=>$user]);
        $userS = $user_search->fetch_assoc();
        switch ($action) {
            case "username":
                if(!isset($json["username"]) || !isset($json["password"]))
                    $api->send(false,1,"user");
                if(
                    !preg_match("/\w{3,16}/", $json["username"]) ||
                    !preg_match("/\w{8}/", $json["password"])
                )
                    $api->send(false,4,"user");
                if($userS["password"] !== md5($json["password"]))
                    $api->send(false,8,"user");
                $other_user = $sql->select($_CONFIG["db"]["users"], ["username"=>$json["username"]]);
                if($other_user->num_rows !== 0)
                    $api->send(false,4,"user");
                $sql->update($_CONFIG["db"]["users"],["username"=>$json["username"]], ["username"=>$user]);
                $api->send(true,0,"user");
                break;
            case "password":
                if(!isset($json["old_password"]) || !isset($json["new_password"]))
                    $api->send(false,1,"user");
                if(
                    !preg_match("/\w{8}/", $json["old_password"]) ||
                    !preg_match("/\w{8}/", $json["new_password"])
                ){
                    $api->send(false,4,"user");
                }
                if($userS["password"] !== md5($json["old_password"]))
                    $api->send(false,7,"user");
                $sql->update(
                    $_CONFIG["db"]["users"],
                    ["password"=>md5($json["new_password"])],
                    ["id"=>$user,"username"=>$user], "|"
                );
                $api->send(true,0,"user");
                break;
            default:
                $api->send(false,6,"user");
        }
        break;
    case "DELETE":
        $api->send(false,5,"user");
        break;

}