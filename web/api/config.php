<?php
$_CONFIG = [
    "allowed_methods" => [
        "GET", "POST", "PUT", "DELETE", "OPTION"
    ],
    "module_blacklist" => [
        "config", "main", "minfo", "sandbox", "stur", "SQLbb"
    ],
    "sql" => [
        "ip" => "127.0.0.1",
        "port" => 3306,
        "user" => "root",
        "password" => "",
        "database" => "general"
    ],
    "db" => [
        "users" => "hpu.users"
    ],
    "db_constructor" => [
        "users" => [
            "`id` INT NOT NULL AUTO_INCREMENT",
            "`username` TINYTEXT NOT NULL",
            "`password` TINYTEXT NOT NULL",
            "`session` TINYTEXT",
            "`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id`)"
        ]
    ]
];