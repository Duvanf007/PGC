FROM php:8.2-apache

# Instalar extensiones PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar proyecto
COPY . /var/www/html/

# Permisos para uploads
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Configurar Apache para escuchar en el puerto dinámico de Render ($PORT)
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

ENV PORT=80
EXPOSE ${PORT}

CMD ["apache2-foreground"]
