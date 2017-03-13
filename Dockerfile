FROM php:7.0-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
RUN docker-php-ext-install -j$(nproc) mbstring
CMD [ "php", "./parse_edict.php" ]
