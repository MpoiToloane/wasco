FROM php:8.2-cli

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Expose Railway port
EXPOSE 80

# Start PHP server
CMD ["php", "-S", "0.0.0.0:80", "-t", "."]
