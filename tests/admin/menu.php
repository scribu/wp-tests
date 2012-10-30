<?php

/**
 * @group admin
 */
class Tests_Admin_Menu extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		require(ABSPATH . 'wp-admin/includes/class-wp-admin-menu.php');

		global $admin_menu;
		$admin_menu = new WP_Admin_Menu;

		$admin_menu->append( array(
			'title' => __( 'Dashboard' ),
			'cap' => 'read',
			'class' => 'menu-top menu-top-first menu-icon-dashboard',
			'id' => 'dashboard',
			'url' => 'index.php',
			'_index' => 2
		) );

		$admin_menu->add_first_submenu( 'dashboard', __( 'Home' ), 0 );
	}

	/**
	 * @ticket 12718
	 */
	function test_removing() {
		// TODO: check for numeric array
		$this->assertTrue( remove_submenu_page( 'index.php', 'index.php' ) );
		$this->assertFalse( remove_submenu_page( 'index.php', 'index.php' ) );

		// TODO: check for numeric array
		$this->assertTrue( remove_menu_page( 'index.php' ) );
		$this->assertFalse( remove_menu_page( 'index.php' ) );
	}
}

