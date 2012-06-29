<?php

include_once(ABSPATH . WPINC . '/post-thumbnail-template.php'); 

class TestXMLRPCServer_mt_getRecentPostTitles extends WPXMLRPCServerTestCase {
	var $post_data;
	var $post_id;
	var $post_date_ts;

	function setUp() {
		parent::setUp();

		$this->post_date_ts = strtotime( '+1 day' );
		$this->post_data = array(
			'post_title' => rand_str(),
			'post_content' => rand_str( 2000 ),
			'post_excerpt' => rand_str( 100 ),
			'post_author' => get_user_by( 'login', 'author' )->ID,
			'post_date'  => strftime( "%Y-%m-%d %H:%M:%S", $this->post_date_ts ),
		);
		$this->post_id = wp_insert_post( $this->post_data );
	}

	function tearDown() {
		parent::tearDown();

		wp_delete_post( $this->post_id );
	}


	function test_invalid_username_password() {
		$result = $this->myxmlrpcserver->mt_getRecentPostTitles( array( 1, 'username', 'password' ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_no_posts() {
		$this->_delete_all_posts();

		$result = $this->myxmlrpcserver->mt_getRecentPostTitles( array( 1, 'author', 'author' ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 500, $result->code );
	}

	function test_no_editable_posts() {
		wp_delete_post( $this->post_id );

		$post_data_editor = array(
			'post_title' => rand_str(),
			'post_content' => rand_str( 2000 ),
			'post_excerpt' => rand_str( 100 ),
			'post_author' => get_user_by( 'login', 'editor' )->ID,
		);
		$post_id = wp_insert_post( $post_data_editor );

		$result = $this->myxmlrpcserver->mt_getRecentPostTitles( array( 1, 'author', 'author' ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 0, count( $result ) );

		wp_delete_post( $post_id );
	}

	function test_date() {
		$results = $this->myxmlrpcserver->mt_getRecentPostTitles( array( 1, 'author', 'author' ) );
		$this->assertNotInstanceOf( 'IXR_Error', $results );

		foreach( $results as $result ) {
			$post = get_post( $result['postid'] );
			$date_gmt = strtotime( get_gmt_from_date( mysql2date( 'Y-m-d H:i:s', $post->post_date, false ), 'Ymd\TH:i:s' ) );

			$this->assertInstanceOf( 'IXR_Date', $result['dateCreated'] );
			$this->assertInstanceOf( 'IXR_Date', $result['date_created_gmt'] );

			$this->assertEquals( strtotime( $post->post_date ), $result['dateCreated']->getTimestamp() );
			$this->assertEquals( $date_gmt, $result['date_created_gmt']->getTimestamp() );
		}
	}
}
