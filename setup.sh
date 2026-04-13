# setup.sh
docker compose -f .devcontainer/docker-compose.yml up -d --build
docker exec xampp-server sed -i 's|/opt/lampp/htdocs|/www|g' /opt/lampp/etc/httpd.conf
docker exec xampp-server /opt/lampp/lampp restart
docker exec xampp-server /opt/lampp/bin/mariadb-upgrade -u root
composer -d php install
npm install --prefix js
npm run build --prefix js
