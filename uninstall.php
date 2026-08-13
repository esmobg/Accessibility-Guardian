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

$accg_tables = array(
	$wpdb->prefix . 'accg_scans',
	$wpdb->prefix . 'accg_issues',
	$wpdb->prefix . 'accg_history',
	// Legacy 1.0 table names, in case uninstall runs before migration.
	$wpdb->prefix . 'ag_scans',
	$wpdb->prefix . 'ag_issues',
	$wpdb->prefix . 'ag_history',
);

foreach ( $accg_tables as $accg_table ) {
	// Table name cannot be parameterized; it is built from a trusted constant prefix.
	$wpdb->query( "DROP TABLE IF EXISTS {$accg_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter
}

delete_option( 'accg_settings' );
delete_option( 'accg_db_version' );
delete_option( 'ag_settings' );
delete_option( 'ag_db_version' );
