<?php
/**
 * FluentCRM Compatibility Shims
 *
 * Fixes bugs in FluentCRM v2.9.65's ORM framework.
 *
 * @package MCP\Adapters
 */

namespace FluentCrm\Framework\Database\Orm;

use FluentCrm\Framework\Support\Str;

/**
 * Provide missing snake_case() function for FluentCRM v2.9.65
 *
 * **FluentCRM Bug**: Builder.php line 1082 calls `snake_case($name)` but this function
 * doesn't exist in the FluentCrm\Framework\Database\Orm namespace.
 *
 * **Root Cause**: FluentCRM should be using `Str::snake()` instead (Str is already imported
 * at line 7 of Builder.php), but they incorrectly call a non-existent global function.
 *
 * **Proper Fix**: FluentCRM should change line 1082 from:
 *   `$this->selectSub($query->toBase(), snake_case($name).'_count');`
 * To:
 *   `$this->selectSub($query->toBase(), Str::snake($name).'_count');`
 *
 * **This Shim**: Provides the missing function by delegating to Str::snake() until
 * FluentCRM fixes their bug upstream.
 *
 * @param string $value The string to convert to snake_case
 * @return string The snake_cased string
 */
if ( ! function_exists( __NAMESPACE__ . '\snake_case' ) ) {
	function snake_case( string $value ): string {
		return Str::snake( $value );
	}
}
