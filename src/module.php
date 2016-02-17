<?php
interface IModule {
    function provider($name, $provider);
    function factory($name, $factory);
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
    
    public function factory($name, $factory) {
        $this->_invokeQueue[] = array("provide", "factory", array($name, $factory));
        return $this;
    }
    
    public function service($name, $className=null) {
        $this->_invokeQueue[] = array("provide", "service", array($name, $className));
        return $this;
    }
    
    public function constant($name, $value) {
        array_unshift($this->_invokeQueue, array("provide", "constant", array($name, $value)));   
        return $this;
    }
    
    public function controller($name, $className=null) {
        $this->_invokeQueue[] = array("controllerProvider", "register", array($name, $className));
        return $this;   
    }
};
?>