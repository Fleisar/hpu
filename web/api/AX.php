<?php
function userAX(string $username, string $session){
    global $_CONFIG, $sql;
    $time = explode("/",$session);
    if(sizeof($time) == 1)
        return false;
    $time = $time[1];
    $user = $sql->select($_CONFIG["db"]["users"],["username"=>$username,"session"=>$session]);
    if($user == null || $user->num_rows == 0)
        return false;
    $user = $user->fetch_assoc();
    return $session == md5($username.":".$user["password"].":".$time)."/".$time;
}