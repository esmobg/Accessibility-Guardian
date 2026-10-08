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
	private const DB_VERSION = '1.2.0';

	/**
	 * Option key that stores the installed schema version.
	 */
	private const DB_VERSION_OPTION = 'accg_db_version';

	/**
	 * Table name suffixes (appended to the site's table prefix).
	 *
	 * @var array<int, string>
	 */
	public const TABLES = array( 'accg_scans', 'accg_issues', 'accg_history' );

	/**
	 * Run on plugin activation.
	 *
	 * @param bool $network_wide Whether the plugin is being network-activated.
	 */
	public static function activate( $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::install_site();
				restore_current_blog();
			}

			return;
		}

		self::install_site();
	}

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		// Intentionally keep data on deactivation; cleanup happens on uninstall.
	}

	/**
	 * Ensure the schema and default settings exist. Cheap when up to date.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}

		self::install_site();
	}

	/**
	 * Create tables for a site added to a network where the plugin is network-active.
	 *
	 * @param \WP_Site $site New site object.
	 */
	public static function install_for_new_site( $site ): void {
		if ( ! $site instanceof \WP_Site ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active_for_network( plugin_basename( ACCG_PLUGIN_FILE ) ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		self::install_site();
		restore_current_blog();
	}

	/**
	 * Drop this plugin's tables when a network site is deleted.
	 *
	 * @param mixed $tables  Table names core is about to drop.
	 * @param int   $site_id Site being deleted.
	 * @return mixed
	 */
	public static function drop_tables_for_site( $tables, $site_id = 0 ) {
		global $wpdb;

		if ( ! is_array( $tables ) ) {
			return $tables;
		}

		$prefix = $wpdb->get_blog_prefix( (int) $site_id );
		foreach ( self::TABLES as $suffix ) {
			$tables[] = $prefix . $suffix;
		}

		return $tables;
	}

	/**
	 * Install tables and default settings for the current site.
	 */
	private static function install_site(): void {
		self::install_tables();
		self::seed_default_settings();
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
			add_option( 'accg_settings', self::default_settings() );
		}
	}

	/**
	 * Default plugin settings for a fresh install.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_settings(): array {
		return array(
			'include_post_types' => array( 'post', 'page' ),
			'include_terms'      => false,
			'wcag_level'         => 'aa',
			'best_practice'      => true,
			'fixes'              => array(),
		);
	}
}
