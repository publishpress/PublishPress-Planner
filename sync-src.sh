#!/bin/sh

# Sync the src folder into wordpress, to auto-update the code while 
# developing. Used with fswatch in Mac OS to detect file changes.

# rsync -rtvu --delete ./src ~/Projects/OSTraining/Git/dev-env-wordpress/www/wp-content/plugins/publishpress
rm -rf ./src/*
cp -R ~/Projects/OSTraining/Git/dev-env-wordpress/www/wp-content/plugins/publishpress/* ./src/