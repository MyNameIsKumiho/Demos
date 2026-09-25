<?php
/**
 * Оформление заказа: короткая форма, дата и интервал доставки, самовывоз без адреса.
 */

defined( 'ABSPATH' ) || exit;

function flowy_delivery_days() {
	$out = array();
	$tz  = wp_timezone();
	for ( $i = 0; $i < 7; $i++ ) {
		$d     = new DateTimeImmutable( "today +$i day", $tz );
		$label = wp_date( 'j F, D', $d->getTimestamp() );
		$out[ $d->format( 'Y-m-d' ) ] = ( 0 === $i ? 'Сегодня, ' : ( 1 === $i ? 'Завтра, ' : '' ) ) . $label;
	}
	return $out;
}

function flowy_delivery_slots() {
	return array( '10:00–12:00', '12:00–14:00', '14:00–16:00', '16:00–18:00', '18:00–20:00', '20:00–22:00' );
}

/* Поля: имя, телефон, почта, адрес. Остальное убираем. */
add_filter( 'woocommerce_checkout_fields', function ( $f ) {
	foreach ( array( 'billing_last_name', 'billing_company', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode' ) as $k ) {
		unset( $f['billing'][ $k ] );
	}
	// Страна нужна WooCommerce для расчёта доставки: оставляем поле, но прячем (продаём только по России).
	if ( isset( $f['billing']['billing_country'] ) ) {
		$f['billing']['billing_country']['class'] = array( 'fl-hidden' );
		$f['billing']['billing_country']['priority'] = 99;
	}
	$f['billing']['billing_first_name'] = array_merge( $f['billing']['billing_first_name'], array( 'label' => 'Имя', 'class' => array( 'form-row-first' ), 'priority' => 10, 'placeholder' => 'Как к вам обращаться' ) );
	$f['billing']['billing_phone']      = array_merge( $f['billing']['billing_phone'], array( 'label' => 'Телефон', 'class' => array( 'form-row-last' ), 'priority' => 20, 'required' => true, 'placeholder' => '+7 900 000-00-00' ) );
	$f['billing']['billing_email']      = array_merge( $f['billing']['billing_email'], array( 'label' => 'Почта', 'class' => array( 'form-row-wide' ), 'priority' => 30, 'placeholder' => 'Пришлём письмо с номером заказа' ) );
	$f['billing']['billing_address_1']  = array_merge( $f['billing']['billing_address_1'], array( 'label' => 'Адрес доставки', 'class' => array( 'form-row-wide', 'fl-address' ), 'priority' => 40, 'required' => false, 'placeholder' => 'Улица, дом, квартира' ) );

	$days = flowy_delivery_days();
	$f['order']['flowy_date'] = array( 'type' => 'select', 'label' => 'Дата доставки', 'required' => true, 'options' => $days, 'class' => array( 'form-row-first' ), 'priority' => 5 );
	$f['order']['flowy_slot'] = array( 'type' => 'select', 'label' => 'Время', 'required' => true, 'options' => array_combine( flowy_delivery_slots(), flowy_delivery_slots() ), 'class' => array( 'form-row-last' ), 'priority' => 6 );
	if ( isset( $f['order']['order_comments'] ) ) {
		$f['order']['order_comments']['label']       = 'Комментарий к заказу';
		$f['order']['order_comments']['placeholder'] = 'Например, позвонить получателю, а не мне';
		$f['order']['order_comments']['priority']    = 10;
		$f['order']['order_comments']['class']       = array( 'form-row-wide' );
	}
	return $f;
} );

/* Страна по умолчанию — Россия, поле скрыто. */
add_filter( 'default_checkout_billing_country', function () {
	return 'RU';
} );
add_filter( 'woocommerce_checkout_get_value', function ( $value, $input ) {
	return 'billing_country' === $input ? 'RU' : $value;
}, 10, 2 );

add_filter( 'woocommerce_enable_order_notes_field', '__return_true' );
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

function flowy_is_pickup_chosen() {
	$chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	foreach ( $chosen as $m ) {
		if ( 0 === strpos( (string) $m, 'local_pickup' ) ) {
			return true;
		}
	}
	return false;
}

/* Проверки: адрес нужен только для курьера, время — не прошедшее. */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	if ( ! flowy_is_pickup_chosen() && mb_strlen( trim( (string) $data['billing_address_1'] ) ) < 5 ) {
		$errors->add( 'billing_address_1_required', 'Укажите адрес доставки: улицу и дом.' );
	}
	$digits = preg_replace( '/\D/', '', (string) $data['billing_phone'] );
	if ( strlen( $digits ) < 10 ) {
		$errors->add( 'billing_phone_short', 'Проверьте телефон: нужно 10–11 цифр, чтобы курьер мог позвонить.' );
	}
	$date = isset( $_POST['flowy_date'] ) ? sanitize_text_field( wp_unslash( $_POST['flowy_date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$slot = isset( $_POST['flowy_slot'] ) ? sanitize_text_field( wp_unslash( $_POST['flowy_slot'] ) ) : ''; // phpcs:ignore
	if ( ! isset( flowy_delivery_days()[ $date ] ) || ! in_array( $slot, flowy_delivery_slots(), true ) ) {
		$errors->add( 'flowy_when', 'Выберите дату и время доставки.' );
	} elseif ( wp_date( 'Y-m-d' ) === $date && (int) $slot < (int) wp_date( 'G' ) + 2 ) {
		$errors->add( 'flowy_when', 'На сегодня этот интервал уже не успеть. Выберите время позже или другую дату.' );
	}
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	foreach ( array( 'flowy_date' => '_flowy_date', 'flowy_slot' => '_flowy_slot' ) as $field => $meta ) {
		if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$order->update_meta_data( $meta, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ); // phpcs:ignore
		}
	}
} );

function flowy_order_when( $order ) {
	$date = $order->get_meta( '_flowy_date' );
	$slot = $order->get_meta( '_flowy_slot' );
	if ( ! $date ) {
		return '';
	}
	return wp_date( 'j F Y', strtotime( $date . ' 12:00' ) ) . ', ' . $slot;
}

/* Дата и время в админке, письмах и на странице «Спасибо». */
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$when = flowy_order_when( $order );
	if ( $when ) {
		echo '<p><strong>Доставка:</strong> ' . esc_html( $when ) . '</p>';
	}
} );
add_filter( 'woocommerce_email_order_meta_fields', function ( $fields, $sent_to_admin, $order ) {
	$when = flowy_order_when( $order );
	if ( $when ) {
		$fields['flowy_when'] = array( 'label' => 'Дата и время', 'value' => $when );
	}
	return $fields;
}, 10, 3 );
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$when = flowy_order_when( $order );
	echo '<section class="fl-thanks-when"><h2>Получение</h2><p>' . esc_html( $order->get_shipping_method() ) . ( $when ? ', ' . esc_html( $when ) : '' ) . '</p>';
	echo '<p class="fl-muted">Флорист напишет вам, когда букет будет готов, и пришлёт фото перед отправкой.</p></section>';
}, 5 );

add_filter( 'woocommerce_thankyou_order_received_text', function () {
	return 'Спасибо! Заказ оформлен.';
} );

/* Если доступна бесплатная доставка, платный курьер не показывается. */
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	$free = array_filter( $rates, function ( $r ) {
		return 'free_shipping' === $r->get_method_id();
	} );
	if ( $free ) {
		foreach ( $rates as $id => $r ) {
			if ( 'flat_rate' === $r->get_method_id() ) {
				unset( $rates[ $id ] );
			}
		}
	}
	return $rates;
}, 100 );

add_filter( 'woocommerce_order_button_text', function () {
	return 'Оформить заказ';
} );

/* Корзина: пустое состояние с кнопкой в каталог. */
add_filter( 'woocommerce_return_to_shop_text', function () {
	return 'Перейти в каталог';
} );
