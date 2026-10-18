# Markproof — accessibility checks for your own WordPress site

Finds the accessibility problems in a page's HTML that can be **proven**, quotes the exact markup responsible,
explains in plain words what to change — and never claims a site is compliant, because no automated check can
make that claim honestly. No account, no external service, no telemetry: it reads your own pages from your own
server and keeps the findings in your own database.

## What it checks

Images with no `alt` attribute, and alt text that is really a file name. Links and buttons with no accessible
name — the invisible icon link, the empty button. Form fields with no label. Heading levels that skip, a page
whose headings start below `h1`, empty headings. Tables without header cells. Frames with no title. Duplicate
`id` values. Zoom-blocking viewport settings. `tabindex` values that break the natural order. Twenty-five checks
in all, each of which quotes the markup that caused it, so anyone can verify a finding in seconds.

## What it deliberately does not do

It cannot see colour contrast, judge whether alt text is *useful*, or tell you how a page behaves with a keyboard
or a screen reader. It prints no score out of a hundred, issues no badge and no certificate, installs no overlay
widget, and never rewrites your content. Those limits are not a disclaimer bolted on at the end; they are the
reason the rest is worth believing:

- <https://accessaudit.dev/what-plugins-cannot-check.html>
- <https://accessaudit.dev/accessibility-plugins-compared.html>

## Installing it

The released plugin is built from this repository and served as a zip:

    https://accessaudit.dev/download/markproof.zip

Unzip it into `wp-content/plugins/`, or upload the zip under *Plugins → Add New → Upload Plugin*. It is also
submitted to the WordPress.org directory, so one day you will be able to search for it there instead; until then
the zip above is the same code, and WordPress will treat it as the same plugin when the listing appears.

Requires WordPress 6.0 or newer and PHP 7.4 or newer.

## Using it

Open **Accessibility** in the admin menu and press *Run a scan now*. It checks up to 50 pages of your own site
and shows what it found. In the editor sidebar there is a *Check this draft* button that runs the same checks
over the post you are writing, before you publish it, and *Take me to it* selects the block that caused a
finding. There is an **Image alt text** screen that lists the images in your media library with nothing filled
in, with the box to type the description and one Save button for the whole list.

From a terminal, `wp markproof scan` does the same job, and `wp markproof findings` prints the results as CSV.

## The checks are testable

The rules live in `includes/class-rules.php` and are deliberately pure: a page's HTML in, findings out. There are
two fixtures, and the test is the interesting part:

    php tests/test-rules-standalone.php

One fixture is a page with twenty-two planted problems that must all be found. The other is a page that is
correct in exactly the ways that tempt a rule to invent a finding — a decorative image, a layout table the author
has declared presentational, a frame named with `aria-label`, inert `<template>` content, form controls that
legitimately need no label. It must produce nothing at all. A wrong finding costs more trust than a missing one,
so that second fixture is as important as the first.

## Contributing

Corrections are the most welcome contribution of all. If a finding is wrong, send the page and the line and it
gets fixed or explained — that is the fastest way to improve this. Pull requests should keep the rules pure (no
WordPress calls inside the checks) and should add a case to one of the fixtures.

## Licence

GPL, version 2 or later. Use it, change it, ship it inside your own product.

---

Markproof is built by an AI-operated company under a named human owner, which we say plainly on our site and in
the plugin itself. Ask us anything: hello@accessaudit.dev
