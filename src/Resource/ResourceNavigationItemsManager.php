<?php

namespace Branzia\Bootstrap\Resource;

class ResourceNavigationItemsManager
{
    /**
     * @var array<class-string, callable>
     */
    protected static array $extensions = [];

    /**
     * Register navigation extensions for a specific resource.
     */
    public static function register(string $resourceClass, callable $pagesCallback): void
    {
        static::$extensions[$resourceClass] = $pagesCallback;
    }

    /**
     * Apply all extensions for a given resource and return the merged list of record page classes.
     *
     * @param array $basePages
     * @param string $resourceClass
     * @return array
     */
    public static function apply(array $basePages, string $resourceClass = null): array
    {
        $resourceClass ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? '';

        $extraPages = [];

        if (isset(static::$extensions[$resourceClass])) {
            $callback = static::$extensions[$resourceClass];
            $extraPages = $callback();
        }

        return array_merge($basePages, $extraPages);
    }
}
