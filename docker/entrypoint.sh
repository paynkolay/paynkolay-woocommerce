#!/bin/bash
set -e

# Start Apache in the background
docker-entrypoint.sh apache2-foreground &
APACHE_PID=$!

# Wait for database
echo "Waiting for database..."
until mysqladmin ping -h db -u wordpress -pwordpress --silent 2>/dev/null; do
  sleep 2
done
echo "Database ready."

# Wait for wp-config.php to be generated
until [ -f /var/www/html/wp-config.php ]; do
  sleep 1
done
sleep 3

# Install WordPress if not yet installed
if ! wp core is-installed --allow-root 2>/dev/null; then
  echo "Installing WordPress..."
  wp core install \
    --url=http://localhost:8888 \
    --title="PayNKolay Test" \
    --admin_user=admin \
    --admin_password=password \
    --admin_email=test@test.com \
    --skip-email \
    --allow-root

  echo "Installing WooCommerce..."
  wp plugin install woocommerce --activate --allow-root

  mkdir -p /var/www/html/wp-content/uploads/wc-logs
  chown -R www-data:www-data /var/www/html/wp-content/uploads

  echo "Activating PayNKolay..."
  wp plugin activate paynkolay-woocommerce --allow-root

  echo "Configuring WooCommerce..."
  wp option update woocommerce_currency TRY --allow-root
  wp option update woocommerce_store_postcode 34000 --allow-root
  wp option update woocommerce_default_country TR --allow-root

  echo "Creating test products..."
  wp wc product create --name="Test Ürün 1" --regular_price=10 --status=publish --user=admin --allow-root
  wp wc product create --name="Test Ürün 2" --regular_price=250 --status=publish --user=admin --allow-root
  wp wc product create --name="Test Ürün 3" --regular_price=49.90 --status=publish --user=admin --allow-root

  echo "Done! http://localhost:8888"
fi

# Keep Apache in the foreground
wait $APACHE_PID
