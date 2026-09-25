<?php
/**
 * Каталог: фильтр по категории и цене, сортировка, карточка товара в сетке.
 */

defined( 'ABSPATH' ) || exit;

/* Сортировка: популярные, новинки, по цене. По умолчанию — популярные. */
add_filter( 'woocommerce_catalog_orderby', function () {
	return array(
		'popularity' => 'Популярные',
		'date'       => 'Новинки',
		'price'      => 'Сначала дешевле',
		'price-desc' => 'Сначала дороже',
	);
} );
add_filter( 'woocommerce_default_catalog_orderby', function () {
	return 'popularity';
} );

add_filter( 'loop_shop_columns', function () {
	return 4;
} );
add_filter( 'loop_shop_per_page', function () {
	return 24;
} );

/* Панель фильтров: категории чипами, цена от/до (штатные параметры min_price/max_price WooCommerce). */
add_action( 'woocommerce_before_shop_loop', 'flowy_filters', 5 );
add_action( 'woocommerce_no_products_found', 'flowy_filters', 5 );
function flowy_filters() {
	if ( ! ( is_shop() || is_product_category() ) ) {
		return;
	}
	$current = is_product_category() ? get_queried_object_id() : 0;
	$cats    = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'exclude' => get_option( 'default_product_cat' ), 'menu_order' => 'asc' ) );
	$base    = is_product_category() ? get_term_link( $current ) : get_permalink( wc_get_page_id( 'shop' ) );
	$min     = isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$max     = isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$keep    = array_filter( array( 'min_price' => $min, 'max_price' => $max, 'orderby' => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : '' ) ); // phpcs:ignore
	?>
	<div class="fl-filters">
		<nav class="fl-chips" aria-label="Категории">
			<a class="fl-chip" href="<?php echo esc_url( add_query_arg( $keep, get_permalink( wc_get_page_id( 'shop' ) ) ) ); ?>"<?php echo $current ? '' : ' aria-current="page"'; ?>>Все товары</a>
			<?php if ( ! is_wp_error( $cats ) ) : foreach ( $cats as $cat ) : ?>
				<a class="fl-chip" href="<?php echo esc_url( add_query_arg( $keep, get_term_link( $cat ) ) ); ?>"<?php echo $current === $cat->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $cat->name ); ?> <small><?php echo (int) $cat->count; ?></small></a>
			<?php endforeach; endif; ?>
		</nav>
		<form class="fl-price" method="get" action="<?php echo esc_url( $base ); ?>">
			<label for="fl-min">Цена, ₽</label>
			<input id="fl-min" name="min_price" inputmode="numeric" placeholder="от" value="<?php echo esc_attr( $min ); ?>">
			<input id="fl-max" name="max_price" inputmode="numeric" placeholder="до" value="<?php echo esc_attr( $max ); ?>" aria-label="Цена до">
			<?php if ( ! empty( $keep['orderby'] ) ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( $keep['orderby'] ); ?>"><?php endif; ?>
			<button type="submit" class="fl-btn">Показать</button>
			<?php if ( $min || $max ) : ?><a class="fl-reset" href="<?php echo esc_url( $base ); ?>">Сбросить</a><?php endif; ?>
		</form>
	</div>
	<?php
}

/* Метка «Новинка» по тегу товара novinka. */
add_action( 'woocommerce_before_shop_loop_item_title', function () {
	global $product;
	if ( has_term( 'novinka', 'product_tag', $product->get_id() ) ) {
		echo '<span class="fl-badge">Новинка</span>';
	}
}, 9 );

/* Под названием — короткий состав. */
add_action( 'astra_woo_shop_title_after', function () {
	global $product;
	$compo = $product->get_meta( '_flowy_compo' );
	if ( $compo ) {
		echo '<p class="fl-compo">' . esc_html( $compo ) . '</p>';
	}
}, 5 );

add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	return $product->is_purchasable() && $product->is_in_stock() ? 'В корзину' : $text;
}, 10, 2 );

/* Хлебные крошки на странице каталога тоже нужны. */
add_filter( 'woocommerce_show_page_title', '__return_true' );

/* Состав отдельным блоком на странице товара. */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$compo = $product->get_meta( '_flowy_compo' );
	if ( $compo ) {
		echo '<div class="fl-compo-box"><b>Состав</b><span class="fl-muted">' . esc_html( $compo ) . '</span></div>';
	}
}, 15 );

/* Короче: без рейтингов, меток и вкладки отзывов. */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['reviews'], $tabs['additional_information'] );
	return $tabs;
} );
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );
