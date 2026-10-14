/**
 * Webto Dashboard - rækken af genveje. Rækken er altid én linje; er der flere genveje, end der er plads til, glider den
 * til siden. Pilene (og den bløde kant) vises kun i den retning, der er mere at se. Tastaturet behøver ikke pilene:
 * browseren ruller selv en genvej frem, når den får fokus. Uden JavaScript kan rækken stadig rulles.
 */
( () => {
	const nav = document.querySelector( '[data-wd-genveje]' );
	if ( ! nav ) {
		return;
	}
	const list = nav.querySelector( '[data-wd-genveje-liste]' );
	const prev = nav.querySelector( '[data-wd-pil="-1"]' );
	const next = nav.querySelector( '[data-wd-pil="1"]' );
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function update() {
		const max = list.scrollWidth - list.clientWidth;
		const before = list.scrollLeft > 2;
		const after = list.scrollLeft < max - 2;
		prev.hidden = ! before;
		next.hidden = ! after;
		nav.classList.toggle( 'har-foer', before );
		nav.classList.toggle( 'har-efter', after );
	}

	[ prev, next ].forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			// Næsten en hel skærmfuld ad gangen, så den sidste synlige genvej står først bagefter.
			list.scrollBy( { left: Number( button.dataset.wdPil ) * list.clientWidth * 0.8, behavior: reduce.matches ? 'auto' : 'smooth' } );
		} );
	} );

	// Krydset på "Fortsæt hvor du slap": gemmes uden at genindlæse, og genvejen forsvinder med det samme. Fokus går til
	// den næste genvej. Uden JavaScript sender formularen bare og kommer tilbage til kontrolpanelet.
	nav.querySelectorAll( 'form[data-wd-skjul]' ).forEach( ( form ) => {
		form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			const item = form.closest( '.wd-genvej' );
			const data = new FormData( form );
			data.append( 'wd_svar', 'json' );
			fetch( form.getAttribute( 'action' ), { method: 'POST', body: data, credentials: 'same-origin' } )
				.then( ( r ) => ( r.ok ? r.json() : null ) )
				.then( ( json ) => {
					if ( ! json || ! json.success ) {
						form.submit();
						return;
					}
					const following = item.nextElementSibling;
					item.remove();
					if ( following ) {
						following.querySelector( 'a' ).focus();
					}
					update();
				} )
				.catch( () => form.submit() );
		} );
	} );

	list.addEventListener( 'scroll', update, { passive: true } );
	if ( 'ResizeObserver' in window ) {
		new ResizeObserver( update ).observe( list );
	} else {
		window.addEventListener( 'resize', update );
	}
	update();
} )();
