<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template: STHC Amenities Carousel (WPBakery Element)
 *
 * This template outputs:
 * - A wrapper <section> that holds the whole carousel.
 * - A top navigation bar that behaves like tabs (one per slide).
 * - One visible slide at a time. Each slide includes:
 *   - Responsive <picture> background (desktop/tablet/mobile).
 *   - Title (H3).
 *   - Body content.
 *   - Optional link.
 *
 * Accessibility notes:
 * - Navigation uses role="tablist" + role="tab" for buttons.
 * - Slides use role="tabpanel" and the HTML "hidden" attribute
 *   so assistive tech only sees the active slide.
 * - Each nav button is a real <button>, not a <a>, so keyboard
 *   users can move focus and activate slides easily.
 * - There is a visible "Pause" button (toggle) that stops and
 *   resumes autoplay.
 * - The script respects prefers-reduced-motion: if a user has
 *   that setting, autoplay is disabled.
 *
 * Performance notes:
 * - CSS/JS assets are registered globally in functions.php,
 *   but only enqueued here when the element is actually used.
 * - Markup is kept lean. All animation runs with transform()
 *   and requestAnimationFrame in a small JS file.
 *
 * @package   SalientChild
 * @subpackage WPBakery_Elements
 */

// -------------------------------------------------------------------------
// 1. Extract and normalise attributes coming from VC
// -------------------------------------------------------------------------

// WPBakery passes $atts into this template. We run it through
// vc_map_get_attributes so default values are applied.
$atts = isset( $atts ) && is_array( $atts ) ? $atts : array();
$atts = vc_map_get_attributes( 'sthc_amenities_carousel', $atts );

// Wrapper-level extra class.
$extra_class_raw = isset( $atts['extra_class'] ) ? $atts['extra_class'] : '';

// Slides come in as a URL-encoded param group string. This helper turns
// that into an array of associative arrays (one per slide).
$slides_raw = array();
if ( ! empty( $atts['slides'] ) ) {
	$slides_raw = vc_param_group_parse_atts( $atts['slides'] );
}

// If there are no slides defined, we do not output anything.
if ( empty( $slides_raw ) || ! is_array( $slides_raw ) ) {
	return;
}

// Flag for editor preview (we do not want to cache while in VC editor
// or on a preview request).
$is_preview = is_preview() || ( isset( $_GET['vc_editable'] ) && 'true' === $_GET['vc_editable'] );

// Unique instance ID so JS and CSS can scope behavior to this one
// carousel even if there are several on a page.
$instance_id = 'sthc-amenities-carousel-' . uniqid();

// Clean up the wrapper class string. This keeps only characters that
// are safe in a CSS class.
$extra_class = trim( preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $extra_class_raw ) );

// -------------------------------------------------------------------------
// 2. Normalise slides: casting and sanitising
// -------------------------------------------------------------------------

$slides      = array();
$slide_index = 0;

foreach ( $slides_raw as $slide_raw ) {

	// Skip completely empty rows (can happen if a user added then removed).
	if ( empty( $slide_raw ) || ! is_array( $slide_raw ) ) {
		continue;
	}

	// Slide navigation name. If missing, fall back to "Slide 1", "Slide 2", etc.
	$label = isset( $slide_raw['slide_label'] ) && '' !== trim( $slide_raw['slide_label'] )
		? wp_kses_post( $slide_raw['slide_label'] )
		: sprintf( __( 'Slide %d', 'salient-child' ), $slide_index + 1 );

	// Autoplay duration in seconds -> convert to ms for JS.
	$autoplay_seconds = isset( $slide_raw['autoplay_seconds'] ) ? (float) $slide_raw['autoplay_seconds'] : 8.0;
	$autoplay_seconds = $autoplay_seconds > 0 ? $autoplay_seconds : 8.0;
	$autoplay_ms      = (int) round( $autoplay_seconds * 1000 );

	// Background images: stored as attachment IDs. We will render them
	// inside a <picture> element so no inline CSS is needed.
	$bg_desktop_id = isset( $slide_raw['bg_desktop'] ) ? absint( $slide_raw['bg_desktop'] ) : 0;
	$bg_tablet_id  = isset( $slide_raw['bg_tablet'] ) ? absint( $slide_raw['bg_tablet'] )  : 0;
	$bg_mobile_id  = isset( $slide_raw['bg_mobile'] ) ? absint( $slide_raw['bg_mobile'] )  : 0;

	// Slide-specific extra class so you can style a single slide if needed.
	$slide_class = isset( $slide_raw['slide_class'] ) ? trim( preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $slide_raw['slide_class'] ) ) : '';

	// Content fields.
	$title   = isset( $slide_raw['title'] ) ? wp_kses_post( $slide_raw['title'] ) : '';
	$content = isset( $slide_raw['content'] ) ? wp_kses_post( $slide_raw['content'] ) : '';

	// VC link field structure. vc_build_link() gives us url, title, target, rel.
	$link_data = array(
		'url'    => '',
		'title'  => '',
		'target' => '',
		'rel'    => '',
	);

	if ( ! empty( $slide_raw['link'] ) ) {
		$ld = vc_build_link( $slide_raw['link'] );
		if ( is_array( $ld ) ) {
			$link_data['url']    = isset( $ld['url'] ) ? esc_url( $ld['url'] ) : '';
			$link_data['title']  = isset( $ld['title'] ) ? sanitize_text_field( $ld['title'] ) : '';
			$link_data['target'] = isset( $ld['target'] ) ? sanitize_text_field( $ld['target'] ) : '';
			$link_data['rel']    = isset( $ld['rel'] ) ? sanitize_text_field( $ld['rel'] ) : '';
		}
	}

	// Collect everything for this slide in a neat array.
	$slides[] = array(
		'label'          => $label,
		'autoplay_ms'    => $autoplay_ms,
		'bg_desktop_id'  => $bg_desktop_id,
		'bg_tablet_id'   => $bg_tablet_id,
		'bg_mobile_id'   => $bg_mobile_id,
		'slide_class'    => $slide_class,
		'title'          => $title,
		'content'        => $content,
		'link'           => $link_data,
	);

	$slide_index++;
}

// If everything was empty, do not output anything.
if ( empty( $slides ) ) {
	return;
}

// -------------------------------------------------------------------------
// 3. Simple output cache (HTML fragment) for logged-out visitors
// -------------------------------------------------------------------------

// Only cache when:
// - user is logged out (no admin bar or preview)
// - not in VC preview/editor.
$cache_group = 'sthc_shortcodes';
$cache_key   = 'sthc_amenities_carousel_' . md5(
	wp_json_encode( $slides ) . '|' . $extra_class
);

$use_cache = ! is_user_logged_in() && ! $is_preview;

if ( $use_cache ) {
	$cached = wp_cache_get( $cache_key, $cache_group );
	if ( $cached ) {
		//echo $cached;
		//return;
	}
}

// -------------------------------------------------------------------------
// 4. Enqueue assets for this instance and start output buffer
// -------------------------------------------------------------------------

// These are registered in functions.php so we can just enqueue here.
// This means the CSS/JS only loads when the shortcode is used.
wp_enqueue_style( 'sthc-amenities-carousel' );
wp_enqueue_script( 'sthc-amenities-carousel' );

// Buffer the markup so we can save it to cache at the end.
ob_start();
?>

<section
	id="<?php echo esc_attr( $instance_id ); ?>"
	class="sthc-amenities-carousel<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>"
	data-sthc-amenities-carousel="1"
	aria-roledescription="<?php esc_attr_e( 'carousel', 'salient-child' ); ?>"
>
	<div class="sthc-amenities-carousel__inner">

		<header class="sthc-amenities-carousel__header">
			<div
				class="sthc-amenities-carousel__nav"
				role="tablist"
				aria-label="<?php esc_attr_e( 'Amenities slides', 'salient-child' ); ?>"
			>
				<?php foreach ( $slides as $index => $slide ) :

					$is_active   = ( 0 === $index );
					$tab_id      = $instance_id . '-tab-' . $index;
					$panel_id    = $instance_id . '-panel-' . $index;
					$autoplay_ms = (int) $slide['autoplay_ms'];
					?>
					<button
						type="button"
						class="sthc-amenities-carousel__nav-item<?php echo $is_active ? ' is-active' : ''; ?>"
						role="tab"
						id="<?php echo esc_attr( $tab_id ); ?>"
						aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
						tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
						data-carousel-tab="<?php echo esc_attr( $index ); ?>"
						data-autoplay-ms="<?php echo esc_attr( $autoplay_ms ); ?>"
					>
						<!--
							Progress indicator:
							- Outer span holds the track.
							- Inner span is the bar that scales from 0 → 1 in JS.
							- aria-hidden because it is a visual cue; time is not
							  announced via screen reader as it would be noisy.
						-->
						<span class="sthc-amenities-carousel__nav-progress" aria-hidden="true">
							<span
								class="sthc-amenities-carousel__nav-progress-bar"
								data-carousel-progress-bar="1"
							></span>
						</span>
						<span class="sthc-amenities-carousel__nav-label">
							<?php echo esc_html( $slide['label'] ); ?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>

          <div class="sthc-amenities-carousel__controls">

            <button
                type="button"
                class="sthc-amenities-carousel__control-arrow sthc-amenities-carousel__control-arrow--prev"
                data-carousel-prev="1"
                aria-label="<?php esc_attr_e( 'Previous slide', 'salient-child' ); ?>"
            >
                <span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path d="M12.207 5.70718L10.7928 4.29297L3.08569 12.0001L10.7928 19.7072L12.207 18.293L6.91404 13H20.9999V11H6.9142L12.207 5.70718Z" fill="black" stroke="black"/>
</svg></span>
            </button>

            <p class="sthc-amenities-carousel__status" aria-live="polite">
                <?php
                printf(
                    esc_html__( 'Showing slide %1$d of %2$d', 'salient-child' ),
                    1,
                    count($slides)
                );
                ?>
            </p>

            <button
                type="button"
                class="sthc-amenities-carousel__control-arrow sthc-amenities-carousel__control-arrow--next"
                data-carousel-next="1"
                aria-label="<?php esc_attr_e( 'Next slide', 'salient-child' ); ?>"
            >
                <span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path d="M11.7929 18.293L13.2071 19.7072L20.9142 12.0001L13.2071 4.29297L11.7929 5.70718L17.0859 11.0002H3V13.0002H17.0857L11.7929 18.293Z" fill="black" stroke="black"/>
</svg></span>
            </button>

        </div>

			<!--
				Pause button:
				- Toggles autoplay on/off.
				- aria-pressed expresses the current state.
			-->
			<button
				type="button"
				class="sthc-amenities-carousel__pause"
				data-carousel-pause="1"
				aria-pressed="false"
			>
				<?php esc_html_e( 'Pause slide rotation', 'salient-child' ); ?>
			</button>
		</header>

		<div class="sthc-amenities-carousel__slides">
			<?php foreach ( $slides as $index => $slide ) :

				$is_active = ( 0 === $index );
				$tab_id    = $instance_id . '-tab-' . $index;
				$panel_id  = $instance_id . '-panel-' . $index;

				$slide_classes = array( 'sthc-amenities-carousel__slide' );
				if ( $is_active ) {
					$slide_classes[] = 'is-active';
				}
				if ( ! empty( $slide['slide_class'] ) ) {
					$slide_classes[] = $slide['slide_class'];
				}

				$slide_class_attr = implode( ' ', array_map( 'sanitize_html_class', $slide_classes ) );
				?>
        <article
          id="<?php echo esc_attr( $panel_id ); ?>"
          class="<?php echo wp_kses_post( $slide_class_attr ); ?>"
          role="tabpanel"
          aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
          aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
          data-carousel-slide="<?php echo esc_attr( $index ); ?>"
        >

					<div class="sthc-amenities-carousel__media">
						<?php
						// Render the responsive background using <picture>.
						// The image is decorative in this layout, so alt is empty.
						// If you want a meaningful alt later, you can make that
						// another field and drop it in here.
						$has_any_image = $slide['bg_desktop_id'] || $slide['bg_tablet_id'] || $slide['bg_mobile_id'];

						if ( $has_any_image ) :
							?>
							<picture class="sthc-amenities-carousel__picture">
								<?php if ( $slide['bg_desktop_id'] ) : ?>
									<source
										media="(min-width: 1025px)"
										srcset="<?php echo esc_url( wp_get_attachment_image_url( $slide['bg_desktop_id'], 'full' ) ); ?>"
									/>
								<?php endif; ?>

								<?php if ( $slide['bg_tablet_id'] ) : ?>
									<source
										media="(min-width: 768px)"
										srcset="<?php echo esc_url( wp_get_attachment_image_url( $slide['bg_tablet_id'], 'large' ) ); ?>"
									/>
								<?php endif; ?>

								<?php
								// Mobile falls back to either mobile, or desktop if mobile not set.
								$mobile_id = $slide['bg_mobile_id'] ? $slide['bg_mobile_id'] : ( $slide['bg_tablet_id'] ? $slide['bg_tablet_id'] : $slide['bg_desktop_id'] );
								if ( $mobile_id ) :
									?>
									<?php echo wp_get_attachment_image(
										$mobile_id,
										'large',
										false,
										array(
											'class' => 'sthc-amenities-carousel__image',
											'alt'   => '',
											'loading' => 'lazy',
										)
									); ?>
								<?php endif; ?>
							</picture>
						<?php endif; ?>
					</div>

					<div class="sthc-amenities-carousel__content">
						<?php if ( $slide['title'] ) : ?>
							<h3 class="sthc-amenities-carousel__title">
								<?php echo esc_html( $slide['title'] ); ?>
							</h3>
						<?php endif; ?>

						<?php if ( $slide['content'] ) : ?>
							<div class="sthc-amenities-carousel__body">
								<?php echo wp_kses_post( wpautop( $slide['content'] ) ); ?>
							</div>
						<?php endif; ?>

						<?php
						$link = $slide['link'];
						if ( ! empty( $link['url'] ) ) :
							$link_title = $link['title'] ? $link['title'] : __( 'Learn more', 'salient-child' );
							?>
							<a
								class="sthc-amenities-carousel__link"
								href="<?php echo esc_url( $link['url'] ); ?>"
								<?php if ( $link['target'] ) : ?>
									target="<?php echo esc_attr( $link['target'] ); ?>"
								<?php endif; ?>
								<?php if ( $link['rel'] ) : ?>
									rel="<?php echo esc_attr( $link['rel'] ); ?>"
								<?php endif; ?>
							>
								<span><?php echo esc_html( $link_title ); ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M8.08916 16.4226L6.91064 15.2441L12.1547 10L6.91064 4.75594L8.08916 3.57743L14.5117 10L8.08916 16.4226Z" fill="#FDDC5C" stroke="#FDDC5C"/>
                </svg>
							</a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
// -------------------------------------------------------------------------
// 5. Flush buffer and save to cache if allowed
// -------------------------------------------------------------------------

$output = ob_get_clean();

if ( $use_cache ) {
	wp_cache_set( $cache_key, $output, $cache_group, HOUR_IN_SECONDS );
}

echo $output;
