<?php
/**
 * Overtager WordPress' kontrolpanel. Webtos kontrolpanel tegnes på WordPress' egen adresse (index.php), så menupunktet
 * "Kontrolpanel", dets undermenu (fx Opdateringer) og alle links til kontrolpanelet virker som før - WordPress' gamle
 * kontrolpanel (widgets) bruges ikke af nogen. Plugins' egne sider under index.php (index.php?page=...) røres ikke.
 * Alle ser det samme kontrolpanel (Lasse 2026-10-13); WordPress' rettigheder styrer, hvad hvert kort viser.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Er vi på kontrolpanelet (index.php uden ?page=)?
 */
function webto_dashboard_is_screen(): bool {
	global $pagenow;
	return is_admin() && 'index.php' === $pagenow && empty( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

add_action(
	'admin_enqueue_scripts',
	static function () {
		if ( ! webto_dashboard_is_screen() ) {
			return;
		}
		wp_enqueue_style( 'webto-dashboard', WEBTO_DASHBOARD_URL . 'assets/dashboard.css', array( 'dashicons', 'buttons' ), webto_dashboard_asset_version( 'assets/dashboard.css' ) );
		wp_enqueue_script( 'webto-dashboard-support', WEBTO_DASHBOARD_URL . 'assets/support.js', array(), webto_dashboard_asset_version( 'assets/support.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
);

add_action(
	'load-index.php',
	static function () {
		if ( ! webto_dashboard_is_screen() || ! current_user_can( 'read' ) ) {
			return;
		}
		// Ingen beskeder fra WordPress og plugins på kontrolpanelet (Lasse 2026-10-13); de ses på alle andre sider.
		// Kontrolpanelets egne beskeder (fx at mail ikke virker) står i kortene.
		webto_dashboard_hide_notices();
		$GLOBALS['title']       = __( 'Kontrolpanel', 'webto-dashboard' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['parent_file'] = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		require_once ABSPATH . 'wp-admin/admin-header.php';
		webto_dashboard_render();
		require_once ABSPATH . 'wp-admin/admin-footer.php';
		exit;
	},
	100
);

/**
 * Fjerner alle beskeder fra WordPress og plugins på den aktuelle skærm (kaldes før wp-admin's header).
 */
function webto_dashboard_hide_notices(): void {
	foreach ( array( 'admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices' ) as $hook ) {
		remove_all_actions( $hook );
	}
}

/**
 * Hilsenen efter tidspunktet på dagen.
 */
function webto_dashboard_greeting(): string {
	$hour = (int) wp_date( 'G' );
	$user = wp_get_current_user();
	$name = '' !== trim( (string) $user->first_name ) ? $user->first_name : $user->display_name;
	if ( $hour < 5 ) {
		/* translators: %s: navn */
		$text = __( 'God nat, %s.', 'webto-dashboard' );
	} elseif ( $hour < 10 ) {
		/* translators: %s: navn */
		$text = __( 'God morgen, %s.', 'webto-dashboard' );
	} elseif ( $hour < 12 ) {
		/* translators: %s: navn */
		$text = __( 'God formiddag, %s.', 'webto-dashboard' );
	} elseif ( $hour < 18 ) {
		/* translators: %s: navn */
		$text = __( 'God eftermiddag, %s.', 'webto-dashboard' );
	} else {
		/* translators: %s: navn */
		$text = __( 'God aften, %s.', 'webto-dashboard' );
	}
	return sprintf( $text, $name ) . ' ' . __( 'Her er overblikket over din hjemmeside.', 'webto-dashboard' );
}

/**
 * Ét kort (rammen tegnes her; indholdet kommer fra kortet selv).
 *
 * @param array<string, mixed> $card Kortet.
 */
function webto_dashboard_render_card( array $card ): void {
	$help  = 'hjaelp' === $card['omraade'];
	$tag   = $help ? 'section' : 'article';
	$hid   = 'wd-kort-' . sanitize_html_class( (string) $card['id'] );
	$level = $help ? 'h2' : 'h3';
	?>
	<<?php echo esc_html( $tag ); ?> class="wd-card <?php echo $help ? 'wd-card--help' : 'wd-card--content'; ?>" id="<?php echo esc_attr( 'wd-' . sanitize_html_class( (string) $card['id'] ) ); ?>" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
		<div class="wd-card__head">
			<<?php echo esc_html( $level ); ?> class="wd-card__name" id="<?php echo esc_attr( $hid ); ?>">
				<?php if ( '' !== $card['ikon'] ) : ?>
					<span class="wd-card__icon dashicons <?php echo esc_attr( (string) $card['ikon'] ); ?>" aria-hidden="true"></span>
				<?php endif; ?>
				<?php echo esc_html( (string) $card['titel'] ); ?>
			</<?php echo esc_html( $level ); ?>>
			<?php if ( ! empty( $card['link']['url'] ) ) : ?>
				<a class="wd-card__all" href="<?php echo esc_url( (string) $card['link']['url'] ); ?>"><?php echo esc_html( (string) ( $card['link']['tekst'] ?? __( 'Se alle', 'webto-dashboard' ) ) ); ?><span class="screen-reader-text"> <?php echo esc_html( mb_strtolower( (string) $card['titel'] ) ); ?></span></a>
			<?php endif; ?>
		</div>
		<?php echo $card['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kortets eget HTML, escaped af den, der laver kortet. ?>
	</<?php echo esc_html( $tag ); ?>>
	<?php
}

/**
 * Kontrolpanelet.
 */
function webto_dashboard_render(): void {
	$cards = webto_dashboard_cards();
	?>
	<div class="wrap wd">
		<div class="wd-head">
			<div>
				<h1 class="wd-title"><?php esc_html_e( 'Kontrolpanel', 'webto-dashboard' ); ?></h1>
				<p class="wd-hello"><?php echo esc_html( webto_dashboard_greeting() ); ?></p>
			</div>
			<div class="wd-head__actions">
				<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span class="dashicons dashicons-external" aria-hidden="true"></span><?php esc_html_e( 'Se hjemmesiden', 'webto-dashboard' ); ?></a>
			</div>
		</div>
		<hr class="wp-header-end">

		<h2 class="screen-reader-text"><?php esc_html_e( 'Dit indhold', 'webto-dashboard' ); ?></h2>
		<div class="wd-grid">
			<?php
			foreach ( $cards as $card ) {
				webto_dashboard_render_card( $card );
			}
			?>
		</div>
	</div>
	<?php
}
