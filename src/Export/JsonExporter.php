<?php
/**
 * JSON report exporter.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Export;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a structured JSON report payload.
 */
final class JsonExporter {

	/**
	 * Build the JSON body for a scan and its issues.
	 *
	 * @param array<string, mixed>             $scan   Scan record.
	 * @param array<int, array<string, mixed>> $issues Issue rows.
	 * @return string JSON content.
	 */
	public function build( array $scan, array $issues ): string {
		$payload = array(
			'generator' => 'Accessibility Guardian',
			'version'   => ACCG_VERSION,
			'generated' => gmdate( 'c' ),
			'scan'      => array(
				'id'           => (int) ( $scan['id'] ?? 0 ),
				'type'         => (string) ( $scan['scan_type'] ?? '' ),
				'score'        => (int) ( $scan['score'] ?? 0 ),
				'errors'       => (int) ( $scan['errors'] ?? 0 ),
				'warnings'     => (int) ( $scan['warnings'] ?? 0 ),
				'passes'       => (int) ( $scan['passes'] ?? 0 ),
				'total_urls'   => (int) ( $scan['total_urls'] ?? 0 ),
				'started_at'   => (string) ( $scan['started_at'] ?? '' ),
				'finished_at'  => (string) ( $scan['finished_at'] ?? '' ),
			),
			'issues'    => array_map(
				static fn ( array $issue ): array => array(
					'id'             => (int) ( $issue['id'] ?? 0 ),
					'rule_id'        => (string) ( $issue['rule_id'] ?? '' ),
					'wcag_ref'       => (string) ( $issue['wcag_ref'] ?? '' ),
					'url'            => (string) ( $issue['url'] ?? '' ),
					'severity'       => (string) ( $issue['severity'] ?? '' ),
					'category'       => (string) ( $issue['category'] ?? '' ),
					'message'        => (string) ( $issue['message'] ?? '' ),
					'html_snippet'   => (string) ( $issue['html_snippet'] ?? '' ),
					'dom_path'       => (string) ( $issue['dom_path'] ?? '' ),
					'fix_suggestion' => (string) ( $issue['fix_suggestion'] ?? '' ),
					'doc_link'       => (string) ( $issue['doc_link'] ?? '' ),
					'status'         => (string) ( $issue['status'] ?? '' ),
				),
				$issues
			),
		);

		return (string) wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Suggested download filename for a scan.
	 *
	 * @param int $scan_id Scan id.
	 */
	public function filename( int $scan_id ): string {
		return sprintf( 'accessibility-guardian-scan-%d-%s.json', $scan_id, gmdate( 'Ymd-His' ) );
	}
}
