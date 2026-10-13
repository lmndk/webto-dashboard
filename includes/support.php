<?php
/**
 * Skriv til Webto: formularen (kontrolpanelet og panelet på sitet), modtagelsen og "Dine beskeder til Webto".
 *
 * - Beskeden sendes til support@webto.dk (filteret webto_dashboard_support_til) med kundens mail som Reply-To.
 * - Med automatisk: sidens adresse og titel, bruger, browser og skærmstørrelse, WordPress-, PHP-, tema- og
 *   pluginversioner.
 * - Én vedhæftning: et billede (jpg, png, gif, webp) eller en PDF på højst 5 MB, tjekket på indholdet, ikke kun navnet.
 *   Filen gemmes uden for mediebiblioteket (uploads/webto-support/, spærret for direkte adgang) og hentes via wp-admin.
 * - Beskeden gemmes altid på sitet (indholdstypen webto_support), også når mailen fejler, så intet går tabt.
 * - Højst 5 beskeder pr. bruger pr. time (filteret webto_dashboard_support_pr_time).
 * - Virker mail ikke (temaets spærre på udviklingssites, eller ingen SMTP), siger formularen det før og efter.
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

const WEBTO_DASHBOARD_MAX_BYTES = 5 * 1024 * 1024;

/**
 * Kan sitet sende mail? 'spaerret' (temaets spærre, fx på homelab), 'ingen-smtp' (Fluent SMTP er ikke sat op) eller 'ok'.
 */
function webto_dashboard_mail_status(): string {
	if ( function_exists( 'webto_mail_allowed' ) && ! webto_mail_allowed() ) {
		return 'spaerret';
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$smtp = is_plugin_active( 'fluent-smtp/fluent-smtp.php' ) ? get_option( 'fluentmail-settings' ) : null;
	$ok   = is_array( $smtp ) && ! empty( $smtp['connections'] );
	return (string) apply_filters( 'webto_dashboard_mail_status', $ok ? 'ok' : 'ingen-smtp' );
}

/**
 * Modtageren.
 */
function webto_dashboard_support_to(): string {
	return (string) apply_filters( 'webto_dashboard_support_til', 'support@webto.dk' );
}

/**
 * Beskeden før formularen, når mail ikke virker.
 */
function webto_dashboard_mail_notice(): string {
	switch ( webto_dashboard_mail_status() ) {
		case 'spaerret':
			return '<p class="wd-notice"><strong>' . esc_html__( 'Mail er slået fra på dette udviklingssite.', 'webto-dashboard' ) . '</strong>' . esc_html__( 'Din besked bliver gemt her, men ikke sendt.', 'webto-dashboard' ) . '</p>';
		case 'ingen-smtp':
			return '<p class="wd-notice"><strong>' . esc_html__( 'Hjemmesidens mail er ikke sat op endnu.', 'webto-dashboard' ) . '</strong>' . esc_html__( 'Din besked kommer måske ikke frem til os, men den bliver altid gemt her.', 'webto-dashboard' ) . '</p>';
	}
	return '';
}

/**
 * Formularen. På sitet ('side') vælger man mellem siden og et generelt spørgsmål; i kontrolpanelet er den generel.
 *
 * @param string               $context 'kontrolpanel' eller 'side'.
 * @param array<string, string> $page   Siden (url og titel), kun på sitet.
 */
function webto_dashboard_support_form( string $context, array $page = array() ): string {
	$p    = 'side' === $context ? 'wd-side' : 'wd-kp';
	$user = wp_get_current_user();
	$auto = array(
		/* translators: %s: e-mailadresse */
		sprintf( __( 'Din e-mail (%s), så vi kan svare dig', 'webto-dashboard' ), $user->user_email ),
		'side' === $context && ! empty( $page['titel'] )
			/* translators: %s: sidens titel */
			? sprintf( __( 'Sidens adresse og titel (%s)', 'webto-dashboard' ), $page['titel'] )
			: __( 'Hjemmesidens adresse', 'webto-dashboard' ),
		__( 'Din browser og skærmstørrelse', 'webto-dashboard' ),
		__( 'Versioner af WordPress, tema og plugins', 'webto-dashboard' ),
	);
	ob_start();
	?>
	<form class="wd-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" data-wd-support>
		<input type="hidden" name="action" value="webto_dashboard_support">
		<input type="hidden" name="kontekst" value="<?php echo esc_attr( $context ); ?>">
		<input type="hidden" name="skaerm" value="" data-wd-skaerm>
		<?php wp_nonce_field( 'webto_dashboard_support', '_wdnonce' ); ?>
		<?php if ( 'side' === $context ) : ?>
			<input type="hidden" name="side_url" value="<?php echo esc_attr( $page['url'] ?? '' ); ?>">
			<input type="hidden" name="side_titel" value="<?php echo esc_attr( $page['titel'] ?? '' ); ?>">
			<fieldset class="wd-fieldset">
				<legend class="wd-legend"><?php esc_html_e( 'Hvad drejer det sig om?', 'webto-dashboard' ); ?></legend>
				<div class="wd-choices">
					<label class="wd-choice"><input type="radio" name="emne" value="side" checked> <span><?php esc_html_e( 'Spørgsmål til denne side', 'webto-dashboard' ); ?><?php if ( ! empty( $page['titel'] ) ) : ?><br><span class="wd-choice__sub"><?php echo esc_html( $page['titel'] ); ?></span><?php endif; ?></span></label>
					<label class="wd-choice"><input type="radio" name="emne" value="generelt"> <?php esc_html_e( 'Generelt spørgsmål', 'webto-dashboard' ); ?></label>
				</div>
			</fieldset>
		<?php endif; ?>
		<div>
			<label class="wd-label" for="<?php echo esc_attr( $p ); ?>-besked"><?php esc_html_e( 'Din besked', 'webto-dashboard' ); ?></label>
			<textarea id="<?php echo esc_attr( $p ); ?>-besked" name="besked" required maxlength="5000" placeholder="<?php esc_attr_e( 'Skriv, hvad du har brug for hjælp til.', 'webto-dashboard' ); ?>"></textarea>
		</div>
		<div>
			<span class="wd-label" id="<?php echo esc_attr( $p ); ?>-fil-lbl"><?php esc_html_e( 'Billede eller PDF (valgfrit)', 'webto-dashboard' ); ?></span>
			<div class="wd-file">
				<input class="wd-file__input" type="file" id="<?php echo esc_attr( $p ); ?>-fil" name="fil" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf" aria-describedby="<?php echo esc_attr( $p ); ?>-fil-lbl <?php echo esc_attr( $p ); ?>-fil-hj" data-wd-fil>
				<label class="button" for="<?php echo esc_attr( $p ); ?>-fil"><span class="dashicons dashicons-paperclip" aria-hidden="true"></span><?php esc_html_e( 'Vedhæft fil', 'webto-dashboard' ); ?></label>
				<span class="wd-file__name" data-wd-filnavn></span>
				<button type="button" class="button-link" data-wd-fjern hidden><?php esc_html_e( 'Fjern', 'webto-dashboard' ); ?></button>
			</div>
			<span class="wd-help" id="<?php echo esc_attr( $p ); ?>-fil-hj"><?php esc_html_e( 'Ét skærmbillede eller én PDF, højst 5 MB.', 'webto-dashboard' ); ?></span>
		</div>
		<details class="wd-auto">
			<summary><?php esc_html_e( 'Det sender vi automatisk med', 'webto-dashboard' ); ?></summary>
			<ul><?php echo implode( '', array_map( static fn( $line ) => '<li>' . esc_html( $line ) . '</li>', $auto ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
		</details>
		<div data-wd-fejl role="alert"></div>
		<button class="button button-primary wd-send" type="submit" data-wd-send><?php esc_html_e( 'Send til Webto', 'webto-dashboard' ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}

/**
 * Beskeden efter afsendelse.
 *
 * @param string $status 'sendt' eller 'ikke-sendt'.
 */
function webto_dashboard_result_html( string $status ): string {
	if ( 'sendt' === $status ) {
		/* translators: %s: e-mailadresse */
		return '<div class="wd-notice wd-notice--ok" role="status"><strong>' . esc_html__( 'Tak, din besked er sendt.', 'webto-dashboard' ) . '</strong>' . esc_html( sprintf( __( 'Vi svarer på %s. Du kan altid se den under Dine beskeder til Webto.', 'webto-dashboard' ), wp_get_current_user()->user_email ) ) . '</div>';
	}
	/* translators: %s: e-mailadresse til support */
	return '<div class="wd-notice" role="alert"><strong>' . esc_html__( 'Din besked er gemt, men ikke sendt.', 'webto-dashboard' ) . '</strong>' . esc_html( sprintf( __( 'Hjemmesiden kan ikke sende mail lige nu. Skriv til %s fra din egen mail, og nævn gerne siden. Beskeden ligger under Dine beskeder til Webto, så intet går tabt.', 'webto-dashboard' ), webto_dashboard_support_to() ) ) . '</div>';
}

/**
 * Beskederne, brugeren må se (sine egne; administratorer ser alle).
 *
 * @param int $limit Antal (-1 = alle).
 * @return WP_Post[]
 */
function webto_dashboard_messages( int $limit = 3 ): array {
	$args = array(
		'post_type'      => 'webto_support',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);
	if ( ! current_user_can( 'manage_options' ) ) {
		$args['author'] = get_current_user_id();
	}
	return get_posts( $args );
}

/**
 * Adressen til én besked (eller listen).
 *
 * @param int $id Beskedens id (0 = listen).
 */
function webto_dashboard_message_url( int $id = 0 ): string {
	return add_query_arg( array_filter( array( 'page' => 'webto-beskeder', 'besked' => $id ?: null ) ), admin_url( 'index.php' ) );
}

/**
 * Status som mærke.
 *
 * @param WP_Post $post Beskeden.
 */
function webto_dashboard_status_pill( WP_Post $post ): string {
	return 'sendt' === get_post_meta( $post->ID, '_wd_status', true )
		? webto_dashboard_pill( __( 'Sendt', 'webto-dashboard' ), 'new' )
		: webto_dashboard_pill( __( 'Ikke sendt', 'webto-dashboard' ), 'warn' );
}

// Kortet "Skriv til Webto" i kontrolpanelets hjælpekolonne.
add_filter(
	'webto_dashboard_tiles',
	static function ( array $cards ): array {
		if ( ! webto_dashboard_can_support() ) {
			return $cards;
		}
		$result = isset( $_GET['wd_support'] ) ? sanitize_key( wp_unslash( $_GET['wd_support'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$html   = '<p class="wd-lead">' . esc_html__( 'Spørgsmål, fejl eller ønsker? Vi svarer på din mail.', 'webto-dashboard' ) . '</p>';
		$html  .= '<div data-wd-resultat>' . ( in_array( $result, array( 'sendt', 'ikke-sendt' ), true ) ? webto_dashboard_result_html( $result ) : '' ) . '</div>';
		if ( 'fejl' === $result ) {
			$html .= '<div class="wd-notice" role="alert">' . esc_html( (string) get_transient( 'webto_dashboard_fejl_' . get_current_user_id() ) ?: __( 'Beskeden kunne ikke sendes. Prøv igen.', 'webto-dashboard' ) ) . '</div>';
		}
		$html .= webto_dashboard_mail_notice() . webto_dashboard_support_form( 'kontrolpanel' );

		$messages = webto_dashboard_messages( 3 );
		$html    .= '<h3 class="wd-sub">' . esc_html__( 'Dine beskeder til Webto', 'webto-dashboard' ) . '</h3>';
		if ( $messages ) {
			$html .= '<ul class="wd-list" data-wd-beskeder>';
			foreach ( $messages as $post ) {
				$html .= '<li><a class="wd-list__title" href="' . esc_url( webto_dashboard_message_url( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a><span class="wd-list__meta">' . webto_dashboard_status_pill( $post ) . esc_html( webto_dashboard_when( (int) get_post_time( 'U', true, $post ) ) ) . '</span></li>';
			}
			$html .= '</ul><p><a href="' . esc_url( webto_dashboard_message_url() ) . '">' . esc_html__( 'Se alle beskeder', 'webto-dashboard' ) . '</a></p>';
		} else {
			$html .= '<ul class="wd-list" data-wd-beskeder hidden></ul><p class="wd-empty" data-wd-ingen>' . esc_html__( 'Du har ikke skrevet til os endnu.', 'webto-dashboard' ) . '</p>';
		}

		$cards[] = array(
			'id'           => 'support',
			'titel'        => __( 'Skriv til Webto', 'webto-dashboard' ),
			'ikon'         => 'dashicons-sos',
			'html'         => $html,
			'omraade'      => 'hjaelp',
			'raekkefoelge' => 10,
		);
		return $cards;
	},
	5
);

/**
 * Oplysningerne, der automatisk sendes med.
 *
 * @param array<string, string> $page    Siden (url, titel), hvis beskeden handler om en side.
 * @param string                $context 'kontrolpanel' eller 'side'.
 * @param string                $screen  Skærmstørrelse fra browseren.
 * @return array<string, string>
 */
function webto_dashboard_support_info( array $page, string $context, string $screen ): array {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$user    = wp_get_current_user();
	$theme   = wp_get_theme();
	$plugins = array();
	foreach ( get_plugins() as $file => $data ) {
		if ( is_plugin_active( $file ) ) {
			$plugins[] = $data['Name'] . ' ' . $data['Version'];
		}
	}
	$info = array(
		'Hjemmeside' => home_url( '/' ),
		'Bruger'     => $user->display_name . ' <' . $user->user_email . '> (' . implode( ', ', array_map( static fn( $r ) => translate_user_role( wp_roles()->roles[ $r ]['name'] ?? $r ), (array) $user->roles ) ) . ')',
		'Sendt fra'  => 'side' === $context ? 'Panelet på sitet' : 'Kontrolpanelet',
	);
	if ( ! empty( $page['url'] ) ) {
		$info['Side'] = trim( ( $page['titel'] ?? '' ) . ' ' . $page['url'] );
	}
	return $info + array(
		'Browser'   => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		'Skærm'     => $screen,
		'WordPress' => get_bloginfo( 'version' ),
		'PHP'       => PHP_VERSION,
		'Tema'      => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) . ( $theme->parent() ? ' (forældretema ' . $theme->parent()->get( 'Name' ) . ' ' . $theme->parent()->get( 'Version' ) . ')' : '' ),
		'Plugins'   => implode( ', ', $plugins ),
		'Tidspunkt' => wp_date( 'Y-m-d H:i' ),
	);
}

/**
 * Tjekker og gemmer vedhæftningen.
 *
 * @param array<string, mixed> $file Fra $_FILES.
 * @return array{sti: string, navn: string, type: string}|WP_Error|null Null = ingen fil.
 */
function webto_dashboard_store_file( array $file ) {
	$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );
	if ( UPLOAD_ERR_NO_FILE === $error ) {
		return null;
	}
	if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error || (int) ( $file['size'] ?? 0 ) > WEBTO_DASHBOARD_MAX_BYTES ) {
		return new WP_Error( 'stor', __( 'Filen er for stor. Den må højst fylde 5 MB.', 'webto-dashboard' ) );
	}
	if ( UPLOAD_ERR_OK !== $error || empty( $file['tmp_name'] ) || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
		return new WP_Error( 'upload', __( 'Filen kunne ikke modtages. Prøv igen.', 'webto-dashboard' ) );
	}
	$mimes = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
		'pdf'          => 'application/pdf',
	);
	$name  = sanitize_file_name( wp_basename( (string) $file['name'] ) );
	$check = wp_check_filetype_and_ext( (string) $file['tmp_name'], $name, $mimes );
	$real  = function_exists( 'finfo_open' ) ? (string) finfo_file( finfo_open( FILEINFO_MIME_TYPE ), (string) $file['tmp_name'] ) : (string) $check['type'];
	if ( empty( $check['ext'] ) || empty( $check['type'] ) || ! in_array( $real, $mimes, true ) ) {
		return new WP_Error( 'type', __( 'Du kan kun vedhæfte et billede (JPG, PNG, GIF eller WebP) eller en PDF.', 'webto-dashboard' ) );
	}
	if ( ! empty( $check['proper_filename'] ) ) {
		$name = sanitize_file_name( (string) $check['proper_filename'] );
	}

	$upload = wp_upload_dir( null, false );
	$dir    = trailingslashit( $upload['basedir'] ) . 'webto-support/' . wp_date( 'Y' );
	if ( ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'mappe', __( 'Filen kunne ikke gemmes. Send beskeden uden fil, eller prøv igen.', 'webto-dashboard' ) );
	}
	// Ingen direkte adgang til mappen (Apache); filnavnet er desuden tilfældigt. Filen hentes via wp-admin.
	$root = trailingslashit( $upload['basedir'] ) . 'webto-support';
	if ( ! file_exists( $root . '/.htaccess' ) ) {
		file_put_contents( $root . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $root . '/index.php', "<?php\n// Stille.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	$target = $dir . '/' . wp_generate_password( 24, false ) . '-' . $name;
	if ( ! move_uploaded_file( (string) $file['tmp_name'], $target ) ) {
		return new WP_Error( 'flyt', __( 'Filen kunne ikke gemmes. Send beskeden uden fil, eller prøv igen.', 'webto-dashboard' ) );
	}
	chmod( $target, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
	return array(
		'sti'  => substr( $target, strlen( trailingslashit( $upload['basedir'] ) ) ),
		'navn' => $name,
		'type' => $real,
	);
}

/**
 * Har brugeren nået grænsen for beskeder i den sidste time? Registrerer samtidig denne besked.
 *
 * @param int  $user_id Brugeren.
 * @param bool $record  Tæl denne besked med.
 */
function webto_dashboard_rate_limited( int $user_id, bool $record = false ): bool {
	$times = array_filter( (array) get_user_meta( $user_id, '_wd_support_tider', true ), static fn( $t ) => (int) $t > time() - HOUR_IN_SECONDS );
	if ( $record ) {
		$times[] = time();
		update_user_meta( $user_id, '_wd_support_tider', array_values( $times ) );
		return false;
	}
	return count( $times ) >= (int) apply_filters( 'webto_dashboard_support_pr_time', 5 );
}

/**
 * Fjerner linjeskift og tegn, der kan misbruges i en mail-header.
 *
 * @param string $value Værdien.
 */
function webto_dashboard_header_safe( string $value ): string {
	return trim( (string) preg_replace( '/[\r\n\t<>",;]+/', ' ', $value ) );
}

/**
 * Modtager en besked (kontrolpanelet eller panelet). Svarer med JSON, når browseren beder om det (wd_svar=json),
 * ellers sendes man tilbage med en besked.
 */
function webto_dashboard_handle_support(): void {
	$json = isset( $_POST['wd_svar'] ) && 'json' === $_POST['wd_svar']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$fail = static function ( string $message, int $code ) use ( $json ) {
		if ( $json ) {
			wp_send_json( array( 'ok' => false, 'fejl' => $message ), $code );
		}
		set_transient( 'webto_dashboard_fejl_' . get_current_user_id(), $message, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'wd_support', 'fejl', admin_url( 'index.php' ) ) . '#wd-support' );
		exit;
	};

	if ( ! webto_dashboard_can_support() ) {
		$fail( __( 'Du har ikke adgang til at skrive til Webto herfra.', 'webto-dashboard' ), 403 );
	}
	if ( ! isset( $_POST['_wdnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wdnonce'] ) ), 'webto_dashboard_support' ) ) {
		$fail( __( 'Siden var for gammel. Genindlæs siden, og prøv igen.', 'webto-dashboard' ), 403 );
	}
	$user_id = get_current_user_id();
	if ( webto_dashboard_rate_limited( $user_id ) ) {
		/* translators: %s: e-mailadresse til support */
		$fail( sprintf( __( 'Du har sendt mange beskeder den sidste time. Vent lidt, eller skriv til %s fra din egen mail.', 'webto-dashboard' ), webto_dashboard_support_to() ), 429 );
	}

	$message = trim( sanitize_textarea_field( wp_unslash( $_POST['besked'] ?? '' ) ) );
	if ( '' === $message ) {
		$fail( __( 'Skriv en besked, før du sender.', 'webto-dashboard' ), 400 );
	}
	$message = mb_substr( $message, 0, 5000 );
	$context = 'side' === ( $_POST['kontekst'] ?? '' ) ? 'side' : 'kontrolpanel';
	$topic   = 'side' === $context && 'side' === ( $_POST['emne'] ?? '' ) ? 'side' : 'generelt';
	$screen  = mb_substr( sanitize_text_field( wp_unslash( $_POST['skaerm'] ?? '' ) ), 0, 120 );
	$page    = array();
	if ( 'side' === $topic ) {
		$url  = esc_url_raw( wp_unslash( $_POST['side_url'] ?? '' ) );
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( '' !== $url && wp_parse_url( $url, PHP_URL_HOST ) === $home ) {
			$page = array(
				'url'   => $url,
				'titel' => mb_substr( sanitize_text_field( wp_unslash( $_POST['side_titel'] ?? '' ) ), 0, 200 ),
			);
		}
	}

	$file = isset( $_FILES['fil'] ) && is_array( $_FILES['fil'] ) ? webto_dashboard_store_file( $_FILES['fil'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( is_wp_error( $file ) ) {
		$fail( $file->get_error_message(), 400 );
	}

	$info  = webto_dashboard_support_info( $page, $context, $screen );
	$title = wp_trim_words( preg_replace( '/\s+/', ' ', $message ) ?? $message, 10, ' ...' );
	if ( 'side' === $topic && ! empty( $page['titel'] ) ) {
		$title = $page['titel'] . ': ' . $title;
	}
	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'webto_support',
				'post_status'  => 'publish',
				'post_title'   => mb_substr( $title, 0, 140 ),
				'post_content' => $message,
				'post_author'  => $user_id,
			)
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		$fail( __( 'Beskeden kunne ikke gemmes. Prøv igen.', 'webto-dashboard' ), 500 );
	}
	webto_dashboard_rate_limited( $user_id, true );
	update_post_meta( $post_id, '_wd_info', $info );
	update_post_meta( $post_id, '_wd_emne', $topic );
	if ( $file ) {
		update_post_meta( $post_id, '_wd_fil', $file );
	}

	// Mailen.
	$user    = wp_get_current_user();
	$lines   = array();
	foreach ( $info as $key => $value ) {
		$lines[] = $key . ': ' . $value;
	}
	$body    = $message . "\n\n--\n" . implode( "\n", $lines ) . "\n\n" . sprintf( 'Beskeden ligger også på sitet: %s', webto_dashboard_message_url( (int) $post_id ) ) . "\n";
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	$email   = sanitize_email( $user->user_email );
	if ( is_email( $email ) ) {
		$headers[] = 'Reply-To: ' . webto_dashboard_header_safe( $user->display_name ) . ' <' . $email . '>';
	}
	$subject = webto_dashboard_header_safe( sprintf( 'Support fra %s: %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $title ) );
	$attach  = $file ? array( trailingslashit( wp_upload_dir( null, false )['basedir'] ) . $file['sti'] ) : array();

	$error  = '';
	$listen = static function ( WP_Error $e ) use ( &$error ) {
		$error = $e->get_error_message();
	};
	add_action( 'wp_mail_failed', $listen );
	$sent = wp_mail( webto_dashboard_support_to(), $subject, $body, $headers, $attach ); // Temaets spærre (homelab) svarer nej og skriver mailen i loggen.
	remove_action( 'wp_mail_failed', $listen );
	if ( ! $sent && 'spaerret' === webto_dashboard_mail_status() ) {
		$error = 'Mail er spærret på udviklingssitet (temaets mail-spærre).';
	}
	$status = $sent ? 'sendt' : 'ikke-sendt';
	update_post_meta( $post_id, '_wd_status', $status );
	if ( ! $sent ) {
		update_post_meta( $post_id, '_wd_fejl', '' !== $error ? $error : 'wp_mail svarede nej.' );
	}

	if ( $json ) {
		wp_send_json(
			array(
				'ok'     => true,
				'status' => $status,
				'html'   => webto_dashboard_result_html( $status ),
				'titel'  => get_the_title( $post_id ),
				'url'    => webto_dashboard_message_url( (int) $post_id ),
				'pille'  => webto_dashboard_status_pill( get_post( $post_id ) ),
				'tid'    => webto_dashboard_when( time() ),
			)
		);
	}
	wp_safe_redirect( add_query_arg( 'wd_support', $status, admin_url( 'index.php' ) ) . '#wd-support' );
	exit;
}
add_action( 'admin_post_webto_dashboard_support', 'webto_dashboard_handle_support' );

// Undermenuen "Beskeder til Webto" under Kontrolpanel.
add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'index.php', __( 'Beskeder til Webto', 'webto-dashboard' ), __( 'Beskeder til Webto', 'webto-dashboard' ), (string) apply_filters( 'webto_dashboard_cap_support', 'edit_posts' ), 'webto-beskeder', 'webto_dashboard_messages_page' );
	}
);

add_action(
	'admin_enqueue_scripts',
	static function () {
		if ( isset( $_GET['page'] ) && 'webto-beskeder' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style( 'webto-dashboard', WEBTO_DASHBOARD_URL . 'assets/dashboard.css', array( 'dashicons', 'buttons' ), webto_dashboard_asset_version( 'assets/dashboard.css' ) );
		}
	}
);

/**
 * Må brugeren se beskeden? (Sin egen, eller alle som administrator.)
 *
 * @param WP_Post|null $post Beskeden.
 */
function webto_dashboard_can_see_message( ?WP_Post $post ): bool {
	return $post && 'webto_support' === $post->post_type && webto_dashboard_can_support() && ( (int) $post->post_author === get_current_user_id() || current_user_can( 'manage_options' ) );
}

/**
 * Siden "Beskeder til Webto": listen, eller én besked (?besked=id).
 */
function webto_dashboard_messages_page(): void {
	$id   = isset( $_GET['besked'] ) ? absint( $_GET['besked'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post = $id ? get_post( $id ) : null;
	if ( $id && ! webto_dashboard_can_see_message( $post ) ) {
		wp_die( esc_html__( 'Beskeden findes ikke.', 'webto-dashboard' ), 404 );
	}
	echo '<div class="wrap wd wd--messages">';
	if ( $post ) {
		$info = (array) get_post_meta( $post->ID, '_wd_info', true );
		$file = get_post_meta( $post->ID, '_wd_fil', true );
		$err  = (string) get_post_meta( $post->ID, '_wd_fejl', true );
		echo '<p class="wd-back"><a href="' . esc_url( webto_dashboard_message_url() ) . '">' . esc_html__( 'Alle beskeder til Webto', 'webto-dashboard' ) . '</a></p>';
		echo '<h1 class="wd-title">' . esc_html( $post->post_title ) . '</h1><hr class="wp-header-end">';
		echo '<div class="wd-card wd-message"><p class="wd-message__meta">' . webto_dashboard_status_pill( $post ) . ' ' . esc_html( wp_date( 'j. F Y H:i', (int) get_post_time( 'U', true, $post ) ) ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pillen er escaped.
		echo '<div class="wd-message__text">' . nl2br( esc_html( $post->post_content ) ) . '</div>';
		if ( is_array( $file ) && ! empty( $file['sti'] ) ) {
			$url = wp_nonce_url( add_query_arg( array( 'action' => 'webto_dashboard_fil', 'besked' => $post->ID ), admin_url( 'admin-post.php' ) ), 'webto_dashboard_fil_' . $post->ID );
			echo '<p><a class="button" href="' . esc_url( $url ) . '"><span class="dashicons dashicons-paperclip" aria-hidden="true"></span>' . esc_html( (string) $file['navn'] ) . '</a></p>';
		}
		if ( '' !== $err && current_user_can( 'manage_options' ) ) {
			echo '<p class="wd-notice"><strong>' . esc_html__( 'Ikke sendt', 'webto-dashboard' ) . '</strong>' . esc_html( $err ) . '</p>';
		}
		echo '<h2 class="wd-sub">' . esc_html__( 'Sendt automatisk med', 'webto-dashboard' ) . '</h2><table class="widefat striped wd-info"><tbody>';
		foreach ( $info as $key => $value ) {
			echo '<tr><th scope="row">' . esc_html( (string) $key ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	} else {
		echo '<h1 class="wd-title">' . esc_html__( 'Beskeder til Webto', 'webto-dashboard' ) . '</h1><hr class="wp-header-end">';
		echo '<p class="wd-hello">' . esc_html__( 'Alt, du har skrevet til Webto herfra. Beskederne gemmes her, også hvis mailen ikke kom frem.', 'webto-dashboard' ) . '</p>';
		$messages = webto_dashboard_messages( -1 );
		if ( ! $messages ) {
			echo '<p>' . esc_html__( 'Du har ikke skrevet til os endnu. Det gør du fra kontrolpanelet eller med knappen Support i den sorte bjælke.', 'webto-dashboard' ) . '</p>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Besked', 'webto-dashboard' ) . '</th>' . ( current_user_can( 'manage_options' ) ? '<th scope="col">' . esc_html__( 'Fra', 'webto-dashboard' ) . '</th>' : '' ) . '<th scope="col">' . esc_html__( 'Status', 'webto-dashboard' ) . '</th><th scope="col">' . esc_html__( 'Dato', 'webto-dashboard' ) . '</th></tr></thead><tbody>';
			foreach ( $messages as $post ) {
				echo '<tr><td><a href="' . esc_url( webto_dashboard_message_url( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a>' . ( get_post_meta( $post->ID, '_wd_fil', true ) ? ' <span class="dashicons dashicons-paperclip" aria-label="' . esc_attr__( 'med fil', 'webto-dashboard' ) . '"></span>' : '' ) . '</td>'
					. ( current_user_can( 'manage_options' ) ? '<td>' . esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ) . '</td>' : '' )
					. '<td>' . webto_dashboard_status_pill( $post ) . '</td><td>' . esc_html( webto_dashboard_when( (int) get_post_time( 'U', true, $post ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ovenfor.
			}
			echo '</tbody></table>';
		}
	}
	echo '</div>';
}

// Henter en vedhæftning (kun for den, der skrev beskeden, og administratorer).
add_action(
	'admin_post_webto_dashboard_fil',
	static function () {
		$id   = isset( $_GET['besked'] ) ? absint( $_GET['besked'] ) : 0;
		$post = get_post( $id );
		check_admin_referer( 'webto_dashboard_fil_' . $id );
		$file = $post ? get_post_meta( $post->ID, '_wd_fil', true ) : null;
		if ( ! webto_dashboard_can_see_message( $post ) || ! is_array( $file ) || empty( $file['sti'] ) ) {
			wp_die( esc_html__( 'Filen findes ikke.', 'webto-dashboard' ), 404 );
		}
		$base = trailingslashit( wp_upload_dir( null, false )['basedir'] );
		$path = realpath( $base . $file['sti'] );
		if ( ! $path || ! str_starts_with( $path, realpath( $base . 'webto-support' ) . '/' ) || ! is_readable( $path ) ) {
			wp_die( esc_html__( 'Filen findes ikke.', 'webto-dashboard' ), 404 );
		}
		nocache_headers();
		header( 'Content-Type: ' . ( in_array( $file['type'], array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf' ), true ) ? $file['type'] : 'application/octet-stream' ) );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: inline; filename="' . webto_dashboard_header_safe( (string) $file['navn'] ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
);
