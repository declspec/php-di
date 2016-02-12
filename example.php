<?php
// include the required file
require("dm.php");

// instantiate the dependency manager
$dm = new DependencyManager();

// add a module 'main' with no dependencies
$main = $dm->module("main", array());

// create some constants and a basic logger function
$main->constant("ApplicationName", "php-di");

// A 'factory' returns the singleton that should be created as the 'log' instance.
// in this case, a function which accepts a single parameter and echos it out along with
// the injected ApplicationName variable.
$main->factory("log", function($ApplicationName) {
    return function($obj) use($ApplicationName) {
        $prefix = "[$ApplicationName " . date('Y-m-d H:i:s O') . "] - "; 
        echo $prefix . print_r($obj, true) . "\n";
    };
});

// Alternatively, create a simple logger class for greater functionality
class Logger {
    private $_appName;
    
    public function __construct($ApplicationName) {
       $this->_appName = $ApplicationName;
    }
    
    public function log($obj) {
        echo print_r($obj, true) . "\n";
    }
    
    public function logPretty($obj) {
        $prefix = "[{$this->_appName} " . date('Y-m-d H:i:s O') . "] - "; 
        echo $prefix . print_r($obj, true) . "\n";
    }   
};

$main->service("logger", "Logger");

// For the sake of the example, create a dummy run block to log some data
$main->run(function($log, $logger) {
    $log("Test Logging 1");
    $log(array("1","2","3"));
    $log(new stdClass());
    
    $logger->log("plain logging");
    $logger->logPretty("pretty logging");
});

// Calling 'createInjector' loads all the modules and returns an injector instance.
// all 'run' blocks will be executed when this happens. This is essentially where you bootstrap the injector
$dm->createInjector(array("main"));