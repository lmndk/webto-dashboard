<?php
/**
 * Ability webto/overblik (Lasse 2026-11-25): det en AI kalder FØRST, når den forbinder til sitet, så den selv ved,
 * hvad den har at arbejde med - uden adgang til andet end Novamira.
 *
 * Svarer med: sitet (navn, adresse, udvikling eller live, synligt for søgemaskiner), temaerne (forældre-/undertema,
 * versioner, sitets unikke løsninger fra UNIKT.md og sitets egne udgaver af skabeloner), plugins, hukommelsen,
 * de seneste linjer i logbogen, hvad der mangler (header, vejledning, SEO, undertema) og hvilke skills der passer.
 * Alt er valgfrit: mangler temaet eller et plugin, springes det over.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Overblikket som array.
 *
 * @return array<string, mixed>
 */
function webto_overblik(): array {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$parent = wp_get_theme( get_template() );
	$child  = wp_get_theme();
	$live   = function_exists( 'webto_mail_dev_host' ) ? ! webto_mail_dev_host() : null;

	$out = array(
		'site'     => array(
			'navn'                    => get_bloginfo( 'name' ),
			'adresse'                 => home_url( '/' ),
			'udvikling_eller_live'    => null === $live ? 'ukendt' : ( $live ? 'live (rigtigt domæne - mail virker)' : 'udvikling (mail er spærret)' ),
			'synlig_for_soegemaskiner' => (bool) get_option( 'blog_public' ),
			'wordpress'               => get_bloginfo( 'version' ),
			'php'                     => PHP_VERSION,
			'webshop'                 => class_exists( 'WooCommerce' ),
			'oversaettelse'           => defined( 'TRP_PLUGIN_VERSION' ) ? 'TranslatePress' : ( function_exists( 'pll_languages_list' ) ? 'Polylang' : ( defined( 'ICL_SITEPRESS_VERSION' ) ? 'WPML' : '' ) ),
		),
		'tema'     => array(
			'forældretema' => $parent->get_stylesheet() . ' ' . $parent->get( 'Version' ),
			'undertema'    => $child->get_stylesheet() !== $parent->get_stylesheet() ? $child->get_stylesheet() . ' ' . $child->get( 'Version' ) . ' (' . $child->get( 'Name' ) . ')' : '(intet - sitet kører direkte på forældretemaet)',
			'regel'        => 'Forældretemaet rettes aldrig på sitet; sitets udvikling sker i undertemaet. Læs undertemaets CLAUDE.md og UNIKT.md (wp-content/themes/<undertema>/).',
			'unikt'        => '',
			'egne_skabeloner' => function_exists( 'webto_template_overrides' ) ? webto_template_overrides() : array(),
		),
		'plugins'  => array(),
		'hukommelse' => array_map( static fn( $p ) => $p->post_title, get_posts( array( 'post_type' => 'novamira_memory', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => 'title', 'order' => 'ASC' ) ) ),
		'logbog'   => webto_logbog_get( array( 'antal' => 25 ) ),
		'mangler'  => array(),
		'skills'   => array(),
		'instruktion' => 'Læs dette, før du ændrer noget. Følg skills: hent den, der passer, med novamira/skill-get, før opgaven. Når en opgave er færdig: skriv én linje i logbogen (webto/log: hvad og hvorfor), og kør tjekkene igen. Ret aldrig i forældretemaet; afvigelser fra det fælles skrives i undertemaets UNIKT.md.',
	);

	$unikt = get_stylesheet_directory() . '/UNIKT.md';
	if ( get_stylesheet_directory() !== get_template_directory() && is_readable( $unikt ) ) {
		$out['tema']['unikt'] = (string) file_get_contents( $unikt, false, null, 0, 8000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	$all = get_plugins();
	foreach ( (array) get_option( 'active_plugins', array() ) as $file ) {
		$out['plugins'][] = trim( ( $all[ $file ]['Name'] ?? $file ) . ' ' . ( $all[ $file ]['Version'] ?? '' ) );
	}

	// Hvad mangler - og hvilken skill der hjælper.
	if ( class_exists( 'Webto_HB_Guard' ) ) {
		$hb = Webto_HB_Guard::run( false );
		if ( ! empty( $hb['antal'] ) ) {
			$out['mangler'][] = array( 'hvad' => 'Headeren: ' . $hb['antal'] . ' fejl (webto-hb/tjek)', 'skill' => 'webto-header' );
		}
	}
	if ( function_exists( 'webto_vejledning_status' ) ) {
		$v = webto_vejledning_status();
		if ( ! empty( $v['antal'] ) ) {
			$out['mangler'][] = array( 'hvad' => 'Vejledning: ' . $v['antal'] . ' mangler eller er forældet (webto-vejledning/status)', 'skill' => 'webto-vejledning' );
		}
	}
	if ( function_exists( 'webto_seo_diff' ) && defined( 'RANK_MATH_VERSION' ) ) {
		if ( webto_seo_diff() ) {
			$out['mangler'][] = array( 'hvad' => 'SEO: grundopsætningen afviger (wp webto seo opsaet)', 'skill' => 'webto-seo-rank-math' );
		}
		$seo = array_filter( webto_seo_site_status(), static fn( $r ) => 'mangler' === $r[0] );
		if ( $seo ) {
			$out['mangler'][] = array( 'hvad' => 'SEO: ' . implode( ', ', array_map( static fn( $r ) => $r[1], $seo ) ), 'skill' => 'webto-seo-rank-math' );
		}
	}
	foreach ( (array) $out['tema']['egne_skabeloner'] as $o ) {
		if ( 'ok' !== $o['status'] ) {
			$out['mangler'][] = array( 'hvad' => 'Undertemaets udgave af ' . $o['fil'] . ' bygger på en ældre version (' . ( $o['site'] ?: 'uden @version' ) . ' -> ' . $o['forælder'] . ')', 'skill' => '' );
		}
	}

	// Skills fra temaerne (Novamiras egne står i Novamiras katalog).
	foreach ( array( 'webto_novamira_site_skills' => 'Sitet', 'webto_novamira_skills' => 'Webto' ) as $fn => $label ) {
		if ( function_exists( $fn ) ) {
			foreach ( $fn() as $skill ) {
				$out['skills'][] = array( 'slug' => $skill['slug'], 'fra' => $label, 'hvad' => $skill['description'] );
			}
		}
	}
	return $out;
}

add_action(
	'wp_abilities_api_init',
	static function () {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		wp_register_ability(
			'webto/overblik',
			array(
				'label'               => __( 'Overblik over sitet', 'webto-dashboard' ),
				'description'         => 'CALL THIS FIRST when you connect to a Webto site, before changing anything. Returns: the site (name, URL, development or live, visible to search engines, webshop, translation), the themes (Webto parent theme that must never be edited on the site + the site child theme where all site work happens, its UNIKT.md list of unique solutions, and any outdated template overrides), active plugins, Novamira memory items, the latest 25 logbook lines (what was done, when, by whom, how), what is missing (header, guides, SEO, child theme) with the skill to fix it, and the Webto/site skills. When you finish a task, write one line with webto/log.',
				'category'            => 'webto',
				'input_schema'        => array( 'type' => 'object', 'properties' => new stdClass(), 'default' => array() ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'site' => array( 'type' => 'object' ), 'tema' => array( 'type' => 'object' ), 'plugins' => array( 'type' => 'array' ),
						'hukommelse' => array( 'type' => 'array' ), 'logbog' => array( 'type' => 'array' ), 'mangler' => array( 'type' => 'array' ),
						'skills' => array( 'type' => 'array' ), 'instruktion' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => static fn() => webto_overblik(),
				'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
				'meta'                => array( 'annotations' => array( 'readonly' => true, 'destructive' => false, 'idempotent' => true ), 'show_in_rest' => true, 'mcp' => array( 'public' => true, 'type' => 'tool' ) ),
			)
		);
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Overblik over sitet (det samme som ability webto/overblik) som JSON.
	 */
	WP_CLI::add_command(
		'webto overblik',
		static function () {
			WP_CLI::line( (string) wp_json_encode( webto_overblik(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}
	);
}
