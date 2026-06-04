<?php
/**
 * Standalone PHPUnit bootstrap.
 *
 * Provides minimal WordPress function stubs so the plugin's pure logic
 * classes can be unit tested without a full WordPress install.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'AG_VERSION' ) ) {
	define( 'AG_VERSION', 'test' );
}

// PSR-4 autoloader for the plugin source.
spl_autoload_register(
	static function ( string $class ): void {
		$prefix   = 'AccessibilityGuardian\\';
		$base_dir = dirname( __DIR__, 2 ) . '/src/';
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

if ( ! function_exists( '__' ) ) {
	/**
	 * Stubbed translation function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain (ignored).
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ): int {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? '';
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $value ): string {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( $value ) ) ?? '' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( string $value ): string {
		return trim( (string) preg_replace( '/<[^>]*>/', '', $value ) );
	}
}

$GLOBALS['ag_test_options']    = array();
$GLOBALS['ag_test_post_types'] = array( 'post', 'page' );

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $name, $default = false ) {
		return $GLOBALS['ag_test_options'][ $name ] ?? $default;
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( array $args = array(), string $output = 'names' ): array {
		$types = array();
		foreach ( $GLOBALS['ag_test_post_types'] as $type ) {
			$types[ $type ] = $type;
		}
		return $types;
	}
}
