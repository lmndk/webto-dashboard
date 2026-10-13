<?php
/**
 * Overblikket: et kort pr. indholdstype (findes af sig selv, også nye typer), billeder og filer (med HappyFiles-mapper),
 * henvendelser fra Fluent Forms (kun når pluginnet er aktivt) og kort fra andre plugins (filteret webto_dashboard_tiles).
 *
 * Et kort er et array: id, titel, ikon (dashicons-navn), link (url + tekst, "Se alle"), html (kortets indhold),
 * omraade ('indhold' eller 'hjaelp') og raekkefoelge. Dashboardet tegner rammen; html er kortets eget (escaped).
 *
 * @package Webto_Dashboard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Et tidspunkt, som et menneske siger det: "i dag 10:42", "i går", "3. okt." eller "3. okt. 2025".
 *
 * @param int $timestamp Unix-tid.
 */
function webto_dashboard_when( int $timestamp ): string {
	$tz    = wp_timezone();
	$day   = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $tz )->format( 'Y-m-d' );
	$today = ( new DateTimeImmutable( 'now', $tz ) )->format( 'Y-m-d' );
	$yday  = ( new DateTimeImmutable( 'yesterday', $tz ) )->format( 'Y-m-d' );
	if ( $day === $today ) {
		/* translators: %s: klokkeslæt */
		return sprintf( __( 'i dag %s', 'webto-dashboard' ), wp_date( 'H:i', $timestamp ) );
	}
	if ( $day === $yday ) {
		return __( 'i går', 'webto-dashboard' );
	}
	return wp_date( wp_date( 'Y' ) === wp_date( 'Y', $timestamp ) ? 'j. M' : 'j. M Y', $timestamp );
}

/**
 * Indholdstyperne i overblikket: Sider, Indlæg og alle typer med deres eget punkt i menuen (sitets egne, også nye).
 * WordPress' interne typer og plugins' typer under pluginnets egen menu kommer ikke med. Kun dem, brugeren må rette.
 *
 * @return WP_Post_Type[]
 */
function webto_dashboard_post_types(): array {
	$out = array();
	foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $name => $type ) {
		if ( in_array( $name, array( 'attachment', 'webto_vejledning', 'webto_support' ), true ) ) {
			continue;
		}
		if ( ! in_array( $name, array( 'post', 'page' ), true ) && ( $type->_builtin || true !== $type->show_in_menu ) ) {
			continue;
		}
		if ( current_user_can( $type->cap->edit_posts ) ) {
			$out[ $name ] = $type;
		}
	}
	// Sider først, så Indlæg, så resten i menuens rækkefølge.
	uasort(
		$out,
		static fn( $a, $b ) => array( 'page' === $a->name ? 0 : ( 'post' === $a->name ? 1 : 2 ), $a->menu_position ?? 99, $a->label ) <=> array( 'page' === $b->name ? 0 : ( 'post' === $b->name ? 1 : 2 ), $b->menu_position ?? 99, $b->label )
	);
	return (array) apply_filters( 'webto_dashboard_post_types', $out );
}

/**
 * Ikonet til en indholdstype (dens eget dashicon, ellers et standardikon).
 *
 * @param WP_Post_Type $type Typen.
 */
function webto_dashboard_type_icon( WP_Post_Type $type ): string {
	if ( 'page' === $type->name ) {
		return 'dashicons-admin-page';
	}
	if ( 'post' === $type->name ) {
		return 'dashicons-admin-post';
	}
	$icon = (string) $type->menu_icon;
	return str_starts_with( $icon, 'dashicons-' ) ? $icon : 'dashicons-admin-post';
}

/**
 * Små runde mærker (status).
 *
 * @param string $text    Tekst.
 * @param string $variant '', 'new', 'future' eller 'warn'.
 */
function webto_dashboard_pill( string $text, string $variant = '' ): string {
	return '<span class="wd-pill' . ( '' !== $variant ? ' wd-pill--' . esc_attr( $variant ) : '' ) . '">' . esc_html( $text ) . '</span>';
}

/**
 * Kortet til én indholdstype.
 *
 * @param WP_Post_Type $type Typen.
 * @return array<string, mixed>
 */
function webto_dashboard_type_card( WP_Post_Type $type ): array {
	$counts = wp_count_posts( $type->name );
	$name   = $type->name;
	$list   = add_query_arg( 'post_type', $name, admin_url( 'edit.php' ) );
	$more   = array();
	foreach (
		array(
			/* translators: %d: antal */
			'draft'   => array( _n( '%d kladde', '%d kladder', (int) ( $counts->draft ?? 0 ), 'webto-dashboard' ), (int) ( $counts->draft ?? 0 ) ),
			/* translators: %d: antal */
			'future'  => array( _n( '%d planlagt', '%d planlagte', (int) ( $counts->future ?? 0 ), 'webto-dashboard' ), (int) ( $counts->future ?? 0 ) ),
			/* translators: %d: antal */
			'pending' => array( _n( '%d venter på godkendelse', '%d venter på godkendelse', (int) ( $counts->pending ?? 0 ), 'webto-dashboard' ), (int) ( $counts->pending ?? 0 ) ),
		) as $status => $info
	) {
		if ( $info[1] > 0 ) {
			$more[] = '<a href="' . esc_url( add_query_arg( 'post_status', $status, $list ) ) . '">' . esc_html( sprintf( $info[0], $info[1] ) ) . '</a>';
		}
	}

	$html  = '<p class="wd-stat"><span class="wd-stat__num">' . esc_html( number_format_i18n( (int) ( $counts->publish ?? 0 ) ) ) . '</span><span class="wd-stat__label">' . esc_html__( 'udgivet', 'webto-dashboard' ) . '</span>';
	$html .= $more ? '<span class="wd-stat__more">' . implode( ', ', $more ) . '</span>' : '';
	$html .= '</p>';

	$latest = get_posts(
		array(
			'post_type'      => $name,
			'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		)
	);
	if ( $latest ) {
		$html .= '<ul class="wd-list" aria-label="' . esc_attr__( 'Senest ændret', 'webto-dashboard' ) . '">';
		foreach ( $latest as $post ) {
			$title = '' !== trim( $post->post_title ) ? $post->post_title : __( '(uden titel)', 'webto-dashboard' );
			$link  = get_edit_post_link( $post->ID ) ?: get_permalink( $post );
			$meta  = '';
			if ( 'future' === $post->post_status ) {
				/* translators: %s: dato */
				$meta = webto_dashboard_pill( sprintf( __( 'Planlagt %s', 'webto-dashboard' ), wp_date( 'j. M', (int) get_post_time( 'U', true, $post ) ) ), 'future' );
			} else {
				$labels = array(
					'draft'   => __( 'Kladde', 'webto-dashboard' ),
					'pending' => __( 'Venter', 'webto-dashboard' ),
					'private' => __( 'Privat', 'webto-dashboard' ),
				);
				$meta   = ( isset( $labels[ $post->post_status ] ) ? webto_dashboard_pill( $labels[ $post->post_status ] ) : '' ) . esc_html( webto_dashboard_when( (int) get_post_modified_time( 'U', true, $post ) ) );
			}
			$html .= '<li><a class="wd-list__title" href="' . esc_url( (string) $link ) . '">' . esc_html( $title ) . '</a><span class="wd-list__meta">' . $meta . '</span></li>';
		}
		$html .= '</ul>';
	} else {
		/* translators: %s: indholdstypens navn med små bogstaver */
		$html = '<p class="wd-empty">' . esc_html( sprintf( __( 'Der er ingen %s endnu.', 'webto-dashboard' ), mb_strtolower( (string) $type->labels->name ) ) ) . '</p>';
	}

	if ( current_user_can( $type->cap->create_posts ) ) {
		$html .= '<div class="wd-card__foot"><a class="button" href="' . esc_url( add_query_arg( 'post_type', $name, admin_url( 'post-new.php' ) ) ) . '">' . esc_html( (string) $type->labels->add_new_item ) . '</a></div>';
	}

	return array(
		'id'           => 'type-' . $name,
		'titel'        => (string) $type->labels->name,
		'ikon'         => webto_dashboard_type_icon( $type ),
		'link'         => array( 'url' => $list, 'tekst' => __( 'Se alle', 'webto-dashboard' ) ),
		'html'         => $html,
		'omraade'      => 'indhold',
		'raekkefoelge' => 10,
	);
}

/**
 * Kortet "Billeder og filer": antal, de seneste fire og HappyFiles' mapper.
 *
 * @return array<string, mixed>|null
 */
function webto_dashboard_media_card(): ?array {
	if ( ! current_user_can( 'upload_files' ) ) {
		return null;
	}
	$counts = (array) wp_count_attachments();
	unset( $counts['trash'] );
	$total = (int) array_sum( array_map( 'intval', $counts ) );
	$html  = '<p class="wd-stat"><span class="wd-stat__num">' . esc_html( number_format_i18n( $total ) ) . '</span><span class="wd-stat__label">' . esc_html( _n( 'fil', 'filer', $total, 'webto-dashboard' ) ) . '</span></p>';

	$latest = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 4,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	if ( $latest ) {
		$html .= '<ul class="wd-thumbs" aria-label="' . esc_attr__( 'Senest uploadet', 'webto-dashboard' ) . '">';
		foreach ( $latest as $file ) {
			$img   = wp_get_attachment_image( $file->ID, 'thumbnail', true, array( 'alt' => '' ) );
			$html .= '<li><a href="' . esc_url( (string) get_edit_post_link( $file->ID ) ) . '" aria-label="' . esc_attr( get_the_title( $file ) ) . '">' . $img . '</a></li>';
		}
		$html .= '</ul>';
	}

	// HappyFiles' mapper (kun når pluginnet er aktivt).
	if ( taxonomy_exists( 'happyfiles_category' ) ) {
		$folders = get_terms(
			array(
				'taxonomy'   => 'happyfiles_category',
				'hide_empty' => false,
				'parent'     => 0,
				'number'     => 8,
				'orderby'    => 'name',
			)
		);
		if ( is_array( $folders ) && $folders ) {
			$html .= '<ul class="wd-folders" aria-label="' . esc_attr__( 'Mapper', 'webto-dashboard' ) . '">';
			foreach ( $folders as $folder ) {
				$url   = add_query_arg( array( 'mode' => 'list', 'taxonomy' => 'happyfiles_category', 'term' => $folder->slug ), admin_url( 'upload.php' ) );
				$html .= '<li><a href="' . esc_url( $url ) . '"><span class="dashicons dashicons-category" aria-hidden="true"></span>' . esc_html( $folder->name ) . ' <span class="screen-reader-text">(</span>' . esc_html( number_format_i18n( (int) $folder->count ) ) . '<span class="screen-reader-text"> ' . esc_html__( 'filer', 'webto-dashboard' ) . ')</span></a></li>';
			}
			$html .= '</ul>';
		}
	}
	$html .= '<div class="wd-card__foot"><a class="button" href="' . esc_url( admin_url( 'media-new.php' ) ) . '">' . esc_html__( 'Upload filer', 'webto-dashboard' ) . '</a></div>';

	return array(
		'id'           => 'medier',
		'titel'        => __( 'Billeder og filer', 'webto-dashboard' ),
		'ikon'         => 'dashicons-admin-media',
		'link'         => array( 'url' => admin_url( 'upload.php' ), 'tekst' => __( 'Se alle', 'webto-dashboard' ) ),
		'html'         => $html,
		'omraade'      => 'indhold',
		'raekkefoelge' => 30,
	);
}

/**
 * Kortet "Henvendelser" fra Fluent Forms (kun når pluginnet er aktivt, og brugeren må se indsendelser).
 *
 * @return array<string, mixed>|null
 */
function webto_dashboard_forms_card(): ?array {
	global $wpdb;
	if ( ! defined( 'FLUENTFORM' ) ) {
		return null;
	}
	$allowed = current_user_can( 'manage_options' );
	if ( ! $allowed && class_exists( '\FluentForm\App\Modules\Acl\Acl' ) && method_exists( '\FluentForm\App\Modules\Acl\Acl', 'hasPermission' ) ) {
		$allowed = (bool) \FluentForm\App\Modules\Acl\Acl::hasPermission( 'fluentform_entries_viewer' );
	}
	if ( ! $allowed ) {
		return null;
	}
	$table = $wpdb->prefix . 'fluentform_submissions';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return null;
	}
	$forms  = $wpdb->prefix . 'fluentform_forms';
	$unread = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'unread'" ); // phpcs:ignore WordPress.DB
	$rows   = $wpdb->get_results( "SELECT s.id, s.form_id, s.status, s.created_at, s.response, f.title FROM {$table} s LEFT JOIN {$forms} f ON f.id = s.form_id WHERE s.status <> 'trashed' ORDER BY s.id DESC LIMIT 3" ); // phpcs:ignore WordPress.DB

	$html  = '<p class="wd-stat"><span class="wd-stat__num">' . esc_html( number_format_i18n( $unread ) ) . '</span><span class="wd-stat__label">' . esc_html( _n( 'ny', 'nye', $unread, 'webto-dashboard' ) ) . '</span><span class="wd-stat__more">' . esc_html__( 'fra formularerne på hjemmesiden', 'webto-dashboard' ) . '</span></p>';
	if ( $rows ) {
		$html .= '<ul class="wd-list" aria-label="' . esc_attr__( 'Seneste henvendelser', 'webto-dashboard' ) . '">';
		foreach ( $rows as $row ) {
			$who   = webto_dashboard_submission_name( (string) $row->response );
			$title = (string) ( $row->title ?: __( 'Formular', 'webto-dashboard' ) ) . ( '' !== $who ? ': ' . $who : '' );
			$url   = admin_url( 'admin.php?page=fluent_forms&route=entries&form_id=' . (int) $row->form_id . '#/entries/' . (int) $row->id );
			$time  = ( new DateTimeImmutable( (string) $row->created_at, wp_timezone() ) )->getTimestamp();
			$html .= '<li><a class="wd-list__title" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a><span class="wd-list__meta">' . ( 'unread' === $row->status ? webto_dashboard_pill( __( 'Ny', 'webto-dashboard' ), 'new' ) : '' ) . esc_html( webto_dashboard_when( $time ) ) . '</span></li>';
		}
		$html .= '</ul>';
	} else {
		$html .= '<p class="wd-empty">' . esc_html__( 'Der er ingen henvendelser endnu.', 'webto-dashboard' ) . '</p>';
	}
	$html .= '<div class="wd-card__foot"><a class="button" href="' . esc_url( admin_url( 'admin.php?page=fluent_forms_all_entries' ) ) . '">' . esc_html__( 'Se henvendelserne', 'webto-dashboard' ) . '</a></div>';

	return array(
		'id'           => 'henvendelser',
		'titel'        => __( 'Henvendelser', 'webto-dashboard' ),
		'ikon'         => 'dashicons-feedback',
		'link'         => array( 'url' => admin_url( 'admin.php?page=fluent_forms_all_entries' ), 'tekst' => __( 'Se alle', 'webto-dashboard' ) ),
		'html'         => $html,
		'omraade'      => 'indhold',
		'raekkefoelge' => 20,
	);
}

/**
 * Et fornavn (eller en e-mail) fra en indsendelse, så listen siger, hvem den er fra.
 *
 * @param string $json Fluent Forms' svar som JSON.
 */
function webto_dashboard_submission_name( string $json ): string {
	$data = json_decode( $json, true );
	if ( ! is_array( $data ) ) {
		return '';
	}
	foreach ( array( 'names', 'name', 'navn', 'full_name' ) as $key ) {
		$value = $data[ $key ] ?? null;
		if ( is_array( $value ) ) {
			$value = $value['first_name'] ?? reset( $value );
		}
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return trim( strtok( trim( $value ), ' ' ) );
		}
	}
	return isset( $data['email'] ) && is_string( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
}

/**
 * Alle kort i den rækkefølge, de vises: indholdstyperne, Henvendelser, Billeder og filer, andre plugins' kort
 * (webto_dashboard_tiles) og til sidst hjælpen (support og vejledning).
 *
 * @return array<int, array<string, mixed>>
 */
function webto_dashboard_cards(): array {
	$cards = array();
	foreach ( webto_dashboard_post_types() as $type ) {
		$cards[] = webto_dashboard_type_card( $type );
	}
	foreach ( array( webto_dashboard_forms_card(), webto_dashboard_media_card() ) as $card ) {
		if ( $card ) {
			$cards[] = $card;
		}
	}
	$cards = (array) apply_filters( 'webto_dashboard_tiles', $cards );
	$cards = array_values(
		array_filter(
			array_map(
				static fn( $c ) => is_array( $c ) ? wp_parse_args( $c, array( 'id' => '', 'titel' => '', 'ikon' => '', 'link' => null, 'html' => '', 'omraade' => 'indhold', 'raekkefoelge' => 50 ) ) : null,
				$cards
			)
		)
	);
	// Stabil sortering: område (indhold før hjælp), så rækkefølge, så den rækkefølge, de blev tilføjet i.
	foreach ( $cards as $i => $card ) {
		$cards[ $i ]['_i'] = $i;
	}
	usort(
		$cards,
		static fn( $a, $b ) => array( 'hjaelp' === $a['omraade'] ? 1 : 0, (int) $a['raekkefoelge'], $a['_i'] ) <=> array( 'hjaelp' === $b['omraade'] ? 1 : 0, (int) $b['raekkefoelge'], $b['_i'] )
	);
	return $cards;
}
