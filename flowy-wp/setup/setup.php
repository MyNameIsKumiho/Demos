<?php
/**
 * Настройка магазина Flowy одной командой:
 *   wp eval-file setup.php /путь/к/папке/с/фото
 *
 * Скрипт можно запускать повторно: товары и страницы находятся по слагу и обновляются.
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

$photos = isset( $args[0] ) ? rtrim( $args[0], '/' ) : __DIR__ . '/photos';

/* ---------- 1. Общие настройки ---------- */
update_option( 'blogname', 'Flowy' );
update_option( 'blogdescription', 'Цветочная мастерская: букеты с доставкой за 2 часа' );
update_option( 'timezone_string', 'Europe/Moscow' );
update_option( 'date_format', 'j F Y' );
update_option( 'permalink_structure', '/%postname%/' );
update_option( 'default_comment_status', 'closed' );

$wc = array(
	'woocommerce_default_country'        => 'RU',
	'woocommerce_currency'               => 'RUB',
	'woocommerce_currency_pos'           => 'right_space',
	'woocommerce_price_thousand_sep'     => ' ',
	'woocommerce_price_decimal_sep'      => ',',
	'woocommerce_price_num_decimals'     => '0',
	'woocommerce_calc_taxes'             => 'no',
	'woocommerce_enable_guest_checkout'  => 'yes',
	'woocommerce_enable_signup_and_login_from_checkout' => 'no',
	'woocommerce_enable_checkout_login_reminder' => 'no',
	'woocommerce_enable_coupons'         => 'no',
	'woocommerce_enable_reviews'         => 'no',
	'woocommerce_ship_to_countries'      => 'specific',
	'woocommerce_specific_ship_to_countries' => array( 'RU' ),
	'woocommerce_allowed_countries'      => 'specific',
	'woocommerce_specific_allowed_countries' => array( 'RU' ),
	'woocommerce_ship_to_destination'    => 'billing_only',
	'woocommerce_enable_ajax_add_to_cart' => 'yes',
	'woocommerce_cart_redirect_after_add' => 'no',
	'woocommerce_shop_page_display'      => '',
	'woocommerce_manage_stock'           => 'no',
	'woocommerce_email_from_name'        => 'Flowy',
	'woocommerce_onboarding_profile'     => array( 'skipped' => true ),
	'woocommerce_task_list_hidden'       => 'yes',
	'woocommerce_show_marketplace_suggestions' => 'no',
	'woocommerce_allow_tracking'         => 'no',
	'woocommerce_coming_soon'            => 'no',
	'woocommerce_checkout_privacy_policy_text' => 'Нажимая «Оформить заказ», вы соглашаетесь на обработку персональных данных по [privacy_policy].',
	'woocommerce_registration_privacy_policy_text' => 'Ваши данные используются только для обработки заказов, подробнее — в [privacy_policy].',
	'woocommerce_store_pages_only'       => 'no',
);
foreach ( $wc as $k => $v ) {
	update_option( $k, $v );
}
update_option( 'woocommerce_permalinks', array(
	'product_base'           => '/catalog',
	'category_base'          => 'kategoriya',
	'tag_base'               => 'metka',
	'attribute_base'         => '',
	'use_verbose_page_rules' => false,
) );

global $wp_rewrite;
$wp_rewrite->init();

/* ---------- 2. Страницы магазина ---------- */
function flowy_page( $slug, $title, $content, $extra = array() ) {
	$page = get_page_by_path( $slug );
	$data = array_merge( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
	), $extra );
	if ( $page ) {
		$data['ID'] = $page->ID;
		return wp_update_post( $data );
	}
	return wp_insert_post( $data );
}

foreach ( array( 'shop' => array( 'catalog', 'Каталог', '' ), 'cart' => array( 'korzina', 'Корзина', '[woocommerce_cart]' ), 'checkout' => array( 'oformlenie', 'Оформление заказа', '[woocommerce_checkout]' ) ) as $key => $p ) {
	$id = (int) get_option( "woocommerce_{$key}_page_id" );
	$data = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $p[0], 'post_title' => $p[1], 'post_content' => $p[2] );
	if ( $id && get_post( $id ) ) {
		$data['ID'] = $id;
		wp_update_post( $data );
	} else {
		$id = wp_insert_post( $data );
	}
	update_option( "woocommerce_{$key}_page_id", $id );
}
$myaccount = (int) get_option( 'woocommerce_myaccount_page_id' );
if ( $myaccount ) {
	wp_update_post( array( 'ID' => $myaccount, 'post_status' => 'draft' ) );
}

$info = <<<HTML
<h2>Доставка</h2>
<p>Курьер привозит заказ в выбранный интервал по городу. Стоимость — 390 ₽, при заказе от 5 000 ₽ бесплатно. Курьер позвонит за 30 минут.</p>
<p>Самовывоз — бесплатно, ул. Садовая, 12, ежедневно 9:00–21:00.</p>
<h2>Оплата</h2>
<p>Картой онлайн через ЮKassa или при получении — картой или наличными курьеру.</p>
<h2>Если букет не понравился</h2>
<p>Перед отправкой мы присылаем фото букета. Если цветы завяли раньше 3 дней, соберём новый букет.</p>
HTML;
flowy_page( 'dostavka-i-oplata', 'Доставка и оплата', $info );

$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
if ( $privacy ) {
	wp_update_post( array( 'ID' => $privacy, 'post_status' => 'publish', 'post_name' => 'politika-konfidencialnosti' ) );
}

/* ---------- 3. Доставка и оплата ---------- */
$zones = WC_Shipping_Zones::get_zones();
foreach ( $zones as $z ) {
	( new WC_Shipping_Zone( $z['id'] ) )->delete();
}
$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'Город' );
$zone->set_zone_order( 1 );
$zone->add_location( 'RU', 'country' );
$zone->save();
$methods = array(
	'flat_rate'     => array( 'title' => 'Курьер', 'cost' => '390', 'tax_status' => 'none' ),
	'free_shipping' => array( 'title' => 'Курьер, бесплатно', 'requires' => 'min_amount', 'min_amount' => '5000' ),
	'local_pickup'  => array( 'title' => 'Самовывоз, ул. Садовая, 12', 'cost' => '', 'tax_status' => 'none' ),
);
foreach ( $methods as $type => $settings ) {
	$instance = $zone->add_shipping_method( $type );
	update_option( "woocommerce_{$type}_{$instance}_settings", array_merge( array( 'enabled' => 'yes' ), $settings ) );
}

update_option( 'woocommerce_cod_settings', array(
	'enabled'            => 'yes',
	'title'              => 'При получении',
	'description'        => 'Картой или наличными курьеру. При самовывозе — в мастерской.',
	'instructions'       => 'Оплата при получении.',
	'enable_for_methods' => array(),
	'enable_for_virtual' => 'yes',
) );
foreach ( array( 'bacs', 'cheque' ) as $g ) {
	update_option( "woocommerce_{$g}_settings", array( 'enabled' => 'no' ) );
}

/* ---------- 4. Категории и товары ---------- */
$cats = array(
	'bukety'     => 'Букеты',
	'kompozicii' => 'Композиции',
	'rasteniya'  => 'Растения',
	'podarki'    => 'Подарки',
);
$cat_ids = array();
$order   = 0;
foreach ( $cats as $slug => $name ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	$cat_ids[ $slug ] = $t ? $t->term_id : wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) )['term_id'];
	update_term_meta( $cat_ids[ $slug ], 'order', $order++ );
}
if ( ! get_term_by( 'slug', 'novinka', 'product_tag' ) ) {
	wp_insert_term( 'Новинка', 'product_tag', array( 'slug' => 'novinka' ) );
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function flowy_media( $path, $title ) {
	$existing = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_flowy_src', 'meta_value' => basename( $path ), 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $existing ) {
		return $existing[0];
	}
	$tmp = wp_tempnam( basename( $path ) );
	copy( $path, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $path ), 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_flowy_src', basename( $path ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return $id;
}

$products = array(
	array( 'buket-utro-v-sadu', 'Утро в саду', 'bukety', 4900, 'bouquet', 'Пионовидные розы, кустовые розы, эвкалипт', 'Пудровый букет высотой около 45 см. Простоит 7–10 дней, если менять воду каждый день.', 'utro', 95, false ),
	array( 'buket-malinovyj-dzhem', 'Малиновый джем', 'bukety', 3600, 'bouquet', 'Кустовые розы, диантусы, гвоздика', 'Яркий плотный букет на день рождения. Высота около 40 см.', 'dzhem', 80, false ),
	array( 'buket-romashkovoe-pole', 'Ромашковое поле', 'bukety', 2400, 'bouquet', 'Ромашки, гипсофила, зелень', 'Лёгкий летний букет. Хорош как знак внимания без повода.', 'romashki', 70, false ),
	array( 'buket-25-tyulpanov', '25 тюльпанов', 'bukety', 3900, 'bouquet', '25 тюльпанов трёх цветов', 'Тюльпаны из тепличного хозяйства области, срезаны за день до доставки.', 'tulips', 88, true ),
	array( 'buket-roz-belyj-shelk', 'Белый шёлк', 'bukety', 5800, 'bouquet', 'Белые розы, эустома, эвкалипт', 'Сдержанный белый букет. Подходит для свадьбы, юбилея, благодарности.', 'shelk', 50, true ),
	array( 'shlyapnaya-korobka-pudra', 'Шляпная коробка «Пудра»', 'kompozicii', 6200, 'composition', 'Пионовидные розы, диантусы, эвкалипт во флористической губке', 'Композиции не нужна ваза: достаточно раз в два дня подливать воду в губку.', 'pudra', 60, false ),
	array( 'korzina-dacha', 'Корзина «Дача»', 'kompozicii', 4700, 'composition', 'Ромашки, тюльпаны, кустовые розы в корзине', 'Композиция в корзине из лозы, корзина остаётся у получателя.', 'dacha', 40, false ),
	array( 'mini-korobka-kompliment', 'Мини-композиция «Комплимент»', 'kompozicii', 2900, 'composition', 'Розы и зелень в стеклянной вазе', 'Маленькая композиция, которую удобно поставить на рабочий стол.', 'kompliment', 45, true ),
	array( 'aloe-v-kashpo', 'Алоэ в бетонном кашпо', 'rasteniya', 2700, 'plant', 'Высота 30–35 см, горшок 14 см', 'Неприхотливое растение: поливать раз в две-три недели, любит светлое место.', 'aloe', 30, false ),
	array( 'monstera', 'Монстера', 'rasteniya', 4300, 'plant', 'Высота 50–60 см, горшок 19 см', 'Крупные резные листья, любит рассеянный свет и полив раз в неделю.', 'monstera', 25, false ),
	array( 'podarochnyj-nabor-prazdnik', 'Подарочный набор «Праздник»', 'podarki', 1900, 'gift', 'Травяной чай, мёд, свеча и открытка в коробке', 'Хорошее дополнение к букету или самостоятельный подарок.', 'nabor', 20, false ),
	array( 'svecha-inzhir-i-vanil', 'Свеча «Инжир и ваниль»', 'podarki', 1400, 'gift', 'Соевый воск, 180 мл, горит около 35 часов', 'Свеча ручной работы в стеклянном стакане.', 'svecha', 15, true ),
);

$day = 0;
foreach ( $products as $p ) {
	list( $slug, $name, $cat, $price, $opts, $compo, $desc, $photo, $sales, $is_new ) = $p;
	$found   = get_page_by_path( $slug, OBJECT, 'product' );
	$product = $found ? wc_get_product( $found->ID ) : new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_slug( $slug );
	$product->set_status( 'publish' );
	$product->set_regular_price( (string) $price );
	$product->set_short_description( $desc );
	$product->set_description( '<p>' . esc_html( $desc ) . '</p><p>Состав: ' . esc_html( $compo ) . '.</p><p>Каждый букет собираем в день доставки, поэтому состав может немного отличаться от фото: флорист заменит цветок похожим по цвету и форме.</p>' );
	$product->set_category_ids( array( $cat_ids[ $cat ] ) );
	$product->set_tag_ids( $is_new ? array( get_term_by( 'slug', 'novinka', 'product_tag' )->term_id ) : array() );
	$product->set_total_sales( $sales );
	$product->set_reviews_allowed( false );
	$product->update_meta_data( '_flowy_opts', $opts );
	$product->update_meta_data( '_flowy_compo', $compo );
	$file = "$photos/$photo.jpg";
	if ( file_exists( $file ) ) {
		$product->set_image_id( flowy_media( $file, $name ) );
	}
	$product->set_date_created( gmdate( 'Y-m-d H:i:s', strtotime( ( $is_new ? '-' . ( ++$day ) : '-' . ( 20 + ( ++$day ) ) ) . ' days' ) ) );
	$product->save();
}

/* ---------- 5. Главная в Elementor ---------- */
function flowy_el_id() {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}
function flowy_box( $classes, $children, $settings = array() ) {
	foreach ( $children as &$child ) {
		if ( 'container' === $child['elType'] ) {
			$child['isInner'] = true;
		}
	}
	unset( $child );
	return array( 'id' => flowy_el_id(), 'elType' => 'container', 'isInner' => false, 'settings' => array_merge( array( 'content_width' => 'full', 'css_classes' => $classes ), $settings ), 'elements' => $children );
}
function flowy_w( $type, $settings, $classes = '' ) {
	return array( 'id' => flowy_el_id(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => array_merge( $settings, $classes ? array( '_css_classes' => $classes ) : array() ), 'elements' => array() );
}
function flowy_img( $id ) {
	return array( 'id' => $id, 'url' => wp_get_attachment_image_url( $id, 'large' ) );
}

$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
$hero     = flowy_media( "$photos/hero.jpg", 'Букет белых тюльпанов' );
$about    = flowy_media( "$photos/about.jpg", 'Букет в мастерской Flowy' );
$cat_imgs = array(
	'bukety'     => flowy_media( "$photos/cat-bouquets.jpg", 'Букеты' ),
	'kompozicii' => flowy_media( "$photos/pudra.jpg", 'Композиции' ),
	'rasteniya'  => flowy_media( "$photos/monstera.jpg", 'Растения' ),
	'podarki'    => flowy_media( "$photos/cat-gifts.jpg", 'Подарки' ),
);

$row = array( 'flex_direction' => 'row', 'flex_wrap' => 'wrap' );
$col = array( 'flex_direction' => 'column' );

$cat_boxes = array();
foreach ( $cats as $slug => $name ) {
	$cat_boxes[] = flowy_w( 'image-box', array(
		'image'      => flowy_img( $cat_imgs[ $slug ] ),
		'title_text' => $name,
		'description_text' => '',
		'link'       => array( 'url' => get_term_link( $cat_ids[ $slug ] ), 'is_external' => '', 'nofollow' => '' ),
		'title_size' => 'h3',
	), 'fl-cat' );
}

$elements = array(
	flowy_box( 'fl-hero', array(
		flowy_box( 'fl-hero-text', array(
			flowy_w( 'heading', array( 'title' => 'Свежие букеты с доставкой за 2 часа', 'header_size' => 'h1' ) ),
			flowy_w( 'text-editor', array( 'editor' => '<p class="fl-lead">Собираем в день заказа и присылаем фото букета перед отправкой.</p>' ) ),
			flowy_w( 'button', array( 'text' => 'Выбрать букет', 'link' => array( 'url' => $shop_url, 'is_external' => '', 'nofollow' => '' ) ) ),
			flowy_w( 'text-editor', array( 'editor' => '<p class="fl-note">Бесплатная доставка от 5 000 ₽ · открытка к любому букету</p>' ) ),
		), $col ),
		flowy_box( 'fl-hero-img', array( flowy_w( 'image', array( 'image' => flowy_img( $hero ), 'image_size' => 'large' ) ) ), $col ),
	), $row ),
	flowy_box( 'fl-block', array(
		flowy_w( 'heading', array( 'title' => 'Что ищете?', 'header_size' => 'h2' ), 'fl-section-title' ),
		flowy_box( 'fl-cats', $cat_boxes, $row ),
	), $col ),
	flowy_box( 'fl-block', array(
		flowy_w( 'heading', array( 'title' => 'Чаще всего заказывают', 'header_size' => 'h2' ), 'fl-section-title' ),
		flowy_w( 'shortcode', array( 'shortcode' => '[products limit="4" columns="4" orderby="popularity"]' ) ),
		flowy_w( 'button', array( 'text' => 'Весь каталог', 'link' => array( 'url' => $shop_url, 'is_external' => '', 'nofollow' => '' ) ), 'fl-more' ),
	), $col ),
	flowy_box( 'fl-block', array(
		flowy_w( 'heading', array( 'title' => 'Отзывы', 'header_size' => 'h2' ), 'fl-section-title' ),
		flowy_box( 'fl-reviews', array(
			flowy_w( 'testimonial', array( 'testimonial_content' => 'Заказала букет маме в другой город. Прислали фото перед отправкой, привезли точно в интервал.', 'testimonial_name' => 'Марина', 'testimonial_job' => 'букет «Утро в саду»', 'testimonial_alignment' => 'left' ), 'fl-review' ),
			flowy_w( 'testimonial', array( 'testimonial_content' => 'Взял корзину «Дача» коллеге на юбилей. Цветы стояли больше недели, корзину она теперь использует для фруктов.', 'testimonial_name' => 'Алексей', 'testimonial_job' => 'корзина «Дача»', 'testimonial_alignment' => 'left' ), 'fl-review' ),
		), $row ),
	), $col ),
	flowy_box( 'fl-about', array(
		flowy_box( 'fl-about-img', array( flowy_w( 'image', array( 'image' => flowy_img( $about ), 'image_size' => 'large' ) ) ), $col ),
		flowy_box( 'fl-about-text', array(
			flowy_w( 'heading', array( 'title' => 'Flowy — цветочная мастерская на Садовой', 'header_size' => 'h2' ) ),
			flowy_w( 'text-editor', array( 'editor' => '<p class="fl-lead">Мы небольшая команда флористов. Цветы берём у проверенных поставщиков два раза в неделю, букет собираем в день доставки.</p><ul class="fl-promises"><li><b>Фото перед отправкой</b>Пришлём в мессенджер, можно попросить поправить.</li><li><b>Точно в интервал</b>Курьер позвонит за 30 минут.</li><li><b>Замена букета</b>Если цветы завяли раньше 3 дней, соберём новый.</li></ul>' ) ),
		), $col ),
	), $row ),
);

$home = flowy_page( 'glavnaya', 'Главная', '', array( 'page_template' => 'elementor_header_footer' ) );
update_post_meta( $home, '_elementor_edit_mode', 'builder' );
update_post_meta( $home, '_elementor_template_type', 'wp-page' );
update_post_meta( $home, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.0.0' );
update_post_meta( $home, '_elementor_data', wp_slash( wp_json_encode( $elements, JSON_UNESCAPED_UNICODE ) ) );
update_post_meta( $home, '_elementor_page_settings', array( 'hide_title' => 'yes' ) );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );
update_option( 'elementor_cpt_support', array( 'page' ) );
update_option( 'elementor_onboarded', true );
update_option( 'elementor_google_font', '0' );
update_option( 'elementor_font_display', 'swap' );

/* ---------- 6. SEO (Yoast) ---------- */
update_post_meta( $home, '_yoast_wpseo_title', 'Доставка цветов за 2 часа — Flowy' );
update_post_meta( $home, '_yoast_wpseo_metadesc', 'Букеты, композиции и растения с доставкой по городу за 2 часа. Собираем в день заказа, присылаем фото перед отправкой. Бесплатная доставка от 5 000 ₽.' );
$tax_meta = get_option( 'wpseo_taxonomy_meta', array() );
$seo_cats = array(
	'bukety'     => array( 'Букеты с доставкой — Flowy', 'Букеты из роз, тюльпанов, пионовидных роз и ромашек с доставкой за 2 часа. Открытка к любому букету.' ),
	'kompozicii' => array( 'Цветочные композиции в коробках и корзинах — Flowy', 'Композиции в шляпных коробках и корзинах: не нужна ваза, стоят до двух недель.' ),
	'rasteniya'  => array( 'Комнатные растения с доставкой — Flowy', 'Неприхотливые комнатные растения в кашпо с доставкой по городу.' ),
	'podarki'    => array( 'Подарки к букету — Flowy', 'Свечи, подарочные наборы и открытки, которые можно добавить к букету.' ),
);
foreach ( $seo_cats as $slug => $m ) {
	$tax_meta['product_cat'][ $cat_ids[ $slug ] ] = array( 'wpseo_title' => $m[0], 'wpseo_desc' => $m[1] );
}
update_option( 'wpseo_taxonomy_meta', $tax_meta );
$shop_id = wc_get_page_id( 'shop' );
update_post_meta( $shop_id, '_yoast_wpseo_title', 'Каталог цветов с доставкой — Flowy' );
update_post_meta( $shop_id, '_yoast_wpseo_metadesc', 'Букеты, композиции, растения и подарки. Фильтр по категории и цене, доставка за 2 часа.' );
$wpseo = get_option( 'wpseo', array() );
$wpseo['enable_xml_sitemap'] = true;
update_option( 'wpseo', $wpseo );

/* ---------- 7. Тема ---------- */
switch_theme( 'flowy' );
flush_rewrite_rules();
set_theme_mod( 'custom_logo', '' );
$astra = get_option( 'astra-settings', array() );
$astra = array_merge( $astra, array(
	'site-content-layout'        => 'plain-container',
	'ast-site-content-layout'    => 'full-width-container',
	'site-content-style'         => 'unboxed',
	'single-page-ast-content-layout' => 'full-width-container',
	'woocommerce-content-layout' => 'plain-container',
	'shop-archive-width'         => 'custom',
) );
update_option( 'astra-settings', $astra );

flush_rewrite_rules();
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}
WP_CLI::success( 'Flowy настроен. Главная: ' . get_permalink( $home ) );
