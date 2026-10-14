<?php
/**
 * Plugin Name:       Webto Dashboard
 * Description:       Kontrolpanelet til dit website: overblik over alt indhold, vejledning og support fra Webto.
 * Version:           1.2.0
 * Requires at least: 6.9
 * Requires PHP:      8.2
 * Author:            Webto
 * Author URI:        https://webto.dk
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://webto.dk/plugins/webto-dashboard
 * Text Domain:       webto-dashboard
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

define( 'WEBTO_DASHBOARD_VERSION', '1.2.0' );
define( 'WEBTO_DASHBOARD_DIR', __DIR__ );
define( 'WEBTO_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

/*
 * Opbygning (se README.md):
 *   oversigt.php      Data til overblikket: indholdstyper, medier (+ HappyFiles-mapper), Fluent Forms, kort fra andre.
 *   kontrolpanel.php  Overtager WordPress' kontrolpanel (index.php) og tegner Webtos.
 *   genveje.php       Rækken af genveje under toppen (Fortsæt hvor du slap, Sider, Indlæg, ... og fra andre plugins).
 *   webto.php         Menupunktet "Webto" (kun for Webto) og "Se som kunde".
 *   synlighed.php     Webto -> Synlighed: plugins, menupunkter og bjælkens punkter, kunden ikke skal se.
 *   support.php       Skriv til Webto: formularen, modtagelse (tjek, vedhæftning, grænse pr. time), mail og
 *                     "Dine beskeder til Webto" (gemmes på sitet, så intet går tabt).
 *   opdatering.php    Opdateringer fra GitHub (automatisk; aldrig for kildekoden i skabelonen).
 *   adminbar.php      Knappen "Support" i adminbaren og panelet på sitet (kun for brugere, der er logget ind).
 */
foreach ( array( 'oversigt', 'kontrolpanel', 'genveje', 'webto', 'synlighed', 'support', 'adminbar', 'opdatering' ) as $webto_dashboard_file ) {
	require_once WEBTO_DASHBOARD_DIR . '/includes/' . $webto_dashboard_file . '.php';
}
unset( $webto_dashboard_file );

/**
 * Versionsnummer ud fra filens ændringstid, så browseren altid får den nyeste fil.
 *
 * @param string $relative Sti relativt til pluginnet.
 */
function webto_dashboard_asset_version( string $relative ): string {
	$file = WEBTO_DASHBOARD_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : WEBTO_DASHBOARD_VERSION;
}

/**
 * Må brugeren skrive til Webto? (Alle, der kan skrive indhold.)
 */
function webto_dashboard_can_support(): bool {
	return is_user_logged_in() && current_user_can( (string) apply_filters( 'webto_dashboard_cap_support', 'edit_posts' ) );
}

/**
 * Er den, der er logget ind, fra Webto? En @webto.dk-mail, eller alle på et udviklingssite (homelab, localhost).
 * Kunden ser aldrig det, der kun er for Webto - heller ikke som administrator. Samme regel som Webto Vejledning.
 * Konstanten WEBTO_DASHBOARD_WEBTO (true/false) i wp-config.php vinder.
 */
function webto_dashboard_is_webto(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( defined( 'WEBTO_DASHBOARD_WEBTO' ) ) {
		return (bool) WEBTO_DASHBOARD_WEBTO;
	}
	$email = strtolower( (string) wp_get_current_user()->user_email );
	foreach ( (array) apply_filters( 'webto_dashboard_webto_domaener', array( 'webto.dk' ) ) as $domain ) {
		if ( str_ends_with( $email, '@' . strtolower( (string) $domain ) ) ) {
			return true;
		}
	}
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$dev  = str_ends_with( $host, '.ts.net' ) || in_array( $host, array( 'homelab', 'localhost', '::1', '[::1]' ), true ) || str_starts_with( $host, '127.' );
	return (bool) apply_filters( 'webto_dashboard_udvikling', $dev );
}

// Beskeder til Webto gemmes som en skjult indholdstype (ingen egen skærm og ingen adresse på sitet).
add_action(
	'init',
	static function () {
		register_post_type(
			'webto_support',
			array(
				'label'               => __( 'Beskeder til Webto', 'webto-dashboard' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'editor', 'author' ),
			)
		);
	}
);
