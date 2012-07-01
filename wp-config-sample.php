<?php

/* Path to the WordPress codebase you'd like to test. Add a backslash in the end. */
define( 'ABSPATH', dirname( __FILE__ ) . '/wordpress/' );

// ** MySQL settings ** //

// these will be used by the copy of wordpress being tested.
// wordpress/wp-config.php will be ignored.

// WARNING WARNING WARNING!
// wp-test will DROP ALL TABLES in the database named below.
// DO NOT use a production database or one that is shared with something else.

define( 'DB_NAME', 'putyourdbnamehere' );    // The name of the database
define( 'DB_USER', 'usernamehere' );     // Your MySQL username
define( 'DB_PASSWORD', 'yourpasswordhere' ); // ...and password
define( 'DB_HOST', 'localhost' );    // 99% chance you won't need to change this value
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// You can have multiple installations in one database if you give each a unique prefix
$table_prefix  = 'wp_';   // Only numbers, letters, and underscores please!

// Change this to localize WordPress.  A corresponding MO file for the
// chosen language must be installed to wp-content/languages.
// For example, install de.mo to wp-content/languages and set WPLANG to 'de'
// to enable German language support.
define ( 'WPLANG', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_TESTS_MULTISITE', false );
 
/* Cron tries to make an HTTP request to the blog, which always fails, because tests are run in CLI mode only */
define( 'DISABLE_WP_CRON', true );

$table_prefix  = 'wp_';

define( 'WP_PHP_BINARY', 'php' );
