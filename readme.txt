=== Advanced CSV Importer ===
Contributors: miyauchi, megumithemes
Tags: comments, spam
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import posts, pages, custom fields, categories, tags and more from a CSV file.

== Description ==

The Advanced CSV Importer will import the following content from a CSV file.

* Posts, pages and other custom post types
* Custom fields and post meta
* Categories, tags

https://github.com/miya0001/advanced-csv-importer

= Action Hooks

* acsv_after_insert_post

= Filter Hooks

* acsv_post_object_keys
* acsv_post_defaults
* acsv_import_upload_size_limit
* acsv_pre_get_post_objects
* acsv_after_get_post_objects
* acsv_csv_format
* acsv_csv_to_hash_array

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Screenshots ==

1. Tools Import Screen
2. The result of an import
3. History of importing

== Changelog ==

= 0.1.0 =
* Initial release.
