#!/bin/bash

# Garantir permissões nas pastas que serão criadas/montadas em volumes
chown -R www-data:www-data /var/www/html/contents 2>/dev/null || true
chown -R www-data:www-data /var/www/html/config 2>/dev/null || true
chown -R www-data:www-data /var/www/html/themes 2>/dev/null || true
chown -R www-data:www-data /var/www/html/files 2>/dev/null || true
chown -R www-data:www-data /var/www/html/cache 2>/dev/null || true
chown -R www-data:www-data /var/www/html/panel/data 2>/dev/null || true

# Iniciar o Apache em primeiro plano
apache2-foreground
