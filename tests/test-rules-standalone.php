#!/usr/bin/env php
<?php
/**
 * The same regression test, without WordPress. Run it with plain PHP:
 *   php tools/test-rules-standalone.php
 * Continuous integration runs this, so a change that breaks a check or invents a finding fails
 * the build before anyone installs it.
 *
 * @package Markproof
 */

define( 'ABSPATH', __DIR__ . '/' );

/* Command line only: this script is a test, never part of a served page. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

/* The handful of WordPress functions the rules file uses. Keeping this list short is the point:
 * the checks stay pure and testable. */
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) { return $text; } // phpcs:ignore
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); } // phpcs:ignore
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); } // phpcs:ignore
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) { return $value; } // phpcs:ignore
}

require_once __DIR__ . '/../includes/class-rules.php';
$fixtures = require __DIR__ . '/fixtures.php';

$found = array();
foreach ( Markproof_Rules::analyse( $fixtures['bad'] ) as $issue ) {
	$found[ $issue['rule_id'] ] = true;
}
$missing = array();
foreach ( $fixtures['expected'] as $rule ) {
	if ( ! isset( $found[ $rule ] ) ) {
		$missing[] = $rule;
	}
}
$clean = Markproof_Rules::analyse( $fixtures['good'] );
$tricky = isset( $fixtures['tricky'] ) ? Markproof_Rules::analyse( $fixtures['tricky'] ) : array();

$meta = Markproof_Rules::metadata();
unset( $meta['scanned_from_stored_content'], $meta['page_too_large'] );

printf( "checks defined: %d\n", count( $meta ) );
printf( "problems detected on the broken fixture: %d of %d expected\n", count( $fixtures['expected'] ) - count( $missing ), count( $fixtures['expected'] ) );
if ( $missing ) {
	printf( "MISSING: %s\n", esc_html( implode( ', ', $missing ) ) );
}
printf( "findings on the clean fixture (want 0): %d\n", count( $clean ) );
foreach ( $clean as $issue ) {
	printf( "  FALSE POSITIVE: %s :: %s\n", esc_html( $issue['rule_id'] ), esc_html( $issue['snippet'] ) );
}
printf( "findings on the correct-but-tricky fixture (want 0): %d\n", count( $tricky ) );
foreach ( $tricky as $issue ) {
	printf( "   %s: %s (%s)\n", $issue['rule_id'], $issue['message'], $issue['snippet'] );
}

$ok = ( ! $missing && ! $clean && ! $tricky );
printf( "RESULT: %s\n", $ok ? 'PASS' : 'FAIL' );
exit( $ok ? 0 : 1 );
