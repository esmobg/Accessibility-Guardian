<?php
/**
 * Data access layer for accessibility issue records.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Storage;

defined( 'ABSPATH' ) || exit;

// Custom accg_issues table; identifiers are $wpdb->prefix plus a hardcoded suffix.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/**
 * Reads and writes issue rows in the accg_issues table.
 */
final class IssueRepository {

	/**
	 * Fully-qualified table name.
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'accg_issues';
	}

	/**
	 * Insert a batch of normalized issues for a scan.
	 *
	 * @param int                                   $scan_id Scan id.
	 * @param array<int, array<string, mixed>>      $issues  Normalized issue rows.
	 * @return int Number of rows inserted.
	 */
	public function insert_batch( int $scan_id, array $issues ): int {
		global $wpdb;

		$count = 0;
		$now   = current_time( 'mysql' );

		foreach ( $issues as $issue ) {
			$inserted = $wpdb->insert(
				$this->table,
				array(
					'scan_id'        => $scan_id,
					'url'            => esc_url_raw( (string) ( $issue['url'] ?? '' ) ),
					'post_id'        => (int) ( $issue['post_id'] ?? 0 ),
					'rule_id'        => sanitize_key( (string) ( $issue['rule_id'] ?? '' ) ),
					'wcag_ref'       => sanitize_text_field( (string) ( $issue['wcag_ref'] ?? '' ) ),
					'severity'       => sanitize_key( (string) ( $issue['severity'] ?? 'minor' ) ),
					'category'       => sanitize_key( (string) ( $issue['category'] ?? '' ) ),
					'impact'         => sanitize_text_field( (string) ( $issue['impact'] ?? '' ) ),
					'message'        => sanitize_textarea_field( (string) ( $issue['message'] ?? '' ) ),
					'html_snippet'   => wp_kses_post( (string) ( $issue['html_snippet'] ?? '' ) ),
					'dom_path'       => sanitize_text_field( (string) ( $issue['dom_path'] ?? '' ) ),
					'fix_suggestion' => sanitize_textarea_field( (string) ( $issue['fix_suggestion'] ?? '' ) ),
					'doc_link'       => esc_url_raw( (string) ( $issue['doc_link'] ?? '' ) ),
					'status'         => (string) ( $issue['status'] ?? 'open' ),
					'created_at'     => $now,
				),
				array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( $inserted ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Count issues for a scan grouped by severity.
	 *
	 * @param int $scan_id Scan id.
	 * @return array<string, int>
	 */
	public function severity_counts( int $scan_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT severity, COUNT(*) AS total FROM {$this->table} WHERE scan_id = %d GROUP BY severity", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scan_id
			),
			ARRAY_A
		);

		$counts = array(
			'critical' => 0,
			'major'    => 0,
			'minor'    => 0,
			'warning'  => 0,
		);

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$counts[ (string) $row['severity'] ] = (int) $row['total'];
			}
		}

		return $counts;
	}

	/**
	 * Count issues for a scan grouped by category.
	 *
	 * @param int $scan_id Scan id.
	 * @return array<string, int>
	 */
	public function category_counts( int $scan_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category, COUNT(*) AS total FROM {$this->table} WHERE scan_id = %d GROUP BY category ORDER BY total DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scan_id
			),
			ARRAY_A
		);

		$counts = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$counts[ (string) $row['category'] ] = (int) $row['total'];
			}
		}

		return $counts;
	}

	/**
	 * Get the most common rules across a scan.
	 *
	 * @param int $scan_id Scan id.
	 * @param int $limit   Maximum rules.
	 * @return array<int, array<string, mixed>>
	 */
	public function top_rules( int $scan_id, int $limit = 10 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT rule_id, wcag_ref, severity, COUNT(*) AS total FROM {$this->table} WHERE scan_id = %d GROUP BY rule_id, wcag_ref, severity ORDER BY total DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scan_id,
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Retrieve issues for a scan with pagination.
	 *
	 * @param int    $scan_id  Scan id.
	 * @param int    $per_page Items per page.
	 * @param int    $page     Page number (1-based).
	 * @param string $severity Optional severity filter.
	 * @return array<int, array<string, mixed>>
	 */
	public function for_scan( int $scan_id, int $per_page = 50, int $page = 1, string $severity = '' ): array {
		global $wpdb;

		$offset = max( 0, ( $page - 1 ) * $per_page );

		if ( '' !== $severity ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} WHERE scan_id = %d AND severity = %s ORDER BY FIELD(severity,'critical','major','minor','warning'), id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$scan_id,
					$severity,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} WHERE scan_id = %d ORDER BY FIELD(severity,'critical','major','minor','warning'), id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$scan_id,
					$per_page,
					$offset
				),
				ARRAY_A
			);
		}

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Total issue count for a scan.
	 *
	 * @param int $scan_id Scan id.
	 */
	public function count_for_scan( int $scan_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE scan_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$scan_id
			)
		);
	}
}
