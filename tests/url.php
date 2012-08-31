<?php

// tests for link-template.php and related URL functions
class Tests_URL extends WP_UnitTestCase {
	var $_old_server;
	function setUp() {
		parent::setUp();
		$this->_old_server = $_SERVER;
	}

	function tearDown() {
		$_SERVER = $this->_old_server;
		parent::tearDown();
	}

	function test_is_ssl_positive() {
		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue( is_ssl() );

		$_SERVER['HTTPS'] = 'ON';
		$this->assertTrue( is_ssl() );

		$_SERVER['HTTPS'] = '1';
		$this->assertTrue( is_ssl() );

		unset( $_SERVER['HTTPS'] );
		$_SERVER['SERVER_PORT'] = '443';
		$this->assertTrue( is_ssl() );
	}

	function test_is_ssl_negative() {
		$_SERVER['HTTPS'] = 'off';
		$this->assertFalse( is_ssl() );

		$_SERVER['HTTPS'] = 'OFF';
		$this->assertFalse( is_ssl() );

		unset($_SERVER['HTTPS']);
		$this->assertFalse( is_ssl() );
	}

	function test_admin_url_valid() {
		$paths = array(
			'' => "/wp-admin/",
			'foo' => "/wp-admin/foo",
			'/foo' => "/wp-admin/foo",
			'/foo/' => "/wp-admin/foo/",
			'foo.php' => "/wp-admin/foo.php",
			'/foo.php' => "/wp-admin/foo.php",
			'/foo.php?bar=1' => "/wp-admin/foo.php?bar=1",
		);
		$https = array('on', 'off');

		foreach ($https as $val) {
			$_SERVER['HTTPS'] = $val;
			$siteurl = get_option('siteurl');
			if ( $val == 'on' )
				$siteurl = str_replace('http://', 'https://', $siteurl);

			foreach ($paths as $in => $out) {
				$this->assertEquals( $siteurl.$out, admin_url($in), "admin_url('{$in}') should equal '{$siteurl}{$out}'");
			}
		}
	}

	function test_admin_url_invalid() {
		$paths = array(
			null => "/wp-admin/",
			0 => "/wp-admin/",
			-1 => "/wp-admin/",
			'../foo/' => "/wp-admin/",
			'///' => "/wp-admin/",
		);
		$https = array('on', 'off');

		foreach ($https as $val) {
			$_SERVER['HTTPS'] = $val;
			$siteurl = get_option('siteurl');
			if ( $val == 'on' )
				$siteurl = str_replace('http://', 'https://', $siteurl);

			foreach ($paths as $in => $out) {
				$this->assertEquals( $siteurl.$out, admin_url($in), "admin_url('{$in}') should equal '{$siteurl}{$out}'");
			}
		}
	}

	function test_home_url_valid() {
		$paths = array(
			'' => "",
			'foo' => "/foo",
			'/foo' => "/foo",
			'/foo/' => "/foo/",
			'foo.php' => "/foo.php",
			'/foo.php' => "/foo.php",
			'/foo.php?bar=1' => "/foo.php?bar=1",
		);
		$https = array('on', 'off');

		foreach ($https as $val) {
			$_SERVER['HTTPS'] = $val;
			$home = get_option('home');
			if ( $val == 'on' )
				$home = str_replace('http://', 'https://', $home);

			foreach ($paths as $in => $out) {
				$this->assertEquals( $home.$out, home_url($in), "home_url('{$in}') should equal '{$home}{$out}'");
			}
		}
	}

	function test_home_url_invalid() {
		$paths = array(
			null => "",
			0 => "",
			-1 => "",
			'../foo/' => "",
			'///' => "/",
		);
		$https = array('on', 'off');

		foreach ($https as $val) {
			$_SERVER['HTTPS'] = $val;
			$home = get_option('home');
			if ( $val == 'on' )
				$home = str_replace('http://', 'https://', $home);

			foreach ($paths as $in => $out) {
				$this->assertEquals( $home.$out, home_url($in), "home_url('{$in}') should equal '{$home}{$out}'");
			}
		}
	}

	function test_home_url_from_admin() {
		$screen = get_current_screen();

		// Pretend to be in the site admin
		set_current_screen( 'dashboard' );
		$home = get_option('home');

		// home_url() should return http when in the admin
		$_SERVER['HTTPS'] = 'on';
		$this->assertEquals( $home, home_url() );

		$_SERVER['HTTPS'] = 'off';
		$this->assertEquals( $home, home_url() );

		// If not in the admin, is_ssl() should determine the scheme
		set_current_screen( 'front' );
		$this->assertEquals( $home, home_url() );
		$_SERVER['HTTPS'] = 'on';
		$home = str_replace('http://', 'https://', $home);
		$this->assertEquals( $home, home_url() );
	}

	function test_network_home_url_from_admin() {
		$screen = get_current_screen();

		// Pretend to be in the site admin
		set_current_screen( 'dashboard' );
		$home = network_home_url();

		// home_url() should return http when in the admin
		$this->assertEquals( 0, strpos( $home, 'http://') );
		$_SERVER['HTTPS'] = 'on';
		$this->assertEquals( $home, network_home_url() );

		$_SERVER['HTTPS'] = 'off';
		$this->assertEquals( $home, network_home_url() );

		// If not in the admin, is_ssl() should determine the scheme
		set_current_screen( 'front' );
		$this->assertEquals( $home, network_home_url() );
		$_SERVER['HTTPS'] = 'on';
		$home = str_replace('http://', 'https://', $home);
		$this->assertEquals( $home, network_home_url() );
	}

	function test_set_url_scheme() {
		if ( ! function_exists( 'set_url_scheme' ) )
			return;

		$links = array(
			'http://wordpress.org/',
			'https://wordpress.org/',
			'http://wordpress.org/news/',
			'http://wordpress.org',
		);

		$https_links = array(
			'https://wordpress.org/',
			'https://wordpress.org/',
			'https://wordpress.org/news/',
			'https://wordpress.org',
		);

		$http_links = array(
			'http://wordpress.org/',
			'http://wordpress.org/',
			'http://wordpress.org/news/',
			'http://wordpress.org',
		);

		$relative_links = array(
			'/',
			'/',
			'/news/',
			''
		);

		$forced_admin = force_ssl_admin();
		$forced_login = force_ssl_login();
		$i = 0;
		foreach ( $links as $link ) {
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'https' ) );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'http' ) );
			$this->assertEquals( $relative_links[ $i ], set_url_scheme( $link, 'relative' ) );

			$_SERVER['HTTPS'] = 'on';
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link ) );

			$_SERVER['HTTPS'] = 'off';
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link ) );

			force_ssl_login( false );
			force_ssl_admin( true );
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'admin' ) );
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'login_post' ) );
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'login' ) );
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'rpc' ) );

			force_ssl_admin( false );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'admin' ) );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'login_post' ) );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'login' ) );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'rpc' ) );

			force_ssl_login( true );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'admin' ) );
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'login_post' ) );
			$this->assertEquals( $http_links[ $i ], set_url_scheme( $link, 'login' ) );
			$this->assertEquals( $https_links[ $i ], set_url_scheme( $link, 'rpc' ) );

			$i++;
		}

		force_ssl_admin( $forced_admin );
		force_ssl_login( $forced_login );
	}

	function test_get_adjacent_post() {
		$post_id = $this->factory->post->create();
		sleep( 1 ); // get_adjacent_post() doesn't handle posts created in the same second.
		$post_id2 = $this->factory->post->create();

		$orig_post = $GLOBALS['post'];
		$GLOBALS['post'] = get_post( $post_id2 );

		$p = get_adjacent_post();
		$this->assertInstanceOf( 'WP_Post', $p );
		$this->assertEquals( $post_id, $p->ID );

		// The same again to make sure a cached query returns the same result
		$p = get_adjacent_post();
		$this->assertInstanceOf( 'WP_Post', $p );
		$this->assertEquals( $post_id, $p->ID );

		// Test next
		$p = get_adjacent_post( false, '', false );
		$this->assertEquals( '', $p );

		unset( $GLOBALS['post'] );
		$this->assertNull( get_adjacent_post() );

		$GLOBALS['post'] = $orig_post;

		// Tests requiring creating more posts can't be run since the query
		// cache in get_adjacent_post() requires a fresh page load to invalidate.
	}
}
