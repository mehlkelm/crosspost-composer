( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useEffect = wp.element.useEffect;
	var useMemo = wp.element.useMemo;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;
	var __ = wp.i18n.__;
	var Button = wp.components.Button;
	var TextareaControl = wp.components.TextareaControl;
	var Notice = wp.components.Notice;
	// WordPress 6.6 moved the panel to wp.editor; keep the old location for 6.5.
	var PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;
	var settings = window.crosspostComposerSettings || {};
	var limit = settings.limit || 280;

	function characters( text ) {
		if ( window.Intl && Intl.Segmenter ) {
			return Array.from( new Intl.Segmenter( undefined, { granularity: 'grapheme' } ).segment( text ), function ( part ) {
				return part.segment;
			} );
		}
		return Array.from( text );
	}

	function characterCount( text ) {
		return characters( text ).length;
	}

	function truncate( text, maximum ) {
		var parts = characters( text );
		if ( parts.length <= maximum ) {
			return text;
		}
		if ( maximum <= 1 ) {
			return maximum ? '…' : '';
		}
		return parts.slice( 0, maximum - 1 ).join( '' ).trimEnd() + '…';
	}

	function plainText( value ) {
		if ( ! value ) {
			return '';
		}

		var html = String( value )
			.replace( /<\s*br\s*\/?\s*>/gi, '\n' )
			.replace( /<\/(p|div|h[1-6]|li|blockquote|pre|figure|table|ul|ol)>/gi, '\n' );
		var document = new DOMParser().parseFromString( html, 'text/html' );
		document.querySelectorAll( 'script, style' ).forEach( function ( node ) {
			node.remove();
		} );

		return ( document.body.textContent || '' )
			.replace( /\u00a0/g, ' ' )
			.replace( /[ \t]+\n/g, '\n' )
			.replace( /\n[ \t]+/g, '\n' )
			.replace( /[ \t]{2,}/g, ' ' )
			.replace( /\n{3,}/g, '\n\n' )
			.trim();
	}

	function textWithUrl( value, url ) {
		var separator = value && url ? '\n\n' : '';
		var reserved = characterCount( separator + url );

		if ( value && reserved < limit ) {
			value = truncate( value, limit - reserved );
		}

		return value + separator + url;
	}

	function containsMedia( content, featuredMedia ) {
		if ( Number( featuredMedia ) > 0 ) {
			return true;
		}

		return /<(img|video|audio|iframe|object|embed)\b/i.test( content ) ||
			/<!--\s+wp:(image|gallery|video|audio|cover|media-text|file|embed)\b/i.test( content ) ||
			/\[(gallery|video|audio|playlist)\b/i.test( content );
	}

	function initialText( content, title, url, featuredMedia ) {
		var postTitle = plainText( title );
		var postContent = plainText( content );
		var completePost = [ postTitle, postContent ].filter( Boolean ).join( '\n\n' );

		if ( ! containsMedia( content, featuredMedia ) && completePost && characterCount( completePost ) < limit ) {
			return completePost;
		}

		return textWithUrl( postTitle || postContent, url );
	}

	function intentUrl( service, text, postUrl ) {
		var encoded = encodeURIComponent( text );
		switch ( service ) {
			case 'x':
				return 'https://x.com/intent/post?text=' + encoded;
			case 'bluesky':
				return 'https://bsky.app/intent/compose?text=' + encoded;
			case 'threads':
				return 'https://www.threads.net/intent/post?text=' + encoded;
			case 'mastodon':
				if ( ! settings.mastodonInstance ) {
					return '';
				}
				return settings.mastodonInstance.replace( /\/$/, '' ) + '/share?text=' + encoded;
			case 'linkedin':
				return 'https://www.linkedin.com/feed/?shareActive=true&text=' + encoded;
			case 'facebook':
				// Facebook's sharer only prefills a link; the text travels as an optional quote.
				if ( ! postUrl ) {
					return '';
				}
				return 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent( postUrl ) + '&quote=' + encoded;
			default:
				return '';
		}
	}

	function openComposer( service, text, postUrl ) {
		var url = intentUrl( service, text, postUrl );
		if ( url ) {
			window.open( url, '_blank', 'noopener,noreferrer,width=720,height=720' );
		}
	}

	function Composer() {
		var post = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			return {
				content: editor.getEditedPostContent() || '',
				title: editor.getEditedPostAttribute( 'title' ) || '',
				url: editor.getPermalink() || '',
				status: editor.getEditedPostAttribute( 'status' ) || '',
				featuredMedia: editor.getEditedPostAttribute( 'featured_media' ) || 0
			};
		}, [] );
		var generated = useMemo( function () {
			return initialText( post.content, post.title, post.url, post.featuredMedia );
		}, [ post.content, post.title, post.url, post.featuredMedia ] );
		var state = useState( generated );
		var text = state[0];
		var setText = state[1];
		var dirtyState = useState( false );
		var isEdited = dirtyState[0];
		var setIsEdited = dirtyState[1];

		useEffect( function () {
			if ( ! isEdited ) {
				setText( generated );
			}
		}, [ generated, isEdited ] );

		var count = characterCount( text );
		var tooLong = count > limit;
		var isPublished = post.status === 'publish';
		var disabled = ! text || tooLong || ! isPublished;
		var services = [
			{ id: 'mastodon', label: 'Mastodon', enabled: settings.mastodon },
			{ id: 'bluesky', label: 'Bluesky', enabled: settings.bluesky },
			{ id: 'threads', label: 'Threads', enabled: settings.threads },
			{ id: 'x', label: 'X', enabled: settings.x },
			{ id: 'linkedin', label: 'LinkedIn', enabled: settings.linkedin },
			{ id: 'facebook', label: 'Facebook', enabled: settings.facebook }
		].filter( function ( service ) {
			return service.enabled;
		} );

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'crosspost-composer',
				title: __( 'Crosspost', 'crosspost-composer' ),
				className: 'crosspost-composer'
			},
			el( TextareaControl, {
				label: __( 'Post text', 'crosspost-composer' ),
				value: text,
				rows: 7,
				onChange: function ( value ) {
					setText( value );
					setIsEdited( true );
				},
				help: el(
					'span',
					{ className: tooLong ? 'crosspost-composer__count is-over-limit' : 'crosspost-composer__count' },
					count + ' / ' + limit
				)
			} ),
			isEdited && el( Button, {
				variant: 'link',
				className: 'crosspost-composer__reset',
				onClick: function () {
					setText( generated );
					setIsEdited( false );
				}
			}, __( 'Reset to suggested text', 'crosspost-composer' ) ),
			tooLong && el( Notice, { status: 'warning', isDismissible: false },
				__( 'Shorten the text before opening a composer.', 'crosspost-composer' )
			),
			! isPublished && el( Notice, { status: 'info', isDismissible: false },
				__( 'Publish the post to enable crossposting.', 'crosspost-composer' )
			),
			settings.mastodon && ! settings.mastodonInstance && el( Notice, { status: 'warning', isDismissible: false },
				__( 'Add a Mastodon instance under Settings → Crosspost.', 'crosspost-composer' )
			),
			services.length === 0 && el( Notice, { status: 'info', isDismissible: false },
				__( 'No services are enabled.', 'crosspost-composer' )
			),
			services.length > 0 && el( 'p', { className: 'crosspost-composer__button-label' },
				__( 'Open composer for:', 'crosspost-composer' )
			),
			el( 'div', { className: 'crosspost-composer__buttons' },
				services.map( function ( service ) {
					var unavailable = service.id === 'mastodon' && ! settings.mastodonInstance;
					return el( Button, {
						key: service.id,
						variant: 'secondary',
						disabled: disabled || unavailable,
						onClick: function () { openComposer( service.id, text, post.url ); }
					}, service.label );
				} )
			)
		);
	}

	wp.plugins.registerPlugin( 'crosspost-composer', { render: Composer } );
}( window.wp ) );
