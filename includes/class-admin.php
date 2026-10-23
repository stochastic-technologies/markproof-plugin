<?php
/**
 * Admin screens: dashboard, issues list, CSV export.
 *
 * @package Markproof – Accessibility Checks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Markproof_Admin {

	const CAP = 'manage_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_ajax_markproof_start', array( $this, 'ajax_start' ) );
		add_action( 'wp_ajax_markproof_batch', array( $this, 'ajax_batch' ) );
		add_action( 'wp_ajax_markproof_test', array( $this, 'ajax_test' ) );
		add_action( 'admin_post_markproof_export', array( $this, 'export_csv' ) );
		add_action( 'admin_notices', array( $this, 'welcome_notice' ) );
		add_action( 'admin_post_markproof_dismiss_welcome', array( $this, 'dismiss_welcome' ) );
		add_action( 'admin_post_markproof_save_alt', array( $this, 'save_alt_text' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_pages_columns', array( $this, 'add_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	public function menu() {
		add_menu_page(
			__( 'Markproof – Accessibility Checks', 'markproof' ),
			__( 'Accessibility', 'markproof' ),
			self::CAP,
			'markproof',
			array( $this, 'page_dashboard' ),
			'dashicons-universal-access',
			58
		);
		add_submenu_page( 'markproof', __( 'Audit', 'markproof' ), __( 'Audit', 'markproof' ), self::CAP, 'markproof', array( $this, 'page_dashboard' ) );
		add_submenu_page( 'markproof', __( 'Findings', 'markproof' ), __( 'Findings', 'markproof' ), self::CAP, 'markproof-findings', array( $this, 'page_findings' ) );
		add_submenu_page( 'markproof', __( 'Image alt text', 'markproof' ), __( 'Image alt text', 'markproof' ), self::CAP, 'markproof-alt-text', array( $this, 'page_alt_text' ) );
	}

	public function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'markproof' ) ) {
			return;
		}
		wp_enqueue_style( 'markproof', STOCHASTIC_SCANNER_URL . 'assets/admin.css', array(), STOCHASTIC_SCANNER_VERSION );
		wp_enqueue_script( 'markproof', STOCHASTIC_SCANNER_URL . 'assets/admin.js', array(), STOCHASTIC_SCANNER_VERSION, true );
		wp_localize_script(
			'markproof',
			'AccessibilityScanner',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'markproof' ),
				'i18n'  => array(
					'starting' => __( 'Starting…', 'markproof' ),
					'working'  => __( 'Checking pages…', 'markproof' ),
					'done'     => __( 'Scan finished.', 'markproof' ),
					'failed'   => __( 'Scan failed.', 'markproof' ),
				),
			)
		);
	}

	public function page_dashboard() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$scan    = Markproof_Store::latest_scan();
		$summary = $scan ? Markproof_Store::summary_for_scan( $scan->id ) : array();
		$meta    = Markproof_Rules::metadata();
		$errors  = 0;
		$warns   = 0;
		foreach ( $summary as $row ) {
			if ( 'error' === $row->severity ) {
				$errors += (int) $row->items;
			} else {
				$warns += (int) $row->items;
			}
		}
		?>
		<div class="wrap markproof">
			<h1><?php esc_html_e( 'Markproof', 'markproof' ); ?> <span class="description"><?php esc_html_e( '– accessibility checks for your own pages', 'markproof' ); ?></span></h1>
			<p class="lead">
				<?php esc_html_e( 'Checks your own pages for accessibility problems that can be found in the HTML, and tells you how to fix each one. It does not check colour contrast or anything that needs a browser rendering, it does not change your content, and nothing about your site is sent anywhere.', 'markproof' ); ?>
			</p>

			<div class="aa-cards">
				<div class="aa-card">
					<span class="aa-num"><?php echo $scan ? (int) $scan->urls_done : 0; ?></span>
					<span class="aa-label"><?php esc_html_e( 'pages checked', 'markproof' ); ?></span>
				</div>
				<div class="aa-card">
					<span class="aa-num aa-error"><?php echo (int) $errors; ?></span>
					<span class="aa-label"><?php esc_html_e( 'errors', 'markproof' ); ?></span>
				</div>
				<div class="aa-card">
					<span class="aa-num aa-warn"><?php echo (int) $warns; ?></span>
					<span class="aa-label"><?php esc_html_e( 'warnings', 'markproof' ); ?></span>
				</div>
			</div>

			<p>
				<button class="button button-primary" id="aa-run"><?php esc_html_e( 'Run a scan now', 'markproof' ); ?></button>
				<button class="button" id="aa-test"><?php esc_html_e( 'Test that the server can read your own pages', 'markproof' ); ?></button>
				<span id="aa-progress" class="aa-progress"></span>
			</p>

			<?php if ( $scan ) : ?>
				<p class="description">
					<?php
					printf(
						/* translators: 1: date, 2: pages, 3: failed count */
						esc_html__( 'Last scan: %1$s, %2$d pages, %3$d pages could not be fetched.', 'markproof' ),
						esc_html( $scan->started_at ),
						(int) $scan->urls_done,
						(int) $scan->urls_failed
					);
					?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=markproof-findings' ) ); ?>"><?php esc_html_e( 'See the findings', 'markproof' ); ?></a>
					·
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=markproof_export&scan=' . (int) $scan->id ), 'markproof_export' ) ); ?>"><?php esc_html_e( 'Download CSV', 'markproof' ); ?></a>
				</p>
			<?php endif; ?>

			<h2><?php esc_html_e( 'What it found, by problem', 'markproof' ); ?></h2>
			<?php if ( ! $summary && $scan ) : ?>
				<p><?php esc_html_e( 'Nothing found in the HTML of the pages that were checked. That is a good sign, and it is not a sign that the plugin is broken: it can only report what it can prove from the markup. Colour contrast, keyboard order once the page is rendered, and how the site sounds in a screen reader still need a person to test them.', 'markproof' ); ?></p>
			<?php elseif ( ! $summary ) : ?>
				<p><?php esc_html_e( 'Nothing yet — run a scan to fill this in.', 'markproof' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Problem', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'Severity', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'Occurrences', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'Pages', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'What to do', 'markproof' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $summary as $row ) : ?>
						<?php $rule = isset( $meta[ $row->rule_id ] ) ? $meta[ $row->rule_id ] : null; ?>
						<tr>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=markproof-findings&rule=' . rawurlencode( $row->rule_id ) ) ); ?>"><?php echo esc_html( $rule ? $rule['title'] : $row->rule_id ); ?></a></td>
							<td><span class="aa-pill aa-<?php echo esc_attr( $row->severity ); ?>"><?php echo esc_html( $row->severity ); ?></span></td>
							<td><?php echo (int) $row->items; ?></td>
							<td><?php echo (int) $row->pages; ?></td>
							<td class="aa-fix"><?php echo esc_html( $rule ? $rule['fix'] : '' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<p class="aa-upto">
				<?php
				printf(
					/* translators: 1: page limit, 2: link */
					esc_html__( 'The free version checks up to %1$d pages of this site per scan. There is a paid version for agencies that monitors their client sites on a schedule, emails them when something new breaks, and produces a client-ready report — %2$s.', 'markproof' ),
					(int) markproof_max_urls(),
					'<a href="' . esc_url( apply_filters( 'markproof_pro_url', 'https://accessaudit.dev/pricing.html' ) ) . '" rel="noopener">' . esc_html__( 'described here', 'markproof' ) . '</a>'
				);
				?>
			</p>

			<h2><?php esc_html_e( 'What this plugin does not do', 'markproof' ); ?></h2>
			<ul class="aa-list">
				<li><?php esc_html_e( 'It cannot check colour contrast or visual layout, because that needs a rendered browser image.', 'markproof' ); ?></li>
				<li><?php esc_html_e( 'It does not certify anything. Passing this scan is not a claim of WCAG or ADA conformance, and nobody can honestly give you one from a plugin.', 'markproof' ); ?></li>
				<li><?php esc_html_e( 'It does not touch your content or install an overlay widget. Every fix it suggests is a change you make yourself.', 'markproof' ); ?></li>
			</ul>
		</div>
		<?php
	}

	public function page_findings() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$scan = Markproof_Store::latest_scan();
		$meta = Markproof_Rules::metadata();
		$rule = isset( $_GET['rule'] ) ? sanitize_text_field( wp_unslash( $_GET['rule'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$url  = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per  = 100;
		$rows = $scan ? Markproof_Store::issues_for_scan( $scan->id, $per, $rule, ( $page - 1 ) * $per, $url ) : array();
		?>
		<div class="wrap markproof">
			<h1><?php esc_html_e( 'Findings', 'markproof' ); ?></h1>
			<?php if ( ! $scan ) : ?>
				<p><?php esc_html_e( 'No scan yet.', 'markproof' ); ?></p>
			<?php else : ?>
				<?php if ( $url ) : ?>
					<p><?php esc_html_e( 'Showing one page:', 'markproof' ); ?> <code><?php echo esc_html( $url ); ?></code>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=markproof-findings' ) ); ?>"><?php esc_html_e( 'All findings', 'markproof' ); ?></a></p>
				<?php endif; ?>
				<?php
				// Findings are a list of problems; a list you cannot act on is an accusation. Point at
				// the one place where several of these can be fixed in a single sitting.
				$alt_items = 0;
				foreach ( Markproof_Store::summary_for_scan( $scan->id ) as $sum ) {
					if ( in_array( $sum->rule_id, array( 'img_missing_alt', 'img_alt_filename', 'img_alt_generic' ), true ) ) {
						$alt_items += (int) $sum->items;
					}
				}
				?>
				<?php if ( $alt_items > 0 ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %d: number of findings about images. */
							esc_html( _n( '%d of these findings is about an image. ', '%d of these findings are about images. ', $alt_items, 'markproof' ) ),
							(int) $alt_items
						);
						?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=markproof-alt-text' ) ); ?>"><?php esc_html_e( 'Fill them in on the Image alt text screen', 'markproof' ); ?></a>.
					</p>
				<?php endif; ?>
				<form method="get">
					<input type="hidden" name="page" value="markproof-findings">
					<label class="screen-reader-text" for="markproof-rule"><?php esc_html_e( 'Filter by problem', 'markproof' ); ?></label>
					<select name="rule" id="markproof-rule">
						<option value=""><?php esc_html_e( 'All problems', 'markproof' ); ?></option>
						<?php foreach ( $meta as $id => $info ) : ?>
							<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $rule, $id ); ?>><?php echo esc_html( $info['title'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<button class="button"><?php esc_html_e( 'Filter', 'markproof' ); ?></button>
				</form>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Page', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'Problem', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'Details', 'markproof' ); ?></th>
						<th><?php esc_html_e( 'Found in your HTML', 'markproof' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<?php $info = isset( $meta[ $row->rule_id ] ) ? $meta[ $row->rule_id ] : null; ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( $row->url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $row->url, PHP_URL_PATH ) ? wp_parse_url( $row->url, PHP_URL_PATH ) : '/' ); ?> <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'markproof' ); ?></span></a>
								<?php $edit = Markproof_Scanner::edit_link( $scan->id, $row->url ); ?>
								<?php if ( $edit ) : ?><br><a class="aa-edit" href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit this content', 'markproof' ); ?></a><?php endif; ?>
							</td>
							<td>
								<span class="aa-pill aa-<?php echo esc_attr( $row->severity ); ?>"><?php echo esc_html( $row->severity ); ?></span>
								<?php echo esc_html( $info ? $info['title'] : $row->rule_id ); ?>
								<?php if ( $row->occurrences > 1 ) : ?>
									<em><?php printf( /* translators: %d: occurrences */ esc_html__( '(×%d)', 'markproof' ), (int) $row->occurrences ); ?></em>
								<?php endif; ?>
							</td>
							<td class="aa-fix">
								<?php echo esc_html( $row->message ); ?><br>
								<span class="description"><?php echo esc_html( $info ? $info['fix'] : '' ); ?></span>
								<?php if ( $info ) : ?>
									<br><a class="description" href="<?php echo esc_url( 'https://accessaudit.dev/checks/' . rawurlencode( $row->rule_id ) . '.html' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'What this means, and how to fix it in WordPress', 'markproof' ); ?> <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'markproof' ); ?></span></a>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $row->snippet ); ?></code></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				$next = $page + 1;
				$prev = max( 1, $page - 1 );
				?>
				<p>
					<?php if ( $page > 1 ) : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'markproof-findings', 'rule' => $rule, 'paged' => $prev ), admin_url( 'admin.php' ) ) ); ?>">&laquo; <?php esc_html_e( 'Previous', 'markproof' ); ?></a>
					<?php endif; ?>
					<?php if ( count( $rows ) === $per ) : ?>
						<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'markproof-findings', 'rule' => $rule, 'paged' => $next ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Next', 'markproof' ); ?> &raquo;</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/** A count of findings next to each post, so problems are visible where people work. */
	public function add_column( $columns ) {
		if ( ! current_user_can( self::CAP ) ) {
			return $columns;
		}
		$columns['markproof'] = __( 'Accessibility', 'markproof' );
		return $columns;
	}

	public function render_column( $column, $post_id ) {
		if ( 'markproof' !== $column ) {
			return;
		}
		$latest = get_option( 'markproof_post_counts_latest', array() );
		if ( empty( $latest['counts'] ) ) {
			echo '<span class="aa-muted">—</span>';
			return;
		}
		$count = isset( $latest['counts'][ $post_id ] ) ? (int) $latest['counts'][ $post_id ] : 0;
		if ( 0 === $count ) {
			echo '<span class="aa-ok">' . esc_html__( 'None found', 'markproof' ) . '</span>';
			return;
		}
		printf(
			'<a class="aa-count" href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=markproof-findings&url=' . rawurlencode( get_permalink( $post_id ) ) ) ),
			esc_html( sprintf( /* translators: %d: number of findings */ _n( '%d finding', '%d findings', $count, 'markproof' ), $count ) )
		);
	}

	/** One dismissible pointer to the first scan. Not an advert, and it never comes back. */
	public function welcome_notice() {
		if ( ! current_user_can( self::CAP ) || get_option( 'markproof_welcome_dismissed' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which screen we are on, changing nothing.
		if ( 0 === strpos( $page, 'markproof' ) ) {
			return; // they are already there
		}
		// One dismissible sentence on the dashboard and the plugin list, not on every screen an
		// administrator visits. A notice that follows people around is the behaviour reviewers
		// rightly object to, and it is not how we would want to be treated either.
		$allowed = array( 'dashboard', 'plugins' );
		if ( $screen && ! in_array( $screen->id, $allowed, true ) ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Markproof – Accessibility Checks is ready.', 'markproof' ); ?></strong>
				<?php esc_html_e( 'Run one scan to see which pages have accessibility problems, and what to change. It takes a minute and nothing is changed automatically.', 'markproof' ); ?>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=markproof' ) ); ?>"><?php esc_html_e( 'Open Markproof – Accessibility Checks', 'markproof' ); ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=markproof_dismiss_welcome' ), 'markproof_dismiss_welcome' ) ); ?>"><?php esc_html_e( 'Dismiss', 'markproof' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function dismiss_welcome() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'markproof' ) );
		}
		check_admin_referer( 'markproof_dismiss_welcome' );
		update_option( 'markproof_welcome_dismissed', 1, false );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	public function ajax_start() {
		check_ajax_referer( 'markproof', 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$start = Markproof_Scanner::start_scan( get_current_user_id() );
		wp_send_json_success( $start );
	}

	/** Can the server fetch its own front page? Most scan failures are really this. */
	public function ajax_test() {
		check_ajax_referer( 'markproof', 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$started  = microtime( true );
		$response = wp_remote_get( home_url( '/' ), array( 'timeout' => 20, 'user-agent' => 'Markproof – Accessibility Checks/' . STOCHASTIC_SCANNER_VERSION ) );
		$ms       = (int) round( ( microtime( true ) - $started ) * 1000 );
		if ( is_wp_error( $response ) ) {
			wp_send_json_success( array( 'ok' => false, 'detail' => $response->get_error_message(), 'ms' => $ms ) );
		}
		wp_send_json_success(
			array(
				'ok'     => true,
				'detail' => 'HTTP ' . (int) wp_remote_retrieve_response_code( $response ) . ', ' . strlen( wp_remote_retrieve_body( $response ) ) . ' bytes',
				'ms'     => $ms,
			)
		);
	}

	public function ajax_batch() {
		check_ajax_referer( 'markproof', 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$scan_id = isset( $_POST['scan'] ) ? (int) $_POST['scan'] : 0;
		if ( ! $scan_id ) {
			wp_send_json_error( 'missing scan' );
		}
		wp_send_json_success( Markproof_Scanner::process_batch( $scan_id, 5, 20 ) );
	}

	public function export_csv() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'markproof' ) );
		}
		check_admin_referer( 'markproof_export' );
		$scan_id = isset( $_GET['scan'] ) ? (int) $_GET['scan'] : 0;
		$meta    = Markproof_Rules::metadata();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=markproof-' . $scan_id . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'page_url', 'severity', 'problem', 'details', 'html_snippet', 'how_to_fix' ) );
		$offset = 0;
		do {
			$rows = Markproof_Store::issues_for_scan( $scan_id, 500, '', $offset );
			foreach ( $rows as $row ) {
				$info = isset( $meta[ $row->rule_id ] ) ? $meta[ $row->rule_id ] : null;
				fputcsv(
					$out,
					array(
						$row->url,
						$row->severity,
						$info ? $info['title'] : $row->rule_id,
						$row->message,
						$row->snippet,
						$info ? $info['fix'] : '',
					)
				);
			}
			$offset += 500;
		} while ( count( $rows ) === 500 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming straight to the browser, not a file on disk.
		fclose( $out );
		exit;
	}

	/**
	 * A work list of images with no alt text, with the fix on the same screen.
	 *
	 * Reporting a problem is half a tool. The other half is being able to act on it without leaving
	 * the page, which is why every row here can be filled in and saved in one go.
	 */
	public function page_alt_text() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a display filter, it changes nothing.
		$show_all = isset( $_GET['show'] ) && 'all' === $_GET['show'];
		$images   = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'post_status'    => 'inherit',
				'posts_per_page' => 200,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$missing = array();
		foreach ( $images as $id ) {
			$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
			if ( '' === trim( $alt ) ) {
				$missing[] = $id;
			}
		}
		$list = $show_all ? $images : $missing;
		?>
		<div class="wrap markproof">
			<h1><?php esc_html_e( 'Image alt text', 'markproof' ); ?></h1>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a count of what was just saved, shown back to the person who saved it. ?>
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<p class="notice notice-success is-dismissible">
					<?php
					$saved = (int) $_GET['saved']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
					printf(
						/* translators: %d: number of images saved. */
						esc_html( _n( 'Saved alt text for %d image.', 'Saved alt text for %d images.', $saved, 'markproof' ) ),
						(int) $saved
					);
					?>
				</p>
			<?php endif; ?>

			<p>
				<?php
				printf(
					/* translators: 1: number of images without alt text, 2: total number of images. */
					esc_html__( '%1$d of your %2$d images have no alt text.', 'markproof' ),
					(int) count( $missing ),
					(int) count( $images )
				);
				?>
				<?php if ( $show_all ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=markproof-alt-text' ) ); ?>"><?php esc_html_e( 'Show only the ones that need it', 'markproof' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=markproof-alt-text&show=all' ) ); ?>"><?php esc_html_e( 'Show every image', 'markproof' ); ?></a>
				<?php endif; ?>
			</p>

			<p class="description">
				<?php esc_html_e( 'Alt text describes what an image is for on the page it appears on, not what it looks like. "Team photo: Dana, Priya and Sam outside the Leeds office" helps somebody who cannot see it; "IMG_2043" does not. Images that are purely decorative should be left with an empty alt text on purpose — WordPress puts that on the image when you set alt to nothing, and this screen will keep asking you about it until you do.', 'markproof' ); ?>
			</p>

			<?php if ( ! $list ) : ?>
				<p><strong><?php esc_html_e( 'Every image has alt text. Nothing to do here.', 'markproof' ); ?></strong></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="markproof_save_alt">
					<?php wp_nonce_field( 'markproof_save_alt' ); ?>
					<table class="widefat striped markproof-alt-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Image', 'markproof' ); ?></th>
								<th scope="col"><?php esc_html_e( 'File', 'markproof' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Alt text', 'markproof' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $list as $id ) : ?>
							<?php
							$alt   = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
							$thumb = wp_get_attachment_image( $id, array( 80, 80 ), true, array( 'style' => 'max-width:80px;height:auto' ) );
							?>
							<tr>
								<td><?php echo $thumb ? wp_kses_post( $thumb ) : esc_html__( '(cannot be shown)', 'markproof' ); ?></td>
								<td>
									<a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><?php echo esc_html( (string) get_the_title( $id ) ); ?></a>
									<br><span class="description"><?php echo esc_html( (string) wp_basename( (string) get_attached_file( $id ) ) ); ?></span>
								</td>
								<td>
									<label class="screen-reader-text" for="alt-<?php echo (int) $id; ?>"><?php esc_html_e( 'Alt text for this image', 'markproof' ); ?></label>
									<textarea id="alt-<?php echo (int) $id; ?>" name="alt[<?php echo (int) $id; ?>]" rows="2" class="large-text"><?php echo esc_textarea( $alt ); ?></textarea>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p>
						<button class="button button-primary" type="submit"><?php esc_html_e( 'Save alt text', 'markproof' ); ?></button>
						<span class="description"><?php esc_html_e( 'Leave a box empty if the image is decorative, and it will be marked as such on purpose.', 'markproof' ); ?></span>
					</p>
				</form>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'This screen looks at your media library, not at your pages: it can tell that alt text is missing, and it cannot tell whether the words you write are any good. No tool can.', 'markproof' ); ?>
			</p>
		</div>
		<?php
	}

	/** Save the alt text the user typed on the screen above. */
	public function save_alt_text() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to change these images.', 'markproof' ) );
		}
		check_admin_referer( 'markproof_save_alt' );

		// The nonce was verified above; every value below is sanitized one by one and every key cast to an integer.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$rows  = isset( $_POST['alt'] ) && is_array( $_POST['alt'] ) ? wp_unslash( $_POST['alt'] ) : array();
		$saved = 0;
		foreach ( $rows as $id => $text ) {
			$id = (int) $id;
			if ( $id <= 0 || 'attachment' !== get_post_type( $id ) ) {
				continue;
			}
			$clean = sanitize_text_field( (string) $text );
			if ( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) === $clean ) {
				continue;
			}
			update_post_meta( $id, '_wp_attachment_image_alt', $clean );
			$saved++;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'markproof-alt-text',
					'saved' => $saved,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
