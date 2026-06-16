# Readministrator (Read Only Administrator)
Contributors: dhanendran
Tags: read only, admin, administrator, options, settings
Requires at least: 4.7
Tested up to: 7.0
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv3 or later
License URI: <a href="http://www.gnu.org/licenses/gpl-3.0.html">http://www.gnu.org/licenses/gpl-3.0.html</a>

== Description ==
Readministrator adds a "Read Only Administrator" role. Assign it to a user and they can browse the entire wp-admin like an administrator — Settings, Users, Plugins, Themes, content, comments and more — but they cannot change anything. Every write is blocked at the capability layer.

What read-only administrators **can** do:

* View every admin screen an administrator can see.
* Browse Settings, Tools, Users, Plugins, Themes and content lists.
* Run a read-only export.

What they **cannot** do:

* Save any Settings page (core or plugin Settings API).
* Create, edit, publish, trash or delete posts, pages or media.
* Add, edit, delete or promote users (including editing their own profile).
* Activate, deactivate, install, update, delete or edit plugins.
* Switch, install, update, delete or edit themes.
* Edit menus, widgets or the Customizer.
* Moderate or edit comments.
* Make changes through the REST API (the block editor included).

= Known limitations =

Enforcement covers core write paths plus the REST API. A small residual surface remains: some third-party plugins that perform writes through their own custom `admin-ajax`/`admin-post` handlers, and Network Admin (multisite) screens, are not yet covered. These are on the roadmap.

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for `Readministrator (Read Only Administrator)`
3. Activate `Readministrator (Read Only Administrator)` from your Plugins page.

= From WordPress.org =

1. Download Readministrator (Read Only Administrator).
2. Upload the 'readministrator-read-only-administrator' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate Readministrator (Read Only Administrator) from your Plugins page.

== Changelog ==

= 1.0.0 =

* [Feature] Read Only Administrators are now genuinely read-only across the whole admin, not just nine core settings pages. Writes are blocked at the capability layer (`user_has_cap` / `map_meta_cap`), with additional guards for option saves, the REST API, and classic write actions (plugins, themes, menus, widgets, comments).
* [Improvement] The role now stores only a safe Editor baseline; the elevated "view everything" capabilities are granted at runtime, so deactivating the plugin downgrades the role to a plain Editor instead of leaving stranded permissions.
* [Improvement] Role capabilities re-sync automatically on update (no reactivation needed).
* [Improvement] A persistent read-only notice now appears on every admin screen.

= 0.0.2 =

* [Fix] The `readministrator` role is now removed on uninstall instead of on deactivation, so temporarily deactivating the plugin no longer destroys the role or strips assigned users.
* [Fix] Plugin stylesheet now loads only in the admin and only for Read Only Administrator users (previously enqueued on every front-end and admin request).

= 0.0.1 =

* Initial release.
