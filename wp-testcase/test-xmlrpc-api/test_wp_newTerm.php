<?php

class TestXMLRPCServer_wp_newTerm extends WPXMLRPCServerTestCase {
	var $term_ids = array();
	var $parent_term;

	function setUp() {
		parent::setUp();
		$this->term_ids = array();
		$this->parent_term = wp_insert_term( 'parent' . rand_str(), 'category' );
		$this->parent_term = $this->parent_term['term_id'];
	}

	function tearDown() {
		parent::tearDown();

		wp_delete_term( $this->parent_term, 'category' );
		foreach ( $this->term_ids as $term_id )
			wp_delete_term( $term_id, 'category' );
	}

	function test_invalid_username_password() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'username', 'password', array() ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_empty_taxonomy() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'subscriber', 'subscriber', array( 'taxonomy' => '' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
		$this->assertEquals( __( 'Invalid taxonomy' ), $result->message );
	}

	function test_invalid_taxonomy() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'subscriber', 'subscriber', array( 'taxonomy' => 'not_existing' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
		$this->assertEquals( __( 'Invalid taxonomy' ), $result->message );
	}

	function test_incapable_user() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'subscriber', 'subscriber', array( 'taxonomy' => 'category' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 401, $result->code );
		$this->assertEquals( __( 'You are not allowed to create terms in this taxonomy.' ), $result->message );
	}

	function test_empty_term() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', array( 'taxonomy' => 'category', 'name' => '' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
		$this->assertEquals( __( 'The term name cannot be empty.' ), $result->message );
	}

	function test_parent_for_nonhierarchical() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', array( 'taxonomy' => 'post_tag', 'parent' => $this->parent_term, 'name' => 'test' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
		$this->assertEquals( __( 'This taxonomy is not hierarchical.' ), $result->message );
	}

	function test_parent_invalid() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', array( 'taxonomy' => 'category', 'parent' => 'dasda', 'name' => 'test' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 500, $result->code );
	}

	function test_parent_not_existing() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', array( 'taxonomy' => 'category', 'parent' => 9999, 'name' => 'test' ) ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
		$this->assertEquals( __( 'Parent term does not exist.' ), $result->message );
	}


	function test_add_term() {
		$result = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', array( 'taxonomy' => 'category', 'name' => 'test' ) ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$this->term_ids[] = $result;
	}

	function test_add_term_with_parent() {
		$result  = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', array( 'taxonomy' => 'category', 'parent' => $this->parent_term, 'name' => 'test' ) ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$this->term_ids[] = $result;
	}

	function test_add_term_with_all() {
		$taxonomy = array( 'taxonomy' => 'category', 'parent' => $this->parent_term, 'name' => 'test_all', 'description' => 'Test all', 'slug' => 'test_all' );
		$result  = $this->myxmlrpcserver->wp_newTerm( array( 1, 'editor', 'editor', $taxonomy ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$this->term_ids[] = $result;
	}
}