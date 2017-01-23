#!/bin/bash

# @package PublishPress
# @author PressShack
#
# Copyright (c) 2017 PressShack
#
# ------------------------------------------------------------------------------
# Based on Edit Flow
# Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
# others
# Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
# ------------------------------------------------------------------------------
#
# This file is part of PublishPress
#
# PublishPress is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PublishPress is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.

if [ $# -eq 0 ]; then
	echo 'Usage: `./deploy-to-svn.sh <tag | HEAD>`'
	exit 1
fi

PUBLISHPRESS_GIT_DIR=$(dirname "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )" )
PUBLISHPRESS_SVN_DIR="/tmp/publishpress"
TARGET=$1

cd $PUBLISHPRESS_GIT_DIR

# Make sure we don't have uncommitted changes.
if [[ -n $( git status -s --porcelain ) ]]; then
	echo "Uncommitted changes found."
	echo "Please deal with them and try again clean."
	exit 1
fi

if [ "$1" != "HEAD" ]; then

	# Make sure we're trying to deploy something that's been tagged. Don't deploy non-tagged.
	if [ -z $( git tag | grep "^$TARGET$" ) ]; then
		echo "Tag $TARGET not found in git repository."
		echo "Please try again with a valid tag."
		exit 1
	fi
else
	read -p "You are about to deploy a change from an unstable state 'HEAD'. This should only be done to update string typos for translators. Are you sure? [y/N]" -n 1 -r
	if [[ $REPLY != "y" && $REPLY != "Y" ]]
	then
		exit 1
	fi
fi

git checkout $TARGET

# Prep a home to drop our new files in. Just make it in /tmp so we can start fresh each time.
rm -rf $PUBLISHPRESS_SVN_DIR

echo "Checking out SVN shallowly to $PUBLISHPRESS_SVN_DIR"
svn -q checkout https://plugins.svn.wordpress.org/publishpress/ --depth=empty $PUBLISHPRESS_SVN_DIR
echo "Done!"

cd $PUBLISHPRESS_SVN_DIR

echo "Checking out SVN trunk to $PUBLISHPRESS_SVN_DIR/trunk"
svn -q up trunk
echo "Done!"

echo "Checking out SVN tags shallowly to $PUBLISHPRESS_SVN_DIR/tags"
svn -q up tags --depth=empty
echo "Done!"

echo "Deleting everything in trunk except for .svn directories"
for file in $(find $PUBLISHPRESS_SVN_DIR/trunk/* -not -path "*.svn*"); do
	rm $file 2>/dev/null
done
echo "Done!"

echo "Rsync'ing everything over from Git except for .git stuffs"
rsync -r --exclude='*.git*' $PUBLISHPRESS_GIT_DIR/* $PUBLISHPRESS_SVN_DIR/trunk
echo "Done!"

echo "Purging paths included in .svnignore"
# check .svnignore
for file in $( cat "$PUBLISHPRESS_GIT_DIR/.svnignore" 2>/dev/null ); do
	rm -rf $PUBLISHPRESS_SVN_DIR/trunk/$file
done
echo "Done!"

# Tag the release.
# svn cp trunk tags/$TARGET

# Change stable tag in the tag itself, and commit (tags shouldn't be modified after comitted)
# perl -pi -e "s/Stable tag: .*/Stable tag: $TARGET/" tags/$TARGET/readme.txt
# svn ci

# Update trunk to point to the freshly tagged and shipped release.
# perl -pi -e "s/Stable tag: .*/Stable tag: $TARGET/" trunk/readme.txt
# svn ci
