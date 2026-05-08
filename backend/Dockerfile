FROM php:8.2-cli

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    postgresql-client \
    libpq-dev \
    curl \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy composer installer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy backend code
COPY . .

# Install PHP dependencies with increased memory limit
RUN composer install --no-dev --optimize-autoloader -n --ignore-platform-reqs \
    || composer install --no-dev --optimize-autoloader --no-scripts

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
