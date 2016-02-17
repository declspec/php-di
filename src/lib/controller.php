<?php
class ControllerProvider {
    const CONTROLLER_REGEXP = '/^(\S+)$/';
    
    private $_controllers = array();
    
    public function register($name, $constructor) {
        $this->_controllers[$name] = $constructor === null ? $name : $constructor;  
    }
    
    public function resolve($name, $throwOnError=true) {
        if (preg_match(self::CONTROLLER_REGEXP, $name) !== 1) {
            if ($throwOnError)
                throw new InvalidArgumentException("Badly formed controller string '$name'");
            return null;
        }
        
        if (!isset($this->_controllers[$name])) {
            if ($throwOnError)
                throw new InvalidArgumentException("'$name' is not a valid controller");
            return null;
        }
        
        return $this->_controllers[$name];
    }
    
    public function _get($injector) {
        return new ControllerFactory($this, $injector);
    }
};

class ControllerFactory {
    private $_injector;
    private $_provider;
    
    public function __construct($provider, $injector) {
        $this->_provider = $provider;
        $this->_injector = $injector;   
    }  
    
    public function exists($name) {
        return is_string($name) && $this->_provider->resolve($name, false) !== null;
    }
    
    public function create($constructor, array $locals=array()) {
        if (is_string($constructor)) {
            $constructor = $this->_provider->resolve($constructor, true);
        }
        
        return $this->_injector->instantiate($constructor, $locals);
    }
};