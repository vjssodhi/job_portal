<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'portal');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'PzcK--IsVp0b%D[_|waK$7i3ZwljQcZMnOyY$Nq7;N3sS:`?sPm(;ZWsUqpwhhsf');
define('SECURE_AUTH_KEY',  '[76ix]3?A-].#M<-_oq{ArW%:;w|6696kCFApQ-EL[n|c&N:3>W~b=`-t~&6lo$9');
define('LOGGED_IN_KEY',    '&zKB7#})%%7]+FC~FA0AWOf.9XXpCdfW05&XqvzrS!`ysW8.a_,(bFkWW#|jH[}m');
define('NONCE_KEY',        'Y+@s2P;!~7ChUzw!L5b8Mh-HQO8/}kZ 7W/VXDL2#G7q_vI*h9Up@8ZPw%e<AAZ@');
define('AUTH_SALT',        'Ggh{3|Tw.*QkYE@Wzb-Ic<v%;#uo|8~)/]E,0Y_V!c@!YjDE,Mt5x.-v3WMtS{R^');
define('SECURE_AUTH_SALT', 'HL:*+@1RRr4AQF|GH?>+3,qnw++6@&=W5}aq]vP=uPT#DUM#g+_C L/~>z+T@0(2');
define('LOGGED_IN_SALT',   'tlp*)FA/Yu=%GM% w;@%|?{@Lm|-mi5Q7SE7+j,Q4JRQWf@:Ue[PdF?Jtj^h-Xnb');
define('NONCE_SALT',       'x ]3VfmXNf`od<p| 9O>gOT+a)6KNSrgH-+[CB2u<$*-9HMO-X?>VZ_Y~HY%X|_%');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
