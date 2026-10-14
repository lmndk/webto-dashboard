<?php
/**
 * Genvejene under toppen af kontrolpanelet (Lasse 2026-10-14): én række, der aldrig bliver til to linjer - er der
 * flere genveje, end der er plads til, glider rækken til siden (pile på computer, swipe på mobil).
 *
 * En genvej er et array: id, titel, ikon (dashicons-navn), url, ny (url + tekst, "+" i genvejens højre side - valgfri),
 * under (lille linje under titlen - valgfri), variant ('' eller 'fortsaet'), skjul (array med post-id, når genvejen kan
 * skjules med et kryds - kun "Fortsæt hvor du slap") og raekkefoelge. Andre plugins og temaet
 * melder deres egne via filteret webto_dashboard_genveje (fx Webto Vejledning). Hver genvej vises kun for dem, der
 * har lov til at bruge den (WordPress' rettigheder).
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brugerens "Fortsæt hvor du slap" er skjult med krydset for dette indhold (post-id). Den kommer igen, så snart
 * brugeren har gemt noget andet (Lasse 2026-10-14). Gemmes pr. bruger.
 */
const WEBTO_DASHBOARD_FORTSAET_SKJULT = 'webto_dashboard_fortsaet_skjult';

/**
 * Det, brugeren sidst har rettet i (sider, indlæg og sitets egne typer): sidst gemt af brugeren, ellers det nyeste,
 * brugeren selv har lavet. Null, når der intet er.
 */
function webto_dashboard_last_edited(): ?WP_Post {
	$types = array_keys( webto_dashboard_post_types() );
	if ( ! $types ) {
		return null;
	}
	$base = array(
		'post_type'      => $types,
		'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'posts_per_page' => 5,
		'no_found_rows'  => true,
	);
	$user = get_current_user_id();
	foreach ( array( array( 'meta_key' => '_edit_last', 'meta_value' => (string) $user ), array( 'author' => $user ) ) as $who ) { // phpcs:ignore WordPress.DB.SlowDBQuery
		foreach ( get_posts( $base + $who ) as $post ) {
			if ( current_user_can( 'edit_post', $post->ID ) ) {
				return $post;
			}
		}
	}
	return null;
}

/**
 * Alle genveje til den, der er logget ind, i rækkefølge.
 *
 * @return array<int, array<string, mixed>>
 */
function webto_dashboard_shortcuts(): array {
	$out = array();

	// Pr. bruger: det, netop denne bruger sidst har gemt - medmindre brugeren har skjult det med krydset.
	$last = webto_dashboard_last_edited();
	if ( $last && (int) get_user_meta( get_current_user_id(), WEBTO_DASHBOARD_FORTSAET_SKJULT, true ) !== $last->ID ) {
		$title = '' !== trim( $last->post_title ) ? $last->post_title : __( '(uden titel)', 'webto-dashboard' );
		$type  = get_post_type_object( $last->post_type );
		$out[] = array(
			'id'           => 'fortsaet',
			'titel'        => __( 'Fortsæt hvor du slap', 'webto-dashboard' ),
			'under'        => $title,
			/* translators: 1: indholdstype (fx Side), 2: titel, 3: hvornår (fx i dag 10:42) */
			'beskrivelse'  => sprintf( __( '%1$s: %2$s, ændret %3$s', 'webto-dashboard' ), $type ? $type->labels->singular_name : '', $title, webto_dashboard_when( (int) get_post_timestamp( $last, 'modified' ) ) ),
			'ikon'         => 'dashicons-edit',
			'url'          => (string) get_edit_post_link( $last->ID, 'raw' ),
			'variant'      => 'fortsaet',
			'skjul'        => array( 'post' => $last->ID ),
			'raekkefoelge' => 0,
		);
	}

	// Sider, Indlæg og sitets egne typer (samme som kortene), hver med "+" til en ny.
	$order = 10;
	foreach ( webto_dashboard_post_types() as $name => $type ) {
		$out[] = array(
			'id'           => 'type-' . $name,
			'titel'        => (string) $type->labels->name,
			'ikon'         => webto_dashboard_type_icon( $type ),
			'url'          => add_query_arg( 'post_type', $name, admin_url( 'edit.php' ) ),
			'ny'           => current_user_can( $type->cap->create_posts ) ? array( 'url' => add_query_arg( 'post_type', $name, admin_url( 'post-new.php' ) ), 'tekst' => (string) $type->labels->add_new_item ) : null,
			'raekkefoelge' => $order++,
		);
	}

	if ( current_user_can( 'upload_files' ) ) {
		$out[] = array(
			'id'           => 'medier',
			'titel'        => __( 'Billeder og filer', 'webto-dashboard' ),
			'ikon'         => 'dashicons-format-image',
			'url'          => admin_url( 'upload.php' ),
			'ny'           => array( 'url' => admin_url( 'media-new.php' ), 'tekst' => __( 'Upload billeder og filer', 'webto-dashboard' ) ),
			'raekkefoelge' => 30,
		);
	}
	if ( current_user_can( 'edit_theme_options' ) && current_theme_supports( 'menus' ) ) {
		$out[] = array(
			'id'           => 'menuer',
			'titel'        => __( 'Menuer', 'webto-dashboard' ),
			'ikon'         => 'dashicons-menu',
			'url'          => admin_url( 'nav-menus.php' ),
			'raekkefoelge' => 80, // Lige foran Plugins (Lasse 2026-10-14).
		);
	}
	if ( current_user_can( 'list_users' ) ) {
		$out[] = array(
			'id'           => 'brugere',
			'titel'        => __( 'Brugere', 'webto-dashboard' ),
			'ikon'         => 'dashicons-admin-users',
			'url'          => admin_url( 'users.php' ),
			'ny'           => current_user_can( 'create_users' ) ? array( 'url' => admin_url( 'user-new.php' ), 'tekst' => __( 'Tilføj ny bruger', 'webto-dashboard' ) ) : null,
			'raekkefoelge' => 50,
		);
	}
	// Plugins kun for Webto: de opdaterer sig selv, og kunden skal ikke kunne slå noget fra ved et uheld.
	if ( current_user_can( 'activate_plugins' ) && webto_dashboard_sees_as_webto() ) {
		$out[] = array(
			'id'           => 'plugins',
			'titel'        => __( 'Plugins', 'webto-dashboard' ),
			'ikon'         => 'dashicons-admin-plugins',
			'url'          => admin_url( 'plugins.php' ),
			'raekkefoelge' => 90,
		);
	}

	$out = array_values(
		array_filter(
			array_map(
				static fn( $s ) => is_array( $s ) ? wp_parse_args( $s, array( 'id' => '', 'titel' => '', 'ikon' => '', 'url' => '', 'ny' => null, 'under' => '', 'beskrivelse' => '', 'variant' => '', 'skjul' => null, 'raekkefoelge' => 50 ) ) : null,
				(array) apply_filters( 'webto_dashboard_genveje', $out )
			),
			// Ingen genvej til en side, der er skjult for brugeren (Webto -> Synlighed).
			static fn( $s ) => $s && '' !== $s['titel'] && '' !== $s['url'] && ! webto_dashboard_url_blocked( (string) $s['url'] )
		)
	);
	foreach ( $out as $i => $s ) {
		$out[ $i ]['_i'] = $i;
	}
	usort( $out, static fn( $a, $b ) => array( (int) $a['raekkefoelge'], $a['_i'] ) <=> array( (int) $b['raekkefoelge'], $b['_i'] ) );
	return $out;
}

/**
 * Rækken af genveje. Pilene vises kun, når der er flere genveje, end der er plads til (assets/genveje.js); uden
 * JavaScript kan rækken stadig rulles.
 */
function webto_dashboard_render_shortcuts(): void {
	$shortcuts = webto_dashboard_shortcuts();
	if ( ! $shortcuts ) {
		return;
	}
	?>
	<nav class="wd-genveje" aria-label="<?php esc_attr_e( 'Genveje', 'webto-dashboard' ); ?>" data-wd-genveje>
		<ul class="wd-genveje__liste" data-wd-genveje-liste>
			<?php foreach ( $shortcuts as $s ) : ?>
				<li class="wd-genvej<?php echo '' !== $s['variant'] ? ' wd-genvej--' . esc_attr( sanitize_html_class( (string) $s['variant'] ) ) : ''; ?>" data-wd-genvej="<?php echo esc_attr( (string) $s['id'] ); ?>">
					<a class="wd-genvej__link" href="<?php echo esc_url( (string) $s['url'] ); ?>"<?php echo '' !== $s['beskrivelse'] ? ' title="' . esc_attr( (string) $s['beskrivelse'] ) . '"' : ''; ?>>
						<span class="wd-genvej__ikon dashicons <?php echo esc_attr( (string) $s['ikon'] ); ?>" aria-hidden="true"></span>
						<span class="wd-genvej__tekst">
							<span class="wd-genvej__navn"><?php echo esc_html( (string) $s['titel'] ); ?></span>
							<?php if ( '' !== $s['under'] ) : ?>
								<span class="wd-genvej__under"><?php echo esc_html( (string) $s['under'] ); ?></span>
							<?php endif; ?>
						</span>
					</a>
					<?php if ( ! empty( $s['skjul']['post'] ) ) : ?>
						<form class="wd-genvej__skjul" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-wd-skjul>
							<input type="hidden" name="action" value="webto_dashboard_skjul_fortsaet">
							<input type="hidden" name="post" value="<?php echo esc_attr( (string) (int) $s['skjul']['post'] ); ?>">
							<?php wp_nonce_field( 'webto_dashboard_skjul_fortsaet', '_wpnonce', false ); ?>
							<button type="submit" class="wd-genvej__kryds" aria-label="<?php esc_attr_e( 'Skjul Fortsæt hvor du slap', 'webto-dashboard' ); ?>" title="<?php esc_attr_e( 'Skjul', 'webto-dashboard' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
						</form>
					<?php endif; ?>
					<?php if ( ! empty( $s['ny']['url'] ) ) : ?>
						<a class="wd-genvej__ny" href="<?php echo esc_url( (string) $s['ny']['url'] ); ?>" aria-label="<?php echo esc_attr( (string) $s['ny']['tekst'] ); ?>" title="<?php echo esc_attr( (string) $s['ny']['tekst'] ); ?>"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<button type="button" class="wd-genveje__pil wd-genveje__pil--tilbage" data-wd-pil="-1" tabindex="-1" aria-hidden="true" hidden><span class="dashicons dashicons-arrow-left-alt2"></span></button>
		<button type="button" class="wd-genveje__pil wd-genveje__pil--frem" data-wd-pil="1" tabindex="-1" aria-hidden="true" hidden><span class="dashicons dashicons-arrow-right-alt2"></span></button>
	</nav>
	<?php
}

/**
 * Krydset på "Fortsæt hvor du slap": skjuler den for brugeren, indtil brugeren gemmer noget andet. Svarer med JSON,
 * når browseren beder om det (assets/genveje.js), ellers tilbage til kontrolpanelet (virker uden JavaScript).
 */
function webto_dashboard_handle_hide_continue(): void {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'Du skal være logget ind.', 'webto-dashboard' ), 403 );
	}
	check_admin_referer( 'webto_dashboard_skjul_fortsaet' );
	$post = absint( $_POST['post'] ?? 0 );
	if ( $post ) {
		update_user_meta( get_current_user_id(), WEBTO_DASHBOARD_FORTSAET_SKJULT, $post );
	}
	if ( 'json' === sanitize_key( wp_unslash( $_POST['wd_svar'] ?? '' ) ) ) {
		wp_send_json_success();
	}
	wp_safe_redirect( admin_url() );
	exit;
}
add_action( 'admin_post_webto_dashboard_skjul_fortsaet', 'webto_dashboard_handle_hide_continue' );
