<?php
require("di.php");

$di = new DependencyInjector();

$m1 = $di->module("module1", array());

$m1->factory("log", function() {
    return function($arg) {
        print_r($arg);   
    };
});

$m2 = $di->module("module2", array("module1"));

$m2->run(function($log) {
    $log("foo");
    $log(1);
    $log($log);
});


$injector = $di->createInjector(array("module2"));

?>