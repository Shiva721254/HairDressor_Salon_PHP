<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    /** @var array<string, class-string> */
    private array $bindings = [];

    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

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
