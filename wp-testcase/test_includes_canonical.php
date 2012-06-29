<?php

/*
 * Tests Canonical redirections.
 *
 * In the process of doing so, it also tests WP, WP_Rewrite and WP_Query, A fail here may show a bug in any one of these areas.
 *
 */

class WP_Canonical extends _WPDataset2 {

	// This can be defined in a subclass of this class which contains it's own data() method, those tests will be run against the specified permastruct
	var $structure = '/%year%/%monthnum%/%day%/%postname%/';

	function SetUp() {
		parent::SetUp();

		update_option('permalink_structure', $this->structure);
		update_option('comments_per_page', 5);

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure($this->structure);
		create_initial_taxonomies();

		$wp_rewrite->flush_rules();
	}

	function tearDown() {
		parent::tearDown();

		delete_option('permalink_structure');

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('');
		$wp_rewrite->flush_rules();

		$_GET = array();
	}

	// URL's are relative to the site "front", ie. /category/uncategorized/ instead of http://site.../category..
	// Return url's are full url's with the prepended home.
	function get_canonical($test_url) {
		$can_url = redirect_canonical( get_option('home') . $test_url, false);
		if ( empty($can_url) )
			return get_option('home') . $test_url; // No redirect will take place for this request

		return $can_url;
	}

	/**
	 * @dataProvider data
	 */
	function test($test_url, $expected, $ticket = 0) {
		if ( $ticket )
			$this->knownWPBug($ticket);

		$ticket_ref = ($ticket > 0) ? 'Ticket #' . $ticket : null;

		if ( is_string($expected) )
			$expected = array('url' => $expected);
		elseif ( is_array($expected) && !isset($expected['url']) && !isset($expected['qv']) )
			$expected = array( 'qv' => $expected );

		if ( !isset($expected['url']) && !isset($expected['qv']) )
			$this->markTestSkipped('No valid expected output was provided');

		$this->http( get_option('home') . $test_url );

		// Does the redirect match what's expected?
		$can_url = $this->get_canonical( $test_url );
		$parsed_can_url = parse_url($can_url);

		// Just test the Path and Query if present
		if ( isset($expected['url']) )
			$this->assertEquals( $expected['url'], $parsed_can_url['path'] . (!empty($parsed_can_url['query']) ? '?' . $parsed_can_url['query'] : ''), $ticket_ref );

		if ( isset($expected['qv']) ) {

			// "make" that the request and check the query is correct
			$this->http( $can_url );

			// Are all query vars accounted for, And correct?
			global $wp;

			$query_vars = array_diff($wp->query_vars, $wp->extra_query_vars);
			if ( !empty($parsed_can_url['query']) ) {
				parse_str($parsed_can_url['query'], $_qv);

				// $_qv should not contain any elements which are set in $query_vars already (ie. $_GET vars should not be present in the Rewrite)
				$this->assertEquals( array(), array_intersect( $query_vars, $_qv ), 'Query vars are duplicated from the Rewrite into $_GET; ' . $ticket_ref );

				$query_vars = array_merge($query_vars, $_qv);
			}

			$this->assertEquals( $expected['qv'], $query_vars );
		} //isset $expected['qv']

	}

	function data() {
		/* Data format:
		 * [0]: $test_url,
		 * [1]: expected results: Any of the following can be used
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 * [2]: (optional) The ticket the test refers to, Can be skipped if unknown.
		 */

		// Please Note: A few test cases are commented out below, Look at the test case following it, in most cases it's simple showing 2 options for the "proper" redirect.
		return array(
			// Categories
			array( '?cat=32', '/category/parent/', 15256 ),
			array( '?cat=50', '/category/parent/child-1/', 15256 ),
			array( '?cat=51', '/category/parent/child-1/child-2/' ), // no children
			array( '/category/uncategorized/', array( 'url' => '/category/uncategorized/', 'qv' => array( 'category_name' => 'uncategorized' ) ) ),
			array( '/category/uncategorized/page/2/', array( 'url' => '/category/uncategorized/page/2/', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2) ) ),
			array( '/category/uncategorized/?paged=2', array( 'url' => '/category/uncategorized/page/2/', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2) ) ),
			array( '/category/uncategorized/?paged=2&category_name=uncategorized', array( 'url' => '/category/uncategorized/page/2/', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2) ), 17174 ),
			array( '/category/child-1/', '/category/parent/child-1/', 18734 ),
			array( '/category/foo/child-1/', '/category/parent/child-1/', 18734 ),

			// Categories & Intersections with other vars
			array( '/category/uncategorized/?tag=post-formats', array( 'url' => '/category/uncategorized/?tag=post-formats', 'qv' => array('category_name' => 'uncategorized', 'tag' => 'post-formats') ) ),
			array( '/?category_name=cat-a,cat-b', array( 'url' => '/?category_name=cat-a,cat-b', 'qv' => array( 'category_name' => 'cat-a,cat-b' ) ) ),

			// Taxonomies with extra Query Vars
			array( '/category/cat-a/page/1/?test=one%20two', '/category/cat-a/?test=one%20two', 18086), // Extra query vars should stay encoded

			// Categories with Dates
			array( '/category/uncategorized/?paged=2&year=2008', array( 'url' => '/category/uncategorized/page/2/?year=2008', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2, 'year' => 2008) ), 17661 ),
//			array( '/2008/04/?cat=1', array( 'url' => '/2008/04/?cat=1', 'qv' => array('cat' => '1', 'year' => '2008', 'monthnum' => '04' ) ), 17661 ),
			array( '/2008/04/?cat=1', array( 'url' => '/category/uncategorized/?year=2008&monthnum=04', 'qv' => array('category_name' => 'uncategorized', 'year' => '2008', 'monthnum' => '04' ) ), 17661 ),
//			array( '/2008/?category_name=cat-a', array( 'url' => '/2008/?category_name=cat-a', 'qv' => array('category_name' => 'cat-a', 'year' => '2008' ) ) ),
			array( '/2008/?category_name=cat-a', array( 'url' => '/category/cat-a/?year=2008', 'qv' => array('category_name' => 'cat-a', 'year' => '2008' ) ), 20386 ),
//			array( '/category/uncategorized/?year=2008', array( 'url' => '/2008/?category_name=uncategorized', 'qv' => array('category_name' => 'uncategorized', 'year' => '2008' ) ), 17661 ),
			array( '/category/uncategorized/?year=2008', array( 'url' => '/category/uncategorized/?year=2008', 'qv' => array('category_name' => 'uncategorized', 'year' => '2008' ) ), 17661 ),

			// Pages
			array( '/sample%20page/', array( 'url' => '/sample-page/', 'qv' => array('pagename' => 'sample-page', 'page' => '' ) ), 17653 ), // Page rules always set 'page'
			array( '/sample------page/', array( 'url' => '/sample-page/', 'qv' => array('pagename' => 'sample-page', 'page' => '' ) ), 14773 ),
			array( '/child-page-1/', '/parent-page/child-page-1/'),
			array( '/?page_id=144', '/parent-page/child-page-1/'),
			array( '/abo', '/about/' ),

			// Posts
			array( '?p=587', '/2008/06/02/post-format-test-audio/'),
			array( '/?name=images-test', '/2008/09/03/images-test/'),
			// Incomplete slug should resolve and remove the ?name= parameter
			array( '/?name=images-te', '/2008/09/03/images-test/', 20374),
			// Page slug should resolve to post slug and remove the ?pagename= parameter
			array( '/?pagename=images-test', '/2008/09/03/images-test/', 20374),

			array( '/2008/06/02/post-format-test-au/', '/2008/06/02/post-format-test-audio/'),
			array( '/2008/06/post-format-test-au/', '/2008/06/02/post-format-test-audio/'),
			array( '/2008/post-format-test-au/', '/2008/06/02/post-format-test-audio/'),
			array( '/2010/post-format-test-au/', '/2008/06/02/post-format-test-audio/'), // A Year the post is not in
			array( '/post-format-test-au/', '/2008/06/02/post-format-test-audio/'),

			array( '/2008/09/03/images-test/3/', array( 'url' => '/2008/09/03/images-test/3/', 'qv' => array( 'name' => 'images-test', 'year' => '2008', 'monthnum' => '09', 'day' => '03', 'page' => '/3' ) ) ), // page = /3 ?!
			array( '/2008/09/03/images-test/8/', '/2008/09/03/images-test/4/', 11694 ), // post with 4 pages
			array( '/2008/09/03/images-test/?page=3', '/2008/09/03/images-test/3/' ),
			array( '/2008/09/03/images-te?page=3', '/2008/09/03/images-test/3/' ),

			// Comments
			array( '/2008/03/03/comment-test/?cpage=2', '/2008/03/03/comment-test/comment-page-2/', 20388 ),
			array( '/2008/03/03/comment-test/comment-page-20/', '/2008/03/03/comment-test/comment-page-3/', 20388 ), // there's only 3 pages
			array( '/2008/03/03/comment-test/?cpage=30', '/2008/03/03/comment-test/comment-page-3/', 20388 ), // there's only 3 pages

			// Attachments
			array( '/?attachment_id=611', '/2008/06/10/post-format-test-gallery/canola2/' ),
			array( '/2008/06/10/post-format-test-gallery/?attachment_id=611', '/2008/06/10/post-format-test-gallery/canola2/' ),

			// Dates
			array( '/?m=2008', '/2008/' ),
			array( '/?m=200809', '/2008/09/'),
			array( '/?m=20080905', '/2008/09/05/'),

			array( '/2008/?day=05', '/2008/?day=05'), // no redirect
			array( '/2008/09/?day=05', '/2008/09/05/'),
			array( '/2008/?monthnum=9', '/2008/09/'),

			array( '/?year=2008', '/2008/'),

			// Authors
			array( '/?author=3', '/author/chip-bennett/' ),
//			array( '/?author=3&year=2008', '/2008/?author=3'),
			array( '/?author=3&year=2008', '/author/chip-bennett/?year=2008', 17661 ),
//			array( '/author/chip-bennett/?year=2008', '/2008/?author=3'), //Either or, see previous testcase.
			array( '/author/chip-bennett/?year=2008', '/author/chip-bennett/?year=2008', 17661 ),

			// Feeds
			array( '/?feed=atom', '/feed/atom/' ),
			array( '/?feed=rss2', '/feed/' ),
			array( '/?feed=comments-rss2', '/comments/feed/'),
			array( '/?feed=comments-atom', '/comments/feed/atom/'),

			// Feeds (per-post)
			array( '/2008/03/03/comment-test/?feed=comments-atom', '/2008/03/03/comment-test/feed/atom/'),
			array( '/?p=149&feed=comments-atom', '/2008/03/03/comment-test/feed/atom/'),
			array( '/2008/03/03/comment-test/?feed=comments-atom', '/2008/03/03/comment-test/feed/atom/' ),

			// Index
			array( '/?paged=1', '/' ),
			array( '/page/1/', '/' ),
			array( '/page1/', '/' ),
			array( '/?paged=2', '/page/2/' ),
			array( '/page2/', '/page/2/' ),

			// Misc
			array( '/2008%20', '/2008' ),
			array( '//2008////', '/2008/' ),

			// Todo: Endpoints (feeds, trackbacks, etc), More fuzzed mixed query variables, comment paging, Home page (Static)

		);
	}
}

class WP_Canonical_PageOnFront extends WP_Canonical {
	var $special_pages = array();

	function SetUp() {
		global $wp_rewrite;
		parent::SetUp();
		$this->special_pages['blog' ] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'blog-page'  ) );
		$this->special_pages['front'] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'front-page' ) );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $this->special_pages['blog'] );
		update_option( 'page_on_front', $this->special_pages['front'] );
		$wp_rewrite->flush_rules();
	}

	function tearDown() {
		parent::tearDown();
		update_option( 'show_on_front', 'posts' );
		
		delete_option( 'page_for_posts' );
		delete_option( 'page_on_front' );
		delete_option( 'permalink_structure' );

		foreach ( $this->special_pages as $p )
			wp_delete_post( $p );
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('');
		$wp_rewrite->flush_rules();

		$_GET = array();
	}

	function data() {
		/* Format:
		 * [0]: $test_url,
		 * [1]: expected results: Any of the following can be used
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 * [3]: (optional) The ticket the test refers to, Can be skipped if unknown.
		 */
		 return array(
			 // Check against an odd redirect: #20385
			 array( '/page/2/', '/page/2/' ),
			 // The page designated as the front page should redirect to the front of the site
			 array( '/front-page/', '/' ),
			 array( '/blog-page/?paged=2', '/blog-page/page/2/' ),
		 );
	}
}

class WP_Canonical_CustomRules extends WP_Canonical {
	function SetUp() {
		global $wp_rewrite;
		parent::SetUp();
		// Add a custom Rewrite rule to test category redirections.
		$wp_rewrite->add_rule('ccr/(.+?)/sort/(asc|desc)', 'index.php?category_name=$matches[1]&order=$matches[2]', 'top'); // ccr = Custom_Cat_Rule
		$wp_rewrite->flush_rules();
	}

	function data() {
		/* Format:
		 * [0]: $test_url,
		 * [1]: expected results: Any of the following can be used
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 * [3]: (optional) The ticket the test refers to, Can be skipped if unknown.
		 */
		return array(
			// Custom Rewrite rules leading to Categories
			array( '/ccr/uncategorized/sort/asc/', array( 'url' => '/ccr/uncategorized/sort/asc/', 'qv' => array( 'category_name' => 'uncategorized', 'order' => 'asc' ) ) ),
			array( '/ccr/uncategorized/sort/desc/', array( 'url' => '/ccr/uncategorized/sort/desc/', 'qv' => array( 'category_name' => 'uncategorized', 'order' => 'desc' ) ) ),
			array( '/ccr/uncategorized/sort/desc/?year=2008', array( 'url' => '/ccr/uncategorized/sort/desc/?year=2008', 'qv' => array( 'category_name' => 'uncategorized', 'order' => 'desc', 'year' => '2008' ) ), 17661 ),
		);
	}
}

class WP_Canonical_NoRewrite extends WP_Canonical {

	var $structure = '';

	// These test cases are run against the test handler in WP_Canonical

	function data() {
		/* Format:
		 * [0]: $test_url,
		 * [1]: expected results: Any of the following can be used
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 * [3]: (optional) The ticket the test refers to, Can be skipped if unknown.
		 */		
		return array(
			array( '/?p=123', '/?p=123' ),

			// This post_type arg should be stripped, because p=1 exists, and does not have post_type= in its query string
			array( '/?post_type=fake-cpt&p=1', '/?p=1' ),

			// Strip an existing but incorrect post_type arg
			array( '/?post_type=page&page_id=1', '/?p=1' ),

			array( '/?p=358 ', array('url' => '/?p=358',  'qv' => array('p' => '358') ) ), // Trailing spaces
			array( '/?p=358%20', array('url' => '/?p=358',  'qv' => array('p' => '358') ) ),

			array( '/?page_id=1', '/?p=1' ), // redirect page_id to p (should cover page_id|p|attachment_id to one another
			array( '/?page_id=1&post_type=revision', '/?p=1' ),

		);
	}
}
