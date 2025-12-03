<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Template: STHC Image Strip Slider
 *
 * Layout
 * - Root <section> wraps a horizontal strip of images.
 * - 1 image is visible on small screens, 2 on tablet, 3 on desktop.
 * - Left and right buttons move the strip by one image at a time.
 *
 * Accessibility
 * - Root uses role="group" and aria-roledescription="carousel".
 * - Buttons are real <button> elements with clear labels.
 * - A live region announces the current image index.
 *
 * Performance
 * - No autoplay, so motion only happens after user input.
 * - CSS and JS are registered in functions.php and only enqueued here.
 */

// --------------------------------------------------------------
// 1. Read shortcode attributes and clean them.
// --------------------------------------------------------------

$atts = isset( $atts ) && is_array( $atts ) ? $atts : array();
$atts = vc_map_get_attributes( 'sthc_image_strip_slider', $atts );

// Wrapper custom class from the builder.
$extra_class_raw = isset( $atts['extra_class'] ) ? $atts['extra_class'] : '';

// Image IDs from attach_images come as a comma separated string.
$image_ids_raw = isset( $atts['images'] ) ? $atts['images'] : '';

// Gap and radius are numeric strings.
$gap_raw    = isset( $atts['gap'] ) ? $atts['gap'] : '12';
$radius_raw = isset( $atts['radius'] ) ? $atts['radius'] : '0';

// Clean wrapper class to safe CSS characters only.
$extra_class = trim( preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $extra_class_raw ) );

// Turn image IDs into an array of integers.
$image_ids = array();
if ( ! empty( $image_ids_raw ) ) {
  $parts = explode( ',', $image_ids_raw );
  foreach ( $parts as $part ) {
    $id = absint( $part );
    if ( $id ) {
      $image_ids[] = $id;
    }
  }
}

// If there are no images, we do not output anything.
if ( empty( $image_ids ) ) {
  return;
}

// Gap and radius as safe integers.
$gap    = is_numeric( $gap_raw )    ? (int) $gap_raw    : 12;
$radius = is_numeric( $radius_raw ) ? (int) $radius_raw : 0;

// Detect VC preview or WP preview so we can skip cache in those cases.
$is_preview = is_preview() || ( isset( $_GET['vc_editable'] ) && 'true' === $_GET['vc_editable'] );

// Unique ID for this gallery, used by JS and CSS.
$instance_id = 'sthc-image-strip-' . uniqid();

// --------------------------------------------------------------
// 2. Simple HTML fragment cache for logged-out visitors.
// --------------------------------------------------------------

$cache_group = 'sthc_shortcodes';
$cache_key   = 'sthc_image_strip_' . md5(
  wp_json_encode( $image_ids ) . '|' . $gap . '|' . $radius . '|' . $extra_class
);

// Only cache when the user is logged out and not in a preview.
$use_cache = ! is_user_logged_in() && ! $is_preview;

if ( $use_cache ) {
  $cached = wp_cache_get( $cache_key, $cache_group );
  if ( $cached ) {
    echo $cached;
    return;
  }
}

// --------------------------------------------------------------
// 3. Enqueue CSS/JS and begin buffer.
// --------------------------------------------------------------

wp_enqueue_style( 'sthc-image-strip-slider' );
wp_enqueue_script( 'sthc-image-strip-slider' );

ob_start();

// Use small CSS variables so the SCSS can stay generic.
$style_parts   = array();
$style_parts[] = '--sthc-image-strip-gap:' . $gap . 'px';
$style_parts[] = '--sthc-image-strip-radius:' . $radius . 'px';
$style_attr    = implode( ';', $style_parts );
?>

<section
  id="<?php echo esc_attr( $instance_id ); ?>"
  class="sthc-image-strip-slider<?php echo $extra_class ? ' ' . esc_attr( $extra_class ) : ''; ?>"
  data-sthc-image-strip-slider="1"
  style="<?php echo esc_attr( $style_attr ); ?>"
  role="group"
  aria-roledescription="<?php esc_attr_e( 'carousel', 'salient-child' ); ?>"
  aria-label="<?php esc_attr_e( 'Image gallery', 'salient-child' ); ?>"
>
  <div class="sthc-image-strip-slider__viewport">
    <div class="sthc-image-strip-slider__track" data-gallery-track>
      <?php foreach ( $image_ids as $index => $image_id ) : ?>
        <figure
          class="sthc-image-strip-slider__item"
          data-gallery-item="<?php echo esc_attr( $index ); ?>"
        >
          <?php
          // Standard WordPress image output.
          // Alt text is managed in the media library.
          echo wp_get_attachment_image(
            $image_id,
            'large',
            false,
            array(
              'class'   => 'sthc-image-strip-slider__image',
              'loading' => 'lazy',
            )
          );
          ?>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="sthc-image-strip-slider__controls">
    <button
      type="button"
      class="sthc-image-strip-slider__arrow sthc-image-strip-slider__arrow--prev"
      data-gallery-prev
      aria-label="<?php esc_attr_e( 'Show previous images', 'salient-child' ); ?>"
      disabled
    >
      <span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path d="M12.207 5.70718L10.7928 4.29297L3.08569 12.0001L10.7928 19.7072L12.207 18.293L6.91404 13H20.9999V11H6.9142L12.207 5.70718Z" fill="black" stroke="black"/>
</svg></span>
    </button>

    <button
      type="button"
      class="sthc-image-strip-slider__arrow sthc-image-strip-slider__arrow--next"
      data-gallery-next
      aria-label="<?php esc_attr_e( 'Show next images', 'salient-child' ); ?>"
    >
      <span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
  <path d="M11.7929 18.293L13.2071 19.7072L20.9142 12.0001L13.2071 4.29297L11.7929 5.70718L17.0859 11.0002H3V13.0002H17.0857L11.7929 18.293Z" fill="black" stroke="black"/>
</svg></span>
    </button>

    <p class="sthc-image-strip-slider__status" aria-live="polite">
      <?php
      // First status message for screen readers.
      printf(
        esc_html__( 'Showing image %1$d of %2$d', 'salient-child' ),
        1,
        count( $image_ids )
      );
      ?>
    </p>
  </div>
</section>

<?php
// --------------------------------------------------------------
// 4. Save cache and print output.
// --------------------------------------------------------------

$output = ob_get_clean();

if ( $use_cache ) {
  wp_cache_set( $cache_key, $output, $cache_group, HOUR_IN_SECONDS );
}

echo $output;
