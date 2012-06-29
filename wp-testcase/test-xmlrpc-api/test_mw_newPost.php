<?php

class TestXMLRPCServer_mw_newPost extends WPXMLRPCServerTestCase {

	function test_invalid_username_password() {
		$post = array();
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'username', 'password', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_incapable_user() {
		$post = array();
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'subscriber', 'subscriber', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_no_content() {
		$post = array();
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 500, $result->code );
		$this->assertEquals( 'Content, title, and excerpt are empty.', $result->message );
	}

	function test_basic_content() {
		$post = array( 'title' => 'Test' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertStringMatchesFormat( '%d', $result );
	}

	function test_ignore_id() {
		$post = array( 'title' => 'Test', 'ID' => 103948 );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertNotEquals( '103948', $result );
	}

	function test_capable_publish() {
		$post = array( 'title' => 'Test', 'post_status' => 'publish' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
	}

	function test_incapable_publish() {
		$post = array( 'title' => 'Test', 'post_status' => 'publish' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'contributor', 'contributor', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_capable_other_author() {
		$other_author_id = get_user_by( 'login', 'author' )->ID;
		$post = array( 'title' => 'Test', 'wp_author_id' => $other_author_id );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
	}

	function test_incapable_other_author() {
		$other_author_id = get_user_by( 'login', 'author' )->ID;
		$post = array( 'title' => 'Test', 'wp_author_id' => $other_author_id );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'contributor', 'contributor', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_invalid_author() {
		$this->knownWPBug( 20356 );
		$post = array( 'title' => 'Test', 'wp_author_id' => 99999999 );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 404, $result->code );
	}

	function test_empty_author() {
		$my_author_id = get_user_by( 'login', 'author' )->ID;
		$post = array( 'title' => 'Test' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$out = wp_get_single_post( $result );
		$this->assertEquals( $my_author_id, $out->post_author );
		$this->assertEquals( 'Test', $out->post_title );
	}

	function test_post_thumbnail() {
		add_theme_support( 'post-thumbnails' );

		// create attachment
		$filename = ( DIR_TESTDATA.'/images/a2-small.jpg' );
		$contents = file_get_contents( $filename );
		$upload = wp_upload_bits( $filename, null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );

		$attachment = array(
			'post_title' => 'Post Thumbnail',
			'post_type' => 'attachment',
			'post_mime_type' => 'image/jpeg',
			'guid' => $upload['url']
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload['file'] );

		$post = array( 'title' => 'Post Thumbnail Test', 'wp_post_thumbnail' => $attachment_id );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( $attachment_id, get_post_meta( $result, '_thumbnail_id', true ) );

		remove_theme_support( 'post-thumbnails' );
	}

	function test_incapable_set_post_type_as_page() {
		$post = array( 'title' => 'Test', 'post_type' => 'page' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_capable_set_post_type_as_page() {
		$post = array( 'title' => 'Test', 'post_type' => 'page' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$out = wp_get_single_post( $result );
		$this->assertEquals( 'Test', $out->post_title );
		$this->assertEquals( 'page', $out->post_type );
	}

}
