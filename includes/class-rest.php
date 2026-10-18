<?php
/**
 * REST endpoint for the editor: what did the last scan find on this post?
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markproof_REST {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes() {
		register_rest_route(
			'markproof/v1',
			'/check',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'check_content' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'content' => array( 'required' => true, 'type' => 'string' ),
					'title'   => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);
		register_rest_route(
			'markproof/v1',
			'/post/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'for_post' ),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'edit_post', (int) $request['id'] );
				},
				'args'                => array(
					'id' => array( 'validate_callback' => function ( $v ) { return is_numeric( $v ); } ),
				),
			)
		);
	}

	/** Check the HTML the editor is about to save, so problems are caught before publishing. */
	public function check_content( WP_REST_Request $request ) {
		$content = (string) $request['content'];
		if ( strlen( $content ) > 2000000 ) {
			return new WP_Error( 'markproof_too_large', __( 'That draft is too large to check here.', 'markproof' ), array( 'status' => 413 ) );
		}
		$title = wp_strip_all_tags( (string) $request['title'] );
		$html  = '<!doctype html><html lang="' . esc_attr( get_bloginfo( 'language' ) ) . '"><head><title>'
			. esc_html( $title ) . '</title></head><body>' . $content . '</body></html>';

		$meta   = Markproof_Rules::metadata();
		$issues = Markproof_Rules::analyse( $html );
		$counts = array();
		foreach ( $issues as $issue ) {
			$key = $issue['rule_id'] . '|' . $issue['snippet'];
			if ( ! isset( $counts[ $key ] ) ) {
				$counts[ $key ]                = $issue;
				$counts[ $key ]['occurrences'] = 0;
			}
			$counts[ $key ]['occurrences']++;
		}
		$out = array();
		foreach ( $counts as $issue ) {
			$id       = $issue['rule_id'];
			$out[]    = array(
				'rule'        => $id,
				'title'       => $meta[ $id ]['title'] ?? $id,
				'severity'    => $issue['severity'],
				'fix'         => $meta[ $id ]['fix'] ?? '',
				'message'     => $issue['message'],
				'snippet'     => $issue['snippet'],
				'occurrences' => (int) $issue['occurrences'],
			);
		}
		return rest_ensure_response(
			array(
				'findings' => $out,
				'scope'    => __( 'These are the problems that can be proven from the HTML of this draft. Colour contrast, how a page behaves in a browser, and how it sounds in a screen reader are not checked here.', 'markproof' ),
			)
		);
	}

	public function for_post( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$url     = get_permalink( $post_id );
		$scan    = Markproof_Store::latest_scan();
		$meta    = Markproof_Rules::metadata();

		if ( ! $scan || ! $url ) {
			return rest_ensure_response( array( 'scanned' => false, 'findings' => array(), 'scan' => null ) );
		}

		$rows  = Markproof_Store::issues_for_scan( $scan->id, 100, '', 0, $url );
		$items = array();
		foreach ( $rows as $row ) {
			$info    = isset( $meta[ $row->rule_id ] ) ? $meta[ $row->rule_id ] : null;
			$items[] = array(
				'rule'      => $row->rule_id,
				'title'     => $info ? $info['title'] : $row->rule_id,
				'severity'  => $row->severity,
				'message'   => $row->message,
				'fix'       => $info ? $info['fix'] : '',
				'snippet'   => $row->snippet,
				'occurrences' => (int) $row->occurrences,
			);
		}

		return rest_ensure_response(
			array(
				'scanned'  => true,
				'scan_id'  => (int) $scan->id,
				'scan_date' => $scan->started_at,
				'findings' => $items,
				'report'   => admin_url( 'admin.php?page=markproof-findings&url=' . rawurlencode( $url ) ),
			)
		);
	}
}
