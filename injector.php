<?php
interface IInjector {
    function invoke($expr, array $locals=array());
    function instantiate($type, $locals);
    function get($name);
    function has($servieName);
};

abstract class InjectorBase implements IInjector {
    private $_cache;
    private $_ref = array();
    
    protected abstract function resolve($name);
    
    public function __construct(array $cache=array()) {
        $this->_cache = $cache;
    }    
    
    public function invoke($expr, array $locals=array()) {
        $parameters = $this->annotate($expr);
        if (!is_callable($expr)) {
            $expr = $expr[count($expr)-1];
            if (!is_callable($expr))
                throw new InvalidArgumentException('$expr is not a valid invokable expression');
        }
        
        $args = array();
        foreach($parameters as $param) {
            $args[] = array_key_exists($locals, $param)
                ? $locals[$param]
                : $this->get($param);
        }
        
        return call_user_func_array($expr, $args);
    }
    
    public function get($serviceName) {
        if (array_key_exists($this->_cache, $serviceName)) {
            if ($this->_cache[$serviceName] === $this->_ref)
                throw new Exception("Circular dependency found when resolving '$serviceName'");
            return $this->_cache[$serviceName];
        }
        
        try {
            $this->_cache[$serviceName] = $this->_ref;
            return $this->_cache[$serviceName] = $this->resolve($serviceName);
        }
        catch(Exception $ex) {
            if ($this->_cache[$serviceName] === $this->_ref)
                unset($this->_cache[$serviceName]);
            throw $ex;
        }
    }
    
    protected function annotate($expr) {
        if (is_callable($expr)) {
            $reflection = is_array($expr)
                ? new ReflectionMethod($expr[0], $expr[1])
                : new ReflectionFunction($expr);
                
            return array_map(function($p) {
                return $p->name;
            }, $reflection->getParameters()); 
        }
        else if (is_array($expr)) {
            return array_slice($expr, 0, count($expr)-1);
        }
        else {
            throw new InvalidArgumentException('$expr is not a valid invokable expression');     
        } 
    }
};

class InstanceInjector extends InjectorBase {
    
};

class ProviderInjector extends InjectorBase {
    
};
?>