<?php
/**
 * Registers and enqueues admin assets for the plugin pages.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Admin;

use AccessibilityGuardian\Scan\ScanController;

defined( 'ABSPATH' ) || exit;

/**
 * Handles wp_enqueue_script/style for Accessibility Guardian screens.
 */
final class AssetManager {

	/**
	 * Register the enqueue hook.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue scripts and styles on plugin pages only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue( string $hook ): void {
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		wp_enqueue_style(
			'ag-admin',
			AG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AG_VERSION
		);

		// axe-core is injected into the scan iframe by URL, so it is not
		// enqueued on the parent admin page.
		wp_enqueue_script(
			'ag-custom-rules',
			AG_PLUGIN_URL . 'assets/js/custom-rules.js',
			array(),
			AG_VERSION,
			true
		);

		wp_enqueue_script(
			'ag-scanner',
			AG_PLUGIN_URL . 'assets/js/scanner.js',
			array( 'ag-custom-rules' ),
			AG_VERSION,
			true
		);

		wp_localize_script(
			'ag-scanner',
			'agScanner',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( ScanController::NONCE_ACTION ),
				'axeUrl'  => AG_PLUGIN_URL . 'assets/js/axe.min.js',
				'i18n'    => array(
					'preparing'  => __( 'Preparing scan…', 'accessibility-guardian' ),
					'scanning'   => __( 'Scanning', 'accessibility-guardian' ),
					'queued'     => __( 'Queued URLs', 'accessibility-guardian' ),
					'issues'     => __( 'issues', 'accessibility-guardian' ),
					'failed'     => __( 'could not be scanned', 'accessibility-guardian' ),
					'complete'   => __( 'Scan complete', 'accessibility-guardian' ),
					'score'      => __( 'Score', 'accessibility-guardian' ),
					'cancelling' => __( 'Cancelling after current page…', 'accessibility-guardian' ),
					'startError' => __( 'Could not start scan.', 'accessibility-guardian' ),
					'finishError' => __( 'Scan could not be finalized.', 'accessibility-guardian' ),
				),
			)
		);

		wp_enqueue_script(
			'ag-dashboard',
			AG_PLUGIN_URL . 'assets/js/dashboard.js',
			array(),
			AG_VERSION,
			true
		);
	}

	/**
	 * Determine whether the current screen belongs to this plugin.
	 */
	private function is_plugin_page(): bool {
		// Reading the page slug for asset gating only; no state change occurs.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return str_starts_with( $page, 'accessibility-guardian' );
	}
}
