<?php
class TestWP_Scripts extends WPTestCase {
	var $old_wp_scripts;

	function setUp() {
		parent::setUp();
		$this->old_wp_scripts = $GLOBALS['wp_scripts'];
		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_scripts']->default_version = get_bloginfo( 'version' );

	}

	function tearDown() {
		$GLOBALS['wp_scripts'] = $this->old_wp_scripts;
		add_action( 'wp_default_scripts', 'wp_default_scripts' );
		parent::tearDown();
	}

	// Test versioning
	function test_wp_enqueue_script() {
		$this->knownWPBug(11315);
		wp_enqueue_script('no-deps-no-version', 'example.com', array());
		wp_enqueue_script('empty-deps-no-version', 'example.com' );
		wp_enqueue_script('empty-deps-version', 'example.com', array(), 1.2);
		wp_enqueue_script('empty-deps-null-version', 'example.com', array(), null);
		$ver = get_bloginfo( 'version' );
		$expected  = "<script type='text/javascript' src='http://example.com?ver=$ver'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com?ver=$ver'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com?ver=1.2'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com'></script>\n";

		$this->assertEquals($expected, get_echo('wp_print_scripts'));

		// No scripts left to print
		$this->assertEquals("", get_echo('wp_print_scripts'));
	}
	
	/**
	 * Test the different protocol references in wp_enqueue_script
	 * @global WP_Scripts $wp_scripts
	 */
	public function test_protocols() {
		$this->knownWPBug( 16560 );

		// Init
		global $wp_scripts;
		$base_url_backup = $wp_scripts->base_url;
		$wp_scripts->base_url = 'http://example.com/wordpress';
		$expected = '';
		$ver = get_bloginfo( 'version' );

		// Try with an HTTP reference
		wp_enqueue_script( 'jquery-http', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected  .= "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver'></script>\n";

		// Try with an HTTPS reference
		wp_enqueue_script( 'jquery-https', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected  .= "<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver'></script>\n";
		
		// Try with an automatic protocol reference (//)
		wp_enqueue_script( 'jquery-doubleslash', '//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected  .= "<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver'></script>\n";

		// Try with a local resource and an automatic protocol reference (//)
		$url = '/' . plugins_url( '/my_plugin/script.js');
		wp_enqueue_script( 'plugin-script', $url  );
		$expected  .= "<script type='text/javascript' src='$url?ver=$ver'></script>\n";

		// Try with a bad protocol
		wp_enqueue_script( 'jquery-ftp', 'ftp://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected  .= "<script type='text/javascript' src='{$wp_scripts->base_url}ftp://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver'></script>\n";
		
		// Go!
		$this->assertEquals( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print
		$this->assertEquals( '', get_echo( 'wp_print_scripts' ) );

		// Cleanup
		$wp_scripts->base_url = $base_url_backup;
	}
}
?>