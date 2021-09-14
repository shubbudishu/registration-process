<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'registration_process' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
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
define( 'AUTH_KEY',         'JdEJ:GmgMFY6.`VF:e>Wz/&:QC=Al j1uXSi(9 F.(.@E5dHyb0Ylj0yi>}X!v-i' );
define( 'SECURE_AUTH_KEY',  '*e`y0^wF2HmU0(v@-AP2LzyBBi44aq5&c*[MP#S%EWS;kypGtHF?{hE)no$DqA;{' );
define( 'LOGGED_IN_KEY',    'r,Y47AOidb4r1bn6FBXwZCrSP(je[`JG +g2~R 9?e$DB4Bu1!b&dm_C{Sw6#Jeb' );
define( 'NONCE_KEY',        '`p4 S/o`eUIr0/A`e-%{R2!jv5h3$J /r!kN!;gj[S99<ds-!}O2[U0W6lFa3E?A' );
define( 'AUTH_SALT',        '{mNfJS{>_.~D%PaM0wtlA:4I0w0z8@U$+`J2ZW`]HSyOL7A_:yA iQeV odZ&%#,' );
define( 'SECURE_AUTH_SALT', ',%lN#{Ns4sJH07N&T4!`i~&eG$|s?{y1n:vtw-*leSaZ|]%+um}p2kLn5_jqm<4O' );
define( 'LOGGED_IN_SALT',   'DF V?+pKmPDspI.(7ky8$Z=cBd!j2WcY5ZP*)rf~*sFebCvu%wbNnhT 7?T!RafH' );
define( 'NONCE_SALT',       'Qy#P?{_=VV8-MGY6r+(E4N${K#~W}Osu+{>qC`sLVdw*J4 n}d>J~W:q5$;m0|3N' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
