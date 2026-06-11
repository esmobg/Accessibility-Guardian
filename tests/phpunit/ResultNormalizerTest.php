<?php
/**
 * Tests for the result normalizer.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Tests;

use AccessibilityGuardian\Rules\RuleCatalog;
use AccessibilityGuardian\Scan\ResultNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AccessibilityGuardian\Scan\ResultNormalizer
 */
final class ResultNormalizerTest extends TestCase {

	private ResultNormalizer $normalizer;

	protected function setUp(): void {
		$this->normalizer = new ResultNormalizer( new RuleCatalog() );
	}

	public function test_violation_is_normalized_to_issue_row(): void {
		$payload = array(
			'url'        => 'https://example.com/page',
			'post_id'    => 12,
			'violations' => array(
				array(
					'id'     => 'image-alt',
					'impact' => 'critical',
					'help'   => 'Images must have alternate text',
					'nodes'  => array(
						array(
							'html'           => '<img src="banner.jpg">',
							'target'         => array( 'img.banner' ),
							'failureSummary' => 'Fix this',
						),
					),
				),
			),
		);

		$issues = $this->normalizer->normalize( $payload );

		$this->assertCount( 1, $issues );
		$issue = $issues[0];

		$this->assertSame( 'image-alt', $issue['rule_id'] );
		$this->assertSame( 'critical', $issue['severity'] );
		$this->assertSame( 'images', $issue['category'] );
		$this->assertSame( 12, $issue['post_id'] );
		$this->assertStringContainsString( '1.1.1', $issue['wcag_ref'] );
		$this->assertSame( 'img.banner', $issue['dom_path'] );
		$this->assertSame( 'open', $issue['status'] );
	}

	public function test_incomplete_results_are_marked_as_warning(): void {
		$payload = array(
			'url'        => 'https://example.com/',
			'post_id'    => 0,
			'incomplete' => array(
				array(
					'id'     => 'color-contrast',
					'impact' => 'serious',
					'help'   => 'Elements must meet contrast ratio',
					'nodes'  => array(
						array(
							'html'   => '<span>Hi</span>',
							'target' => array( 'span' ),
						),
					),
				),
			),
		);

		$issues = $this->normalizer->normalize( $payload );

		$this->assertCount( 1, $issues );
		$this->assertSame( 'warning', $issues[0]['severity'] );
		$this->assertStringContainsString( 'Needs manual review', $issues[0]['message'] );
	}

	public function test_unknown_rule_falls_back_to_impact_severity(): void {
		$payload = array(
			'url'        => 'https://example.com/',
			'violations' => array(
				array(
					'id'     => 'some-future-rule',
					'impact' => 'serious',
					'help'   => 'Future rule',
					'nodes'  => array(
						array(
							'html'   => '<div></div>',
							'target' => array( 'div' ),
						),
					),
				),
			),
		);

		$issues = $this->normalizer->normalize( $payload );

		$this->assertSame( 'major', $issues[0]['severity'] );
	}

	public function test_long_multibyte_snippet_is_truncated_to_valid_utf8(): void {
		// 3000 Cyrillic characters = 6000 bytes; must be cut on a character
		// boundary so MySQL strict mode does not reject the row.
		$snippet = '<p>' . str_repeat( 'я', 3000 ) . '</p>';

		$payload = array(
			'url'        => 'https://example.com/',
			'violations' => array(
				array(
					'id'     => 'image-alt',
					'impact' => 'critical',
					'help'   => 'Help',
					'nodes'  => array(
						array(
							'html'   => $snippet,
							'target' => array( 'p' ),
						),
					),
				),
			),
		);

		$issues = $this->normalizer->normalize( $payload );
		$stored = $issues[0]['html_snippet'];

		$this->assertSame( 2003, mb_strlen( $stored, 'UTF-8' ) ); // 2000 chars + '...'.
		$this->assertTrue( (bool) mb_check_encoding( $stored, 'UTF-8' ) );
		$this->assertStringEndsWith( 'я...', $stored );
	}

	public function test_severity_counts_aggregate_correctly(): void {
		$issues = array(
			array( 'severity' => 'critical' ),
			array( 'severity' => 'critical' ),
			array( 'severity' => 'minor' ),
			array( 'severity' => 'warning' ),
		);

		$counts = $this->normalizer->severity_counts( $issues );

		$this->assertSame( 2, $counts['critical'] );
		$this->assertSame( 0, $counts['major'] );
		$this->assertSame( 1, $counts['minor'] );
		$this->assertSame( 1, $counts['warning'] );
	}
}
