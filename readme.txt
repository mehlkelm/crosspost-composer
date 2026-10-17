=== Crosspost Composer ===
Contributors: stefanpauwels
Tags: social, share, crosspost, mastodon, bluesky
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Share published posts to X, Bluesky, Threads, Mastodon, LinkedIn and Facebook from the block editor. No API keys needed.

== Description ==

Crosspost Composer adds a **Crosspost** panel to the post sidebar in the block editor. It suggests a short post text from your title and content, lets you edit it, and opens each network's own web composer with that text filled in. You review and publish there, as yourself. The plugin never talks to the networks itself, so there is nothing to connect, authorise or keep secret.

= How the suggested text is built =

* When the title and plain-text content together fit within 280 characters, both are used and no URL is added.
* For posts containing embedded or featured media, the public URL of the first media item is appended when the result fits. Otherwise the post permalink is used.
* Longer text-only posts use the title, or the content when there is no title, followed by the permalink.
* The suggestion is capped at 280 characters and stays editable. A counter shows the length, and a reset link brings the suggestion back.

= Services =

All services are enabled by default, and each gets its own button. Buttons become available after the post is published and open the service's public composer in a new window. Turn individual services off under Settings > Crosspost. Mastodon needs the URL of your instance; mastodon.social is used when none is entered.

Facebook's share page only accepts a link, so its button opens the sharer with the post permalink and passes the text as a quote that Facebook may ignore.

= Third-party services =

Nothing is sent anywhere until you press a button. Each button opens a page on the respective service in a new browser window, with your text in the URL:

* X: https://x.com/intent/post ([Terms](https://x.com/tos), [Privacy](https://x.com/privacy))
* Bluesky: https://bsky.app/intent/compose ([Terms](https://bsky.social/about/support/tos), [Privacy](https://bsky.social/about/support/privacy-policy))
* Threads: https://www.threads.net/intent/post ([Terms](https://help.instagram.com/769983657850450), [Privacy](https://privacycenter.instagram.com/policy))
* Mastodon: the share page of the instance you configure, for example https://mastodon.social/share. Terms and privacy policy are those of your instance.
* LinkedIn: https://www.linkedin.com/feed/?shareActive=true ([Terms](https://www.linkedin.com/legal/user-agreement), [Privacy](https://www.linkedin.com/legal/privacy-policy))
* Facebook: https://www.facebook.com/sharer/sharer.php ([Terms](https://www.facebook.com/terms), [Privacy](https://www.facebook.com/privacy/policy))

The plugin itself makes no network requests and stores only your service choices in the WordPress options table. Those are removed when the plugin is deleted.

== Installation ==

1. Upload the `crosspost-composer` folder to `/wp-content/plugins/`, or install it from Plugins > Add New.
2. Activate the plugin.
3. Optionally visit Settings > Crosspost to pick services and set your Mastodon instance.
4. Open a published post in the block editor and find the Crosspost panel in the post sidebar.

== Frequently Asked Questions ==

= Why are the buttons disabled? =

Buttons work once the post is published, because the suggested text may contain the permalink. They are also disabled while the text is longer than 280 characters.

= Why 280 characters for every service? =

280 is the shortest limit among the supported services. One text that fits everywhere keeps the composer simple.

= Does it post automatically? =

No. Each button opens the service's own composer in a new window. You confirm the post there, logged in as yourself.

= Can I add another Mastodon-compatible server? =

Enter any instance URL under Settings > Crosspost. The plugin opens `/share?text=` on that server, which Mastodon and compatible software such as Pleroma and Akkoma support.

= Why does Facebook ignore my text? =

Facebook's share page does not accept prefilled text. The button passes the permalink, and the text as a quote that Facebook may or may not display.

== Screenshots ==

1. The Crosspost panel in the post sidebar with a suggested text and one button per service.
2. Settings > Crosspost, where services and the Mastodon instance are configured.

== Changelog ==

= 0.4.0 =
* Add a Settings link on the Plugins screen.
* Open the X composer on x.com instead of twitter.com.
* Use the block editor's current panel API, with a fallback for WordPress 6.5.
* Make the editor strings translatable and remove the stored settings on uninstall.
* Complete the readme for the plugin directory.

= 0.3.0 =
* Add LinkedIn and Facebook.

= 0.2.0 =
* Use a public media URL for posts with featured or embedded media, and fall back to the permalink when the result is too long.

= 0.1.0 =
* First release with X, Bluesky, Threads and Mastodon, a shared editable text and per-service buttons.
