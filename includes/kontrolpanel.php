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
		wp_enqueue_script( 'webto-dashboard-top', WEBTO_DASHBOARD_URL . 'assets/top.js', array(), webto_dashboard_asset_version( 'assets/top.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
 * Billederne til toppen (assets/baggrunde/<nr>.webp + <nr>-1000.webp), i nummerorden. Filteret
 * webto_dashboard_baggrunde kan give en anden liste (array af array( 'stor' => url, 'lille' => url )).
 *
 * @return array<int, array{stor: string, lille: string}>
 */
function webto_dashboard_backgrounds(): array {
	$out   = array();
	$files = glob( WEBTO_DASHBOARD_DIR . '/assets/baggrunde/*.webp' ) ?: array();
	$big   = array_filter( $files, static fn( $f ) => (bool) preg_match( '/\/(\d+)\.webp$/', $f ) );
	usort( $big, static fn( $a, $b ) => (int) basename( $a ) <=> (int) basename( $b ) );
	foreach ( $big as $file ) {
		$name  = basename( $file, '.webp' );
		$small = is_file( WEBTO_DASHBOARD_DIR . '/assets/baggrunde/' . $name . '-1000.webp' ) ? $name . '-1000.webp' : $name . '.webp';
		$out[] = array(
			'stor'  => WEBTO_DASHBOARD_URL . 'assets/baggrunde/' . $name . '.webp?ver=' . webto_dashboard_asset_version( 'assets/baggrunde/' . $name . '.webp' ),
			'lille' => WEBTO_DASHBOARD_URL . 'assets/baggrunde/' . $small . '?ver=' . webto_dashboard_asset_version( 'assets/baggrunde/' . $small ),
		);
	}
	return array_values( (array) apply_filters( 'webto_dashboard_baggrunde', $out ) );
}

/**
 * Hvilket billede der vises nu: det skifter hver hele time, og alle installationer viser det samme billede i samme
 * time (timer siden 1970, UTC, modulo antallet af billeder). top.js bruger samme regel med serverens ur.
 *
 * @param int      $count Antal billeder.
 * @param int|null $time  Unix-tid (standard: nu).
 */
function webto_dashboard_background_index( int $count, ?int $time = null ): int {
	return $count > 0 ? intdiv( $time ?? time(), HOUR_IN_SECONDS ) % $count : 0;
}

/**
 * Toppen af kontrolpanelet: skiftende billede, velkomst, "Se hjemmesiden" og et ur med dato (sitets tidszone).
 */
function webto_dashboard_render_top(): void {
	$images = webto_dashboard_backgrounds();
	$now    = time();
	$index  = webto_dashboard_background_index( count( $images ), $now );
	$tz     = wp_timezone_string();
	$tz     = preg_match( '#^[A-Za-z_]+(/[A-Za-z_+-]+)*$#', $tz ) ? $tz : 'UTC'; // En fast forskydning (+01:00) kan Intl ikke bruge.
	?>
	<header class="wd-hero" data-wd-top data-wd-servertid="<?php echo esc_attr( (string) $now ); ?>" data-wd-tidszone="<?php echo esc_attr( $tz ); ?>">
		<div class="wd-hero__media" aria-hidden="true">
			<?php if ( $images ) : ?>
				<img class="wd-hero__img is-on" src="<?php echo esc_url( $images[ $index ]['lille'] ); ?>" srcset="<?php echo esc_attr( esc_url( $images[ $index ]['lille'] ) . ' 1000w, ' . esc_url( $images[ $index ]['stor'] ) . ' 2000w' ); ?>" sizes="100vw" alt="" decoding="async" fetchpriority="high">
			<?php endif; ?>
		</div>
		<script type="application/json" data-wd-billeder><?php echo wp_json_encode( $images, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES ); ?></script>
		<div class="wd-hero__inner">
			<div class="wd-hero__text">
				<h1 class="wd-hero__title"><?php esc_html_e( 'Velkommen til dit kontrolpanel', 'webto-dashboard' ); ?></h1>
				<p class="wd-hero__hello"><?php echo esc_html( webto_dashboard_greeting() ); ?></p>
				<a class="wd-hero__button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Se hjemmesiden', 'webto-dashboard' ); ?><span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></a>
			</div>
			<p class="wd-hero__clock">
				<time class="wd-hero__time" datetime="<?php echo esc_attr( wp_date( 'c', $now ) ); ?>" data-wd-tid><?php echo esc_html( wp_date( 'H:i', $now ) ); ?></time>
				<span class="wd-hero__date" data-wd-dato><?php echo esc_html( wp_date( 'l j. F', $now ) ); ?></span>
			</p>
		</div>
	</header>
	<?php
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
		<?php webto_dashboard_render_top(); ?>
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
