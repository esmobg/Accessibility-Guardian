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
			'accg-admin',
			ACCG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ACCG_VERSION
		);

		wp_enqueue_script(
			'accg-custom-rules',
			ACCG_PLUGIN_URL . 'assets/js/custom-rules.js',
			array(),
			ACCG_VERSION,
			true
		);

		wp_localize_script(
			'accg-custom-rules',
			'accgCustomRulesI18n',
			array(
				'genericLinkHelp'      => __( 'Links must use descriptive text', 'accessibility-guardian' ),
				'genericLinkSummary'   => __( 'Link text does not describe its destination.', 'accessibility-guardian' ),
				'placeholderHelp'      => __( 'Placeholder is not a substitute for a label', 'accessibility-guardian' ),
				'placeholderSummary'   => __( 'Field relies on placeholder text instead of a persistent label.', 'accessibility-guardian' ),
				'newWindowHelp'        => __( 'Warn users when links open new windows', 'accessibility-guardian' ),
				'newWindowSummary'     => __( 'Link opens in a new window without warning the user.', 'accessibility-guardian' ),
				'pdfHelp'              => __( 'Verify accessibility of linked PDF documents', 'accessibility-guardian' ),
				'pdfSummary'           => __( 'Linked PDF detected; verify it is tagged and accessible.', 'accessibility-guardian' ),
			)
		);

		wp_enqueue_script(
			'accg-scanner',
			ACCG_PLUGIN_URL . 'assets/js/scanner.js',
			array( 'accg-custom-rules' ),
			ACCG_VERSION,
			true
		);

		$settings   = (array) get_option( 'accg_settings', array() );
		$wcag_level = isset( $settings['wcag_level'] ) ? sanitize_key( (string) $settings['wcag_level'] ) : 'aa';
		if ( ! in_array( $wcag_level, array( 'a', 'aa' ), true ) ) {
			$wcag_level = 'aa';
		}

		$tags = array( 'wcag2a', 'wcag21a' );
		if ( 'aa' === $wcag_level ) {
			$tags = array_merge( $tags, array( 'wcag2aa', 'wcag21aa', 'wcag22aa' ) );
		}

		wp_localize_script(
			'accg-scanner',
			'accgScanner',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( ScanController::NONCE_ACTION ),
				'axeUrl'       => ACCG_PLUGIN_URL . 'assets/js/axe.min.js',
				'dashboardUrl' => admin_url( 'admin.php?page=accessibility-guardian' ),
				'issuesUrl'    => admin_url( 'admin.php?page=accessibility-guardian-issues' ),
				'wcagTags'     => $tags,
				'i18n'         => array(
					'preparing'    => __( 'Preparing scan…', 'accessibility-guardian' ),
					'scanning'     => __( 'Scanning', 'accessibility-guardian' ),
					'queued'       => __( 'Queued URLs', 'accessibility-guardian' ),
					'issues'       => __( 'issues', 'accessibility-guardian' ),
					'failed'       => __( 'could not be scanned', 'accessibility-guardian' ),
					'saveFailed'   => __( 'results could not be saved', 'accessibility-guardian' ),
					'complete'     => __( 'Scan complete', 'accessibility-guardian' ),
					'score'        => __( 'Score', 'accessibility-guardian' ),
					'cancelling'   => __( 'Cancelling after current page…', 'accessibility-guardian' ),
					'cancelled'    => __( 'Scan cancelled', 'accessibility-guardian' ),
					'startError'   => __( 'Could not start scan. Please reload the page and try again.', 'accessibility-guardian' ),
					'finishError'  => __( 'Scan could not be finalized.', 'accessibility-guardian' ),
					'networkError' => __( 'Connection problem while talking to the server.', 'accessibility-guardian' ),
					'pagesFailed'  => __( 'pages could not be scanned', 'accessibility-guardian' ),
				),
			)
		);

		wp_enqueue_script(
			'accg-dashboard',
			ACCG_PLUGIN_URL . 'assets/js/dashboard.js',
			array(),
			ACCG_VERSION,
			true
		);

		wp_localize_script(
			'accg-dashboard',
			'accgDashboard',
			array(
				'i18n' => array(
					'noHistory'  => __( 'No history yet.', 'accessibility-guardian' ),
					/* translators: %d: accessibility score. */
					'scoreLabel' => __( 'Latest accessibility score %d out of 100', 'accessibility-guardian' ),
				),
			)
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
