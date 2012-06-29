<?php

class TestXMLRPCServer_wp_getTaxonomies extends WPXMLRPCServerTestCase {

	function test_invalid_username_password() {
		$result = $this->myxmlrpcserver->wp_getTaxonomies( array( 1, 'username', 'password' ) );
		$this->assertInstanceOf( 'IXR_Error', $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_taxonomy_validated() {
		$result = $this->myxmlrpcserver->wp_getTaxonomies( array( 1, 'editor', 'editor' ) );
		$this->assertNotInstanceOf( 'IXR_Error', $result );
	}
}