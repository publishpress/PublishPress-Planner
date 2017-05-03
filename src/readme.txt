=== PublishPress - WordPress Editorial Calendar and Team Publishing by PublishPress ===
Contributors: PressShack
Author: PressShack, PublishPress
Author URI: https://pressshack.com
Tags: PressShack, PublishPress, publish, press, publish press, press publish, publish flow, workflow, editorial, edit flow, newsroom, management, journalism, post status, custom status, notifications, email, comments, editorial comments, usergroups, calendars, editorial calendar, content overview
Requires at least: 4.4
Tested up to: 4.7
Stable tag: 1.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PublishPress is the essential plugin for any site with multiple writers.
PublishPress gives you the tools you need to manage content on your busy WordPress site.

== Description ==

If your WordPress site has more than one content creator, then you need PublishPress.

PublishPress is a plugin with several tools that helps your team stay on track:

* Use the Calendar and Content Overview get a clear picture of all your planned and published content.
* You can create Comments and Notifications to leave feedback and keep your team in the loop.
* You can select Metadata to make sure all your content meets your team’s standards
* Utilize Custom Statuses so that WordPress matches your team’s workflow.

Current features:

* [Calendar](https://pressshack.com/publishpress/docs/calendar/) - The calendar lets you see your posts over a customizable date range.
* [Custom Statuses](https://pressshack.com/publishpress/docs/custom-statuses/) - Create custom post statuses to define the stages of your publishing workflow.
* [Comments](https://pressshack.com/publishpress/docs/editorial-comments/) - Share internal notes with your team.
* [Metadata](https://pressshack.com/publishpress/docs/editorial-metadata/) - With Metadata you can customize the extra data that’s tracked for your content.
* [Notifications](https://pressshack.com/publishpress/docs/notifications/) - With email notifications, you can keep everyone updated about what’s happening with your content.
* [Content Overview](https://pressshack.com/publishpress/docs/content-overview/) - A single screen that shows the publication status of all your content.
* [User Groups](https://pressshack.com/publishpress/docs/user-groups/) - Organize your users into groups who can take different roles in your publishing workflow.

**PublishPress Pro Add-ons Coming soon:**

* Content Checklist - This is a pre-publishing checklist that allows WordPress teams to specifiy tasks that must be completed before posts and pages are published.
* Slack support for PublishPress - Due in mid-2017, this add-on integrates PublishPress with Slack, so you can get comment and status change notifications directly on Slack.
* Pre-publishing checklists for WooCommerce - This add-on allows WooCommerce teams to define tasks that must be complete before products are published.
* Multiple authors support for PublishPress - Due in mid-2017, this add-on allows you choose multiple authors for a single post. This add-on is ideal for teams who write collabratively.
* Multi-site and Multiple support for PublishPress - Due in mid-late 2017, this add-on enables PublishPress to support multiple WordPress sites. Write on one site, but publish to many sites.
* Zapier support for PublishPress - Due in mid-2017, this add-on integrates PublishPress with Zapier, so you can send comment and status changes notifications directly to Zapier.
* Advanced permissions for PublishPress - Due in late-2017, this add-on allows you to control which users can complete certain tasks, such as publishing content.


PublishPress is based on Edit Flow. Edit Flow is produced by Daniel Bachhuber, Mo Jangda, and Scott Bressler, with special help from Andrew Spittle and Andrew Witherspoon.

You can easily migrate from Edit Flow into PublishPress importing all the data and settings automatically.

== Screenshots ==

1. With Calendar you can see your posts over a customizable date range
2. Content Overview is a single screen that shows the publication status of all your content
3. In the Admin Page you can have access too all features and settings
4. Create Custom Statuses to define the stages of your publishing
5. Organize your users into groups who can take different roles in your publishing workflow
6. With Metadata your can customize the extra data that's tracked for your content

== Installation ==
There're two ways to install PublishPress plugin:

**Through your WordPress site's admin**

1. Go to your site's admin page;
2. Access the "Plugins" page;
3. Click on the "Add New" button;
4. Search for "PublishPress";
5. Install PublishPress plugin;
6. Activate the PublishPress plugin.

**Manually uploading the plugin to your repository**

1. Download the PublishPress plugin zip file;
2. Upload the plugin to your site's repository under the *"/wp-content/plugins/"* directory;
3. Go to your site's admin page;
4. Access the "Plugins" page;
5. Activate the PublishPress plugin.

== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

= [1.3.2] - 2017-05-04 =
* Fixed:
* Fixed the list of Add-ons, to list correctly the installation and activation status
* Fixed selector for the active admin menu
* Fixed readme.txt file with better title, tags and description

= [1.3.1] - 2017-05-02 =
* Fixed:
* Fixed the calendar quick-edit popup
* Fixed minor issue in the code style

* Added:
* Added WooCommerce add-on to the add-ons tab

* Changed:
* Removed message after update settings
* Updated name of add-ons in the settings tab
* Improved content from the readme file

= [1.3.0] - 2017-04-27 =
* Fixed:
* Fixed popup for items created as quick post
* Fixed typos

* Added:
* Added integration with Freemius for feedback and contact form
* Added filters and actions allowing to extend modules with add-ons
* Added default modal window scripts and styles for add-ons
* Added new tab to showcase the add-ons

* Changed:
* Changed code standards for WordPress
* Cleaned up the metadata removing default registers for "Needs photo" and "Word Count"
* Improved twig files removing hardcoded strings

= [1.2.2] - 2017-03-16 =
* Changed:
* Add icon to the print link on the Overview screen
* Update the language files

= [1.2.1] - 2017-03-15 =
* Changed:
* Better label for Comments metadata box
* Set Calendar Subscriptions enabled by default
* Set Always Show Dropdown enabled by default on custom statuses
* Add small notes to some tabs
* Update POT file

* Fixed:
* Fixed empty messages while deleting custom statuses, metadata and user groups
* Fixed link to redirect after the EditFlow migration

= [1.2.0] - 2017-03-15 =
* Changed:
* Better style for the calendar
* Click anywhere on the calendar cell to create content, intead show a button
* Extends the drag-and-drop feature to custom statuses
* Minor improvements on the code

* Added:
* Allow to create multiple types of content on the calendar

= [1.1.0] - 2017-03-07 =
* Changed:
* Complete rebranding to PublishPress and PressShack
* Clean up on the UI
* Move sub-pages to a common settings page
* Clean up on the text
* Refactor Story Budget to Content Overview
* Move PublishPress menu to after Comments menu

= [1.0.5] - 2017-02-16 =
* Changed:
* Update version to trigger a new release fixing SVN issues on the last release

= [1.0.4] - 2017-02-15 =
* Changed:
* Cleanup on the code
* Cleanup on the admin interface
* Add PressShack logo to the top of admin pages
* Set minimum WordPress version to 4.4
* Set minimum PHP version to 5.4
* Move the Settings menu item to the main PublishPress menu
* Minor improvement to the icons

* Fixed:
* Update language strings and some links
* Fix bug on editorial comments box in the post form

= [1.0.3] - 2017-02-01 =
* Changed:
* Update language .mo files

= [1.0.2] - 2017-02-01 =
* Changed:
* Update plugin's description

= [1.0.1] - 2017-02-01 =
* Changed:
* Update plugin's description
* Update language strings

= [1.0.0] - 2017-02-01 =
* Changed:
* Renamed to PublishPress
* Rebrand for Joomlashack