#!/usr/bin/env bash

set -a
source /project/.env
set +a

for locale in $LANG_LOCALES
do
    po_file="./$LANG_DIR/$PLUGIN_SLUG-${locale}.po"
    if [ -f "$po_file" ]; then
        npx po2json "$po_file" > "./$LANG_DIR/$PLUGIN_SLUG-${locale}.json"
    fi
done
