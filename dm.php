<?php
require_once(__DIR__ . "/injector.php");
require_once(__DIR__ . "/factory.php");
require_once(__DIR__ . "/module.php");

class DependencyManager {
    private $_modules = array();
    
    public function __construct() {
        // Create the core 'di' module
        $di = $this->module("di", array());   
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
        if (!$modules) 
            throw new InvalidArgumentException("Must specificy at least one module in order to create a dependency injector");
        
        // Force load the core module into all injectors
        array_unshift($modules, "di");
        
        // Create the injectors.
        $providerInjector = new Injector(function($name) {
            throw new RuntimeException("Unknown provider '$name' encountered");
        });
        
        $instanceInjector = new Injector(function($name, $injector) use(&$providerInjector) {
            $provider = $providerInjector->get($name.DependencyFactory::PROVIDER_SUFFIX);
            
            // If '_get' is a property of the class then invoke it as-is, otherwise invoke it as a class method
            $invokable = property_exists($provider, "_get")
                ? $provider->_get
                : array($provider, "_get");
            
            return $injector->invoke($invokable);    
        });
        
        // Manually create the 'provide' DependencyFactory
        $dependencyFactory = new DependencyFactory($providerInjector, $instanceInjector);
        $providerInjector->set("provide", $dependencyFactory);
        
        // Load all the required modules, then run their blocks
        foreach($this->loadModules($modules, $providerInjector) as $block) {
            if ($block) 
                $instanceInjector->invoke($block);   
        }
        
        // Give back the instance injector
        return $instanceInjector;
    }
    
    private function loadModules(array $modules, IInjector $providerInjector, array &$cached=null) {
        if ($cached === null)
            $cached = array();
            
        $blocks = array();
        
        foreach($modules as $module) {
            if (!is_string($module))
                $blocks[] = $providerInjector->invoke($module);
            else if (!array_key_exists($module, $cached)) {
                $cached[$module] = true;
                
                $mod = $this->module($module);
                $blocks = array_merge($blocks, $this->loadModules($mod->getDependencies(), $providerInjector, $cached), $mod->_runBlocks);
                $this->runQueue($mod->_invokeQueue, $providerInjector);
                $this->runQueue($mod->_configBlocks, $providerInjector);
            }
        }
        
        return $blocks;
    }
    
    private function runQueue($queue, $injector) {
        foreach($queue as $args) {
            $obj = $injector->get($args[0]);
            call_user_func_array(array($obj, $args[1]), $args[2]); 
        }
    }
}
?>