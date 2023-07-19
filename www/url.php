<?php 

// список обрабатываемых запросов
$urlList = [
    "/" => [
        "GET" => "Controller@home",
    ],


    "/users/list" => [
        "GET" => "UserController@list",
    ],
    "/users/get/{id}" => [
        "GET" => "UserController@getUser"
    ],
    "/users/update" => [
        "PUT" => "UserController@update",
    ],
    "/users/login" => [
        "POST" => "UserController@login",
    ],
    "/users/logout" => [
        "GET" => "UserController@logout",
    ],
    "/users/reset_password/{email}" => [
        "GET" => "UserController@resetPassword",
    ],


    "/admin/users/list" => [
        "GET" => "AdminController@list"
    ],
    "/admin/users/get/{id}" => [
        "GET" => "AdminController@getUser"
    ],
    "/admin/users/delete/{id}" => [
        "DELETE" => "AdminController@deleteUser"
    ],
    "/admin/users/update/{id}" => [
        "PUT" => "AdminController@update"
    ],
];