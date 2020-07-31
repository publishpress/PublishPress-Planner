# PublishPress


## Description

PublishPress helps you plan and publish content with WordPress. Features include a content calendar, notifications, and custom statuses.

Based on Edit Flow. Edit Flow is produced by Daniel Bachhuber, Mo Jangda, and Scott Bressler, with special help from Andrew Spittle and Andrew Witherspoon.

## Documentation

https://publishpress.com/docs/

## How to report bugs or send suggestions

Feel free to email us via [help@publishpress.com](mailto:help@publishpress.com). We would love to hear you, and will work hard to help you.

### Guidelines

* Write a clear summary
* Write precise steps to reproduce

## Installation

:warning: **Warning! This plugin requires to be built before being installed!**

This repository doesn't store external dependencies required by the plugin. It's not possible to simply clone or download the repository code and have a working WordPress plugin.

We aim to follow good practices on development, and we are using Composer as dependency manager, which recommends to not add external dependencies into the repository. You can find more information on their documentation page: [Should I commit the dependencies in my vendor directory?](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md)

### How to install?

You can download a built package from [releases page](/releases/) and install it on your WordPress sites by uploading the zip file.

## How to build a package?

Please, check the instructions on our [documentation pages](https://publishpress.github.io/docs/deployment/building).

## How to contribute with code

* Clone the repository
* Create a new branch
* Implement and commit the code
* Create a Pull Request targeting the "development" branch adding details about your fix

We will review and contact you as soon as possible.

## Development

### React

We use [React](https://facebook.github.io/react/) to build part of the user interface.
The sources files are named with the extension JSX. Which is optional on React, but provides a way to write modern code and compile to be compatible with legacy browsers. We use [babeljs.io](babeljs.io) with the presets: react and es2015 to compile to JS files.

#### Compiling JSX files to JS

You can install Babel on your environment following its documentation. But here we will describe how to use a Docker container for that.

```
$ docker run -it --rm -v `pwd`:/app ostraining/node-babel:latest bash
```

**To compile one file**

```
# babel src/modules/efmigration/lib/js/efmigration.jsx --out-file src/modules/efmigration/lib/js/efmigration.js
```

**To watch a folder automatically compile changed files**

```
# babel -w src/modules/efmigration/lib/babel -d src/modules/efmigration/lib/js
```

## License

License: [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)
