# Multi-stage build for optimized container
FROM alpine:3.19

# Install packages
RUN apk add --no-cache \
    nginx \
    php82 \
    php82-fpm \
    php82-json \
    php82-mbstring \
    php82-session \
    php82-openssl \
    supervisor \
    curl \
    && rm -rf /var/cache/apk/*

# Create directories
RUN mkdir -p /var/www/html/data \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /run/nginx \
    && mkdir -p /run/php

# Copy configuration files
COPY nginx.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application files
COPY guestbook.php /var/www/html/

# Set ownership and permissions
RUN chown -R nginx:nginx /var/www/html \
    && chmod 755 /var/www/html \
    && chmod 755 /var/www/html/data \
    && chmod 644 /var/www/html/*.php

# Configure PHP-FPM
RUN sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php82/php.ini \
    && sed -i 's/user = nobody/user = nginx/' /etc/php82/php-fpm.d/www.conf \
    && sed -i 's/group = nobody/group = nginx/' /etc/php82/php-fpm.d/www.conf \
    && sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /etc/php82/php-fpm.d/www.conf

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/guestbook.php || exit 1

# Start services with supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]