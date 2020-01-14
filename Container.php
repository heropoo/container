<?php
/**
 * Datetime: 2020/01/4 14:08
 */

namespace Moon\Container;

use Closure;

class Container
{
    protected $bindings = [];
    protected $instances = [];

    public function add($name, $object)
    {
        $this->instance($name, $object);
    }

    public function get($name, $force = false)
    {
        return $this->make($name, $force);
    }

    /**
     * Bind to Container
     * @param string $name
     * @param Closure $closure
     * @param bool $force Bind as Single Instance
     */
    public function bind($name, Closure $closure, $force = false)
    {
        $this->bindings[$name] = compact('closure', $force);
    }

    /**
     * Register Single Instance
     * @param string $name
     * @param Closure $closure
     */
    public function single($name, Closure $closure)
    {
        $this->bind($name, $closure, true);
    }

    /**
     * Set Instance to Container
     * @param string $name
     * @param Object $object
     */
    public function instance($name, $object)
    {
        $this->instances[$name] = $object;
    }

    /**
     * Make or get a instance
     * @param string $name
     * @param bool $force
     * @return Object
     * @throws Exception
     */
    public function make($name, $force = false)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $closure = $this->getClosure($name);
        $object = $this->build($closure);

        if (isset($this->bindings[$name]['force']) && $this->bindings[$name]['force'] || $force) {
            $this->instances[$name] = $object;
        }
        return $object;
    }

    /**
     * @param string $function
     * @param array $params
     * @return mixed
     */
    public function callFunction($function, $params = [])
    {
        $reflectionFunction = new \ReflectionFunction($function);
        $args = $this->getDependencies($reflectionFunction->getParameters(), $params);
        return $reflectionFunction->invokeArgs($args);
    }

    /**
     * @param string $class
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public function callMethod($class, $method, $params = [])
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);
        $args = $this->getDependencies($reflectionMethod->getParameters(), $params);
        return $reflectionMethod->invokeArgs($this->build($class), $args);
    }

    protected function getClosure($name)
    {
        return isset($this->bindings[$name]) ? $this->bindings[$name]['closure'] : $name;
    }

    protected function build($className)
    {
        if ($className instanceof Closure) {
            return $className($this);
        }

        $ref = new \ReflectionClass($className);
        // Check class is instantiable? Not abstract or interface and trait
        if (!$ref->isInstantiable()) {
            throw new Exception("Class $className is not instantiable.");
        }

        $constructor = $ref->getConstructor();
        if (is_null($constructor)) {
            return new $className;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->getDependencies($parameters);

        return $ref->newInstanceArgs($dependencies);
    }

    protected function getDependencies($parameters, $nonClassParams = [])
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            /** @var \ReflectionParameter $parameter */
            $dependency = $parameter->getClass();
            if (is_null($dependency)) {
                if (isset($nonClassParams[$parameter->getName()])) {
                    $dependencies[] = $nonClassParams[$parameter->getName()];
                } else {
                    $dependencies[] = $this->resolveNonClass($parameter);
                }
            } else {
                if (isset($this->instances[$dependency->getName()])) {
                    $dependencies[] = $this->instances[$dependency->getName()];
                } else {
                    $dependencies[] = $this->build($dependency->getName());
                }
            }
        }
        return $dependencies;
    }

    protected function resolveNonClass(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        if ($parameter->getDeclaringClass()) {
            $msg = "Constructor parameter '\${$parameter->getName()}' for'
            .' class '{$parameter->getDeclaringClass()->getName()}' does not has a default value.";
        } else {
            $msg = "Closure parameter '\${$parameter->getName()} does not has a default value.";
        }
        throw new Exception($msg);
    }
}