<?php
/**
 * The checks. Each one is a real, verifiable condition found in the HTML —
 * no guessing, no scoring theatre, and no claim that a site "is accessible".
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markproof_Rules {

	/** Human-facing description of every check. */
	public static function metadata() {
		return array(
			'img_missing_alt'        => array(
				'severity' => 'error',
				'title'    => __( 'Image without alt text', 'markproof' ),
				'why'      => __( 'Screen readers announce the file name or nothing at all, so the image carries no information for a blind visitor.', 'markproof' ),
				'fix'      => __( 'Add an alt attribute describing the image in context. If the image is purely decorative, use an empty alt (alt="") so it is skipped on purpose.', 'markproof' ),
			),
			'link_empty_name'        => array(
				'severity' => 'error',
				'title'    => __( 'Link with no accessible name', 'markproof' ),
				'why'      => __( 'A link that contains only an icon or empty markup is announced as "link" with nothing else, so nobody using a screen reader knows where it goes.', 'markproof' ),
				'fix'      => __( 'Give the link text, or an aria-label, or a nested image with meaningful alt text.', 'markproof' ),
			),
			'button_empty_name'      => array(
				'severity' => 'error',
				'title'    => __( 'Button with no accessible name', 'markproof' ),
				'why'      => __( 'A button with no label cannot be used with a screen reader or by voice control.', 'markproof' ),
				'fix'      => __( 'Add visible text or an aria-label to the button.', 'markproof' ),
			),
			'input_missing_label'    => array(
				'severity' => 'error',
				'title'    => __( 'Form field without a label', 'markproof' ),
				'why'      => __( 'The field is announced without saying what to type, which makes forms impossible to complete reliably without sight.', 'markproof' ),
				'fix'      => __( 'Connect a <label for="..."> to the field id, or wrap the field in a label, or add aria-label.', 'markproof' ),
			),
			'heading_order'          => array(
				'severity' => 'warning',
				'title'    => __( 'Heading levels skip', 'markproof' ),
				'why'      => __( 'Screen reader users navigate by headings; jumping from h1 to h4 breaks the outline of the page.', 'markproof' ),
				'fix'      => __( 'Use headings in order (h1, h2, h3...). If you need smaller text, use CSS, not a lower heading level.', 'markproof' ),
			),
			'heading_missing_h1'     => array(
				'severity' => 'warning',
				'title'    => __( 'Headings start below h1', 'markproof' ),
				'why'      => __( 'This page has headings but no h1, so the outline starts halfway down: a visitor using a screen reader hears a section of a document whose main heading is missing. Reported only when other headings are present - a page with no headings at all may be a list or a gallery, and telling you to add an h1 to something that is not a document would be advice, not a finding.', 'markproof' ),
				'fix'      => __( 'Make the first heading on the page an h1 that describes its content, or, if the page genuinely has no document structure, remove the headings you have rather than inventing an h1 to satisfy a checker.', 'markproof' ),
			),
			'heading_empty'          => array(
				'severity' => 'warning',
				'title'    => __( 'Empty heading', 'markproof' ),
				'why'      => __( 'An empty heading is announced as a heading with no text, which is confusing noise.', 'markproof' ),
				'fix'      => __( 'Remove it or put real text inside.', 'markproof' ),
			),
			'html_missing_lang'      => array(
				'severity' => 'error',
				'title'    => __( 'Page language is not declared', 'markproof' ),
				'why'      => __( 'Without <html lang="..."> a screen reader may read the page in the wrong language, which sounds like nonsense.', 'markproof' ),
				'fix'      => __( 'Add a lang attribute to the html element, for example lang="en".', 'markproof' ),
			),
			'table_missing_header'   => array(
				'severity' => 'warning',
				'title'    => __( 'Table without header cells', 'markproof' ),
				'why'      => __( 'Without th cells, a screen reader cannot say which column or row a number belongs to.', 'markproof' ),
				'fix'      => __( 'If this is a data table, mark its header cells with <th scope="col"> or scope="row". If it is a layout table, say so with role="presentation" and leave it alone: that is a legitimate use and we cannot tell the two apart from the outside.', 'markproof' ),
			),
			'iframe_missing_title'   => array(
				'severity' => 'error',
				'title'    => __( 'Embedded frame without a title', 'markproof' ),
				'why'      => __( 'Frames are announced by their title; without one the visitor hears "frame" and cannot tell what is embedded.', 'markproof' ),
				'fix'      => __( 'Add a title attribute describing the embedded content.', 'markproof' ),
			),
			'positive_tabindex'      => array(
				'severity' => 'error',
				'title'    => __( 'Positive tabindex', 'markproof' ),
				'why'      => __( 'A positive tabindex reorders the whole page for keyboard users and usually makes some controls unreachable.', 'markproof' ),
				'fix'      => __( 'Use tabindex="0" or nothing at all, and put elements in the right order in the markup instead.', 'markproof' ),
			),
			'generic_link_text'      => array(
				'severity' => 'warning',
				'title'    => __( 'Link text does not say where it goes', 'markproof' ),
				'why'      => __( 'Screen reader users often browse a list of links out of context, where "click here" means nothing.', 'markproof' ),
				'fix'      => __( 'Describe the destination, for example "read the pricing guide" instead of "click here".', 'markproof' ),
			),
			'missing_page_title'     => array(
				'severity' => 'error',
				'title'    => __( 'Page has no title', 'markproof' ),
				'why'      => __( 'The document title is the first thing announced and the label of the browser tab.', 'markproof' ),
				'fix'      => __( 'Give the page a short, unique title.', 'markproof' ),
			),
			'duplicate_ids'          => array(
				'severity' => 'warning',
				'title'    => __( 'Duplicate id attributes', 'markproof' ),
				'why'      => __( 'An id must be unique. When it is not, labels and anchors can point at the wrong element.', 'markproof' ),
				'fix'      => __( 'Make every id unique on the page.', 'markproof' ),
			),
			'zoom_blocked'           => array(
				'severity' => 'warning',
				'title'    => __( 'Zoom is blocked', 'markproof' ),
				'why'      => __( 'Blocking pinch zoom or limiting maximum-scale makes the page unusable for people with low vision.', 'markproof' ),
				'fix'      => __( 'Remove user-scalable=no and maximum-scale from the viewport meta tag.', 'markproof' ),
			),
			'aria_hidden_focusable'  => array(
				'severity' => 'error',
				'title'    => __( 'Focusable element hidden from screen readers', 'markproof' ),
				'why'      => __( 'An element that can receive keyboard focus but is aria-hidden creates a focus stop that announces nothing.', 'markproof' ),
				'fix'      => __( 'Remove aria-hidden from focusable elements, or remove them from the tab order properly.', 'markproof' ),
			),
			'aria_reference_missing' => array(
				'severity' => 'error',
				'title'    => __( 'Label points at an id that does not exist', 'markproof' ),
				'why'      => __( 'aria-labelledby and label for point at an id. If that id is missing, the control has no name at all.', 'markproof' ),
				'fix'      => __( 'Fix the spelling of the id, or add the missing element.', 'markproof' ),
			),
			'link_new_window'        => array(
				'severity' => 'warning',
				'title'    => __( 'Link opens a new window without warning', 'markproof' ),
				'why'      => __( 'A new tab is disorienting for people who cannot see that it happened, and the back button no longer behaves as expected.', 'markproof' ),
				'fix'      => __( 'Say so in the link text ("opens in a new tab") or remove target="_blank".', 'markproof' ),
			),
			'autoplay_media'         => array(
				'severity' => 'warning',
				'title'    => __( 'Media plays automatically', 'markproof' ),
				'why'      => __( 'Sound that starts on its own is one of the most disruptive failures, and it fights with a screen reader.', 'markproof' ),
				'fix'      => __( 'Remove autoplay, or add muted so nothing is heard until the visitor asks for it.', 'markproof' ),
			),
			'scanned_from_stored_content' => array(
				'severity' => 'warning',
				'title'    => __( 'Checked from stored content, not the live page', 'markproof' ),
				'why'      => __( 'The server could not reach the page over HTTP, which is common on locked-down hosts. The findings for this page come from the saved content instead, so they may not match the live page.', 'markproof' ),
				'fix'      => __( 'Nothing to fix on the page itself. If this appears on every page, ask your host why the site cannot request its own front page, or run the scan from a host that allows loopback requests.', 'markproof' ),
			),
			'img_alt_filename'       => array(
				'severity' => 'warning',
				'title'    => __( 'Alt text looks like a file name', 'markproof' ),
				'why'      => __( 'A screen reader will read "IMG 2481 dot jay peg" aloud. The attribute exists, but it tells nobody anything.', 'markproof' ),
				'fix'      => __( 'Describe what the image shows and why it is there, or use alt="" if it is decorative.', 'markproof' ),
			),
			'img_alt_generic'        => array(
				'severity' => 'warning',
				'title'    => __( 'Alt text says nothing useful', 'markproof' ),
				'why'      => __( 'Words like "image", "photo" or "logo" are announced before the description anyway, so they add noise instead of meaning.', 'markproof' ),
				'fix'      => __( 'Describe the content, not the format. For a logo that links home, "Company name, home page" is usually right.', 'markproof' ),
				),
			'link_javascript_href'   => array(
				'severity' => 'error',
				'title'    => __( 'Link built out of JavaScript', 'markproof' ),
				'why'      => __( 'href="javascript:..." cannot be opened in a new tab, copied, or reliably activated with a keyboard, and many screen readers announce it as a link that goes nowhere.', 'markproof' ),
				'fix'      => __( 'Use a real URL, or use a <button> if it triggers an action.', 'markproof' ),
				),
			'nested_interactive'    => array(
				'severity' => 'error',
				'title'    => __( 'Interactive element inside another one', 'markproof' ),
				'why'      => __( 'A link or button inside a link or button produces an unpredictable target for keyboard and screen reader users. It is a common accident when whole cards are made clickable.', 'markproof' ),
				'fix'      => __( 'Keep one interactive element per card and remove the inner one.', 'markproof' ),
				),
			'input_image_missing_alt' => array(
				'severity' => 'error',
				'title'    => __( 'Image button has no alt text', 'markproof' ),
				'why'      => __( 'An <input type="image"> is a button. Without alt text it is announced as an unlabelled button.', 'markproof' ),
				'fix'      => __( 'Add an alt attribute describing the action, for example alt="Search".', 'markproof' ),
				),
			'page_too_large'          => array(
				'severity' => 'warning',
				'title'    => __( 'Page skipped: too much HTML to analyse safely', 'markproof' ),
				'why'      => __( "Loading a page this large into memory could exceed the host's PHP memory limit and take the site down. We chose to skip it and tell you.", 'markproof' ),
				'fix'      => __( 'If this page matters, ask your theme or page-builder author why it produces megabytes of HTML, or check that page by hand.', 'markproof' ),
			),
			'obsolete_element'       => array(
				'severity' => 'warning',
				'title'    => __( 'Obsolete scrolling element', 'markproof' ),
				'why'      => __( 'marquee and blink cannot be paused, and moving content must be controllable.', 'markproof' ),
				'fix'      => __( 'Remove it. If the movement matters, use CSS animation that respects prefers-reduced-motion.', 'markproof' ),
			),
		);
	}

	/** Run every check against one page of HTML. */
	public static function analyse( $html ) {
		$issues = array();
		if ( '' === trim( (string) $html ) ) {
			return $issues;
		}

		$dom  = new DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		$xpath = new DOMXPath( $dom );

		$add = function ( $rule, $node, $message = '' ) use ( &$issues, $dom ) {
			$meta    = self::metadata();
			$snippet = '';
			if ( $node instanceof DOMNode ) {
				$snippet = $dom->saveHTML( $node );
				$snippet = preg_replace( '/\s+/', ' ', (string) $snippet );
				$snippet = mb_substr( trim( $snippet ), 0, 200 );
			}
			$issues[] = array(
				'rule_id'  => $rule,
				'severity' => isset( $meta[ $rule ] ) ? $meta[ $rule ]['severity'] : 'warning',
				'message'  => $message ? $message : $meta[ $rule ]['title'],
				'snippet'  => $snippet,
			);
		};

		/* --- images without alt --- */
		foreach ( $xpath->query( '//img' ) as $img ) {
			if ( self::inside_template( $img ) ) {
				continue;
			}
			if ( $img->hasAttribute( 'aria-hidden' ) || 'presentation' === $img->getAttribute( 'role' ) ) {
				continue;
			}
			if ( self::is_spacer( $img ) ) {
				continue; // a zero-width or zero-height image is a spacer, not something a visitor sees
			}
			// An image is named by any of alt, aria-label, aria-labelledby or title, in that order of
			// precedence; flagging one that carries aria-label but no alt was a false positive, and the
			// W3C's ACT rule for this check counts such an image as named.
			$named = '' !== trim( (string) $img->getAttribute( 'alt' ) )
				|| '' !== trim( (string) $img->getAttribute( 'aria-label' ) )
				|| ( '' !== trim( (string) $img->getAttribute( 'aria-labelledby' ) ) && self::labelled_by_exists( $img ) )
				|| '' !== trim( (string) $img->getAttribute( 'title' ) );
			if ( self::is_invisible_or_beacon( $img ) ) {
				continue; // a tracking beacon or an image hidden by its author is not a missing description
			}
			if ( ! $named && ! self::has_hidden_ancestor( $img ) ) {
				// If the image is the only content of a link or button that has no accessible name, the
				// link finding below already says what to do about it. Two findings for one defect is
				// noise, and noise is what teaches people to stop reading our findings.
				if ( self::inside_unnamed_control( $img ) ) {
					continue;
				}
				$add( 'img_missing_alt', $img );
			}
		}

		/* --- links with no accessible name --- */
		foreach ( $xpath->query( '//a[@href]' ) as $a ) {
			if ( $a->hasAttribute( 'aria-hidden' ) || self::class_marks_hidden( $a ) || self::has_hidden_ancestor( $a ) ) {
				continue;
			}
			$name = self::accessible_name( $a );
			if ( '' === $name ) {
				$add( 'link_empty_name', $a );
				continue;
			}
			if ( preg_match( '/^\s*(click here|here|read more|more|learn more|this link|link|details)\s*[.!>»]*$/i', $name ) ) {
				$add( 'generic_link_text', $a, sprintf( /* translators: %s: link text */ __( 'Link text says only "%s"', 'markproof' ), $name ) );
			}
		}

		/* --- buttons with no accessible name --- */
		foreach ( $xpath->query( '//button' ) as $button ) {
			if ( self::has_hidden_ancestor( $button ) || self::class_marks_hidden( $button ) ) {
				continue;
			}
			if ( '' === self::accessible_name( $button ) ) {
				$add( 'button_empty_name', $button );
			}
		}
		foreach ( $xpath->query( '//input[@type="submit" or @type="button" or @type="reset"]' ) as $button ) {
			if ( '' === trim( (string) $button->getAttribute( 'value' ) ) && '' === trim( (string) $button->getAttribute( 'aria-label' ) ) ) {
				$add( 'button_empty_name', $button );
			}
		}

		/* --- form fields without a label --- */
		$field_types = array( 'text', 'email', 'password', 'search', 'tel', 'url', 'number', 'date', 'datetime-local', 'file', 'checkbox', 'radio', 'range', 'color' );
		foreach ( $xpath->query( '//input|//select|//textarea' ) as $field ) {
			$type = strtolower( (string) $field->getAttribute( 'type' ) );
			if ( 'input' === strtolower( $field->nodeName ) && ! in_array( $type, $field_types, true ) ) {
				continue;
			}
			if ( $field->hasAttribute( 'aria-label' ) || $field->hasAttribute( 'aria-labelledby' ) || $field->hasAttribute( 'title' ) ) {
				continue;
			}
			/* Fields kept out of the keyboard order, or inside an aria-hidden subtree, are not part of
			 * what a visitor can reach, so we stay quiet about them instead of raising false alarms. */
			if ( self::has_hidden_ancestor( $field ) || self::class_marks_hidden( $field ) ) {
				continue;
			}
			if ( $field->hasAttribute( 'tabindex' ) && (int) $field->getAttribute( 'tabindex' ) < 0 ) {
				continue;
			}
			$id = $field->getAttribute( 'id' );
			if ( $id && $xpath->query( '//label[@for="' . $id . '"]' )->length ) {
				continue;
			}
			$parent = $field->parentNode;
			$wrapped = false;
			while ( $parent && 'label' !== strtolower( $parent->nodeName ) ) {
				$parent = $parent->parentNode;
			}
			if ( $parent ) {
				$wrapped = true;
			}
			if ( ! $wrapped ) {
				$add( 'input_missing_label', $field, sprintf( /* translators: %s: field type */ __( 'Form field of type "%s" has no label', 'markproof' ), $type ? $type : 'input' ) );
			}
		}

		/* --- heading structure --- */
		$headings   = array();
		$has_h1     = false;
		foreach ( $xpath->query( '//h1|//h2|//h3|//h4|//h5|//h6' ) as $h ) {
			$level = (int) substr( $h->nodeName, 1 );
			if ( 1 === $level ) {
				$has_h1 = true;
			}
			if ( '' === self::accessible_name( $h ) ) {
				$add( 'heading_empty', $h );
			}
			$headings[] = array( 'level' => $level, 'node' => $h );
		}
		$prev_level = 0;
		foreach ( $headings as $heading ) {
			if ( $prev_level && $heading['level'] > $prev_level + 1 ) {
				$add( 'heading_order', $heading['node'], sprintf( /* translators: 1: previous heading level, 2: new heading level */ __( 'Heading jumps from h%1$d to h%2$d', 'markproof' ), $prev_level, $heading['level'] ) );
			}
			$prev_level = $heading['level'];
		}
		if ( $headings && ! $has_h1 ) {
			$add( 'heading_missing_h1', $headings[0]['node'] );
		}

		/* --- page language --- */
		$html_nodes = $xpath->query( '//html' );
		if ( $html_nodes->length && '' === trim( (string) $html_nodes->item( 0 )->getAttribute( 'lang' ) ) ) {
			$add( 'html_missing_lang', $html_nodes->item( 0 ) );
		}

		/* --- document title --- */
		$title_nodes = $xpath->query( '//title' );
		if ( ! $title_nodes->length || '' === trim( (string) $title_nodes->item( 0 )->textContent ) ) {
			$add( 'missing_page_title', null );
		}

		/* --- tables --- */
		foreach ( $xpath->query( '//table' ) as $table ) {
			// role="presentation" (or "none") is the author telling us this is a layout table, which has
			// no header row to have and no business being reported.
			$role = strtolower( (string) $table->getAttribute( 'role' ) );
			if ( 'presentation' === $role || 'none' === $role ) {
				continue;
			}
			if ( ! $xpath->query( './/th', $table )->length ) {
				$add( 'table_missing_header', $table );
			}
		}

		/* --- iframes --- */
		foreach ( $xpath->query( '//iframe' ) as $iframe ) {
			$named = '' !== trim( (string) $iframe->getAttribute( 'title' ) )
				|| '' !== trim( (string) $iframe->getAttribute( 'aria-label' ) )
				|| ( '' !== trim( (string) $iframe->getAttribute( 'aria-labelledby' ) ) && self::labelled_by_exists( $iframe ) );
			if ( ! $named && ! self::looks_hidden( $iframe ) && ! self::has_hidden_ancestor( $iframe ) ) {
				$add( 'iframe_missing_title', $iframe );
			}
		}

		/* --- positive tabindex --- */
		foreach ( $xpath->query( '//*[@tabindex]' ) as $node ) {
			$value = (int) $node->getAttribute( 'tabindex' );
			if ( $value > 0 ) {
				$add( 'positive_tabindex', $node, sprintf( /* translators: %d: tabindex value */ __( 'tabindex="%d" changes the focus order', 'markproof' ), $value ) );
			}
		}

		/* --- duplicate ids --- */
		$ids = array();
		foreach ( $xpath->query( '//*[@id]' ) as $node ) {
			$id = (string) $node->getAttribute( 'id' );
			if ( '' === $id ) {
				continue;
			}
			if ( isset( $ids[ $id ] ) ) {
				$add( 'duplicate_ids', $node, sprintf( /* translators: %s: id value */ __( 'id="%s" is used more than once', 'markproof' ), $id ) );
			}
			$ids[ $id ] = true;
		}

		/* --- zoom --- */
		foreach ( $xpath->query( '//meta[@name="viewport"]' ) as $meta ) {
			$content = strtolower( (string) $meta->getAttribute( 'content' ) );
			if ( preg_match( '/user-scalable\s*=\s*(no|0)/', $content ) || preg_match( '/maximum-scale\s*=\s*(1|1\.0|1\.5|2)([,\s]|$)/', $content ) ) {
				$add( 'zoom_blocked', $meta );
			}
		}

		/* --- alt text that exists but says nothing --- */
		foreach ( $xpath->query( '//img[@alt]' ) as $img ) {
			$alt = trim( (string) $img->getAttribute( 'alt' ) );
			if ( '' === $alt || self::class_marks_hidden( $img ) ) {
				continue;
			}
			if ( preg_match( '/\.(jpe?g|png|gif|webp|svg|bmp|tiff?)$/i', $alt ) || preg_match( '/\b(img|dsc|dcim|image|photo|screenshot)[-_ ]?\d{2,}\b/i', $alt ) ) {
				$add( 'img_alt_filename', $img, sprintf( /* translators: %s: alt text */ __( 'Alt text is "%s"', 'markproof' ), $alt ) );
				continue;
			}
			if ( preg_match( '/^(an? )?(image|photo|picture|graphic|icon|logo|banner|spacer|arrow|button)s?([ .!-]*(of|for)?[ .!-]*)?$/i', $alt ) ) {
				$add( 'img_alt_generic', $img, sprintf( /* translators: %s: alt text */ __( 'Alt text is just "%s"', 'markproof' ), $alt ) );
			}
		}

		/* --- links built out of JavaScript --- */
		foreach ( $xpath->query( '//a[starts-with(@href, "javascript:")] | //a[starts-with(@href, "JAVASCRIPT:")]' ) as $a ) {
			$add( 'link_javascript_href', $a );
		}

		/* --- image buttons without alt text --- */
		foreach ( $xpath->query( '//input[@type="image"]' ) as $input_image ) {
			if ( '' === trim( (string) $input_image->getAttribute( 'alt' ) ) && '' === trim( (string) $input_image->getAttribute( 'aria-label' ) ) ) {
				$add( 'input_image_missing_alt', $input_image );
			}
		}

		/* --- interactive elements nested inside other interactive elements --- */
		foreach ( $xpath->query( '//a[@href]//a[@href] | //a[@href]//button | //button//a[@href] | //button//button' ) as $nested ) {
			$add( 'nested_interactive', $nested );
		}

		/* --- aria-hidden on (or containing) focusable elements --- */
		foreach ( $xpath->query( '//*[@aria-hidden="true"]' ) as $node ) {
			$candidates = array();
			if ( self::is_focusable( $node ) ) {
				$candidates[] = $node;
			}
			foreach ( $xpath->query( './/a[@href] | .//button | .//input | .//select | .//textarea | .//*[@tabindex]', $node ) as $child ) {
				if ( self::is_focusable( $child ) ) {
					$candidates[] = $child;
				}
			}
			foreach ( $candidates as $candidate ) {
				/* A closed dialog or lightbox (PhotoSwipe, modals, "…--hidden" wrappers) is not
				 * reachable at all, so calling its buttons a problem would be noise, not help. */
				if ( self::sits_inside_hidden( $candidate ) ) {
					continue;
				}
				$add( 'aria_hidden_focusable', $candidate );
			}
		}

		/* --- aria-labelledby and label for pointing at ids that are not on the page --- */
		$all_ids = array();
		foreach ( $xpath->query( '//*[@id]' ) as $id_node ) {
			$all_ids[ (string) $id_node->getAttribute( 'id' ) ] = true;
		}
		foreach ( $xpath->query( '//*[@aria-labelledby]' ) as $node ) {
			foreach ( preg_split( '/\s+/', trim( (string) $node->getAttribute( 'aria-labelledby' ) ) ) as $ref ) {
				if ( '' !== $ref && ! isset( $all_ids[ $ref ] ) ) {
					$add( 'aria_reference_missing', $node, sprintf( /* translators: %s: id */ __( 'aria-labelledby points at id="%s", which is not on the page', 'markproof' ), $ref ) );
				}
			}
		}
		foreach ( $xpath->query( '//label[@for]' ) as $node ) {
			$for = trim( (string) $node->getAttribute( 'for' ) );
			if ( '' !== $for && ! isset( $all_ids[ $for ] ) ) {
				$add( 'aria_reference_missing', $node, sprintf( /* translators: %s: id */ __( 'label for="%s" has no matching form field', 'markproof' ), $for ) );
			}
		}

		/* --- links that open a new window without saying so --- */
		foreach ( $xpath->query( '//a[@target="_blank"]' ) as $a ) {
			$label = strtolower( (string) $a->getAttribute( 'aria-label' ) . ' ' . (string) $a->getAttribute( 'title' ) . ' ' . $a->textContent );
			if ( ! preg_match( '/new (window|tab)|opens in/', $label ) ) {
				$add( 'link_new_window', $a );
			}
		}

		/* --- media that plays by itself --- */
		foreach ( $xpath->query( '//video[@autoplay] | //audio[@autoplay]' ) as $node ) {
			if ( ! $node->hasAttribute( 'muted' ) ) {
				$add( 'autoplay_media', $node );
			}
		}

		/* --- obsolete marquee / blink --- */
		foreach ( $xpath->query( '//marquee | //blink' ) as $node ) {
			$add( 'obsolete_element', $node );
		}

		return $issues;
	}

	/** Is this element reachable with a keyboard? */
	private static function is_focusable( DOMNode $node ) {
		$name = strtolower( $node->nodeName );
		if ( 'a' === $name && ! $node->hasAttribute( 'href' ) ) {
			return false;
		}
		if ( 'input' === $name && 'hidden' === strtolower( (string) $node->getAttribute( 'type' ) ) ) {
			return false;
		}
		if ( $node->hasAttribute( 'disabled' ) ) {
			return false;
		}
		/* tabindex="-1" takes an element out of the keyboard order, so aria-hidden on it is fine. */
		if ( $node->hasAttribute( 'tabindex' ) && (int) $node->getAttribute( 'tabindex' ) < 0 ) {
			return false;
		}
		if ( in_array( $name, array( 'a', 'button', 'input', 'select', 'textarea' ), true ) ) {
			return true;
		}
		return $node->hasAttribute( 'tabindex' ) && (int) $node->getAttribute( 'tabindex' ) >= 0;
	}

	/** Markup signals that an element and its children are not shown to anyone at all. */
	private static function hidden_markup( DOMNode $node ) {
		if ( $node instanceof DOMElement && $node->hasAttribute( 'hidden' ) ) {
			return true;
		}
		$style = strtolower( (string) $node->getAttribute( 'style' ) );
		if ( false !== strpos( $style, 'display:none' ) || false !== strpos( $style, 'display: none' ) || false !== strpos( $style, 'visibility:hidden' ) ) {
			return true;
		}
		$class = strtolower( (string) $node->getAttribute( 'class' ) );
		foreach ( preg_split( '/\s+/', $class ) as $token ) {
			if ( '' === $token ) {
				continue;
			}
			if ( in_array( $token, array( 'hidden', 'hide', 'd-none', 'display-none', 'is-hidden' ), true ) || substr( $token, -8 ) === '--hidden' ) {
				return true;
			}
		}
		return false;
	}

	/** Is any ancestor of this element hidden by markup we can see? */
	private static function sits_inside_hidden( DOMNode $node ) {
		$parent = $node->parentNode;
		while ( $parent && $parent instanceof DOMElement ) {
			if ( self::hidden_markup( $parent ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	/** Is this element inside something the author marked aria-hidden? */

	/**
	 * Is this image a beacon or something the author hid on purpose?
	 *
	 * One-by-one pixels from Facebook, Google and ad networks are not pictures anyone is meant to see; they cannot
	 * carry useful alt text, and reporting them buries the findings that matter. Real reports taught us this: the
	 * first paid audit we ran on a large marketing site listed a Facebook tracking pixel as its top finding on
	 * every page.
	 */
	private static function is_invisible_or_beacon( DOMElement $img ) {
		$w = rtrim( strtolower( trim( (string) $img->getAttribute( 'width' ) ) ), 'px' );
		$h = rtrim( strtolower( trim( (string) $img->getAttribute( 'height' ) ) ), 'px' );
		if ( is_numeric( $w ) && is_numeric( $h ) && (float) $w <= 2 && (float) $h <= 2 ) {
			return true;
		}
		$style = strtolower( (string) $img->getAttribute( 'style' ) );
		if ( preg_match( '/display\s*:\s*none|visibility\s*:\s*hidden/', $style ) ) {
			return true;
		}
		$host = strtolower( (string) parse_url( (string) $img->getAttribute( 'src' ), PHP_URL_HOST ) );
		if ( '' === $host ) {
			return false;
		}
		foreach ( array( 'facebook.com', 'facebook.net', 'google-analytics.com', 'googletagmanager.com',
			'doubleclick.net', 'analytics.tiktok.com', 'bat.bing.com', 'ads.linkedin.com', 'sc-static.net',
			'hotjar.com', 'clarity.ms', 'matomo', 'piwik', 'segment.io', 'mixpanel.com' ) as $needle ) {
			if ( false !== strpos( $host, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	private static function has_hidden_ancestor( DOMNode $node ) {
		$parent = $node->parentNode;
		while ( $parent && $parent instanceof DOMElement ) {
			if ( 'true' === strtolower( (string) $parent->getAttribute( 'aria-hidden' ) ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	/** Is this image a layout spacer rather than something a visitor is meant to see? */
	private static function is_spacer( DOMNode $node ) {
		$w = strtolower( trim( (string) $node->getAttribute( 'width' ) ) );
		$h = strtolower( trim( (string) $node->getAttribute( 'height' ) ) );
		foreach ( array( $w, $h ) as $side ) {
			if ( '0' === $side || '0px' === $side || '0%' === $side ) {
				return true;
			}
		}
		return false;
	}

	/** Is this node the only content of a link or button that has no accessible name? */
	private static function inside_unnamed_control( DOMNode $node ) {
		$parent = $node->parentNode;
		while ( $parent && $parent instanceof DOMElement ) {
			$tag = strtolower( $parent->tagName );
			if ( 'a' === $tag || 'button' === $tag ) {
				return '' === self::accessible_name( $parent );
			}
			if ( 'p' === $tag || 'li' === $tag || 'td' === $tag || 'figcaption' === $tag ) {
				return false; // the control is somewhere above this text; the image is not its only content
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	/** Is this node inside a <template>, which is inert until a script clones it? */
	private static function inside_template( DOMNode $node ) {
		$parent = $node->parentNode;
		while ( $parent && $parent instanceof DOMElement ) {
			if ( 'template' === strtolower( $parent->tagName ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	/** Does an aria-labelledby on this node point at an element that exists? */
	private static function labelled_by_exists( DOMNode $node ) {
		$ids = preg_split( '/\\s+/', trim( (string) $node->getAttribute( 'aria-labelledby' ) ) );
		$doc = $node->ownerDocument;
		foreach ( $ids as $id ) {
			if ( '' === $id ) {
				continue;
			}
			if ( ! $doc || ! $doc->getElementById( $id ) ) {
				return false;
			}
		}
		return $ids ? true : false;
	}

	/** Does a class name suggest the author hid this element on purpose? */
	private static function class_marks_hidden( DOMNode $node ) {
		$class = strtolower( (string) $node->getAttribute( 'class' ) );
		if ( '' === $class ) {
			return false;
		}
		foreach ( preg_split( '/\s+/', $class ) as $token ) {
			if ( in_array( $token, array( 'hidden', 'hide', 'd-none', 'display-none', 'is-hidden' ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/** Obviously invisible: zero width/height, or an inline display:none / visibility:hidden. */
	private static function looks_hidden( DOMNode $node ) {
		$style = strtolower( (string) $node->getAttribute( 'style' ) );
		if ( false !== strpos( $style, 'display:none' ) || false !== strpos( $style, 'display: none' ) || false !== strpos( $style, 'visibility:hidden' ) ) {
			return true;
		}
		$w = (string) $node->getAttribute( 'width' );
		$h = (string) $node->getAttribute( 'height' );
		if ( ( '0' === $w && '0' === $h ) || '0px' === $w || '0px' === $h ) {
			return true;
		}
		return self::class_marks_hidden( $node );
	}

	/** Accessible name, roughly as a screen reader would compute it (no CSS). */
	private static function accessible_name( DOMNode $node ) {
		foreach ( array( 'aria-label', 'title' ) as $attr ) {
			$value = self::normalise_text( $node->getAttribute( $attr ) );
			if ( '' !== $value ) {
				return $value;
			}
		}
		$labelledby = self::normalise_text( $node->getAttribute( 'aria-labelledby' ) );
		if ( '' !== $labelledby ) {
			return $labelledby;
		}
		$text = self::normalise_text( $node->textContent );
		if ( '' !== $text ) {
			return $text;
		}
		$imgs = $node->getElementsByTagName( 'img' );
		foreach ( $imgs as $img ) {
			$alt = self::normalise_text( $img->getAttribute( 'alt' ) );
			if ( '' !== $alt ) {
				return $alt;
			}
		}
		return '';
	}

	/**
	 * Text with every kind of space flattened, including the ones that hide empty names.
	 *
	 * A link whose whole content is &nbsp; is announced as an empty link, exactly like a link with no
	 * content at all. PHP's \s does not match a non-breaking space, so before this a row of them
	 * counted as a name and we said nothing. Found by comparing our findings with axe-core's on a real
	 * site: they flagged it, we missed it, and they were right.
	 */
	private static function normalise_text( $value ) {
		$value = (string) $value;
		$clean = @preg_replace( '/[\s\x{00A0}\x{1680}\x{2000}-\x{200B}\x{202F}\x{205F}\x{3000}\x{FEFF}]+/u', ' ', $value );
		if ( null === $clean ) {
			$clean = preg_replace( '/[\s\x00]+/', ' ', str_replace( "\xC2\xA0", ' ', $value ) );
		}
		return trim( (string) $clean );
	}
}
