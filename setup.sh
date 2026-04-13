# setup.sh
docker compose -f .devcontainer/docker-compose.yml up -d --build

# 1. Modifica DocumentRoot
docker exec xampp-server sed -i 's|/opt/lampp/htdocs|/www|g' /opt/lampp/etc/httpd.conf

# 2. DISABILITA Directory Indexing (Rimuove 'Indexes' dalla configurazione di /www)
docker exec xampp-server sed -i '/<Directory "\/www">/,/<\/Directory>/ s/Options Indexes/Options/' /opt/lampp/etc/httpd.conf

# 3. BLOCCO FILE SENSIBILI (.sql, .log, .bak, ecc.)
docker exec xampp-server bash -c "echo '
<Directory \"/www\">
    <FilesMatch \"\.(sql|sqlite|log|conf|bak|env|ini|sh|md)$\">
        Require all denied
    </FilesMatch>
    <FilesMatch \"^\.\">
        Require all denied
    </FilesMatch>
</Directory>' >> /opt/lampp/etc/httpd.conf"

# Configurazione database e auth esistente
docker exec xampp-server /opt/lampp/bin/mariadb-upgrade -u root
docker exec xampp-server mkdir -p /opt/lampp/auth
docker exec xampp-server /opt/lampp/bin/htpasswd -b -c /opt/lampp/auth/.htpasswd admin 'S3rieTV_98!#xmpP'

# Protezione phpMyAdmin esistente
docker exec xampp-server bash -c "echo '
<Directory \"/opt/lampp/phpmyadmin\">
    # Forza il server a dare priorità a queste regole
    AllowOverride AuthConfig
    AuthType Basic
    AuthName \"Accesso Riservato - Inserisci Password\"
    AuthUserFile /opt/lampp/auth/.htpasswd
    Require valid-user
    
    # Sovrascrive eventuali permessi "granted" precedenti
    Order allow,deny
    Allow from all
</Directory>' >> /opt/lampp/etc/httpd.conf"

# Riavvio e installazione dipendenze
docker exec xampp-server /opt/lampp/lampp restart
composer -d php install
npm install --prefix js
npm run build --prefix js
