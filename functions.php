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



?>