<?php
/**
 * Uninstall routine for Accessibility Guardian.
 *
 * Removes custom tables and stored options when the plugin is deleted,
 * on every site of a multisite network.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove the plugin's tables and options from the current site.
 */
function accg_uninstall_site(): void {
	global $wpdb;

	foreach ( array( 'accg_scans', 'accg_issues', 'accg_history' ) as $accg_suffix ) {
		$accg_table = $wpdb->prefix . $accg_suffix;
		// Table name cannot be parameterized; it is the site prefix plus a hardcoded suffix.
		$wpdb->query( "DROP TABLE IF EXISTS {$accg_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	delete_option( 'accg_settings' );
	delete_option( 'accg_db_version' );
	delete_transient( 'accg_settings_notice' );
}

if ( is_multisite() ) {
	$accg_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $accg_site_ids as $accg_site_id ) {
		switch_to_blog( (int) $accg_site_id );
		accg_uninstall_site();
		restore_current_blog();
	}
} else {
	accg_uninstall_site();
}
