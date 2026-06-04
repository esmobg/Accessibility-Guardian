<?php
/**
 * Issues listing page, categorized by severity.
 *
 * @package AccessibilityGuardian
 *
 * @var int                                       $scan_id
 * @var string                                    $severity
 * @var int                                       $paged
 * @var int                                       $per_page
 * @var array<string,int>                         $severity_counts
 * @var array<int,array<string,mixed>>            $issues
 * @var \AccessibilityGuardian\Rules\RuleCatalog  $catalog
 * @var string                                    $base_url
 * @var string                                    $settings_url
 */

defined( 'ABSPATH' ) || exit;

$ag_severity_labels = array(
	'critical' => __( 'Critical', 'accessibility-guardian' ),
	'major'    => __( 'Major', 'accessibility-guardian' ),
	'minor'    => __( 'Minor', 'accessibility-guardian' ),
	'warning'  => __( 'Warning', 'accessibility-guardian' ),
);

$ag_total = array_sum( $severity_counts );
?>
<div class="wrap ag-wrap">
	<h1 class="ag-title">
		<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
		<?php esc_html_e( 'Accessibility Issues', 'accessibility-guardian' ); ?>
	</h1>

	<?php if ( 0 === $scan_id ) : ?>
		<div class="ag-empty">
			<p><?php esc_html_e( 'No scans yet. Run a scan to see categorized issues here.', 'accessibility-guardian' ); ?></p>
		</div>
	<?php else : ?>
		<ul class="ag-sev-tabs">
			<li>
				<a class="ag-sev-tab <?php echo '' === $severity ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( add_query_arg( array( 'scan_id' => $scan_id ), $base_url ) ); ?>">
					<?php esc_html_e( 'All', 'accessibility-guardian' ); ?>
					<span class="ag-sev-tab__count"><?php echo esc_html( (string) $ag_total ); ?></span>
				</a>
			</li>
			<?php foreach ( $ag_severity_labels as $ag_key => $ag_label ) : ?>
				<li>
					<a class="ag-sev-tab ag-sev-tab--<?php echo esc_attr( $ag_key ); ?> <?php echo $severity === $ag_key ? 'is-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( array( 'scan_id' => $scan_id, 'severity' => $ag_key ), $base_url ) ); ?>">
						<?php echo esc_html( $ag_label ); ?>
						<span class="ag-sev-tab__count"><?php echo esc_html( (string) ( $severity_counts[ $ag_key ] ?? 0 ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( empty( $issues ) ) : ?>
			<p class="ag-muted"><?php esc_html_e( 'No issues in this category. Great work!', 'accessibility-guardian' ); ?></p>
		<?php else : ?>
			<div class="ag-issues">
				<?php foreach ( $issues as $ag_issue ) : ?>
					<?php
					$ag_sev      = (string) $ag_issue['severity'];
					$ag_rule     = (string) $ag_issue['rule_id'];
					$ag_auto_fix = $catalog->auto_fix_for( $ag_rule );
					?>
					<article class="ag-issue ag-issue--<?php echo esc_attr( $ag_sev ); ?>">
						<header class="ag-issue__head">
							<span class="ag-pill ag-pill--<?php echo esc_attr( $ag_sev ); ?>"><?php echo esc_html( $ag_severity_labels[ $ag_sev ] ?? ucfirst( $ag_sev ) ); ?></span>
							<code class="ag-issue__rule"><?php echo esc_html( $ag_rule ); ?></code>
							<span class="ag-issue__wcag"><?php echo esc_html( (string) $ag_issue['wcag_ref'] ); ?></span>
						</header>

						<p class="ag-issue__message"><?php echo esc_html( (string) $ag_issue['message'] ); ?></p>

						<?php if ( '' !== (string) $ag_issue['url'] ) : ?>
							<p class="ag-issue__meta">
								<strong><?php esc_html_e( 'Page:', 'accessibility-guardian' ); ?></strong>
								<a href="<?php echo esc_url( (string) $ag_issue['url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( (string) $ag_issue['url'] ); ?>
								</a>
							</p>
						<?php endif; ?>

						<?php if ( '' !== (string) $ag_issue['dom_path'] ) : ?>
							<p class="ag-issue__meta">
								<strong><?php esc_html_e( 'Element:', 'accessibility-guardian' ); ?></strong>
								<code><?php echo esc_html( (string) $ag_issue['dom_path'] ); ?></code>
							</p>
						<?php endif; ?>

						<?php if ( '' !== (string) $ag_issue['html_snippet'] ) : ?>
							<pre class="ag-issue__snippet"><code><?php echo esc_html( (string) $ag_issue['html_snippet'] ); ?></code></pre>
						<?php endif; ?>

						<div class="ag-issue__fix">
							<strong><?php esc_html_e( 'Recommended fix:', 'accessibility-guardian' ); ?></strong>
							<span><?php echo esc_html( (string) $ag_issue['fix_suggestion'] ); ?></span>
						</div>

						<footer class="ag-issue__foot">
							<?php if ( null !== $ag_auto_fix ) : ?>
								<span class="ag-autofix-badge">
									<span class="dashicons dashicons-admin-tools" aria-hidden="true"></span>
									<?php
									printf(
										/* translators: %s: auto-fix option name. */
										esc_html__( 'Auto-fix available: %s', 'accessibility-guardian' ),
										esc_html( $ag_auto_fix['label'] )
									);
									?>
									<a href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Enable', 'accessibility-guardian' ); ?></a>
								</span>
							<?php endif; ?>
							<?php if ( '' !== (string) $ag_issue['doc_link'] ) : ?>
								<a class="ag-issue__doc" href="<?php echo esc_url( (string) $ag_issue['doc_link'] ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'Documentation', 'accessibility-guardian' ); ?>
									<span class="dashicons dashicons-external" aria-hidden="true"></span>
								</a>
							<?php endif; ?>
						</footer>
					</article>
				<?php endforeach; ?>
			</div>

			<?php
			$ag_filtered_total = '' !== $severity ? (int) ( $severity_counts[ $severity ] ?? 0 ) : $ag_total;
			$ag_total_pages    = (int) max( 1, (int) ceil( $ag_filtered_total / $per_page ) );
			if ( $ag_total_pages > 1 ) :
				$ag_page_links = paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%', add_query_arg( array_filter( array( 'scan_id' => $scan_id, 'severity' => $severity ) ), $base_url ) ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $ag_total_pages,
						'prev_text' => __( '&laquo; Previous', 'accessibility-guardian' ),
						'next_text' => __( 'Next &raquo;', 'accessibility-guardian' ),
					)
				);
				if ( $ag_page_links ) :
					?>
					<nav class="ag-pagination" aria-label="<?php esc_attr_e( 'Issues pagination', 'accessibility-guardian' ); ?>">
						<?php echo wp_kses_post( $ag_page_links ); ?>
					</nav>
					<?php
				endif;
			endif;
			?>
		<?php endif; ?>
	<?php endif; ?>
</div>
