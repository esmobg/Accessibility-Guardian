<?php
/**
 * AJAX endpoints that orchestrate scanning from the browser.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Scan;

use AccessibilityGuardian\Storage\IssueRepository;
use AccessibilityGuardian\Storage\ScanRepository;

defined( 'ABSPATH' ) || exit;

// Nonce is verified in guard() via check_ajax_referer( 'accg_scan' ) before any request read.
// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.Security.NonceVerification.Recommended

/**
 * Handles start/save/finish/status AJAX requests for scans.
 */
final class ScanController {

	/**
	 * Nonce action used by every scan AJAX request.
	 */
	public const NONCE_ACTION = 'accg_scan';

	/**
	 * URL provider.
	 */
	private UrlProvider $url_provider;

	/**
	 * Scan repository.
	 */
	private ScanRepository $scans;

	/**
	 * Issue repository.
	 */
	private IssueRepository $issues;

	/**
	 * Result normalizer.
	 */
	private ResultNormalizer $normalizer;

	/**
	 * Score calculator.
	 */
	private ScoreCalculator $score;

	/**
	 * Constructor.
	 *
	 * @param UrlProvider      $url_provider URL provider.
	 * @param ScanRepository   $scans        Scan repository.
	 * @param IssueRepository  $issues       Issue repository.
	 * @param ResultNormalizer $normalizer   Result normalizer.
	 * @param ScoreCalculator  $score        Score calculator.
	 */
	public function __construct(
		UrlProvider $url_provider,
		ScanRepository $scans,
		IssueRepository $issues,
		ResultNormalizer $normalizer,
		ScoreCalculator $score
	) {
		$this->url_provider = $url_provider;
		$this->scans        = $scans;
		$this->issues       = $issues;
		$this->normalizer   = $normalizer;
		$this->score        = $score;
	}

	/**
	 * Register AJAX hooks.
	 */
	public function register(): void {
		add_action( 'wp_ajax_accg_start_scan', array( $this, 'handle_start' ) );
		add_action( 'wp_ajax_accg_save_results', array( $this, 'handle_save' ) );
		add_action( 'wp_ajax_accg_finish_scan', array( $this, 'handle_finish' ) );
		add_action( 'wp_ajax_accg_scan_status', array( $this, 'handle_status' ) );
	}

	/**
	 * Start a scan: build the URL queue and create the scan record.
	 */
	public function handle_start(): void {
		$this->guard();

		// Recover from scans abandoned mid-run (e.g. the admin closed the tab).
		$this->scans->fail_stale();

		$scan_type = isset( $_POST['scan_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['scan_type'] ) ) : 'full';
		$post_id   = isset( $_POST['post_id'] ) ? absint( wp_unslash( (string) $_POST['post_id'] ) ) : 0;

		if ( 'single' === $scan_type && $post_id > 0 ) {
			$queue = $this->url_provider->get_single_post_urls( $post_id );
		} else {
			$scan_type = 'full';
			$queue     = $this->url_provider->get_full_site_urls( UrlProvider::resolve_url_limit() );
		}

		if ( empty( $queue ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No scannable URLs were found.', 'accessibility-guardian' ) ),
				400
			);
		}

		$scan_id = $this->scans->create( $scan_type, count( $queue ) );

		if ( 0 === $scan_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Unable to create a scan record.', 'accessibility-guardian' ) ),
				500
			);
		}

		wp_send_json_success(
			array(
				'scan_id' => $scan_id,
				'queue'   => array_map(
					static fn ( array $entry ): array => array(
						'url'     => $entry['url'],
						'post_id' => $entry['post_id'],
						'label'   => $entry['label'],
					),
					$queue
				),
			)
		);
	}

	/**
	 * Save the results for a single scanned URL.
	 */
	public function handle_save(): void {
		$this->guard();

		$scan_id = isset( $_POST['scan_id'] ) ? absint( wp_unslash( (string) $_POST['scan_id'] ) ) : 0;
		$raw     = isset( $_POST['payload'] ) ? wp_unslash( (string) $_POST['payload'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON payload is decoded and type-checked below.

		if ( 0 === $scan_id || '' === $raw ) {
			wp_send_json_error( array( 'message' => __( 'Invalid scan payload.', 'accessibility-guardian' ) ), 400 );
		}

		if ( strlen( $raw ) > 524288 ) {
			wp_send_json_error( array( 'message' => __( 'Scan payload is too large.', 'accessibility-guardian' ) ), 413 );
		}

		$scan = $this->scans->find( $scan_id );

		if ( null === $scan || 'running' !== $scan['status'] ) {
			wp_send_json_error( array( 'message' => __( 'Scan is not running.', 'accessibility-guardian' ) ), 409 );
		}

		$payload = json_decode( $raw, true );

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => __( 'Malformed scan payload.', 'accessibility-guardian' ) ), 400 );
		}

		$issues   = $this->normalizer->normalize( $payload );
		$inserted = $this->issues->insert_batch( $scan_id, $issues );
		$this->scans->increment_scanned( $scan_id, 1 );

		wp_send_json_success(
			array(
				'inserted' => $inserted,
				'counts'   => $this->normalizer->severity_counts( $issues ),
			)
		);
	}

	/**
	 * Finalize a scan and compute the aggregate score.
	 */
	public function handle_finish(): void {
		$this->guard();

		$scan_id = isset( $_POST['scan_id'] ) ? absint( wp_unslash( (string) $_POST['scan_id'] ) ) : 0;
		$passes  = isset( $_POST['passes'] ) ? absint( wp_unslash( (string) $_POST['passes'] ) ) : 0;

		$scan = $this->scans->find( $scan_id );
		if ( 0 === $scan_id || null === $scan ) {
			wp_send_json_error( array( 'message' => __( 'Invalid scan id.', 'accessibility-guardian' ) ), 400 );
		}

		if ( 'complete' === $scan['status'] ) {
			wp_send_json_success(
				array(
					'score'  => (int) $scan['score'],
					'band'   => $this->score->band( (int) $scan['score'] ),
					'totals' => array(
						'score'    => (int) $scan['score'],
						'errors'   => (int) $scan['errors'],
						'warnings' => (int) $scan['warnings'],
						'passes'   => (int) $scan['passes'],
					),
					'counts' => $this->issues->severity_counts( $scan_id ),
				)
			);
		}

		if ( 'running' !== $scan['status'] ) {
			wp_send_json_error( array( 'message' => __( 'Scan is not running.', 'accessibility-guardian' ) ), 409 );
		}

		$counts_by_url = $this->issues->severity_counts_by_url( $scan_id );
		$scanned_urls  = (int) $scan['scanned_urls'];
		$counts        = $this->issues->severity_counts( $scan_id );
		$score         = $this->score->calculate_site_score( $counts_by_url, $scanned_urls );

		$totals = array(
			'score'    => $score,
			'errors'   => $this->score->error_count( $counts ),
			'warnings' => $this->score->warning_count( $counts ),
			'passes'   => $passes,
		);

		$this->scans->complete( $scan_id, $totals );

		wp_send_json_success(
			array(
				'score'  => $score,
				'band'   => $this->score->band( $score ),
				'totals' => $totals,
				'counts' => $counts,
			)
		);
	}

	/**
	 * Report the current progress of a scan.
	 */
	public function handle_status(): void {
		$this->guard();

		$scan_id = isset( $_GET['scan_id'] ) ? absint( wp_unslash( (string) $_GET['scan_id'] ) ) : 0;
		$scan    = $this->scans->find( $scan_id );

		if ( null === $scan ) {
			wp_send_json_error( array( 'message' => __( 'Scan not found.', 'accessibility-guardian' ) ), 404 );
		}

		wp_send_json_success(
			array(
				'status'       => $scan['status'],
				'total_urls'   => (int) $scan['total_urls'],
				'scanned_urls' => (int) $scan['scanned_urls'],
				'score'        => (int) $scan['score'],
			)
		);
	}

	/**
	 * Verify nonce and capability for every request.
	 */
	private function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to run scans.', 'accessibility-guardian' ) ), 403 );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}
}
