<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Template: STHC Image Gallery (WPBakery Element) — Marquee Carousel
 *
 * Responsive controls from VC:
 * - height_desktop, height_tablet, height_mobile (px)
 * - gap (px), radius (px), speed (seconds per loop)
 *
 * @package SalientChild
 * @subpackage WPBakery_Elements
 * @since 1.5.0
 */

// ---------------------------------------------------------------------
// Extract shortcode attributes
// ---------------------------------------------------------------------
$atts  = isset( $atts ) && is_array( $atts ) ? $atts : array();
$atts  = vc_map_get_attributes( 'sthc_image_gallery', $atts );

$images          = isset( $atts['images'] ) ? $atts['images'] : '';
$h_desktop_raw   = isset( $atts['height_desktop'] ) ? $atts['height_desktop'] : '350';
$h_tablet_raw    = isset( $atts['height_tablet'] )  ? $atts['height_tablet']  : '280';
$h_mobile_raw    = isset( $atts['height_mobile'] )  ? $atts['height_mobile']  : '180';
$gap_desktop_raw = isset( $atts['gap_desktop'] )    ? $atts['gap_desktop']    : '12';
$gap_tablet_raw  = isset( $atts['gap_tablet'] )     ? $atts['gap_tablet']     : '12';
$gap_mobile_raw  = isset( $atts['gap_mobile'] )     ? $atts['gap_mobile']     : '12';
$radius_raw      = isset( $atts['radius'] )         ? $atts['radius']         : '6';
$speed_raw       = isset( $atts['speed'] )          ? $atts['speed']          : '25';

// Cast to ints/floats safely
$h_desktop   = max( 1, (int) $h_desktop_raw );
$h_tablet    = max( 1, (int) $h_tablet_raw );
$h_mobile    = max( 1, (int) $h_mobile_raw );
$gap_desktop = max( 0, (int) $gap_desktop_raw );
$gap_tablet  = max( 0, (int) $gap_tablet_raw );
$gap_mobile  = max( 0, (int) $gap_mobile_raw );
$radius      = max( 0, (int) $radius_raw );
$speed       = max( 1, (float) $speed_raw );

if ( empty( $images ) ) return;
$image_ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $images ) ) );
if ( empty( $image_ids ) ) return;

// Unique instance ID for scoping
$instance_id = 'sthc-marquee-' . uniqid();

// Skip cache in previews or while editing
$is_preview = is_preview() || ( isset( $_GET['vc_editable'] ) && $_GET['vc_editable'] === 'true' );
$cache_key   = 'sthc_image_marquee_' . md5( $images . '|' . $h_desktop . '|' . $h_tablet . '|' . $h_mobile . '|' . $gap_desktop . '|' . $gap_tablet . '|' . $gap_mobile . '|' . $radius . '|' . $speed );
$cache_group = 'sthc_shortcodes';
$use_cache   = ! is_user_logged_in() && ! $is_preview;

if ( $use_cache && $cached = wp_cache_get( $cache_key, $cache_group ) ) { echo $cached; return; }

// Build seamless list
$marquee_ids = array_merge( $image_ids, $image_ids );

// ---------------------------------------------------------------------
// Build output
// ---------------------------------------------------------------------
ob_start(); ?>

<style>
	/* Scoped styles per instance */
	#<?php echo esc_attr( $instance_id ); ?> {
		position: relative;
		width: 100%;
    margin-bottom: 0;
	}
	#<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__track {
		display: inline-flex;
		align-items: center;
		gap: <?php echo (int) $gap_desktop; ?>px;
		will-change: transform;
	}
	#<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__item {
		height: <?php echo (int) $h_desktop; ?>px; /* desktop default */
		width: auto;
		flex: 0 0 auto;
		object-fit: cover;
		border-radius: <?php echo (int) $radius; ?>px;
		display: block;
	}

	/* Tablet: 768–1024 */
	@media (max-width: 1024px) {
    #<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__track {
      gap: <?php echo (int) $gap_tablet; ?>px;
    }
		#<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__item {
			height: <?php echo (int) $h_tablet; ?>px;
		}
	}
	/* Mobile: ≤767 */
	@media (max-width: 767px) {
    #<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__track {
      gap: <?php echo (int) $gap_mobile; ?>px;
    }
		#<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__item {
			height: <?php echo (int) $h_mobile; ?>px;
		}
	}
	@media (prefers-reduced-motion: reduce) {
		#<?php echo esc_attr( $instance_id ); ?> .sthc-marquee__track {
			animation: none !important;
			transform: none !important;
		}
	}
</style>

<div id="<?php echo esc_attr( $instance_id ); ?>"
	 class="sthc-marquee"
	 aria-label="<?php echo esc_attr__( 'Image marquee carousel', 'salient-child' ); ?>"
	 data-speed="<?php echo esc_attr( $speed ); ?>">
	<div class="sthc-marquee__track">
		<?php
		$first_set = count( $image_ids );
		foreach ( $marquee_ids as $i => $id ) {
			$attrs = array(
				'class'    => 'sthc-marquee__item',
				'loading'  => 'lazy',
				'decoding' => 'async',
			);
			if ( $i >= $first_set ) {
				$attrs['alt']         = '';
				$attrs['aria-hidden'] = 'true';
			} else {
				$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
				if ( ! $alt ) $alt = get_the_title( $id );
				$attrs['alt'] = esc_attr( $alt );
			}
			echo wp_get_attachment_image( $id, 'medium_large', false, $attrs );
		}
		?>
	</div>
</div>

<script>
/**
 * STHC Image Marquee — Responsive, scoped, and resize-safe.
 * - Reads speed from data attribute per instance.
 * - Measures true width after images load.
 * - Recalculates on resize (debounced).
 * — Responsive, scoped, and resume on hover-out.
 */
(function() {
	const id = <?php echo json_encode( $instance_id ); ?>;
	const marquee = document.getElementById(id);
	if (!marquee) return;

	const track = marquee.querySelector('.sthc-marquee__track');
	if (!track) return;

	let rafId = null;
	let paused = false;
	let lastTime = null;
	let scrollAmount = 0;
	let distance = 0; // one set width (half the track)
	let pps = 0;      // pixels per second

	function compute() {
		// Duplicate if content shorter than twice container width
		if (track.scrollWidth < marquee.offsetWidth * 2) {
			track.innerHTML += track.innerHTML;
		}
		distance = track.scrollWidth / 2;
		const speed = parseFloat(marquee.getAttribute('data-speed')) || 50;
		pps = distance / speed;
		scrollAmount = 0;
		track.style.transform = 'translateX(0)';
	}

	function step(ts) {
		if (paused) {
			rafId = requestAnimationFrame(step);
			return;
		}
		if (!lastTime) lastTime = ts;
		const delta = (ts - lastTime) / 1000;
		lastTime = ts;

		scrollAmount += pps * delta;
		if (scrollAmount >= distance) scrollAmount = 0;

		track.style.transform = 'translateX(' + (-scrollAmount) + 'px)';
		rafId = requestAnimationFrame(step);
	}

	function start() {
		cancelAnimationFrame(rafId);
		lastTime = null;
		rafId = requestAnimationFrame(step);
	}

	function recomputeAndRestart() {
		compute();
		start();
	}

	// Handle hover pause/resume
	marquee.addEventListener('mouseenter', function() {
		paused = true;
	});
	marquee.addEventListener('mouseleave', function() {
		paused = false;
		lastTime = null; // reset delta timing for smoother resume
	});

	// Debounced resize handling
	let resizeTimer;
	window.addEventListener('resize', function() {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(recomputeAndRestart, 150);
	});

	// Init once images are loaded
	if (document.readyState === 'complete') {
		recomputeAndRestart();
	} else {
		window.addEventListener('load', recomputeAndRestart, { once: true });
	}
})();
</script>


<?php
$output = ob_get_clean();
if ( $use_cache ) {
	wp_cache_set( $cache_key, $output, $cache_group, HOUR_IN_SECONDS );
}
echo $output;
