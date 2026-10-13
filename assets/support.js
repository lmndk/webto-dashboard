/**
 * Webto Dashboard - Skriv til Webto (kontrolpanelet og panelet på sitet).
 *
 * Formularen virker uden JavaScript (admin-post.php sender en tilbage til kontrolpanelet). Med JavaScript:
 * skærmstørrelsen sendes med, filen tjekkes før afsendelse (billede eller PDF, højst 5 MB), beskeden sendes uden at
 * forlade siden, og svaret vises med det samme. På sitet åbner knappen "Support" i adminbaren panelet (<dialog>);
 * Esc, Luk og et klik ved siden af lukker, og fokus går tilbage til knappen.
 */
( () => {
	const MAX = 5 * 1024 * 1024;
	const TYPES = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf' ];

	function screenInfo() {
		return window.innerWidth + 'x' + window.innerHeight + ' vindue, ' + window.screen.width + 'x' + window.screen.height + ' skærm, zoom ' + ( Math.round( window.devicePixelRatio * 100 ) / 100 );
	}

	function setup( form ) {
		const scope = form.closest( '.wd-card, .wd-dialog' ) || document;
		const file = form.querySelector( '[data-wd-fil]' );
		const fileName = form.querySelector( '[data-wd-filnavn]' );
		const remove = form.querySelector( '[data-wd-fjern]' );
		const error = form.querySelector( '[data-wd-fejl]' );
		const send = form.querySelector( '[data-wd-send]' );
		const result = scope.querySelector( '[data-wd-resultat]' );

		const showError = ( text ) => {
			error.replaceChildren();
			if ( text ) {
				const p = document.createElement( 'p' );
				p.className = 'wd-notice';
				p.textContent = text;
				error.append( p );
			}
		};

		const checkFile = () => {
			const f = file.files && file.files[ 0 ];
			fileName.textContent = f ? f.name : '';
			remove.hidden = ! f;
			if ( f && f.size > MAX ) {
				return 'Filen er for stor. Den må højst fylde 5 MB.';
			}
			if ( f && f.type && ! TYPES.includes( f.type ) ) {
				return 'Du kan kun vedhæfte et billede (JPG, PNG, GIF eller WebP) eller en PDF.';
			}
			return '';
		};

		file.addEventListener( 'change', () => showError( checkFile() ) );
		remove.addEventListener( 'click', () => {
			file.value = '';
			showError( checkFile() );
			file.focus();
		} );

		form.addEventListener( 'submit', async ( e ) => {
			form.querySelector( '[data-wd-skaerm]' ).value = screenInfo();
			if ( ! window.fetch || ! window.FormData ) {
				return; // Almindelig afsendelse.
			}
			e.preventDefault();
			const problem = checkFile();
			const text = form.querySelector( 'textarea' );
			if ( ! text.value.trim() ) {
				showError( 'Skriv en besked, før du sender.' );
				text.focus();
				return;
			}
			if ( problem ) {
				showError( problem );
				return;
			}
			showError( '' );
			const label = send.textContent;
			send.disabled = true;
			send.textContent = 'Sender ...';
			const data = new FormData( form );
			data.append( 'wd_svar', 'json' );
			let answer = null;
			try {
				// getAttribute: form.action ville være det skjulte felt, der hedder "action".
				const response = await fetch( form.getAttribute( 'action' ), { method: 'POST', body: data, credentials: 'same-origin' } );
				answer = await response.json();
			} catch ( err ) {
				answer = { ok: false, fejl: 'Beskeden kunne ikke sendes. Tjek forbindelsen, og prøv igen.' };
			}
			send.disabled = false;
			send.textContent = label;
			if ( ! answer || ! answer.ok ) {
				showError( ( answer && answer.fejl ) || 'Beskeden kunne ikke sendes. Prøv igen.' );
				return;
			}

			// Svaret er HTML fra serveren (escaped dér).
			result.innerHTML = answer.html;
			form.reset();
			checkFile();

			// Kontrolpanelet: den nye besked øverst i "Dine beskeder til Webto".
			const list = scope.querySelector( '[data-wd-beskeder]' );
			if ( list ) {
				const li = document.createElement( 'li' );
				const a = document.createElement( 'a' );
				a.className = 'wd-list__title';
				a.href = answer.url;
				a.textContent = answer.titel;
				const meta = document.createElement( 'span' );
				meta.className = 'wd-list__meta';
				meta.innerHTML = answer.pille;
				meta.append( answer.tid );
				li.append( a, meta );
				list.prepend( li );
				list.hidden = false;
				const none = scope.querySelector( '[data-wd-ingen]' );
				if ( none ) {
					none.hidden = true;
				}
			}

			// Panelet: formularen skjules, svaret står alene med "Skriv en ny besked" og "Luk".
			const wrap = scope.querySelector( '[data-wd-formular]' );
			const after = scope.querySelector( '[data-wd-efter]' );
			if ( wrap && after ) {
				wrap.hidden = true;
				after.hidden = false;
			}
			result.setAttribute( 'tabindex', '-1' );
			result.focus();
		} );
	}

	document.querySelectorAll( 'form[data-wd-support]' ).forEach( setup );

	// Panelet på sitet.
	const dialog = document.getElementById( 'wd-support-dialog' );
	if ( ! dialog || 'function' !== typeof dialog.showModal ) {
		return;
	}
	const title = dialog.querySelector( '.wd-dialog__title' );
	let opener = null;

	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest( '#wp-admin-bar-webto-support > a' );
		if ( ! link || e.ctrlKey || e.metaKey || e.shiftKey || 0 !== e.button ) {
			return;
		}
		e.preventDefault();
		opener = link;
		dialog.showModal();
		title.focus();
	} );
	dialog.querySelectorAll( '[data-wd-close]' ).forEach( ( b ) => b.addEventListener( 'click', () => dialog.close() ) );
	dialog.querySelector( '[data-wd-ny]' ).addEventListener( 'click', () => {
		dialog.querySelector( '[data-wd-resultat]' ).replaceChildren();
		dialog.querySelector( '[data-wd-formular]' ).hidden = false;
		dialog.querySelector( '[data-wd-efter]' ).hidden = true;
		dialog.querySelector( 'textarea' ).focus();
	} );
	dialog.addEventListener( 'click', ( e ) => {
		if ( e.target === dialog ) {
			dialog.close();
		}
	} );
	dialog.addEventListener( 'close', () => {
		if ( opener ) {
			opener.focus();
		}
	} );
} )();
