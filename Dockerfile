ARG PHP_IMAGE_VERSION=alpine3.10

FROM php:$PHP_IMAGE_VERSION as builder

RUN apk add composer

COPY composer.json composer.json
RUN composer install

FROM php:$PHP_IMAGE_VERSION
WORKDIR /workdir

RUN adduser -u 2004 -D docker
COPY docs /docs
RUN chown -R docker:docker . /docs

USER docker

COPY src src
COPY --from=builder vendor vendor

ENTRYPOINT [ "php", "-d", "memory_limit=-1" ]

CMD [ "src/main/php/CodacyPDepend/index.php" ]
