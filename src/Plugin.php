<?php
/**
 * Main plugin bootstrap and service container.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian;

use AccessibilityGuardian\Activation\Installer;
use AccessibilityGuardian\Admin\AdminMenu;
use AccessibilityGuardian\Admin\AssetManager;
use AccessibilityGuardian\Export\ExportController;
use AccessibilityGuardian\Rules\RuleCatalog;
use AccessibilityGuardian\Scan\ResultNormalizer;
use AccessibilityGuardian\Scan\ScanController;
use AccessibilityGuardian\Scan\ScoreCalculator;
use AccessibilityGuardian\Scan\UrlProvider;
use AccessibilityGuardian\Storage\IssueRepository;
use AccessibilityGuardian\Storage\ScanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight service registry that wires the plugin together.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Resolved service instances keyed by id.
	 *
	 * @var array<string, object>
	 */
	private array $services = array();

	/**
	 * Whether init() has already run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Private constructor to enforce the singleton.
	 */
	private function __construct() {}

	/**
	 * Retrieve the shared plugin instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register the services and hooks.
	 */
	public function init(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->register_services();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( Installer::class, 'maybe_upgrade' ) );

		$this->service( AdminMenu::class )->register();
		$this->service( AssetManager::class )->register();
		$this->service( ScanController::class )->register();
		$this->service( ExportController::class )->register();
	}

	/**
	 * Load the plugin translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'accessibility-guardian',
			false,
			dirname( AG_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Resolve a registered service by class name.
	 *
	 * @template T of object
	 * @param class-string<T> $id Service class name.
	 * @return T
	 */
	public function service( string $id ): object {
		if ( ! isset( $this->services[ $id ] ) ) {
			throw new \RuntimeException( sprintf( 'Service "%s" is not registered.', $id ) );
		}

		/** @var T */
		return $this->services[ $id ];
	}

	/**
	 * Instantiate and store every service.
	 */
	private function register_services(): void {
		$scan_repository  = new ScanRepository();
		$issue_repository = new IssueRepository();
		$rule_catalog     = new RuleCatalog();
		$score_calculator = new ScoreCalculator();
		$url_provider     = new UrlProvider();
		$normalizer       = new ResultNormalizer( $rule_catalog );

		$this->services = array(
			ScanRepository::class   => $scan_repository,
			IssueRepository::class  => $issue_repository,
			RuleCatalog::class      => $rule_catalog,
			ScoreCalculator::class  => $score_calculator,
			UrlProvider::class      => $url_provider,
			ResultNormalizer::class => $normalizer,
			AssetManager::class     => new AssetManager(),
			AdminMenu::class        => new AdminMenu( $scan_repository, $issue_repository, $score_calculator ),
			ScanController::class   => new ScanController(
				$url_provider,
				$scan_repository,
				$issue_repository,
				$normalizer,
				$score_calculator
			),
			ExportController::class => new ExportController( $issue_repository, $scan_repository ),
		);
	}
}
