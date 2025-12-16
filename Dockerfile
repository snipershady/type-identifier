FROM php:8.4-apache

RUN apt-get update && apt-get install zip unzip -y


COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY ./src ./src
COPY ./tests ./tests
COPY composer.* ./
COPY .php-cs-fixer.dist.php ./

RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist


RUN composer dump-autoload --optimize

COPY ./entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]


CMD apache2-foreground