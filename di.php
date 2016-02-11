<?php
require_once("module.php");
require_once("injector.php");

require_once("lib/controller.php");

class DependencyInjector implements IModuleProvider {
    private $_modules = array();
    
    public function __construct() {
        $di = $this->module("di", array());
        $di->provider("controller", new ControllerProvider());
    }
    
    public function module($name, array $dependencies=null) {
        if (isset($this->_modules[$name]) && $dependencies !== null)
            $this->_modules[$name] = null;
        else if (isset($this->_modules[$name]))
            return $this->_modules[$name];
            
        if ($dependencies === null) {
            throw new InvalidArgumentException("Module '$name' is not available. You either misspelled the module name or forgot to load it. " .
                "If you are registering a module, ensure that you specify the dependencies as the second argument");   
        }
        
        return $this->_modules[$name] = new Module($name, $dependencies);
    }
    
    public function createInjector(array $modules) {
        array_unshift($modules, "di");
        return new Injector($this, $modules);
    }
};
