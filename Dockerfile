FROM php:alpine3.10

WORKDIR /workdir

RUN apk add composer

COPY composer.json composer.json

# COPY docs /docs
RUN adduser -u 2004 -D docker
USER docker

RUN composer install 

RUN chown -R docker:docker /home/docker 
# chown /docs too
COPY src src

ENTRYPOINT [ "php", "-d", "memory_limit=-1" ]

CMD [ "src/main/php/CodacyPDepend/index.php" ]
