FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    cron

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create the log file to be able to run tail
RUN touch /var/log/cron.log

# Add crontab file in the cron directory
COPY ./crontab /etc/cron.d/symfony-cron

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d/symfony-cron

# Apply cron job
RUN crontab /etc/cron.d/symfony-cron

# Create the log file to be able to run tail
RUN touch /var/log/cron.log

CMD ["php-fpm"]