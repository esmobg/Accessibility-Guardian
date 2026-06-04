<?php
/**
 * Applies optional one-click accessibility fixes on the front end.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Fixes;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end remediation layer. Each fix is opt-in via plugin settings and is
 * applied through WordPress hooks rather than by modifying theme files.
 */
final class AutoFixer {

	/**
	 * Enabled fix flags keyed by fix id.
	 *
	 * @var array<string, bool>
	 */
	private array $fixes;

	/**
	 * Constructor: read the enabled fixes from settings.
	 */
	public function __construct() {
		$settings    = (array) get_option( 'ag_settings', array() );
		$raw         = isset( $settings['fixes'] ) && is_array( $settings['fixes'] ) ? $settings['fixes'] : array();
		$this->fixes = array_map( static fn ( $v ): bool => (bool) $v, $raw );
	}

	/**
	 * Register front-end hooks for the enabled fixes.
	 */
	public function register(): void {
		if ( is_admin() ) {
			return;
		}

		if ( ! $this->any_enabled() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );

		if ( $this->on( 'add_skip_link' ) ) {
			add_action( 'wp_body_open', array( $this, 'render_skip_link' ), 1 );
		}

		if ( $this->on( 'label_search_form' ) ) {
			add_filter( 'get_search_form', array( $this, 'label_search_form' ) );
		}
	}

	/**
	 * Whether a given fix is enabled.
	 *
	 * @param string $key Fix id.
	 */
	public function on( string $key ): bool {
		return ! empty( $this->fixes[ $key ] );
	}

	/**
	 * Whether at least one fix is enabled.
	 */
	private function any_enabled(): bool {
		foreach ( $this->fixes as $enabled ) {
			if ( $enabled ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue the front-end fix assets and pass the active flags to JS.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'ag-frontend-fixes',
			AG_PLUGIN_URL . 'assets/css/frontend-fixes.css',
			array(),
			AG_VERSION
		);

		wp_enqueue_script(
			'ag-frontend-fixes',
			AG_PLUGIN_URL . 'assets/js/frontend-fixes.js',
			array(),
			AG_VERSION,
			true
		);

		wp_localize_script(
			'ag-frontend-fixes',
			'agFixes',
			array(
				'flags' => array(
					'ensureHtmlLang'        => $this->on( 'add_html_lang' ),
					'newWindowWarning'      => $this->on( 'new_window_warning' ),
					'fixViewport'           => $this->on( 'fix_viewport' ),
					'removePositiveTabindex' => $this->on( 'remove_positive_tabindex' ),
					'removeTitleAttr'       => $this->on( 'remove_title_attr' ),
				),
				'lang'  => str_replace( '_', '-', get_bloginfo( 'language' ) ),
				'i18n'  => array(
					'newWindow' => __( '(opens in a new window)', 'accessibility-guardian' ),
				),
			)
		);
	}

	/**
	 * Add body classes that enable CSS-only fixes.
	 *
	 * @param array<int, string> $classes Existing body classes.
	 * @return array<int, string>
	 */
	public function add_body_classes( array $classes ): array {
		if ( $this->on( 'add_focus_outline' ) ) {
			$classes[] = 'ag-fix-focus-outline';
		}

		if ( $this->on( 'underline_links' ) ) {
			$classes[] = 'ag-fix-underline-links';
		}

		return $classes;
	}

	/**
	 * Output a skip-to-content link at the top of the page.
	 */
	public function render_skip_link(): void {
		printf(
			'<a class="ag-skip-link screen-reader-text" href="#content">%s</a>',
			esc_html__( 'Skip to content', 'accessibility-guardian' )
		);
	}

	/**
	 * Ensure the default search form field has an accessible label.
	 *
	 * @param string $form Search form markup.
	 */
	public function label_search_form( string $form ): string {
		if ( false !== strpos( $form, 'aria-label' ) ) {
			return $form;
		}

		return str_replace(
			'type="search"',
			'type="search" aria-label="' . esc_attr__( 'Search', 'accessibility-guardian' ) . '"',
			$form
		);
	}
}
