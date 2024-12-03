FROM php:8.2-cli
RUN docker-php-ext-install mysqli
RUN echo "date.timezone = America/Chicago" > /usr/local/etc/php/conf.d/timezone.ini
# Install dependencies required for Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY . /usr/src/app
WORKDIR /usr/src/app
RUN php /usr/local/bin/composer require voryx/thruway
RUN php /usr/local/bin/composer require thruway/pawl-transport
CMD ["php", "./index.php"]
