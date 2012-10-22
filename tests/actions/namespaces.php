<?php

/**
 * Test hook namespaces
 *
 * @group hooks
 */
class Tests_Actions_Namespaces extends WP_UnitTestCase {

	function test_remove() {
		$this->assertFalse( has_filter( 'test' ) );

		add_filter( 'test:p2p', '__return_true' );
		$this->assertTrue( has_filter( 'test' ) );

		remove_filter( 'test:p2p' );
		$this->assertFalse( has_filter( 'test' ) );
	}

	function test_remove_wildcard() {
		$this->assertFalse( has_filter( 'foo' ) );
		$this->assertFalse( has_filter( 'bar' ) );

		add_filter( 'foo:p2p', '__return_true' );
		$this->assertTrue( has_filter( 'foo' ) );

		add_filter( 'bar:p2p', '__return_true' );
		$this->assertTrue( has_filter( 'bar' ) );

		remove_filter( '*:p2p' );

		$this->assertFalse( has_filter( 'foo' ) );
		$this->assertFalse( has_filter( 'bar' ) );
	}
}

