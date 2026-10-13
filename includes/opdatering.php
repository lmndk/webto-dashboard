<?php
/**
 * Opdateringer fra GitHub (planens "Opdatering ude hos kunderne", Lasse 2026-10-13).
 *
 * - Pluginnet spørger sit offentlige GitHub-repo (releases) via plugin-update-checker (MIT, vendor/). En ny version
 *   vises i WordPress som enhver anden pluginopdatering, og den installeres automatisk (auto_update_plugin).
 * - Udgivelser laves med plugins/udgiv.sh i skabelonens repo, og først efter en test på en kopi på homelab.
 *   Den forrige version ligger altid på GitHub, så den kan installeres igen.
 * - Kildekoden i skabelonen (markørfilen .udvikling) opdateres aldrig: dér tjekkes der slet ikke.
 * - GitHub-ejeren sættes i WEBTO_DASHBOARD_GITHUB (eller filteret webto_dashboard_github). Er den tom, tjekkes der ikke.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

// Repoets adresse, fx https://github.com/<ejer>/webto-dashboard/ (tom = ingen opdateringer endnu).
if ( ! defined( 'WEBTO_DASHBOARD_GITHUB' ) ) {
	define( 'WEBTO_DASHBOARD_GITHUB', 'https://github.com/lmndk/webto-dashboard/' );
}

/**
 * Er det kildekoden i skabelonen (som aldrig må opdateres herfra)?
 */
function webto_dashboard_is_source(): bool {
	return file_exists( WEBTO_DASHBOARD_DIR . '/.udvikling' );
}

add_action(
	'plugins_loaded',
	static function () {
		$repo = (string) apply_filters( 'webto_dashboard_github', WEBTO_DASHBOARD_GITHUB );
		if ( '' === $repo || webto_dashboard_is_source() || ! is_readable( WEBTO_DASHBOARD_DIR . '/vendor/plugin-update-checker/plugin-update-checker.php' ) ) {
			return;
		}
		require_once WEBTO_DASHBOARD_DIR . '/vendor/plugin-update-checker/plugin-update-checker.php';
		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( $repo, WEBTO_DASHBOARD_DIR . '/webto-dashboard.php', 'webto-dashboard' );
		// KUN den færdige zip-fil fra udgivelsen (bygget af udgiv.sh). GitHubs automatiske kildekode-zip indeholder
		// markøren .udvikling og ville stoppe alle senere opdateringer - mangler zip-filen, tilbydes udgivelsen ikke.
		$checker->getVcsApi()->enableReleaseAssets( '/webto-dashboard-[0-9.]+\.zip$/', \YahnisElsts\PluginUpdateChecker\v5p7\Vcs\Api::REQUIRE_RELEASE_ASSETS );
	}
);

// Automatisk opdatering er slået til for pluginnet (aldrig for kildekoden i skabelonen).
add_filter(
	'auto_update_plugin',
	static function ( $update, $item ) {
		if ( is_object( $item ) && isset( $item->plugin ) && 'webto-dashboard/webto-dashboard.php' === $item->plugin ) {
			return ! webto_dashboard_is_source();
		}
		return $update;
	},
	10,
	2
);
