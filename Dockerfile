FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    default-mysql-client \
    curl \
    gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY . /app

RUN mkdir -p /app/uploads && chmod -R 775 /app/uploads

RUN if [ -f package.json ]; then npm install --omit=dev; fi

EXPOSE 10000

CMD ["./start.sh"]

