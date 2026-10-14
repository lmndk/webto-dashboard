/**
 * Webto Dashboard - blokke i editoren (Webto -> Synlighed, Lasse 2026-10-14).
 *
 * 1. Skjuler blokke for brugeren: en skjult blok forsvinder kun fra listen over blokke, man kan indsætte (supports.inserter
 *    = false). Den er stadig tilladt, så blokke, der allerede står på en side, virker og kan rettes, og blokgrupper med
 *    blokken kan stadig sættes ind. Listen (window.webtoDashboardBlokke.skjul) kommer fra PHP og passer til brugeren.
 * 2. Varianter (fx Indlejr -> YouTube, Vimeo, X) har nøglen "blok/variant", fx core/embed/youtube. En skjult variant
 *    mister kun pladsen i blokvælgeren (scope "inserter"); en indlejring, der allerede står på en side, virker.
 * 3. Husker sitets blokke og varianter for Webto (også dem, der kun findes i JavaScript), så Synlighed kan vise dem.
 *
 * Filteret skal være på plads, før blokkene registreres: scriptet indlæses tidligt og afhænger kun af wp-hooks.
 */
( () => {
	const data = window.webtoDashboardBlokke || {};
	const hide = new Set( data.skjul || [] );
	const seen = {};
	const inInserter = ( v ) => ! v.scope || v.scope.includes( 'inserter' );
	const withoutInserter = ( v ) => ( { ...v, scope: ( v.scope || [ 'block', 'inserter' ] ).filter( ( s ) => 'inserter' !== s ) } );

	function remember( name, variation ) {
		if ( seen[ name ] && inInserter( variation ) && variation.name ) {
			seen[ name ].varianter[ variation.name ] = String( variation.title || variation.name );
		}
	}

	window.wp.hooks.addFilter( 'blocks.registerBlockType', 'webto-dashboard/synlighed', ( settings, name ) => {
		const supports = settings.supports || {};
		// Under-blokke (fx formularfelter, tabelrækker) styres gennem deres forælder og kommer ikke på listen.
		const child = ( settings.parent && settings.parent.length ) || ( settings.ancestor && settings.ancestor.length );
		if ( false !== supports.inserter && ! child ) {
			seen[ name ] = { titel: String( settings.title || name ), kategori: String( settings.category || '' ), varianter: {} };
			( settings.variations || [] ).forEach( ( v ) => remember( name, v ) );
		}
		let out = settings;
		if ( Array.isArray( settings.variations ) && settings.variations.some( ( v ) => hide.has( name + '/' + v.name ) ) ) {
			out = { ...out, variations: settings.variations.map( ( v ) => ( hide.has( name + '/' + v.name ) ? withoutInserter( v ) : v ) ) };
		}
		if ( hide.has( name ) ) {
			out = { ...out, supports: { ...supports, inserter: false } };
		}
		return out;
	} );

	window.addEventListener( 'load', () => {
		const blocks = window.wp.blocks;
		if ( ! blocks ) {
			return;
		}
		// Varianter, som plugins melder efter blokken (registerBlockVariation): husk dem, og skjul dem, der skal skjules.
		Object.keys( seen ).forEach( ( name ) => {
			( blocks.getBlockVariations( name ) || [] ).forEach( ( v ) => {
				if ( ! ( v.name in seen[ name ].varianter ) && inInserter( v ) ) {
					remember( name, v );
					if ( hide.has( name + '/' + v.name ) ) {
						blocks.unregisterBlockVariation( name, v.name );
						blocks.registerBlockVariation( name, withoutInserter( v ) );
					}
				}
			} );
		} );
		if ( ! data.husk || ! data.rest ) {
			return;
		}
		// Når editoren er klar: send listen, hvis den er anderledes end den, siden kender.
		window.setTimeout( () => {
			const keys = [];
			Object.keys( seen ).forEach( ( name ) => {
				keys.push( name );
				Object.keys( seen[ name ].varianter ).forEach( ( v ) => keys.push( name + '/' + v ) );
			} );
			const signature = keys.sort().join( ',' );
			if ( ! keys.length || signature === data.kendt ) {
				return;
			}
			window.fetch( data.rest, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': data.nonce },
				body: JSON.stringify( { blokke: seen } ),
			} ).catch( () => {} );
		}, 1500 );
	} );
} )();
