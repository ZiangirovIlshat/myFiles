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


    "/files/list" => [
        "GET" => "FilesController@listFile"
    ],
    "/files/get/{id}" => [
        "GET" => "FilesController@getFile"
    ],
    "/files/add" => [
        "POST" => "FilesController@addFile"
    ],
    "/files/rename" => [
        "PUT" => "FilesController@renameFile"
    ],
    "/files/remove/{id}" => [
        "DELETE" => "FilesController@removeFile"
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