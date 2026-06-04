<?php
/**
 * CSV report exporter.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Streams issue rows as a CSV download.
 */
final class CsvExporter {

	/**
	 * Column headers in output order.
	 *
	 * @var array<int, string>
	 */
	private const COLUMNS = array(
		'id',
		'rule_id',
		'wcag_ref',
		'url',
		'severity',
		'category',
		'message',
		'html_snippet',
		'dom_path',
		'fix_suggestion',
		'doc_link',
		'status',
	);

	/**
	 * Build the CSV body for a set of issue rows.
	 *
	 * @param array<int, array<string, mixed>> $issues Issue rows.
	 * @return string CSV content.
	 */
	public function build( array $issues ): string {
		$stream = fopen( 'php://temp', 'r+' );

		fputcsv( $stream, self::COLUMNS );

		foreach ( $issues as $issue ) {
			$row = array();
			foreach ( self::COLUMNS as $column ) {
				$row[] = (string) ( $issue[ $column ] ?? '' );
			}
			fputcsv( $stream, $row );
		}

		rewind( $stream );
		$content = (string) stream_get_contents( $stream );
		fclose( $stream );

		return $content;
	}

	/**
	 * Suggested download filename for a scan.
	 *
	 * @param int $scan_id Scan id.
	 */
	public function filename( int $scan_id ): string {
		return sprintf( 'accessibility-guardian-scan-%d-%s.csv', $scan_id, gmdate( 'Ymd-His' ) );
	}
}
