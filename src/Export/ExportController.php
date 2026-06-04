<?php
/**
 * Handles report download requests via admin-post.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Export;

use AccessibilityGuardian\Storage\IssueRepository;
use AccessibilityGuardian\Storage\ScanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Streams CSV/JSON exports for a scan.
 */
final class ExportController {

	/**
	 * Maximum number of issues to include in a single export.
	 */
	private const EXPORT_LIMIT = 10000;

	/**
	 * Issue repository.
	 */
	private IssueRepository $issues;

	/**
	 * Scan repository.
	 */
	private ScanRepository $scans;

	/**
	 * Constructor.
	 *
	 * @param IssueRepository $issues Issue repository.
	 * @param ScanRepository  $scans  Scan repository.
	 */
	public function __construct( IssueRepository $issues, ScanRepository $scans ) {
		$this->issues = $issues;
		$this->scans  = $scans;
	}

	/**
	 * Register the admin-post hook.
	 */
	public function register(): void {
		add_action( 'admin_post_ag_export', array( $this, 'handle' ) );
	}

	/**
	 * Validate the request and stream the requested format.
	 */
	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to export reports.', 'accessibility-guardian' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'ag_export' );

		$scan_id = isset( $_GET['scan_id'] ) ? absint( wp_unslash( (string) $_GET['scan_id'] ) ) : 0;
		$format  = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( (string) $_GET['format'] ) ) : 'csv';

		$scan = $this->scans->find( $scan_id );

		if ( null === $scan ) {
			wp_die( esc_html__( 'Scan not found.', 'accessibility-guardian' ), '', array( 'response' => 404 ) );
		}

		$issues = $this->issues->for_scan( $scan_id, self::EXPORT_LIMIT, 1 );

		if ( 'json' === $format ) {
			$this->stream_json( $scan_id, $scan, $issues );
		}

		$this->stream_csv( $scan_id, $issues );
	}

	/**
	 * Send the CSV download.
	 *
	 * @param int                              $scan_id Scan id.
	 * @param array<int, array<string, mixed>> $issues  Issue rows.
	 */
	private function stream_csv( int $scan_id, array $issues ): void {
		$exporter = new CsvExporter();
		$body     = $exporter->build( $issues );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $exporter->filename( $scan_id ) . '"' );
		header( 'Content-Length: ' . strlen( $body ) );

		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Send the JSON download.
	 *
	 * @param int                              $scan_id Scan id.
	 * @param array<string, mixed>             $scan    Scan record.
	 * @param array<int, array<string, mixed>> $issues  Issue rows.
	 */
	private function stream_json( int $scan_id, array $scan, array $issues ): void {
		$exporter = new JsonExporter();
		$body     = $exporter->build( $scan, $issues );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $exporter->filename( $scan_id ) . '"' );
		header( 'Content-Length: ' . strlen( $body ) );

		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
