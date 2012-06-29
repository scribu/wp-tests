<?php
// Test functions that fetch stuff from the theme directory
class TestPageTemplates extends _WPEmptyBlog {
	function setUp() {
		parent::setUp();
		$this->theme_root = realpath(DIR_TESTROOT.'/'.DIR_TESTDATA.'/themedir1');

		$this->orig_theme_dir = $GLOBALS['wp_theme_directories'];
		$GLOBALS['wp_theme_directories'] = array( WP_CONTENT_DIR . '/themes', $this->theme_root );

		add_filter('theme_root', array(&$this, '_theme_root'));
		add_filter( 'stylesheet_root', array(&$this, '_theme_root') );
		add_filter( 'template_root', array(&$this, '_theme_root') );

		// clear caches
		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );
	}

	function tearDown() {
		$GLOBALS['wp_theme_directories'] = $this->orig_theme_dir;
		remove_filter('theme_root', array(&$this, '_theme_root'));
		remove_filter( 'stylesheet_root', array(&$this, '_theme_root') );
		remove_filter( 'template_root', array(&$this, '_theme_root') );

		wp_clean_themes_cache();
		parent::tearDown();
	}

	// replace the normal theme root dir with our premade test dir
	function _theme_root($dir) {
		return $this->theme_root;
	}

	function test_page_templates() {
		$this->knownWPBug(10959);
		$this->knownWPBug(11216);
		$theme = get_theme('Page Template Theme');
		$this->assertFalse( empty($theme) );

		switch_theme($theme['Template'], $theme['Stylesheet']);

		$templates = get_page_templates();
		$this->assertEquals(3, count($templates));
		$this->assertEquals("template-top-level.php", $templates['Top Level']);
		$this->assertEquals("subdir/template-sub-dir.php", $templates['Sub Dir']);
		$this->assertEquals("template-header.php", $templates['This Template Header Is On One Line']);
	}
}
?>
