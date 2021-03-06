<?php

/**
 * @group post
 * @group media
 * @group upload
 */
class Tests_Post_Attachments extends WP_UnitTestCase {

	function tearDown() {
		// Remove all uploads.
		$uploads = wp_upload_dir();
		foreach ( scandir( $uploads['basedir'] ) as $file )
			_rmdir( $uploads['basedir'] . '/' . $file );

		parent::tearDown();
	}

	function _make_attachment($upload, $parent_post_id=-1) {

		$type = '';
		if ( !empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent_post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $this->ids[] = $id;

	}

	function test_insert_bogus_image() {
		$filename = rand_str().'.jpg';
		$contents = rand_str();

		$upload = wp_upload_bits($filename, null, $contents);
		$this->assertTrue( empty($upload['error']) );

		$id = $this->_make_attachment($upload);
	}

	function test_insert_image_no_thumb() {

		// this image is smaller than the thumbnail size so it won't have one
		$filename = ( DIR_TESTDATA.'/images/test-image.jpg' );
		$contents = file_get_contents($filename);

		$upload = wp_upload_bits(basename($filename), null, $contents);
		$this->assertTrue( empty($upload['error']) );

		$id = $this->_make_attachment($upload);

		// intermediate copies should not exist
		$this->assertFalse( image_get_intermediate_size($id, 'thumbnail') );
		$this->assertFalse( image_get_intermediate_size($id, 'medium') );

		// medium and full size will both point to the original
		$downsize = image_downsize($id, 'medium');
		$this->assertEquals( 'test-image.jpg', basename($downsize[0]) );
		$this->assertEquals( 50, $downsize[1] );
		$this->assertEquals( 50, $downsize[2] );

		$downsize = image_downsize($id, 'full');
		$this->assertEquals( 'test-image.jpg', basename($downsize[0]) );
		$this->assertEquals( 50, $downsize[1] );
		$this->assertEquals( 50, $downsize[2] );

	}

	function test_insert_image_thumb_only() {
		update_option( 'medium_size_w', 0 );
		update_option( 'medium_size_h', 0 );

		$filename = ( DIR_TESTDATA.'/images/a2-small.jpg' );
		$contents = file_get_contents($filename);

		$upload = wp_upload_bits(basename($filename), null, $contents);
		$this->assertTrue( empty($upload['error']) );

		$id = $this->_make_attachment($upload);

		// intermediate copies should exist: thumbnail only
		$thumb = image_get_intermediate_size($id, 'thumbnail');
		$this->assertEquals( 'a2-small-150x150.jpg', $thumb['file'] );

		$uploads = wp_upload_dir();
		$this->assertTrue( is_file($uploads['basedir'] . DIRECTORY_SEPARATOR . $thumb['path']) );

		$this->assertFalse( image_get_intermediate_size($id, 'medium') );

		// the thumb url should point to the thumbnail intermediate
		$this->assertEquals( $thumb['url'], wp_get_attachment_thumb_url($id) );

		// image_downsize() should return the correct images and sizes
		$downsize = image_downsize($id, 'thumbnail');
		$this->assertEquals( 'a2-small-150x150.jpg', basename($downsize[0]) );
		$this->assertEquals( 150, $downsize[1] );
		$this->assertEquals( 150, $downsize[2] );

		// medium and full will both point to the original
		$downsize = image_downsize($id, 'medium');
		$this->assertEquals( 'a2-small.jpg', basename($downsize[0]) );
		$this->assertEquals( 400, $downsize[1] );
		$this->assertEquals( 300, $downsize[2] );

		$downsize = image_downsize($id, 'full');
		$this->assertEquals( 'a2-small.jpg', basename($downsize[0]) );
		$this->assertEquals( 400, $downsize[1] );
		$this->assertEquals( 300, $downsize[2] );

	}

	function test_insert_image_medium() {
		update_option('medium_size_w', 400);
		update_option('medium_size_h', 0);

		$filename = ( DIR_TESTDATA.'/images/2007-06-17DSC_4173.JPG' );
		$contents = file_get_contents($filename);

		$upload = wp_upload_bits(basename($filename), null, $contents);
		$this->assertTrue( empty($upload['error']) );

		$id = $this->_make_attachment($upload);
		$uploads = wp_upload_dir();

		// intermediate copies should exist: thumbnail and medium
		$thumb = image_get_intermediate_size($id, 'thumbnail');
		$this->assertEquals( '2007-06-17DSC_4173-150x150.jpg', $thumb['file'] );
		$this->assertTrue( is_file($uploads['basedir'] . DIRECTORY_SEPARATOR . $thumb['path']) );

		$medium = image_get_intermediate_size($id, 'medium');
		$this->assertEquals( '2007-06-17DSC_4173-400x602.jpg', $medium['file'] );
		$this->assertTrue( is_file($uploads['basedir'] . DIRECTORY_SEPARATOR . $medium['path']) );

		// the thumb url should point to the thumbnail intermediate
		$this->assertEquals( $thumb['url'], wp_get_attachment_thumb_url($id) );

		// image_downsize() should return the correct images and sizes
		$downsize = image_downsize($id, 'thumbnail');
		$this->assertEquals( '2007-06-17DSC_4173-150x150.jpg', basename($downsize[0]) );
		$this->assertEquals( 150, $downsize[1] );
		$this->assertEquals( 150, $downsize[2] );

		$downsize = image_downsize($id, 'medium');
		$this->assertEquals( '2007-06-17DSC_4173-400x602.jpg', basename($downsize[0]) );
		$this->assertEquals( 400, $downsize[1] );
		$this->assertEquals( 602, $downsize[2] );

		$downsize = image_downsize($id, 'full');
		$this->assertEquals( '2007-06-17DSC_4173.jpg', basename($downsize[0]) );
		$this->assertEquals( 680, $downsize[1] );
		$this->assertEquals( 1024, $downsize[2] );
	}


	function test_insert_image_delete() {
		update_option('medium_size_w', 400);
		update_option('medium_size_h', 0);

		$filename = ( DIR_TESTDATA.'/images/2007-06-17DSC_4173.JPG' );
		$contents = file_get_contents($filename);

		$upload = wp_upload_bits(basename($filename), null, $contents);
		$this->assertTrue( empty($upload['error']) );

		$id = $this->_make_attachment($upload);
		$uploads = wp_upload_dir();

		// check that the file and intermediates exist
		$thumb = image_get_intermediate_size($id, 'thumbnail');
		$this->assertEquals( '2007-06-17DSC_4173-150x150.jpg', $thumb['file'] );
		$this->assertTrue( is_file($uploads['basedir'] . DIRECTORY_SEPARATOR . $thumb['path']) );

		$medium = image_get_intermediate_size($id, 'medium');
		$this->assertEquals( '2007-06-17DSC_4173-400x602.jpg', $medium['file'] );
		$this->assertTrue( is_file($uploads['basedir'] . DIRECTORY_SEPARATOR . $medium['path']) );

		$meta = wp_get_attachment_metadata($id);
		$original = $meta['file'];
		$this->assertTrue( is_file($uploads['basedir'] . DIRECTORY_SEPARATOR . $original) );

		// now delete the attachment and make sure all files are gone
		wp_delete_attachment($id);

		$this->assertFalse( is_file($thumb['path']) );
		$this->assertFalse( is_file($medium['path']) );
		$this->assertFalse( is_file($original) );
	}

}
