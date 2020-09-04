ARG PHP_IMAGE_VERSION=alpine3.10

FROM php:$PHP_IMAGE_VERSION as builder

RUN apk add composer

COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-scripts
COPY src src
COPY tests tests

RUN composer test && composer check-formatting

RUN composer install --no-dev

FROM php:$PHP_IMAGE_VERSION
WORKDIR /workdir
RUN adduser -u 2004 -D docker
COPY docs /docs
RUN chown -R docker:docker . /docs
USER docker

COPY --from=builder vendor vendor
COPY src src

ENTRYPOINT [ "php", "-d", "memory_limit=-1" ]

CMD [ "src/index.php" ]
