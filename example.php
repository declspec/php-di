<?php
// include the required file
require("dm.php");

// instantiate the dependency manager
$dm = new DependencyManager();

// add a module 'main' with no dependencies
$main = $dm->module("main", array());

// create some constants and a basic logger function
$main->constant("ApplicationName", "php-di");

$main->factory("log", function($ApplicationName) {
	// A 'factory' returns the singleton that should be created as the 'log' instance.
    // in this case, a function which accepts a single parameter and echos it out along with
    // the injected ApplicationName variable.
    return function($obj) use($ApplicationName) {
        $prefix = "[$ApplicationName " . date('Y-m-d H:i:s O') . "] - "; 
        echo $prefix . print_r($obj, true) . "\n";
    };
});

// For the sake of the example, create a dummy run block to log some data
$main->run(function($log) {
    $log("Test Logging 1");
    $log(array("1","2","3"));
    $log(new stdClass());
});

// Calling 'createInjector' loads all the modules and returns an injector instance.
// all 'run' blocks will be executed when this happens. This is essentially where you bootstrap the injector
$dm->createInjector(array("main"));