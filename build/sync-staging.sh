#!/bin/sh

#==========================================
#            Check the env vars           =
#==========================================

[ -z "$PS_WP_PATH" ] && echo "Need to set PS_WP_PATH" && exit 1;
[ ! -f "$PS_WP_PATH/wp-config.php" ] && echo "WordPress not found on: $PS_WP_PATH." && echo "Check the PS_WP_PATH env var." && exit 1;

#=====  End of Check the env vars  ======


#==========================================
#                The script               =
#==========================================
# Sync the src folder into wordpress, to auto-update the code while
# developing. Used with fswatch in Mac OS to detect file changes.

STAGING_PATH=$PS_WP_PATH/wp-content/plugins/publishpress
SRC_PATH=../src


if [ ! -d $STAGING_PATH ]; then
	mkdir $STAGING_PATH
fi

rm -rf $STAGING_PATH/*
cp -R $SRC_PATH/* $STAGING_PATH
