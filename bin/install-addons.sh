#!/usr/bin/env bash

downloadPlugin () {
    TMPDIR=${TMPDIR-/tmp}
    TMPDIR=$(echo $TMPDIR | sed -e "s/\/$//")

    REPO="https://$GITHUB_USERNAME:$GITHUB_ACCESS_TOKEN@github.com/$1/$2.git"
    VERSION=$3     # tag name or the word "latest"
    CLONE_DIR="$TMPDIR/wordpress/wp-content/plugins/$4"
    PLUGIN_ALIAS=$4

    alias errcho='>&2 echo'

    # Remove cloned repo if exists
    if [ -d $CLONE_DIR ]; then
        rm -rf $CLONE_DIR
    fi

    # Clone the repository
    git clone --branch $VERSION $REPO $CLONE_DIR
}

downloadPlugin OSTraining PublishPress-Permissions 1.0.4 publishpress-permissions
