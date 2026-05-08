FROM php:8.2-fpm

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    postgresql-client \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy composer installer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy backend code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Create uploads directory and set permissions
RUN mkdir -p uploads/requests uploads/completions \
    && chmod -R 755 uploads

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8000/api/auth/login.php || exit 1

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000"]
