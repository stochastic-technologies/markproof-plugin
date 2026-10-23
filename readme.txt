=== Markproof – Accessibility Checks ===
Tags: accessibility, wcag, ada, audit, alt text
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Finds the accessibility problems that live in your HTML and quotes the exact markup to fix. Free, on your own server, no account.

== Description ==

Markproof – Accessibility Checks scans the pages of **your own site** and reports accessibility problems that can be found by reading the HTML. For every finding you get the page, a snippet of the actual markup, and a short explanation of how to fix it.

**What it checks**

* Images with no `alt` attribute
* Links and buttons with no accessible name (the "empty icon link" problem)
* Form fields with no label
* Heading levels that skip, a page whose headings start below `h1`, and empty headings
* Missing `lang` on the html element, and missing document titles
* Tables with no header cells
* Embedded frames with no title
* Positive `tabindex` values, which scramble keyboard order
* `aria-hidden` on elements that can still receive focus
* Duplicate `id` attributes
* Viewport settings that block pinch zoom
* Link text that says "click here" instead of where it goes
* Labels and aria-labelledby attributes pointing at an id that does not exist
* Links that open a new window without warning
* Video or audio that plays by itself
* Obsolete `marquee` and `blink` elements
* Alt text that is really a file name ("IMG_2481.jpg") or a useless word ("image")
* Links built out of JavaScript (`href="javascript:..."`)
* Interactive elements nested inside other interactive elements, a common accident when a whole card is made clickable
* Image buttons (`<input type="image">`) with no alt text

**Where you see it**

* A dashboard with the totals and what to do about each problem.
* A findings list where every entry quotes the offending markup and links straight to the editor for that post.
* A column on the Posts and Pages screens showing how many findings each page has.
* A panel inside the block editor showing the findings for the page you are working on, with the markup and the fix one click away.
* **Check this draft**: a button in that panel that runs the same checks over the post you are writing, before you publish it, instead of telling you tomorrow what you got wrong today.
* **Take me to it**: on any finding from a scan, one click selects the block that caused it and scrolls to it. When the cause is outside the post — the theme, a plugin, a widget — it says so rather than guessing.
* An **Image alt text** screen listing every image in your media library with no alt text, with the box to type the description and one Save button for all of them. Findings about images point at it.
* CSV export of everything.

**What it deliberately does not do**

* It cannot check colour contrast or visual layout: that needs a rendered browser image, which a plugin running on your server cannot see. Any plugin claiming otherwise is guessing.
* It does not install an accessibility overlay or widget, and it does not rewrite your content. Overlays are widely criticised because they hide problems rather than fix them, and several have been the subject of lawsuits.
* It does not certify conformance. A scan is evidence of what a machine could see on the day it ran. It is not a legal opinion, not a VPAT, and not a guarantee.
* It does not send anything anywhere. No account, no external API, no telemetry. The scan runs on your own server against your own pages.

**Who it is for**

Site owners who want to know what is actually broken before they hire anyone, and agencies who need an evidence list to work from. It is a finding tool, not a magic fix.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the Plugins screen.
2. Activate it.
3. Go to **Markproof – Accessibility Checks** in the admin menu and press **Run a scan now**.
4. Read the findings, fix the mark-up, run it again.

This plugin is free and checks up to 50 pages per scan, with CSV export. A separate paid product, Markproof – Accessibility Checks Station (sold from our own site, not in this directory), is for agencies looking after many client sites: it runs on any PHP host, checks them on a schedule, shows what changed, emails you when something new appears or when a scan could not read a site at all, and prints a report under your own name. Up to three client sites are free there too.

== A free online version ==

If you would rather try before installing, the same checks run on one page at
[accessibility-scanner.dev/scan](https://accessibility-scanner.dev/scan): paste any URL and see the findings with the markup that
caused them. Nothing is stored and no account is needed. There is also a
[statement generator](https://accessibility-scanner.dev/accessibility-statement-generator.html) for the accessibility
statement every site is supposed to publish.

== Frequently Asked Questions ==

= Does passing this scan mean my site is accessible? =

No. Nothing automated can tell you that. This plugin finds a specific, useful set of HTML-level problems. People with disabilities are the only reliable testers, and manual testing with assistive technology is what actually establishes accessibility. Use this to remove the obvious obstacles before you do that.

= Is my content sent to your server? =

No. There is no server. The plugin fetches your own pages over your own site's HTTP connection and analyses the HTML in PHP on your machine.

= How is this different from an accessibility overlay widget? =

An overlay is a script that adds buttons and fixes on top of your page. It does not change the underlying
markup, so the original problem stays for anyone whose tool ignores the overlay, and several overlays have
been the subject of lawsuits rather than a defence against them. Markproof – Accessibility Checks does the opposite: it never
touches your content, it points at the offending markup, and it leaves the decision to you. It also makes no
claim about conformance, because that is not something a plugin can honestly certify.

= How is this different from plugins that put a score on my dashboard? =

A score tells you a number. A finding tells you which line of HTML is wrong and what to write instead. We
only report things that can be verified by reading the markup, and we deliberately stay silent about things
we cannot see, such as colour contrast.

= Does it work with a page builder? =

It works on the rendered output, so it will check whatever your builder produces on the front end. It does not read your builder's data.

= Why did it find nothing on a page that is clearly broken? =

The most common causes: the page needs a browser to render its content (JavaScript-only markup), the page is not publicly reachable (draft, private, or behind a login), or the problem is visual rather than structural.

= Can it slow my site down for visitors? =

No. Scanning happens only in the admin, in small batches, and never on a visitor's request.

== Screenshots ==

1. The dashboard: how many pages were checked, how many errors and warnings, and what to do about each problem.
2. Findings: page, problem, plain-language explanation, and the exact snippet of markup.
3. The editor sidebar: what the last scan found on the page you are editing, with the markup and the fix one click away — and "Take me to it", which selects the block that caused the finding.
4. Image alt text: every image with nothing filled in, with the box to type the description and one Save button for all of them.

== Changelog ==

= 0.2.1 =
* Images that are tracking beacons (one-by-one pixels from Facebook, Google and the like) or that their own
  author hid with display:none or visibility:hidden are no longer reported as missing alt text. They are not
  pictures anyone is meant to see, they cannot carry a useful description, and reporting them buried the
  findings that matter: on a large marketing site, a Facebook pixel was the top finding on every page.

= 0.2.0 =
* New: check the post you are writing, before you publish it. A button in the editor sidebar runs
  the same checks over your unsaved draft and tells you what it found.
* New: every finding in the editor sidebar has a "Take me to it" button, which selects the block it
  came from and scrolls to it. When a finding comes from something outside the post — a theme, a
  plugin, a widget — it says so instead of pretending to jump.
* New: an "Image alt text" screen that lists the images in your media library with no alt text,
  with the box to fill in right there and one Save button for the lot. Findings about images now
  point at that screen, because a list of problems you cannot act on is just an accusation.
* The scan's storage layer was rewritten so every table name comes from WordPress's own prefix, and
  every remaining advisory from WordPress's Plugin Check carries a written reason: the plugin now
  passes that checker with no errors and no warnings.

= 0.1.0 =
* First release: 25 HTML-level checks, admin dashboard, findings list, CSV export, WP-CLI command, regression tests.

== Upgrade Notice ==

= 0.2.0 =
Adds the draft check in the editor, "Take me to it" for every finding, and an Image alt text screen
that lists the images with nothing filled in and lets you save them all at once.

= 0.1.0 =
First release.

== A note on who built this ==

This plugin is built and maintained by AI workers, under the responsibility of a named human owner. We say so because you deserve to know how your tools are made, and because we would rather be judged on the findings than on the marketing. Every finding links to the exact markup it is talking about, so you can check it yourself before you trust it.
