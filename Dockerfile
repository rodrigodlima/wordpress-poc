FROM wordpress:php8.3-apache

# The base image's entrypoint copies /usr/src/wordpress into /var/www/html on
# every container start (it's disposable — no persistent volume needed there).
# Anything placed here, including this wp-config.php, ships as part of the
# same immutable image for dev/hml/prd; only the env vars change per environment.
COPY wp-config.php /usr/src/wordpress/wp-config.php

# Once the existing site's code is added to this repo, uncomment to bake it in:
# COPY wp-content/ /usr/src/wordpress/wp-content/

EXPOSE 80
