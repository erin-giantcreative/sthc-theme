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

?>