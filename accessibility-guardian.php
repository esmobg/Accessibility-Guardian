<?php
/**
 * Plugin Name:       Accessibility Guardian
 * Plugin URI:        https://github.com/esmobg/Accessibility-Guardian
 * Description:       Automated WCAG 2.2 AA accessibility auditor for WordPress. Scans posts, pages, custom post types, products and terms with axe-core and reports issues with remediation guidance.
 * Version:           1.1.1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            esmobg
 * Author URI:        https://profiles.wordpress.org/esmobg/
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

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Accessibility Guardian requires PHP 8.0 or later.', 'accessibility-guardian' );
			echo '</p></div>';
		}
	);
	return;
}

define( 'ACCG_VERSION', '1.1.1' );
define( 'ACCG_PLUGIN_FILE', __FILE__ );
define( 'ACCG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACCG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACCG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$accg_autoload = ACCG_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $accg_autoload ) ) {
	require_once $accg_autoload;
} else {
	// Fallback PSR-4 autoloader so the plugin works without `composer install`.
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix   = 'AccessibilityGuardian\\';
			$base_dir = ACCG_PLUGIN_DIR . 'src/';
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
