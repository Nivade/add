# What the GitLab test jobs used to install on every run, built once by the ci-image job.
FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip xz-utils \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sSLf -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions @composer bcmath intl pcntl pdo_mysql pdo_pgsql sockets zip

# Node 22 from its tarball: Debian's own nodejs is 20, which is end of life.
RUN curl -fsSL https://nodejs.org/dist/latest-v22.x/SHASUMS256.txt | grep -o 'node-v22[^ ]*-linux-x64.tar.xz' \
    | xargs -I{} curl -fsSL https://nodejs.org/dist/latest-v22.x/{} | tar -xJ -C /usr/local --strip-components=1
