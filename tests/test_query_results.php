<?php

// Test various query vars and make sure the WP_Query class selects the correct posts.
// We're testing against a known data set, so we can check that specific posts are included in the output.

class TestWPQueryPosts extends _WPDataset1 {
	function setUp() {
		parent::setUp();
		$this->q = new WP_Query();
	}

	function tearDown() {
		parent::tearDown();
		unset($this->q);
	}

	function post_slugs($posts) {
		$out = array();
		foreach ($posts as $post)
			$out[] = $post->post_name;
		return $out;
	}

	function test_query_default() {
		$posts = $this->q->query('');

		// the output should be the most recent 10 posts as listed here
		$expected = array (
			0 => 'tags-a-and-c',
			1 => 'tags-b-and-c',
			2 => 'tags-a-and-b',
			3 => 'tag-c',
			4 => 'tag-b',
			5 => 'tag-a',
			6 => 'tags-a-b-c',
			7 => 'raw-html-code',
			8 => 'simple-markup-test',
			9 => 'embedded-video',
		);

		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

	function test_query_tag_a() {
		$posts = $this->q->query('tag=tag-a');

		// there are 4 posts with Tag A
		$this->assertEquals( 4, count($posts) );
		$this->assertEquals( 'tags-a-and-c', $posts[0]->post_name );
		$this->assertEquals( 'tags-a-and-b', $posts[1]->post_name );
		$this->assertEquals( 'tag-a', $posts[2]->post_name );
		$this->assertEquals( 'tags-a-b-c', $posts[3]->post_name );
	}

	function test_query_tag_b() {
		$posts = $this->q->query('tag=tag-b');

		// there are 4 posts with Tag A
		$this->assertEquals( 4, count($posts) );
		$this->assertEquals( 'tags-b-and-c', $posts[0]->post_name );
		$this->assertEquals( 'tags-a-and-b', $posts[1]->post_name );
		$this->assertEquals( 'tag-b', $posts[2]->post_name );
		$this->assertEquals( 'tags-a-b-c', $posts[3]->post_name );
	}

	function test_query_tag_id() {
		$tag = tag_exists('tag-a');
		$posts = $this->q->query("tag_id={$tag[term_id]}");

		// there are 4 posts with Tag A
		$this->assertEquals( 4, count($posts) );
		$this->assertEquals( 'tags-a-and-c', $posts[0]->post_name );
		$this->assertEquals( 'tags-a-and-b', $posts[1]->post_name );
		$this->assertEquals( 'tag-a', $posts[2]->post_name );
		$this->assertEquals( 'tags-a-b-c', $posts[3]->post_name );
	}

	function test_query_tag_slug__in() {
		$posts = $this->q->query("tag_slug__in[]=tag-b&tag_slug__in[]=tag-c");

		// there are 4 posts with either Tag B or Tag C
		$this->assertEquals( 6, count($posts) );
		$this->assertEquals( 'tags-a-and-c', $posts[0]->post_name );
		$this->assertEquals( 'tags-b-and-c', $posts[1]->post_name );
		$this->assertEquals( 'tags-a-and-b', $posts[2]->post_name );
		$this->assertEquals( 'tag-c', $posts[3]->post_name );
		$this->assertEquals( 'tag-b', $posts[4]->post_name );
		$this->assertEquals( 'tags-a-b-c', $posts[5]->post_name );
	}


	function test_query_tag__in() {
		$tag_a = tag_exists('tag-a');
		$tag_b = tag_exists('tag-b');
		$posts = $this->q->query("tag__in[]={$tag_a[term_id]}&tag__in[]={$tag_b[term_id]}");

		// there are 6 posts with either Tag A or Tag B
		$this->assertEquals( 6, count($posts) );
		$this->assertEquals( 'tags-a-and-c', $posts[0]->post_name );
		$this->assertEquals( 'tags-b-and-c', $posts[1]->post_name );
		$this->assertEquals( 'tags-a-and-b', $posts[2]->post_name );
		$this->assertEquals( 'tag-b', $posts[3]->post_name );
		$this->assertEquals( 'tag-a', $posts[4]->post_name );
		$this->assertEquals( 'tags-a-b-c', $posts[5]->post_name );
	}

	function test_query_tag__not_in() {
		$tag_a = tag_exists('tag-a');
		$posts = $this->q->query("tag__not_in[]={$tag_a[term_id]}");

		// the most recent 10 posts with Tag A excluded
		// (note the different between this and test_query_default)
		$expected = array (
			0 => 'tags-b-and-c',
			1 => 'tag-c',
			2 => 'tag-b',
			3 => 'raw-html-code',
			4 => 'simple-markup-test',
			5 => 'embedded-video',
			6 => 'contributor-post-approved',
			7 => 'one-comment',
			8 => 'no-comments',
			9 => 'many-trackbacks',
		);

		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

	function test_query_tag__in_but__not_in() {
		$tag_a = tag_exists('tag-a');
		$tag_b = tag_exists('tag-b');
		$posts = $this->q->query("tag__in[]={$tag_a[term_id]}&tag__not_in[]={$tag_b[term_id]}");

		// there are 4 posts with Tag A, only 2 when we exclude Tag B
		$this->assertEquals( 2, count($posts) );
		$this->assertEquals( 'tags-a-and-c', $posts[0]->post_name );
		$this->assertEquals( 'tag-a', $posts[1]->post_name );
	}



	function test_query_category_name() {
		$posts = $this->q->query('category_name=cat-a');

		// there are 4 posts with Cat A, we'll check for them by name
		$this->assertEquals( 4, count($posts) );
		$this->assertEquals( 'cat-a', $posts[0]->post_name );
		$this->assertEquals( 'cats-a-and-c', $posts[1]->post_name );
		$this->assertEquals( 'cats-a-and-b', $posts[2]->post_name );
		$this->assertEquals( 'cats-a-b-c', $posts[3]->post_name );
	}

	function test_query_cat() {
		$cat = category_exists('cat-b');
		$posts = $this->q->query("cat=$cat");

		// there are 4 posts with Cat B
		$this->assertEquals( 4, count($posts) );
		$this->assertEquals( 'cat-b', $posts[0]->post_name );
		$this->assertEquals( 'cats-b-and-c', $posts[1]->post_name );
		$this->assertEquals( 'cats-a-and-b', $posts[2]->post_name );
		$this->assertEquals( 'cats-a-b-c', $posts[3]->post_name );
	}

	function test_query_posts_per_page() {
		$posts = $this->q->query('posts_per_page=5');

		$expected = array (
			0 => 'tags-a-and-c',
			1 => 'tags-b-and-c',
			2 => 'tags-a-and-b',
			3 => 'tag-c',
			4 => 'tag-b',
		);

		$this->assertEquals( 5, count($posts) );
		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

	function test_query_offset() {
		$posts = $this->q->query('offset=2');

		$expected = array (
			0 => 'tags-a-and-b',
			1 => 'tag-c',
			2 => 'tag-b',
			3 => 'tag-a',
			4 => 'tags-a-b-c',
			5 => 'raw-html-code',
			6 => 'simple-markup-test',
			7 => 'embedded-video',
			8 => 'contributor-post-approved',
			9 => 'one-comment',
		);

		$this->assertEquals( 10, count($posts) );
		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

	function test_query_paged() {
		$posts = $this->q->query('paged=2');

		$expected = array (
			0 => 'contributor-post-approved',
			1 => 'one-comment',
			2 => 'no-comments',
			3 => 'many-trackbacks',
			4 => 'one-trackback',
			5 => 'comment-test',
			6 => 'lorem-ipsum',
			7 => 'cat-c',
			8 => 'cat-b',
			9 => 'cat-a',
		);

		$this->assertEquals( 10, count($posts) );
		$this->assertTrue( $this->q->is_paged() );
		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

	function test_query_paged_and_posts_per_page() {
		$posts = $this->q->query('paged=4&posts_per_page=4');

		$expected = array (
			0 => 'no-comments',
			1 => 'many-trackbacks',
			2 => 'one-trackback',
			3 => 'comment-test',
		);

		$this->assertEquals( 4, count($posts) );
		$this->assertTrue( $this->q->is_paged() );
		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

	function test_query_offset_and_paged() {
		$this->knownWPBug(18897);

		$posts = $this->q->query('paged=2&offset=3');

		$expected = array (
			0 => 'many-trackbacks',
			1 => 'one-trackback',
			2 => 'comment-test',
			3 => 'lorem-ipsum',
			4 => 'cat-c',
			5 => 'cat-b',
			6 => 'cat-a',
			7 => 'cats-a-and-c',
			8 => 'cats-b-and-c',
			9 => 'cats-a-and-b',
		);

		$this->assertEquals( 10, count($posts) );
		$this->assertTrue( $this->q->is_paged() );
		$this->assertEquals( $expected, $this->post_slugs($posts) );
	}

}