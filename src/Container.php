<?php

namespace Php22;

class Container
{
    private static $instance = null;
    private $bindings = [];
    private $instances = [];

    /**
     * Get the singleton instance of the container.
     *
     * @return Container
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a binding in the container.
     *
     * @param string $key
     * @param callable|object $resolver
     */
    public function bind(string $key, $resolver)
    {
        $this->bindings[$key] = $resolver;
    }

    /**
     * Resolve a binding from the container.
     *
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function resolve(string $key)
    {
        // Return existing instance if already resolved
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (isset($this->bindings[$key])) {
            $resolver = $this->bindings[$key];
            $object = is_callable($resolver) ? $resolver($this) : $resolver;

            // Cache the resolved instance
            $this->instances[$key] = $object;

            return $object;
        }

        throw new \Exception("No binding found for key '{$key}'");
    }
}
