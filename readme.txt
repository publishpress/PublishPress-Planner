=== PublishPress Content Calendar and Notifications ===
Contributors: publishpress, andergmartins, stevejburge, pressshack, kevinb, deenison
Author: PublishPress, PressShack
Author URI: https://publishpress.com
Tags: notifications, Editorial Calendar, workflow, statuses, permissions
Requires at least: 4.6
Requires PHP: 5.4
Tested up to: 5.2
Stable tag: 1.20.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PublishPress is the plugin for professional publishers. Get an editorial calendar, flexible permissions and notifications.

== Description ==

PublishPress can help you create great content in WordPress. Here’s an overview of the publishing tools you’ll find in PublishPress:


* There’s a beautiful [Editorial Calendar](https://publishpress.com/docs/calendar/) to give a clear picture of all your planned and published content.
* You can create [Notifications](https://publishpress.com/docs/notifications/) to keep your team up-to-date with what’s happening.
* You can write [Editorial Comments](https://publishpress.com/docs/editorial-comments/) to leave feedback for your site’s authors.
* There are [Custom Statuses](https://publishpress.com/docs/custom-statuses/) so you can see where content is in your publishing workflow.
* The [Content Overview](https://publishpress.com/docs/calendar/) screen allows you to drill down and analyze your site’s content.
* You can add [Metadata](https://publishpress.com/docs/editorial-metadata/) to give your team extra information about each post.

Interested in finding out more about PublishPress?

* [Click here to try a free demo](https://publishpress.com/demo/).
* [Check out the premium add-ons](https://publishpress.com/pricing/) for access to all the PublishPress features.

= WHO SHOULD USE PUBLISHPRESS? =

PublishPress is ideal for WordPress sites that publish high-quality content. With PublishPress, you can collaborate much more effectively. This makes PublishPress a great solution for any site with multiple users. PublishPress is often used by companies and non-profits, universities and schools, plus by magazines, newspapers and blogs.

= PREMIUM ADD-ONS FOR PUBLISHPRESS =

* [Content Checklist](https://publishpress.com/addons/content-checklist/): Set high standards for all your published content.
* [Multiple Authors](https://publishpress.com/addons/multiple-authors-publishpress/): Easily assign multiple authors to one content item.
* [Reminders](http://publishpress.com/addons/reminders): Automatically send notifications before or after content is published.
* [Permissions](https://publishpress.com/addons/publishpress-permissions/): Control who gets to click the “Publish” button.
* [WooCommerce Checklist](https://publishpress.com/addons/woocommerce-checklist/): Set high standards for all your WooCommerce products.
* [Slack Notifications](https://publishpress.com/addons/publishpress-slack/): Get Slack updates for all content changes.

= EDITORIAL CALENDAR =

The calendar gives you a powerful overview of your publishing schedule. Using the Editorial Calendar, you can easily see when content is planned, and when it was published. You can also drag-and-drop content to a new publication date. By default, you see all the WordPress content you have planned for the next six weeks. If you need to drill down, you can filter the calendar by post status, categories, users or post types.

[Click here for more on the PublishPress Editorial Calendar](https://publishpress.com/docs/calendar/)


= NOTIFICATION WORKFLOWS =

Notifications keep you and your team up to date on changes to important content. Users can be subscribed to notifications for any post, either individually or by selecting user groups. PublishPress allows you to create powerful notification workflows based on post types, categories, status changes and much more.

* [Click here for more on PublishPress Notifications](https://publishpress.com/docs/notifications/)

= CONTENT OVERVIEW =

The Content Overview screen is a companion to the Calendar screen. Whereas the Calendar allows you to see content organized by dates, Content Overview allows you to drill down and see content organized by status, categories, or users. In the top-right corner is a “Print” button. Click this to get a printable overview of all your planned content.

* [Click here for more on the PublishPress Content Overview](https://publishpress.com/docs/content-overview/)

= CUSTOM STATUSES =
 tru
This feature allows you to create custom post statuses such as “In Progress” or “Pending Review”. You can define statuses to match the stages of your team’s publishing workflow.

By default, WordPress provides you with a very limited set of status choices: Draft and Pending Review. With PublishPress you’ll see a much wider range of options. When you first install PublishPress, you’ll see these extra statuses: Pitch, Assigned, and In Progress. You can then create more custom post statuses to define the stages of your publishing workflow.

* [Click here for more on the PublishPress Custom Statuses](https://publishpress.com/docs/custom-statuses/)

= EDITORIAL COMMENTS =

A very important feature in PublishPress is commenting. You can leave comments under each post you write. This is a private conversation between writers and editors and allows you to discuss what needs to be changed before publication.

* [Click here for more on PublishPress Editorial Comments](https://publishpress.com/docs/editorial-comments/)

= METADATA =

Metadata enables you to keep track of important requirements for your content. This feature allows you to create fields and store information about content items.

By default, PublishPress provide 4 examples of metadata, but you can add your own to meet your team’s needs.

* [Click here for more on PublishPress Editorial Metadata](https://publishpress.com/docs/editorial-metadata/)

= USER GROUPS =

For larger organizations with many people involved in the publishing process, user groups help keep your workflow organized and informed.

To find the user settings, go to the PublishPress link in your WordPress admin area, and click the “User Groups” tab. By default, PublishPress provides four user groups: Copy Editors, Photographers, Reporters and Section Editors.

* [Click here for more on PublishPress User Groups](https://publishpress.com/docs/user-groups/)

= IMPORTING FROM EDITFLOW =

PublishPress is based on the EditFlow plugin. It is easy for Edit Flow users to import your data and settings.

* [Click here for full instructions on moving from Edit Flow to PublishPress](https://publishpress.com/docs/migrate/)

= I FOUND A BUG, OR WANT TO CONTRIBUTE CODE =
K_TCPDF_CALLS_IN_HTML
Great! We’d love to hear from you! PublishPress [is available on Github](https://github.com/AllediaWordPress/PublishPress), and we welcome contributions from everyone.

= FAQ =

== Installation ==

You can install PublishPress through your WordPress admin area:

1. Access the “Plugins” page.
1. Click on the “Add New” button.
1. Search for “PublishPress”.
1. Install the PublishPress plugin.
1. Activate the PublishPress plugin.

= Where Can I Get Support? =

You can ask for help via [the PublishPress contact form](https://publishpress.com/contact/).
ph
= Do I Need Coding Skills to Use PublishPress? =

Not at all. You can set up everything your team needs without any coding knowledge. We made it super easy.

== Screenshots ==

1. With Calendar you can see your posts over a customizable date range.

== Changelog ==

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

= [1.20.8] - 2019-08-19 =

* Feature: Support post meta fields in notification body (also requires PublishPress Reminders 1.1.1)
* Feature: Added general debug information and settings to the Site Health page
* Feature: Added a list of installed modules to the Site Health page
* Feature: Added a list of scheduled notifications ot the Site Health page
* Changed: Convert debug panel to read only
* Changed: symfony/polyfill-ctype library updated from 1.11.0 to 1.12.0
* Fixed: If Async Notifications enabled and more than one workflow notification applies to a post, the additional notifications were not sent (corresponding fix in PublishPress Reminders 1.1.1)
* Fixed: PHP warning "date() expects parameter 2 to be int, string given"
* Fixed: Hidden calendar on datepicker fields in Gutenberg due to negative z-index
* Fixed: The column "Last Updated" on content overview had a wrong date format
* Fixed: Missed "use" statement for Dependency Injection on the custom status module

= [1.20.7] - 2019-06-17 =

* Fix fatal error in wp-admin when active alongside WPML or another plugin that uses an obsolete version of the Twig library
* Fix alignment of stars on "please leave us a rating" footer
* Fix redirect behavior on "Already reviewed" selection from "Please leave a review" notice
* Fix PHP warning when a invalid taxonomy is loaded
* Fix an empty space on the statuses screen
* Add publish time field on New Post popup within Calendar
* Add post status field on New Post popup within Calendar
* Fix call-time passed-by-reference occurrences
* Changed calendar to display or hide the publish time according to the post status
* Add new PublishPress icon to the Settings screen
* Update the menu icon
* Fix calendar subscription after a fresh install, don't require saving the settings before it works
* Fix the empty author list in the calendar popup
* For PublishPress Reminders, support status selection in "Before Publication" workflow notifications
* Fix double slash on some assets URLs

= [1.20.6] - 2019-05-16 =
* Fix fatal error on Edit Notification Workflow screen
* Fix JS error in wp-admin (blocking other plugin JS) for sites running on localhost and Windows servers

= [1.20.5] - 2019-05-15 =
* Fix blacklisting taxonomies causing WSOD in some envs containing a lot of Terms
* Fix Calendar displaying times on wrong Timezones
* Fix Custom Statuses listing on Settings page not being reordable anymore

= [1.20.4] - 2019-05-03 =
* Fix JS error in wp-admin (blocking other plugin JS) for sites running on localhost and Windows servers

= [1.20.3] - 2019-05-03 =

* Fix fatal error in Calendar module: "undefined function mb_strtolower()" on servers that don't have PHP multibyte string extension
* Fix PHP notice in Unpublished Content dashboard metabox

= [1.20.2] - 2019-05-02 =

* Partially adds support for Gutenberg-Ramp plugin
* Fix PublishPress icon missing on admin sidebar in some envs
* Fix PHP warnings on Calendar module
* Fix issue on posts where MetaData date fields were losing their formats and values after saving
* Fix Calendar date filter going back to 1970 if user edits filter value but doesn't change it
* Fix PHP warnings on Notifications section on Posts form if WP_DEBUG is on
* Add option to blacklist taxonomies-slugs on the taxonomies filter for adding New Notification Workflow

= [1.20.1] - 2019-04-16 =

* Fix PHP warning regarding missing variable reference
* Fix custom statuses not being available for Quick/Bulk post editing
* Fix publish time being displayed on any post regardless of its status on Calendar
* Fix missing default value for the Display Publish Time calendar option
* Fix a performance issue caused by duplicated DB queries calls on Calendar

= [1.20.0] - 2019-04-08 =

* Fix metadata not showing up on Content Overview;
* Fix Content Overview Start Date filter not detecting current date;
* Fix minor inconsistency on Content Overview datepicker filter output format;
* Fix Custom Statuses table squeezing up content on settings page when a lot of custom post types are in use;
* Add "new" to available statuses for notification workflow;
* Add Author field on Calendar item pop up;
* Add option to toggle posts/pages publish time on Calendar;

= [1.19.4] - 2019-02-22 =

* Fixed the list of notification receivers for not excluding emails with numeric chars;
* Fixed email notifications for email addresses added directly into the post, using the "Notify" box;
* Fixed the method to detect when a post is using the block editor, checking additionally the filter "use_block_editor_for_post_type";
* Fixed default state for custom statuses, do not disabling it by default when the block editor is being used;
* Updated Tested Up To version to 5.1 and added kevinb as contributor;
* Fixed compatibility with Revisionary < v1.2.3 and Statuses;

= [1.19.3] - 2019-02-15 =

* Fixed the publishing workflow, removing Published from the select box of post statuses;
* Fixed the list of statuses applying the filter which allows to add-ons like Permissions to filter the list;

= [1.19.2] - 2019-02-12 =

* Fixed bug preventing to unpublish posts;

= [1.19.1] - 2019-02-12 =

* Fixed method that detects the block editor, restoring the publish button in the classic editor;
* Fixed the Save button for custom statuses in the block editor;

= [1.19.0] - 2019-02-11 =

* Fixed PHP Warning about to_notify variable;
* Removed the requirement for the classic editor when the Content Checklist is active;
* Removed the 20% discount subscription form and replaced with a simple banner;
* Added support to custom statuses for posts in Gutenberg;
* Improved text for custom status settings options;

= [1.18.5] - 2019-01-30 =

* Fixed warning message when a string is added as param for the shortcode psppno_workflow;
* Fixed redirection after dismissing the alert regarding reviewing PublishPress, when calendar is not activated;
* Fixed Gutenberg compatibility do not falling off to the classic editor;
* Fixed compatibility with Bedrock, fixing paths for assets and the plugin;

= [1.18.4] - 2019-01-25 =

* Fixed incompatibility with UpStream 1.23.1;

= [1.18.3] - 2019-01-23 =

* Released only to trigger a new update on sites due to corrupted package for 1.18.2;

= [1.18.2] - 2019-01-23 =

* Updated the subscription form for discount coupon, for the new Mailchimp account;
* Updated the settings tabs for the editorial metadata and custom statuses moving Options before Add New;
* Removed warning about Classic Editor as requirement;
* Updated the default value for selected post types for the custom status module, disabling by default if Gutenberg is installed;

= [1.18.1] - 2019-01-14 =

* Fixed minor performance issue in the settings page removing a code from a loop;
* Added an option for selecting a default notification channel;
* Fixed the position of the editorial comments metabox removing from the sidebar and added as high priority;
* Fixed the output of boolean values in the Debug page;

= [1.18.0] - 2018-12-06 =

* Fixed non-escaped attributes and URLs in the whole plugin;
* Fixed style of editorial comments for fitting the sidebar in Gutenberg;
* Added a new filter for get_post_types methods: "publishpress_supported_module_post_types";
* Fixed a missed ";" from the admin interface;
* Changed the action publishpress is hooked to. From "plugins_loaded" to "init";
* Fixed double "use" statement for Dependence_Injector in the PublishPress\Notifications\Workflow\Step\Channel\Base class;
* Added the option to add non-users emails (and name) in the notify box for posts;
* Improved the title and text for the notify box;
* Added a new shortcode "psppno_receiver" for the notification content, which supports name and email params;
* Added a list of active workflows to the notify box;
* Added a slightly darker background color to the month name row in the calendar;
* Added a check for Gutenberg, disabling the block editor for post types where custom statuses and the content-checklist (add-on) are enabled;
* Added a check for Classic Editor in WordPress 5.0, showing a notice advising to install and activate it;
* Fixed broken HTML syntax in some settings panels;
* Updated the "Tested up" version to 5.0

= [1.17.0] - 2018-11-08 =

* Fixed permalink for posts in multisite URLs;
* Fixed the position for the editorial comments metabox for Gutenberg;
* Fixed dashboard widget hiding Published and Private Posts counts, since the box relates to unpublished content;
* Fixed method that changes user's data to make a verification before change anything;
* Added a new capability, "pp_set_notification_channel", for controlling who can select a different notification channel in the profile page;
* Added a new option to disable PublishPress' branding for who has at least one activated license key;
* Added a debug module with basic information for debugging, and log viewer;

= [1.16.4] - 2018-10-03 =

*Fixed:*

* Fixed properties (color and icon) for customizing core statuses;
* Fixed fatal error in shortcode processing when the param is sent as string - for notifications' body;

= [1.16.3] - 2018-09-19 =

*Fixed:*

* Fixed compatibility with UpStream fixing a PHP warning displayed in the UpStream settings page, related to PublishPress getting confuse with both using Allex Framework for dealing with add-ons. You need to update UpStream as well;
* Fixed some queries executed when they were not useful. The queries are related to the options for icon and color of custom statuses;
* Fixed license key activation and upgrade form when installed alongside UpStream - requires to update UpStream as well;
* Fixed icon of Multiple Authors add-on in the add-ons page;
* Fixed wrong URL for assets on Windows machines;

= [1.16.2] - 2018-08-28 =

*Fixed:*

* Fixed a bug in the URL sent in the validation of license keys for add-ons;

= [1.16.1] - 2018-08-27 =

*Fixed:*

* Fixed a bug in the validation of license keys for add-ons;

= [1.16.0] - 2018-08-27 =

* Changed:*

* Refactored the add-ons page centralizing the license key management;
* Rebranded with the new logo;
* Added sidebar with form offering discount of 20% in a plan when the user doesn't have any plugin installed;

= [1.15.0] - 2018-07-19 =

*Added:*

* Added filter for taxonomies and improved custom post type support in notification workflows;

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
