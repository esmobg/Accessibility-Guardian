<?php
/**
 * Accessibility score calculation.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Scan;

defined( 'ABSPATH' ) || exit;

/**
 * Computes the 0-100 accessibility score from severity counts.
 */
final class ScoreCalculator {

	/**
	 * Penalty weights per severity bucket.
	 */
	private const WEIGHTS = array(
		'critical' => 10,
		'major'    => 5,
		'minor'    => 2,
		'warning'  => 1,
	);

	/**
	 * Maximum possible score.
	 */
	public const MAX_SCORE = 100;

	/**
	 * Calculate the score for a set of severity counts.
	 *
	 * @param array<string, int> $counts Counts keyed by severity.
	 * @return int Score clamped between 0 and 100.
	 */
	public function calculate( array $counts ): int {
		$penalty = 0;

		foreach ( self::WEIGHTS as $severity => $weight ) {
			$penalty += (int) ( $counts[ $severity ] ?? 0 ) * $weight;
		}

		$score = self::MAX_SCORE - $penalty;

		return (int) max( 0, min( self::MAX_SCORE, $score ) );
	}

	/**
	 * Number of "error" issues (critical + major) from counts.
	 *
	 * @param array<string, int> $counts Counts keyed by severity.
	 */
	public function error_count( array $counts ): int {
		return (int) ( $counts['critical'] ?? 0 ) + (int) ( $counts['major'] ?? 0 );
	}

	/**
	 * Number of "warning" issues (minor + warning) from counts.
	 *
	 * @param array<string, int> $counts Counts keyed by severity.
	 */
	public function warning_count( array $counts ): int {
		return (int) ( $counts['minor'] ?? 0 ) + (int) ( $counts['warning'] ?? 0 );
	}

	/**
	 * Resolve the qualitative band for a score.
	 *
	 * @param int $score Score value.
	 * @return array{label:string,key:string}
	 */
	public function band( int $score ): array {
		if ( $score >= 95 ) {
			return array(
				'key'   => 'excellent',
				'label' => __( 'Excellent', 'accessibility-guardian' ),
			);
		}

		if ( $score >= 80 ) {
			return array(
				'key'   => 'good',
				'label' => __( 'Good', 'accessibility-guardian' ),
			);
		}

		if ( $score >= 60 ) {
			return array(
				'key'   => 'needs-improvement',
				'label' => __( 'Needs Improvement', 'accessibility-guardian' ),
			);
		}

		return array(
			'key'   => 'poor',
			'label' => __( 'Poor', 'accessibility-guardian' ),
		);
	}
}
