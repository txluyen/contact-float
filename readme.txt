=== Contact Float ===
Contributors:      tquanreal
Tags:              contact, floating button, popup, zalo, price list
Requires at least: 5.8
Tested up to:      7.0
Stable tag:        1.1.0
Requires PHP:      7.4
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Floating contact buttons (Call, Zalo, Price List popup) with a self-contained overlay — no theme dependency.

== Description ==

Contact Float adds a fixed floating widget to your site with up to three action buttons:

* **Gọi (Call)** — direct phone link with pulse animation
* **Zalo** — external Zalo chat link
* **Bảng Giá (Price List)** — opens a self-contained popup rendered from any WordPress shortcode (e.g. a Flatsome UX Block)

**Key features:**

* Zero theme dependency — the popup overlay is fully managed by the plugin
* Responsive: phone number shown on desktop, hidden on mobile
* Customisable background colour, text colour, and left/right position
* Accessible: keyboard-dismissible (Esc), focus-trapped, `aria-modal`
* Works with any shortcode as popup content

== Installation ==

1. Upload the `txluyen-contact-float` folder to `/wp-content/plugins/`.
2. Activate the plugin from **Plugins → Installed Plugins**.
3. Go to **Settings → Contact Float** and fill in your details.

= Using a Flatsome UX Block as the Price List popup =

1. Create a UX Block in **Flatsome → UX Blocks** with your price table content.
2. Copy its shortcode (e.g. `[ux_block id="1234"]`).
3. Paste it into the **UX Block Shortcode** field in plugin settings.

The plugin renders the block content server-side and opens it in its own overlay — no need for Flatsome's popup system.

== Frequently Asked Questions ==

= Does this work without Flatsome? =

Yes. The plugin renders any valid WordPress shortcode as popup content. Flatsome is not required.

= Can I use a different shortcode, like a contact form? =

Yes. Any shortcode that outputs valid HTML can be used (e.g. `[contact-form-7 id="1234"]`).

= Where is the widget positioned? =

You can choose left or right side via Settings → Contact Float → Position.

== Screenshots ==

1. Floating widget on the frontend (right side, desktop).
2. Price List popup overlay.
3. Settings page in wp-admin.

== Changelog ==

= 1.1.0 =
* Changed Bảng Giá field from numeric Block ID to full shortcode input.
* Plugin now renders the shortcode server-side into a self-contained overlay popup.
* Removed dependency on Flatsome's popup system.
* Added `uninstall.php` for clean removal.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
The Bảng Giá field now accepts a shortcode (e.g. `[ux_block id="1234"]`) instead of a plain numeric ID. Please update the field value in Settings → Contact Float after upgrading.
