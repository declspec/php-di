php-di: A PHP dependency injector
====================

The library is based heavily on the AngularJS dependency injector infrastructure and should be familiar
to anyone who has worked with Angular.

### Examples:

```php
<?php
// include the required file
require("php-di/dm.php");

// instantiate the dependency manager
$dm = new DependencyManager();

// add a module 'main' with no dependencies
$main = $dm->module("main", array());

// create some constants and a basic logger function
$main->constant("ApplicationName", "php-di");

// A factory is a lot more flexible than the 'service' alternative.
// 'service' is useful when you want to instantiate a class as a singleton,
// whereas factory allows you to return what object you like as the 'service',
// be it a class instance, closure, string or even NULL.
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
// this will be executed when the injector is created.
$main->run(function($log) {
    $log("Test Logging 1");
    $log(array("1","2","3"));
    $log(new stdClass());
});

// Calling 'createInjector' loads all the modules and returns an injector instance.
$dm->createInjector(array("main"));
```
