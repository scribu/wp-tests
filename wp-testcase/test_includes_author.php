<?php

// test functions in wp-includes/author.php, author-template.php

class TestWPAuthor extends _WPEmptyBlog {

	var $user_ids = array();
	protected $_deprecated_errors = array();
	protected $old_post_id = 0;
	protected $author_id = 0;
	protected $post_id = 0;

	function setUp() {
		parent::setUp();
		// keep track of users we create
		$this->user_ids = array();
		$this->_deprecated_errors = array();

		$this->author_id = $this->_make_user( 'author', 'test_author' );	
		$user = new WP_User( $this->author_id );

		$post = array(
			'post_author' => $this->author_id,
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_type' => 'post'
		);

		// insert a post and make sure the ID is ok
		$this->post_id = wp_insert_post( $post );

		setup_postdata( get_post( $this->post_id ) );
	}

	function tearDown() {
		parent::tearDown();

		// delete any users that were created during tests
		$this->_destroy_users();

		wp_reset_postdata();
	}

	public function deprecated_handler( $function, $message, $version ) {
		$this->_deprecated_errors[] = array(
			'function' => $function,
			'message'  => $message,
			'version'  => $version
		);
	}

	function test_get_the_author() {
		$author_name = get_the_author();
		$user = new WP_User( $this->author_id );

		$this->assertEquals( $user->display_name, $author_name );
		$this->assertEquals( 'test_author', $author_name );
	}

	function test_get_the_author_meta() {
		$this->assertEquals( 'test_author', get_the_author_meta( 'login' ) );
		$this->assertEquals( 'test_author', get_the_author_meta( 'user_login' ) );
		$this->assertEquals( 'test_author', get_the_author_meta( 'display_name' ) );

		$this->assertEquals( 'test_author', get_the_author_meta( 'description' ) );
		$this->assertEquals( 'test_author', get_the_author_meta( 'user_description' ) );
		add_user_meta( $this->author_id, 'user_description', 'user description' );
		$this->assertEquals( 'user description', get_user_meta( $this->author_id, 'user_description', true ) );
		// user_description in meta is ignored. The content of description is returned instead.
		// See #20285
		$this->assertEquals( 'test_author', get_the_author_meta( 'user_description' ) );
		$this->assertEquals( 'test_author', get_the_author_meta( 'description' ) );
		update_user_meta( $this->author_id, 'user_description', '' );
		$this->assertEquals( '', get_user_meta( $this->author_id, 'user_description', true ) );
		$this->assertEquals( 'test_author', get_the_author_meta( 'user_description' ) );
		$this->assertEquals( 'test_author', get_the_author_meta( 'description' ) );

		$this->assertEquals( '', get_the_author_meta( 'does_not_exist' ) );
	}

	function test_get_the_author_meta_no_authordata() {
		unset( $GLOBALS['authordata'] );
		$this->assertEquals( '', get_the_author_meta( 'id' ) );
		$this->assertEquals( '', get_the_author_meta( 'user_login' ) );
		$this->assertEquals( '', get_the_author_meta( 'does_not_exist' ) );
	}
}