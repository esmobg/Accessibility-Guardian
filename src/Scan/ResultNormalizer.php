<?php
/**
 * Normalizes raw axe-core results into storable issue rows.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Scan;

use AccessibilityGuardian\Rules\RuleCatalog;

defined( 'ABSPATH' ) || exit;

/**
 * Converts the JSON payload produced by the browser scanner into the
 * canonical issue schema used by the storage layer.
 */
final class ResultNormalizer {

	/**
	 * Rule metadata catalog.
	 */
	private RuleCatalog $catalog;

	/**
	 * Constructor.
	 *
	 * @param RuleCatalog $catalog Rule metadata catalog.
	 */
	public function __construct( RuleCatalog $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * Normalize a single URL's scan payload into issue rows.
	 *
	 * @param array<string, mixed> $payload Decoded payload for one URL.
	 * @return array<int, array<string, mixed>>
	 */
	public function normalize( array $payload ): array {
		$url     = isset( $payload['url'] ) ? esc_url_raw( (string) $payload['url'] ) : '';
		$post_id = isset( $payload['post_id'] ) ? absint( $payload['post_id'] ) : 0;

		$issues = array();

		foreach ( $this->extract_results( $payload, 'violations' ) as $result ) {
			foreach ( $this->build_rows( $result, $url, $post_id, false ) as $row ) {
				$issues[] = $row;
			}
		}

		foreach ( $this->extract_results( $payload, 'incomplete' ) as $result ) {
			foreach ( $this->build_rows( $result, $url, $post_id, true ) as $row ) {
				$issues[] = $row;
			}
		}

		return $issues;
	}

	/**
	 * Build issue rows from a single axe result group.
	 *
	 * @param array<string, mixed> $result        One axe result (rule with nodes).
	 * @param string               $url           Page URL.
	 * @param int                  $post_id       Post id.
	 * @param bool                 $is_incomplete Whether this is a "needs review" item.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_rows( array $result, string $url, int $post_id, bool $is_incomplete ): array {
		$rule_id = isset( $result['id'] ) ? sanitize_key( (string) $result['id'] ) : '';
		$impact  = isset( $result['impact'] ) ? sanitize_text_field( (string) $result['impact'] ) : '';
		$help    = isset( $result['help'] ) ? sanitize_text_field( (string) $result['help'] ) : '';

		$meta     = $this->catalog->get( $rule_id, $impact );
		$severity = $is_incomplete ? 'warning' : $meta['severity'];

		$nodes = isset( $result['nodes'] ) && is_array( $result['nodes'] ) ? $result['nodes'] : array();

		// A result with no nodes still represents a page-level issue.
		if ( empty( $nodes ) ) {
			$nodes = array( array() );
		}

		$rows = array();

		foreach ( $nodes as $node ) {
			$node    = is_array( $node ) ? $node : array();
			$snippet = isset( $node['html'] ) ? (string) $node['html'] : '';
			$target  = $this->target_to_path( $node['target'] ?? array() );
			$summary = isset( $node['failureSummary'] ) ? (string) $node['failureSummary'] : '';

			$message = '' !== $help ? $help : $summary;
			if ( $is_incomplete ) {
				$message = sprintf(
					/* translators: %s: rule description. */
					__( 'Needs manual review: %s', 'accessibility-guardian' ),
					$message
				);
			}

			$rows[] = array(
				'url'            => $url,
				'post_id'        => $post_id,
				'rule_id'        => $rule_id,
				'wcag_ref'       => $meta['wcag'],
				'severity'       => $severity,
				'category'       => $meta['category'],
				'impact'         => $impact,
				'message'        => wp_strip_all_tags( $message ),
				'html_snippet'   => $this->truncate( $snippet, 2000 ),
				'dom_path'       => $this->truncate( $target, 1000 ),
				'fix_suggestion' => $meta['fix'],
				'doc_link'       => $meta['doc'],
				'status'         => 'open',
			);
		}

		return $rows;
	}

	/**
	 * Aggregate severity counts for a set of normalized issue rows.
	 *
	 * @param array<int, array<string, mixed>> $issues Normalized rows.
	 * @return array<string, int>
	 */
	public function severity_counts( array $issues ): array {
		$counts = array(
			'critical' => 0,
			'major'    => 0,
			'minor'    => 0,
			'warning'  => 0,
		);

		foreach ( $issues as $issue ) {
			$severity = (string) ( $issue['severity'] ?? 'minor' );
			if ( isset( $counts[ $severity ] ) ) {
				++$counts[ $severity ];
			}
		}

		return $counts;
	}

	/**
	 * Extract a results group from the payload.
	 *
	 * @param array<string, mixed> $payload Decoded payload.
	 * @param string               $key     Group key (violations|incomplete).
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_results( array $payload, string $key ): array {
		if ( ! isset( $payload[ $key ] ) || ! is_array( $payload[ $key ] ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$payload[ $key ],
				static fn ( $item ): bool => is_array( $item )
			)
		);
	}

	/**
	 * Convert an axe target (array of selectors) into a readable DOM path.
	 *
	 * @param mixed $target axe target value.
	 */
	private function target_to_path( mixed $target ): string {
		if ( is_array( $target ) ) {
			$parts = array();
			foreach ( $target as $part ) {
				if ( is_array( $part ) ) {
					$parts[] = implode( ' ', array_map( 'strval', $part ) );
				} else {
					$parts[] = (string) $part;
				}
			}

			return sanitize_text_field( implode( ' > ', $parts ) );
		}

		return sanitize_text_field( (string) $target );
	}

	/**
	 * Truncate a string to a maximum character length without splitting
	 * multibyte characters (which would produce invalid UTF-8 that strict
	 * MySQL modes reject, silently dropping the whole issue row).
	 *
	 * @param string $value  Source string.
	 * @param int    $length Maximum length in characters.
	 */
	private function truncate( string $value, int $length ): string {
		// WordPress core polyfills mb_substr(), so it is always available.
		if ( mb_strlen( $value, 'UTF-8' ) <= $length ) {
			return $value;
		}

		return mb_substr( $value, 0, $length, 'UTF-8' ) . '...';
	}
}
