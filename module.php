<?php
interface IModuleProvider {
    function module($module, array $dependencies);
};

interface IModule {
    function provider($name, $provider);
    function factory($name, $factory, $enforceReturn);
    function service($name, $className);
    function constant($name, $value);
    function controller($name, $className);
    
    function config($configFn);
    function run($configFn);
    
    function getDependencies();
    function getName();
};

class Module implements IModule {
    private $_dependencies;
    private $_name;
    
    // internal variables
    public $_invokeQueue = array();
    public $_configBlocks = array();
    public $_runBlocks = array();
    
    public function getName() { return $this->_name; }
    public function getDependencies() { return $this->_dependencies; }
    
    public function __construct($name, array $dependencies=array()) {
        $this->_name = $name;
        $this->_dependencies = $dependencies;
    }   
    
    public function config($configBlock) {
        $this->_configBlocks[] = array("injector", "invoke", array($configBlock));
        return $this;   
    }
    
    public function run($runBlock) {
        $this->_runBlocks[] = $runBlock;
        return $this;
    }
    
    public function provider($name, $provider) {
        $this->_invokeQueue[] = array("provide", "provider", array($name, $provider));
        return $this;
    }
    
    public function factory($name, $factory, $enforceReturn=true) {
        $this->_invokeQueue[] = array("provide", "factory", array($name, $factory, $enforceReturn));
        return $this;
    }
    
    public function service($name, $className) {
        $this->_invokeQueue[] = array("provide", "service", array($name, $className));
        return $this;
    }
    
    public function constant($name, $value) {
        array_unshift($this->_invokeQueue, array("provide", "constant", array($name, $className)));   
        return $this;
    }
    
    public function controller($name, $className) {
        $this->_invokeQueue[] = array("controller", "register", array($name, $className));
        return $this;   
    }
};
?>