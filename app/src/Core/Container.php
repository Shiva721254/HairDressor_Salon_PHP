<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Simple Service Container for Dependency Injection
 * 
 * Provides automatic resolution of class dependencies using reflection.
 * Supports binding abstractions to concrete implementations.
 * 
 * Example:
 *   $container->bind(UserRepositoryInterface::class, UserRepository::class);
 *   $user = $container->make(UserRepository::class);
 */
final class Container
{
    /** @var array<string, class-string> */
    private array $bindings = [];

    /**
     * Bind an abstract class/interface to a concrete implementation
     * 
     * @param string $abstract The interface or abstract class name
     * @param string $concrete The concrete class name that implements it
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Create an instance of a class, automatically resolving constructor dependencies
     * 
     * Uses reflection to inspect constructor parameters and recursively resolves
     * required dependencies from the container.
     * 
     * @param string $id The class name to instantiate
     * @return object A new instance of the class
     * @throws RuntimeException If class not found or cannot be resolved
     */
    public function make(string $id): object
    {
        $concrete = $this->bindings[$id] ?? $id;

        if (!class_exists($concrete)) {
            throw new RuntimeException('Class not found: ' . $concrete);
        }

        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException('Class is not instantiable: ' . $concrete);
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return new $concrete();
        }

        $deps = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    $deps[] = $param->getDefaultValue();
                    continue;
                }

                throw new RuntimeException(
                    'Cannot resolve constructor parameter $' . $param->getName() . ' for ' . $concrete
                );
            }

            $deps[] = $this->make($type->getName());
        }

        return $reflection->newInstanceArgs($deps);
    }
}
