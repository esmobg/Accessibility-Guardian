<?php
/**
 * Data access layer for scan records.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes scan rows in the ag_scans table.
 */
final class ScanRepository {

	/**
	 * Fully-qualified table name.
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ag_scans';
	}

	/**
	 * Create a new scan record.
	 *
	 * @param string $scan_type  Scan type (single|full).
	 * @param int    $total_urls Number of URLs queued.
	 * @return int New scan id (0 on failure).
	 */
	public function create( string $scan_type, int $total_urls ): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table,
			array(
				'scan_type'  => $scan_type,
				'status'     => 'running',
				'started_at' => current_time( 'mysql' ),
				'total_urls' => $total_urls,
			),
			array( '%s', '%s', '%s', '%d' )
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Increment the scanned-url counter for a scan.
	 *
	 * @param int $scan_id Scan id.
	 * @param int $by      Amount to increment by.
	 */
	public function increment_scanned( int $scan_id, int $by = 1 ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET scanned_urls = scanned_urls + %d WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$by,
				$scan_id
			)
		);
	}

	/**
	 * Finalize a scan with aggregate metrics.
	 *
	 * @param int   $scan_id Scan id.
	 * @param array{score:int,errors:int,warnings:int,passes:int} $totals Aggregate counts.
	 */
	public function complete( int $scan_id, array $totals ): void {
		global $wpdb;

		$wpdb->update(
			$this->table,
			array(
				'status'      => 'complete',
				'finished_at' => current_time( 'mysql' ),
				'score'       => (int) $totals['score'],
				'errors'      => (int) $totals['errors'],
				'warnings'    => (int) $totals['warnings'],
				'passes'      => (int) $totals['passes'],
			),
			array( 'id' => $scan_id ),
			array( '%s', '%s', '%d', '%d', '%d', '%d' ),
			array( '%d' )
		);

		$history = $wpdb->prefix . 'ag_history';
		$wpdb->insert(
			$history,
			array(
				'scan_id'    => $scan_id,
				'score'      => (int) $totals['score'],
				'errors'     => (int) $totals['errors'],
				'warnings'   => (int) $totals['warnings'],
				'passes'     => (int) $totals['passes'],
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%d', '%d', '%s' )
		);
	}

	/**
	 * Mark a scan as failed.
	 *
	 * @param int $scan_id Scan id.
	 */
	public function fail( int $scan_id ): void {
		global $wpdb;

		$wpdb->update(
			$this->table,
			array(
				'status'      => 'failed',
				'finished_at' => current_time( 'mysql' ),
			),
			array( 'id' => $scan_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Fetch a single scan row.
	 *
	 * @param int $scan_id Scan id.
	 * @return array<string, mixed>|null
	 */
	public function find( int $scan_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $scan_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get the most recent completed scan.
	 *
	 * @return array<string, mixed>|null
	 */
	public function latest(): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			"SELECT * FROM {$this->table} WHERE status = 'complete' ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Get recent scans for the dashboard listing.
	 *
	 * @param int $limit Maximum rows.
	 * @return array<int, array<string, mixed>>
	 */
	public function recent( int $limit = 10 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get score history points ordered chronologically.
	 *
	 * @param int $limit Maximum points.
	 * @return array<int, array<string, mixed>>
	 */
	public function history( int $limit = 30 ): array {
		global $wpdb;

		$history = $wpdb->prefix . 'ag_history';
		$rows    = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$history} ORDER BY id DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_reverse( $rows );
	}
}
