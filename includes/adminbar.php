<?php
/**
 * Knappen "Support" i adminbaren. På sitet åbner den panelet "Skriv til Webto" (<dialog>) med valget mellem siden og et
 * generelt spørgsmål; i wp-admin fører den til kortet i kontrolpanelet. Kun for brugere, der må skrive til Webto -
 * besøgende får hverken CSS, JS eller panel.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $bar ) {
		if ( ! webto_dashboard_can_support() ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'webto-support',
				'title' => '<span class="ab-icon dashicons dashicons-sos" aria-hidden="true"></span><span class="ab-label">' . esc_html__( 'Support', 'webto-dashboard' ) . '</span>',
				'href'  => admin_url( 'index.php#wd-support' ),
				'meta'  => array( 'title' => __( 'Skriv til Webto', 'webto-dashboard' ) ),
			)
		);
	},
	86
);

// Ikonet i adminbaren, også på mobil (hvor WordPress ellers skjuler de fleste punkter).
$webto_dashboard_ab_css = static function () {
	if ( is_admin_bar_showing() && webto_dashboard_can_support() ) {
		wp_add_inline_style(
			'admin-bar',
			'#wpadminbar #wp-admin-bar-webto-support .ab-icon{top:2px}'
			. '@media screen and (max-width:782px){#wpadminbar li#wp-admin-bar-webto-support{display:block}'
			. '#wpadminbar #wp-admin-bar-webto-support>.ab-item{width:52px;padding:0;text-align:center}'
			. '#wpadminbar #wp-admin-bar-webto-support .ab-icon{top:0;margin:0;padding:0;width:52px}'
			. '#wpadminbar #wp-admin-bar-webto-support .ab-icon:before{font-size:32px;width:52px;text-align:center}'
			. '#wpadminbar #wp-admin-bar-webto-support .ab-label{display:none}}'
		);
	}
};
add_action( 'wp_enqueue_scripts', $webto_dashboard_ab_css );
add_action( 'admin_enqueue_scripts', $webto_dashboard_ab_css );
unset( $webto_dashboard_ab_css );

/**
 * Skal panelet med på denne side? Kun på sitet, med adminbar, for brugere, der må skrive til Webto.
 */
function webto_dashboard_panel_active(): bool {
	return ! is_admin() && is_admin_bar_showing() && webto_dashboard_can_support();
}

/**
 * Siden, man står på (adresse og titel), til "Spørgsmål til denne side".
 *
 * @return array{url: string, titel: string}
 */
function webto_dashboard_current_page(): array {
	global $wp;
	if ( is_singular() ) {
		$post = get_queried_object();
		return array(
			'url'   => (string) get_permalink( $post ),
			'titel' => wp_strip_all_tags( get_the_title( $post ) ),
		);
	}
	return array(
		'url'   => home_url( add_query_arg( array(), $wp->request ? trailingslashit( $wp->request ) : '' ) ),
		'titel' => wp_strip_all_tags( wp_get_document_title() ),
	);
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! webto_dashboard_panel_active() ) {
			return;
		}
		wp_enqueue_style( 'webto-dashboard', WEBTO_DASHBOARD_URL . 'assets/dashboard.css', array( 'dashicons', 'buttons' ), webto_dashboard_asset_version( 'assets/dashboard.css' ) );
		wp_enqueue_script( 'webto-dashboard-support', WEBTO_DASHBOARD_URL . 'assets/support.js', array(), webto_dashboard_asset_version( 'assets/support.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);

add_action(
	'wp_footer',
	static function () {
		if ( ! webto_dashboard_panel_active() ) {
			return;
		}
		?>
		<dialog class="wd-dialog wp-core-ui" id="wd-support-dialog" aria-labelledby="wd-support-dialog-title">
			<div class="wd-dialog__head">
				<h2 class="wd-dialog__title" id="wd-support-dialog-title" tabindex="-1"><?php esc_html_e( 'Skriv til Webto', 'webto-dashboard' ); ?></h2>
				<button type="button" class="button" data-wd-close><?php esc_html_e( 'Luk', 'webto-dashboard' ); ?></button>
			</div>
			<div class="wd-dialog__body wd">
				<div data-wd-resultat></div>
				<div data-wd-formular>
					<?php echo webto_dashboard_mail_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i funktionen. ?>
					<?php echo webto_dashboard_support_form( 'side', webto_dashboard_current_page() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i funktionen. ?>
				</div>
				<p class="wd-dialog__after" data-wd-efter hidden>
					<button type="button" class="button" data-wd-ny><?php esc_html_e( 'Skriv en ny besked', 'webto-dashboard' ); ?></button>
					<button type="button" class="button button-primary" data-wd-close><?php esc_html_e( 'Luk', 'webto-dashboard' ); ?></button>
				</p>
			</div>
		</dialog>
		<?php
	}
);
