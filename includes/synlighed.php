<?php
/**
 * Webto -> Synlighed (Lasse 2026-10-14): hvad kunderne ikke skal se - plugins (i listen over plugins), punkter i
 * WordPress menu og i admin bar (Lasses navne) og blokke i editoren (kun i listen over blokke, der kan indsættes;
 * ingen blok findes i to udgaver for kunden - den, temaet har stylet, bliver; se webto_dashboard_visibility_defaults). Siden finder selv alt, der findes på sitet.
 *
 * Valg pr. punkt: '' = vis, 'kunder' = skjult for kunder, 'alle' = skjult for alle (kun menupunkter og bjælken; Webto kan
 * stadig åbne en side, der er skjult for alle, via adressen). Et menupunkt, der er skjult for kunden, er også spærret for
 * kunden (også dets underpunkter), så det ikke kan åbnes via adressen. Et plugin, der er skjult for kunden, kan kunden
 * ikke slå fra eller slette.
 *
 * Standardreglerne (webto_dashboard_visibility_defaults) gælder på alle sites; sitets egne valg gemmes i
 * optionen webto_dashboard_synlighed som forskellen fra standarden, så en ny standard i en opdatering slår igennem.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

const WEBTO_DASHBOARD_SYNLIGHED       = 'webto_dashboard_synlighed';
const WEBTO_DASHBOARD_ADMINBAR_SET    = 'webto_dashboard_adminbar_set';
const WEBTO_DASHBOARD_BLOKKE_SET      = 'webto_dashboard_blokke_set';
const WEBTO_DASHBOARD_SYNLIGHED_GRUPPER = array( 'plugins', 'menu', 'adminbar', 'blokke' );

/**
 * Standarden på alle sites (Lasse 2026-10-14). Novamira og FluentSnippets er Webtos værktøjer; Webtos egne plugins
 * skal kunden ikke kunne slå fra; ACF's feltgrupper bygger Webto (kunden udfylder felterne på siderne).
 *
 * @return array<string, array<string, string>>
 */
function webto_dashboard_visibility_defaults(): array {
	$defaults = array(
		'plugins'  => array(
			'novamira/novamira.php'                   => 'kunder',
			'novamira-pro/novamira-pro.php'           => 'kunder',
			'easy-code-manager/easy-code-manager.php' => 'kunder',
			'webto-dashboard/webto-dashboard.php'     => 'kunder',
			'webto-vejledning/webto-vejledning.php'   => 'kunder',
		),
		'menu'     => array(
			'novamira-connect'                   => 'kunder',
			'fluent-snippets'                    => 'kunder',
			'edit.php?post_type=acf-field-group' => 'kunder',
		),
		'adminbar' => array(
			'novamira-mcp-status' => 'kunder',
			'fluent_form'         => 'alle',
		),
		// Blokke (Lasse 2026-10-14): kunden får ikke to udgaver af samme blok, men den, temaet har stylet - Kadences række,
		// knap, harmonika, faner og ikon (inc/kadence.php + kadence.css), WordPress' egne tekst-, billed-, tabel-,
		// galleri- og afstandsblokke (theme.json, base.css; dem bygger AI'en med) og temaets Hero. Desuden væk: det, der
		// hører til skabeloner (titel, dato, kommentarer, logo, menu ...), gamle/rå blokke (HTML, klassisk, shortcode,
		// widgets), Kadences formularer (vi bruger Fluent Forms), sidehoved/navigation (header builderen) og
		// udviklerblokke (query, repeater, dynamisk indhold, SVG-kode, Lottie).
		'blokke'   => array_fill_keys(
			array(
				// To af hver: WordPress' udgave er væk, Kadences (stylet til temaet) bliver.
				'core/buttons', 'core/columns', 'core/group', 'core/accordion', 'core/details', 'core/tabs', 'core/icon',
				'core/cover', 'core/latest-posts',
				// To af hver: Kadences udgave er væk, WordPress' / temaets bliver.
				'kadence/advancedheading', 'kadence/image', 'kadence/table', 'kadence/advancedgallery', 'kadence/spacer',
				'kadence/search', 'kadence/splitcontent', 'kadence/imageoverlay', 'happyfiles/gallery',
				// Kadence: formularer, sidehoved, udviklerblokke.
				'kadence/form', 'kadence/advanced-form', 'kadence/header', 'kadence/navigation', 'kadence/navigation-link',
				'kadence/off-canvas', 'kadence/off-canvas-trigger', 'kadence/identity', 'kadence/query', 'kadence/query-card', 'kadence/repeater',
				'kadence/dynamichtml', 'kadence/dynamiclist', 'kadence/portfoliogrid', 'kadence/postgrid', 'kadence/vector',
				'kadence/lottie', 'kadence/userinfo', 'kadence-conversions/conversion',
				// WordPress: hører til skabeloner, ikke til sider.
				'core/template-part', 'core/post-content', 'core/post-template', 'core/query', 'core/query-pagination',
				'core/query-no-results', 'core/query-title', 'core/query-total', 'core/post-title', 'core/post-date',
				'core/post-excerpt', 'core/post-featured-image', 'core/post-author-name',
				'core/post-author-biography', 'core/post-terms', 'core/post-comments-count', 'core/post-comments-form',
				'core/post-comments-link', 'core/post-navigation-link', 'core/post-time-to-read', 'core/read-more',
				'core/comments', 'core/comments-title', 'core/comment-author-name', 'core/comment-content',
				'core/comment-date', 'core/comment-edit-link', 'core/comment-reply-link', 'core/latest-comments',
				'core/term-count', 'core/term-description', 'core/term-name', 'core/term-template', 'core/terms-query',
				'core/site-logo', 'core/site-title', 'core/site-tagline', 'core/navigation', 'core/navigation-overlay-close',
				'core/page-list', 'core/loginout', 'core/avatar', 'core/breadcrumbs', 'core/archives', 'core/calendar',
				'core/categories', 'core/rss', 'core/tag-cloud', 'core/more', 'core/nextpage',
				// WordPress: gamle og rå blokke.
				'core/html', 'core/freeform', 'core/shortcode', 'core/legacy-widget', 'core/widget-group',
				// SEOPress: den gamle FAQ og det, Webto sætter op.
				'wpseopress/faq-block', 'wpseopress/sitemap', 'wpseopress/preferred-source',
				// Indlejr: tjenester, danske sites sjældent bruger (YouTube, Vimeo, X, Bluesky, Spotify, SoundCloud,
				// TikTok og Pinterest bliver; Facebook og Instagram vises ikke i blokvælgeren af WordPress selv).
				'core/embed/animoto', 'core/embed/cloudup', 'core/embed/crowdsignal', 'core/embed/dailymotion',
				'core/embed/flickr', 'core/embed/imgur', 'core/embed/issuu', 'core/embed/kickstarter', 'core/embed/mixcloud',
				'core/embed/pocket-casts', 'core/embed/reddit', 'core/embed/reverbnation', 'core/embed/scribd',
				'core/embed/smugmug', 'core/embed/speaker-deck', 'core/embed/ted', 'core/embed/tumblr', 'core/embed/videopress',
				'core/embed/wordpress-tv', 'core/embed/amazon-kindle', 'core/embed/wolfram-cloud', 'core/embed/wordpress',
			),
			'kunder'
		),
	);
	return (array) apply_filters( 'webto_dashboard_synlighed_standard', $defaults );
}

/**
 * Sitets egne valg (forskellen fra standarden). En tom streng betyder "vis" (også når standarden skjuler).
 *
 * @return array<string, array<string, string>>
 */
function webto_dashboard_visibility_saved(): array {
	$saved = get_option( WEBTO_DASHBOARD_SYNLIGHED, array() );
	return is_array( $saved ) ? $saved : array();
}

/**
 * De gældende regler: standarden med sitets egne valg ovenpå (kun de skjulte).
 *
 * @return array<string, array<string, string>>
 */
function webto_dashboard_visibility(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$defaults = webto_dashboard_visibility_defaults();
	$saved    = webto_dashboard_visibility_saved();
	$out      = array();
	foreach ( WEBTO_DASHBOARD_SYNLIGHED_GRUPPER as $group ) {
		$rules         = array_merge( (array) ( $defaults[ $group ] ?? array() ), (array) ( $saved[ $group ] ?? array() ) );
		$out[ $group ] = array_filter( $rules, static fn( $v ) => in_array( $v, array( 'kunder', 'alle' ), true ) );
	}
	$cache = $out;
	return $out;
}

/**
 * Valget for ét punkt ('' = vis).
 *
 * @param string $group plugins, menu eller adminbar.
 * @param string $key   Nøglen (plugin-fil, menu-slug, "forælder>slug" eller bjælkens id).
 */
function webto_dashboard_visibility_state( string $group, string $key ): string {
	return (string) ( webto_dashboard_visibility()[ $group ][ $key ] ?? '' );
}

/**
 * Er punktet skjult for den, der ser siden nu?
 *
 * @param string $group Gruppen.
 * @param string $key   Nøglen.
 */
function webto_dashboard_is_hidden( string $group, string $key ): bool {
	$state = webto_dashboard_visibility_state( $group, $key );
	return 'alle' === $state || ( 'kunder' === $state && ! webto_dashboard_sees_as_webto() );
}

/**
 * Punkter, der aldrig kan skjules (kontrolpanelet, Webto-siden, brugerens egen menu og "Se som kunde").
 *
 * @param string $group Gruppen.
 * @param string $key   Nøglen.
 */
function webto_dashboard_visibility_locked( string $group, string $key ): bool {
	$locked = array(
		'menu'     => array( 'index.php', 'index.php>index.php', 'webto' ),
		'adminbar' => array( 'my-account', 'menu-toggle', 'top-secondary', 'webto-se-som-kunde' ),
	);
	return in_array( $key, $locked[ $group ] ?? array(), true ) || ( 'menu' === $group && str_starts_with( $key, 'webto>' ) );
}

/**
 * Menuteksten uden tællere og HTML ("Kommentarer 0" -> "Kommentarer").
 *
 * @param string $html Menuens tekst.
 */
function webto_dashboard_clean_label( string $html ): string {
	$html = (string) preg_replace( '#<span[^>]*class="[^"]*(awaiting-mod|update-plugins|count|menu-counter|plugin-count)[^"]*"[^>]*>.*?</span>\s*</span>#s', '', $html );
	$html = (string) preg_replace( '#<span[^>]*class="[^"]*(awaiting-mod|update-plugins|count|menu-counter|plugin-count)[^"]*"[^>]*>.*?</span>#s', '', $html );
	return trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $html ) ) );
}

/*
 * --- WordPress menu ---
 */

/**
 * Menuen, som den så ud, før noget blev skjult (til Synlighed-siden og spærringen).
 *
 * @return array{menu: array<int, array<int, string>>, submenu: array<string, array<int, array<int, string>>>}
 */
function webto_dashboard_menu_snapshot(): array {
	return $GLOBALS['webto_dashboard_menu_snapshot'] ?? array( 'menu' => array(), 'submenu' => array() );
}

add_action(
	'admin_menu',
	static function () {
		global $menu, $submenu;
		$GLOBALS['webto_dashboard_menu_snapshot'] = array( 'menu' => (array) $menu, 'submenu' => (array) $submenu );
		foreach ( (array) $menu as $item ) {
			if ( ! empty( $item[2] ) && ! webto_dashboard_visibility_locked( 'menu', $item[2] ) && webto_dashboard_is_hidden( 'menu', $item[2] ) ) {
				remove_menu_page( $item[2] );
			}
		}
		foreach ( (array) $submenu as $parent => $items ) {
			foreach ( (array) $items as $item ) {
				if ( ! empty( $item[2] ) && webto_dashboard_is_hidden( 'menu', $parent . '>' . $item[2] ) ) {
					remove_submenu_page( $parent, $item[2] );
				}
			}
		}
	},
	PHP_INT_MAX - 1
);

/**
 * Siderne, kunden ikke må åbne: menupunkter skjult for kunder eller alle, med alle deres underpunkter.
 *
 * @return string[] Menuens slugs.
 */
function webto_dashboard_blocked_slugs(): array {
	if ( webto_dashboard_sees_as_webto() ) {
		return array(); // Webto kan altid åbne siderne.
	}
	$snap  = webto_dashboard_menu_snapshot();
	$slugs = array();
	foreach ( $snap['menu'] as $item ) {
		$slug = (string) ( $item[2] ?? '' );
		if ( '' === $slug || webto_dashboard_visibility_locked( 'menu', $slug ) || '' === webto_dashboard_visibility_state( 'menu', $slug ) ) {
			continue;
		}
		$slugs[] = $slug;
		foreach ( (array) ( $snap['submenu'][ $slug ] ?? array() ) as $sub ) {
			$slugs[] = (string) $sub[2];
		}
	}
	foreach ( $snap['submenu'] as $parent => $items ) {
		foreach ( (array) $items as $sub ) {
			if ( '' !== webto_dashboard_visibility_state( 'menu', $parent . '>' . $sub[2] ) ) {
				$slugs[] = (string) $sub[2];
			}
		}
	}
	return array_values( array_unique( array_filter( $slugs ) ) );
}

/**
 * Passer en menu-slug til en adresse i wp-admin? En slug er enten en side under et plugin (admin.php?page=<slug>) eller
 * en fil i wp-admin med eventuelle parametre (edit.php?post_type=acf-field-group). post.php hører til sin indholdstype.
 *
 * @param string               $slug Menuens slug.
 * @param string               $file Filen (fx admin.php, edit.php).
 * @param array<string, mixed> $args Adressens parametre.
 */
function webto_dashboard_slug_matches( string $slug, string $file, array $args ): bool {
	$slug_file = basename( (string) wp_parse_url( $slug, PHP_URL_PATH ) );
	if ( '' === $slug_file || ! is_file( ABSPATH . 'wp-admin/' . $slug_file ) ) {
		return isset( $args['page'] ) && (string) $args['page'] === $slug; // Et plugins egen side.
	}
	parse_str( (string) wp_parse_url( $slug, PHP_URL_QUERY ), $want );
	// Et indlæg i editoren (post.php?post=ID) hører til listen over dets type.
	if ( 'post.php' === $file && in_array( $slug_file, array( 'edit.php', 'post-new.php' ), true ) && ! isset( $want['page'] ) ) {
		$post = isset( $args['post'] ) ? get_post( (int) $args['post'] ) : null;
		return $post && $post->post_type === ( $want['post_type'] ?? 'post' );
	}
	if ( $slug_file !== $file ) {
		return false;
	}
	$defaults = array( 'post_type' => in_array( $file, array( 'edit.php', 'post-new.php' ), true ) ? 'post' : null );
	foreach ( array_merge( array_filter( $defaults ), $want ) as $key => $value ) {
		$have = $args[ $key ] ?? ( $defaults[ $key ] ?? null );
		if ( (string) $have !== (string) $value ) {
			return false;
		}
	}
	// En side under en fil (fx tools.php?page=x) må ikke spærre selve filen, og omvendt.
	return isset( $want['page'] ) === isset( $args['page'] );
}

/**
 * Er en adresse i wp-admin spærret for den, der ser siden nu? (Bruges også af genvejene.)
 *
 * @param string $url Adressen.
 */
function webto_dashboard_url_blocked( string $url ): bool {
	$blocked = webto_dashboard_blocked_slugs();
	if ( ! $blocked ) {
		return false;
	}
	parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );
	$file = basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	foreach ( $blocked as $slug ) {
		if ( webto_dashboard_slug_matches( $slug, '' === $file ? 'index.php' : $file, $args ) ) {
			return true;
		}
	}
	return false;
}

// Spærringen: kunden kan ikke åbne en skjult side via adressen.
add_action(
	'current_screen',
	static function () {
		global $pagenow;
		$blocked = webto_dashboard_blocked_slugs();
		if ( ! $blocked ) {
			return;
		}
		$args = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $blocked as $slug ) {
			if ( webto_dashboard_slug_matches( $slug, (string) $pagenow, (array) $args ) ) {
				wp_die(
					'<p>' . esc_html__( 'Den side er ikke tilgængelig. Skriv til Webto, hvis du har brug for den.', 'webto-dashboard' ) . '</p><p><a href="' . esc_url( admin_url() ) . '">' . esc_html__( 'Til kontrolpanelet', 'webto-dashboard' ) . '</a></p>',
					esc_html__( 'Ikke tilgængelig', 'webto-dashboard' ),
					array( 'response' => 403 )
				);
			}
		}
	}
);

/*
 * --- Admin bar ---
 */

add_action(
	'admin_bar_menu',
	static function ( WP_Admin_Bar $bar ) {
		$nodes = (array) $bar->get_nodes();
		// Webto: husk bjælkens punkter (fra wp-admin og fra sitet), så Synlighed kan vise dem.
		if ( webto_dashboard_is_webto() ) {
			webto_dashboard_remember_adminbar( $nodes );
		}
		$hidden = array();
		foreach ( array_keys( webto_dashboard_visibility()['adminbar'] ) as $id ) {
			if ( ! webto_dashboard_visibility_locked( 'adminbar', $id ) && webto_dashboard_is_hidden( 'adminbar', $id ) ) {
				$hidden[] = $id;
			}
		}
		// Punktet og alt under det.
		while ( $hidden ) {
			$id = array_shift( $hidden );
			foreach ( $nodes as $node ) {
				if ( $node->parent === $id ) {
					$hidden[] = $node->id;
				}
			}
			$bar->remove_node( $id );
		}
	},
	PHP_INT_MAX
);

/**
 * Gemmer bjælkens øverste punkter (id => tekst), når der er nye. Kun Webto kalder den.
 *
 * @param array<string, object> $nodes Bjælkens punkter.
 */
function webto_dashboard_remember_adminbar( array $nodes ): void {
	// WordPress' egne punkter har kun et ikon og en tæller; de får et navn, man kan genkende.
	$names = array(
		'wp-logo'         => __( 'WordPress-logoet (Om WordPress)', 'webto-dashboard' ),
		'site-name'       => __( 'Sitets navn (til hjemmesiden / wp-admin)', 'webto-dashboard' ),
		'command-palette' => __( 'Søg i wp-admin (Ctrl+K)', 'webto-dashboard' ),
		'comments'        => __( 'Kommentarer', 'webto-dashboard' ),
		'new-content'     => __( 'Tilføj (+)', 'webto-dashboard' ),
		'edit'            => __( 'Rediger siden', 'webto-dashboard' ),
		'customize'       => __( 'Tilpas', 'webto-dashboard' ),
		'search'          => __( 'Søg på hjemmesiden', 'webto-dashboard' ),
		'updates'         => __( 'Opdateringer', 'webto-dashboard' ),
	);
	$known = get_option( WEBTO_DASHBOARD_ADMINBAR_SET, array() );
	$known = is_array( $known ) ? $known : array();
	$new   = $known;
	foreach ( $nodes as $node ) {
		if ( ( empty( $node->parent ) || 'top-secondary' === $node->parent ) && ! webto_dashboard_visibility_locked( 'adminbar', $node->id ) ) {
			$label = $names[ $node->id ] ?? webto_dashboard_clean_label( (string) $node->title );
			if ( '' === $label && ! empty( $node->meta['title'] ) ) {
				$label = (string) $node->meta['title'];
			}
			$new[ $node->id ] = '' !== $label ? $label : (string) $node->id;
		}
	}
	if ( $new !== $known ) {
		update_option( WEBTO_DASHBOARD_ADMINBAR_SET, $new, false );
	}
}

/*
 * --- Blokke i editoren ---
 */

/**
 * Sitets blokke, der kan indsættes (navn => titel), grupperet efter plugin: dem, PHP kender, plus dem, editoren har
 * meldt (nogle findes kun i JavaScript, fx Kadences formular), set af Webto. Varianter står som "blok/variant" (fx
 * core/embed/youtube) - dem kender kun editoren.
 *
 * @return array<string, string>
 */
function webto_dashboard_blocks_list(): array {
	$out = array();
	foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $type ) {
		if ( ( isset( $type->supports['inserter'] ) && false === $type->supports['inserter'] ) || ! empty( $type->parent ) || ! empty( $type->ancestor ) ) {
			continue; // Ikke på listen over blokke, eller en under-blok, der styres gennem sin forælder.
		}
		$out[ $name ] = '' !== (string) $type->title ? (string) $type->title : $name;
	}
	$seen = get_option( WEBTO_DASHBOARD_BLOKKE_SET, array() );
	foreach ( is_array( $seen ) ? $seen : array() as $name => $title ) {
		$out[ $name ] = $out[ $name ] ?? (string) $title;
	}
	return $out;
}

/**
 * Blokkene, der er skjult for den, der ser editoren nu.
 *
 * @return string[]
 */
function webto_dashboard_hidden_blocks(): array {
	return array_values( array_filter( array_keys( webto_dashboard_visibility()['blokke'] ?? array() ), static fn( $name ) => webto_dashboard_is_hidden( 'blokke', (string) $name ) ) );
}

// Editoren: skjul blokkene og (for Webto) husk sitets blokke. Tidligt, så filteret er på plads før blokkene registreres.
add_action(
	'enqueue_block_editor_assets',
	static function () {
		$data = array( 'skjul' => webto_dashboard_hidden_blocks() );
		if ( webto_dashboard_can_webto() ) {
			$known         = get_option( WEBTO_DASHBOARD_BLOKKE_SET, array() );
			$known         = is_array( $known ) ? array_keys( $known ) : array();
			sort( $known );
			$data['husk']  = true;
			$data['kendt'] = implode( ',', $known );
			$data['rest']  = rest_url( 'webto-dashboard/v1/blokke' );
			$data['nonce'] = wp_create_nonce( 'wp_rest' );
		}
		wp_enqueue_script( 'webto-dashboard-blokke', WEBTO_DASHBOARD_URL . 'assets/blokke.js', array( 'wp-hooks' ), webto_dashboard_asset_version( 'assets/blokke.js' ), false );
		wp_add_inline_script( 'webto-dashboard-blokke', 'window.webtoDashboardBlokke = ' . wp_json_encode( $data ) . ';', 'before' );
	},
	1
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'webto-dashboard/v1',
			'/blokke',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'webto_dashboard_can_webto',
				'callback'            => static function ( WP_REST_Request $request ) {
					$blocks = (array) $request->get_param( 'blokke' );
					$clean  = array();
					foreach ( array_slice( $blocks, 0, 1000, true ) as $name => $info ) {
						if ( ! preg_match( '#^[a-z0-9-]+/[a-z0-9-]+$#', (string) $name ) ) {
							continue;
						}
						$clean[ (string) $name ] = sanitize_text_field( (string) ( is_array( $info ) ? ( $info['titel'] ?? $name ) : $info ) );
						// Varianter (fx Indlejr -> YouTube) som "blok/variant".
						foreach ( array_slice( (array) ( is_array( $info ) ? ( $info['varianter'] ?? array() ) : array() ), 0, 200, true ) as $variant => $title ) {
							if ( preg_match( '#^[a-z0-9-]+$#', (string) $variant ) ) {
								$clean[ $name . '/' . $variant ] = sanitize_text_field( (string) $title );
							}
						}
					}
					ksort( $clean );
					if ( $clean && get_option( WEBTO_DASHBOARD_BLOKKE_SET ) !== $clean ) {
						update_option( WEBTO_DASHBOARD_BLOKKE_SET, $clean, false );
					}
					return rest_ensure_response( array( 'antal' => count( $clean ) ) );
				},
			)
		);
	}
);

/*
 * --- Plugins ---
 */

/**
 * Er et plugin skjult for den, der ser siden nu? (Plugins kan kun skjules for kunder.)
 *
 * @param string $file Pluginnets fil (mappe/fil.php).
 */
function webto_dashboard_plugin_hidden( string $file ): bool {
	// Kun for en kunde, der er logget ind: WP-CLI, cron og andre kald uden bruger skal se alle plugins (ellers forsvinder
	// de fx fra `wp plugin list` - fundet ved prøven af 1.2.0).
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ! is_user_logged_in() ) {
		return false;
	}
	return '' !== webto_dashboard_visibility_state( 'plugins', $file ) && ! webto_dashboard_sees_as_webto();
}

add_filter(
	'all_plugins',
	static function ( $plugins ) {
		foreach ( array_keys( (array) $plugins ) as $file ) {
			if ( webto_dashboard_plugin_hidden( (string) $file ) ) {
				unset( $plugins[ $file ] );
			}
		}
		return $plugins;
	}
);

// Kunden kan ikke slå et skjult plugin fra, slette eller opdatere det (heller ikke via adressen).
add_action(
	'load-plugins.php',
	static function () {
		$files = array_merge( (array) ( $_REQUEST['plugin'] ?? array() ), (array) ( $_REQUEST['checked'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $files as $file ) {
			if ( webto_dashboard_plugin_hidden( sanitize_text_field( wp_unslash( (string) $file ) ) ) ) {
				wp_die( esc_html__( 'Det plugin styrer Webto. Skriv til Webto, hvis du har brug for at ændre det.', 'webto-dashboard' ), esc_html__( 'Ikke tilgængelig', 'webto-dashboard' ), array( 'response' => 403 ) );
			}
		}
	}
);

/*
 * --- Siden Webto -> Synlighed ---
 */

/**
 * Nøglen i et formularfelt (menuens slugs kan indeholde [ og ], som PHP ikke tåler i feltnavne).
 *
 * @param string $key Nøglen.
 */
function webto_dashboard_field_key( string $key ): string {
	return rtrim( strtr( base64_encode( $key ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Valgene i en række.
 *
 * @param string $group Gruppen.
 * @param string $key   Nøglen.
 * @param string $label Punktets navn (til skærmlæsere).
 */
function webto_dashboard_visibility_select( string $group, string $key, string $label ): string {
	$state   = webto_dashboard_visibility_state( $group, $key );
	$options = array(
		''       => __( 'Vis', 'webto-dashboard' ),
		'kunder' => __( 'Skjul for kunder', 'webto-dashboard' ),
	);
	if ( 'plugins' !== $group ) {
		$options['alle'] = __( 'Skjul for alle', 'webto-dashboard' );
	}
	$id   = 'wd-s-' . $group . '-' . md5( $key );
	$html = '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
	$html .= '<select id="' . esc_attr( $id ) . '" name="wd_regel[' . esc_attr( $group ) . '][' . esc_attr( webto_dashboard_field_key( $key ) ) . ']" data-wd-regel>';
	foreach ( $options as $value => $text ) {
		$html .= '<option value="' . esc_attr( $value ) . '"' . selected( $state, $value, false ) . '>' . esc_html( $text ) . '</option>';
	}
	return $html . '</select>';
}

/**
 * Standarden som tekst (til kolonnen "Standard").
 *
 * @param string $group Gruppen.
 * @param string $key   Nøglen.
 */
function webto_dashboard_visibility_default_text( string $group, string $key ): string {
	$default = (string) ( webto_dashboard_visibility_defaults()[ $group ][ $key ] ?? '' );
	return array(
		''       => __( 'Vis', 'webto-dashboard' ),
		'kunder' => __( 'Skjul for kunder', 'webto-dashboard' ),
		'alle'   => __( 'Skjul for alle', 'webto-dashboard' ),
	)[ $default ] ?? '';
}

/**
 * En række i en tabel.
 *
 * @param string $group Gruppen.
 * @param string $key   Nøglen.
 * @param string $name  Navnet.
 * @param string $note  Lille tekst under navnet.
 * @param bool   $sub   Et underpunkt (rykket ind).
 */
function webto_dashboard_visibility_row( string $group, string $key, string $name, string $note = '', bool $sub = false ): string {
	$state = webto_dashboard_visibility_state( $group, $key );
	return '<tr class="' . ( $sub ? 'wd-s-sub' : 'wd-s-top' ) . ( '' !== $state ? ' is-skjult' : '' ) . '" data-wd-noegle="' . esc_attr( $group . ':' . $key ) . '">'
		. '<th scope="row"><span class="wd-s-name">' . esc_html( $name ) . '</span>' . ( '' !== $note ? '<span class="wd-s-note">' . esc_html( $note ) . '</span>' : '' ) . '</th>'
		. '<td>' . ( webto_dashboard_visibility_locked( $group, $key ) ? '<span class="wd-s-locked">' . esc_html__( 'Altid synlig', 'webto-dashboard' ) . '</span>' : webto_dashboard_visibility_select( $group, $key, $name ) ) . '</td>'
		. '<td class="wd-s-default">' . esc_html( webto_dashboard_visibility_default_text( $group, $key ) ) . '</td>'
		. '</tr>';
}

/**
 * Siden.
 */
function webto_dashboard_visibility_page(): void {
	if ( ! webto_dashboard_can_webto() ) {
		wp_die( esc_html__( 'Du har ikke adgang.', 'webto-dashboard' ), 403 );
	}
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$snap    = webto_dashboard_menu_snapshot();
	$bar     = get_option( WEBTO_DASHBOARD_ADMINBAR_SET, array() );
	$bar     = is_array( $bar ) ? $bar : array();
	$message = isset( $_GET['wd_synlighed'] ) ? sanitize_key( wp_unslash( $_GET['wd_synlighed'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	// Standardpunkter i bjælken, som ikke er set endnu (fx kun på sitet), kommer også med.
	foreach ( array_keys( webto_dashboard_visibility_defaults()['adminbar'] ?? array() ) as $id ) {
		$bar[ $id ] = $bar[ $id ] ?? $id;
	}
	$head = '<thead><tr><th scope="col">' . esc_html__( 'Navn', 'webto-dashboard' ) . '</th><th scope="col">' . esc_html__( 'Synlighed', 'webto-dashboard' ) . '</th><th scope="col">' . esc_html__( 'Standard', 'webto-dashboard' ) . '</th></tr></thead>';
	?>
	<div class="wrap wd wd-webto">
		<div class="wd-webto__head">
			<div>
				<h1 class="wd-title"><?php esc_html_e( 'Synlighed', 'webto-dashboard' ); ?></h1>
				<p class="wd-hello"><?php esc_html_e( 'Kun for Webto. Vælg, hvad kunderne ikke skal se. Kunden ser hverken denne side eller menupunktet Webto.', 'webto-dashboard' ); ?></p>
			</div>
			<a class="button" href="<?php echo esc_url( webto_dashboard_preview_url( true ) ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><?php esc_html_e( 'Se som kunde', 'webto-dashboard' ); ?></a>
		</div>
		<hr class="wp-header-end">
		<?php if ( 'gemt' === $message ) : ?>
			<div class="wd-notice wd-notice--ok" role="status"><?php esc_html_e( 'Gemt.', 'webto-dashboard' ); ?></div>
		<?php elseif ( 'nulstillet' === $message ) : ?>
			<div class="wd-notice wd-notice--ok" role="status"><?php esc_html_e( 'Alt er sat tilbage til standard.', 'webto-dashboard' ); ?></div>
		<?php endif; ?>
		<nav class="wd-webto__nav" aria-label="<?php esc_attr_e( 'Afsnit på siden', 'webto-dashboard' ); ?>">
			<?php
			// Genveje til afsnittene (Lasse 2026-10-14: siden er lang). Står fast øverst, mens man ruller; tallet er, hvor
			// mange punkter der er skjult i afsnittet (opdateres, når et valg ændres).
			foreach ( array( 'plugins' => __( 'Plugins', 'webto-dashboard' ), 'menu' => __( 'WordPress menu', 'webto-dashboard' ), 'adminbar' => __( 'Admin bar', 'webto-dashboard' ), 'blokke' => __( 'Blokke', 'webto-dashboard' ) ) as $group => $label ) :
				?>
				<a class="wd-webto__jump" href="#wd-afsnit-<?php echo esc_attr( $group ); ?>"><?php echo esc_html( $label ); ?><span class="wd-webto__antal" data-wd-antal="<?php echo esc_attr( $group ); ?>"></span></a>
			<?php endforeach; ?>
		</nav>
		<ul class="wd-webto__help">
			<li><?php esc_html_e( 'Skjul for kunder: kunden ser det ikke og kan heller ikke åbne siden via adressen. Et skjult plugin kan kunden ikke slå fra eller slette.', 'webto-dashboard' ); ?></li>
			<li><?php esc_html_e( 'Skjul for alle: heller ikke Webto ser det i WordPress menu eller admin bar (Webto kan stadig åbne siden via adressen).', 'webto-dashboard' ); ?></li>
			<li><?php esc_html_e( 'Skjules et punkt i WordPress menu, skjules dets underpunkter også.', 'webto-dashboard' ); ?></li>
		</ul>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wd-webto__form">
			<input type="hidden" name="action" value="webto_dashboard_synlighed">
			<?php wp_nonce_field( 'webto_dashboard_synlighed' ); ?>

			<section class="wd-card wd-s" id="wd-afsnit-plugins" aria-labelledby="wd-s-plugins">
				<h2 class="wd-card__name" id="wd-s-plugins"><span class="wd-card__icon dashicons dashicons-admin-plugins" aria-hidden="true"></span><?php esc_html_e( 'Plugins', 'webto-dashboard' ); ?></h2>
				<p class="wd-s-lead"><?php esc_html_e( 'I listen over plugins.', 'webto-dashboard' ); ?></p>
				<div class="wd-s-table"><table>
					<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ovenfor. ?>
					<tbody>
					<?php
					foreach ( get_plugins() as $file => $data ) {
						echo webto_dashboard_visibility_row( 'plugins', (string) $file, (string) $data['Name'], is_plugin_active( (string) $file ) ? '' : __( 'Ikke aktivt', 'webto-dashboard' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i rækken.
					}
					?>
					</tbody>
				</table></div>
			</section>

			<section class="wd-card wd-s" id="wd-afsnit-menu" aria-labelledby="wd-s-menu">
				<h2 class="wd-card__name" id="wd-s-menu"><span class="wd-card__icon dashicons dashicons-menu-alt" aria-hidden="true"></span><?php esc_html_e( 'WordPress menu', 'webto-dashboard' ); ?></h2>
				<div class="wd-s-table"><table>
					<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ovenfor. ?>
					<tbody>
					<?php
					// Som menuen: i WordPress' rækkefølge og kun det, der faktisk vises (WordPress rydder op efter plugins,
					// fx Links, når de er slået fra) - plus det, Synlighed selv har skjult.
					global $menu, $submenu;
					$shown     = array_column( (array) $menu, 2 );
					$shown_sub = array_map( static fn( $items ) => array_column( (array) $items, 2 ), (array) $submenu );
					$top       = $snap['menu'];
					uksort( $top, 'strnatcasecmp' );
					foreach ( $top as $item ) {
						$slug = (string) ( $item[2] ?? '' );
						if ( '' === $slug || 'webto' === $slug || str_contains( (string) ( $item[4] ?? '' ), 'wp-menu-separator' ) ) {
							continue;
						}
						if ( ! in_array( $slug, $shown, true ) && '' === webto_dashboard_visibility_state( 'menu', $slug ) ) {
							continue;
						}
						$subs = array_filter(
							(array) ( $snap['submenu'][ $slug ] ?? array() ),
							static fn( $sub ) => in_array( $sub[2], $shown_sub[ $slug ] ?? array(), true ) || '' !== webto_dashboard_visibility_state( 'menu', $slug . '>' . $sub[2] ) || ! in_array( $slug, $shown, true )
						);
						$name = webto_dashboard_clean_label( (string) $item[0] );
						echo webto_dashboard_visibility_row( 'menu', $slug, '' !== $name ? $name : $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i rækken.
						foreach ( $subs as $sub ) {
							if ( webto_dashboard_visibility_locked( 'menu', $slug . '>' . $sub[2] ) ) {
								continue;
							}
							$sub_name = webto_dashboard_clean_label( (string) $sub[0] );
							echo webto_dashboard_visibility_row( 'menu', $slug . '>' . $sub[2], '' !== $sub_name ? $sub_name : (string) $sub[2], '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i rækken.
						}
					}
					?>
					</tbody>
				</table></div>
			</section>

			<section class="wd-card wd-s" id="wd-afsnit-adminbar" aria-labelledby="wd-s-adminbar">
				<h2 class="wd-card__name" id="wd-s-adminbar"><span class="wd-card__icon dashicons dashicons-admin-generic" aria-hidden="true"></span><?php esc_html_e( 'Admin bar', 'webto-dashboard' ); ?></h2>
				<p class="wd-s-lead"><?php esc_html_e( 'Punkterne i admin bar fra wp-admin og fra hjemmesiden (et punkt, der kun findes på hjemmesiden, kommer på listen, første gang Webto har set det).', 'webto-dashboard' ); ?></p>
				<div class="wd-s-table"><table>
					<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ovenfor. ?>
					<tbody>
					<?php
					foreach ( $bar as $id => $label ) {
						echo webto_dashboard_visibility_row( 'adminbar', (string) $id, (string) $label, (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i rækken.
					}
					?>
					</tbody>
				</table></div>
			</section>

			<section class="wd-card wd-s" id="wd-afsnit-blokke" aria-labelledby="wd-s-blokke">
				<h2 class="wd-card__name" id="wd-s-blokke"><span class="wd-card__icon dashicons dashicons-block-default" aria-hidden="true"></span><?php esc_html_e( 'Blokke', 'webto-dashboard' ); ?></h2>
				<p class="wd-s-lead"><?php esc_html_e( 'I listen over blokke i editoren. En skjult blok kan ikke sættes ind, men blokke, der allerede står på en side, virker og kan rettes. Kunden skal ikke have to udgaver af samme blok - kun den, temaet har stylet. Blokke og varianter (fx Indlejr -> YouTube), der kun findes i editoren, kommer på listen, første gang Webto har åbnet editoren. Skjules en blok, skjules dens varianter også.', 'webto-dashboard' ); ?></p>
				<div class="wd-s-table"><table>
					<?php echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ovenfor. ?>
					<tbody>
					<?php
					$groups   = array();
					$variants = array();
					foreach ( webto_dashboard_blocks_list() as $name => $title ) {
						$parts = explode( '/', (string) $name );
						if ( 3 === count( $parts ) ) {
							$variants[ $parts[0] . '/' . $parts[1] ][ (string) $name ] = (string) $title; // Fx core/embed/youtube.
						} else {
							$groups[ $parts[0] ][ (string) $name ] = (string) $title;
						}
					}
					$labels = array( 'webto' => 'Webto', 'core' => 'WordPress', 'kadence' => 'Kadence', 'kadence-conversions' => 'Kadence Conversions', 'fluentfom' => 'Fluent Forms', 'fluentform' => 'Fluent Forms', 'wpseopress' => 'SEOPress', 'happyfiles' => 'HappyFiles', 'acf' => 'ACF' );
					uksort( $groups, static fn( $a, $b ) => array( array_search( $a, array_keys( $labels ), true ) === false ? 99 : array_search( $a, array_keys( $labels ), true ), $a ) <=> array( array_search( $b, array_keys( $labels ), true ) === false ? 99 : array_search( $b, array_keys( $labels ), true ), $b ) );
					foreach ( $groups as $ns => $blocks ) {
						asort( $blocks, SORT_NATURAL | SORT_FLAG_CASE );
						echo '<tr class="wd-s-group"><th scope="rowgroup" colspan="3">' . esc_html( $labels[ $ns ] ?? $ns ) . '</th></tr>';
						foreach ( $blocks as $name => $title ) {
							echo webto_dashboard_visibility_row( 'blokke', $name, $title, $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i rækken.
							$subs = $variants[ $name ] ?? array();
							asort( $subs, SORT_NATURAL | SORT_FLAG_CASE );
							foreach ( $subs as $key => $sub_title ) {
								echo webto_dashboard_visibility_row( 'blokke', $key, $sub_title, $key, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped i rækken.
							}
						}
					}
					?>
					</tbody>
				</table></div>
			</section>

			<p class="wd-webto__actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Gem', 'webto-dashboard' ); ?></button>
				<button type="submit" class="button" name="nulstil" value="1" data-wd-confirm="<?php esc_attr_e( 'Sæt alt tilbage til standard?', 'webto-dashboard' ); ?>"><?php esc_html_e( 'Nulstil til standard', 'webto-dashboard' ); ?></button>
			</p>
		</form>
	</div>
	<script>
	// Rækken markeres, når valget ændres, og tallene i genvejene øverst følger med; "Nulstil til standard" spørger først.
	const wdCount = () => document.querySelectorAll( '.wd-webto [data-wd-antal]' ).forEach( ( el ) => {
		const n = document.querySelectorAll( '#wd-afsnit-' + el.dataset.wdAntal + ' tr.is-skjult' ).length;
		el.textContent = n ? n + ' skjult' : '';
	} );
	document.querySelectorAll( '.wd-webto [data-wd-regel]' ).forEach( ( s ) => s.addEventListener( 'change', () => {
		s.closest( 'tr' ).classList.toggle( 'is-skjult', '' !== s.value );
		wdCount();
	} ) );
	wdCount();
	document.querySelectorAll( '.wd-webto [data-wd-confirm]' ).forEach( ( b ) => b.addEventListener( 'click', ( e ) => { if ( ! window.confirm( b.dataset.wdConfirm ) ) { e.preventDefault(); } } ) );
	</script>
	<?php
}

/**
 * Gemmer valgene som forskellen fra standarden (eller nulstiller).
 */
function webto_dashboard_handle_visibility_save(): void {
	if ( ! webto_dashboard_can_webto() ) {
		wp_die( esc_html__( 'Du har ikke adgang.', 'webto-dashboard' ), 403 );
	}
	check_admin_referer( 'webto_dashboard_synlighed' );
	$target = admin_url( 'admin.php?page=webto' );
	if ( ! empty( $_POST['nulstil'] ) ) {
		delete_option( WEBTO_DASHBOARD_SYNLIGHED );
		wp_safe_redirect( add_query_arg( 'wd_synlighed', 'nulstillet', $target ) );
		exit;
	}
	$defaults = webto_dashboard_visibility_defaults();
	$posted   = (array) wp_unslash( $_POST['wd_regel'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- renses herunder.
	$saved    = array();
	foreach ( WEBTO_DASHBOARD_SYNLIGHED_GRUPPER as $group ) {
		foreach ( (array) ( $posted[ $group ] ?? array() ) as $field => $value ) {
			$key   = (string) base64_decode( strtr( (string) $field, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$value = sanitize_key( (string) $value );
			if ( '' === $key || webto_dashboard_visibility_locked( $group, $key ) || ! in_array( $value, array( '', 'kunder', 'alle' ), true ) || ( 'plugins' === $group && 'alle' === $value ) ) {
				continue;
			}
			if ( $value !== (string) ( $defaults[ $group ][ $key ] ?? '' ) ) {
				$saved[ $group ][ $key ] = $value;
			}
		}
	}
	update_option( WEBTO_DASHBOARD_SYNLIGHED, $saved, true );
	wp_safe_redirect( add_query_arg( 'wd_synlighed', 'gemt', $target ) );
	exit;
}
add_action( 'admin_post_webto_dashboard_synlighed', 'webto_dashboard_handle_visibility_save' );
