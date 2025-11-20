<?php
define( 'WP_CACHE', true );

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u768796151_iorganize' );

/** Database username */
define( 'DB_USER', 'u768796151_iorganize' );

/** Database password */
define( 'DB_PASSWORD', 'Iorganize123' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '#(yqT_z6$-sx+0EKj+//$OC2_=}<Qo>eX<([B56`BnqV}(BOJ=a`9{.hfQD*Pj*(' );
define( 'SECURE_AUTH_KEY',   'yG:;QHG=h_NsEh]{q;dE-,xSr{8c;CM+H?*Ki?S4fJ}B{(#*9cYSU[x<?xXxLpFu' );
define( 'LOGGED_IN_KEY',     '+&M`Xt(H$7-&~]lo;j^O ~mF7fv>X(m#*%&W%{<fZ< 7G(WMz3SqEA8,U^v~X,pZ' );
define( 'NONCE_KEY',         'q{{)YaE$9u!WiNZ/U30Wt(gRiJB1se_?D(]k9U#[.8m~}:Pcc9U1+u;]}Xt.W]Y4' );
define( 'AUTH_SALT',         'k-gCO]ViS7J|c36;&Q=hz~p:+hgvbIP9VS;o#,+pgevZw%:S>tqlYHB3@nr.*6DS' );
define( 'SECURE_AUTH_SALT',  '!/B>z%HI0SOZFz5[OsJIUS/1oufm2t+.B:2300{5P}&Y{!5%{|GJ+o#~8mlQmmaJ' );
define( 'LOGGED_IN_SALT',    'm<oQa=TJz[;,>iE8UBbq(A`,Jak#C~ac[/mw5X<TE9EP8zYp#`X^;T4M_HzUG)|1' );
define( 'NONCE_SALT',        'TLmuP ijhu4,%VtrluUOJ8z5qD&W]23nnyKjtPtdX=(zCH$v(e1SmcA?pOfJ@>i`' );
define( 'WP_CACHE_KEY_SALT', ':OV?(oW:T;[Ma7>$XAJ1m{Y+Zs7Nh*}hC2*4|wIL813t9|M/Xzkzp9@n<}RZo{//' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '25606a679a771b6bc5ff489b98add2a5' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
