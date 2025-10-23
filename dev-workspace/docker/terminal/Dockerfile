ARG NODE_VERSION=18

FROM node:$NODE_VERSION-alpine AS node

FROM publishpress/dev-workspace-terminal:generic-4.4.3

ENV PLUGIN_NAME="PublishPress Planner"
ENV PLUGIN_TYPE="FREE"

COPY --from=node /usr/lib /usr/lib
COPY --from=node /usr/local/share /usr/local/share
COPY --from=node /usr/local/lib /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin /usr/local/bin

COPY git/.gitconfig /root/.gitconfig
COPY .zshrc.local /root/.zshrc.local

RUN set -ex \
    git config --global --add safe.directory /project \
    && npm install -g cross-env \
    # Clean npm cache to reduce image size
    && npm cache clean --force \
    # Remove unnecessary files
    && rm -rf /tmp/* \
    && rm -rf /var/cache/apk/*
