<?php

class TestXMLRPCServer_wp_deletePost extends WPXMLRPCServerTestCase {

	function test_invalid_username_password() {
		$result = $this->myxmlrpcserver->wp_deletePost( array( 1, 'username', 'password', 0 ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_invalid_post() {
		$result = $this->myxmlrpcserver->wp_deletePost( array( 1, 'editor', 'editor', 0 ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 404, $result->code );
	}

	function test_incapable_user() {
		$this->_insert_quick_posts( 1 );
		$post_id = array_pop( $this->post_ids );

		$result = $this->myxmlrpcserver->wp_deletePost( array( 1, 'subscriber', 'subscriber', $post_id ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 401, $result->code );

		wp_delete_post( $post_id, true );
	}

	function test_post_deleted() {
		$this->_insert_quick_posts( 1 );
		$post_id = array_pop( $this->post_ids );

		$result = $this->myxmlrpcserver->wp_deletePost( array( 1, 'editor', 'editor', $post_id ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertTrue( $result );

		$post = get_post( $post_id );
		$this->assertEquals( 'trash', $post->post_status );

		wp_delete_post( $post_id, true );
	}
}