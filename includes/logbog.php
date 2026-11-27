<?php
/**
 * Logbogen (Lasse 2026-11-25): hvad er lavet på sitet, hvornår, af hvem og hvordan - så en AI, der forbinder, selv
 * kan finde ud af det (og Webto kan se det under Webto -> Logbog).
 *
 * - Skriver sig selv: sider/indlæg/blokgrupper oprettet, ændret, udgivet, slettet; billeder uploadet; menuer;
 *   plugins, tema og WordPress slået til/fra eller opdateret; vigtige indstillinger (header, SEO, Synlighed, navn,
 *   forside, synlighed for søgemaskiner); brugere. Kilden: wp-admin, AI (Novamira/MCP), WP-CLI, automatisk (cron)
 *   eller sitet. Gentagne gem af det samme inden for 15 minutter samles i én linje (antal).
 * - AI'en skriver selv én linje pr. opgave: hvad og hvorfor (ability webto/log, `wp webto log`).
 * - Læses med ability webto/logbog, `wp webto logbog` og i webto/overblik.
 * - Egen tabel ({prefix}webto_logbog). Linjer ældre end 2 år slettes én gang i døgnet.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

const WEBTO_LOGBOG_DB_VERSION = '1';

/**
 * Tabellens navn.
 */
function webto_logbog_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'webto_logbog';
}

// Tabellen laves (og ændres) af pluginnet selv - også efter en opdatering.
add_action(
	'plugins_loaded',
	static function () {
		if ( get_option( 'webto_logbog_db' ) === WEBTO_LOGBOG_DB_VERSION ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			'CREATE TABLE ' . webto_logbog_table() . " (
			id bigint(20) unsigned NOT NULL auto_increment,
			tid datetime NOT NULL,
			bruger_id bigint(20) unsigned NOT NULL default 0,
			bruger varchar(190) NOT NULL default '',
			kilde varchar(20) NOT NULL default '',
			handling varchar(40) NOT NULL default '',
			objekt_type varchar(60) NOT NULL default '',
			objekt_id bigint(20) unsigned NOT NULL default 0,
			titel text NOT NULL,
			detaljer text NOT NULL,
			antal int(10) unsigned NOT NULL default 1,
			PRIMARY KEY  (id),
			KEY tid (tid),
			KEY objekt (objekt_type, objekt_id)
		) " . $wpdb->get_charset_collate() . ';'
		);
		update_option( 'webto_logbog_db', WEBTO_LOGBOG_DB_VERSION, true );
	},
	5
);

/**
 * Hvordan ændringen er sket: ai (Novamira/MCP), wp-cli, automatisk (cron), wp-admin (også blok-editorens REST) eller sitet.
 */
function webto_logbog_source(): string {
	if ( ! empty( $GLOBALS['webto_logbog_ai'] ) ) {
		return 'ai';
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return 'wp-cli';
	}
	if ( wp_doing_cron() ) {
		return 'automatisk';
	}
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST && is_user_logged_in() ) ) {
		return 'wp-admin';
	}
	return 'sitet';
}

// Kald til Novamira (MCP-serverne) og Abilities API'et udefra = en AI.
add_filter(
	'rest_pre_dispatch',
	static function ( $result, $server, $request ) {
		$route = (string) $request->get_route();
		if ( str_starts_with( $route, '/mcp/' ) || str_starts_with( $route, '/wp-abilities/' ) ) {
			$GLOBALS['webto_logbog_ai'] = true;
		}
		return $result;
	},
	1,
	3
);

/**
 * Skriver en linje i logbogen. Samme handling på det samme af den samme bruger inden for 15 minutter samles (antal).
 *
 * @param string $handling Fx oprettet, ændret, udgivet, slettet, opdateret, slået til, note.
 * @param array  $args     titel, detaljer, objekt_type, objekt_id, kilde (standard: webto_logbog_source()).
 */
function webto_log( string $handling, array $args = array() ): void {
	global $wpdb;
	if ( get_option( 'webto_logbog_db' ) !== WEBTO_LOGBOG_DB_VERSION ) {
		return;
	}
	$user  = wp_get_current_user();
	$row   = array(
		'tid'         => current_time( 'mysql', true ),
		'bruger_id'   => (int) $user->ID,
		'bruger'      => $user->ID ? (string) $user->display_name : '',
		'kilde'       => (string) ( $args['kilde'] ?? webto_logbog_source() ),
		'handling'    => $handling,
		'objekt_type' => (string) ( $args['objekt_type'] ?? '' ),
		'objekt_id'   => (int) ( $args['objekt_id'] ?? 0 ),
		'titel'       => wp_strip_all_tags( (string) ( $args['titel'] ?? '' ) ),
		'detaljer'    => wp_strip_all_tags( (string) ( $args['detaljer'] ?? '' ) ),
	);
	if ( 'note' !== $handling ) {
		$same = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT id FROM ' . webto_logbog_table() . ' WHERE bruger_id = %d AND kilde = %s AND handling = %s AND objekt_type = %s AND objekt_id = %d AND tid > %s ORDER BY id DESC LIMIT 1', // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$row['bruger_id'],
				$row['kilde'],
				$handling,
				$row['objekt_type'],
				$row['objekt_id'],
				gmdate( 'Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS )
			)
		);
		if ( $same ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . webto_logbog_table() . ' SET antal = antal + 1, tid = %s, titel = %s WHERE id = %d', $row['tid'], $row['titel'], $same ) ); // phpcs:ignore WordPress.DB
			return;
		}
	}
	$wpdb->insert( webto_logbog_table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * De seneste linjer.
 *
 * @param array $args antal (standard 50, højst 500), dage (kun de seneste n dage), kilde.
 * @return list<array<string, mixed>>
 */
function webto_logbog_get( array $args = array() ): array {
	global $wpdb;
	if ( get_option( 'webto_logbog_db' ) !== WEBTO_LOGBOG_DB_VERSION ) {
		return array();
	}
	$where = array( '1=1' );
	$vals  = array();
	if ( ! empty( $args['dage'] ) ) {
		$where[] = 'tid > %s';
		$vals[]  = gmdate( 'Y-m-d H:i:s', time() - (int) $args['dage'] * DAY_IN_SECONDS );
	}
	if ( ! empty( $args['kilde'] ) ) {
		$where[] = 'kilde = %s';
		$vals[]  = (string) $args['kilde'];
	}
	$limit  = max( 1, min( 500, (int) ( $args['antal'] ?? 50 ) ) );
	$sql    = 'SELECT tid, bruger, kilde, handling, objekt_type, objekt_id, titel, detaljer, antal FROM ' . webto_logbog_table() . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT ' . $limit;
	$rows   = $vals ? $wpdb->get_results( $wpdb->prepare( $sql, $vals ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB
	$out = array();
	foreach ( (array) $rows as $r ) {
		$r['tid']       = get_date_from_gmt( $r['tid'], 'Y-m-d H:i' ); // Sitets tid.
		$r['objekt_id'] = (int) $r['objekt_id'];
		$r['antal']     = (int) $r['antal'];
		$out[]          = $r;
	}
	return $out;
}

/*
 * --- Det, der logges af sig selv ---
 */

/**
 * Indholdstyper, der logges: de offentlige (undtagen vedhæftninger) + blokgrupper, megamenu-paneler, navigation og
 * WordPress' skabeloner.
 */
function webto_logbog_post_types(): array {
	$types = array_diff( array_keys( get_post_types( array( 'public' => true ) ) ), array( 'attachment' ) );
	return (array) apply_filters( 'webto_logbog_post_types', array_merge( $types, array( 'wp_block', 'webto_mega', 'wp_navigation', 'wp_template', 'wp_template_part', 'wp_global_styles' ) ) );
}

/**
 * En indholdstypes navn (Side, Indlæg ...).
 */
function webto_logbog_type_label( string $type ): string {
	$obj = get_post_type_object( $type );
	return $obj ? (string) $obj->labels->singular_name : $type;
}

add_action(
	'transition_post_status',
	static function ( $new, $old, $post ) {
		if ( ! $post instanceof WP_Post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) || 'auto-draft' === $new || ! in_array( $post->post_type, webto_logbog_post_types(), true ) ) {
			return;
		}
		if ( 'trash' === $new ) {
			$handling = 'flyttet til papirkurv';
		} elseif ( 'publish' === $new && 'publish' !== $old ) {
			$handling = 'udgivet';
		} elseif ( in_array( $old, array( 'new', 'auto-draft' ), true ) ) {
			$handling = 'oprettet';
		} else {
			$handling = 'ændret';
		}
		webto_log( $handling, array( 'objekt_type' => $post->post_type, 'objekt_id' => $post->ID, 'titel' => webto_logbog_type_label( $post->post_type ) . ': ' . ( '' !== $post->post_title ? $post->post_title : '(uden titel)' ), 'detaljer' => 'publish' === $new ? '' : 'status: ' . $new ) );
	},
	20,
	3
);

add_action(
	'before_delete_post',
	static function ( $id, $post ) {
		if ( $post instanceof WP_Post && in_array( $post->post_type, webto_logbog_post_types(), true ) ) {
			webto_log( 'slettet', array( 'objekt_type' => $post->post_type, 'objekt_id' => (int) $id, 'titel' => webto_logbog_type_label( $post->post_type ) . ': ' . $post->post_title ) );
		}
	},
	10,
	2
);

add_action(
	'add_attachment',
	static function ( $id ) {
		webto_log( 'uploadet', array( 'objekt_type' => 'attachment', 'titel' => 'Billeder og filer', 'detaljer' => 'seneste: ' . get_the_title( $id ) ) );
	}
);

add_action(
	'wp_update_nav_menu',
	static function ( $menu_id ) {
		$menu = wp_get_nav_menu_object( $menu_id );
		webto_log( 'ændret', array( 'objekt_type' => 'menu', 'objekt_id' => (int) $menu_id, 'titel' => 'Menu: ' . ( $menu ? $menu->name : $menu_id ) ) );
	}
);

foreach ( array( 'activated_plugin' => 'slået til', 'deactivated_plugin' => 'slået fra', 'deleted_plugin' => 'slettet' ) as $webto_logbog_hook => $webto_logbog_what ) {
	add_action(
		$webto_logbog_hook,
		static function ( $plugin ) use ( $webto_logbog_what ) {
			webto_log( $webto_logbog_what, array( 'objekt_type' => 'plugin', 'titel' => 'Plugin: ' . webto_logbog_plugin_name( (string) $plugin ) ) );
		}
	);
}
unset( $webto_logbog_hook, $webto_logbog_what );

/**
 * Et plugins navn (og version) ud fra dets fil.
 */
function webto_logbog_plugin_name( string $plugin ): string {
	$file = WP_PLUGIN_DIR . '/' . $plugin;
	if ( ! is_readable( $file ) ) {
		return $plugin;
	}
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$data = get_plugin_data( $file, false, false );
	return trim( ( $data['Name'] ?: $plugin ) . ' ' . $data['Version'] );
}

add_action(
	'upgrader_process_complete',
	static function ( $upgrader, $extra ) {
		$type = (string) ( $extra['type'] ?? '' );
		if ( 'plugin' === $type ) {
			foreach ( (array) ( $extra['plugins'] ?? ( isset( $extra['plugin'] ) ? array( $extra['plugin'] ) : array() ) ) as $plugin ) {
				webto_log( 'opdateret', array( 'objekt_type' => 'plugin', 'titel' => 'Plugin: ' . webto_logbog_plugin_name( (string) $plugin ) ) );
			}
		} elseif ( 'theme' === $type ) {
			foreach ( (array) ( $extra['themes'] ?? ( isset( $extra['theme'] ) ? array( $extra['theme'] ) : array() ) ) as $theme ) {
				$t = wp_get_theme( (string) $theme );
				webto_log( 'opdateret', array( 'objekt_type' => 'tema', 'titel' => 'Tema: ' . $t->get( 'Name' ) . ' ' . $t->get( 'Version' ) ) );
			}
		} elseif ( 'core' === $type ) {
			webto_log( 'opdateret', array( 'objekt_type' => 'core', 'titel' => 'WordPress ' . get_bloginfo( 'version' ) ) );
		}
	},
	20,
	2
);

add_action(
	'switch_theme',
	static function ( $name ) {
		webto_log( 'slået til', array( 'objekt_type' => 'tema', 'titel' => 'Tema: ' . $name ) );
	}
);

/**
 * Indstillinger, der logges (option => hvad det hedder i logbogen). theme_mods logges kun for header builderen og logoet.
 */
function webto_logbog_options(): array {
	return (array) apply_filters(
		'webto_logbog_options',
		array(
			'blogname'                         => 'Sitets navn',
			'blogdescription'                  => 'Sitets undertitel',
			'blog_public'                      => 'Synlig for søgemaskiner',
			'page_on_front'                    => 'Forsiden',
			'permalink_structure'              => 'Permalinks',
			'admin_email'                      => 'Administrators e-mail',
			'webto_dashboard_synlighed'        => 'Webto -> Synlighed',
			'rank-math-options-general'        => 'Rank Math: generelle indstillinger',
			'rank-math-options-titles'         => 'Rank Math: titler og firmaoplysninger',
			'rank-math-options-sitemap'        => 'Rank Math: sitemap',
			'rank_math_modules'                => 'Rank Math: moduler',
			'woocommerce_currency'             => 'WooCommerce: valuta',
		)
	);
}

add_action(
	'updated_option',
	static function ( $option, $old, $new ) {
		$labels = webto_logbog_options();
		if ( isset( $labels[ $option ] ) ) {
			$detail = is_scalar( $new ) && is_scalar( $old ) ? wp_trim_words( (string) $old, 8 ) . ' -> ' . wp_trim_words( (string) $new, 8 ) : '';
			webto_log( 'ændret', array( 'objekt_type' => 'indstilling', 'titel' => $labels[ $option ], 'detaljer' => $detail ) );
			return;
		}
		if ( 'theme_mods_' . get_stylesheet() === $option ) {
			foreach ( array( 'webto_hb' => 'Headeren (header builderen)', 'custom_logo' => 'Logoet', 'nav_menu_locations' => 'Menupladserne' ) as $key => $label ) {
				if ( ( $old[ $key ] ?? null ) !== ( $new[ $key ] ?? null ) ) {
					webto_log( 'ændret', array( 'objekt_type' => 'indstilling', 'titel' => $label ) );
				}
			}
		}
	},
	10,
	3
);

add_action(
	'user_register',
	static function ( $id ) {
		$u = get_userdata( $id );
		webto_log( 'oprettet', array( 'objekt_type' => 'bruger', 'objekt_id' => (int) $id, 'titel' => 'Bruger: ' . ( $u ? $u->display_name . ' (' . implode( ', ', $u->roles ) . ')' : $id ) ) );
	}
);
add_action(
	'delete_user',
	static function ( $id ) {
		$u = get_userdata( $id );
		webto_log( 'slettet', array( 'objekt_type' => 'bruger', 'objekt_id' => (int) $id, 'titel' => 'Bruger: ' . ( $u ? $u->display_name : $id ) ) );
	}
);
add_action(
	'set_user_role',
	static function ( $id, $role, $old ) {
		if ( $old ) {
			webto_log( 'ændret', array( 'objekt_type' => 'bruger', 'objekt_id' => (int) $id, 'titel' => 'Brugerrolle', 'detaljer' => implode( ', ', (array) $old ) . ' -> ' . $role ) );
		}
	},
	10,
	3
);

// Oprydning: linjer ældre end 2 år.
add_action( 'webto_logbog_ryd', static function () {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . webto_logbog_table() . ' WHERE tid < %s', gmdate( 'Y-m-d H:i:s', time() - 2 * YEAR_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
} );
add_action(
	'admin_init',
	static function () {
		if ( ! wp_next_scheduled( 'webto_logbog_ryd' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'webto_logbog_ryd' );
		}
	}
);

/*
 * --- Til AI'en: abilities og WP-CLI ---
 */

add_action(
	'wp_abilities_api_categories_init',
	static function () {
		if ( function_exists( 'wp_register_ability_category' ) && ! wp_has_ability_category( 'webto' ) ) {
			wp_register_ability_category( 'webto', array( 'label' => __( 'Webto', 'webto-dashboard' ), 'description' => __( 'Webtos egne funktioner på sitet.', 'webto-dashboard' ) ) );
		}
	}
);

add_action(
	'wp_abilities_api_init',
	static function () {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		$mcp = array( 'public' => true, 'type' => 'tool' );
		wp_register_ability(
			'webto/log',
			array(
				'label'               => __( 'Logbog: skriv', 'webto-dashboard' ),
				'description'         => 'Webto logbook: write ONE line when you finish a task on this site - what you did and why, in Danish, short (e.g. "Byggede kontaktsiden med formular og kort; Lasse ønskede telefonnummer øverst"). The site logs page/plugin/setting changes by itself; this line is the human-readable summary future AIs and Webto read first.',
				'category'            => 'webto',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'tekst'    => array( 'type' => 'string', 'description' => 'Hvad og hvorfor, én linje.' ),
						'detaljer' => array( 'type' => 'string', 'description' => 'Valgfrit: filer, sider, beslutninger.' ),
					),
					'required'   => array( 'tekst' ),
				),
				'output_schema'       => array( 'type' => 'object', 'properties' => array( 'ok' => array( 'type' => 'boolean' ) ) ),
				'execute_callback'    => static function ( $input ) {
					webto_log( 'note', array( 'kilde' => 'ai', 'objekt_type' => 'note', 'titel' => (string) ( $input['tekst'] ?? '' ), 'detaljer' => (string) ( $input['detaljer'] ?? '' ) ) );
					return array( 'ok' => true );
				},
				'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
				'meta'                => array( 'annotations' => array( 'readonly' => false, 'destructive' => false, 'idempotent' => false ), 'show_in_rest' => true, 'mcp' => $mcp ),
			)
		);
		wp_register_ability(
			'webto/logbog',
			array(
				'label'               => __( 'Logbog: læs', 'webto-dashboard' ),
				'description'         => 'Webto logbook: what has been done on this site, when, by whom and how (kilde: ai, wp-admin, wp-cli, automatisk, sitet) - pages, posts, media, menus, plugins, theme, settings, users, plus the notes AIs wrote after each task. Read it when you connect, before changing anything.',
				'category'            => 'webto',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'dage'  => array( 'type' => 'integer', 'description' => 'Kun de seneste n dage.' ),
						'antal' => array( 'type' => 'integer', 'description' => 'Højst så mange linjer (standard 50, højst 500).' ),
						'kilde' => array( 'type' => 'string', 'enum' => array( 'ai', 'wp-admin', 'wp-cli', 'automatisk', 'sitet' ) ),
					),
					'default'    => array(),
				),
				'output_schema'       => array( 'type' => 'object', 'properties' => array( 'linjer' => array( 'type' => 'array' ) ) ),
				'execute_callback'    => static fn( $input = array() ) => array( 'linjer' => webto_logbog_get( (array) $input ) ),
				'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
				'meta'                => array( 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ), 'show_in_rest' => true, 'mcp' => $mcp ),
			)
		);
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Skriver en linje (note) i logbogen: hvad og hvorfor.
	 *
	 * <tekst>
	 * : Én linje.
	 *
	 * [--kilde=<kilde>]
	 * : ai (standard, når en AI bruger WP-CLI via Novamira) eller wp-cli.
	 */
	WP_CLI::add_command(
		'webto log',
		static function ( $args, $assoc ) {
			webto_log( 'note', array( 'kilde' => (string) ( $assoc['kilde'] ?? 'ai' ), 'objekt_type' => 'note', 'titel' => implode( ' ', $args ) ) );
			WP_CLI::success( 'Skrevet i logbogen.' );
		}
	);

	/**
	 * Viser logbogen (nyeste først).
	 *
	 * [--dage=<n>]
	 * : Kun de seneste n dage.
	 *
	 * [--antal=<n>]
	 * : Højst så mange linjer (standard 50).
	 *
	 * [--kilde=<kilde>]
	 * : ai, wp-admin, wp-cli, automatisk eller sitet.
	 *
	 * [--format=<format>]
	 * : table (standard), json eller csv.
	 */
	WP_CLI::add_command(
		'webto logbog',
		static function ( $args, $assoc ) {
			$rows = webto_logbog_get( $assoc );
			if ( ! $rows ) {
				WP_CLI::log( 'Logbogen er tom.' );
				return;
			}
			WP_CLI\Utils\format_items( $assoc['format'] ?? 'table', $rows, array( 'tid', 'bruger', 'kilde', 'handling', 'titel', 'antal', 'detaljer' ) );
		}
	);
}

/*
 * --- Webto -> Logbog (kun for Webto) ---
 */

add_action(
	'admin_menu',
	static function () {
		if ( webto_dashboard_can_webto() ) {
			add_submenu_page( 'webto', __( 'Logbog', 'webto-dashboard' ), __( 'Logbog', 'webto-dashboard' ), 'manage_options', 'webto-logbog', 'webto_logbog_page' );
		}
	},
	20
);

/**
 * Siden Webto -> Logbog: de seneste 200 linjer, filter på kilde.
 */
function webto_logbog_page(): void {
	if ( ! webto_dashboard_can_webto() ) {
		wp_die( esc_html__( 'Kun for Webto.', 'webto-dashboard' ), 403 );
	}
	$kilder = array( '' => 'Alle', 'ai' => 'AI', 'wp-admin' => 'wp-admin', 'wp-cli' => 'WP-CLI', 'automatisk' => 'Automatisk', 'sitet' => 'Sitet' );
	$kilde  = isset( $_GET['kilde'] ) ? sanitize_key( wp_unslash( $_GET['kilde'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$kilde  = isset( $kilder[ $kilde ] ) ? $kilde : '';
	$rows   = webto_logbog_get( array( 'antal' => 200, 'kilde' => $kilde ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Logbog', 'webto-dashboard' ); ?></h1>
		<p><?php esc_html_e( 'Hvad er lavet på sitet, hvornår, af hvem og hvordan. Skrives af sig selv; AI\'er skriver desuden én linje pr. opgave.', 'webto-dashboard' ); ?></p>
		<ul class="subsubsub">
			<?php
			$keys = array_keys( $kilder );
			foreach ( $kilder as $key => $label ) {
				$url = add_query_arg( array( 'page' => 'webto-logbog', 'kilde' => $key ), admin_url( 'admin.php' ) );
				// Skillestregen står inde i punktet (som WordPress' egne lister), så listen kun har <li>-børn.
				echo '<li><a href="' . esc_url( $url ) . '"' . ( $key === $kilde ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>' . ( end( $keys ) === $key ? '' : ' |' ) . '</li> ';
			}
			?>
		</ul>
		<table class="widefat striped" style="clear:both">
			<thead><tr><th scope="col"><?php esc_html_e( 'Tid', 'webto-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Hvem', 'webto-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Hvordan', 'webto-dashboard' ); ?></th><th scope="col"><?php esc_html_e( 'Hvad', 'webto-dashboard' ); ?></th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'Logbogen er tom.', 'webto-dashboard' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $rows as $r ) : ?>
				<tr>
					<td><?php echo esc_html( $r['tid'] ); ?></td>
					<td><?php echo esc_html( '' !== $r['bruger'] ? $r['bruger'] : '-' ); ?></td>
					<td><?php echo esc_html( $kilder[ $r['kilde'] ] ?? $r['kilde'] ); ?></td>
					<td>
						<?php echo 'note' === $r['handling'] ? '<strong>' . esc_html( $r['titel'] ) . '</strong>' : esc_html( $r['titel'] . ' - ' . $r['handling'] ); ?>
						<?php echo $r['antal'] > 1 ? esc_html( ' (' . $r['antal'] . ' gange)' ) : ''; ?>
						<?php if ( '' !== $r['detaljer'] ) : ?>
							<br><span class="description"><?php echo esc_html( $r['detaljer'] ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
