# What's going on for WordPress, a simple WAF

A simple Web Application Firewall for WordPress.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-1.png)

## Main info

* Plugin URI: https://jnjsite.com/whats-going-on-for-wordpress/
* Contributors: jaimenj
* Tags: wordpress, security, waf, frontend
* Requires at least: 5.0
* Tested up to: 5.4
* Requires PHP: 7.2
* Stable tag: 1.0
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Donate link: https://www.paypal.me/jaimeninoles
* Repository URL: https://github.com/jaimenj/whats-going-on

## Description

A very simple plugin for WordPress that allows you to see all real requests to your website. It’s a WAF, a Web Application Firewall that is installed in the frontend of WordPress. It’s installed in the server with the plugin, and it checks requests from the web browsers, bots or webcrawlers to your WordPress. It executes the WAF codes before every request to PHP files of WordPress, so it also works before every request to the WordPress cache.

Plugin website: \
<a href="https://jnjsite.com/whats-going-on-for-wordpress/">https://jnjsite.com/whats-going-on-for-wordpress/</a>

## Features

* This plugins is under construction, feel free to contribute in GitHub to improve the project.
* ..
* It’s free, completely free.
* Detection and protection of DoS attacks.
* It can protect you against SQL injection and XSS attacks using your own Regexes.
* Permanent block or bypass of custom IPs, it allows you to configure IP masks and ranges with your own Regexes too.
* Log and show you section for Regexes errors.
* 404s detections.
* Possible DDoS attacks detection and notification by email.

## Installation

1. Copy the files in the directory /wp-content/plugins/whats-going-on/ like others plugins.
2. Activate the plugin in the WordPress backend menu of plugins.
3. Got to the admin section of What’s going on.
4. Install in front of every single request to PHP files, by clicking on the button that says ‘Install .user.ini’.
5. See how it works and play with it configs, fully personalizable of what to allow or not.
6. Enjoy.. 🙂

Alternative install with SSH:

1. Goto the plugins directory doing: cd /wp-content/plugins/
2. Clone the GitHub repository doing: git clone git@github.com:jaimenj/whats-going-on.git

With SSH you can stay up to date using the normal git pull command.

## Uninstall

1. Deactivate the plugin into the Plugins menu in the admin panel of WordPress.
2. Delete into the Plugins menu.

All the options configured into the plugin are removed when plugin is deleted, not when plugin is deactivated. All the database tables are removed when plugin is deactivated. So if you want to remove the plugin and all data stored, first deactivate the plugin and then remove it from the plugin admin zone into the WordPress backend.

## Frequently Asked Questions

* Can I block myself?

Yes. By default, you cannot block yourself while you are activating the plugin. But be careful while setting restrictions for the firewall. 

* I blocked myself, how can I disable it without access to the backend?

In case of emergency, you can remove the file .user.ini and the directory /wp-content/plugins/whats-going-on/. Maybe you also want to restart PHP-fpm or Apache/Nginx if WAF is cached.

## Upgrade Notice

Not upgraded yet.

## Screenshots

1. The main view into the admin panel.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-2.png)

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-3.png)

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-4.png)

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-5.png)

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-6.png)

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-7.png)

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-8.png)

## Changelog

### 0.3

* Main chart with requests, average, standard deviation and others.
* Fix Regexes saving for XSS, SQL injection and xploits detection.
* Debug zone for your own Regexes results.
* Background filling data of countries.
* More configurable option like: email of notifications, behind a proxy or not, days to store data..
* DDoS detection is working.
* Refactoring of all files, implementing MVC.

### 0.2

* Fill countries data in background.
* New section of countries started.
* Maths of DDoS detection, and chart.
* Some fixes and refactoring.

### 0.1

* Initial version
