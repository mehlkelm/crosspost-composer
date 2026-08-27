=== Crosspost Composer ===
Contributors: stefanpauwels
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later

A shared, no-API composer for X, Bluesky, Threads, and Mastodon.

== Description ==

Crosspost Composer adds a panel to the WordPress block editor. When the title and plain-text content together fit the limit, both are used without a URL. For posts containing embedded or featured media, the plugin uses a public URL for the first available media item when the resulting post fits the limit. If no media URL is available or the result is too long, it uses the post permalink. Longer text-only posts use the post permalink. Link posts use the title, or the content when there is no title. Suggested text is capped at 280 characters and remains editable.

All four services are enabled by default, and each gets its own button. Buttons become available after the post is published and open the service's public web composer in a new window, where the post must be confirmed manually. No developer accounts or API credentials are needed.

Configure services and the Mastodon instance under Settings > Crosspost. Mastodon.social is used when no instance is entered.
