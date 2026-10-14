<?php
/**
 * Menupunktet "Webto" - kun for Webto (Lasse 2026-10-14). Kunden ser hverken menupunktet eller siderne, heller ikke som
 * administrator. Første side er Synlighed (synlighed.php); flere Webto-sider kommer under samme punkt.
 *
 * "Se som kunde": på et udviklingssite (homelab) tæller alle som Webto, så intet ville være skjult. Med "Se som kunde"
 * ser en Webto-bruger wp-admin og sitet, som kunden gør - indtil knappen i admin bar slår det fra igen.
 * webto_dashboard_is_webto() er stadig sand (adgangen til Webto-siden), men webto_dashboard_sees_as_webto() er falsk.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

const WEBTO_DASHBOARD_SE_SOM_KUNDE = 'webto_dashboard_se_som_kunde';

/**
 * Har Webto-brugeren slået "Se som kunde" til?
 */
function webto_dashboard_preview_customer(): bool {
	return webto_dashboard_is_webto() && (bool) get_user_meta( get_current_user_id(), WEBTO_DASHBOARD_SE_SOM_KUNDE, true );
}

/**
 * Ser brugeren lige nu wp-admin som Webto? (Falsk for kunder og for Webto under "Se som kunde".) Brug den til alt, der
 * skal skjules for kunder.
 */
function webto_dashboard_sees_as_webto(): bool {
	return webto_dashboard_is_webto() && ! webto_dashboard_preview_customer();
}

/**
 * Må brugeren åbne Webto-siderne? (Webto-administratorer - også under "Se som kunde", så de kan komme tilbage.)
 */
function webto_dashboard_can_webto(): bool {
	return webto_dashboard_is_webto() && current_user_can( 'manage_options' );
}

/**
 * Adressen, der slår "Se som kunde" til eller fra.
 *
 * @param bool $on Til (true) eller fra.
 */
function webto_dashboard_preview_url( bool $on ): string {
	return wp_nonce_url( add_query_arg( array( 'action' => 'webto_dashboard_se_som_kunde', 'til' => $on ? '1' : '0' ), admin_url( 'admin-post.php' ) ), 'webto_dashboard_se_som_kunde' );
}

/**
 * Menuikonet: W'et fra Webtos logo (WordPress farver det som de andre ikoner).
 */
function webto_dashboard_menu_icon(): string {
	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-10 -24 384 384"><path fill="black" transform="translate(-273.57 -216.26)" d="M362.77,524.66l-89.2-280h46l54,175.2a121.2,121.2,0,0,1,4.4,15q2,8.61,3.6,17.4t3,16.4q1.4,7.6,2.6,12.4l-11.6-.4q6-21.2,9.4-32.8t5.4-17.8q2-6.19,3.6-10.6l50.4-126.4h38.8L532,419.46q4,9.6,7,19t5.4,18.8q2.4,9.4,5.2,19.8l-10.4,2.8q2-10.8,3-17c.66-4.13,1.27-7.53,1.8-10.2s1.07-5.26,1.6-7.8,1.4-5.8,2.6-9.8,2.87-9.46,5-16.4l53.2-174h46.4l-89.2,280h-30l-74-175.2,3.6,1.6L392,524.66Z"/></svg>';
	return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

add_action(
	'admin_menu',
	static function () {
		if ( ! webto_dashboard_can_webto() ) {
			return;
		}
		add_menu_page( __( 'Webto', 'webto-dashboard' ), __( 'Webto', 'webto-dashboard' ), 'manage_options', 'webto', 'webto_dashboard_visibility_page', webto_dashboard_menu_icon(), '3.5' );
		add_submenu_page( 'webto', __( 'Synlighed', 'webto-dashboard' ), __( 'Synlighed', 'webto-dashboard' ), 'manage_options', 'webto', 'webto_dashboard_visibility_page' );
	}
);

// Under "Se som kunde" er menupunktet væk som for kunden (siden kan stadig åbnes, og bjælken fører tilbage).
add_action(
	'admin_menu',
	static function () {
		if ( webto_dashboard_preview_customer() ) {
			remove_menu_page( 'webto' );
		}
	},
	PHP_INT_MAX
);

add_action(
	'admin_enqueue_scripts',
	static function ( $hook ) {
		if ( 'toplevel_page_webto' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'webto-dashboard', WEBTO_DASHBOARD_URL . 'assets/dashboard.css', array( 'dashicons', 'buttons' ), webto_dashboard_asset_version( 'assets/dashboard.css' ) );
	}
);

// Ingen beskeder fra WordPress og plugins på Webto-siderne (som kontrolpanelet).
add_action(
	'in_admin_header',
	static function () {
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_webto' === $screen->id ) {
			webto_dashboard_hide_notices();
		}
	},
	PHP_INT_MAX
);

/**
 * Slår "Se som kunde" til eller fra og går tilbage, hvor man kom fra (fra Webto-siden: til kontrolpanelet, for
 * kunden ser ikke Webto-siden).
 */
function webto_dashboard_handle_preview(): void {
	if ( ! webto_dashboard_is_webto() ) {
		wp_die( esc_html__( 'Du har ikke adgang.', 'webto-dashboard' ), 403 );
	}
	check_admin_referer( 'webto_dashboard_se_som_kunde' );
	$on = isset( $_GET['til'] ) && '1' === $_GET['til'];
	if ( $on ) {
		update_user_meta( get_current_user_id(), WEBTO_DASHBOARD_SE_SOM_KUNDE, 1 );
	} else {
		delete_user_meta( get_current_user_id(), WEBTO_DASHBOARD_SE_SOM_KUNDE );
	}
	$back = (string) wp_get_referer();
	if ( '' === $back || ( $on && str_contains( $back, 'page=webto' ) && ! str_contains( $back, 'page=webto-' ) ) ) {
		$back = admin_url();
	}
	wp_safe_redirect( $back );
	exit;
}
add_action( 'admin_post_webto_dashboard_se_som_kunde', 'webto_dashboard_handle_preview' );

// Bjælken under "Se som kunde": tydelig, i wp-admin og på sitet, og kan ikke skjules af Synlighed.
add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $bar ) {
		if ( ! webto_dashboard_preview_customer() ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'webto-se-som-kunde',
				'title' => '<span class="ab-icon dashicons dashicons-visibility" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'Du ser siden som kunde - tilbage til Webto', 'webto-dashboard' ) . '</span>',
				'href'  => webto_dashboard_preview_url( false ),
				'meta'  => array( 'title' => __( 'Slå "Se som kunde" fra', 'webto-dashboard' ) ),
			)
		);
	},
	1
);

$webto_dashboard_preview_css = static function () {
	if ( is_admin_bar_showing() && webto_dashboard_preview_customer() ) {
		wp_add_inline_style(
			'admin-bar',
			'#wpadminbar #wp-admin-bar-webto-se-som-kunde>.ab-item{background:#b32d2e;color:#fff}'
			. '#wpadminbar #wp-admin-bar-webto-se-som-kunde>.ab-item:hover,#wpadminbar #wp-admin-bar-webto-se-som-kunde>.ab-item:focus{background:#8a2424;color:#fff}'
			. '#wpadminbar #wp-admin-bar-webto-se-som-kunde .ab-icon{top:2px}#wpadminbar #wp-admin-bar-webto-se-som-kunde .ab-icon:before{color:#fff}'
			. '@media screen and (max-width:782px){#wpadminbar li#wp-admin-bar-webto-se-som-kunde{display:block}'
			. '#wpadminbar #wp-admin-bar-webto-se-som-kunde>.ab-item{width:52px;padding:0;text-align:center}'
			. '#wpadminbar #wp-admin-bar-webto-se-som-kunde .ab-icon{top:0;margin:0;padding:0;width:52px}'
			. '#wpadminbar #wp-admin-bar-webto-se-som-kunde .ab-icon:before{font-size:32px;width:52px;text-align:center}'
			. '#wpadminbar #wp-admin-bar-webto-se-som-kunde .ab-label{display:none}}'
		);
	}
};
add_action( 'wp_enqueue_scripts', $webto_dashboard_preview_css );
add_action( 'admin_enqueue_scripts', $webto_dashboard_preview_css );
unset( $webto_dashboard_preview_css );

// Webto Vejledning har sin egen Webto-regel (Dækning); under "Se som kunde" skal den også se siden som kunden.
add_filter(
	'webto_vejledning_er_webto',
	static fn( $is ) => $is && ! webto_dashboard_preview_customer()
);
