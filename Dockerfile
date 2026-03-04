FROM php:8.2-apache

# Instalacja niezbędnych bibliotek dla SQLite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Włączenie modułu mod_rewrite
RUN a2enmod rewrite

# Ustawienie katalogu roboczego
WORKDIR /var/www/html

# Kopiowanie plików aplikacji
COPY . /var/www/html/

# Tworzenie katalogu na bazę i nadanie uprawnień dla użytkownika www-data
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/data

EXPOSE 80
