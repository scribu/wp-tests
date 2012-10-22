<?php

/**
 * Test hook namespaces
 *
 * @group hooks
 */
class Tests_Actions_Namespaces extends WP_UnitTestCase {

	function test_namespaces() {
		$this->assertFalse( has_filter( 'test' ) );

		add_filter( 'test:p2p', '__return_true' );
		$this->assertTrue( has_filter( 'test' ) );

		remove_filter( 'test:p2p' );
		$this->assertFalse( has_filter( 'test' ) );
	}
}

