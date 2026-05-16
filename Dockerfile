FROM php:8.2-apache

# 1. Inštalácia rozšírení pre MySQL (PDO aj mysqli pre istotu)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. Zapnutie modulu rewrite, aby fungoval .htaccess
RUN a2enmod rewrite

# 3. NEPRIESTRELNÉ NASTAVENIE PRÁV: 
# Tento príkaz natvrdo prepíše konfiguráciu Apache, aby POVOLIL čítanie .htaccess (AllowOverride All)
# a zobrazovanie zoznamu súborov v priečinku /var/www/html.
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 4. Oprava práv k súborom (aby mal Apache prístup k priečinku www z Windows/Mac)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# 5. Odstránenie varovania pri štarte Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80