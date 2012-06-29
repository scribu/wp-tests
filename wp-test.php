<?php
/**
 * wp-test.php
 *
 * WordPress Testrunner
 *
 * Examples:
 *
 * # php wp-test.php
 * # php wp-test.php -l
 * # php wp-test.php -t TestImageMetaFunctions,TestImageSizeFunctions
 *
 * Command line options:
 *   - -d  Set WP_DEBUG to true
 *   - -f  Force known bugs
 *   - -h  Help
 *   - -l  List all tests
 *   - -m  Test multisite
 *   - -n  Do not clean up the database at the end of the run
 *   - -p  PHPUnit --verbose
 *   - -q  Save queries
 *   - -r  Uses the given path as the WP_DIR to be tested.  Overrides -v.
 *   - -s  Skip known bugs
 *   - -t  Specific test class names to be run (separated by spaces or commas)
 *   - -v  Sets WP_DIR to "DIR_TESTROOT/wordpress-<value>".  Overridden by -r.
 *   - -g  Runs a group of test cases by matching part of the test class name
 */

// parse options
$options = 'g:r:t:v:dfhlmnpqs';
if (is_callable('getopt')) {
	$opts = getopt($options);
} else {
	include( dirname(__FILE__) . '/wp-testlib/getopt.php' );
	$opts = getoptParser::getopt($options);
}

if (isset($opts['h'])) {
	echo <<<EOH
WordPress Testrunner

Usage: php wp-test.php [arguments]

Examples:
# php wp-test.php
# php wp-test.php -l
# php wp-test.php -t TestImageMetaFunctions,TestImageSizeFunctions

Arguments:
  -d  Set WP_DEBUG to true
  -f  Force known bugs
  -h  Help
  -l  List all tests
  -m  Test multisite
  -n  Do not clean up the database at the end of the run
  -p  PHPUnit --verbose
  -q  Save queries
  -r  Uses the given path as the WP_DIR to be tested.  Overrides -v.
  -s  Skip known bugs
  -t  Specific test class names to be run (separated by spaces or commas)
  -v  Sets WP_DIR to "DIR_TESTROOT/wordpress-<value>".  Overridden by -r.
  -g  Runs a group of test cases by matching part of the test class name

EOH;
	exit(1);
}

define('DIR_TESTROOT', realpath(dirname(__FILE__)));
if (!defined('DIR_TESTCASE')) {
	define('DIR_TESTCASE', './wp-testcase');
}
if (!defined('DIR_TESTDATA'))
	define('DIR_TESTDATA', './wp-testdata');
define('TEST_WP', true);
define('TEST_MS', isset( $opts['m'] ) );
define('TEST_SKIP_KNOWN_BUGS', array_key_exists('s', $opts));
define('TEST_FORCE_KNOWN_BUGS', array_key_exists('f', $opts));
define('WP_DEBUG', array_key_exists('d', $opts) );
define('SAVEQUERIES', array_key_exists('q', $opts) );
define('WP_PHPUNIT_VERBOSE', array_key_exists('p', $opts));

if (!empty($opts['r']))
	define('DIR_WP', realpath($opts['r']));
else
	if (!empty($opts['v']))
		define('DIR_WP', DIR_TESTROOT.'/wordpress-'.$opts['v']);
	else
		define('DIR_WP', DIR_TESTROOT.'/wordpress');

// make sure all useful errors are displayed during setup
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', true);

require_once(DIR_TESTROOT.'/wp-testlib/base.php');
require_once(DIR_TESTROOT.'/wp-testlib/utils.php');

// configure wp

if ( TEST_MS ) {
	define( 'MULTISITE', true );
	define( 'SUBDOMAIN_INSTALL', false );
	define( 'DOMAIN_CURRENT_SITE', 'localhost' );
	define( 'PATH_CURRENT_SITE', '/' );
	define( 'SITE_ID_CURRENT_SITE', 1 );
	define( 'BLOG_ID_CURRENT_SITE', 1 );
}

require_once(DIR_TESTROOT.'/wp-config.php');
define('ABSPATH', realpath(DIR_WP).'/');

if (!defined('DIR_TESTPLUGINS'))
	define('DIR_TESTPLUGINS', './wp-plugins');


// install wp
define('WP_BLOG_TITLE', rand_str());
define('WP_USER_NAME', rand_str());
define('WP_USER_EMAIL', rand_str().'@example.com');


// initialize wp
define('WP_INSTALLING', 1);
$_SERVER['PATH_INFO'] = $_SERVER['SCRIPT_NAME']; // prevent a warning from some sloppy code in wp-settings.php
require_once(ABSPATH.'wp-settings.php');

// override stuff
require_once(DIR_TESTROOT.'/wp-testlib/mock-mailer.php');
$GLOBALS['phpmailer'] = new MockPHPMailer();

// Allow tests to override wp_die
add_filter( 'wp_die_handler', '_wp_die_handler_filter' );

drop_tables();

require_once(ABSPATH.'wp-admin/includes/upgrade.php');
wp_install(WP_BLOG_TITLE, WP_USER_NAME, WP_USER_EMAIL, true);

if ( TEST_MS ) {
	install_network();
	$base = $path = '/';
	$domain = 'localhost';
	populate_network( 1, $domain, WP_USER_EMAIL, 'Test Site', $base, false );
	// Pulled from the !is_multiste() blocks in populate_network().  These don't run in
	// the call to populate_network() above due to setting MULTISITE earlier in wp-test.php.
	$site_user = get_user_by( 'email', WP_USER_EMAIL );
	$site_admins = array( $site_user->user_login );
	$users = get_users( array( 'fields' => array( 'ID', 'user_login' ) ) );
	if ( $users ) {
		foreach ( $users as $user ) {
			if ( is_super_admin( $user->ID ) && !in_array( $user->user_login, $site_admins ) )
				$site_admins[] = $user->user_login;
		}
	}
	update_site_option( 'site_admins', $site_admins );

	$wpdb->insert( $wpdb->blogs, array( 'site_id' => 1, 'domain' => $domain, 'path' => $path, 'registered' => current_time( 'mysql' ) ) );
	$blog_id = $wpdb->insert_id;
	update_user_meta( $site_user->ID, 'source_domain', $domain );
	update_user_meta( $site_user->ID, 'primary_blog', $blog_id );
	if ( !$upload_path = get_option( 'upload_path' ) ) {
		$upload_path = substr( WP_CONTENT_DIR, strlen( ABSPATH ) ) . '/uploads';
		update_option( 'upload_path', $upload_path );
	}
	update_option( 'fileupload_url', get_option( 'siteurl' ) . '/' . $upload_path );

	// wp-settings.php would normally init this stuff, but that doesn't work because we've
	// only just installed
	$GLOBALS['blog_id'] = 1;
	$GLOBALS['wpdb']->blogid = 1;
	$GLOBALS['current_blog'] = $GLOBALS['wpdb']->get_results('SELECT * from wp_blogs where blog_id = 1');
}

// make sure we're installed
assert(true == is_blog_installed());

// include plugins for testing, if any
if (is_dir(DIR_TESTPLUGINS)) {
	$plugins = glob(realpath(DIR_TESTPLUGINS).'/*.php');
	foreach ($plugins as $plugin)
		include_once($plugin);
}

// needed for jacob's tests
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . ABSPATH . '/wp-includes');
define('PHPUnit_MAIN_METHOD', false);
$original_wpdb = $GLOBALS['wpdb'];

include_once(DIR_TESTDATA . '/sample_blogs.php');
// include all files in DIR_TESTCASE, and fetch all the WPTestCase descendents
$files = wptest_get_all_test_files(DIR_TESTCASE);
foreach ($files as $file) {
	require_once($file);
}
$classes = wptest_get_all_test_cases();

// some of jacob's tests clobber the wpdb object, so restore it
$GLOBALS['wpdb'] = $original_wpdb;

if ( isset($opts['l']) ) {
	wptest_listall_testcases($classes);
} else {
	do_action('test_start');

	// hide warnings during testing, since that's the normal WP behaviour
	if ( !WP_DEBUG ) {
		error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE );
	}
	// run the tests and print the results
	list ($result, $printer) = wptest_run_tests($classes, isset($opts['t']) ? $opts['t'] : array(), isset($opts['g']) ? $opts['g'] : null );
	wptest_print_result($printer,$result);
}
if ( !isset($opts['n']) ) {
	// clean up the database
	drop_tables();
}
?>
