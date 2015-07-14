=== WP Staging - Create a staging site can be easy === 

Author URL: https://www.wp-staging.net
Plugin URL: https://www.wp-staging.net
Contributors: ReneHermi
Donate link: https://www.wp-staging.net
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: staging, development, cloning, admin, page, content, plugin, media, backup, test, testing, sandbox
Requires at least: 3.6+
Tested up to: 4.2.2
Stable tag: 0.9

WP Staging creates independent staging and development sites that are only available to administrators.

== Description == 

> #### WP Staging
> This plugin allows you to create an staging or development environment in seconds* <br />
> It creates a clone of your website into a subfolder of your current WordPress installation with an entire copy of your database. 
> This sounds pretty simple and yes it is! All the hard and time consumpting DB and file copy stuff will be done in the background.
>
> I created this plugin because all other solutions are way too complex, overloaded with dozens of options or are having server requirements which are not available on most shared hosting solutions.
> All these reasons prevent user from testing new plugins and updates first before installing them on their live website, so its time to release a plugin which has the potential to be merged into everyone´s wordpress workflow.
>
><p><small><em>* Time of creation depends on size of your database and file size</em></small></p>
>

WP Staging can prevent your website from beeing broken or unavailable because of installing untested plugin updates! 

Change your workflow to the following:

<li> 1. Create a staging website with all your latest production data</li>
<li> 2. Update or install your plugins</li>
<li> 3. Test them carefully</li>
<li> 4. If they are working without issues you are save to install these tested plugins in your live website</li>


<h3> Why should you use a staging website? </h3>

Plugin updates and theme customizations should be tested on a staging platform first and its recommended to have the staging platforme on the same server like the production one.
Everytime you run a plugin update or plan to install a new one, it is a necessary task to check first the modifications on a clone of your production website.
This makes sure that any modifications is  working on your website without throwing unexpected errors or preventing your site from loading. (Better known as the wordpress blank page error)

Unfortunately, testing a plugin update before installing it in live environment isn´t done very often by most user because existing staging solutions are too complex and need a lot of time for creation of a 
up-to-date copy of your website.

Some people are also afraid of installing plugins updates because they follow the rule "never touch a running system" with having in mind that untested updates are increasing the risk of breaking their site.
I totally understand this and i am guilty as well here, but unfortunately this leads to one of the main reasons why WordPress installations are often outdated, not updated at all and unsecure due to this non-update behavior.

<strong> I think its time to change this, so i created "WP Staging" </strong>

<h3> Can´t i just use my local wordpress deveolopment copy for testing? </h3>

No, you can´t! If your local hardware and software environment is not a 100% exact clone of your production server there is no guarantee that every aspect 
of your local copy is working on your live website exactely as aspected. 
There are some obvious things like differences in your php and server version or the type of your server like Nginx or Apache. 
But there are less differences things like the amount of ram, the cpu performance, the settings of your server and php config and dozens of other possible adjustments 
which could lead to conflicts between the expected staging and production behavior.

= Main Features =

* Creates a staging website with a few clicks
* Access to the staging site will be granted only for administrators
* Admin bar notice reflects that you are working on a staging site
* No access to staging site for search engines
* Extensive logging if things goes wrong. (Find them in wp-content/wp-staging/logs)

= What does not work or is not tested? =

* Staging of wordpress multisites (not tested)
* Staging on windows server (not tested but should work)

= I need you feedback =
This plugin was made to work on even the smallest shared webhosting package but i am limited in testing this only on a handful of different server so i need your help:
Please open a [support request](https://wordpress.org/support/plugin/wp-staging/ "support request") and describe your problem exactely. In wp-content/wp-staging/logs you find extended logfiles. Have a look there and let me know the error-thrown lines.


= Important =

Per default the staging site will have permalinks disabled because the staging site will be cloned into a subfolder and regular permalinks are not working 
without doing changes to your .htaccess or nginx.conf than.
In the majority of cases this is abolutely fine for a staging platform and you still will be able to test new plugins and do some theme changes on your staging platform. 
If you need the same permalink stucture on your staging platform as you have in your prodcution website you have to create a custom .htaccess for apache webserver 
or to adjust your nginx.conf.

We will tell you later here how to do so if you are not already knowing this!
 
= How to install and setup? =
Install it via the admin dashboard and to 'Plugins', click 'Add New' and search the plugins for 'Staging'. Install the plugin with 'Install Now'.
After installation goto the settings page 'Staging' and do your adjustments there.


== Frequently Asked Questions ==


== Official Site ==


== Installation ==
1. Download the file "wp-staging" , unzip and place it in your wp-content/plugins/wp-staging folder. You can alternatively upload and install it via the WordPress plugin backend.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Start Plugins->Staging

== Screenshots ==

1. Create a staging website


== Changelog ==

= 0.9 =
* New: Release

== Upgrade Notice ==

= 0.0 =
0.9 <strong> Initial release</strong>