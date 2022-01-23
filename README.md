# What's going on for WordPress, a simple firewall

A simple Web Application Firewall for WordPress.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-1.png)

## Main info

* Contributors: jaimenj
* Tags: wordpress, security, waf, firewall
* Requires at least: 5.0
* Tested up to: 5.8
* Requires PHP: 7.2
* Stable tag: 1.1
* License: MIT
* Repository URL: https://github.com/jaimenj/whats-going-on
* Plugin URI: https://jnjsite.com/whats-going-on-for-wordpress/

## Description

A very simple firewall for WordPress that allows you to see all real requests to your WordPress and protect you from Internet attacks. It’s a WAF, a Web Application Firewall that is installed in front of WordPress. It’s installed in the server with the plugin, and it checks requests from the web browsers, bots or webcrawlers to your WordPress. It executes the WAF codes before every request to PHP files of WordPress, so it also works before every request to the WordPress cache.

Plugin website: \
<a href="https://jnjsite.com/whats-going-on-for-wordpress/">https://jnjsite.com/whats-going-on-for-wordpress/</a>
WordPress plugin page: \
<a href="https://wordpress.org/plugins/whats-going-on/">https://wordpress.org/plugins/whats-going-on/</a>

## Features

* Feel free to contribute in GitHub to improve the project.
* It’s free, completely free.
* Detection and protection of DoS attacks.
* Detection and notification of possible DDoS attacks.
* It can protect you against SQL injection, XSS and Xploit attacks using your own Regexes.
* Permanent block or bypass of custom IPs, it allows you to configure IPs with your own Regexes too.
* Log and show Regex errors, for debug and improve your Regexes.
* Save payloads, all or only when match a regex.
* Block and allow countries and continents.
* 404s detections.
* Show URLs or IPs doing 404s.
* Show IPs that are doing most of the visits.
* Show URLs most visited.

## Installation

1. Search it in the WordPress admin section of plugins.
2. Click install when you find it.
3. Activate the plugin in the WordPress backend menu of plugins.
4. Got to the admin section of What’s going on.
5. Install in front of every single request to PHP files, by clicking on the button that says ‘Install .user.ini’.
6. See how it works and play with it configs, fully personalizable of what to allow or not.
7. Enjoy.. 🙂

Alternative install uploading manually the files to the server:

1. Copy the files in the directory /wp-content/plugins/whats-going-on/ like others plugins, or upload it it the admin section into a .zip file.
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

1. Uninstall .user.ini file.
2. Deactivate the plugin into the Plugins menu in the admin panel of WordPress.
3. Delete into the Plugins menu.

All the options configured into the plugin are removed when plugin is deleted, not when plugin is deactivated. All the database tables are removed when plugin is deactivated. So if you want to remove the plugin and all data stored, first deactivate the plugin and then remove it from the plugin admin zone into the WordPress backend.

## Frequently Asked Questions

* Can I block myself?

You cannot block yourself while you are activating the plugin. But you can block yourself while setting restrictions for the firewall. First read carefully and config it slowly, testing configurations and showing the results.

* I blocked myself, how can I disable it without access to the backend?

If something is broken because of this WAF, edit and empty the files /waf-going-on.php and /wp-content/plugins/whats-going-on/waf-going-on.php. Do not remove it, you can rename it and make an empty one with the same name. It will continue working, but doing nothing.

## Screenshots

1. The main view into the admin panel.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-1.png)

2. Administration of unique IPs.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-2.png)

3. Regexes administration.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-3.png)

4. DoS detection and prevention, DDoS detection and notification.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-4.png)

5. Countries and continents administration.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-5.png)

6. Last blocks reasons and times blocked.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-6.png)

7. Suspicious behaviours.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-7.png)

8. Administration of the ban rules.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-8.png)

9. Current banned IPs and rules that banned them.

![Plugin image](https://raw.githubusercontent.com/jaimenj/whats-going-on/master/assets/screenshot-9.png)

## Changelog

### v1.1

* Administration zone improved.
* Autoreload main graph, main table content.
* New section for suspicious behaviours.
* New section for ban rules administration, IA/SBR working in background in a cronjob.
* New section for IPs banned.
* Some other small fixes, styles and Javascript changes.

### v1.0

* Datatables for showing the main data.
* Improving and refactoring assets.
* Fix filling country randomizeing select query.
* Fix no URL encoding when filtering.
* Fix last blocks total and listing.

### v0.9

* Bugfix DB update system.
* Bugfix download Regexes capturing submit.

### v0.8

* Refactoring codes.
* Main chart with min line for % of request for DDoS detection.
* Bugfix JS onload undefined and onload overriding.
* Bugfix WAF file for empty options.
* Download current Regexes files.

### v0.7

* Set default Regexes buttons to protect you from URI and Payload attacks.

### v0.6

* Securize input and outputs in the backend.
* Improving install of firewall outside of WordPress plugin files.
* Config files and logs into uploads dir.
* Some checks and fixes.

### v0.5

* AJAX loading of more info.
* Payloads saving, for all or only when matching a regex.
* A better install for all subdirs of WordPress. 
* Some other bugfixes.

### v0.4

* Countries and continents section working.
* A lot of checks done, bugfix and more refactoring of codes.

### v0.3

* Main chart with requests, average, standard deviation and others.
* Fix Regexes saving for XSS, SQL injection and Xploits detection.
* Debug zone for your own Regexes results.
* Background filling data of countries.
* More configurable option like: email of notifications, behind a proxy or not, days to store data..
* DDoS detection is working.
* Refactoring of all files, implementing VC with singletones.

### v0.2

* Fill countries data in background.
* New section of countries started.
* Maths of DDoS detection, and chart.
* Some fixes and refactoring.

### v0.1

* Initial version.
