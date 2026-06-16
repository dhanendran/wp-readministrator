# Readministrator (Read Only Administrator)
Contributors: dhanendran
Tags: read only, admin, administrator, options, settings
Requires at least: 4.4
Tested up to: 7.0
Stable tag: 0.0.2
License: GPLv3 or later
License URI: <a href="http://www.gnu.org/licenses/gpl-3.0.html">http://www.gnu.org/licenses/gpl-3.0.html</a>

== Description ==
Allowing users to see the admin settings page. Just Seeing, No edit allowed :) These users will have all the privilege of editors along with that they will have the ability to see the admin settings.

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

= 0.0.2 =

* [Fix] The `readministrator` role is now removed on uninstall instead of on deactivation, so temporarily deactivating the plugin no longer destroys the role or strips assigned users.
* [Fix] Plugin stylesheet now loads only in the admin and only for Read Only Administrator users (previously enqueued on every front-end and admin request).

= 0.0.1 =

* Initial release.
