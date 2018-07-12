=== PublishPress helps WordPress teams create great content ===
Contributors: publishpress, andergmartins, stevejburge, pressshack
Author: PublishPress, PressShack
Author URI: https://publishpress.com
Tags: Content Calendar, Editorial Calendar, workflow, checklist, permissions
Requires at least: 4.6
Requires PHP: 5.4
Tested up to: 4.9.5
Stable tag: 1.14.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PublishPress is the plugin for WordPress teams. Your team gets an editorial calendar, flexible permissions and notification workflows.

== Description ==

The slogan of PublishPress is “WordPress for Teams”.

PublishPress is the essential plugin for any WordPress site with multiple team members.

PublishPress has multiple tools that help your team stay organized when creating content:

* Use the [Editorial Calendar](https://publishpress.com/docs/calendar/) and [Content Overview](https://publishpress.com/docs/content-overview/) to get a clear picture of all your planned and published content.
* You can write [Comments](https://publishpress.com/docs/editorial-comments/) to leave feedback.
* Set up [Notification Workflows](https://publishpress.com/docs/notifications/) to keep your team up-to-date with what’s happening.
* You can add [Metadata](https://publishpress.com/docs/editorial-metadata/) to give your team extra information about each post.
* Create [Custom Statuses](https://publishpress.com/docs/custom-statuses/) so that WordPress matches your team’s workflow.

Interested in finding out more?

[Click here to try a free demo of PublishPress](https://publishpress.com/demo/).
[Check out premium add-ons](https://publishpress.com/pricing/) for access to all the PublishPress features.


= WHO SHOULD USE PUBLISHPRESS? =

PublishPress is ideal for WordPress sites that have content teams. With PublishPress, you can collaborate much more effectively. This makes PublishPress a great solution for any site with multiple users. PublishPress is often used by companies and non-profits, universities and schools, plus by magazines, newspapers and blogs.

= PREMIUM ADD-ONS FOR PUBLISHPRESS =

* [Content Checklist](https://publishpress.com/addons/content-checklist/): Set high standards for all your published content
* [Multiple Authors](https://publishpress.com/addons/multiple-authors-publishpress/): Easily assign multiple authors to one content item
* [Permissions](https://publishpress.com/addons/publishpress-permissions/): Control who gets the click the “Publish” button
* [WooCommerce Checklist](https://publishpress.com/addons/woocommerce-checklist/): Set high standards for all your WooCommerce products
* [Slack Notifications](https://publishpress.com/addons/publishpress-slack/): Get Slack updates for all content changes

[Check out premium add-ons](https://publishpress.com/pricing/) for access to all the PublishPress features.

= EDITORIAL CALENDAR =

The calendar gives you a powerful overview of your publishing schedule. Using the Editorial Calendar, you can easily see when content is planned, and when it was published. You can also drag-and-drop content to a new publication date. By default, you see all the WordPress content you have planned for the next six weeks. If you need to drill down, you can filter the calendar by post status, categories, users or post types.

* [Click here for more on the PublishPress Editorial Calendar](https://publishpress.com/docs/calendar/)

= NOTIFICATION WORKFLOWS =

Notifications keep you and your team up to date on changes to important content. Users can be subscribed to notifications for any post, either individually or by selecting user groups. PublishPress allows you to create powerful notification workflows based on post types, categories, status changes and much more.

* [Click here for more on PublishPress Notifications](https://publishpress.com/docs/notifications/)

= CONTENT OVERVIEW =

The Content Overview screen is a companion to the Calendar screen. Whereas the Calendar allows you to see content organized by dates, Content Overview allows you to drill down and see content organized by status, categories, or users. In the top-right corner is a “Print” button. Click this to get a printable overview of all your planned content.

* [Click here for more on the PublishPress Content Overview](https://publishpress.com/docs/content-overview/)

= CUSTOM STATUSES =

This feature allows you to create custom post statuses such as “In Progress” or “Pending Review”. You can define statuses to match the stages of your team’s publishing workflow.

By default, WordPress provides you with a very limited set of status choices: Draft and Pending Review. With PublishPress you’ll see a much wider range of options. When you first install PublishPress, you’ll see these extra statuses: Pitch, Assigned, and In Progress. You can then create more custom post statuses to define the stages of your publishing workflow.

* [Click here for more on the PublishPress Custom Statuses](https://publishpress.com/docs/custom-statuses/)

= EDITORIAL COMMENTS =

A very important feature in PublishPress is commenting. You can leave comments under each post you write. This is a private conversation between writers and editors and allows you to discuss what needs to be changed before publication.

* [Click here for more on PublishPress Editorial Comments](https://publishpress.com/docs/editorial-comments/)

= EDITORIAL METADATA =

Metadata enabled you to keep track of important requirements for your content. This feature allows you to create fields and store information about content items.

By default, PublishPress provide 4 examples of metadata, but you can add your own to meet your team’s needs.

* [Click here for more on PublishPress Editorial Metadata](https://publishpress.com/docs/editorial-metadata/)

= CUSTOM USER ROLES =

For larger organizations, user roles can keep your publishing workflows organized and make sure notifications are sent to the correct people.

To find the role settings, go to the PublishPress link in your WordPress admin area, and click the “Roles” link.

* [Click here for more on PublishPress Custom User Roles](https://publishpress.com/docs/roles/)

= IMPORTING FROM EDITFLOW =

PublishPress is based on the EditFlow plugin. It is easy for Edit Flow users to import your data and settings.

* [Click here for full instructions on moving from Edit Flow to PublishPress](https://publishpress.com/docs/migrate/)

= I FOUND A BUG, OR WANT TO CONTRIBUTE CODE =

Great! We’d love to hear from you! PublishPress [is available on Github](https://github.com/OSTraining/PublishPress), and we welcome contributions from everyone.

== Frequently Asked Questions ==

= Where Can I Get Support? =

You can ask for help via [the PublishPress contact form](https://publishpress.com/contact/).

= Do I Need Coding Skills to Use PublishPress? =

Not at all. You can set up everything your team needs without any coding knowledge. We made it super easy.

== Screenshots ==

1. With Calendar you can see your posts over a customizable date range

== Installation ==

You can install PublishPress through your WordPress admin area:

1. Access the “Plugins” page.
1. Click on the “Add New” button.
1. Search for “PublishPress”.
1. Install the PublishPress plugin.
1. Activate the PublishPress plugin.

== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

= [1.14.1] - 2018-07-12 =

*Fixed:*

* Fixed a PHP warning when we receive an array as receiver for notifications;
* Fixed notifications when roles are selected as receivers;

= [1.14.0] - 2018-06-12 =

*Fixed:*

* Fixed the menu structure when the calendar and notification workflow modules are deactivated;
* Fixed saving post when you remove all the notification receivers. Before the fix it wasn't removing the receivers if you remove all, only if you left at least one;

*Changed:*

* Increased the timeout for duplicated notifications to 10 minutes and added filter to customize the timeout;
* Added chosen JS library for add-ons;
* Changed the filter publishpress_notif_async_timestamp to send the post id as the 3rd param;

*Added:*

* Added a new action (action_enqueue_notification) after enqueuing  notifications;

= [1.13.0] - 2018-05-16 =

*Added:*

* Added new shortcodes for displaying author's data in the notification content: author_display_name, author_email, author_login;

*Changed:*

* Improved some help texts;
* Updated language files;

= [1.12.1] - 2018-05-09 =

*Fixed:*

* Fixed PHP strict warning about trait and a redefined property;
* Fixed duplicated notifications on some scenarios;

*Changed:*

* Increased the priority of the hook user_register to have the multiple roles loaded and available for add-ons;

= [1.12.0] - 2018-04-18 =

*Added:*

* Added option to display the edit link of the post in a notification's message;

*Fixed:*

* Fixed the warning about migration of legacy data in fresh installs;
* Fixed the post link on notifications;

*Changed:*

* Improved async notifications module for allowing extending workflows with add-ons (specially Reminders);

= [1.11.4] - 2018-04-05 =

*Fixed:*

* Fixed error 500 while saving users;
* Fixed Preview and Publish button for posts;
* Fixed compatibility with Forum Role field of bbPress;
* Fixed start date for the calendar to display current week if a custom date is not set;

= [1.11.3] - 2018-03-20 =

*Fixed:*

* Fixed roles editing form, denying to remove current user from the administrator role;
* Fixed error in the dashboard widget "My Content Notifications"
* Fixed default permissions for administrators for being able to see the Roles menu item;

*Changed:*

* Removed option to delete the administrator role;
* Improved some text;

= [1.11.2] - 2018-03-19 =

*Fixed:*

* Fixed migration of following users and user groups in notifications;

= [1.11.1] - 2018-03-13 =

*Fixed:*

* Fixed backward compatibility with legacy PublishPress Permissions;

= [1.11.0] - 2018-03-13 =

*Fixed:*

* Fixed workflows and notifications for new posts;
* Fixed issue when installed from composer, related to the vendor dir not being found;
* Fixed style for icons in the buttons of the popup for iCal subscriptions in the calendar;
* Fixed hidden submenus adding basic capabilities after installing for the first time;

*Changed:*

* Changed the workflow form, adding all fields as required;
* Removed support for User Groups - they are deprecated in favor of Roles, in PublishPress;
* Moved Notifications metabox to the sidebar with high priority for posts;
* Cleaned up UI removing logo from the title in the admin;

*Added:*

* Added new "From" status: New. Allowing to create workflows specifically from new posts;
* Added new submenu for managing Roles;
* Added new receiver option for notification workflows to reach Roles;
* Added support for multiple Roles per user. A new field is displayed in the user's profile allowing to select multiple roles;

= [1.10.0] - 2018-02-21 =

*Added:*

* Added async notifications using WP Cron;
* Added form to configure filters for the calendar subscription link;
* Added option to the notifications to subscribe or not the author and current user by default;
* Allow to configure custom slugs for statuses, making easy to fix issues with titles and UTF-8 chars;

*Fixed:*

* Fixed the issue where posts with "Pending" status where hidden;
* Fixed the subscription link for the calendar and add download link;
* Fixed issue where custom post types where not recognized by some modules;
* Fixed the publishing date for unpublished posts. Now it is not changed when saved so unscheduled posts keep ready for publishing immediately;

= [1.9.8] - 2018-02-06 =

*Fixed:*

* Fixed icon styling issues in non-default themes;
* Fixed missed submenus after activating the plugin for the first time;

*Changed:*

* Removed Date column from the notification workflows list;

= [1.9.7] - 2018-02-01 =

*Fixed:*

* Fixed broken menu;

= [1.9.6] - 2018-02-01 =

*Fixed:*

* Fixed PublishPress menu ordering;
* Fixed last release, which changed version but didn't include all the changes;

= [1.9.5] - 2018-01-31 =

*Fixed:*

* Fixed fatal error when saving posts and triggering notification;

*Changed:*

* Removed Freemius integration and contact form;

= [1.9.4] - 2018-01-25 =

*Fixed:*

* Fixed the filters on the notification workflow form, allowing to creating new notifications again;
* Fixed PHP warning when user does not have permission to see the menu;
* Fixed a typo removing a PHP warning;
* Fixed the status description on the date column for posts;
* Fixed notifications for users who selected "Notify me" for the content;
* Fixed string "Draft" and "Pending" after the post title in the post lists;

*Added:*

* Added a new optional receiver to the notification workflow form, "Users who selected Notify Me for the content";

= [1.9.3] - 2018-01-18 =

*Fixed:*

* Fixed file permissions;
* Fixed blank status dropdown on quick and build edit;
* Fixed modified date for scheduled posts;
* Fixed suppressed warning about undefined offset;

*Changed:*

* Rebranded for PublishPress;
* Added footer to the addons page;

= [1.9.2] - 2017-12-14 =

*Fixed:*

* Fixed view link for published posts in the list of posts. It was displaying "preview" instead of "view";
* Fixed CSS loader to only load it in the admin pages;

= [1.9.1] - 2017-11-10 =

*Fixed:*

* Fixed compatibility with the plugin Custom Permalink;
* Fixed PHP fatal error after activate plugin on fresh installs;

*Changed:*

* Updated required PHP version to 5.4;

= [1.9.0] - 2017-11-09 =

*Changed:*

* Improved the form of notification workflows adding consistency between all the filters and fields;
* Added search box for each list field on the notification workflow form;
* Updated the Post Type and Category filters for notification workflows. If not selected, they disable the filter and all the content would be picked;

= [1.8.1] - 2017-11-01 =

*Changed:*

* Updated the label for the setting field “Allow ‘following’ on these post types” to make it clear;
* Updated the placeholder for the “Email from” field to display the default values instead of a static label;
* Added text domain into the plugin header;

*Fixed:*

* Fixed missed “published” status on filters in notifications if “custom status” feature is disabled;
* Fixed PHP warning when some features are disabled;

= [1.8.0] - 2017-10-26 =

*Fixed:*

* Fixed duplicated "Scheduled" status in the dashboard widget;

*Changed:*

* Improved style for the dashboard widget;
* Updated form of notification workflow moving some fields to a new column;
* Updated the POT file;

*Added:*

* Added a setting field to configure the "email from" information for email notifications;

= [1.7.5] - 2017-10-11 =

*Fixed:*

* Fixed the empty pubDate for posts with custom status;
* Fixed the title of the Notification Workflows module in the settings panel;
* Fixed the verification of the notifications module statue before create content type and display the menu;
* Fixed the drag and drop of metadata items in the settings tab;

*Changed:*

* Removed quick edit option from the metadata tab;
* Updated the Twig library to v1.35.0;
* Adjusted filters on notification workflows to only consider selected items. Before that, if no post type, category, or statuses where selected, all the items would be considered selected.

= [1.7.4] - 2017-09-13 =

*Fixed:*

* Fixed the button Submit for Review which was hidden for contributors, or users who can't publish;
* Fixed empty permalink when publishing posts, or saving new posts with empty title;

= [1.7.3] - 2017-09-04 =

*Added:*

* Added shortcode for displaying the date and time set for the content on the notifications;
* Added shortcode for displaying the old and new statuses on the notifications;

*Fixed:*

* Fixed the save event filters when no event is selected;
* Fixed the separator param on shortcodes for notifications;
* Fixed notifications avoiding messages for auto-drafts;

*Changed:*

* Improved the column titles on the workflows list to match the labels from the form;
* Replaced the string 'Follow' to 'Notify' on the UI;
* Removed the filter for months on the Workflows list;

= [1.7.2] - 2017-08-31 =

*Fixed:*

* Fixed default notification workflows to avoid notifications on every post save, but only when the content transition to Published;
* Fixed the message after delete workflows;
* Fixed the notifications workflows to ignore autosaves;
* Fixed post type options in the calendar settings, selecting the Post post type by default, if nothing is selected - Displaying a warning;

= [1.7.1] - 2017-08-30 =

*Fixed:*

* Fixed bug which blocked pages for guests after installing;
* Fixed error 500 on saving the calendar settings;
* Fixed bug on saving modules settings where changes were not being saved;
* Fixed default color and icon for existent statuses;

*Changed:*

* Improved default colors for custom statuses;

= [1.7.0] - 2017-08-29 =

*Added:*

* Added notification workflows for more control over notifications with custom text and more;
* Added special fields on the user profile to configure where to receive the new notifications;
* Added the option for users to mute notifications from specific workflows;
* Allow to customize color and icon for all post statuses in the calendar;
* Allow to send emails in HTML format for notifications;

*Fixed:*

* Fixed spacing for content overview;
* Fixed compatibility with Capsman Enhanced and Press Permit, refactoring the action pp_admin_menu to publishpress_admin_menu;
* Fixed the creation and drag-and-drop of content on the calendar to set the scheduled date. Moving the content, if published or scheduled, now updates the status (and icon) according to the current time;
* Fixed the issue with notifications disappearing from the admin after a few seconds;
* Fixed empty filter for tags in the calendar if there are no tags;

*Changed:*

* Filter content in the calendar page on the change event;
* Filter content in content overview page on the change event;
* Allow to drag-and-drop published content on the calendar;
* Improved responsive support for calendar page;
* Improved responsive support for content overview page;
* Improved users and user groups layout;
* Changed the label of the setting for the notification module adjusting to the new notification workflows;
* Removed field "Always notify the blog admin" from the notification settings. This is now set on the notification workflow;

= [1.6.1] - 2017-07-27 =

*Changed:*

* Updated the add-ons page for supporting the new add-on: Multiple Authors

*Fixed:*

* Fixed the icon size for calendar

= [1.6.0] - 2017-07-12 =

*Fixed:*

* Fixed an error message after install after redirect to the calendar;

*Changed:*

* Moved the Add-ons tab to a menu item;
* Updated internal method to show post type settings fields for add-ons;

= [1.5.1] - 2017-06-28 =

*Fixed:*

* Fixes required capability to see the calendar

= [1.5.0] - 2017-06-27 =

*Changed:*

* Moved Calendar to the PublishPress menu
* Moved Content Overview to the PublishPress menu
* Changed the Calendar page as the main page
* Renamed story-budget module to content-overview
* Removed the Featured module and tab in settings
* Moved the General tab as the first in the settings page
* Changed calendar settings enabling all post type by default
* Improved styling in the calendar view

*Fixed:*

* Fixed link and filename of the .ics file downloaded from the calendar\
* Fixed the "Click to create" on Chrome label in Firefox
* Fixed last column popup display in Internet Explorer 11

= [1.4.3] - 2017-06-21 =

*Added:*

* Added filter to control if users can or not edit metadata
* Added new add-on PublishPress Permissions to the add-ons tab

*Fixed:*

* Fixed thge pt-BR translations
* Fixed datetime format in metadata fields for non-english languages
* Fixed the metadata editing on the calendar
* Fixed PHP warning after save options

*Changed:*

* Updated "Tested up" to 4.8
* Improved the output for unset medatada

= [1.4.2] - 2017-06-06 =

*Fixed:*

* Fixed French translation. Thanks [Thierry Pasquier](https://github.com/jeau)

= [1.4.1] - 2017-05-29 =

*Changed:*

* Added the Slack add-on as available in the Add-on tab.

= [1.4.0] - 2017-05-25 =

*Fixed:*

* Restores the icon in the freemius sdk
* Fixed minor JavaScript warning in the Editorial Comment module

*Changed:*

* Refactor notification module allowing add-ons to use any other message system
* Allow to customize priority for the action triggered on the status change

= [1.3.3] - 2017-05-22 =

*Fixed:*

* Fixed the context in translations for date picker in the Story Budget module
* Fixed the "Hello Dolly" message in the Freemius opt-in dialog
* Fixed date fields in the Editorial Metadata box

*Changed:*

* Added partial pt-PT translation
* Increased the minimum WordPress version to 4.6

= [1.3.2] - 2017-05-04 =

*Fixed:*

* Fixed the list of Add-ons, to list correctly the installation and activation status
* Fixed selector for the active admin menu
* Fixed readme.txt file with better title, tags and description

= [1.3.1] - 2017-05-02 =

*Fixed:*

* Fixed the calendar quick-edit popup
* Fixed minor issue in the code style

*Added:*

* Added WooCommerce add-on to the add-ons tab

*Changed:*

* Removed message after update settings
* Updated name of add-ons in the settings tab
* Improved content from the readme file

= [1.3.0] - 2017-04-27 =

*Fixed:*

* Fixed popup for items created as quick post
* Fixed typos

*Added:*

* Added integration with Freemius for feedback and contact form
* Added filters and actions allowing to extend modules with add-ons
* Added default modal window scripts and styles for add-ons
* Added new tab to showcase the add-ons

*Changed:*

* Changed code standards for WordPress
* Cleaned up the metadata removing default registers for "Needs photo" and "Word Count"
* Improved twig files removing hardcoded strings

= [1.2.2] - 2017-03-16 =

*Changed:*

* Add icon to the print link on the Overview screen
* Update the language files

= [1.2.1] - 2017-03-15 =

*Changed:*

* Better label for Comments metadata box
* Set Calendar Subscriptions enabled by default
* Set Always Show Dropdown enabled by default on custom statuses
* Add small notes to some tabs
* Update POT file

*Fixed:*

* Fixed empty messages while deleting custom statuses, metadata and user groups
* Fixed link to redirect after the EditFlow migration

= [1.2.0] - 2017-03-15 =

*Changed:*

* Better style for the calendar
* Click anywhere on the calendar cell to create content, intead show a button
* Extends the drag-and-drop feature to custom statuses
* Minor improvements on the code

*Added:*

* Allow to create multiple types of content on the calendar

= [1.1.0] - 2017-03-07 =

*Changed:*

* Complete rebranding to PublishPress and PressShack
* Clean up on the UI
* Move sub-pages to a common settings page
* Clean up on the text
* Refactor Story Budget to Content Overview
* Move PublishPress menu to after Comments menu

= [1.0.5] - 2017-02-16 =

*Changed:*

* Update version to trigger a new release fixing SVN issues on the last release

= [1.0.4] - 2017-02-15 =

*Changed:*

* Cleanup on the code
* Cleanup on the admin interface
* Add PressShack logo to the top of admin pages
* Set minimum WordPress version to 4.4
* Set minimum PHP version to 5.4
* Move the Settings menu item to the main PublishPress menu
* Minor improvement to the icons

*Fixed:*

* Update language strings and some links
* Fix bug on editorial comments box in the post form

= [1.0.3] - 2017-02-01 =

*Changed:*

* Update language .mo files

= [1.0.2] - 2017-02-01 =

*Changed:*

* Update plugin's description

= [1.0.1] - 2017-02-01 =

*Changed:*

* Update plugin's description
* Update language strings

= [1.0.0] - 2017-02-01 =

*Changed:*

* Renamed to PublishPress
* Rebrand for Joomlashack
