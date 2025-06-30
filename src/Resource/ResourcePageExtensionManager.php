<?php

namespace Branzia\Bootstrap\Resource;

class ResourcePageExtensionManager
{
    /** @var array<string, array<callable>> */
    protected static array $pageCallbacks = [];

    public static function register(string $resourceClass, callable $callback): void
    {
        static::$pageCallbacks[$resourceClass][] = $callback;
    }

    public static function apply(array $basePages, string $resourceClass): array
    {
        foreach (static::$pageCallbacks[$resourceClass] ?? [] as $callback) {
            $basePages = array_merge($basePages, $callback());
        }

        return $basePages;
    }
}
