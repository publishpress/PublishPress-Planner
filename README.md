# PublishPress

[![Build Status](https://travis-ci.org/OSTraining/PublishPress.svg?branch=development)](https://travis-ci.org/OSTraining/PublishPress)

## Description

PublishPress is the essential plugin for any site with multiple writers: Content Calendar, Email Notifications, Custom Publishing Statuses and more.

Based on Edit Flow. Edit Flow is produced by Daniel Bachhuber, Mo Jangda, and Scott Bressler, with special help from Andrew Spittle and Andrew Witherspoon.

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

## License

License: [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)
