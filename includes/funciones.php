<?php

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
};

function s($html) : string {
    $s = htmlspecialchars($html);
    return $s;
};

function iniciar_sesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
};

function autenticado(){
    iniciar_sesion();
    
    if(isset($_SESSION["login"])){
        header("Location: /localhost");
        exit;
    };
};

function no_autenticado(){
    iniciar_sesion();
    
    if(!isset($_SESSION["login"])){
        header("Location: /login");
        exit;
    };
};