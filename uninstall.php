<?php
/**
 * Uninstall routine for Accessibility Guardian.
 *
 * Removes custom tables and stored options when the plugin is deleted.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$ag_tables = array(
	$wpdb->prefix . 'ag_scans',
	$wpdb->prefix . 'ag_issues',
	$wpdb->prefix . 'ag_history',
);

foreach ( $ag_tables as $ag_table ) {
	// Table name cannot be parameterized; it is built from a trusted constant prefix.
	$wpdb->query( "DROP TABLE IF EXISTS {$ag_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
}

delete_option( 'ag_settings' );
delete_option( 'ag_db_version' );
