/* Markproof – Accessibility Checks admin: start a scan, then poll batches until it finishes. */
( function () {
	const button = document.getElementById( 'aa-run' );
	if ( ! button || typeof AccessibilityScanner === 'undefined' ) {
		return;
	}
	const out = document.getElementById( 'aa-progress' );
	const testButton = document.getElementById( 'aa-test' );

	function show( html ) {
		out.innerHTML = html;
	}

	async function post( action, body ) {
		const data = new URLSearchParams( Object.assign( { action: action, nonce: AccessibilityScanner.nonce }, body || {} ) );
		const res = await fetch( AccessibilityScanner.ajax, { method: 'POST', credentials: 'same-origin', body: data } );
		return res.json();
	}

	button.addEventListener( 'click', async function () {
		button.disabled = true;
		show( AccessibilityScanner.i18n.starting );
		try {
			const start = await post( 'markproof_start' );
			if ( ! start.success ) {
				throw new Error( ( start.data && start.data.message ) || 'could not start' );
			}
			const scan = start.data.scan_id;
			const total = start.data.total;
			let done = 0;
			for (;;) {
				const batch = await post( 'markproof_batch', { scan: scan } );
				if ( ! batch.success ) {
					throw new Error( 'batch failed' );
				}
				done = batch.data.done;
				const pct = total ? Math.round( ( done / total ) * 100 ) : 0;
				show( '<span class="aa-bar"><span style="width:' + pct + '%"></span></span>' + AccessibilityScanner.i18n.working + ' ' + done + '/' + total );
				if ( batch.data.finished ) {
					break;
				}
			}
			show( AccessibilityScanner.i18n.done + ' ' + done + '/' + total );
			window.setTimeout( function () {
				window.location.reload();
			}, 1200 );
		} catch ( e ) {
			show( AccessibilityScanner.i18n.failed + ' ' + e.message );
			button.disabled = false;
		}
	} );
	testButton.addEventListener( 'click', async function () {
		show( 'Testing…' );
		try {
			const res = await post( 'markproof_test' );
			if ( ! res.success ) {
				throw new Error( 'request failed' );
			}
			const d = res.data;
			show( d.ok
				? 'The server can read your own pages (' + d.detail + ', ' + d.ms + ' ms).'
				: 'The server could NOT read ' + 'its own front page: ' + d.detail + '. Scans will fall back to stored content.' );
		} catch ( e ) {
			show( 'Test failed: ' + e.message );
		}
	} );
} )();
