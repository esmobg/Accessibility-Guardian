<?php
/**
 * Registers admin menu pages and renders their templates.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Admin;

use AccessibilityGuardian\Rules\RuleCatalog;
use AccessibilityGuardian\Scan\ScoreCalculator;
use AccessibilityGuardian\Storage\IssueRepository;
use AccessibilityGuardian\Storage\ScanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the Accessibility Guardian admin section.
 */
final class AdminMenu {

	/**
	 * Dashboard page slug (also the top-level slug).
	 */
	public const SLUG_DASHBOARD = 'accessibility-guardian';

	/**
	 * Scan page slug.
	 */
	public const SLUG_SCAN = 'accessibility-guardian-scan';

	/**
	 * Issues page slug.
	 */
	public const SLUG_ISSUES = 'accessibility-guardian-issues';

	/**
	 * Settings page slug.
	 */
	public const SLUG_SETTINGS = 'accessibility-guardian-settings';

	/**
	 * Scan repository.
	 */
	private ScanRepository $scans;

	/**
	 * Issue repository.
	 */
	private IssueRepository $issues;

	/**
	 * Score calculator.
	 */
	private ScoreCalculator $score;

	/**
	 * Rule metadata catalog.
	 */
	private RuleCatalog $catalog;

	/**
	 * Constructor.
	 *
	 * @param ScanRepository  $scans   Scan repository.
	 * @param IssueRepository $issues  Issue repository.
	 * @param ScoreCalculator $score   Score calculator.
	 * @param RuleCatalog     $catalog Rule metadata catalog.
	 */
	public function __construct( ScanRepository $scans, IssueRepository $issues, ScoreCalculator $score, RuleCatalog $catalog ) {
		$this->scans   = $scans;
		$this->issues  = $issues;
		$this->score   = $score;
		$this->catalog = $catalog;
	}

	/**
	 * Register admin hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
	}

	/**
	 * Register the menu and subpages.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Accessibility Guardian', 'accessibility-guardian' ),
			__( 'Accessibility Guardian', 'accessibility-guardian' ),
			'manage_options',
			self::SLUG_DASHBOARD,
			array( $this, 'render_dashboard' ),
			'dashicons-universal-access-alt',
			81
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'Dashboard', 'accessibility-guardian' ),
			__( 'Dashboard', 'accessibility-guardian' ),
			'manage_options',
			self::SLUG_DASHBOARD,
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'Run Scan', 'accessibility-guardian' ),
			__( 'Run Scan', 'accessibility-guardian' ),
			'manage_options',
			self::SLUG_SCAN,
			array( $this, 'render_scan' )
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'Issues', 'accessibility-guardian' ),
			__( 'Issues', 'accessibility-guardian' ),
			'manage_options',
			self::SLUG_ISSUES,
			array( $this, 'render_issues' )
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'Settings', 'accessibility-guardian' ),
			__( 'Settings', 'accessibility-guardian' ),
			'manage_options',
			self::SLUG_SETTINGS,
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard(): void {
		$this->guard();

		$latest = $this->scans->latest();
		$recent = $this->scans->recent( 10 );
		$history = $this->scans->history( 20 );

		$severity_counts = array();
		$category_counts = array();
		$top_rules       = array();
		$band            = $this->score->band( 0 );

		if ( null !== $latest ) {
			$scan_id         = (int) $latest['id'];
			$severity_counts = $this->issues->severity_counts( $scan_id );
			$category_counts = $this->issues->category_counts( $scan_id );
			$top_rules       = $this->issues->top_rules( $scan_id, 8 );
			$band            = $this->score->band( (int) $latest['score'] );
		}

		$this->render(
			'dashboard',
			array(
				'latest'          => $latest,
				'recent'          => $recent,
				'history'         => $history,
				'severity_counts' => $severity_counts,
				'category_counts' => $category_counts,
				'top_rules'       => $top_rules,
				'band'            => $band,
				'scan_url'        => admin_url( 'admin.php?page=' . self::SLUG_SCAN ),
			)
		);
	}

	/**
	 * Render the run-scan page.
	 */
	public function render_scan(): void {
		$this->guard();

		// Reading filter params for display only.
		$post_id   = isset( $_GET['post_id'] ) ? absint( wp_unslash( (string) $_GET['post_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$scan_type = $post_id > 0 ? 'single' : 'full';

		$this->render(
			'scan',
			array(
				'post_id'   => $post_id,
				'scan_type' => $scan_type,
				'post_title' => $post_id > 0 ? get_the_title( $post_id ) : '',
			)
		);
	}

	/**
	 * Render the issues listing page, categorized by severity.
	 */
	public function render_issues(): void {
		$this->guard();

		// Read-only filters for display; no state change.
		$scan_id  = isset( $_GET['scan_id'] ) ? absint( wp_unslash( (string) $_GET['scan_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$severity = isset( $_GET['severity'] ) ? sanitize_key( wp_unslash( (string) $_GET['severity'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( (string) $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 0 === $scan_id ) {
			$latest  = $this->scans->latest();
			$scan_id = null !== $latest ? (int) $latest['id'] : 0;
		}

		$valid_sev = array( 'critical', 'major', 'minor', 'warning' );
		if ( '' !== $severity && ! in_array( $severity, $valid_sev, true ) ) {
			$severity = '';
		}

		$per_page        = 25;
		$severity_counts = $scan_id > 0 ? $this->issues->severity_counts( $scan_id ) : array();
		$issues          = $scan_id > 0 ? $this->issues->for_scan( $scan_id, $per_page, $paged, $severity ) : array();

		$this->render(
			'issues',
			array(
				'scan_id'         => $scan_id,
				'severity'        => $severity,
				'paged'           => $paged,
				'per_page'        => $per_page,
				'severity_counts' => $severity_counts,
				'issues'          => $issues,
				'catalog'         => $this->catalog,
				'base_url'        => admin_url( 'admin.php?page=' . self::SLUG_ISSUES ),
				'settings_url'    => admin_url( 'admin.php?page=' . self::SLUG_SETTINGS ),
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings(): void {
		$this->guard();

		$notice = get_transient( 'accg_settings_notice' );
		if ( is_string( $notice ) && '' !== $notice ) {
			delete_transient( 'accg_settings_notice' );
			add_settings_error( 'accg_settings', 'accg_settings_saved', $notice, 'updated' );
		}

		$settings   = (array) get_option( 'accg_settings', array() );
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );

		$this->render(
			'settings',
			array(
				'settings'    => $settings,
				'post_types'  => $post_types,
				'fix_catalog' => $this->catalog->fixes(),
			)
		);
	}

	/**
	 * Persist settings when the settings form is submitted.
	 */
	public function maybe_save_settings(): void {
		if ( ! isset( $_POST['accg_settings_submit'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'accessibility-guardian' ) );
		}

		check_admin_referer( 'accg_save_settings' );

		$post_types = array();
		if ( isset( $_POST['include_post_types'] ) && is_array( $_POST['include_post_types'] ) ) {
			$post_types = array_map( 'sanitize_key', wp_unslash( $_POST['include_post_types'] ) );
		}

		$fixes     = array();
		$available = array_keys( $this->catalog->fixes() );
		$submitted = isset( $_POST['fixes'] ) && is_array( $_POST['fixes'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['fixes'] ) )
			: array();
		foreach ( $available as $fix_key ) {
			$fixes[ $fix_key ] = in_array( $fix_key, $submitted, true );
		}

		$wcag_level = isset( $_POST['wcag_level'] ) ? sanitize_key( wp_unslash( (string) $_POST['wcag_level'] ) ) : 'aa';
		if ( ! in_array( $wcag_level, array( 'a', 'aa' ), true ) ) {
			$wcag_level = 'aa';
		}

		$settings = array(
			'include_post_types' => $post_types,
			'include_terms'      => ! empty( $_POST['include_terms'] ),
			'batch_size'         => isset( $_POST['batch_size'] ) ? min( 50, max( 1, absint( wp_unslash( (string) $_POST['batch_size'] ) ) ) ) : 5,
			'wcag_level'         => $wcag_level,
			'fixes'              => $fixes,
		);

		update_option( 'accg_settings', $settings );

		set_transient(
			'accg_settings_notice',
			__( 'Settings saved.', 'accessibility-guardian' ),
			30
		);

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG_SETTINGS ) );
		exit;
	}

	/**
	 * Capability gate shared by all pages.
	 */
	private function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'accessibility-guardian' ) );
		}
	}

	/**
	 * Render a template with extracted variables.
	 *
	 * @param string               $template Template filename without extension.
	 * @param array<string, mixed> $context  Variables exposed to the template.
	 */
	private function render( string $template, array $context ): void {
		$file = ACCG_PLUGIN_DIR . 'templates/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		// Helpers available to every template.
		$score_calculator = $this->score;

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $context, EXTR_SKIP );

		require $file;
	}
}
