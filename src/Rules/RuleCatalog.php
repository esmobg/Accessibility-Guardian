<?php
/**
 * WCAG rule metadata catalog.
 *
 * Maps axe-core rule identifiers (and supplemental custom rules) to
 * human-readable WCAG references, categories, default severities,
 * remediation guidance and documentation links.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Provides metadata for every supported accessibility rule.
 */
final class RuleCatalog {

	/**
	 * Cached rule map.
	 *
	 * @var array<string, array{wcag:string,category:string,severity:string,fix:string,doc:string}>|null
	 */
	private ?array $map = null;

	/**
	 * Get metadata for a single rule id, or a sensible default.
	 *
	 * @param string $rule_id    axe-core or custom rule id.
	 * @param string $impact     axe impact level used to derive a fallback severity.
	 * @return array{wcag:string,category:string,severity:string,fix:string,doc:string}
	 */
	public function get( string $rule_id, string $impact = '' ): array {
		$map = $this->all();

		if ( isset( $map[ $rule_id ] ) ) {
			return $map[ $rule_id ];
		}

		return array(
			'wcag'     => __( 'Best practice', 'accessibility-guardian' ),
			'category' => 'general',
			'severity' => $this->severity_from_impact( $impact ),
			'fix'      => __( 'Review this element against WCAG 2.2 guidelines and apply the recommended remediation.', 'accessibility-guardian' ),
			'doc'      => 'https://dequeuniversity.com/rules/axe/4.10/' . rawurlencode( $rule_id ),
		);
	}

	/**
	 * Catalog of available one-click automatic fixes.
	 *
	 * Each entry: key => [ label, description ]. The key is also the settings
	 * flag stored under accg_settings['fixes'].
	 *
	 * @return array<string, array{label:string,description:string}>
	 */
	public function fixes(): array {
		return array(
			'add_html_lang'           => array(
				'label'       => __( 'Add missing language attribute', 'accessibility-guardian' ),
				'description' => __( 'Sets a lang attribute on the <html> element when one is missing (WCAG 3.1.1).', 'accessibility-guardian' ),
			),
			'add_skip_link'           => array(
				'label'       => __( 'Add a skip-to-content link', 'accessibility-guardian' ),
				'description' => __( 'Inserts a keyboard skip link at the top of every page (WCAG 2.4.1).', 'accessibility-guardian' ),
			),
			'add_focus_outline'       => array(
				'label'       => __( 'Add a visible focus outline', 'accessibility-guardian' ),
				'description' => __( 'Ensures focused links and controls show a clear outline (WCAG 2.4.7).', 'accessibility-guardian' ),
			),
			'underline_links'         => array(
				'label'       => __( 'Underline links in content', 'accessibility-guardian' ),
				'description' => __( 'Underlines in-content links so they are not distinguished by color alone (WCAG 1.4.1).', 'accessibility-guardian' ),
			),
			'new_window_warning'      => array(
				'label'       => __( 'Warn about links opening new windows', 'accessibility-guardian' ),
				'description' => __( 'Appends a hidden "(opens in a new window)" notice to target="_blank" links (WCAG 3.2.5).', 'accessibility-guardian' ),
			),
			'fix_viewport'            => array(
				'label'       => __( 'Allow zooming (fix viewport)', 'accessibility-guardian' ),
				'description' => __( 'Removes user-scalable=no and maximum-scale limits so users can zoom (WCAG 1.4.4).', 'accessibility-guardian' ),
			),
			'remove_positive_tabindex' => array(
				'label'       => __( 'Remove positive tabindex values', 'accessibility-guardian' ),
				'description' => __( 'Resets tabindex values greater than 0 to keep a natural focus order (WCAG 2.4.3).', 'accessibility-guardian' ),
			),
			'label_search_form'       => array(
				'label'       => __( 'Add labels to search forms', 'accessibility-guardian' ),
				'description' => __( 'Adds an accessible label to the default search form field (WCAG 3.3.2).', 'accessibility-guardian' ),
			),
			'remove_title_attr'       => array(
				'label'       => __( 'Remove redundant title attributes', 'accessibility-guardian' ),
				'description' => __( 'Strips title attributes from links and inputs that already have visible text.', 'accessibility-guardian' ),
			),
		);
	}

	/**
	 * Resolve which automatic fix (if any) can address a given rule.
	 *
	 * @param string $rule_id Rule identifier.
	 * @return array{key:string,label:string}|null
	 */
	public function auto_fix_for( string $rule_id ): ?array {
		$map = array(
			'html-has-lang'         => 'add_html_lang',
			'html-lang-valid'       => 'add_html_lang',
			'bypass'                => 'add_skip_link',
			'link-in-text-block'    => 'underline_links',
			'ag-new-window-warning' => 'new_window_warning',
			'meta-viewport'         => 'fix_viewport',
			'tabindex'              => 'remove_positive_tabindex',
		);

		if ( ! isset( $map[ $rule_id ] ) ) {
			return null;
		}

		$key   = $map[ $rule_id ];
		$fixes = $this->fixes();

		return array(
			'key'   => $key,
			'label' => $fixes[ $key ]['label'] ?? $key,
		);
	}

	/**
	 * Map an axe impact level to a plugin severity bucket.
	 *
	 * @param string $impact axe impact level.
	 */
	public function severity_from_impact( string $impact ): string {
		return match ( $impact ) {
			'critical' => 'critical',
			'serious'  => 'major',
			'moderate' => 'minor',
			default    => 'warning',
		};
	}

	/**
	 * Return the full rule metadata map.
	 *
	 * @return array<string, array{wcag:string,category:string,severity:string,fix:string,doc:string}>
	 */
	public function all(): array {
		if ( null !== $this->map ) {
			return $this->map;
		}

		$axe = 'https://dequeuniversity.com/rules/axe/4.10/';

		$rules = array(
			// Images.
			'image-alt' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'images',
				'severity' => 'critical',
				'fix'      => __( 'Add a meaningful alt attribute describing the image purpose. Use alt="" for purely decorative images.', 'accessibility-guardian' ),
				'doc'      => $axe . 'image-alt',
			),
			'input-image-alt' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'images',
				'severity' => 'critical',
				'fix'      => __( 'Add an alt attribute to the image input describing the button action.', 'accessibility-guardian' ),
				'doc'      => $axe . 'input-image-alt',
			),
			'image-redundant-alt' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'images',
				'severity' => 'minor',
				'fix'      => __( 'Remove redundant text that duplicates adjacent visible text or the alt value.', 'accessibility-guardian' ),
				'doc'      => $axe . 'image-redundant-alt',
			),
			'svg-img-alt' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'images',
				'severity' => 'critical',
				'fix'      => __( 'Provide an accessible name for the SVG via <title>, aria-label, or role="img" with a label.', 'accessibility-guardian' ),
				'doc'      => $axe . 'svg-img-alt',
			),
			'object-alt' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'images',
				'severity' => 'major',
				'fix'      => __( 'Provide alternative text for the <object> element.', 'accessibility-guardian' ),
				'doc'      => $axe . 'object-alt',
			),
			'area-alt' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'images',
				'severity' => 'critical',
				'fix'      => __( 'Add alt text to each <area> within an image map.', 'accessibility-guardian' ),
				'doc'      => $axe . 'area-alt',
			),

			// Color contrast.
			'color-contrast' => array(
				'wcag'     => __( '1.4.3 Contrast (Minimum)', 'accessibility-guardian' ),
				'category' => 'contrast',
				'severity' => 'major',
				'fix'      => __( 'Increase the contrast ratio between text and background to at least 4.5:1 (3:1 for large text).', 'accessibility-guardian' ),
				'doc'      => $axe . 'color-contrast',
			),

			// Headings.
			'empty-heading' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'headings',
				'severity' => 'minor',
				'fix'      => __( 'Provide discernible text inside the heading, or remove the empty heading element.', 'accessibility-guardian' ),
				'doc'      => $axe . 'empty-heading',
			),
			'heading-order' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'headings',
				'severity' => 'minor',
				'fix'      => __( 'Do not skip heading levels. Use a logical, sequential heading hierarchy.', 'accessibility-guardian' ),
				'doc'      => $axe . 'heading-order',
			),
			'page-has-heading-one' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'headings',
				'severity' => 'minor',
				'fix'      => __( 'Add a single descriptive <h1> that identifies the main content of the page.', 'accessibility-guardian' ),
				'doc'      => $axe . 'page-has-heading-one',
			),

			// Links.
			'link-name' => array(
				'wcag'     => __( '2.4.4 Link Purpose (In Context)', 'accessibility-guardian' ),
				'category' => 'links',
				'severity' => 'major',
				'fix'      => __( 'Provide descriptive link text or an accessible name (aria-label) that conveys the link purpose.', 'accessibility-guardian' ),
				'doc'      => $axe . 'link-name',
			),
			'link-in-text-block' => array(
				'wcag'     => __( '1.4.1 Use of Color', 'accessibility-guardian' ),
				'category' => 'links',
				'severity' => 'major',
				'fix'      => __( 'Distinguish links from surrounding text using more than color (for example, an underline).', 'accessibility-guardian' ),
				'doc'      => $axe . 'link-in-text-block',
			),
			'identical-links-same-purpose' => array(
				'wcag'     => __( '2.4.4 Link Purpose (In Context)', 'accessibility-guardian' ),
				'category' => 'links',
				'severity' => 'minor',
				'fix'      => __( 'Links with identical names should point to the same destination, or be given distinct accessible names.', 'accessibility-guardian' ),
				'doc'      => $axe . 'identical-links-same-purpose',
			),

			// Forms.
			'label' => array(
				'wcag'     => __( '3.3.2 Labels or Instructions', 'accessibility-guardian' ),
				'category' => 'forms',
				'severity' => 'critical',
				'fix'      => __( 'Associate a visible <label> with the form control using for/id, or add an aria-label.', 'accessibility-guardian' ),
				'doc'      => $axe . 'label',
			),
			'label-title-only' => array(
				'wcag'     => __( '3.3.2 Labels or Instructions', 'accessibility-guardian' ),
				'category' => 'forms',
				'severity' => 'minor',
				'fix'      => __( 'Provide a real label; do not rely solely on the title attribute.', 'accessibility-guardian' ),
				'doc'      => $axe . 'label-title-only',
			),
			'form-field-multiple-labels' => array(
				'wcag'     => __( '3.3.2 Labels or Instructions', 'accessibility-guardian' ),
				'category' => 'forms',
				'severity' => 'minor',
				'fix'      => __( 'Ensure each form field is associated with a single label.', 'accessibility-guardian' ),
				'doc'      => $axe . 'form-field-multiple-labels',
			),
			'autocomplete-valid' => array(
				'wcag'     => __( '1.3.5 Identify Input Purpose', 'accessibility-guardian' ),
				'category' => 'forms',
				'severity' => 'minor',
				'fix'      => __( 'Use a valid autocomplete token appropriate for the input purpose.', 'accessibility-guardian' ),
				'doc'      => $axe . 'autocomplete-valid',
			),
			'select-name' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'forms',
				'severity' => 'critical',
				'fix'      => __( 'Give the select element an accessible name via a label or aria-label.', 'accessibility-guardian' ),
				'doc'      => $axe . 'select-name',
			),

			// ARIA.
			'aria-allowed-attr' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Remove ARIA attributes that are not allowed for the element role.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-allowed-attr',
			),
			'aria-allowed-role' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'minor',
				'fix'      => __( 'Use an ARIA role that is allowed for this element.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-allowed-role',
			),
			'aria-required-attr' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'critical',
				'fix'      => __( 'Add the ARIA attributes required by this role.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-required-attr',
			),
			'aria-required-children' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'critical',
				'fix'      => __( 'Ensure the role contains its required child roles.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-required-children',
			),
			'aria-required-parent' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'critical',
				'fix'      => __( 'Ensure the role is contained within its required parent role.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-required-parent',
			),
			'aria-roles' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Use a valid ARIA role value.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-roles',
			),
			'aria-valid-attr' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Correct the misspelled or invalid ARIA attribute name.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-valid-attr',
			),
			'aria-valid-attr-value' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Provide a valid value for the ARIA attribute.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-valid-attr-value',
			),
			'aria-hidden-body' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'critical',
				'fix'      => __( 'Do not set aria-hidden="true" on the document <body>.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-hidden-body',
			),
			'aria-hidden-focus' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Do not place focusable elements inside aria-hidden="true" containers.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-hidden-focus',
			),
			'aria-command-name' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Provide an accessible name for the ARIA command (button, link, menuitem).', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-command-name',
			),
			'aria-input-field-name' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Provide an accessible name for the ARIA input field.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-input-field-name',
			),
			'aria-toggle-field-name' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'aria',
				'severity' => 'major',
				'fix'      => __( 'Provide an accessible name for the ARIA toggle field.', 'accessibility-guardian' ),
				'doc'      => $axe . 'aria-toggle-field-name',
			),

			// Duplicate / structure.
			'duplicate-id-aria' => array(
				'wcag'     => __( '4.1.1 Parsing', 'accessibility-guardian' ),
				'category' => 'structure',
				'severity' => 'minor',
				'fix'      => __( 'Ensure id values referenced by ARIA are unique within the document.', 'accessibility-guardian' ),
				'doc'      => $axe . 'duplicate-id-aria',
			),
			'duplicate-id-active' => array(
				'wcag'     => __( '4.1.1 Parsing', 'accessibility-guardian' ),
				'category' => 'structure',
				'severity' => 'minor',
				'fix'      => __( 'Ensure id values on active elements are unique within the document.', 'accessibility-guardian' ),
				'doc'      => $axe . 'duplicate-id-active',
			),
			'duplicate-id' => array(
				'wcag'     => __( '4.1.1 Parsing', 'accessibility-guardian' ),
				'category' => 'structure',
				'severity' => 'minor',
				'fix'      => __( 'Ensure every id attribute is unique within the document.', 'accessibility-guardian' ),
				'doc'      => $axe . 'duplicate-id',
			),

			// Tables.
			'td-headers-attr' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'tables',
				'severity' => 'major',
				'fix'      => __( 'Ensure the headers attribute references valid header cell ids in the same table.', 'accessibility-guardian' ),
				'doc'      => $axe . 'td-headers-attr',
			),
			'th-has-data-cells' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'tables',
				'severity' => 'major',
				'fix'      => __( 'Ensure each <th> is associated with data cells, or use a layout that does not rely on it.', 'accessibility-guardian' ),
				'doc'      => $axe . 'th-has-data-cells',
			),
			'scope-attr-valid' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'tables',
				'severity' => 'major',
				'fix'      => __( 'Use a valid scope value (row, col, rowgroup, colgroup) on header cells.', 'accessibility-guardian' ),
				'doc'      => $axe . 'scope-attr-valid',
			),
			'table-duplicate-name' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'tables',
				'severity' => 'minor',
				'fix'      => __( 'Ensure the table caption and summary do not duplicate each other.', 'accessibility-guardian' ),
				'doc'      => $axe . 'table-duplicate-name',
			),
			'empty-table-header' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'tables',
				'severity' => 'minor',
				'fix'      => __( 'Provide text content for table header cells.', 'accessibility-guardian' ),
				'doc'      => $axe . 'empty-table-header',
			),

			// Language.
			'html-has-lang' => array(
				'wcag'     => __( '3.1.1 Language of Page', 'accessibility-guardian' ),
				'category' => 'language',
				'severity' => 'major',
				'fix'      => __( 'Add a lang attribute to the <html> element identifying the page language.', 'accessibility-guardian' ),
				'doc'      => $axe . 'html-has-lang',
			),
			'html-lang-valid' => array(
				'wcag'     => __( '3.1.1 Language of Page', 'accessibility-guardian' ),
				'category' => 'language',
				'severity' => 'major',
				'fix'      => __( 'Use a valid BCP 47 language code in the lang attribute.', 'accessibility-guardian' ),
				'doc'      => $axe . 'html-lang-valid',
			),
			'html-xml-lang-mismatch' => array(
				'wcag'     => __( '3.1.1 Language of Page', 'accessibility-guardian' ),
				'category' => 'language',
				'severity' => 'minor',
				'fix'      => __( 'Ensure lang and xml:lang attributes agree.', 'accessibility-guardian' ),
				'doc'      => $axe . 'html-xml-lang-mismatch',
			),
			'valid-lang' => array(
				'wcag'     => __( '3.1.2 Language of Parts', 'accessibility-guardian' ),
				'category' => 'language',
				'severity' => 'major',
				'fix'      => __( 'Use a valid language code on inline lang attributes.', 'accessibility-guardian' ),
				'doc'      => $axe . 'valid-lang',
			),

			// Landmarks / regions.
			'region' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'landmarks',
				'severity' => 'minor',
				'fix'      => __( 'Wrap page content in landmark regions (main, nav, header, footer).', 'accessibility-guardian' ),
				'doc'      => $axe . 'region',
			),
			'landmark-one-main' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'landmarks',
				'severity' => 'minor',
				'fix'      => __( 'Provide exactly one <main> landmark on the page.', 'accessibility-guardian' ),
				'doc'      => $axe . 'landmark-one-main',
			),
			'landmark-no-duplicate-main' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'landmarks',
				'severity' => 'minor',
				'fix'      => __( 'Avoid more than one main landmark on the page.', 'accessibility-guardian' ),
				'doc'      => $axe . 'landmark-no-duplicate-main',
			),
			'landmark-unique' => array(
				'wcag'     => __( '1.3.1 Info and Relationships', 'accessibility-guardian' ),
				'category' => 'landmarks',
				'severity' => 'minor',
				'fix'      => __( 'Give landmarks of the same type unique accessible names.', 'accessibility-guardian' ),
				'doc'      => $axe . 'landmark-unique',
			),

			// Buttons.
			'button-name' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'buttons',
				'severity' => 'critical',
				'fix'      => __( 'Provide discernible text or an aria-label for the button.', 'accessibility-guardian' ),
				'doc'      => $axe . 'button-name',
			),
			'nested-interactive' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'buttons',
				'severity' => 'major',
				'fix'      => __( 'Do not nest interactive controls inside other interactive controls.', 'accessibility-guardian' ),
				'doc'      => $axe . 'nested-interactive',
			),

			// Document.
			'document-title' => array(
				'wcag'     => __( '2.4.2 Page Titled', 'accessibility-guardian' ),
				'category' => 'document',
				'severity' => 'major',
				'fix'      => __( 'Provide a non-empty, descriptive <title> for the page.', 'accessibility-guardian' ),
				'doc'      => $axe . 'document-title',
			),
			'bypass' => array(
				'wcag'     => __( '2.4.1 Bypass Blocks', 'accessibility-guardian' ),
				'category' => 'keyboard',
				'severity' => 'major',
				'fix'      => __( 'Provide a skip link or landmarks so keyboard users can bypass repeated content.', 'accessibility-guardian' ),
				'doc'      => $axe . 'bypass',
			),
			'frame-title' => array(
				'wcag'     => __( '4.1.2 Name, Role, Value', 'accessibility-guardian' ),
				'category' => 'document',
				'severity' => 'major',
				'fix'      => __( 'Provide a descriptive title attribute for each <iframe>.', 'accessibility-guardian' ),
				'doc'      => $axe . 'frame-title',
			),
			'meta-viewport' => array(
				'wcag'     => __( '1.4.4 Resize Text', 'accessibility-guardian' ),
				'category' => 'responsive',
				'severity' => 'major',
				'fix'      => __( 'Remove user-scalable=no and maximum-scale limits so users can zoom the page.', 'accessibility-guardian' ),
				'doc'      => $axe . 'meta-viewport',
			),
			'tabindex' => array(
				'wcag'     => __( '2.4.3 Focus Order', 'accessibility-guardian' ),
				'category' => 'keyboard',
				'severity' => 'major',
				'fix'      => __( 'Avoid positive tabindex values; rely on natural DOM order for focus.', 'accessibility-guardian' ),
				'doc'      => $axe . 'tabindex',
			),

			// Supplemental custom JS rules (prefixed ag-).
			'ag-generic-link-text' => array(
				'wcag'     => __( '2.4.4 Link Purpose (In Context)', 'accessibility-guardian' ),
				'category' => 'links',
				'severity' => 'minor',
				'fix'      => __( 'Replace generic link text such as "click here" or "read more" with text that describes the destination.', 'accessibility-guardian' ),
				'doc'      => 'https://www.w3.org/WAI/WCAG22/Understanding/link-purpose-in-context.html',
			),
			'ag-placeholder-as-label' => array(
				'wcag'     => __( '3.3.2 Labels or Instructions', 'accessibility-guardian' ),
				'category' => 'forms',
				'severity' => 'minor',
				'fix'      => __( 'Do not rely on placeholder text as the only label. Add a persistent visible label.', 'accessibility-guardian' ),
				'doc'      => 'https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html',
			),
			'ag-new-window-warning' => array(
				'wcag'     => __( '3.2.5 Change on Request', 'accessibility-guardian' ),
				'category' => 'links',
				'severity' => 'warning',
				'fix'      => __( 'Warn users when a link opens in a new window, for example by appending "(opens in new window)" to the link text.', 'accessibility-guardian' ),
				'doc'      => 'https://www.w3.org/WAI/WCAG22/Understanding/change-on-request.html',
			),
			'ag-pdf-link' => array(
				'wcag'     => __( '1.1.1 Non-text Content', 'accessibility-guardian' ),
				'category' => 'documents',
				'severity' => 'warning',
				'fix'      => __( 'Linked PDF detected. Verify the PDF is tagged and accessible; automated checks cannot validate PDF contents.', 'accessibility-guardian' ),
				'doc'      => 'https://www.w3.org/WAI/WCAG22/Understanding/non-text-content.html',
			),
		);

		$this->map = $rules;

		return $this->map;
	}
}
