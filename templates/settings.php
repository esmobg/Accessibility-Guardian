<?php
/**
 * Settings page template.
 *
 * @package AccessibilityGuardian
 *
 * @var array<string,mixed>                                       $settings
 * @var array<string,\WP_Post_Type>                               $post_types
 * @var array<string,array{label:string,description:string}>      $fix_catalog
 */

defined( 'ABSPATH' ) || exit;

$accg_selected_types = isset( $settings['include_post_types'] ) && is_array( $settings['include_post_types'] )
	? array_map( 'strval', $settings['include_post_types'] )
	: array();
$accg_include_terms  = ! empty( $settings['include_terms'] );
$accg_batch_size     = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 5;
$accg_wcag_level     = isset( $settings['wcag_level'] ) ? (string) $settings['wcag_level'] : 'aa';
$accg_enabled_fixes  = isset( $settings['fixes'] ) && is_array( $settings['fixes'] ) ? $settings['fixes'] : array();

settings_errors( 'accg_settings' );
?>
<div class="wrap ag-wrap">
	<h1 class="ag-title">
		<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
		<?php esc_html_e( 'Accessibility Guardian Settings', 'accessibility-guardian' ); ?>
	</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'accg_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content to scan', 'accessibility-guardian' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Post types to include', 'accessibility-guardian' ); ?></legend>
							<?php foreach ( $post_types as $accg_type ) : ?>
								<label class="ag-checkbox">
									<input type="checkbox" name="include_post_types[]"
										value="<?php echo esc_attr( $accg_type->name ); ?>"
										<?php checked( in_array( $accg_type->name, $accg_selected_types, true ) ); ?> />
									<?php echo esc_html( $accg_type->labels->name ); ?>
									<code><?php echo esc_html( $accg_type->name ); ?></code>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Choose which public post types are included in full-site scans.', 'accessibility-guardian' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Term archives', 'accessibility-guardian' ); ?></th>
					<td>
						<label class="ag-checkbox">
							<input type="checkbox" name="include_terms" value="1" <?php checked( $accg_include_terms ); ?> />
							<?php esc_html_e( 'Include category and tag archive pages', 'accessibility-guardian' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ag-batch-size"><?php esc_html_e( 'Batch size', 'accessibility-guardian' ); ?></label></th>
					<td>
						<input type="number" min="1" max="50" id="ag-batch-size" name="batch_size"
							value="<?php echo esc_attr( (string) $accg_batch_size ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Reserved for future server-side batching. The current in-browser scanner always processes one page at a time.', 'accessibility-guardian' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ag-wcag-level"><?php esc_html_e( 'WCAG level', 'accessibility-guardian' ); ?></label></th>
					<td>
						<select id="ag-wcag-level" name="wcag_level">
							<option value="a" <?php selected( $accg_wcag_level, 'a' ); ?>><?php esc_html_e( 'A', 'accessibility-guardian' ); ?></option>
							<option value="aa" <?php selected( $accg_wcag_level, 'aa' ); ?>><?php esc_html_e( 'AA (recommended)', 'accessibility-guardian' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Automatic fixes', 'accessibility-guardian' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Enable safe, site-wide remediations applied automatically on the front end. Each fix is optional; test your site after enabling.', 'accessibility-guardian' ); ?>
		</p>
		<fieldset class="ag-fixes">
			<legend class="screen-reader-text"><?php esc_html_e( 'Automatic fixes', 'accessibility-guardian' ); ?></legend>
			<?php foreach ( $fix_catalog as $accg_fix_key => $accg_fix ) : ?>
				<label class="ag-fix-option">
					<input type="checkbox" name="fixes[]" value="<?php echo esc_attr( $accg_fix_key ); ?>"
						<?php checked( ! empty( $accg_enabled_fixes[ $accg_fix_key ] ) ); ?> />
					<span class="ag-fix-option__label"><?php echo esc_html( $accg_fix['label'] ); ?></span>
					<span class="ag-fix-option__desc"><?php echo esc_html( $accg_fix['description'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<?php submit_button( __( 'Save settings', 'accessibility-guardian' ), 'primary', 'accg_settings_submit' ); ?>
	</form>
</div>
