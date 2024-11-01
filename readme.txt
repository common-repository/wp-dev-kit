=== WordPress Development Kit Plugin ===
Plugin Name:  WordPress Development Kit Plugin
Contributors: charlestonsw
Donate link: https://wordpress.storelocatorplus.com/product/wordpress-development-kit-plugin/
Tags: WordPress, development, plugins
Requires at least: 4.4
Tested up to: 4.7
Stable tag: 4.7.1

Add support for premium plugins and add-on packs to your WooCommerce offerings.  Includes subscription validation.

== Description ==

This plugin provides a premium plugin update and listing system as well as a subscription validation mechanism for plugin authors.
I use it for the "latest versions" listings, download listings, and the inline update system for the add-on packs at [Store Locator Plus](https://wordpress.storelocatorplus.com).
The plugin will list all the latest versions and serve updates.
If you enter a WooCommerce subscription product ID and enter plugin slugs on the settings page, those listed slugs will only install updates if the incoming query can validate the uid and sid provided.

Part of the free WordPress Development Kit that I have been putting together to help automate the dozen-plus free and premium plugins that I have been creating over the years.
This is one more cog in the system that helps cut down on manual page updates that show things like change logs, latest news, and other details the customer base finds relevant or useful.
You can learn more via my [WordPress Development Kit articles](http://www.charlestonsw.com/tag/wordpress+development+kit/).
This plugin will do more as my WordPress Development Kit and related processes are refined.
Special thanks to [WordCamp Atlanta](http://central.wordcamp.org/) for planting the seeds that grew into this project.
If you've not been to a local WordCamp you should attend one soon.

= Usage =

Set the location of your production plugins.json file via the Settings/WP Dev Kit menu item in the admin panel of your site.
You can learn more about the plugins.json format from the [WP Dev Kit repository on Bitbucket](https://bitbucket.org/lance_cleveland/wp-dev-kit/wiki/Home).
Yup, it is also GPL and a public repo so go ahead and check it out.

The plugins.json is a simple text file that lists your plugin slugs and version information.
Put the plugins.json plus the readme.txt file from your plugin, renamed <slug>_readme.txt into a directory on your server.
Now use the shortcodes noted below and the plugin information from your readme will be rendered in your page when using details mode.
List mode will list all of the plugins that appear in your plugins.json file.

Go to a page or post and use the wpdevkit shortcode.

**Actions**
[wpdevkit action="list"] to list out the production plugins metadata in a stylized format.
[wpdevkit action="filelist"] to create a download list of files
[wpdevkit action="show_subscription"] output a user ID and subscription key for users logged in on your plugin sales site.

**Styles (default: formatted)**
[wpdevkit action='list' style='formatted'] list the details in an HTML formatted layout
[wpdevkit action="list" style="raw"] to list out the production plugins metadata in a print_r array format (ugly mode).

**Types (default: basic)**
[wpdevkit action='list' type='basic'] list basic details = version, updated, directory, wp versions
[wpdevkit action='list' type='detailed'] list all details = version, updated, directory, wp versions, description

**Slug (default: none = list ALL)**
[wpdevkit action="list" slug="wordpress-dev-kit-plugin"] to list out the production metadata in a stylized format for a single product.

**Target (default: production)**
[wpdevkit action="list" slug="wordpress-dev-kit-plugin" target="production"] to list out the production metadata for production builds.
[wpdevkit action="list" slug="wordpress-dev-kit-plugin" target="prerelease"] to list out the production metadata for prerelease builds.

= Example =

You can see the current iteration of [the plugin in action on my site](http://www.charlestonsw.com/support/documentation/store-locator-plus/release-notes/release-notes-4-0/).
The Current Releases section is updated automatically whenever I use my WP Dev Kit to publish a release to the public WordPress Plugin Directory or when premium add-ons are published to my site.
WP Dev Kit uses Grunt tasks and the plugins.json file to automate my production.
This plugin ties into that automated production system to create things like the WordPress shortcodes above to create formatted output on my site with no direct editing required.

= Updater System =

Includes premium plugin update system that can be queried by any premium plugins using the WordPress pre_set_site_transient_update_plugins and plugins_api hooks.
This provides inline admin-panel updates to premium add-on packs.
Your premium add-on plugins must use a standard updates class that uses these built-in WordPress hooks to query your website that is running this WordPress Dev Kit plugin.
If it is implemented properly, your premium add-on packs will query this plugin, which reads the JSON file data stored on your WordPress server, and sends back the most current plugin version data.
If a new version of your premium plugin is available this plugin can also handle serving the files from the specified production directory.
You must code your premium plugins accordingly and keep your plugins.json and zip files updated on the specified production directory (a WP Dev Kit setting) on your server.
The query URL for the update sytem is your website's Admin AJAX URL, for example a VVV install using the trunk install with this plugin would have a URL of http://local.wordpress-trunk.dev/wp-admin/admin-ajax.php with an action of wpdk_updater.

Here is a code snippet the utilizes the target release (production/prerelease), the subscription validation system, and records other attributes for all update requests.
        $this->update_request_path = $this->base_path .
            '?action=wpdk_updater' .
            '&surl=' . urlencode(site_url()) .
            '&uid=' . $this->slplus->options_nojs['premium_user_id'] .
            '&sid=' . $this->slplus->options_nojs['premium_subscription_id'] .
			'&target=' . $this->slplus->options_nojs['build_target'] .
            '&slug='.$slug .
            '&current_version=' . $version;
    }

= Logs Update Requests =

All version checks are logged in the server in an update_history table kept outside of the core WordPress tables.

= Validate Woocommerce Subscriptions =

Version 1.0 adds the ability to specify a Woocommerce subscription product ID and a list of plugin slugs to be validated against an active subscription.
The update request must send a UID (user ID) and SID (subscription ID) parameter to the update system.
The UID and SID will be validated against known active subscriptions and only allow an update file to be retrieved if the specified UID has a valid subscription.

= Related Links =

* [Other CSA Plugins](http://profiles.wordpress.org/charlestonsw/)

== Installation ==

= Requirements =

* Wordpress: 4.4
* PHP: 5.2.4

== Frequently Asked Questions ==

= What are the terms of the license? =

Like ALL of my plugins, including my premium add-ons, the license is GPL.
You get the code, feel free to modify it as you wish.
I prefer customers pay me because they like what I do and want to support my efforts to bring useful software to market.
Learn more on the  License Terms](https://wordpress.storelocatorplus.com/products/general-eula/) page.

= What does this do? =

This is the plugin I use to drive [Store Locator Plus](https://wordpress.storelocatorplus.com) premium add-on pack information and updates.
I upload a plugins.json file and the readme.txt files for each of my add-on packs to a directory on my web server.
This plugin allows me to easily list the latest production and prerelease versions of my plugins, provide a list of files
with download links on my protected subscriber-ony pages, and ties into my add-on packs inline WordPress update system.

= Did you say inline WordPress updates for Premium plugins? =

Yes I did.   I put the zip file on my server and make sure the plugins.json file is kept updated.
My plugins hook into the WordPress updates system to make sure when WordPress goes looking for plugin updates my server
is queried via this plugin.    A plugin that updates plugins.

= Do you have example code? =

Sure do.  Check out my linux-based grunt production system, also called ["WordPress Development Kit" on Bitbucket](https://bitbucket.org/lance_cleveland/wp-dev-kit/wiki/Home),
that I use to push zip files, the readme.text files, and plugin.json files to my servers.  Take a look at the ./grunt/plugins.json file for the plugin data example.

You can take a look at my [Store Locator Plus](https://wordpress.org/plugins/store-locator-le/) plugin and check out the include/class.update.php code.  This is the base class that all of my premium add-ons use to query the update system in this plugin.

== Changelog ==

= 4.6.4 =

Fix the missing content-length when sending the archive files.   Allows proxy servers to be smarter and not corrupt the zip file headers.

= 4.6.3 =

Fix the filelist shortcode for prerelease data while retaining the WooCommerce show download file versions.

= 4.6.2 =

* Update for WooCommerce 2.0 subscriptions compatibility.
* Show download version based on file download path.  Must use relative file names not URLs and must match your plugins.json deployment info.

= 4.6.1 =

* Add download versions to the WooCommerce account downloads.

= 4.5.2 =

* Add REST route for pulling update history domains.
* Add option to restrict some of the REST API endpoints to a specific list of IP addresses.

= 4.5 =

* Only add history records if new $_REQUEST.
* Update timestamp only if $_REQUEST has not changed.

= 1.6 =

* Pass SID and UID on update download links.

= 1.5 =

* Changed REST namespace from wpdk to wp-dev-kit to follow WP best practices.

= 1.4 =

* Log WooCommerce subscription validation errors.

= 1.3.1 =

* Add lastupdated index to the history table.
* Tested with WP 4.5

= 1.2 =

* Add REST endpoints for reporting data.
* Add a dashboard report for the lastest <n> update requests.

= 1.1 =

* Base object refactoring for more efficient coding.
* Switch to https URL references for StoreLocatorPlus.com website.

= 1.0.04 =

* Skip readme on file and version update requests.

= 1.0.03 =

* Fix the subscription ID output.

= 1.0.02 =

* Return the Woo subscription key for the show_subscriptions action.
* Add option to validate specified product slugs against subscriptions before sending download files.

= 1.0.01 =

* Update the activation version check to see if the options are empty or not set.
* Add more informational messages when subscriptions are not active.

= 1.0 =

* Fix non-array foreach issue.
* Wrap URL in last 10 requests in esc_html().