FROM php:alpine3.10

WORKDIR /workdir

RUN apk add composer

COPY composer.json composer.json

RUN adduser -u 2004 -D docker
COPY docs /docs
RUN chown -R docker:docker . /docs
RUN composer install

USER docker

COPY src src

ENTRYPOINT [ "php", "-d", "memory_limit=-1" ]

CMD [ "src/main/php/CodacyPDepend/index.php" ]
