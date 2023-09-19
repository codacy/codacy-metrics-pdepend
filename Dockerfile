FROM alpine:3.18.3 as base

RUN apk add --no-cache php php-phar php-iconv php-openssl php-tokenizer php-dom php-mbstring php-xmlwriter php-xml

FROM base as builder

WORKDIR /workdir

RUN apk add --no-cache composer

COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --no-scripts
COPY src src
COPY tests tests

RUN composer test && composer check-formatting

RUN composer install --no-dev

FROM base
WORKDIR /workdir
COPY docs /docs
RUN adduser -u 2004 -D docker && \
    chown -R docker:docker . /docs
USER docker

COPY --from=builder /workdir/vendor vendor
COPY src src

ENTRYPOINT [ "php", "-d", "memory_limit=-1" ]

CMD [ "src/index.php" ]
