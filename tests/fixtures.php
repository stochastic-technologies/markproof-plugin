<?php
/**
 * Test fixtures shared by the WordPress test and the standalone test.
 *
 * The "bad" page contains deliberate mistakes; the "good" page must produce nothing at all,
 * because a false positive costs more trust than a missed finding.
 *
 * @package AccessAudit
 */

return array(
	'bad'      => '<!doctype html><html><head><title>Broken</title></head><body>
<h1>Title</h1>
<h4>Skipping</h4>
<img src="a.jpg">
<a href="/x/">click here</a>
<a href="/y/"><img src="icon.png"></a>
<button></button>
<form><input type="text" name="q"></form>
<table><tr><td>a</td><td>b</td></tr></table>
<iframe src="https://example.com"></iframe>
<div tabindex="3">x</div>
<p aria-hidden="true"><a href="/z/">hidden but focusable</a></p>
<span id="dup"></span><span id="dup"></span>
<meta name="viewport" content="width=device-width, maximum-scale=1">
<h2></h2>
<label for="nope">Missing field</label>
<a href="/x/" target="_blank">external</a>
<video src="v.mp4" autoplay></video>
<marquee>scrolling</marquee>
<span aria-labelledby="ghost">x</span>
<img src="a.jpg" alt="IMG_2481.jpg">
<img src="b.jpg" alt="image">
<a href="/spaces/">&nbsp;&nbsp;&nbsp;</a>
<button>&#160;</button>
<a href="javascript:void(0)">do something</a>
<input type="image" src="go.png">
<a href="/outer/"><button>nested</button></a>
</body></html>',

	/* A page that is correct in ways that tempt a rule into a false positive: decorative images,
	   labels on frames, a layout table declared as such, content inside an inert <template>, and form
	   controls that are named or that need no name. A false positive costs more trust than a missed
	   finding, so this page must produce nothing at all. */
	'tricky'   => '<!doctype html><html lang="en"><head><title>Tricky but correct</title>
<meta name="viewport" content="width=device-width, initial-scale=1"></head><body>
<h1>Heading</h1>
<h2>Sub</h2>
<h3>Deeper</h3>
<p>An icon link: <a href="/basket/" aria-label="Basket"><svg viewBox="0 0 10 10" aria-hidden="true"><path d="M0 0h10v10H0z"/></svg></a></p>
<p>A button named by its image: <button><img src="/search.png" alt="Search"></button></p>
<p>A button named by aria-label: <button aria-label="Close"></button></p>
<p>Decorative on purpose: <img src="/swirl.png" role="presentation"></p>
<p>Also decorative: <img src="/rule.png" aria-hidden="true"></p>
<form>
  <label for="q">Search</label>
  <input type="search" id="q" name="q">
  <label>Your email <input type="email" name="email"></label>
  <input type="hidden" name="token" value="abc">
  <input type="submit" value="Go">
  <input type="button" value="Cancel">
  <select aria-label="Sort by"><option>Newest</option></select>
</form>
<table role="presentation"><tr><td>Layout</td><td>cell</td></tr></table>
<iframe src="https://example.com/embed" aria-label="A video about our work"></iframe>
<template><h4>Only shown later by a script</h4><img src="/later.png"></template>
<p>A plain link: <a href="/prices/">our prices</a></p>
</body></html>',

	'good'     => '<!doctype html><html lang="en"><head><title>A clean page</title>
<meta name="viewport" content="width=device-width, initial-scale=1"></head><body>
<a class="skip" href="#main">Skip to content</a>
<h1>Heading</h1>
<h2>Sub heading</h2>
<h3>Deeper</h3>
<p>Text with a <a href="/pricing/">link that says where it goes</a>.</p>
<img src="chart.png" alt="Sales rose from 10 to 42 units in March">
<a href="/contact/"><img src="icon.png" alt="Contact us"></a>
<form>
  <label for="email">Email</label>
  <input type="email" id="email" name="email">
  <button type="submit">Send</button>
</form>
<table>
  <caption>Prices</caption>
  <tr><th scope="col">Service</th><th scope="col">Price</th></tr>
  <tr><td>Repair</td><td>50</td></tr>
</table>
<iframe src="https://example.com" title="Our opening hours video"></iframe>
<div aria-hidden="true">decorative</div>
<div aria-hidden="true"><a href="/hidden/" tabindex="-1">not reachable by keyboard</a></div>
<input type="text" name="trap" tabindex="-1" aria-hidden="true">
<a href="/legal/" target="_blank" aria-label="Terms, opens in a new tab">Terms</a>
</body></html>',

	/* Every rule that must fire on the bad fixture. */
	'expected' => array(
		'img_missing_alt',
		'link_empty_name',
		'button_empty_name',
		'input_missing_label',
		'heading_order',
		'table_missing_header',
		'iframe_missing_title',
		'positive_tabindex',
		'aria_hidden_focusable',
		'generic_link_text',
		'zoom_blocked',
		'duplicate_ids',
		'heading_empty',
		'aria_reference_missing',
		'link_new_window',
		'autoplay_media',
		'obsolete_element',
		'img_alt_filename',
		'img_alt_generic',
		'link_javascript_href',
		'input_image_missing_alt',
		'nested_interactive',
	),
);
