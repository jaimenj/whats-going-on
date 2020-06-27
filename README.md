# What's going on for WordPress, a simple WAF

What's going on for WordPress is a simple Web Application Firewall for WordPress.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-1.png)

## Main info

* Plugin URI: https://jnjsite.com/whats-going-on-for-wordpress-a-simple-waf/
* Contributors: jaimenj
* Tags: wordpress, security, waf, frontend
* Requires at least: 5.0
* Tested up to: 5.4
* Requires PHP: 7.2
* Stable tag: 1.1
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Donate link: https://www.paypal.me/jaimeninoles
* Repository URL: https://jnjsite.com/whats-going-on-for-wordpress-a-simple-waf/

## Description

A very simple plugin for WordPress that allows you to see all real requests to your website. 

It's installed in front of every PHP file, in the aplication layer of WordPress. With this plugin you can see all individual requests made to every PHP files. It works in front of every cached PHP response also. 

Plugin website: \
<a href="https://jnjsite.com/whats-going-on-for-wordpress-a-simple-waf/">https://jnjsite.com/whats-going-on-for-wordpress-a-simple-waf/</a>

* Really easy to use and very powerfull.
* Very small, it doesn't slows down the performance of your site.

## Installation

1. Install the plugin uploading files intto the directory /wp-content/plugins/whats-going-on/ like others plugins.
2. Activate the plugin into the Plugins menu in the admin panel of WordPress.
3. Got to Tools > What's going on, or click on the topbar link, and set all the configs that you want.
4. Save and flush cache of the frontend if needed.

## Uninstall

1. Deactivate the plugin into the Plugins menu in the admin panel of WordPress.
2. Delete into the Plugins menu.

All the options configured into the plugin are removed when plugin is deleted, not when plugin is deactivated. All data records of requests are stored into database in separate tables for a better performance. Those database tables are removed when plugin is deactivated. If you remove the plugin whithout deactivating it before, those tables will remain into the database.

## Frequently Asked Questions

* Can I block myself?

By default, you cannot be blocked yourself while testing the plugin. Be careful while testing and setting restrictions for the firewall. 

* I blocked myself, how can I disable it without access to the backend?

In case of emergency, you can remove the file .user.ini and the directory /wp-content/plugins/whats-going-on/.

## Upgrade Notice

Not upgraded yet.

## Screenshots

1. The main configuration view into the admin panel.

## Changelog

### 1.0
* Initial version
