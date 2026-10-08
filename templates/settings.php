<?php
/**
 * Settings page template.
 *
 * @package AccessibilityGuardian
 *
 * @var array<string,mixed> $args Template context.
 *
 * @var array<string,mixed>                                       $accg_settings
 * @var array<string,\WP_Post_Type>                               $accg_post_types
 * @var array<string,array{label:string,description:string}>      $accg_fix_catalog
 */

defined( 'ABSPATH' ) || exit;

// Template context passed by AdminMenu::render() via load_template().
$accg_settings    = $args['settings'] ?? array();
$accg_post_types  = $args['post_types'] ?? array();
$accg_fix_catalog = $args['fix_catalog'] ?? array();

$accg_selected_types = isset( $accg_settings['include_post_types'] ) && is_array( $accg_settings['include_post_types'] )
	? array_map( 'strval', $accg_settings['include_post_types'] )
	: array();
$accg_include_terms  = ! empty( $accg_settings['include_terms'] );
$accg_wcag_level     = isset( $accg_settings['wcag_level'] ) ? (string) $accg_settings['wcag_level'] : 'aa';
$accg_best_practice  = ! isset( $accg_settings['best_practice'] ) || ! empty( $accg_settings['best_practice'] );
$accg_enabled_fixes  = isset( $accg_settings['fixes'] ) && is_array( $accg_settings['fixes'] ) ? $accg_settings['fixes'] : array();
?>
<div class="wrap ag-wrap">
	<h1 class="ag-title">
		<span class="dashicons dashicons-admin-settings" aria-hidden="true"></span>
		<?php esc_html_e( 'Accessibility Guardian Settings', 'accessibility-guardian' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php settings_errors( 'accg_settings' ); ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'accg_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Content to scan', 'accessibility-guardian' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Post types to include', 'accessibility-guardian' ); ?></legend>
							<?php foreach ( $accg_post_types as $accg_type ) : ?>
								<label class="ag-checkbox">
									<input type="checkbox" name="include_post_types[]"
										value="<?php echo esc_attr( $accg_type->name ); ?>"
										<?php checked( in_array( $accg_type->name, $accg_selected_types, true ) ); ?> />
									<?php echo esc_html( $accg_type->labels->name ); ?>
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
					<th scope="row"><label for="ag-wcag-level"><?php esc_html_e( 'WCAG level', 'accessibility-guardian' ); ?></label></th>
					<td>
						<select id="ag-wcag-level" name="wcag_level">
							<option value="a" <?php selected( $accg_wcag_level, 'a' ); ?>><?php esc_html_e( 'A', 'accessibility-guardian' ); ?></option>
							<option value="aa" <?php selected( $accg_wcag_level, 'aa' ); ?>><?php esc_html_e( 'AA (recommended)', 'accessibility-guardian' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Best-practice checks', 'accessibility-guardian' ); ?></th>
					<td>
						<label class="ag-checkbox">
							<input type="checkbox" name="best_practice" value="1" <?php checked( $accg_best_practice ); ?> />
							<?php esc_html_e( 'Also check headings, landmarks and other axe-core best practices', 'accessibility-guardian' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'These rules are not strict WCAG failures but catch common structural problems such as skipped heading levels, a missing main heading or content outside landmarks.', 'accessibility-guardian' ); ?></p>
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
			<?php foreach ( $accg_fix_catalog as $accg_fix_key => $accg_fix ) : ?>
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
