<?php 

namespace App\Libraries\Providers; 

use Jenssegers\Blade\Blade;

/**
 * Provides and registers Blade directives for the component system.
 * This includes @component, @endcomponent, @slot, and @endslot.
 *
 * This class encapsulates the logic for parsing component tags,
 * resolving view paths, managing component data scopes, handling slots,
 * and generating the necessary PHP code for the Blade compiler.
 * It aims to provide a component syntax similar to Laravel's.
 */
class ComponentDirectiveProvider
{
    /**
     * Defines a list of internal variable names used during component directive processing.
     * These variables are specific to the directive's implementation (parsing, setup, etc.)
     * and are automatically filtered out from the data passed into the actual
     * component view to prevent scope pollution and mimic variable isolation.
     * This list should be kept synchronized with any temporary variables introduced
     * within the compilation or helper methods below.
     *
     * @var array<int, string>
     */
    private static array $internalComponentVars = [
        // Blade / Framework Internals (Commonly available, filter just in case)
        '__env',
        '__data',
        'obLevel',
        'app',
        'config',
        // Directive Parsing & State Variables
        'expression',
        'parts',
        'component',
        'attributes',
        'isSelfClosing',
        'parsed',
        'componentPath',
        'attributesString',
        'phpSetupCode',
        'firstCommaPos',
        'remainingExpression',
        'trimmedExpressionEnd',
        'selfClosingFlag',
        'lastCommaPos',
        // Component Data Handling Variables
        '__componentPath',
        '__componentAttributes',
        '__componentData',
        '__componentSlot',
        '__currentSlot', // Slot specific
        // Variable Filtering & Setup Helpers
        '__definedVars',
        '__internalVars',
        'internalVarsList',
        'internalVarsJson',
        'finalFilterKeys',
        'finalFilterKeysJson',
    ];

    //======================================================================
    // Public Registration Method
    //======================================================================

    /**
     * Registers all component-related directives with the Blade compiler instance.
     * This method is typically called by the main BladeExtension class.
     *
     * @param Blade $blade The Blade compiler instance provided by Jenssegers\Blade.
     * @return void
     */
    public function register(Blade $blade): void
    {
        $blade->directive('component', [$this, 'compileComponent']);
        $blade->directive('endcomponent', [$this, 'compileEndComponent']);
        $blade->directive('slot', [$this, 'compileSlot']);
        $blade->directive('endslot', [$this, 'compileEndSlot']);
    }

    //======================================================================
    // Directive Compilation Methods (Called by Blade Compiler via `directive` calls)
    //======================================================================

    /**
     * Compiles the `@component` directive into PHP code.
     * Parses the expression, resolves the view, sets up the component data,
     * and either prepares for slot content (starts output buffering) or
     * directly renders a self-closing component.
     *
     * @param string $expression The raw expression string passed to the directive
     *                           (e.g., "'x-alert', ['type' => 'error'], true").
     * @return string The compiled PHP code to be executed during view rendering.
     */
    public function compileComponent(string $expression): string
    {
        $parsed = $this->_parseComponentExpression($expression);
        $componentPath = $this->_resolveComponentPath($parsed['name']);
        $phpSetupCode = $this->_generateComponentSetupCode($componentPath, $parsed['attributes']);

        if ($parsed['isSelfClosing']) {
            return "<?php " . $phpSetupCode .
                "\$__componentData['slot'] = ''; " .
                "echo blade_view(\$__componentPath, \$__componentData, true); " .
                "unset(\$__componentPath, \$__componentAttributes, \$__componentData, \$__definedVars, \$__internalVars); " .
                "?>";
        } else {
            return "<?php " . $phpSetupCode .
                "\$__componentSlot = ''; " .
                "ob_start(); " .
                "?>";
        }
    }

    /**
     * Compiles the `@endcomponent` directive into PHP code.
     * This code captures the output buffer (containing the default slot content),
     * adds it to the component data, renders the component view via `blade_view`,
     * and cleans up internal variables.
     *
     * @return string The compiled PHP code.
     */
    public function compileEndComponent(): string
    {
        return "<?php \$__componentSlot = ob_get_clean();
                 \$__componentData['slot'] = \$__componentSlot;
                 echo blade_view(\$__componentPath, \$__componentData, true);
                 unset(\$__componentPath, \$__componentAttributes, \$__componentData, \$__componentSlot, \$__definedVars, \$__internalVars); " .
            "?>";
    }

    /**
     * Compiles the `@slot` directive into PHP code.
     * This code stores the provided slot name and starts output buffering
     * to capture the content for that named slot.
     *
     * @param string $expression The slot name expression (e.g., "'footer'").
     * @return string The compiled PHP code.
     */
    public function compileSlot(string $expression): string
    {
        return "<?php \$__currentSlot = {$expression}; ob_start(); ?>";
    }

    /**
     * Compiles the `@endslot` directive into PHP code.
     * This code captures the output buffer for the current named slot,
     * stores the captured content in the component's data array under the slot's name,
     * and cleans up the current slot tracking variable.
     *
     * @return string The compiled PHP code.
     */
    public function compileEndSlot(): string
    {
        return "<?php \$__componentData[\$__currentSlot] = ob_get_clean(); unset(\$__currentSlot); ?>";
    }


    //======================================================================
    // Private Helper Methods (Internal logic used by compilation methods)
    //======================================================================

    /**
     * Parses the raw string expression from the @component directive.
     * Extracts the component name, the string representation of the attributes array,
     * and determines if the component is intended to be self-closing (via a trailing ', true').
     * Handles cases with only name, name + attributes, and name + attributes + self-closing flag.
     *
     * @param string $expression Raw directive expression string.
     * @return array An array containing 'name' (string), 'attributes' (string, PHP array format),
     *               and 'isSelfClosing' (bool).
     */
    private function _parseComponentExpression(string $expression): array
    {
        $expression = trim($expression);
        $parsed = ['name' => 'error-parsing-component', 'attributes' => '[]', 'isSelfClosing' => false];

        $firstCommaPos = strpos($expression, ',');

        if ($firstCommaPos === false) {
            $parsed['name'] = trim($expression, " \t\n\r\0\x0B'\"");
        } else {
            $parsed['name'] = trim(substr($expression, 0, $firstCommaPos), " \t\n\r\0\x0B'\"");
            $remainingExpression = substr($expression, $firstCommaPos + 1);
            $selfClosingFlag = ', true';

            if (\str_ends_with(rtrim($remainingExpression), $selfClosingFlag)) { // PHP 8+ required
                $parsed['isSelfClosing'] = true;
                $lastCommaPos = strrpos($remainingExpression, ',');
                if ($lastCommaPos !== false) {
                    $attributesString = trim(substr($remainingExpression, 0, $lastCommaPos));
                    if (!empty($attributesString)) {
                        $parsed['attributes'] = $attributesString;
                    }
                }
            } else {
                $parsed['attributes'] = trim($remainingExpression);
                $parsed['isSelfClosing'] = false;
            }
        }

        // Ensure the attributes string is a valid PHP array representation or empty array '[]'
        if (empty($parsed['attributes']) || !preg_match('/^\[.*\]$/', trim($parsed['attributes']))) {
            $parsed['attributes'] = '[]';
        }
        return $parsed;
    }

    /**
     * Resolves the parsed component name into a Blade view identifier suitable for `blade_view`.
     * Primarily converts the 'x-name' syntax into the 'components::name' namespace syntax,
     * assuming a 'components' namespace is registered in the `blade_view` helper.
     * If the name doesn't start with 'x-', it's returned as is, assuming it's already
     * a valid view path (e.g., 'shared.modal').
     *
     * @param string $componentName The component name extracted by `_parseComponentExpression`.
     * @return string The resolved Blade view identifier (e.g., 'components::alert').
     */
    private function _resolveComponentPath(string $componentName): string
    {
        // *** This assumes 'components' is the registered namespace for components ***
        // *** Adjust if a different namespace convention is used in blade_view ***
        $componentNamespace = 'components';

        if (\str_starts_with($componentName, 'x-')) { // PHP 8+ required
            return $componentNamespace . '::' . substr($componentName, 2);
        }
        return $componentName;
    }

    /**
     * Generates the initial PHP setup code required by the compiled @component directive.
     * This code snippet defines component-specific variables (`$__componentPath`, `$__componentAttributes`),
     * captures the surrounding variable scope (`get_defined_vars`), filters out internal/directive-specific
     * variables (using `$internalComponentVars`), merges the scope with passed attributes,
     * and performs a final filter before the data (`$__componentData`) is ready for the view.
     *
     * @param string $componentPath The resolved Blade view identifier for the component.
     * @param string $attributesString A string containing the PHP array representation of component attributes.
     * @return string A PHP code snippet (without opening/closing tags) to be prepended in the compiled view.
     */
    private function _generateComponentSetupCode(string $componentPath, string $attributesString): string
    {
        $internalVarsJson = json_encode(self::$internalComponentVars);
        $finalFilterKeysJson = json_encode(['__componentPath', '__componentAttributes', '__componentData', '__componentSlot', '__currentSlot']);

        $phpCode = '';
        $phpCode .= "\$__componentPath = '{$componentPath}'; ";
        $phpCode .= "\$__componentAttributes = {$attributesString}; ";
        $phpCode .= "\$__definedVars = get_defined_vars(); ";
        $phpCode .= "\$__internalVars = json_decode('{$internalVarsJson}', true); ";
        $phpCode .= "foreach(array_keys(\$__definedVars) as \$__key) { ";
        $phpCode .= "if (\\str_starts_with(\$__key, '__') || in_array(\$__key, \$__internalVars)) { unset(\$__definedVars[\$__key]); } "; // PHP 8+ required
        $phpCode .= "} ";
        $phpCode .= "\$__componentData = array_merge(\$__definedVars, \$__componentAttributes); ";
        $phpCode .= "\$__finalFilterKeys = json_decode('{$finalFilterKeysJson}', true); ";
        $phpCode .= "\$__componentData = array_filter(\$__componentData, fn(\$key) => !in_array(\$key, \$__finalFilterKeys), ARRAY_FILTER_USE_KEY); ";

        return $phpCode;
    }
}
