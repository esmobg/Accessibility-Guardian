<?php
/**
 * Database installation and lifecycle handling.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Activation;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and maintains the plugin database schema.
 */
final class Installer {

	/**
	 * Current schema version. Bump to trigger dbDelta migrations.
	 */
	private const DB_VERSION = '1.1.0';

	/**
	 * Option key that stores the installed schema version.
	 */
	private const DB_VERSION_OPTION = 'accg_db_version';

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		self::maybe_migrate_legacy();
		self::install_tables();
		self::seed_default_settings();
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Intentionally keep data on deactivation; cleanup happens on uninstall.
	}

	/**
	 * Ensure the schema is up to date. Safe to call repeatedly.
	 */
	public static function maybe_upgrade(): void {
		self::maybe_migrate_legacy();

		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install_tables();
		}
	}

	/**
	 * Rename 1.0 ag_* tables/options to accg_* when they still exist.
	 */
	private static function maybe_migrate_legacy(): void {
		global $wpdb;

		$pairs = array(
			$wpdb->prefix . 'ag_scans'   => $wpdb->prefix . 'accg_scans',
			$wpdb->prefix . 'ag_issues'  => $wpdb->prefix . 'accg_issues',
			$wpdb->prefix . 'ag_history' => $wpdb->prefix . 'accg_history',
		);

		foreach ( $pairs as $old => $new ) {
			$old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( $old_exists && ! $new_exists ) {
				// Table names are $wpdb->prefix plus hardcoded ag_/accg_ suffixes, not user input.
				$wpdb->query( "RENAME TABLE {$old} TO {$new}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter
			}
		}

		$legacy_settings = get_option( 'ag_settings', false );
		if ( false !== $legacy_settings && false === get_option( 'accg_settings', false ) ) {
			add_option( 'accg_settings', $legacy_settings );
		}

		$legacy_version = get_option( 'ag_db_version', false );
		if ( false !== $legacy_version && false === get_option( self::DB_VERSION_OPTION, false ) ) {
			add_option( self::DB_VERSION_OPTION, $legacy_version );
		}
	}

	/**
	 * Create or update the custom tables via dbDelta().
	 */
	private static function install_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$scans           = $wpdb->prefix . 'accg_scans';
		$issues          = $wpdb->prefix . 'accg_issues';
		$history         = $wpdb->prefix . 'accg_history';

		$schema = array();

		$schema[] = "CREATE TABLE {$scans} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			scan_type VARCHAR(20) NOT NULL DEFAULT 'single',
			status VARCHAR(20) NOT NULL DEFAULT 'running',
			started_at DATETIME NULL DEFAULT NULL,
			finished_at DATETIME NULL DEFAULT NULL,
			total_urls INT(11) NOT NULL DEFAULT 0,
			scanned_urls INT(11) NOT NULL DEFAULT 0,
			score TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			errors INT(11) NOT NULL DEFAULT 0,
			warnings INT(11) NOT NULL DEFAULT 0,
			passes INT(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY started_at (started_at)
		) {$charset_collate};";

		$schema[] = "CREATE TABLE {$issues} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			scan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			url TEXT NOT NULL,
			post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			rule_id VARCHAR(100) NOT NULL DEFAULT '',
			wcag_ref VARCHAR(100) NOT NULL DEFAULT '',
			severity VARCHAR(20) NOT NULL DEFAULT 'minor',
			category VARCHAR(50) NOT NULL DEFAULT '',
			impact VARCHAR(20) NOT NULL DEFAULT '',
			message TEXT NOT NULL,
			html_snippet LONGTEXT NOT NULL,
			dom_path TEXT NOT NULL,
			fix_suggestion TEXT NOT NULL,
			doc_link TEXT NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			created_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY scan_id (scan_id),
			KEY severity (severity),
			KEY rule_id (rule_id),
			KEY post_id (post_id)
		) {$charset_collate};";

		$schema[] = "CREATE TABLE {$history} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			scan_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			score TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			errors INT(11) NOT NULL DEFAULT 0,
			warnings INT(11) NOT NULL DEFAULT 0,
			passes INT(11) NOT NULL DEFAULT 0,
			created_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) {$charset_collate};";

		foreach ( $schema as $statement ) {
			dbDelta( $statement );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Store default settings if none exist yet.
	 */
	private static function seed_default_settings(): void {
		if ( false === get_option( 'accg_settings' ) ) {
			add_option(
				'accg_settings',
				array(
					'include_post_types' => array( 'post', 'page' ),
					'include_terms'      => false,
					'batch_size'         => 5,
					'wcag_level'         => 'aa',
				)
			);
		}
	}
}
