<?php
interface IInjector {
    function invoke($expr, array $locals);
    function instantiate(string $className, array $locals);
    function get($name);
};

class InternalInjector implements IInjector {
    // Used as a reference to detect circular dependencies.
    private static $_ref = array();

    protected $_cache;
    protected $_resolver;

    public function __construct(array &$cache, callable $resolver) {
        $this->_cache = &$cache;
        $this->_resolver = $resolver;
        
        // Always provide a reference to the injector
        $this->_cache["injector"] = $this;
    }    
    
    public function invoke($expr, array $locals=array()) {
        $parameters = $this->annotate($expr);
        
        if (!is_callable($expr)) {
            $expr = $expr[count($expr)-1];
            if (!is_callable($expr))
                throw new InvalidArgumentException('$expr is not a valid invokable expression');
        }
        
        return call_user_func_array($expr, $this->resolveParameters($parameters, $locals));
    }
    
    public function instantiate(string $className, array $locals=array()) {
        if (!is_class($className))
            throw new InvalidArgumentException("'$className' is not a valid class");
        
        $class = new ReflectionClass($className);
        $constructor = $class->getConstructor();
        
        if ($constructor === null)
            return $class->newInstanceWithoutConstructor();
            
        $parameters = array_map(function($p) { 
            return $p->name;
        }, $constructor->getParameters());
        
        return $constructor->newInstanceArgs($this->resolveParameters($parameters, $locals));
    }
    
    public function get($serviceName) {
        if (array_key_exists($serviceName, $this->_cache)) {
            if ($this->_cache[$serviceName] === self::$_ref)
                throw new Exception("Circular dependency found when resolving '$serviceName'");
            return $this->_cache[$serviceName];
        }
        
        try {
            $this->_cache[$serviceName] = self::$_ref;
            return $this->_cache[$serviceName] = call_user_func($this->_resolver, $serviceName);
        }
        catch(Exception $ex) {
            if ($this->_cache[$serviceName] === self::$_ref)
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
    
    protected function resolveParameters(array $params, array $locals) {
        return array_map(function($p) use(&$locals) {
            return array_key_exists($p, $locals) ? $locals[$p] : $this->get($p);
        }, $params);
    }
};
?>