( function () {
	'use strict';

	var form = document.getElementById( 'idp-events-filter' );
	var results = document.getElementById( 'idp-events-results' );
	var moreBtn = document.getElementById( 'idp-events-more' );

	if ( ! form || ! results || ! moreBtn || typeof idpEvents === 'undefined' ) {
		return;
	}

	function request( page ) {
		var body = new URLSearchParams( new FormData( form ) );
		body.append( 'action', 'idp_filter_events' );
		body.append( 'nonce', idpEvents.nonce );
		body.append( 'page', page );

		return fetch( idpEvents.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( res ) {
			return res.json();
		} );
	}

	function render( data, append ) {
		if ( ! data || ! data.success ) {
			return;
		}
		if ( append ) {
			results.insertAdjacentHTML( 'beforeend', data.data.html );
		} else {
			results.innerHTML = data.data.html;
		}
		moreBtn.dataset.max = data.data.max_pages;
	}

	function updateMore() {
		moreBtn.hidden = parseInt( moreBtn.dataset.page, 10 ) >= parseInt( moreBtn.dataset.max, 10 );
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		results.setAttribute( 'aria-busy', 'true' );
		request( 1 ).then( function ( data ) {
			moreBtn.dataset.page = 1;
			render( data, false );
			updateMore();
		} ).finally( function () {
			results.removeAttribute( 'aria-busy' );
		} );
	} );

	moreBtn.addEventListener( 'click', function () {
		var next = parseInt( moreBtn.dataset.page, 10 ) + 1;
		request( next ).then( function ( data ) {
			moreBtn.dataset.page = next;
			render( data, true );
			updateMore();
		} );
	} );
}() );
