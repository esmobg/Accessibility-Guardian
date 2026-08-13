<?php
/**
 * Dashboard page template.
 *
 * @package AccessibilityGuardian
 *
 * @var array<string,mixed>|null                         $latest
 * @var array<int,array<string,mixed>>                   $recent
 * @var array<int,array<string,mixed>>                   $history
 * @var array<string,int>                                $severity_counts
 * @var array<string,int>                                $category_counts
 * @var array<int,array<string,mixed>>                   $top_rules
 * @var array{label:string,key:string}                   $band
 * @var string                                           $scan_url
 * @var \AccessibilityGuardian\Scan\ScoreCalculator      $score_calculator
 */

defined( 'ABSPATH' ) || exit;

$accg_score    = null !== $latest ? (int) $latest['score'] : 0;
$accg_errors   = null !== $latest ? (int) $latest['errors'] : 0;
$accg_warnings = null !== $latest ? (int) $latest['warnings'] : 0;
$accg_passes   = null !== $latest ? (int) $latest['passes'] : 0;
$accg_total    = null !== $latest ? (int) $latest['total_urls'] : 0;

$accg_severity_total = array_sum( $severity_counts );
$accg_history_json   = wp_json_encode(
	array_map(
		static fn ( array $row ): array => array(
			'score' => (int) $row['score'],
			'date'  => $row['created_at'],
		),
		$history
	)
);

$accg_export_base = wp_nonce_url(
	admin_url( 'admin-post.php?action=accg_export&scan_id=' . ( null !== $latest ? (int) $latest['id'] : 0 ) ),
	'accg_export'
);
?>
<div class="wrap ag-wrap">
	<h1 class="ag-title">
		<span class="dashicons dashicons-universal-access-alt" aria-hidden="true"></span>
		<?php esc_html_e( 'Accessibility Guardian', 'accessibility-guardian' ); ?>
	</h1>

	<?php if ( null === $latest ) : ?>
		<div class="ag-empty">
			<p><?php esc_html_e( 'No scans yet. Run your first accessibility scan to see results here.', 'accessibility-guardian' ); ?></p>
			<a class="button button-primary button-hero" href="<?php echo esc_url( $scan_url ); ?>">
				<?php esc_html_e( 'Run your first scan', 'accessibility-guardian' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="ag-actions">
			<a class="button button-primary" href="<?php echo esc_url( $scan_url ); ?>">
				<?php esc_html_e( 'Run new scan', 'accessibility-guardian' ); ?>
			</a>
			<span class="ag-actions__export">
				<?php esc_html_e( 'Export:', 'accessibility-guardian' ); ?>
				<a class="button" href="<?php echo esc_url( $accg_export_base . '&format=csv' ); ?>">CSV</a>
				<a class="button" href="<?php echo esc_url( $accg_export_base . '&format=json' ); ?>">JSON</a>
			</span>
		</div>

		<div class="ag-cards">
			<div class="ag-card ag-card--score ag-score--<?php echo esc_attr( $band['key'] ); ?>">
				<div class="ag-score__circle" role="img"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %d: score. */ __( 'Accessibility score %d out of 100', 'accessibility-guardian' ), $accg_score ) ); ?>"
					style="--ag-score: <?php echo esc_attr( (string) $accg_score ); ?>">
					<span class="ag-score__value"><?php echo esc_html( (string) $accg_score ); ?></span>
					<span class="ag-score__max">/100</span>
				</div>
				<p class="ag-score__band"><?php echo esc_html( $band['label'] ); ?></p>
			</div>

			<div class="ag-card">
				<span class="ag-card__label"><?php esc_html_e( 'Pages scanned', 'accessibility-guardian' ); ?></span>
				<span class="ag-card__value"><?php echo esc_html( (string) $accg_total ); ?></span>
			</div>
			<div class="ag-card ag-card--error">
				<span class="ag-card__label"><?php esc_html_e( 'Errors', 'accessibility-guardian' ); ?></span>
				<span class="ag-card__value"><?php echo esc_html( (string) $accg_errors ); ?></span>
			</div>
			<div class="ag-card ag-card--warning">
				<span class="ag-card__label"><?php esc_html_e( 'Warnings', 'accessibility-guardian' ); ?></span>
				<span class="ag-card__value"><?php echo esc_html( (string) $accg_warnings ); ?></span>
			</div>
			<div class="ag-card ag-card--pass">
				<span class="ag-card__label"><?php esc_html_e( 'Passed checks', 'accessibility-guardian' ); ?></span>
				<span class="ag-card__value"><?php echo esc_html( (string) $accg_passes ); ?></span>
			</div>
		</div>

		<div class="ag-grid">
			<section class="ag-panel" aria-labelledby="ag-severity-heading">
				<h2 id="ag-severity-heading"><?php esc_html_e( 'Issues by severity', 'accessibility-guardian' ); ?></h2>
				<ul class="ag-bars">
					<?php
					$accg_severity_labels = array(
						'critical' => __( 'Critical', 'accessibility-guardian' ),
						'major'    => __( 'Major', 'accessibility-guardian' ),
						'minor'    => __( 'Minor', 'accessibility-guardian' ),
						'warning'  => __( 'Warning', 'accessibility-guardian' ),
					);
					foreach ( $accg_severity_labels as $accg_key => $accg_label ) :
						$accg_value      = (int) ( $severity_counts[ $accg_key ] ?? 0 );
						$accg_percent    = $accg_severity_total > 0 ? round( ( $accg_value / $accg_severity_total ) * 100 ) : 0;
						$accg_issues_url = admin_url( 'admin.php?page=accessibility-guardian-issues&severity=' . $accg_key );
						?>
						<li class="ag-bars__row">
							<a class="ag-bars__link" href="<?php echo esc_url( $accg_issues_url ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: 1: count, 2: severity label. */ __( 'View %1$d %2$s issues', 'accessibility-guardian' ), $accg_value, $accg_label ) ); ?>">
								<span class="ag-bars__label"><?php echo esc_html( $accg_label ); ?></span>
								<span class="ag-bars__track">
									<span class="ag-bars__fill ag-bars__fill--<?php echo esc_attr( $accg_key ); ?>"
										style="width: <?php echo esc_attr( (string) $accg_percent ); ?>%;"></span>
								</span>
								<span class="ag-bars__value"><?php echo esc_html( (string) $accg_value ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="ag-panel" aria-labelledby="ag-category-heading">
				<h2 id="ag-category-heading"><?php esc_html_e( 'WCAG category distribution', 'accessibility-guardian' ); ?></h2>
				<?php if ( empty( $category_counts ) ) : ?>
					<p class="ag-muted"><?php esc_html_e( 'No issues recorded.', 'accessibility-guardian' ); ?></p>
				<?php else : ?>
					<ul class="ag-bars">
						<?php
						$accg_cat_max = max( $category_counts );
						foreach ( $category_counts as $accg_cat => $accg_cat_value ) :
							$accg_cat_percent = $accg_cat_max > 0 ? round( ( $accg_cat_value / $accg_cat_max ) * 100 ) : 0;
							?>
							<li class="ag-bars__row">
								<span class="ag-bars__label"><?php echo esc_html( ucfirst( (string) $accg_cat ) ); ?></span>
								<span class="ag-bars__track">
									<span class="ag-bars__fill ag-bars__fill--category"
										style="width: <?php echo esc_attr( (string) $accg_cat_percent ); ?>%;"></span>
								</span>
								<span class="ag-bars__value"><?php echo esc_html( (string) $accg_cat_value ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<section class="ag-panel" aria-labelledby="ag-trend-heading">
				<h2 id="ag-trend-heading"><?php esc_html_e( 'Progress over time', 'accessibility-guardian' ); ?></h2>
				<div id="ag-trend" class="ag-trend"
					data-history="<?php echo esc_attr( (string) $accg_history_json ); ?>"
					data-empty="<?php echo esc_attr__( 'No history yet.', 'accessibility-guardian' ); ?>"></div>
			</section>

			<section class="ag-panel" aria-labelledby="ag-top-heading">
				<h2 id="ag-top-heading"><?php esc_html_e( 'Most common problems', 'accessibility-guardian' ); ?></h2>
				<?php if ( empty( $top_rules ) ) : ?>
					<p class="ag-muted"><?php esc_html_e( 'No issues recorded.', 'accessibility-guardian' ); ?></p>
				<?php else : ?>
					<table class="widefat striped ag-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Rule', 'accessibility-guardian' ); ?></th>
								<th scope="col"><?php esc_html_e( 'WCAG', 'accessibility-guardian' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Severity', 'accessibility-guardian' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Count', 'accessibility-guardian' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_rules as $accg_rule ) : ?>
								<tr>
									<td><code><?php echo esc_html( (string) $accg_rule['rule_id'] ); ?></code></td>
									<td><?php echo esc_html( (string) $accg_rule['wcag_ref'] ); ?></td>
									<td><span class="ag-pill ag-pill--<?php echo esc_attr( (string) $accg_rule['severity'] ); ?>"><?php echo esc_html( ucfirst( (string) $accg_rule['severity'] ) ); ?></span></td>
									<td><?php echo esc_html( (string) $accg_rule['total'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
		</div>

		<section class="ag-panel" aria-labelledby="ag-recent-heading">
			<h2 id="ag-recent-heading"><?php esc_html_e( 'Recent scans', 'accessibility-guardian' ); ?></h2>
			<table class="widefat striped ag-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date', 'accessibility-guardian' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Type', 'accessibility-guardian' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Pages', 'accessibility-guardian' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Score', 'accessibility-guardian' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Errors', 'accessibility-guardian' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Warnings', 'accessibility-guardian' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'accessibility-guardian' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent as $accg_row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $accg_row['started_at'] ); ?></td>
							<td><?php echo esc_html( ucfirst( (string) $accg_row['scan_type'] ) ); ?></td>
							<td><?php echo esc_html( (string) $accg_row['total_urls'] ); ?></td>
							<td><?php echo esc_html( (string) $accg_row['score'] ); ?></td>
							<td><?php echo esc_html( (string) $accg_row['errors'] ); ?></td>
							<td><?php echo esc_html( (string) $accg_row['warnings'] ); ?></td>
							<td><span class="ag-pill ag-pill--<?php echo esc_attr( (string) $accg_row['status'] ); ?>"><?php echo esc_html( ucfirst( (string) $accg_row['status'] ) ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
</div>
