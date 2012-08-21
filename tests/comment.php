<?php

// Test the output of Comment Querying functions

/**
 * @group comment
 */
class Test_Comment_Query extends WP_UnitTestCase {
	var $post_id;
	var $comment_id;

	function setUp() {
		parent::setUp();

		$this->post_id = $this->factory->post->create();
		$this->comment_id = $this->factory->comment->create();
	}

	function test_get_comment_comment_approved_0() {
		$this->knownWPBug( 21101 );
		$comments_approved_0 = get_comments( array( 'comment_approved' => '0' ) );
		$this->assertEquals( 0, count( $comments_approved_0 ) );
	}

	function test_get_comment_comment_approved_1() {
		$this->knownWPBug( 21101 );
		$comments_approved_1 = get_comments( array( 'comment_approved' => '1' ) );

		$this->assertEquals( 1, count( $comments_approved_1 ) );
		$result = $comments_approved_1[0];

		$this->assertEquals( $this->comment_id, $result->comment_ID );
	}
}
