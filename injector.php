<?php
require_once("internal-injector.php");
require_once("module.php");

interface IProvideFactory {
    function provider($name, $provider);
    function factory($name, $factory, $enforceReturn);
    function service($name, $className);
    function constant($name, $value);
}

class Injector implements IInjector, IProvideFactory {
    const PROVIDER_SUFFIX = "Provider";
    
    private $_providerInjector;
    private $_providerCache;
    
    private $_instanceInjector;
    private $_instanceCache;
    
    public function __construct(IModuleProvider $moduleProvider, array $modules) {
        $this->_providerCache = array("provide" => $this);
        $this->_instanceCache = array();
        
        $this->_providerInjector = new InternalInjector($this->_providerCache, function($name) {
            throw new RuntimeException("Unknown provider '$name' encountered");
        });
        
        $this->_instanceInjector = new InternalInjector($this->_instanceCache, function($name) {
            $provider = $this->_providerInjector->get($name . self::PROVIDER_SUFFIX);
            // If '_get' is a property of the class then invoke it as-is, otherwise invoke it as a class method
            $invokable = property_exists($provider, "_get")
                ? $provider->_get
                : array($provider, "_get");
            
            return $this->_instanceInjector->invoke($invokable);
        });
        
        foreach($this->loadModules($moduleProvider, $modules) as $invokable) {
            if ($invokable)
                $this->_instanceInjector->invoke($invokable);   
        }
    }  

    // Implementing IProvideFactory
    public function provider($name, $provider) {
        if (is_string($provider))
            $provider = $this->_providerInjector->instantiate($provider);
        
        if (!property_exists($provider, "_get") && !method_exists($provider, "_get"))
            throw new InvalidArgumentException('$provider is not a valid provider (must expose an invokable \'_get\' property or method)');

        return $this->_providerCache[$name . self::PROVIDER_SUFFIX] = $provider;
    }
    
    public function factory($name, $factoryFn, $enforceReturn) {
        $factoryProvider = new stdClass();
        $factoryProvider->_get = $factoryFn;
        
        return $this->provider($name, $factoryProvider);
    }
    
    public function service($name, $className) {
        $serviceProvider = new stdClass();
        $serviceProvider->_get = array("injector", function($injector) use($className) {
            return $injector->instantiate($className);
        });
        
        return $this->factory($name, $serviceProvider);   
    }
    
    public function constant($name, $value) {
        $this->_providerCache[$name] = $this->_instanceCache[$name] = $value;   
    }
    
    // Implementing IInjector
    public function invoke($expr, array $locals=array()) {
        return $this->_instanceInjector->invoke($expr, $locals);   
    }
    
    public function instantiate(string $className, array $locals=array()) {
        return $this->_instanceInjector->instantiate($className, $locals);    
    }
    
    public function get($name) {
        return $this->_instanceInjector->get($name);   
    }
    
    // Internal methods
    private function runQueue(array $queue) {
        foreach($queue as $args) {
            $provider = $this->_providerInjector->get($args[0]);
            call_user_func_array(array($provider, $args[1]), $args[2]);  
        }   
    }
    
    private function loadModules(IModuleProvider $moduleProvider, array $modules, array $cached=array()) {
        $blocks = array();
        
        foreach($modules as $module) {            
            if (!is_string($module))
                $blocks[] = $this->_providerInjector->invoke($module);
            else if (!array_key_exists($module, $cached)) {
                $cached[$module] = true;
                
                $mod = $moduleProvider->module($module);
                $blocks = array_merge($blocks, $this->loadModules($moduleProvider, $mod->getDependencies(), $cached), $mod->_runBlocks);
                $this->runQueue($mod->_invokeQueue);
                $this->runQueue($mod->_configBlocks);
            }
        }   
        
        return $blocks;
    }
};
?>