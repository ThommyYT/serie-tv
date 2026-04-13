# setup.sh
docker compose -f .devcontainer/docker-compose.yml up -d --build
docker exec xampp-server sed -i 's|/opt/lampp/htdocs|/www|g' /opt/lampp/etc/httpd.conf
docker exec xampp-server /opt/lampp/bin/mariadb-upgrade -u root
docker exec xampp-server mkdir -p /opt/lampp/auth
docker exec xampp-server /opt/lampp/bin/htpasswd -b -c /opt/lampp/auth/.htpasswd admin 'S3rieTV_98!#xmpP'
docker exec xampp-server bash -c "echo '
<Directory \"/opt/lampp/phpmyadmin\">
    AuthType Basic
    AuthName \"Accesso Riservato - Inserisci Password\"
    AuthUserFile /opt/lampp/auth/.htpasswd
    Require valid-user
    Order allow,deny
    Allow from all
</Directory>' >> /opt/lampp/etc/httpd.conf"
docker exec xampp-server /opt/lampp/lampp restart
composer -d php install
npm install --prefix js
npm run build --prefix js
