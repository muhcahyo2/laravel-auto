FROM composer:lts
 
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel
 
USER laravel
 
WORKDIR /var/www
 
ENTRYPOINT [ "composer", "--ignore-platform-reqs" ]