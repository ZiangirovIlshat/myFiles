<?php 

// список обрабатываемых запросов
$urlList = [
    "/" => [
        "GET" => "Controller@home",
    ],

    '/users/list ' => [
        "GET" => "UserController@list",
    ],

    "/users/list" => [
        "GET" => "UserController@list",
    ],
    "/users/get/{id}" => [
        "GET" => "UserController@getUser"
    ],
    "/user/update" => [
        "PUT" => "UserController@update",
    ],
    "/users/login" => [
        "POST" => "UserController@login",
    ],
    "/users/logout" => [
        "GET" => "UserController@logout",
    ],
    "/users/reset_password" => [
        "GET" => "UserController@resetPassword",
    ],


    "admin/users/list" => [
        "GET" => "AdminController@list"
    ],
    "/admin/users/get/{id}" => [
        "GET" => "AdminController@delete"
    ],
    "/admin/users/delete/{id}" => [
        "GET" => "AdminController@deleteUser"
    ],
    "/admin/users/update/{id}" => [
        "GET" => "AdminController@update"
    ],


    "/files/list" => [
        "GET" => "FilesController@list"
    ],
    "/files/get/{id}" => [
        "GET" => "FilesController@get"
    ],
    "/files/add" => [
        "POST" => "FilesController@addFile"
    ],
    "/files/rename" => [
        "PUT" => "FilesController@rename"
    ],
    "/files/remove/{id}" => [
        "DELETE" => "FilesController@remove"
    ],
    "/directories/add" => [
        "POST" => "FilesController@addDirectories"
    ],
    "/directories/rename" => [
        "PUT" => "FilesController@renameDirectories"
    ],
    "/directories/get/{id" => [
        "GET" => "FilesController@getDirectories"
    ],
    "/directories/delete/{id}" => [
        "DELETE" => "FilesController@deleteDirectories"
    ],
];
