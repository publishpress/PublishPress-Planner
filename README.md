# PublishPress

Contributors: PressShack
Tags: publish flow, workflow, editorial, edit flow, newsroom, management, journalism, post status, custom status, notifications, email, comments, editorial comments, usergroups, calendars, editorial calendar, story budget
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PublishPress is the essential plugin for any site with multiple writers: Content Calendar, Email Notifications, Custom Publishing Statuses and more.

## Description

Get the power to collaborate with your editorial team without leaving WordPress.

PublishPress is based on Edit Flow. Edit Flow is produced by Daniel Bachhuber, Mo Jangda, and Scott Bressler, with special help from Andrew Spittle and Andrew Witherspoon.

## Development

### React

We use [React](https://facebook.github.io/react/) to build part of the user interface.
The sources files are named with the extension JSX. Which is optional on React, but provides a way to write modern code and compile to be compatible with legacy browsers. We use [babeljs.io](Babel) with the presets: react and es2015 to compile to JS files.

#### Compiling JSX files to JS

You can install Babel on your environment following its documentation. But here we will describe how to use a Docker container for that.

```
$ docker run -it --rm -v `pwd`:/app ostraining/node-babel:latest bash
```

**To compile one file**

```
# babel src/modules/efmigration/lib/babel/efmigration.jsx --out-file src/modules/efmigration/lib/js/efmigration.js
```

**To watch a folder automatically compile changed files**

```
# babel -w src/modules/efmigration/lib/babel -d src/modules/efmigration/lib/js
```
