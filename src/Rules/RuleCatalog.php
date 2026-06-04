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
			'image-alt'                   => array( '1.1.1 Non-text Content', 'images', 'critical', 'Add a meaningful alt attribute describing the image purpose. Use alt="" for purely decorative images.', $axe . 'image-alt' ),
			'input-image-alt'             => array( '1.1.1 Non-text Content', 'images', 'critical', 'Add an alt attribute to the image input describing the button action.', $axe . 'input-image-alt' ),
			'image-redundant-alt'         => array( '1.1.1 Non-text Content', 'images', 'minor', 'Remove redundant text that duplicates adjacent visible text or the alt value.', $axe . 'image-redundant-alt' ),
			'svg-img-alt'                 => array( '1.1.1 Non-text Content', 'images', 'critical', 'Provide an accessible name for the SVG via <title>, aria-label, or role="img" with a label.', $axe . 'svg-img-alt' ),
			'object-alt'                  => array( '1.1.1 Non-text Content', 'images', 'serious', 'Provide alternative text for the <object> element.', $axe . 'object-alt' ),
			'area-alt'                    => array( '1.1.1 Non-text Content', 'images', 'critical', 'Add alt text to each <area> within an image map.', $axe . 'area-alt' ),

			// Color contrast.
			'color-contrast'              => array( '1.4.3 Contrast (Minimum)', 'contrast', 'major', 'Increase the contrast ratio between text and background to at least 4.5:1 (3:1 for large text).', $axe . 'color-contrast' ),

			// Headings.
			'empty-heading'               => array( '1.3.1 Info and Relationships', 'headings', 'minor', 'Provide discernible text inside the heading, or remove the empty heading element.', $axe . 'empty-heading' ),
			'heading-order'               => array( '1.3.1 Info and Relationships', 'headings', 'minor', 'Do not skip heading levels. Use a logical, sequential heading hierarchy.', $axe . 'heading-order' ),
			'page-has-heading-one'        => array( '1.3.1 Info and Relationships', 'headings', 'minor', 'Add a single descriptive <h1> that identifies the main content of the page.', $axe . 'page-has-heading-one' ),

			// Links.
			'link-name'                   => array( '2.4.4 Link Purpose (In Context)', 'links', 'major', 'Provide descriptive link text or an accessible name (aria-label) that conveys the link purpose.', $axe . 'link-name' ),
			'link-in-text-block'          => array( '1.4.1 Use of Color', 'links', 'major', 'Distinguish links from surrounding text using more than color (for example, an underline).', $axe . 'link-in-text-block' ),
			'identical-links-same-purpose' => array( '2.4.4 Link Purpose (In Context)', 'links', 'minor', 'Links with identical names should point to the same destination, or be given distinct accessible names.', $axe . 'identical-links-same-purpose' ),

			// Forms.
			'label'                       => array( '3.3.2 Labels or Instructions', 'forms', 'critical', 'Associate a visible <label> with the form control using for/id, or add an aria-label.', $axe . 'label' ),
			'label-title-only'           => array( '3.3.2 Labels or Instructions', 'forms', 'minor', 'Provide a real label; do not rely solely on the title attribute.', $axe . 'label-title-only' ),
			'form-field-multiple-labels'  => array( '3.3.2 Labels or Instructions', 'forms', 'minor', 'Ensure each form field is associated with a single label.', $axe . 'form-field-multiple-labels' ),
			'autocomplete-valid'          => array( '1.3.5 Identify Input Purpose', 'forms', 'minor', 'Use a valid autocomplete token appropriate for the input purpose.', $axe . 'autocomplete-valid' ),
			'select-name'                 => array( '4.1.2 Name, Role, Value', 'forms', 'critical', 'Give the select element an accessible name via a label or aria-label.', $axe . 'select-name' ),

			// ARIA.
			'aria-allowed-attr'           => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Remove ARIA attributes that are not allowed for the element role.', $axe . 'aria-allowed-attr' ),
			'aria-allowed-role'           => array( '4.1.2 Name, Role, Value', 'aria', 'minor', 'Use an ARIA role that is allowed for this element.', $axe . 'aria-allowed-role' ),
			'aria-required-attr'          => array( '4.1.2 Name, Role, Value', 'aria', 'critical', 'Add the ARIA attributes required by this role.', $axe . 'aria-required-attr' ),
			'aria-required-children'      => array( '1.3.1 Info and Relationships', 'aria', 'critical', 'Ensure the role contains its required child roles.', $axe . 'aria-required-children' ),
			'aria-required-parent'        => array( '1.3.1 Info and Relationships', 'aria', 'critical', 'Ensure the role is contained within its required parent role.', $axe . 'aria-required-parent' ),
			'aria-roles'                  => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Use a valid ARIA role value.', $axe . 'aria-roles' ),
			'aria-valid-attr'             => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Correct the misspelled or invalid ARIA attribute name.', $axe . 'aria-valid-attr' ),
			'aria-valid-attr-value'       => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Provide a valid value for the ARIA attribute.', $axe . 'aria-valid-attr-value' ),
			'aria-hidden-body'            => array( '4.1.2 Name, Role, Value', 'aria', 'critical', 'Do not set aria-hidden="true" on the document <body>.', $axe . 'aria-hidden-body' ),
			'aria-hidden-focus'           => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Do not place focusable elements inside aria-hidden="true" containers.', $axe . 'aria-hidden-focus' ),
			'aria-command-name'           => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Provide an accessible name for the ARIA command (button, link, menuitem).', $axe . 'aria-command-name' ),
			'aria-input-field-name'       => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Provide an accessible name for the ARIA input field.', $axe . 'aria-input-field-name' ),
			'aria-toggle-field-name'      => array( '4.1.2 Name, Role, Value', 'aria', 'serious', 'Provide an accessible name for the ARIA toggle field.', $axe . 'aria-toggle-field-name' ),

			// Duplicate / structure.
			'duplicate-id-aria'           => array( '4.1.1 Parsing', 'structure', 'minor', 'Ensure id values referenced by ARIA are unique within the document.', $axe . 'duplicate-id-aria' ),
			'duplicate-id-active'         => array( '4.1.1 Parsing', 'structure', 'minor', 'Ensure id values on active elements are unique within the document.', $axe . 'duplicate-id-active' ),
			'duplicate-id'                => array( '4.1.1 Parsing', 'structure', 'minor', 'Ensure every id attribute is unique within the document.', $axe . 'duplicate-id' ),

			// Tables.
			'td-headers-attr'             => array( '1.3.1 Info and Relationships', 'tables', 'serious', 'Ensure the headers attribute references valid header cell ids in the same table.', $axe . 'td-headers-attr' ),
			'th-has-data-cells'           => array( '1.3.1 Info and Relationships', 'tables', 'serious', 'Ensure each <th> is associated with data cells, or use a layout that does not rely on it.', $axe . 'th-has-data-cells' ),
			'scope-attr-valid'            => array( '1.3.1 Info and Relationships', 'tables', 'serious', 'Use a valid scope value (row, col, rowgroup, colgroup) on header cells.', $axe . 'scope-attr-valid' ),
			'table-duplicate-name'        => array( '1.3.1 Info and Relationships', 'tables', 'minor', 'Ensure the table caption and summary do not duplicate each other.', $axe . 'table-duplicate-name' ),
			'empty-table-header'          => array( '1.3.1 Info and Relationships', 'tables', 'minor', 'Provide text content for table header cells.', $axe . 'empty-table-header' ),

			// Language.
			'html-has-lang'               => array( '3.1.1 Language of Page', 'language', 'serious', 'Add a lang attribute to the <html> element identifying the page language.', $axe . 'html-has-lang' ),
			'html-lang-valid'             => array( '3.1.1 Language of Page', 'language', 'serious', 'Use a valid BCP 47 language code in the lang attribute.', $axe . 'html-lang-valid' ),
			'html-xml-lang-mismatch'      => array( '3.1.1 Language of Page', 'language', 'minor', 'Ensure lang and xml:lang attributes agree.', $axe . 'html-xml-lang-mismatch' ),
			'valid-lang'                  => array( '3.1.2 Language of Parts', 'language', 'serious', 'Use a valid language code on inline lang attributes.', $axe . 'valid-lang' ),

			// Landmarks / regions.
			'region'                      => array( '1.3.1 Info and Relationships', 'landmarks', 'minor', 'Wrap page content in landmark regions (main, nav, header, footer).', $axe . 'region' ),
			'landmark-one-main'           => array( '1.3.1 Info and Relationships', 'landmarks', 'minor', 'Provide exactly one <main> landmark on the page.', $axe . 'landmark-one-main' ),
			'landmark-no-duplicate-main'  => array( '1.3.1 Info and Relationships', 'landmarks', 'minor', 'Avoid more than one main landmark on the page.', $axe . 'landmark-no-duplicate-main' ),
			'landmark-unique'             => array( '1.3.1 Info and Relationships', 'landmarks', 'minor', 'Give landmarks of the same type unique accessible names.', $axe . 'landmark-unique' ),

			// Buttons.
			'button-name'                 => array( '4.1.2 Name, Role, Value', 'buttons', 'critical', 'Provide discernible text or an aria-label for the button.', $axe . 'button-name' ),
			'nested-interactive'          => array( '4.1.2 Name, Role, Value', 'buttons', 'serious', 'Do not nest interactive controls inside other interactive controls.', $axe . 'nested-interactive' ),

			// Document.
			'document-title'              => array( '2.4.2 Page Titled', 'document', 'serious', 'Provide a non-empty, descriptive <title> for the page.', $axe . 'document-title' ),
			'bypass'                      => array( '2.4.1 Bypass Blocks', 'keyboard', 'serious', 'Provide a skip link or landmarks so keyboard users can bypass repeated content.', $axe . 'bypass' ),
			'frame-title'                 => array( '4.1.2 Name, Role, Value', 'document', 'serious', 'Provide a descriptive title attribute for each <iframe>.', $axe . 'frame-title' ),
			'meta-viewport'               => array( '1.4.4 Resize Text', 'responsive', 'serious', 'Remove user-scalable=no and maximum-scale limits so users can zoom the page.', $axe . 'meta-viewport' ),
			'tabindex'                    => array( '2.4.3 Focus Order', 'keyboard', 'serious', 'Avoid positive tabindex values; rely on natural DOM order for focus.', $axe . 'tabindex' ),

			// Supplemental custom JS rules (prefixed ag-).
			'ag-generic-link-text'        => array( '2.4.4 Link Purpose (In Context)', 'links', 'minor', 'Replace generic link text such as "click here" or "read more" with text that describes the destination.', 'https://www.w3.org/WAI/WCAG22/Understanding/link-purpose-in-context.html' ),
			'ag-placeholder-as-label'     => array( '3.3.2 Labels or Instructions', 'forms', 'minor', 'Do not rely on placeholder text as the only label. Add a persistent visible label.', 'https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html' ),
			'ag-new-window-warning'       => array( '3.2.5 Change on Request', 'links', 'warning', 'Warn users when a link opens in a new window, for example by appending "(opens in new window)" to the link text.', 'https://www.w3.org/WAI/WCAG22/Understanding/change-on-request.html' ),
			'ag-pdf-link'                 => array( '1.1.1 Non-text Content', 'documents', 'warning', 'Linked PDF detected. Verify the PDF is tagged and accessible; automated checks cannot validate PDF contents.', 'https://www.w3.org/WAI/WCAG22/Understanding/non-text-content.html' ),
		);

		$this->map = array();
		foreach ( $rules as $id => $data ) {
			$this->map[ $id ] = array(
				'wcag'     => $data[0],
				'category' => $data[1],
				'severity' => $data[2],
				'fix'      => $data[3],
				'doc'      => $data[4],
			);
		}

		return $this->map;
	}
}
