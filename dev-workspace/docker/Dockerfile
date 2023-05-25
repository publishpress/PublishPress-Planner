FROM php:8.0-cli

####################################################################################################
# Install base dependencies
####################################################################################################

RUN set -ex; \
	\
  # Prepare for installing docker-cli
  apt-get update && \
    apt-get install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg-agent \
        software-properties-common && \
    curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add - && \
    add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/debian $(lsb_release -cs) stable" && \
  \
  # Prepare for installing yarn
  curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
  echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
  \
  # Install base dependencies
	apt-get update; \
    apt-get install -y \
        build-essential \
        libyaml-dev \
        g++ \
        make \
        autoconf \
        libzip-dev \
        curl \
        libcurl4-openssl-dev \
        wget \
        bash \
        coreutils \
        git \
        openssh-client \
        patch \
        subversion \
        tini \
        unzip \
        zip \
        rsync \
        python3.5 \
        python3-pip \
        nano \
        vim \
        zsh \
        iputils-ping \
        net-tools \
        default-mysql-client \
        bsdmainutils \
        docker.io \
        docker-compose \
        yarn \
        ; \
    mkdir -p /usr/src/php/ext/yaml; \
    curl -fsSL https://pecl.php.net/get/yaml | tar xvz -C "/usr/src/php/ext/yaml" --strip 1; \
    \
    docker-php-ext-configure zip; \
	docker-php-ext-install zip gettext yaml mysqli pdo_mysql; \
    \
    apt-get purge -y --auto-remove; \
	rm -rf /var/lib/apt/lists/*

####################################################################################################
# Install ZSH
####################################################################################################

# More info: https://github.com/deluan/zsh-in-docker

RUN sh -c "$(wget -O- https://github.com/deluan/zsh-in-docker/releases/download/v1.1.3/zsh-in-docker.sh)" -- \
    -t ys \
    -p git \
    -p asdf \
    -p ag \
    -p wp-cli

COPY root/.zshrc /root/.zshrc

####################################################################################################
# Configure PHP ini
####################################################################################################

RUN { \
# https://www.php.net/manual/en/errorfunc.constants.php
# https://github.com/docker-library/wordpress/issues/420#issuecomment-517839670
		echo 'error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR'; \
		echo 'display_errors = Off'; \
		echo 'display_startup_errors = Off'; \
		echo 'log_errors = On'; \
		echo 'error_log = /dev/stderr'; \
		echo 'log_errors_max_len = 1024'; \
		echo 'ignore_repeated_errors = On'; \
		echo 'ignore_repeated_source = Off'; \
		echo 'html_errors = Off'; \
	} > /usr/local/etc/php/conf.d/error-logging.ini

####################################################################################################
# Install Composer
####################################################################################################

RUN printf "# composer php cli ini settings\n\
date.timezone=UTC\n\
memory_limit=-1\n\
" > $PHP_INI_DIR/php-cli.ini

RUN mkdir /root/.composer

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_HOME /root/.composer
ENV COMPOSER_VERSION 2.3.9

RUN set -eux ; \
  # install https://github.com/mlocati/docker-php-extension-installer
  curl \
    --silent \
    --fail \
    --location \
    --retry 3 \
    --output /usr/local/bin/install-php-extensions \
    --url https://github.com/mlocati/docker-php-extension-installer/releases/download/1.5.47/install-php-extensions \
  ; \
  echo a4703daabe294839f00d995675cdad02a19a5b585d9e96bf2a9ddda2306afa8de472473c83a02f70b86af88226f493a181b24cb602bfd32404f22ce2cb889349 /usr/local/bin/install-php-extensions | sha512sum --strict --check ; \
  chmod +x /usr/local/bin/install-php-extensions ; \
  # install public keys for snapshot and tag validation, see https://composer.github.io/pubkeys.html
  curl \
    --silent \
    --fail \
    --location \
    --retry 3 \
    --output /tmp/keys.dev.pub \
    --url https://raw.githubusercontent.com/composer/composer.github.io/e7f28b7200249f8e5bc912b42837d4598c74153a/snapshots.pub \
  ; \
  echo 572b963c4b7512a7de3c71a788772440b1996d918b1d2b5354bf8ba2bb057fadec6f7ac4852f2f8a8c01ab94c18141ce0422aec3619354b057216e0597db5ac2 /tmp/keys.dev.pub | sha512sum --strict --check ; \
  curl \
    --silent \
    --fail \
    --location \
    --retry 3 \
    --output /tmp/keys.tags.pub \
    --url https://raw.githubusercontent.com/composer/composer.github.io/e7f28b7200249f8e5bc912b42837d4598c74153a/releases.pub \
  ; \
  echo 47f374b8840dcb0aa7b2327f13d24ab5f6ae9e58aa630af0d62b3d0ea114f4a315c5d97b21dcad3c7ffe2f0a95db2edec267adaba3f4f5a262abebe39aed3a28 /tmp/keys.tags.pub | sha512sum --strict --check ; \
  # download installer.php, see https://getcomposer.org/download/
  curl \
    --silent \
    --fail \
    --location \
    --retry 3 \
    --output /tmp/installer.php \
    --url https://raw.githubusercontent.com/composer/getcomposer.org/0a51b6fe383f7f61cf1d250c742ec655aa044c94/web/installer \
  ; \
  echo ba79d97d127330ad12538395723d3c6bdabacd010bb8b845b900f2e5e3de0ad199b41d21e9fbe7a9c01f5a88a827c9da2dac3ba09da64648e63ad4eef069e48e /tmp/installer.php | sha512sum --strict --check ; \
  # install composer phar binary
  php /tmp/installer.php \
    --no-ansi \
    --install-dir=/usr/bin \
    --filename=composer \
    --version=${COMPOSER_VERSION} \
  ; \
  composer --ansi --version --no-interaction ; \
  composer diagnose ; \
  rm -f /tmp/installer.php ; \
  find /tmp -type d -exec chmod -v 1777 {} +

####################################################################################################
# Install node.js LTS version
####################################################################################################

ENV NODE_VERSION=14.19.3
ENV NPM_VERSION=6.14.17
ENV NVM_DIR=/root/.nvm
ENV PATH="/root/.nvm/versions/node/v${NODE_VERSION}/bin/:${PATH}"
RUN curl \
    --silent \
    --fail \
    --location \
    --retry 3 \
    --output /tmp/nvm-install.sh \
    --url https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.2/install.sh \
  ; \
  echo c6e6e30aa7fdba27a5e9b3d5b47cf5d93043b775f316e6aa2ee6981bcd3e074e88d35ed136bc050deb73e4db8047b4be86fb02a5b6bd83b8726fb068622072d9 /tmp/nvm-install.sh | sha512sum --strict --check ; \
  bash /tmp/nvm-install.sh

RUN . "$NVM_DIR/nvm.sh" && nvm install ${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm use v${NODE_VERSION}
RUN . "$NVM_DIR/nvm.sh" && nvm alias default v${NODE_VERSION}
RUN node --version

RUN npm install -g npm@${NPM_VERSION}

RUN npm --version
RUN yarn --version

####################################################################################################
# Install Python libs
####################################################################################################

# RUN pip3 install med2image

####################################################################################################
# Configure the image
####################################################################################################

RUN mkdir /project

VOLUME /project

WORKDIR /project

COPY scripts/ppbuild /usr/local/bin/ppbuild

ENV PATH="/project/node_modules/.bin:/project/vendor/bin:$PATH"

RUN chmod +x /usr/local/bin/ppbuild
