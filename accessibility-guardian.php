<?php
/**
 * Plugin Name:       Accessibility Guardian
 * Plugin URI:        https://example.com/accessibility-guardian
 * Description:       Automated WCAG 2.2 AA accessibility auditor for WordPress. Scans posts, pages, custom post types, products and terms with axe-core and reports issues with remediation guidance.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.2
 * Author:            Accessibility Guardian
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       accessibility-guardian
 * Domain Path:       /languages
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian;

defined( 'ABSPATH' ) || exit;

define( 'AG_VERSION', '1.0.0' );
define( 'AG_PLUGIN_FILE', __FILE__ );
define( 'AG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$ag_autoload = AG_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $ag_autoload ) ) {
	require_once $ag_autoload;
} else {
	// Fallback PSR-4 autoloader so the plugin works without `composer install`.
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix   = 'AccessibilityGuardian\\';
			$base_dir = AG_PLUGIN_DIR . 'src/';
			$len      = strlen( $prefix );
			if ( 0 !== strncmp( $prefix, $class, $len ) ) {
				return;
			}
			$relative = substr( $class, $len );
			$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	);
}

register_activation_hook( __FILE__, array( Activation\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Activation\Installer::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->init();
	}
);
