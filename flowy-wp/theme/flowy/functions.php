<?php
/**
 * Flowy — дочерняя тема Astra для магазина цветов.
 */

defined( 'ABSPATH' ) || exit;

define( 'FLOWY_VERSION', '1.0.0' );
define( 'FLOWY_DIR', get_stylesheet_directory() );
define( 'FLOWY_URI', get_stylesheet_directory_uri() );

require FLOWY_DIR . '/inc/layout.php';
require FLOWY_DIR . '/inc/catalog.php';
require FLOWY_DIR . '/inc/product-options.php';
require FLOWY_DIR . '/inc/checkout.php';
require FLOWY_DIR . '/inc/seo.php';

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'flowy-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat+Alternates:wght@600&family=Nunito:wght@400;600;700&display=swap', array(), null );
	wp_enqueue_style( 'flowy', get_stylesheet_uri(), array( 'astra-theme-css' ), FLOWY_VERSION );
	wp_enqueue_script( 'flowy', FLOWY_URI . '/assets/flowy.js', array( 'jquery' ), FLOWY_VERSION, true );
}, 20 );

add_action( 'wp_head', function () {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}, 1 );

/** Контакты магазина: меняются в «Настройки → Общие» без правки кода. */
function flowy_contacts() {
	return array(
		'phone'   => get_option( 'flowy_phone', '+7 900 000-00-00' ),
		'email'   => get_option( 'flowy_email', 'hello@flowy.example' ),
		'address' => get_option( 'flowy_address', 'ул. Садовая, 12' ),
		'hours'   => get_option( 'flowy_hours', 'Ежедневно 9:00–21:00' ),
	);
}

add_action( 'admin_init', function () {
	$fields = array(
		'flowy_phone'       => 'Телефон магазина',
		'flowy_email'       => 'Почта магазина',
		'flowy_address'     => 'Адрес (самовывоз)',
		'flowy_hours'       => 'Часы работы',
		'flowy_metrika_id'  => 'Номер счётчика Яндекс.Метрики',
	);
	add_settings_section( 'flowy', 'Flowy: контакты и аналитика', '__return_false', 'general' );
	foreach ( $fields as $key => $label ) {
		register_setting( 'general', $key, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field( $key, $label, function () use ( $key ) {
			printf( '<input type="text" class="regular-text" name="%1$s" id="%1$s" value="%2$s">', esc_attr( $key ), esc_attr( get_option( $key, '' ) ) );
		}, 'general', 'flowy' );
	}
} );

function flowy_rub( $amount ) {
	return number_format( (float) $amount, 0, ',', "\u{00A0}" ) . "\u{00A0}₽";
}
