<?php

/**
 * Get WPAjaxTestCase class
 */
require_once( DIR_TESTCASE . '/test_admin_includes_ajax_actions.php' );

/**
 * Admin ajax functions to be tested
 */
include_once( ABSPATH . 'wp-admin/includes/ajax-actions.php' );

/**
 * Testing ajax comment functionality
 *
 * @package    WordPress
 * @subpackage Unit Tests
 * @since      3.4.0
 * @group      Ajax
 */
class TestAjaxCommentsEdit extends WPAjaxTestCase {

	/**
	 * A post with at least one comment
	 * @var mixed
	 */
	protected $_comment_post = null;

	/**
	 * Set up the test fixture
	 */
	public function setUp() {
		global $wpdb;
		parent::setUp();
		$posts = $wpdb->get_results( $wpdb->prepare("
		SELECT
			COUNT(c.comment_ID) AS comments,
			p.ID AS post_ID
		FROM
			$wpdb->posts p
			LEFT JOIN $wpdb->comments c ON c.comment_post_ID = p.ID
		WHERE
			p.post_status = 'publish'
			AND p.post_type = 'post'
		GROUP BY
			p.ID
		") );
		foreach ( (array) $posts as $tmp ) {
			if ( null === $this->_comment_post && $tmp->comments > 0 ) {
				$this->_comment_post = get_post( $tmp->post_ID );
				break;
			}
		}
	}

	/**
	 * Get comments as a privilged user (administrator)
	 * Expects test to pass
	 * @return void
	 */
	public function test_as_admin() {

		// Become an administrator
		$this->_setRole( 'administrator' );

		// Get a comment
		$comments = get_comments( array(
			'post_id' => $this->_comment_post->ID
		) );
		$comment = array_pop( $comments );

		// Set up a default request
		$_POST['_ajax_nonce-replyto-comment'] = wp_create_nonce( 'replyto-comment' );
		$_POST['comment_ID']                  = $comment->comment_ID;
		$_POST['content']                     = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

		// Make the request
		try {
			$this->_handleAjax( 'edit-comment' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// Get the response
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// Check the meta data
		$this->assertEquals( -1, (string) $xml->response[0]->edit_comment['position'] );
		$this->assertEquals( $comment->comment_ID, (string) $xml->response[0]->edit_comment['id'] );
		$this->assertEquals( 'edit-comment_' . $comment->comment_ID, (string) $xml->response['action'] );
		
		// Check the payload
		$this->assertNotEmpty( (string) $xml->response[0]->edit_comment[0]->response_data );
		
		// And supplemental is empty
		$this->assertEmpty( (string) $xml->response[0]->edit_comment[0]->supplemental );
	}

	/**
	 * Get comments as a non-privileged user (subscriber)
	 * Expects test to fail
	 * @return void
	 */
	public function test_as_subscriber() {

		// Become an administrator
		$this->_setRole( 'subscriber' );

		// Get a comment
		$comments = get_comments( array(
			'post_id' => $this->_comment_post->ID
		) );
		$comment = array_pop( $comments );

		// Set up a default request
		$_POST['_ajax_nonce-replyto-comment'] = wp_create_nonce( 'replyto-comment' );
		$_POST['comment_ID']                  = $comment->comment_ID;
		$_POST['content']                     = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

		// Make the request
		$this->setExpectedException( 'WPAjaxDieStopException', '-1' );
		$this->_handleAjax( 'edit-comment' );
	}

	/**
	 * Get comments with a bad nonce
	 * Expects test to fail
	 * @return void
	 */
	public function test_bad_nonce() {

		// Become an administrator
		$this->_setRole( 'administrator' );

		// Get a comment
		$comments = get_comments( array(
			'post_id' => $this->_comment_post->ID
		) );
		$comment = array_pop( $comments );
		
		// Set up a default request
		$_POST['_ajax_nonce-replyto-comment'] = wp_create_nonce( uniqid() );
		$_POST['comment_ID']                  = $comment->comment_ID;
		$_POST['content']                     = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

		// Make the request
		$this->setExpectedException( 'WPAjaxDieStopException', '-1' );
		$this->_handleAjax( 'get-comments' );
	}

	/**
	 * Get comments for an invalid post
	 * This should return valid XML
	 * @return void
	 */
	public function test_invalid_comment() {

		// Become an administrator
		$this->_setRole( 'administrator' );

		// Set up a default request
		$_POST['_ajax_nonce-replyto-comment'] = wp_create_nonce( 'replyto-comment' );
		$_POST['comment_ID']                  = 123456789;
		$_POST['content']                     = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

		// Make the request
		$this->setExpectedException( 'WPAjaxDieStopException', '-1' );
		$this->_handleAjax( 'edit-comment' );
	}
}
