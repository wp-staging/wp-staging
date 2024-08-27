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

// ** MySQL settings ** //

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'cwC1]Y4Qz<-jS?V W0U*CbW~$yY9U@=-t5X{][-S0GF~LqY yt[ChoYm@?`}iJzm');
define('SECURE_AUTH_KEY', 'dmZ5$.d:gCbUxX)FZ+[h-t81O>Yd5W!x<:D[q;6{A?;Q0F>fvKcjaM V0V?-XD(t');
define('LOGGED_IN_KEY', '{B%(DDM,))zFtDD8gLk;N^EK`iiG<V`XNZ/k~d]ne^t/MLN85o5ILrMWC!.:cq.X');
define('NONCE_KEY', 'tvt!~WL>)x{ ``SK6ZO^/R1lwZPerR?&(W>]h/da(Z^M$2?)ZDVsICxQV3?/h6)U');
define('AUTH_SALT', ';%e$?CJm$s-N!a;(B;NM/>_~gDuPa(VM1t:nUvQ+LZw;e]1)_`-qwCZe,-@^{Xd%');
define('SECURE_AUTH_SALT', '? nh*~x!7Jm^4E4w(y(qmaPK:$l5aB6!n6L}$IN+cXZSsE?<2~FfK|qN=s:=P+(c');
define('LOGGED_IN_SALT', '=Ty=Q):}E/[pW3y4IDN@Bas/&-MInTxFXziE)9H9^rnl g7TUj-7OP*UX2Oyz=Y$');
define('NONCE_SALT', 'HATY?A^EQ#F;oN8!W-oe5P%)aFaeU,E;rLFmRm&u-<g6tL9k(pyh77_,Kc _q2BY');
define('WP_CACHE_KEY_SALT', '5Y@>B@S8O{a w%ASH BX!;wu/RBk.HT[~R{csF.r)5f0q/YTy%$8lwV4o0eygz};');

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
