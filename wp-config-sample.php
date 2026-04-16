<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'megemit_database' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'vitalidad115' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'kR}E!G}8xD@[/_uJ)BKAQ#I*l~X%2@1r2ykL7HXNOJK-Z-fp2XlzKQ_Ml%{}UZ@6' );
define( 'SECURE_AUTH_KEY',  'p)>NVwLN4C>/bQcW<JSzamwP&2&=$1c[(i8CGiJRMjG_}:3U*t(SP5`xH;-nxo:%' );
define( 'LOGGED_IN_KEY',    'khBhG-bMP&j13b-gfC@3w8|SU0,W_0hhlTosQ) ^(B3l.6rX4p0HXB%Yfuy-1>:)' );
define( 'NONCE_KEY',        ';~cyp:O;s$y.1VxG4h| CdfV{[* 3Z4-mK-/h]M!,i#k@i+Vt|6CH=47o1:pe`>[' );
define( 'AUTH_SALT',        'Dp;;H86H111</7hh|!s%:`)^XfH^}TvB_(+TDlNc5~4+0nM:8?VS||(JY[Y?yay}' );
define( 'SECURE_AUTH_SALT', '78L8/MnP<A7;T6h]9i&^jZj13eh$O]e0sU,WC^faif~HC?Rd%(Imv&Rb_q#[Nlw/' );
define( 'LOGGED_IN_SALT',   'ou&n.g5L-%?&1xBoQ^hX3pQ@T(>Z,#/!Q@?*c:!o+j*i%@=9;d_#D2__y>]vKu/6' );
define( 'NONCE_SALT',       'pou#}[S:;*DI DH?GnCFY9m~K0(snG<FiNbm(]Q=z?nnt!YNs/ZE&!S/&7;!=jRC' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wpgr_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );
define( 'WP_MEMORY_LIMIT', '1024' );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
