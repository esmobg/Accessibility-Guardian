<?php
/**
 * Builds the list of URLs that a scan should visit.
 *
 * @package AccessibilityGuardian
 */

declare(strict_types=1);

namespace AccessibilityGuardian\Scan;

defined( 'ABSPATH' ) || exit;

/**
 * Enumerates scannable front-end URLs from WordPress content.
 */
final class UrlProvider {

	/**
	 * Build the list of URLs for a full-site scan.
	 *
	 * @param int $limit Maximum number of URLs to return (safety cap).
	 * @return array<int, array{url:string,post_id:int,label:string}>
	 */
	public function get_full_site_urls( int $limit = 500 ): array {
		$urls = array();

		$urls[] = array(
			'url'     => home_url( '/' ),
			'post_id' => 0,
			'label'   => __( 'Home page', 'accessibility-guardian' ),
		);

		foreach ( $this->scannable_post_types() as $post_type ) {
			foreach ( $this->get_post_type_urls( $post_type, $limit ) as $entry ) {
				$urls[] = $entry;
			}
		}

		if ( $this->include_terms() ) {
			foreach ( $this->get_term_urls( $limit ) as $entry ) {
				$urls[] = $entry;
			}
		}

		$urls = $this->deduplicate( $urls );

		return array_slice( $urls, 0, $limit );
	}

	/**
	 * Build a single-URL list for a single post/page scan.
	 *
	 * @param int $post_id Post identifier.
	 * @return array<int, array{url:string,post_id:int,label:string}>
	 */
	public function get_single_post_urls( int $post_id ): array {
		$permalink = get_permalink( $post_id );

		if ( false === $permalink ) {
			return array();
		}

		return array(
			array(
				'url'     => $permalink,
				'post_id' => $post_id,
				'label'   => get_the_title( $post_id ),
			),
		);
	}

	/**
	 * Resolve the post types that should be scanned.
	 *
	 * @return array<int, string>
	 */
	public function scannable_post_types(): array {
		$settings   = (array) get_option( 'ag_settings', array() );
		$configured = isset( $settings['include_post_types'] ) && is_array( $settings['include_post_types'] )
			? array_map( 'strval', $settings['include_post_types'] )
			: array();

		$public = get_post_types( array( 'public' => true ), 'names' );
		unset( $public['attachment'] );

		if ( empty( $configured ) ) {
			return array_values( $public );
		}

		return array_values( array_intersect( $configured, $public ) );
	}

	/**
	 * Get URLs for a given post type.
	 *
	 * @param string $post_type Post type name.
	 * @param int    $limit     Maximum posts to fetch.
	 * @return array<int, array{url:string,post_id:int,label:string}>
	 */
	private function get_post_type_urls( string $post_type, int $limit ): array {
		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
			)
		);

		$entries = array();

		foreach ( $query->posts as $post_id ) {
			$post_id   = (int) $post_id;
			$permalink = get_permalink( $post_id );

			if ( false === $permalink ) {
				continue;
			}

			$entries[] = array(
				'url'     => $permalink,
				'post_id' => $post_id,
				'label'   => get_the_title( $post_id ),
			);
		}

		return $entries;
	}

	/**
	 * Get URLs for public taxonomy term archives.
	 *
	 * @param int $limit Maximum terms to fetch.
	 * @return array<int, array{url:string,post_id:int,label:string}>
	 */
	private function get_term_urls( int $limit ): array {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );

		if ( empty( $taxonomies ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => array_values( $taxonomies ),
				'hide_empty' => true,
				'number'     => $limit,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$entries = array();

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$entries[] = array(
				'url'     => $link,
				'post_id' => 0,
				'label'   => sprintf(
					/* translators: 1: taxonomy name, 2: term name. */
					__( '%1$s: %2$s', 'accessibility-guardian' ),
					$term->taxonomy,
					$term->name
				),
			);
		}

		return $entries;
	}

	/**
	 * Whether term archives should be included, per settings.
	 */
	private function include_terms(): bool {
		$settings = (array) get_option( 'ag_settings', array() );

		return ! empty( $settings['include_terms'] );
	}

	/**
	 * Remove duplicate URLs while preserving order.
	 *
	 * @param array<int, array{url:string,post_id:int,label:string}> $urls Raw URL list.
	 * @return array<int, array{url:string,post_id:int,label:string}>
	 */
	private function deduplicate( array $urls ): array {
		$seen   = array();
		$result = array();

		foreach ( $urls as $entry ) {
			$key = $entry['url'];

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$result[]     = $entry;
		}

		return $result;
	}
}
