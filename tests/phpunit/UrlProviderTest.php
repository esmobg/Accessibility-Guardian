<?php
/**
 * Tests for the URL provider post-type resolution.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Tests;

use AccessibilityGuardian\Scan\UrlProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AccessibilityGuardian\Scan\UrlProvider
 */
final class UrlProviderTest extends TestCase {

	private UrlProvider $provider;

	protected function setUp(): void {
		$this->provider                    = new UrlProvider();
		$GLOBALS['ag_test_options']        = array();
		$GLOBALS['ag_test_post_types']     = array( 'post', 'page', 'product' );
	}

	public function test_returns_all_public_types_when_unconfigured(): void {
		$types = $this->provider->scannable_post_types();

		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
		$this->assertContains( 'product', $types );
	}

	public function test_respects_configured_post_types(): void {
		$GLOBALS['ag_test_options']['ag_settings'] = array(
			'include_post_types' => array( 'page', 'product' ),
		);

		$types = $this->provider->scannable_post_types();

		$this->assertEqualsCanonicalizing( array( 'page', 'product' ), $types );
		$this->assertNotContains( 'post', $types );
	}

	public function test_ignores_configured_types_that_are_not_public(): void {
		$GLOBALS['ag_test_options']['ag_settings'] = array(
			'include_post_types' => array( 'page', 'nonexistent' ),
		);

		$types = $this->provider->scannable_post_types();

		$this->assertSame( array( 'page' ), $types );
	}
}
