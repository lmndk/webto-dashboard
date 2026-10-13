/**
 * Webto Dashboard - toppen af kontrolpanelet: uret med dato og billedet, der skifter hver hele time.
 *
 * Billedet: timer siden 1970 (UTC) modulo antallet af billeder - samme regel som PHP
 * (webto_dashboard_background_index), så alle installationer viser samme billede i samme time. Serverens ur bruges
 * (forskellen til browserens ur måles ved indlæsning), så en forkert indstillet computer ikke giver et andet billede.
 * Uret og datoen vises i sitets tidszone. Uden JavaScript står serverens tid og billede.
 */
( () => {
	const top = document.querySelector( '[data-wd-top]' );
	if ( ! top ) {
		return;
	}
	const media = top.querySelector( '.wd-hero__media' );
	const timeEl = top.querySelector( '[data-wd-tid]' );
	const dateEl = top.querySelector( '[data-wd-dato]' );
	let images = [];
	try {
		images = JSON.parse( top.querySelector( '[data-wd-billeder]' ).textContent ) || [];
	} catch ( e ) {
		images = [];
	}
	const offset = Number( top.dataset.wdServertid ) * 1000 - Date.now();
	const now = () => Date.now() + ( Number.isFinite( offset ) ? offset : 0 );
	const HOUR = 3600000;
	const MINUTE = 60000;
	let zone = top.dataset.wdTidszone || undefined;
	try {
		new Intl.DateTimeFormat( 'da-DK', { timeZone: zone } );
	} catch ( e ) {
		zone = undefined; // Ukendt tidszone: browserens egen.
	}
	const timeFmt = new Intl.DateTimeFormat( 'da-DK', { hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZone: zone } );
	const dateFmt = new Intl.DateTimeFormat( 'da-DK', { weekday: 'long', day: 'numeric', month: 'long', timeZone: zone } );
	const part = ( parts, type ) => ( parts.find( ( p ) => p.type === type ) || {} ).value || '';
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	let current = images.length ? Math.floor( now() / HOUR ) % images.length : 0;

	function updateClock() {
		const d = new Date( now() );
		const t = timeFmt.formatToParts( d );
		const p = dateFmt.formatToParts( d );
		// "20:42" og "tirsdag 13. oktober" (som PHP's wp_date( 'H:i' ) og wp_date( 'l j. F' )).
		timeEl.textContent = part( t, 'hour' ) + ':' + part( t, 'minute' );
		timeEl.setAttribute( 'datetime', d.toISOString() );
		dateEl.textContent = part( p, 'weekday' ) + ' ' + part( p, 'day' ) + '. ' + part( p, 'month' );
	}

	function show( index ) {
		const data = images[ index ];
		if ( ! data ) {
			return;
		}
		const img = new Image();
		img.className = 'wd-hero__img';
		img.alt = '';
		img.decoding = 'async';
		img.sizes = '100vw';
		img.srcset = data.lille + ' 1000w, ' + data.stor + ' 2000w';
		img.src = data.lille;
		const swap = () => {
			media.append( img );
			// Næste billede tones frem, og det gamle fjernes bagefter (ingen overgang ved "reducer bevægelse").
			window.requestAnimationFrame( () => window.requestAnimationFrame( () => img.classList.add( 'is-on' ) ) );
			media.querySelectorAll( '.wd-hero__img' ).forEach( ( old ) => {
				if ( old !== img ) {
					old.classList.remove( 'is-on' );
					window.setTimeout( () => old.remove(), reduce.matches ? 0 : 1600 );
				}
			} );
			current = index;
		};
		( img.decode ? img.decode() : Promise.resolve() ).then( swap, swap );
	}

	function updateImage() {
		if ( images.length > 1 ) {
			const index = Math.floor( now() / HOUR ) % images.length;
			if ( index !== current ) {
				show( index );
			}
		}
	}

	// Ét tjek ved hvert minutskifte: uret, og billedet (der kun skifter ved et timeskifte).
	function loop() {
		updateClock();
		updateImage();
		window.setTimeout( loop, MINUTE - ( now() % MINUTE ) + 50 );
	}
	loop();

	// En fane i baggrunden får sjældnere timere: ret med det samme, når man kommer tilbage.
	document.addEventListener( 'visibilitychange', () => {
		if ( ! document.hidden ) {
			updateClock();
			updateImage();
		}
	} );
} )();
