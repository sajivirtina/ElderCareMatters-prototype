<?php
/**
 * Lead Inbox — STUB. No lead data source exists yet (future phase: leads model +
 * intake capture + scoring + per-lead billing). Rendered with sample data.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="dash-main__header">
	<div>
		<div class="dash-main__title">Lead Inbox <span class="pd-sample-badge">Sample data</span></div>
		<div class="dash-main__sub">Geo-matched family enquiries will appear here</div>
	</div>
	<div style="display:flex;align-items:center;gap:10px;">
		<span style="font-size:0.82rem;color:var(--gray-500);">Preview</span>
	</div>
</div>

<div class="dash-main__body">

	<div class="pd-notice pd-notice--info">
		This is a preview of the Lead Inbox. Live leads, scoring, and per-lead purchasing arrive in a later phase.
	</div>

	<!-- Featured exclusive banner -->
	<div class="featured-banner">
		<div class="featured-crown">👑</div>
		<div class="featured-info">
			<h3>Featured Exclusive Access Window</h3>
			<p>Featured providers get first access to new leads before others see them.</p>
		</div>
	</div>

	<!-- Tabs (static preview) -->
	<div class="leads-tabs">
		<button class="leads-tab active" type="button">Available <span class="tab-count">3</span></button>
		<button class="leads-tab" type="button">Purchased <span class="tab-count">0</span></button>
		<button class="leads-tab" type="button">Expired <span class="tab-count">0</span></button>
	</div>

	<div class="lead-panel active">
		<?php
		$pd_sample_leads = [
			[ '🏠 Home Care', 'Urgent', 'Austin, TX', '$3,000–$4,000/mo', 82, 78, '$38' ],
			[ '⚖️ Elder Law', 'Within 1 month', 'Round Rock, TX', 'Consultation', 91, 85, '$42' ],
			[ '🧠 Memory Care', '1–3 months', 'Cedar Park, TX', '$4,500–$6,000/mo', 74, 68, '$31' ],
		];
		foreach ( $pd_sample_leads as $L ) : ?>
		<div class="lead-card-v2">
			<div class="lead-info">
				<div class="lead-chips">
					<span class="lead-type-chip"><?php echo esc_html( $L[0] ); ?></span>
					<span class="urgency-chip--urgent"><?php echo esc_html( $L[1] ); ?></span>
				</div>
				<div class="lead-meta">
					<span>📍 <?php echo esc_html( $L[2] ); ?></span>
					<span>💰 <?php echo esc_html( $L[3] ); ?></span>
				</div>
				<div class="lead-locked">🔒 Contact details hidden until purchased</div>
			</div>
			<div class="lead-scores">
				<div class="score-bar-group">
					<div class="score-bar-label"><span>Lead Quality</span><span class="score-num"><?php echo (int) $L[4]; ?></span></div>
					<div class="score-bar-track"><div class="score-bar-fill score-bar-fill--lqs" style="width:<?php echo (int) $L[4]; ?>%"></div></div>
				</div>
				<div class="score-bar-group">
					<div class="score-bar-label"><span>Buying Intent</span><span class="score-num"><?php echo (int) $L[5]; ?></span></div>
					<div class="score-bar-track"><div class="score-bar-fill score-bar-fill--lis" style="width:<?php echo (int) $L[5]; ?>%"></div></div>
				</div>
			</div>
			<div class="lead-action">
				<div class="lead-price"><?php echo esc_html( $L[6] ); ?></div>
				<button class="btn--primary btn--sm" type="button" disabled>Preview</button>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</div>
