/*
 * Editor sidebar: the findings for the post you are editing, right where you are editing it.
 *
 * Written against the WordPress globals (wp.plugins, wp.editPost, wp.element, wp.apiFetch) so
 * there is no build step and nothing to install. Plain and small on purpose.
 */
( function ( plugins, editPost, element, components, apiFetch, i18n ) {
	'use strict';

	if ( ! plugins || ! editPost || ! element ) {
		return;
	}

	var el = element.createElement;
	var useState = element.useState;
	var useEffect = element.useEffect;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var sprintf = i18n.sprintf;

	function severityColour( severity ) {
		return 'error' === severity ? '#b32d2e' : '#996800';
	}

	/*
	 * Find the block a finding came from, so the list can take you to it.
	 *
	 * A finding quotes markup from the page as a browser received it, which can come from the theme,
	 * a plugin or a widget rather than from this post's own blocks. So this looks for the block and
	 * says plainly when there is not one, instead of pretending to jump somewhere.
	 */
	function condense( value ) {
		return String( value || '' ).replace( /<[^>]*>/g, ' ' ).replace( /\s+/g, ' ' ).trim();
	}

	function searchTokens( snippet ) {
		var tokens = [];
		// The words inside the tags, with whitespace flattened: the browser and the block editor
		// disagree about spaces between tags, so compare the words rather than the exact markup.
		var text = condense( snippet );
		if ( text.length >= 4 ) {
			tokens.push( text );
		}
		var m;
		var re = /(?:src|href|id|name|alt|title)\s*=\s*"([^"]{3,})"/g;
		while ( ( m = re.exec( String( snippet || '' ) ) ) ) {
			tokens.push( m[ 1 ] );
		}
		return tokens;
	}

	function findBlockFor( snippet ) {
		var tokens = searchTokens( snippet );
		if ( ! tokens.length || ! window.wp || ! window.wp.data ) {
			return null;
		}
		var blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
		var hit = null;

		function consider( list ) {
			for ( var i = 0; i < list.length && ! hit; i++ ) {
				var block = list[ i ];
				var haystack = block.originalContent ? block.originalContent + ' ' : '';
				if ( block.attributes ) {
					for ( var key in block.attributes ) {
						var value = block.attributes[ key ];
						if ( typeof value === 'string' ) {
							haystack += value + ' ';
						} else if ( value && typeof value === 'object' ) {
							try { haystack += JSON.stringify( value ) + ' '; } catch ( e ) {}
						}
					}
				}
				var condensed = condense( haystack );
				for ( var t = 0; t < tokens.length; t++ ) {
					if ( haystack.indexOf( tokens[ t ] ) !== -1 || condensed.indexOf( tokens[ t ] ) !== -1 ) {
						hit = block;
						return;
					}
				}
				if ( block.innerBlocks && block.innerBlocks.length ) {
					consider( block.innerBlocks );
				}
			}
		}

		consider( blocks );
		return hit;
	}

	function Finding( props ) {
		var f = props.finding;
		var open = useState( false );
		var note = useState( '' );

		function takeMeToIt() {
			var block = findBlockFor( f.snippet );
			if ( ! block ) {
				note[ 1 ]( __( 'This one is not in the blocks of this post — it can come from the theme, a plugin or a widget. Search for the markup above.', 'markproof' ) );
				return;
			}
			window.wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
			window.setTimeout( function () {
				var node = document.querySelector( '[data-block="' + block.clientId + '"]' );
				if ( node && node.scrollIntoView ) {
					node.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				}
			}, 150 );
			note[ 1 ]( __( 'Selected the block this came from.', 'markproof' ) );
		}
		return el(
			'li',
			{ style: { marginBottom: '0.75rem', paddingBottom: '0.75rem', borderBottom: '1px solid #eee' } },
			el(
				'div',
				null,
				el( 'span', { style: { color: severityColour( f.severity ), fontWeight: 600, marginRight: '0.35rem' } }, f.severity ),
				el( 'strong', null, f.title ),
				f.occurrences > 1 ? el( 'em', null, ' ×' + f.occurrences ) : null
			),
			el( 'div', { style: { color: '#50575e' } }, f.message ),
			el(
				'div',
				{ style: { marginTop: '0.35rem' } },
				el(
					components.Button,
					{ variant: 'link', onClick: function () { open[1]( ! open[0] ); }, style: { padding: 0 } },
					open[0] ? __( 'Hide the markup', 'markproof' ) : __( 'Show the markup and how to fix it', 'markproof' )
				),
				' · ',
				el(
					components.Button,
					{ variant: 'link', onClick: takeMeToIt, style: { padding: 0 } },
					__( 'Take me to it', 'markproof' )
				)
			),
			note[ 0 ] ? el( 'p', { style: { margin: '0.35rem 0 0', color: '#0b6b4f' } }, note[ 0 ] ) : null,
			open[0]
				? el(
						Fragment,
						null,
						el( 'code', { style: { display: 'block', whiteSpace: 'pre-wrap', wordBreak: 'break-word', background: '#f6f7f7', padding: '0.4rem', marginTop: '0.35rem' } }, f.snippet ),
						el( 'p', { style: { margin: '0.4rem 0 0', color: '#0b6b4f' } }, f.fix )
				  )
				: null
		);
	}

	function LiveCheck() {
		var state = useState( { loading: false, data: null, error: null } );
		var st = state[0];
		var setSt = state[1];

		function run() {
			setSt( { loading: true, data: null, error: null } );
			var content = wp.data.select( 'core/editor' ).getEditedPostContent();
			var title = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' );
			apiFetch( { path: '/markproof/v1/check', method: 'POST', data: { content: content, title: title } } )
				.then( function ( data ) { setSt( { loading: false, data: data, error: null } ); } )
				.catch( function ( e ) { setSt( { loading: false, data: null, error: e.message } ); } );
		}

		var body = null;
		if ( st.loading ) {
			body = el( components.Spinner, null );
		} else if ( st.error ) {
			body = el( 'p', null, st.error );
		} else if ( st.data ) {
			if ( ! st.data.findings.length ) {
				body = el( 'p', { style: { color: '#0b6b4f' } }, __( 'No problems found in this draft. Remember what this cannot see: contrast, rendered behaviour, and screen reader experience.', 'markproof' ) );
			} else {
				body = el(
					Fragment,
					null,
					el( 'p', null, sprintf( /* translators: %d: number of findings */ __( '%d finding(s) in this draft:', 'markproof' ), st.data.findings.length ) ),
					el( 'ul', { style: { listStyle: 'none', margin: 0, padding: 0 } }, st.data.findings.map( function ( f, i ) {
						return el( Finding, { key: i, finding: f } );
					} ) ),
					el( 'p', { className: 'description', style: { color: '#50575e', fontSize: '0.85em' } }, st.data.scope )
				);
			}
		}

		return el(
			'div',
			{ style: { padding: '0 1rem 1rem', borderTop: '1px solid #e0e0e0' } },
			el( 'h3', { style: { margin: '1rem 0 0.4rem', fontSize: '0.95rem' } }, __( 'Before you publish', 'markproof' ) ),
			el(
				'p',
				{ style: { margin: '0 0 0.6rem', color: '#50575e' } },
				__( 'Check what you have written so far, without saving or publishing it.', 'markproof' )
			),
			el( components.Button, { variant: 'secondary', onClick: run, disabled: st.loading }, __( 'Check this draft', 'markproof' ) ),
			body
		);
	}

	function Panel() {
		var postId = wp.data.useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostId();
		}, [] );
		var state = useState( { loading: true, data: null, error: null } );

		useEffect(
			function () {
				var cancelled = false;
				if ( ! postId ) {
					return function () {};
				}
				state[1]( { loading: true, data: null, error: null } );
				apiFetch( { path: '/markproof/v1/post/' + postId } )
					.then( function ( data ) {
						if ( ! cancelled ) {
							state[1]( { loading: false, data: data, error: null } );
						}
					} )
					.catch( function ( e ) {
						if ( ! cancelled ) {
							state[1]( { loading: false, data: null, error: e.message } );
						}
					} );
				return function () { cancelled = true; };
			},
			[ postId ]
		);

		var st = state[0];
		var body;

		if ( st.loading ) {
			body = el( components.Spinner, null );
		} else if ( st.error ) {
			body = el( 'p', null, st.error );
		} else if ( ! st.data || ! st.data.scanned ) {
			body = el( 'p', null, __( 'This post has not been scanned yet. Run a scan from the Markproof – Accessibility Checks screen.', 'markproof' ) );
		} else if ( ! st.data.findings.length ) {
			body = el( 'p', null, __( 'Nothing found on this page. Note that the scan cannot see colour contrast or how the page sounds in a screen reader.', 'markproof' ) );
		} else {
			body = el(
				Fragment,
				null,
				el(
					'p',
					null,
					sprintf(
						/* translators: 1: number of findings, 2: date */
						__( '%1$d findings on the last scan (%2$s).', 'markproof' ),
						st.data.findings.length,
						st.data.scan_date
					)
				),
				el( 'ul', { style: { listStyle: 'none', margin: 0, padding: 0 } }, st.data.findings.map( function ( f, i ) {
					return el( Finding, { key: i, finding: f } );
				} ) ),
				el( 'p', null, el( 'a', { href: st.data.report }, __( 'Open the full findings list', 'markproof' ) ) )
			);
		}

		return el(
			Fragment,
			null,
			el(
				editPost.PluginSidebarMoreMenuItem,
				{ target: 'markproof-sidebar', icon: 'universal-access-alt' },
				__( 'Accessibility', 'markproof' )
			),
			el(
				editPost.PluginSidebar,
				{ name: 'markproof-sidebar', title: __( 'Accessibility', 'markproof' ), icon: 'universal-access-alt' },
				el(
					'div',
					{ style: { padding: '1rem' } },
					el( 'p', { style: { color: '#50575e' } }, __( 'What the last scan found on this page. Fixes are yours to make; nothing is changed automatically.', 'markproof' ) ),
					body,
					el( LiveCheck, null )
				)
			)
		);
	}

	plugins.registerPlugin( 'markproof', {
		render: Panel,
		icon: 'universal-access-alt',
	} );
} )(
	window.wp && window.wp.plugins,
	window.wp && window.wp.editPost,
	window.wp && window.wp.element,
	window.wp && window.wp.components,
	window.wp && window.wp.apiFetch,
	window.wp && window.wp.i18n
);
