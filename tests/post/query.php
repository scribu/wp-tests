<?php

/**
 * @group meta
 */
class Tests_Post_Query extends WP_UnitTestCase {
    function setUp() {
	parent::setUp();
    }

    function test_meta_key_or_query() {
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'foo', rand_str() );
	add_post_meta( $post_id, 'foo', rand_str() );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'bar', 'val2' );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'baz', rand_str() );
	$post_id4 = $this->factory->post->create();
	add_post_meta( $post_id4, 'froo', rand_str() );
	$post_id5 = $this->factory->post->create();
	add_post_meta( $post_id5, 'tango', 'val2' );
	$post_id6 = $this->factory->post->create();
	add_post_meta( $post_id6, 'bar', 'val1' );

	$query = new WP_Query( array(
	    'meta_query' => array(
		    array(
			    'key' => 'foo'
		    ),
		    array(
			    'key' => 'bar',
			    'value' => 'val2'
		    ),
		    array(
			    'key' => 'baz'
		    ),
		    array(
			    'key' => 'froo'
		    ),
		    'relation' => 'OR',
	    ),
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 4, count( $posts ) );

	$post_ids = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id2, $post_id3, $post_id4 ), $post_ids );
    }

    function test_meta_key_and_query() {
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'foo', rand_str() );
	add_post_meta( $post_id, 'foo', rand_str() );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'bar', 'val2' );
	add_post_meta( $post_id2, 'foo', rand_str() );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'baz', rand_str() );
	$post_id4 = $this->factory->post->create();
	add_post_meta( $post_id4, 'froo', rand_str() );
	$post_id5 = $this->factory->post->create();
	add_post_meta( $post_id5, 'tango', 'val2' );
	$post_id6 = $this->factory->post->create();
	add_post_meta( $post_id6, 'bar', 'val1' );
	add_post_meta( $post_id6, 'foo', rand_str() );
	$post_id7 = $this->factory->post->create();
	add_post_meta( $post_id7, 'foo', rand_str() );
	add_post_meta( $post_id7, 'froo', rand_str() );
	add_post_meta( $post_id7, 'baz', rand_str() );
	add_post_meta( $post_id7, 'bar', 'val2' );

	$query = new WP_Query( array(
	    'meta_query' => array(
		    array(
			    'key' => 'foo'
		    ),
		    array(
			    'key' => 'bar',
			    'value' => 'val2'
		    ),
		    array(
			    'key' => 'baz'
		    ),
		    array(
			    'key' => 'froo'
		    ),
		    'relation' => 'AND',
	    ),
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 1, count( $posts ) );

	$post_ids = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id7 ), $post_ids );

	$query = new WP_Query( array(
	    'meta_query' => array(
		    array(
			    'key' => 'foo'
		    ),
		    array(
			    'key' => 'bar',
		    ),
		    'relation' => 'AND',
	    ),
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 3, count( $posts ) );

	$post_ids = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id2, $post_id6, $post_id7 ), $post_ids );
    }

    /**
     * @ticket 18158
     */
    function test_meta_key_not_exists() {
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'foo', rand_str() );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'bar', rand_str() );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'bar', rand_str() );
	$post_id4 = $this->factory->post->create();
	add_post_meta( $post_id4, 'baz', rand_str() );
	$post_id5 = $this->factory->post->create();
	add_post_meta( $post_id5, 'foo', rand_str() );

	$query = new WP_Query( array(
	    'meta_query' => array(
		array(
		    'key' => 'foo',
		    'compare' => 'NOT EXISTS',
		),
	    ),
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 3, count( $posts ) );

	$query = new WP_Query( array(
	    'meta_query' => array(
		array(
		    'key' => 'foo',
		    'compare' => 'NOT EXISTS',
		),
	        array(
		    'key' => 'bar',
		    'compare' => 'NOT EXISTS',
		),
	    ),
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 1, count( $posts ) );

	$query = new WP_Query( array(
	    'meta_query' => array(
		array(
		    'key' => 'foo',
		    'compare' => 'NOT EXISTS',
		),
	        array(
		    'key' => 'bar',
		    'compare' => 'NOT EXISTS',
		),
	        array(
		    'key' => 'baz',
		    'compare' => 'NOT EXISTS',
		),
	    )
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 0, count( $posts ) );
    }

    /**
     * @ticket 20604
     */
    function test_taxonomy_empty_or() {
	// An empty tax query should return an empty array, not all posts.

	$this->factory->post->create_many( 10 );

	$query = new WP_Query( array(
	    'fields'	=> 'ids',
	    'tax_query' => array(
		'relation' => 'OR',
		array(
			'taxonomy' => 'post_tag',
			'field' => 'id',
			'terms' => false,
			'operator' => 'IN'
		),
		array(
			'taxonomy' => 'category',
			'field' => 'id',
			'terms' => false,
			'operator' => 'IN'
		)
	    )	
	) );

	$posts = $query->get_posts();
	$this->assertEquals( 0 , count( $posts ) );
    }

    function test_meta_between_not_between() {
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'time', 500 );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'time', 1001 );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'time', 0 );
	$post_id4 = $this->factory->post->create();
	add_post_meta( $post_id4, 'time', 1 );
	$post_id5 = $this->factory->post->create();
	add_post_meta( $post_id5, 'time', 1000 );
    
	$args = array(
		'meta_key' => 'time',
		'meta_value' => array( 1, 1000 ),
		'meta_type' => 'numeric',
		'meta_compare' => 'NOT BETWEEN'
	    );

	$query = new WP_Query( $args );
	$this->assertEquals( 2, count ( $query->posts ) );
	$posts = wp_list_pluck( $query->posts, 'ID' );
	$this->assertEquals( array( $post_id2, $post_id3 ), $posts );
	
	$args = array(
		'meta_key' => 'time',
		'meta_value' => array( 1, 1000 ),
		'meta_type' => 'numeric',
		'meta_compare' => 'BETWEEN'
	    );

	$query = new WP_Query( $args );
	$this->assertEquals( 3, count ( $query->posts ) );
	$posts = wp_list_pluck( $query->posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id4, $post_id5 ), $posts );
    }

    /**
     * @ticket 16829
     */
    function test_meta_default_compare() {
	// compare should default to IN when meta_value is an array
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'foo', 'bar' );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'bar', 'baz' );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'foo', 'baz' );
	$post_id4 = $this->factory->post->create();
	add_post_meta( $post_id4, 'baz', 'bar' );
	$post_id5 = $this->factory->post->create();
	add_post_meta( $post_id5, 'foo', rand_str() );

	$posts = get_posts( array(
	    'meta_key' => 'foo',
	    'meta_value' => array( 'bar', 'baz' )
	) );

	$this->assertEquals( 2, count( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id3 ), $posts );

	$posts = get_posts( array(
	    'meta_key' => 'foo',
	    'meta_value' => array( 'bar', 'baz' ),
	    'meta_compare' => 'IN'
	) );

	$this->assertEquals( 2, count( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id3 ), $posts );
    }

    /**
     * @ticket 17264
     */
    function test_duplicate_posts_when_no_key() {
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'city', 'Lorem' );
	add_post_meta( $post_id, 'address', '123 Lorem St.' );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'city', 'Lorem' );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'city', 'Loren' );

	$args = array(
	    'meta_query' => array(
		array(
		    'value' => 'lorem',
		    'compare' => 'LIKE'
		)
	    )
	);

	$posts = get_posts( $args );
	$this->assertEquals( 2, count( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id2 ), $posts );
    }

    /**
     * @ticket 15292
     */
    function test_empty_meta_value() {
	$post_id = $this->factory->post->create();
	add_post_meta( $post_id, 'foo', '0' );
	add_post_meta( $post_id, 'bar', 0 );
	$post_id2 = $this->factory->post->create();
	add_post_meta( $post_id2, 'foo', 1 );
	$post_id3 = $this->factory->post->create();
	add_post_meta( $post_id3, 'baz', 0 );
	$post_id4 = $this->factory->post->create();
	add_post_meta( $post_id4, 'baz', 0 );
	$post_id5 = $this->factory->post->create();
	add_post_meta( $post_id5, 'baz', 0 );
	add_post_meta( $post_id5, 'bar', '0' );
	$post_id6 = $this->factory->post->create();
	add_post_meta( $post_id6, 'baz', 0 );

	$posts = get_posts( array( 'meta_key' => 'foo', 'meta_value' => '0' ) );
	$this->assertEquals( 1, count ( $posts ) );
	$this->assertEquals( $post_id, $posts[0]->ID );

	$posts = get_posts( array( 'meta_key' => 'bar', 'meta_value' => '0' ) );
	$this->assertEquals( 2, count ( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id5 ), $posts );

    	$posts = get_posts( array( 'meta_key' => 'bar', 'meta_value' => 0 ) );
	$this->assertEquals( 2, count ( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id5 ), $posts );

    	$posts = get_posts( array( 'meta_value' => 0 ) );
	$this->assertEquals( 5, count ( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id3, $post_id4, $post_id5, $post_id6 ), $posts );

    	$posts = get_posts( array( 'meta_value' => '0' ) );
	$this->assertEquals( 5, count ( $posts ) );
	$posts = wp_list_pluck( $posts, 'ID' );
	$this->assertEquals( array( $post_id, $post_id3, $post_id4, $post_id5, $post_id6 ), $posts );
    }
}