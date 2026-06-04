<?php
/**
 * Run-scan page template.
 *
 * @package AccessibilityGuardian
 *
 * @var int    $post_id
 * @var string $scan_type
 * @var string $post_title
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ag-wrap">
	<h1 class="ag-title">
		<span class="dashicons dashicons-search" aria-hidden="true"></span>
		<?php esc_html_e( 'Run Accessibility Scan', 'accessibility-guardian' ); ?>
	</h1>

	<p class="ag-intro">
		<?php esc_html_e( 'Scanning runs in your browser using axe-core against the rendered front-end of each page. Keep this tab open until the scan completes.', 'accessibility-guardian' ); ?>
	</p>

	<div class="ag-scan-controls">
		<?php if ( 'single' === $scan_type && $post_id > 0 ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: post title. */
					esc_html__( 'Single page scan: %s', 'accessibility-guardian' ),
					'<strong>' . esc_html( $post_title ) . '</strong>'
				);
				?>
			</p>
			<button type="button" class="button button-primary button-hero" id="ag-start-scan"
				data-scan-type="single" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
				<?php esc_html_e( 'Start single page scan', 'accessibility-guardian' ); ?>
			</button>
		<?php else : ?>
			<button type="button" class="button button-primary button-hero" id="ag-start-scan"
				data-scan-type="full" data-post-id="0">
				<?php esc_html_e( 'Start full site scan', 'accessibility-guardian' ); ?>
			</button>
		<?php endif; ?>

		<button type="button" class="button" id="ag-cancel-scan" hidden>
			<?php esc_html_e( 'Cancel', 'accessibility-guardian' ); ?>
		</button>
	</div>

	<div id="ag-progress" class="ag-progress" hidden>
		<div class="ag-progress__head">
			<span id="ag-progress-message" class="ag-progress__message"></span>
			<span id="ag-progress-count" class="ag-progress__count">0 / 0</span>
		</div>
		<div class="ag-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
			<div id="ag-progress-bar" class="ag-progress__bar" style="width: 0;"></div>
		</div>
	</div>

	<ul id="ag-scan-log" class="ag-log" aria-live="polite"></ul>

	<iframe id="ag-scan-frame" class="ag-scan-frame" title="<?php esc_attr_e( 'Accessibility scan sandbox', 'accessibility-guardian' ); ?>" aria-hidden="true" tabindex="-1"></iframe>
</div>
