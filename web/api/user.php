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
        "This method is not supported."
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
    case "POST":
        if(!isset($_POST["username"]) || !isset($_POST["password"]))
            $api->send(false,1,"user");
        if(
            !preg_match("/\w{3,16}/", $_POST["username"]) ||
            !preg_match("/\w{8}/", $_POST["password"])
        ){
            $api->send(false,4,"user");
        }
        $user_search = $sql->select($_CONFIG["db"]["users"],["username"=>$_POST["username"]]);
        if($user_search->num_rows !== 0)
            $api->send(false,3,"user");
        $user = $sql->insert(
            $_CONFIG["db"]["users"],
            ["username"=>$_POST["username"],"password"=>md5($_POST["password"])]
        );
        $api->send(true,0,"user",[
            "user" => [
                "id" => $sql->sql->insert_id,
                "username" => $sql->sql->real_escape_string($_POST["username"])
            ]
        ]);
        break;
    case "PUT":
    case "DELETE":
        $api->send(false,5,"user");
        break;

}