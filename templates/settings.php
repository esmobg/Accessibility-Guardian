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

$ag_selected_types = isset( $settings['include_post_types'] ) && is_array( $settings['include_post_types'] )
	? array_map( 'strval', $settings['include_post_types'] )
	: array();
$ag_include_terms  = ! empty( $settings['include_terms'] );
$ag_batch_size     = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 5;
$ag_wcag_level     = isset( $settings['wcag_level'] ) ? (string) $settings['wcag_level'] : 'aa';
$ag_enabled_fixes  = isset( $settings['fixes'] ) && is_array( $settings['fixes'] ) ? $settings['fixes'] : array();

settings_errors( 'ag_settings' );
?>
<div class="wrap ag-wrap">
	<h1 class="ag-title">
		<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
		<?php esc_html_e( 'Accessibility Guardian Settings', 'accessibility-guardian' ); ?>
	</h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'ag_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content to scan', 'accessibility-guardian' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Post types to include', 'accessibility-guardian' ); ?></legend>
							<?php foreach ( $post_types as $ag_type ) : ?>
								<label class="ag-checkbox">
									<input type="checkbox" name="include_post_types[]"
										value="<?php echo esc_attr( $ag_type->name ); ?>"
										<?php checked( in_array( $ag_type->name, $ag_selected_types, true ) ); ?> />
									<?php echo esc_html( $ag_type->labels->name ); ?>
									<code><?php echo esc_html( $ag_type->name ); ?></code>
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
							<input type="checkbox" name="include_terms" value="1" <?php checked( $ag_include_terms ); ?> />
							<?php esc_html_e( 'Include category and tag archive pages', 'accessibility-guardian' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ag-batch-size"><?php esc_html_e( 'Batch size', 'accessibility-guardian' ); ?></label></th>
					<td>
						<input type="number" min="1" max="50" id="ag-batch-size" name="batch_size"
							value="<?php echo esc_attr( (string) $ag_batch_size ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Number of pages processed before progress is reported. Lower values reduce memory use.', 'accessibility-guardian' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ag-wcag-level"><?php esc_html_e( 'WCAG level', 'accessibility-guardian' ); ?></label></th>
					<td>
						<select id="ag-wcag-level" name="wcag_level">
							<option value="a" <?php selected( $ag_wcag_level, 'a' ); ?>><?php esc_html_e( 'A', 'accessibility-guardian' ); ?></option>
							<option value="aa" <?php selected( $ag_wcag_level, 'aa' ); ?>><?php esc_html_e( 'AA (recommended)', 'accessibility-guardian' ); ?></option>
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
			<?php foreach ( $fix_catalog as $ag_fix_key => $ag_fix ) : ?>
				<label class="ag-fix-option">
					<input type="checkbox" name="fixes[]" value="<?php echo esc_attr( $ag_fix_key ); ?>"
						<?php checked( ! empty( $ag_enabled_fixes[ $ag_fix_key ] ) ); ?> />
					<span class="ag-fix-option__label"><?php echo esc_html( $ag_fix['label'] ); ?></span>
					<span class="ag-fix-option__desc"><?php echo esc_html( $ag_fix['description'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<?php submit_button( __( 'Save settings', 'accessibility-guardian' ), 'primary', 'ag_settings_submit' ); ?>
	</form>
</div>
