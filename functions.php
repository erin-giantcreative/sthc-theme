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
  if ( is_page(1954) ) {
    wp_enqueue_style( 'sthc-homepage-style', get_stylesheet_directory_uri() . '/assets/css/thank-you.min.css', '', $nectar_theme_version );
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

?>