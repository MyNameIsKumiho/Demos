<?php
/**
 * Яндекс.Метрика и мелочи для SEO. Sitemap, robots.txt, title/description и
 * Schema.org делают Yoast SEO и WooCommerce.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	$id = preg_replace( '/\D/', '', (string) get_option( 'flowy_metrika_id', '' ) );
	if ( ! $id || is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
<script>
(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();
for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}
k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");
ym(<?php echo (int) $id; ?>,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true,webvisor:true,ecommerce:"dataLayer"});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?php echo (int) $id; ?>" style="position:absolute;left:-9999px;" alt=""></div></noscript>
	<?php
}, 20 );

/* Служебные страницы магазина не индексируются. */
add_filter( 'wpseo_robots', function ( $robots ) {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return 'noindex, follow';
	}
	return $robots;
} );
