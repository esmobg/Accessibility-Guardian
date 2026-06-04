<?php
/**
 * Tests for the score calculator.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Tests;

use AccessibilityGuardian\Scan\ScoreCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AccessibilityGuardian\Scan\ScoreCalculator
 */
final class ScoreCalculatorTest extends TestCase {

	private ScoreCalculator $calculator;

	protected function setUp(): void {
		$this->calculator = new ScoreCalculator();
	}

	public function test_perfect_score_with_no_issues(): void {
		$score = $this->calculator->calculate(
			array(
				'critical' => 0,
				'major'    => 0,
				'minor'    => 0,
				'warning'  => 0,
			)
		);

		$this->assertSame( 100, $score );
	}

	public function test_weighted_penalties_are_applied(): void {
		// 1 critical (-10) + 2 major (-10) + 3 minor (-6) + 4 warning (-4) = -30.
		$score = $this->calculator->calculate(
			array(
				'critical' => 1,
				'major'    => 2,
				'minor'    => 3,
				'warning'  => 4,
			)
		);

		$this->assertSame( 70, $score );
	}

	public function test_score_is_clamped_to_zero(): void {
		$score = $this->calculator->calculate( array( 'critical' => 50 ) );

		$this->assertSame( 0, $score );
	}

	public function test_error_and_warning_counts(): void {
		$counts = array(
			'critical' => 2,
			'major'    => 3,
			'minor'    => 4,
			'warning'  => 5,
		);

		$this->assertSame( 5, $this->calculator->error_count( $counts ) );
		$this->assertSame( 9, $this->calculator->warning_count( $counts ) );
	}

	/**
	 * @dataProvider band_provider
	 */
	public function test_band_thresholds( int $score, string $expected_key ): void {
		$this->assertSame( $expected_key, $this->calculator->band( $score )['key'] );
	}

	/**
	 * @return array<string, array{0:int,1:string}>
	 */
	public static function band_provider(): array {
		return array(
			'excellent'         => array( 96, 'excellent' ),
			'excellent edge'    => array( 95, 'excellent' ),
			'good'              => array( 80, 'good' ),
			'needs improvement' => array( 60, 'needs-improvement' ),
			'poor'              => array( 59, 'poor' ),
		);
	}
}
