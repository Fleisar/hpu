<?php
$_CONFIG = [
    "allowed_methods" => [
        "GET", "POST", "PUT", "DELETE", "OPTION"
    ],
    "module_blacklist" => [
        "config", "main", "minfo", "sandbox", "stur", "SQLbb", "AX"
    ],
    "sql" => [
        "ip" => "127.0.0.1",
        "port" => 3306,
        "user" => "root",
        "password" => "",
        "database" => "general"
    ],
    "timeOffset" => 6,
    "db" => [
        "users" => "hpu.users",
        "messages" => "hpu.messages"
    ],
    "db_constructor" => [
        "users" => [
            "`id` INT NOT NULL AUTO_INCREMENT",
            "`username` TINYTEXT NOT NULL",
            "`password` TINYTEXT NOT NULL",
            "`session` TINYTEXT",
            "`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id`)"
        ],
        "messages" => [
            "`id` INT NOT NULL AUTO_INCREMENT",
            "`from` TINYTEXT NOT NULL",
            "`to` TINYTEXT NOT NULL",
            "`content` TINYTEXT NOT NULL",
            "`sent` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id`)"
        ]
    ]
];