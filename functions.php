<?php 

add_action( 'wp_enqueue_scripts', 'salient_child_enqueue_styles', 100);

function salient_child_enqueue_styles() {
  $nectar_theme_version = nectar_get_theme_version();
  wp_enqueue_style( 'salient-child-style', get_stylesheet_directory_uri() . '/style.css', '', $nectar_theme_version );
  wp_enqueue_style( 'theme-custom-style', get_stylesheet_directory_uri() . '/assets/css/style.min.css', '', $nectar_theme_version );
  wp_register_script( 'salient-custom-js', get_stylesheet_directory_uri() . '/custom.js', array('jquery'),'',true  ); 
  wp_enqueue_script( 'salient-custom-js' );
  if ( is_front_page() ) {
    wp_enqueue_style( 'sthc-homepage-style', get_stylesheet_directory_uri() . '/assets/css/home.min.css', '', $nectar_theme_version );
  }
  if ( is_page('thank-you') ) {
    wp_enqueue_style( 'sthc-homepage-style', get_stylesheet_directory_uri() . '/assets/css/thank-you.min.css', '', $nectar_theme_version );
  }
  if ( is_page('book-a-free-tour') ) {
    wp_enqueue_style( 'sthc-forms-style', get_stylesheet_directory_uri() . '/assets/css/book-a-free-tour.min.css', '', $nectar_theme_version );
  }
  if ( is_rtl() ) {
    wp_enqueue_style(  'salient-rtl',  get_template_directory_uri(). '/rtl.css', array(), '1', 'screen' );
  }
}

// Show content beside main logo.
function sthc_header_logo_content() {

  $beside_logo_text = get_field( 'beside_logo_text', 'option' );
  $beside_logo_link = get_field( 'beside_logo_link', 'option' );

  if ( ! $beside_logo_text && ! $beside_logo_link ) {
    return;
  }
    $link_url = $beside_logo_link['url'];
    $link_title = $beside_logo_link['title'];
    $link_target = $beside_logo_link['target'] ? $link['target'] : '_self';

  $content = '<div class="header-content">' .
    '<span>' . $beside_logo_text . '</span>' .
    '<a href="' . esc_url( $link_url ) . '" target="' . esc_attr( $link_target ) . '">' . esc_html( $link_title ) . '</a>' .
  '</div>';

  echo $content;
}

add_action( 'nectar_hook_before_logo', 'sthc_header_logo_content' );




/**
 * VC: STHC Image Gallery
 */
add_action( 'vc_before_init', 'sthc_register_image_gallery_element' );
function sthc_register_image_gallery_element() {
  if ( ! function_exists( 'vc_map' ) ) return;

  vc_map( [
    'name'          => __( 'STHC Image Gallery', 'salient-child' ),
    'base'          => 'sthc_image_gallery',
    'icon'          => 'icon-wpb-images-stack',
    'category'      => __( 'Content', 'salient-child' ),
    'description'   => __( 'Gallery that only takes multiple images', 'salient-child' ),
    'html_template' => locate_template( 'custom-elements/sthc-image-gallery.php' ),
    'params'        => [
      [ 'type' => 'attach_images', 'heading' => __( 'Images', 'salient-child' ), 'param_name' => 'images', 'admin_label' => true ],
      [ 'type' => 'textfield', 'heading' => __( 'Height (Desktop, px)', 'salient-child' ), 'param_name' => 'height_desktop', 'value' => '350' ],
      [ 'type' => 'textfield', 'heading' => __( 'Height (Tablet, px)',  'salient-child' ), 'param_name' => 'height_tablet',  'value' => '280' ],
      [ 'type' => 'textfield', 'heading' => __( 'Height (Mobile, px)',  'salient-child' ), 'param_name' => 'height_mobile',  'value' => '177' ],
      [ 'type' => 'textfield', 'heading' => __( 'Gap (Desktop, px)',    'salient-child' ), 'param_name' => 'gap_desktop',    'value' => '12'  ],
      [ 'type' => 'textfield', 'heading' => __( 'Gap (Tablet, px)',     'salient-child' ), 'param_name' => 'gap_tablet',     'value' => '12'  ],
      [ 'type' => 'textfield', 'heading' => __( 'Gap (Mobile, px)',     'salient-child' ), 'param_name' => 'gap_mobile',     'value' => '12'  ],
      [ 'type' => 'textfield', 'heading' => __( 'Radius (px)',          'salient-child' ), 'param_name' => 'radius',         'value' => '6'   ],
      [ 'type' => 'textfield', 'heading' => __( 'Speed (seconds per loop)', 'salient-child' ), 'param_name' => 'speed', 'value' => '50' ],
    ],
  ] );
}

/**
 * Register CSS/JS for STHC Amenities Carousel.
 *
 * These are only enqueued when the VC element is actually rendered,
 * inside the template file. Here we just register handles and paths.
 */
add_action( 'wp_enqueue_scripts', 'sthc_register_amenities_carousel_assets', 5 );
function sthc_register_amenities_carousel_assets() {

	// Use the Salient theme version so browsers get a new file
	// when you deploy updated assets.
	if ( function_exists( 'nectar_get_theme_version' ) ) {
		$version = nectar_get_theme_version();
	} else {
		$version = wp_get_theme()->get( 'Version' );
	}

	// CSS: compiled from your SCSS into this file.
	wp_register_style(
		'sthc-amenities-carousel',
		get_stylesheet_directory_uri() . '/assets/css/sthc-amenities-carousel.min.css',
		array(), // No dependencies.
		$version
	);

	// JS: small vanilla script that controls autoplay, pausing,
	// keyboard control, and the progress line for each slide.
	wp_register_script(
		'sthc-amenities-carousel',
		get_stylesheet_directory_uri() . '/assets/js/sthc-amenities-carousel.js',
		array(), // No dependencies – pure JS, no jQuery requirement.
		$version,
		true // Load in footer for better performance.
	);
}

/**
 * VC: STHC Amenities Carousel
 *
 * AODA–friendly carousel with:
 * - Tab-style navigation across the top
 * - One slide visible at a time
 * - Per-slide autoplay timing
 * - Pause-on-hover and a dedicated pause button
 */
add_action( 'vc_before_init', 'sthc_register_amenities_carousel_element' );
function sthc_register_amenities_carousel_element() {
	// Bail out early if WPBakery is not available.
	if ( ! function_exists( 'vc_map' ) ) {
		return;
	}

	vc_map( array(
		'name'          => __( 'STHC Amenities Carousel', 'salient-child' ),
		'base'          => 'sthc_amenities_carousel',
		'icon'          => 'icon-wpb-images-carousel',
		'category'      => __( 'Content', 'salient-child' ),
		'description'   => __( 'Accessible carousel with tab-style navigation.', 'salient-child' ),

		// This tells WPBakery to load our PHP template when the shortcode
		// renders both in the editor and on the front end.
		'html_template' => locate_template( 'custom-elements/sthc-amenities-carousel.php' ),

		'params'        => array(

			// Wrapper-level custom class. Lets you target a single instance.
			array(
				'type'        => 'textfield',
				'heading'     => __( 'Wrapper Extra Class', 'salient-child' ),
				'param_name'  => 'extra_class',
				'description' => __( 'Optional extra CSS class for this carousel instance.', 'salient-child' ),
			),

			// Slides param group: each "row" here is one slide.
			array(
				'type'        => 'param_group',
				'heading'     => __( 'Slides', 'salient-child' ),
				'param_name'  => 'slides',
				'description' => __( 'Define each slide in the carousel.', 'salient-child' ),
				'params'      => array(

					array(
						'type'        => 'textfield',
						'heading'     => __( 'Slide Navigation Name', 'salient-child' ),
						'param_name'  => 'slide_label',
						'admin_label' => true,
						'description' => __( 'Label shown in the top navigation bar.', 'salient-child' ),
					),

					array(
						'type'        => 'textfield',
						'heading'     => __( 'Autoplay Duration (seconds)', 'salient-child' ),
						'param_name'  => 'autoplay_seconds',
						'value'       => '8',
						'description' => __( 'How long this slide stays on screen before advancing.', 'salient-child' ),
					),

					array(
						'type'       => 'attach_image',
						'heading'    => __( 'Background Image (Desktop)', 'salient-child' ),
						'param_name' => 'bg_desktop',
					),

					array(
						'type'       => 'attach_image',
						'heading'    => __( 'Background Image (Tablet)', 'salient-child' ),
						'param_name' => 'bg_tablet',
					),

					array(
						'type'       => 'attach_image',
						'heading'    => __( 'Background Image (Mobile)', 'salient-child' ),
						'param_name' => 'bg_mobile',
					),

					array(
						'type'       => 'textfield',
						'heading'    => __( 'Slide Extra Class', 'salient-child' ),
						'param_name' => 'slide_class',
						'description' => __( 'Optional extra CSS class for this slide.', 'salient-child' ),
					),

					array(
						'type'       => 'textfield',
						'heading'    => __( 'Title', 'salient-child' ),
						'param_name' => 'title',
					),

					array(
						'type'       => 'textarea',
						'heading'    => __( 'Content', 'salient-child' ),
						'param_name' => 'content',
					),

					array(
						'type'       => 'vc_link',
						'heading'    => __( 'Link', 'salient-child' ),
						'param_name' => 'link',
						'description' => __( 'Optional link (URL, text, target).', 'salient-child' ),
					),
				),
			),
		),
	) );
}

/**
 * Register CSS/JS for the STHC Image Strip Slider.
 *
 * Files are only enqueued from the template when the shortcode
 * is rendered on a page.
 */
add_action( 'wp_enqueue_scripts', 'sthc_register_image_strip_assets', 5 );
function sthc_register_image_strip_assets() {

  // Get the theme version so browsers can cache-bust when you deploy.
  if ( function_exists( 'nectar_get_theme_version' ) ) {
    $version = nectar_get_theme_version();
  } else {
    $theme   = wp_get_theme();
    $version = $theme ? $theme->get( 'Version' ) : null;
  }

  // CSS compiled from your SCSS.
  wp_register_style(
    'sthc-image-strip-slider',
    get_stylesheet_directory_uri() . '/assets/css/sthc-image-strip-slider.min.css',
    array(),
    $version
  );

  // Small vanilla JS file for strip movement.
  wp_register_script(
    'sthc-image-strip-slider',
    get_stylesheet_directory_uri() . '/assets/js/sthc-image-strip-slider.js',
    array(),
    $version,
    true // Load in footer.
  );
}

/**
 * WPBakery element: STHC Image Strip Slider.
 *
 * Simple three-up gallery with previous/next arrows.
 */
add_action( 'vc_before_init', 'sthc_register_image_strip_slider_element' );
function sthc_register_image_strip_slider_element() {

  // Do nothing if VC is not active.
  if ( ! function_exists( 'vc_map' ) ) {
    return;
  }

  vc_map( array(
    'name'        => __( 'STHC Image Strip Slider', 'salient-child' ),
    'base'        => 'sthc_image_strip_slider',
    'icon'        => 'icon-wpb-images-carousel',
    'category'    => __( 'Content', 'salient-child' ),
    'description' => __( 'Three-up image slider with arrows.', 'salient-child' ),

    // Point to the PHP template file we will create next.
    'html_template' => locate_template( 'custom-elements/sthc-image-strip-slider.php' ),

    'params'      => array(
      array(
        'type'        => 'textfield',
        'heading'     => __( 'Wrapper Extra Class', 'salient-child' ),
        'param_name'  => 'extra_class',
        'description' => __( 'Optional extra CSS class for this gallery.', 'salient-child' ),
      ),
      array(
        'type'        => 'attach_images',
        'heading'     => __( 'Images', 'salient-child' ),
        'param_name'  => 'images',
        'admin_label' => true,
        'description' => __( 'Pick the images shown in the slider.', 'salient-child' ),
      ),
      array(
        'type'        => 'textfield',
        'heading'     => __( 'Border Radius (px)', 'salient-child' ),
        'param_name'  => 'radius',
        'value'       => '0',
        'description' => __( 'Corner radius for each image.', 'salient-child' ),
      ),
      array(
        'type'        => 'textfield',
        'heading'     => __( 'Gap Between Images (px)', 'salient-child' ),
        'param_name'  => 'gap',
        'value'       => '12',
        'description' => __( 'Horizontal gap between images.', 'salient-child' ),
      ),
    ),
  ) );
}

?>