<?php
/**
 * Stateless wp-config.php — every environment-specific value comes from
 * environment variables injected by the platform (Azure App Service
 * Application Settings, Key Vault references, or docker-compose for local dev).
 * The image itself is identical across dev/hml/prd.
 */

define( 'DB_NAME', getenv( 'WORDPRESS_DB_NAME' ) ?: 'wordpress' );
define( 'DB_USER', getenv( 'WORDPRESS_DB_USER' ) ?: 'wordpress' );
define( 'DB_PASSWORD', getenv( 'WORDPRESS_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'WORDPRESS_DB_HOST' ) ?: 'localhost' );
define( 'DB_CHARSET', getenv( 'WORDPRESS_DB_CHARSET' ) ?: 'utf8mb4' );
define( 'DB_COLLATE', getenv( 'WORDPRESS_DB_COLLATE' ) ?: '' );

$table_prefix = getenv( 'WORDPRESS_TABLE_PREFIX' ) ?: 'wp_';

// Auth keys/salts — no fallback on purpose: prod/hml must fail fast if the
// Key Vault-backed App Settings weren't wired up, rather than boot insecurely.
foreach ( array(
	'AUTH_KEY',
	'SECURE_AUTH_KEY',
	'LOGGED_IN_KEY',
	'NONCE_KEY',
	'AUTH_SALT',
	'SECURE_AUTH_SALT',
	'LOGGED_IN_SALT',
	'NONCE_SALT',
) as $unique_key_name ) {
	$env_value = getenv( 'WORDPRESS_' . $unique_key_name );
	if ( $env_value ) {
		define( $unique_key_name, $env_value );
	}
}

// Home/site URL fixed per environment instead of left to the siteurl option,
// so dev/hml/prd never drift or get overwritten by a DB restore between envs.
if ( getenv( 'WORDPRESS_HOME' ) ) {
	define( 'WP_HOME', getenv( 'WORDPRESS_HOME' ) );
	define( 'WP_SITEURL', getenv( 'WORDPRESS_SITEURL' ) ?: getenv( 'WORDPRESS_HOME' ) );
}

// Azure App Service terminates TLS at the front door and proxies plain HTTP
// to the container; without this WordPress thinks every request is insecure.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

define( 'FORCE_SSL_ADMIN', filter_var( getenv( 'WORDPRESS_FORCE_SSL_ADMIN' ) ?: true, FILTER_VALIDATE_BOOLEAN ) );

// The container filesystem is disposable and not writable across instances/restarts:
// block in-dashboard file edits, plugin/theme installs and core auto-updates.
// Ship all code changes through the image instead.
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', filter_var( getenv( 'WORDPRESS_DISALLOW_FILE_MODS' ) ?: true, FILTER_VALIDATE_BOOLEAN ) );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_AUTO_UPDATE_CORE', false );

// With multiple/scaled instances, per-request pseudo-cron fires redundantly
// and unreliably. Disable it and trigger wp-cron.php from an external scheduler
// (Azure Scheduler / Logic App / WebJob hitting one instance) instead.
define( 'DISABLE_WP_CRON', filter_var( getenv( 'WORDPRESS_DISABLE_WP_CRON' ) ?: true, FILTER_VALIDATE_BOOLEAN ) );

define( 'WP_DEBUG', filter_var( getenv( 'WORDPRESS_DEBUG' ) ?: false, FILTER_VALIDATE_BOOLEAN ) );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_ENVIRONMENT_TYPE', getenv( 'WORDPRESS_ENVIRONMENT_TYPE' ) ?: 'production' );

// Optional external object cache (e.g. Azure Cache for Redis) — keeps object
// caching working the same way whether there's one instance or several.
if ( getenv( 'WORDPRESS_REDIS_HOST' ) ) {
	define( 'WP_REDIS_HOST', getenv( 'WORDPRESS_REDIS_HOST' ) );
	define( 'WP_REDIS_PORT', getenv( 'WORDPRESS_REDIS_PORT' ) ?: 6380 );
	define( 'WP_REDIS_PASSWORD', getenv( 'WORDPRESS_REDIS_PASSWORD' ) ?: '' );
	define( 'WP_REDIS_SCHEME', getenv( 'WORDPRESS_REDIS_SCHEME' ) ?: 'tls' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
