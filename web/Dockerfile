# Use a slim Python base (Debian-based)
FROM python:3.12-slim

# Install nginx, php-fpm, supervisor, build tools (for pip wheels)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx php-fpm supervisor build-essential ca-certificates \
 && rm -rf /var/lib/apt/lists/*

# Working directory
WORKDIR /app

# Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Application code
COPY app/ ./app

# Nginx + Supervisor configs
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# provide a PHP root (for /auth)
COPY php/ /var/www/

# Redirect nginx logs to Docker stdout/stderr
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
 && ln -sf /dev/stderr /var/log/nginx/error.log

# Expose only 8443 (nginx)
EXPOSE 8443

# Start supervisor (runs nginx + php-fpm + Pyramid)
CMD ["/usr/bin/supervisord","-c","/etc/supervisor/conf.d/supervisord.conf"]
