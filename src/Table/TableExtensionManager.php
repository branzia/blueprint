<?php

namespace Branzia\Bootstrap\Table;
/**
 * TableExtensionManager
 * 
 * A utility class that allows modular extensions to Filament table columns 
 * without modifying the original resource definitions.
 * 
 * Ideal for package-based or domain-driven development where different modules 
 * need to contribute their own table columns dynamically.
 * 
 * -------------------
 * ✅ Key Features:
 * -------------------
 * - Register additional table columns for specific Filament resource classes.
 * - Supports inserting columns `before` or `after` an existing column (by name).
 * - Falls back to appending the column if no matching position is found.
 * 
 * -------------------
 * 🔧 Usage Overview:
 * -------------------
 * // 1. Register columns (typically in a Service Provider):
 * TableExtensionManager::register(YourResource::class, fn () => [
 *     ['column' => TextColumn::make('custom_column')->label('Custom'), 'after' => 'some_column'],
 * ]);
 * 
 * // 2. Apply columns inside the resource table definition:
 * public static function table(Table $table): Table {
 *     return $table->columns(
 *         TableExtensionManager::apply([
 *             TextColumn::make('some_column'),
 *             TextColumn::make('created_at'),
 *         ], static::class)
 *     );
 * }
 * 
 * -------------------
 * 🧩 Method Summary:
 * -------------------
 * register(string $resourceClass, callable $callback):
 *     Registers a set of column definitions for the given resource.
 * 
 * apply(array $baseColumns, string $resourceClass): array
 *     Applies all registered extensions and returns the merged column list.
 * 
 * insertColumn(array $columns, $newColumn, ?string $after, ?string $before): array
 *     Inserts the given column in the specified position relative to other columns.
 * 
 * -------------------
 * 📝 Notes:
 * -------------------
 * - Only columns with a `getName()` method can be used for `before` / `after` matching.
 * - Designed to encourage clean separation between core and feature-specific logic.
 * - Works best in modular applications where multiple modules can extend UI components independently.
 */

 
class TableExtensionManager
{
    /** @var array<string, array<callable>> */
    protected static array $columnCallbacks = [];

    public static function register(string $resourceClass, callable $callback): void
    {
        static::$columnCallbacks[$resourceClass][] = $callback;
    }

    public static function apply(array $baseColumns, string $resourceClass): array
    {
        $columns = $baseColumns;

        foreach (static::$columnCallbacks[$resourceClass] ?? [] as $callback) {
            $extraColumns = $callback();

            foreach ($extraColumns as $col) {
                if (is_array($col) && isset($col['column'])) {
                    $columns = self::insertColumn(
                        $columns,
                        $col['column'],
                        $col['after'] ?? null,
                        $col['before'] ?? null
                    );
                } else {
                    $columns[] = $col;
                }
            }
        }

        return $columns;
    }

    protected static function insertColumn(array $columns, $newColumn, ?string $after = null, ?string $before = null): array
    {
        $newSchema = [];
        $inserted = false;

        foreach ($columns as $column) {
            if ($before && method_exists($column, 'getName') && $column->getName() === $before) {
                $newSchema[] = $newColumn;
                $inserted = true;
            }

            $newSchema[] = $column;

            if ($after && method_exists($column, 'getName') && $column->getName() === $after) {
                $newSchema[] = $newColumn;
                $inserted = true;
            }
        }

        if (!$inserted && !$after && !$before) {
            $newSchema[] = $newColumn;
        }

        return $newSchema;
    }
}