FROM php:8.2
RUN docker-php-ext-install mysqli
# Install dependencies required for Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN php /usr/local/bin/composer require voryx/thruway
RUN php /usr/local/bin/composer require thruway/pawl-transport
ADD ./ /var/www/html
