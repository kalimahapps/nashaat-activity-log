=== Nashaat Activity Log ===
Contributors: khr2003
Tags: activity,log,monitor,WooCommerce,event
Requires at least: 5.3
Tested up to: 6.1
Stable tag: 1.2.3
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Log site editors activity

== Description ==
Nashaat (Arabic for activity) logs and monitors user activity to troubleshoot errors, trackback actions, protect your website or increase productivity.

It logs actions for the following sections of WordPress:
- Plugins
- Comments
- Media
- Menus
- Site Options
- Posts
- Pages
- System actions (Core update, export)
- Taxonomy
- Themes
- Users
- Widgets

It logs plugins and themes activation, deactivate, installation and removal. For posts, pages, widgets, site options and media some details are provided of the previous data vs the new ones.

=== Third party plugins ===
Built in support for these third-party plugins events:
**[WooCommerce](https://woocommerce.com/)**
 - Products
- Orders
- Copouns,
- Settings
- Product variations

**[Gravity Forms](https://www.gravityforms.com/)**
- Forms changes (status, settings .. etc)
- Core settings
- Import and export
- Notification and confirmations updates

**[User switching](https://wordpress.org/plugins/user-switching/)**
- Switch off
- Switch to/back

**[WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)**
- Events
- Schedules

**[Yoast Duplicate Post](https://wordpress.org/plugins/duplicate-post/)**
- Post clone
- Settings change


== Features ==
- Log user actions in various sections of WordPress
- Search, sort and filter logs
- Set time after which logs are deleted.
- Export data to CSV. Either the entire log data or filtered data.

== Planned Features ==
I am planning to add the following features. There is no time or version set yet. Since the plugin is in early releases I am focusing on fixing bugs, updating code and optimizing performance.
- Multisite support.
- Popular plugins support:

* Yoast SEO
* Gravity Forms (completed)
* Advanced Custom Fields (ACF)
* WPForms
* bbPress
* Contact Form 7
* Easy Digital Downloads

and any other popular plugins that might be requested.
- User session management. To see current singed in user and terminate the session if needed.
- Toggling logging action option. Disable/Enable certain actions to be logged.
- Expand logging to other areas of WordPress
- Add previous/current data for menu changes


== Screenshots ==

1. Log data view
2. Filter options for context
3. Filters applied view
4. Settings page

== Changelog ==
=1.2=
- Added gravityforms support
- Added user switching support
- Added WP Crontrol support
- Added Yoast Duplicate post support
- Add delete row action
- Better exception handling

=1.1=
- Added WooCommerce support (settings, products, orders, variations and copouns events)
- Fixed few minor bugs

= 1.0 =
- First version
