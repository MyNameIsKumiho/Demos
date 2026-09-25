<?php
/**
 * Шапка, подвал и общие правки вёрстки Astra.
 */

defined( 'ABSPATH' ) || exit;

/* Своя шапка и подвал вместо конструктора Astra. */
add_action( 'wp', function () {
	remove_all_actions( 'astra_header' );
	remove_all_actions( 'astra_footer' );
	add_action( 'astra_header', 'flowy_header' );
	add_action( 'astra_footer', 'flowy_footer' );
} );

function flowy_logo_mark() {
	return '<svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><g fill="#8E2F45"><circle cx="15" cy="8" r="5"/><circle cx="21.7" cy="12.9" r="5"/><circle cx="19.1" cy="20.7" r="5"/><circle cx="10.9" cy="20.7" r="5"/><circle cx="8.3" cy="12.9" r="5"/></g><circle cx="15" cy="15" r="3.6" fill="#F1D9D3"/></svg>';
}

function flowy_cart_button() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$total = WC()->cart ? WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() : 0;
	ob_start();
	?>
	<a class="fl-cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="Корзина">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 11.1a2 2 0 0 1-2 1.9H8.2a2 2 0 0 1-2-1.9z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/></svg>
		<span class="fl-cart-sum"><?php echo $count ? esc_html( flowy_rub( $total ) ) : 'Корзина'; ?></span>
		<span class="fl-cart-count"><?php echo (int) $count; ?></span>
	</a>
	<?php
	return ob_get_clean();
}

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['a.fl-cart'] = flowy_cart_button();
	return $fragments;
} );

function flowy_header() {
	$c    = flowy_contacts();
	$shop = get_permalink( wc_get_page_id( 'shop' ) );
	$info = get_page_by_path( 'dostavka-i-oplata' );
	?>
	<div class="fl-topline"><div class="fl-wrap">
		<span>Доставка по городу за 2 часа, бесплатно от 5&nbsp;000&nbsp;₽</span>
		<span><?php echo esc_html( $c['hours'] ); ?></span>
	</div></div>
	<header class="fl-header" id="masthead">
		<div class="fl-wrap">
			<a class="fl-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo flowy_logo_mark(); // phpcs:ignore ?><?php bloginfo( 'name' ); ?></a>
			<nav class="fl-menu" aria-label="Разделы">
				<a href="<?php echo esc_url( $shop ); ?>"<?php echo ( is_shop() || is_product_taxonomy() || is_product() ) ? ' aria-current="page"' : ''; ?>>Каталог</a>
				<?php if ( $info ) : ?><a href="<?php echo esc_url( get_permalink( $info ) ); ?>"<?php echo is_page( $info->ID ) ? ' aria-current="page"' : ''; ?>>Доставка и оплата</a><?php endif; ?>
				<a href="#contacts">Контакты</a>
			</nav>
			<a class="fl-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $c['phone'] ) ); ?>"><?php echo esc_html( $c['phone'] ); ?></a>
			<?php echo flowy_cart_button(); // phpcs:ignore ?>
		</div>
	</header>
	<?php
}


function flowy_footer() {
	$c    = flowy_contacts();
	$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => get_option( 'default_product_cat' ), 'menu_order' => 'asc' ) );
	$info = get_page_by_path( 'dostavka-i-oplata' );
	?>
	<footer class="fl-footer" id="contacts">
		<div class="fl-wrap">
			<div class="fl-footer-cols">
				<div><span class="fl-logo"><?php bloginfo( 'name' ); ?></span><p>Цветочная мастерская. Собираем букеты в день доставки.</p></div>
				<div><h3>Контакты</h3><ul>
					<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $c['phone'] ) ); ?>"><?php echo esc_html( $c['phone'] ); ?></a></li>
					<li><a href="mailto:<?php echo esc_attr( $c['email'] ); ?>"><?php echo esc_html( $c['email'] ); ?></a></li>
					<li><?php echo esc_html( $c['address'] ); ?></li>
					<li><?php echo esc_html( $c['hours'] ); ?></li>
				</ul></div>
				<div><h3>Магазин</h3><ul>
					<?php if ( ! is_wp_error( $cats ) ) : foreach ( $cats as $cat ) : ?>
						<li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a></li>
					<?php endforeach; endif; ?>
				</ul></div>
				<div><h3>Покупателям</h3><ul>
					<?php if ( $info ) : ?><li><a href="<?php echo esc_url( get_permalink( $info ) ); ?>">Доставка и оплата</a></li><?php endif; ?>
					<?php if ( get_privacy_policy_url() ) : ?><li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Политика конфиденциальности</a></li><?php endif; ?>
				</ul></div>
			</div>
			<p class="fl-fine">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		</div>
	</footer>
	<?php
}


/* Без сайдбара и без заголовка страницы на главной. */
add_filter( 'astra_page_layout', function () {
	return 'no-sidebar';
} );
add_filter( 'astra_the_title_enabled', function ( $enabled ) {
	return is_front_page() ? false : $enabled;
} );
add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'flowy';
	return $classes;
} );

/* Хлебные крошки WooCommerce — короче и с «Главная». */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $d ) {
	$d['delimiter']   = '<span class="fl-sep">/</span>';
	$d['wrap_before'] = '<nav class="fl-crumbs" aria-label="Навигационная цепочка">';
	$d['wrap_after']  = '</nav>';
	$d['home']        = 'Главная';
	return $d;
} );

/* Уведомление «товар добавлен» не уводит со страницы. */
add_filter( 'woocommerce_cart_redirect_after_error', '__return_false' );
