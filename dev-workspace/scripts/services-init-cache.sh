#!/usr/bin/env bash

set -a
source ../.env
set +a

# If the legacy dir "cache" exists, move its content to $CACHE_PATH and remove it.
if [[ -d "cache" ]]; then
    mv cache/* $CACHE_PATH
    rm -rf cache
fi

# Create empty cache files if not exists.
[[ -d $CACHE_PATH ]] || mkdir -p $CACHE_PATH

[[ -d $CACHE_PATH/terminal/.npm/_cacache ]] || mkdir -p $CACHE_PATH/terminal/.npm/_cacache
[[ -d $CACHE_PATH/terminal/.npm/_logs ]] || mkdir -p $CACHE_PATH/terminal/.npm/_logs
[[ -d $CACHE_PATH/terminal/.composer/cache ]] || mkdir -p $CACHE_PATH/terminal/.composer/cache
[[ -d $CACHE_PATH/terminal/.oh-my-zsh/log ]] || mkdir -p $CACHE_PATH/terminal/.oh-my-zsh/log
[[ -f $CACHE_PATH/terminal/.zsh_history ]] || touch $CACHE_PATH/terminal/.zsh_history
[[ -f $CACHE_PATH/terminal/.bash_history ]] || touch $CACHE_PATH/terminal/.bash_history
[[ -f $CACHE_PATH/terminal/.composer/auth.json ]] || echo '{}' > $CACHE_PATH/terminal/.composer/auth.json

[[ -d $CACHE_PATH/test-db/mysql ]] || mkdir -p $CACHE_PATH/test-db/mysql
[[ -d $CACHE_PATH/test-db/logs ]] || mkdir -p $CACHE_PATH/test-db/logs

[[ -d $CACHE_PATH/test-wp/html ]] || mkdir -p $CACHE_PATH/test-wp/html
[[ -d $CACHE_PATH/test-wp/tmp ]] || mkdir -p $CACHE_PATH/test-wp/tmp

[[ -d $CACHE_PATH/test-wpcli/tmp ]] || mkdir -p $CACHE_PATH/test-wpcli/tmp

[[ -d $CACHE_PATH/dev-db/mysql ]] || mkdir -p $CACHE_PATH/dev-db/mysql
[[ -d $CACHE_PATH/dev-db/logs ]] || mkdir -p $CACHE_PATH/dev-db/logs

[[ -d $CACHE_PATH/dev-wp/html ]] || mkdir -p $CACHE_PATH/dev-wp/html
[[ -d $CACHE_PATH/dev-wp/tmp ]] || mkdir -p $CACHE_PATH/dev-wp/tmp

[[ -d $CACHE_PATH/dev-wpcli/tmp ]] || mkdir -p $CACHE_PATH/dev-wpcli/tmp

[[ -d $CACHE_PATH/mailhog/maildir ]] || mkdir -p $CACHE_PATH/mailhog/maildir
