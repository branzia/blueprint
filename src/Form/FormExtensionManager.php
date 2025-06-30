<?php

namespace Branzia\Bootstrap\Form;
use Illuminate\Support\Facades\Log;

/**
 * FormExtensionManager
 * 
 * A flexible utility for dynamically injecting additional fields into Filament form schemas,
 * particularly useful for modular or package-based Laravel applications.
 * 
 * -------------------
 * ✅ Key Features:
 * -------------------
 * - Register extra form fields for specific Filament resources.
 * - Supports field insertion using `before` or `after` placement relative to named fields.
 * - Recursively supports nested container components like Section, Card, Fieldset, Tabs.
 * - Clean separation of concern: does not modify the base form directly.
 * 
 * -------------------
 * 🔧 Usage Overview:
 * -------------------
 * // 1. Register form fields (typically in a module's service provider):
 * FormExtensionManager::register(YourResource::class, fn () => [
 *     ['field' => TextInput::make('custom_field'), 'after' => 'email'],
 * ]);
 * 
 * // 2. Use in Resource:
 * public static function form(Form $form): Form {
 *     $baseSchema = [...]; // your default schema
 *     return $form->schema(
 *         FormExtensionManager::apply($baseSchema, static::class)
 *     );
 * }
 * 
 * -------------------
 * 🧩 Method Summary:
 * -------------------
 * register(string $resourceClass, callable $callback):
 *     Stores the callback that returns additional fields to inject into the specified resource.
 * 
 * apply(array $baseSchema, string $resourceClass): array
 *     Applies all registered field extensions to the provided schema, returning the updated array.
 * 
 * insertField(array $schema, $newField, ?string $after, ?string $before): array
 *     Inserts the field at the correct position in the schema based on the provided `after` or `before` key.
 *     Also handles inserting into nested container components (e.g., Section, Fieldset).
 * 
 * -------------------
 * 📝 Notes:
 * -------------------
 * - Components must implement `getName()` to be positioned.
 * - Container components must implement both `getChildComponents()` and `childComponents()` to support nesting.
 * - Fallback logic ensures fields are appended if no matching position is found.
 * 
 * -------------------
 * 🧠 Ideal For:
 * -------------------
 * - Building modular Filament packages.
 * - Extending existing forms without modifying core resource files.
 * - Injecting fields from external modules like billing, tax, permissions, etc.
 */

 
class FormExtensionManager
{
    /** @var array<string, array<callable>> */
    protected static array $fieldCallbacks = [];

    public static function register(string $resourceClass, callable $callback): void
    {
        static::$fieldCallbacks[$resourceClass][] = $callback;
    }

    public static function apply(array $baseSchema, string $resourceClass): array
    {
        $schema = $baseSchema;

        foreach (static::$fieldCallbacks[$resourceClass] ?? [] as $callback) {
            $fields = $callback();

            // check for positioning meta
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    // Support metadata like ['field' => $field, 'after' => 'email']
                    if (is_array($field) && isset($field['field'])) {
                        $schema = self::insertField($schema, $field['field'], $field['after'] ?? null, $field['before'] ?? null);
                    } else {
                        $schema[] = $field; // fallback: push to end
                    }
                }
            }
        }

        return $schema;
    }


protected static function insertField(array $schema, $newField, ?string $after = null, ?string $before = null): array
{
    $newSchema = [];
    $inserted = false;

    foreach ($schema as $item) {
        // Handle container components (Section, Card, Fieldset, etc.)
        if (method_exists($item, 'getChildComponents') && method_exists($item, 'childComponents')) {
            $innerSchema = $item->getChildComponents();
            $updatedInnerSchema = [];

            foreach ($innerSchema as $innerField) {
                // Insert before
                if ($before && method_exists($innerField, 'getName') && $innerField->getName() === $before) {
                    $updatedInnerSchema[] = $newField;
                    $inserted = true;
                }

                $updatedInnerSchema[] = $innerField;

                // Insert after
                if ($after && method_exists($innerField, 'getName') && $innerField->getName() === $after) {
                    $updatedInnerSchema[] = $newField;
                    $inserted = true;
                }
            }

            if (!$inserted && !$after && !$before) {
                $updatedInnerSchema[] = $newField;
                $inserted = true;
            }

            $item = $item->childComponents($updatedInnerSchema);
        }

        $newSchema[] = $item;
    }

    // If still not inserted, try root level
    if (!$inserted) {
        $finalSchema = [];

        foreach ($newSchema as $item) {
            if ($before && method_exists($item, 'getName') && $item->getName() === $before) {
                $finalSchema[] = $newField;
                $inserted = true;
            }

            $finalSchema[] = $item;

            if ($after && method_exists($item, 'getName') && $item->getName() === $after) {
                $finalSchema[] = $newField;
                $inserted = true;
            }
        }

        if (!$inserted && !$after && !$before) {
            $finalSchema[] = $newField;
        }

        return $finalSchema;
    }

    return $newSchema;
}





}
