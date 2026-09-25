<?php
/**
 * Опции товара без платных плагинов: размер, упаковка, кашпо, открытка.
 *
 * Набор опций задаётся полем товара «Тип опций» (мета _flowy_opts):
 * bouquet — размер, упаковка, открытка; composition — размер, открытка;
 * plant — кашпо; gift — открытка.
 */

defined( 'ABSPATH' ) || exit;

function flowy_option_sets() {
	return array(
		'size' => array(
			'label'   => 'Размер',
			'default' => 'M',
			'choices' => array(
				'S' => array( 'Маленький', 'mult' => 0.75 ),
				'M' => array( 'Средний', 'mult' => 1 ),
				'L' => array( 'Большой', 'mult' => 1.45 ),
			),
		),
		'wrap' => array(
			'label'   => 'Упаковка',
			'default' => 'kraft',
			'choices' => array(
				'kraft'  => array( 'Крафт', 'add' => 0 ),
				'felt'   => array( 'Фетр', 'add' => 200 ),
				'hatbox' => array( 'Шляпная коробка', 'add' => 600 ),
			),
		),
		'pot'  => array(
			'label'   => 'Кашпо',
			'default' => 'none',
			'choices' => array(
				'none'    => array( 'Без кашпо', 'add' => 0 ),
				'ceramic' => array( 'Керамическое кашпо', 'add' => 900 ),
			),
		),
	);
}

const FLOWY_CARD_PRICE = 150;

function flowy_product_groups( $product ) {
	$type = $product->get_meta( '_flowy_opts' );
	$map  = array(
		'bouquet'     => array( 'size', 'wrap', 'card' ),
		'composition' => array( 'size', 'card' ),
		'plant'       => array( 'pot' ),
		'gift'        => array( 'card' ),
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : array();
}

function flowy_default_options( $product ) {
	$sets = flowy_option_sets();
	$out  = array();
	foreach ( flowy_product_groups( $product ) as $g ) {
		$out[ $g ] = 'card' === $g ? '' : $sets[ $g ]['default'];
	}
	return $out;
}

/** Цена единицы с опциями. База товара — цена среднего размера. */
function flowy_price_with_options( $base, $opts ) {
	$sets  = flowy_option_sets();
	$price = (float) $base;
	if ( ! empty( $opts['size'] ) ) {
		$price = round( $price * $sets['size']['choices'][ $opts['size'] ]['mult'] / 100 ) * 100;
	}
	foreach ( array( 'wrap', 'pot' ) as $g ) {
		if ( ! empty( $opts[ $g ] ) ) {
			$price += $sets[ $g ]['choices'][ $opts[ $g ] ]['add'];
		}
	}
	if ( isset( $opts['card'] ) && '' !== $opts['card'] ) {
		$price += FLOWY_CARD_PRICE;
	}
	return $price;
}

/* Поле «Тип опций» в админке товара. */
add_action( 'woocommerce_product_options_general_product_data', function () {
	woocommerce_wp_select( array(
		'id'      => '_flowy_opts',
		'label'   => 'Тип опций',
		'options' => array(
			''            => 'Без опций',
			'bouquet'     => 'Букет: размер, упаковка, открытка',
			'composition' => 'Композиция: размер, открытка',
			'plant'       => 'Растение: кашпо',
			'gift'        => 'Подарок: открытка',
		),
		'desc_tip'    => true,
		'description' => 'Цена товара — цена среднего размера. Маленький ×0,75, большой ×1,45.',
	) );
	woocommerce_wp_text_input( array( 'id' => '_flowy_compo', 'label' => 'Состав (коротко)' ) );
} );
add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	foreach ( array( '_flowy_opts', '_flowy_compo' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore
		}
	}
} );

/* Вывод опций на странице товара. */
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	$groups = flowy_product_groups( $product );
	if ( ! $groups ) {
		return;
	}
	$sets = flowy_option_sets();
	$base = (float) $product->get_price();
	echo '<div class="fl-options" data-base="' . esc_attr( $base ) . '" data-card="' . esc_attr( FLOWY_CARD_PRICE ) . '">';
	foreach ( $groups as $g ) {
		if ( 'card' === $g ) {
			echo '<div class="fl-opt-card"><label class="fl-check"><input type="checkbox" id="flowy_card_on" name="flowy_card_on" value="1"> Добавить открытку, +' . esc_html( flowy_rub( FLOWY_CARD_PRICE ) ) . '</label>';
			echo '<textarea name="flowy_card" id="flowy_card" maxlength="160" rows="3" placeholder="Текст открытки, до 160 символов" hidden></textarea></div>';
			continue;
		}
		$set = $sets[ $g ];
		echo '<fieldset class="fl-opt"><legend>' . esc_html( $set['label'] ) . '</legend><div class="fl-seg">';
		foreach ( $set['choices'] as $key => $c ) {
			$hint = 'size' === $g ? flowy_rub( flowy_price_with_options( $base, array( 'size' => $key ) ) ) : ( $c['add'] ? '+' . flowy_rub( $c['add'] ) : 'бесплатно' );
			$data = 'size' === $g ? 'data-mult="' . esc_attr( $c['mult'] ) . '"' : 'data-add="' . esc_attr( $c['add'] ) . '"';
			printf(
				'<label><input type="radio" name="flowy_%1$s" value="%2$s" %3$s %4$s><span>%5$s<small>%6$s</small></span></label>',
				esc_attr( $g ), esc_attr( $key ), $data, checked( $key, $set['default'], false ), esc_html( $c[0] ), esc_html( $hint ) // phpcs:ignore
			);
		}
		echo '</div></fieldset>';
	}
	echo '</div>';
} );

/* Кнопка «Купить сейчас» рядом с «В корзину». */
add_action( 'woocommerce_after_add_to_cart_button', function () {
	global $product;
	if ( ! $product || ! $product->is_type( 'simple' ) ) {
		return;
	}
	// Кнопка отправляет ту же форму: товар добавляется и сразу открывается оформление.
	echo '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product->get_id() ) . '">';
	echo '<button type="submit" name="flowy_buy_now" value="1" class="button fl-buy-now">Купить сейчас</button>';
} );
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
	if ( ! empty( $_REQUEST['flowy_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return wc_get_checkout_url();
	}
	return $url;
} );
add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
	return 'В корзину';
} );

/* Опции попадают в позицию корзины. Из каталога (AJAX) — значения по умолчанию. */
add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	$product = wc_get_product( $product_id );
	$groups  = flowy_product_groups( $product );
	if ( ! $groups ) {
		return $data;
	}
	$sets = flowy_option_sets();
	$opts = flowy_default_options( $product );
	foreach ( $groups as $g ) {
		if ( 'card' === $g ) {
			if ( ! empty( $_POST['flowy_card_on'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$text        = isset( $_POST['flowy_card'] ) ? sanitize_textarea_field( wp_unslash( $_POST['flowy_card'] ) ) : ''; // phpcs:ignore
				$opts['card'] = '' === trim( $text ) ? ' ' : mb_substr( $text, 0, 160 );
			}
			continue;
		}
		$val = isset( $_POST[ 'flowy_' . $g ] ) ? sanitize_key( wp_unslash( $_POST[ 'flowy_' . $g ] ) ) : ''; // phpcs:ignore
		if ( isset( $sets[ $g ]['choices'][ strtoupper( $val ) ] ) ) {
			$val = strtoupper( $val );
		}
		if ( isset( $sets[ $g ]['choices'][ $val ] ) ) {
			$opts[ $g ] = $val;
		}
	}
	$data['flowy'] = $opts;
	return $data;
}, 10, 2 );

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['flowy'] ) ) {
			continue;
		}
		$base = (float) wc_get_product( $item['product_id'] )->get_price( 'edit' );
		$item['data']->set_price( flowy_price_with_options( $base, $item['flowy'] ) );
	}
}, 20 );

function flowy_options_readable( $opts ) {
	$sets = flowy_option_sets();
	$rows = array();
	foreach ( array( 'size', 'wrap', 'pot' ) as $g ) {
		if ( ! empty( $opts[ $g ] ) ) {
			$rows[ $sets[ $g ]['label'] ] = $sets[ $g ]['choices'][ $opts[ $g ] ][0];
		}
	}
	if ( isset( $opts['card'] ) && '' !== $opts['card'] ) {
		$rows['Открытка'] = '' === trim( $opts['card'] ) ? 'без текста' : $opts['card'];
	}
	return $rows;
}

add_filter( 'woocommerce_get_item_data', function ( $display, $item ) {
	if ( ! empty( $item['flowy'] ) ) {
		foreach ( flowy_options_readable( $item['flowy'] ) as $k => $v ) {
			$display[] = array( 'key' => $k, 'value' => $v );
		}
	}
	return $display;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( ! empty( $values['flowy'] ) ) {
		foreach ( flowy_options_readable( $values['flowy'] ) as $k => $v ) {
			$line->add_meta_data( $k, $v, true );
		}
	}
}, 10, 3 );

/* В каталоге у товаров с размерами — «от» цены маленького. */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( is_admin() || is_product() && get_queried_object_id() === $product->get_id() ) {
		return $html;
	}
	if ( in_array( 'size', flowy_product_groups( $product ), true ) && $product->get_price() ) {
		return 'от ' . wc_price( flowy_price_with_options( $product->get_price(), array( 'size' => 'S' ) ) );
	}
	return $html;
}, 10, 2 );
