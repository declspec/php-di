<?php
require_once(__DIR__ . '/injector.php');

interface IDependencyFactory {
    function provider($name, $provider);
    function factory($name, $factoryFn);
    function service($name, $className);
    function constant($name, $value);
};

class DependencyFactory {
    const PROVIDER_SUFFIX = "Provider";
    
    private $_providerInjector;
    private $_instanceInjector;
    
    public function __construct(IInjector $providerInjector, IInjector $instanceInjector) {
        $this->_providerInjector = $providerInjector;
        $this->_instanceInjector = $instanceInjector;
    }   
    
    public function provider($name, $provider) {
        if (is_string($provider))
            $provider = $this->_providerInjector->instantiate($provider);
        
        if (!is_object($provider) && !property_exists($provider, "_get") && !method_exists($provider, "_get"))
            throw new InvalidArgumentException('$provider is not a valid provider (must expose an invokable \'_get\' property or method)');

        return $this->_providerInjector->set($name.self::PROVIDER_SUFFIX, $provider);
    }
    
    public function factory($name, $factoryFn) {
        $factoryProvider = new stdClass();
        $factoryProvider->_get = $factoryFn;

        return $this->provider($name, $factoryProvider);
    }
    
    public function service($name, $className) {
        if ($className === null)
            $className = $name;
            
        $serviceProvider = new stdClass();
        $serviceProvider->_get = array("injector", function($injector) use(&$className) {
            return $injector->instantiate($className);
        });
        
        return $this->provider($name, $serviceProvider);   
    }
    
    public function constant($name, $value) {
        $this->_providerInjector->set($name, $value);
        $this->_instanceInjector->set($name, $value);   
    }
}
