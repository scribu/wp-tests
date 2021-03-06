<?php

/**
 * @group functions.php
 */
class Tests_Functions extends WP_UnitTestCase {
	function test_wp_parse_args_object() {
		$x = new MockClass;
		$x->_baba = 5;
		$x->yZ = "baba";
		$x->a = array(5, 111, 'x');
		$this->assertEquals(array('_baba' => 5, 'yZ' => 'baba', 'a' => array(5, 111, 'x')), wp_parse_args($x));
		$y = new MockClass;
		$this->assertEquals(array(), wp_parse_args($y));
	}
	function test_wp_parse_args_array()  {
		// arrays
		$a = array();
		$this->assertEquals(array(), wp_parse_args($a));
		$b = array('_baba' => 5, 'yZ' => 'baba', 'a' => array(5, 111, 'x'));
		$this->assertEquals(array('_baba' => 5, 'yZ' => 'baba', 'a' => array(5, 111, 'x')), wp_parse_args($b));
	}
	function test_wp_parse_args_defaults() {
		$x = new MockClass;
		$x->_baba = 5;
		$x->yZ = "baba";
		$x->a = array(5, 111, 'x');
		$d = array('pu' => 'bu');
		$this->assertEquals(array('pu' => 'bu', '_baba' => 5, 'yZ' => 'baba', 'a' => array(5, 111, 'x')), wp_parse_args($x, $d));
		$e = array('_baba' => 6);
		$this->assertEquals(array('_baba' => 5, 'yZ' => 'baba', 'a' => array(5, 111, 'x')), wp_parse_args($x, $e));
	}
	function test_wp_parse_args_other() {
		$b = true;
		wp_parse_str($b, $s);
		$this->assertEquals($s, wp_parse_args($b));
		$q = 'x=5&_baba=dudu&';
		wp_parse_str($q, $ss);
		$this->assertEquals($ss, wp_parse_args($q));
	}
	function test_size_format() {
		$kb = 1024;
		$mb = $kb*1024;
		$gb = $mb*1024;
		$tb = $gb*1024;
		// test if boundaries are correct
		$this->assertEquals('1 GB', size_format($gb, 0));
		$this->assertEquals('1 MB', size_format($mb, 0));
		$this->assertEquals('1 kB', size_format($kb, 0));
		// now some values around
		// add some bytes to make sure the result isn't 1.4999999
		$this->assertEquals('1.5 TB', size_format($tb + $tb/2 + $mb, 1));
		$this->assertEquals('1,023.999 GB', size_format($tb-$mb-$kb, 3));
		// edge
		$this->assertFalse(size_format(-1));
		$this->assertFalse(size_format(0));
		$this->assertFalse(size_format('baba'));
		$this->assertFalse(size_format(array()));
	}

	function test_path_is_absolute() {
		if ( !is_callable('path_is_absolute') )
			$this->markTestSkipped();

		$absolute_paths = array(
			'/',
			'/foo/',
			'/foo',
			'/FOO/bar',
			'/foo/bar/',
			'/foo/../bar/',
			'\\WINDOWS',
			'C:\\',
			'C:\\WINDOWS',
			'\\\\sambashare\\foo',
			);
		foreach ($absolute_paths as $path)
			$this->assertTrue( path_is_absolute($path), "path_is_absolute('$path') should return true" );
	}

	function test_path_is_not_absolute() {
		if ( !is_callable('path_is_absolute') )
			$this->markTestSkipped();

		$relative_paths = array(
			'',
			'.',
			'..',
			'../foo',
			'../',
			'../foo.bar',
			'foo/bar',
			'foo',
			'FOO',
			'..\\WINDOWS',
			);
		foreach ($relative_paths as $path)
			$this->assertFalse( path_is_absolute($path), "path_is_absolute('$path') should return false" );
	}


	function test_wp_unique_filename() {
		/* this test requires:
		   - that you have dir + file 'data/images/test-image.png',
		   - and that this dir is writeable
		   - there is an image 'test-image.png' that will be used to test unique filenames

		   NB: there is a hardcoded dependency that the testing file is '.png'; however,
		       this limitation is arbitary, so change it if you like.
		*/
		$testdir = DIR_TESTDATA . '/images/';
		$testimg = 'test-image.png';
		$this->assertTrue( file_exists($testdir) );
		$this->assertTrue( is_writable($testdir) );
		$this->assertTrue( file_exists($testdir . $testimg) );

		$cases = array(
			// null case
			'null' . $testimg,

			// edge cases: '.png', 'abc.', 'abc', 'abc0', 'abc1', 'abc0.png', 'abc1.png' (num @ end)
			'.png',
			'abc',
			'abc.',
			'abc0',
			'abc1',
			'abc0.png',
			'abc1.png',

			// replacing # with _
			str_replace('-', '#', $testimg), // test#image.png
			str_replace('-', '##', $testimg), // test##image.png
			str_replace(array('-', 'e'), '#', $testimg), // t#st#imag#.png
			str_replace(array('-', 'e'), '##', $testimg), // t##st##imag##.png

			// replacing \ or ' with nothing
			str_replace('-', '\\', $testimg), // test\image.png
			str_replace('-', '\\\\', $testimg), // test\\image.png
			str_replace(array('-', 'e'), '\\', $testimg), // t\st\imag\.png
			str_replace(array('-', 'e'), '\\\\', $testimg), // t\\st\\imag\\.png
			str_replace('-', "'", $testimg), // test'image.png
			str_replace('-', "'", $testimg), // test''image.png
			str_replace(array('-', 'e'), "'", $testimg), // t'st'imag'.png
			str_replace(array('-', 'e'), "''", $testimg), // t''st''imag''.png
			str_replace('-', "\'", $testimg), // test\'image.png
			str_replace('-', "\'\'", $testimg), // test\'\'image.png
			str_replace(array('-', 'e'), "\'", $testimg), // t\'st\'imag\'.png
			str_replace(array('-', 'e'), "\'\'", $testimg), // t\'\'st\'\'imag\'\'.png

			'test' . str_replace('e', 'é', $testimg), // testtést-imagé.png

			'12%af34567890~!@#$..%^&*()|_+qwerty  fgh`jkl zx<>?:"{}[]="\'/?.png', // kitchen sink
			$testdir.'test-image-with-path.png',
		);

		// what we expect the replacements will do
		$expected = array(
				'null' . $testimg,

				'png',
				'abc',
				'abc',
				'abc0',
				'abc1',
				'abc0.png',
				'abc1.png',

				'testimage.png',
				'testimage.png',
				'tstimag.png',
				'tstimag.png',

				'testimage.png',
				'testimage.png',
				'tstimag.png',
				'tstimag.png',
				'testimage.png',
				'testimage.png',
				'tstimag.png',
				'tstimag.png',
				'testimage.png',
				'testimage.png',
				'tstimag.png',
				'tstimag.png',

				'testtést-imagé.png',

				'12%af34567890@..%^_+qwerty-fghjkl-zx.png',
				str_replace( array( '\\', '/', ':' ), '', $testdir ).'test-image-with-path.png',
			);

		foreach ($cases as $key => $case) {
			// make sure expected file doesn't exist already
			// happens when tests fail and the unlinking doesn't happen
			if( $expected[$key] !== $testimg && file_exists($testdir . $expected[$key]) )
				unlink($testdir . $expected[$key]);

			// -- TEST 1: the replacement is as expected
			$this->assertEquals( $expected[$key], wp_unique_filename($testdir, $case, NULL), $case );
			// -- end TEST 1

			// -- TEST 2: the renaming will produce a unique name
			// create the expected file
			copy($testdir . $testimg, $testdir . $expected[$key]);
			// test that wp_unique_filename actually returns a unique filename
			$this->assertFileNotExists( $testdir . wp_unique_filename($testdir, $case, NULL) );
			// -- end TEST 2

			// cleanup
			if( $expected[$key] !== $testimg &&  file_exists($testdir . $expected[$key]) )
				unlink($testdir . $expected[$key]);
		}
	}

	/**
	 * @ticket 9930
	 */
	function test_is_serialized() {
		$cases = array(
			serialize(null),
			serialize(true),
			serialize(false),
			serialize(-25),
			serialize(25),
			serialize(1.1),
			serialize(2.1E+200),
			serialize('this string will be serialized'),
			serialize("a\nb"),
			serialize(array()),
			serialize(array(1,1,2,3,5,8,13)),
			serialize( (object)array('test' => true, '3', 4) )
		);
		foreach ( $cases as $case )
			$this->assertTrue( is_serialized($case), "Serialized data: $case" );

		$not_serialized = array(
			'a string',
			'garbage:a:0:garbage;',
			'b:4;',
			's:4:test;'
		);
		foreach ( $not_serialized as $case )
			$this->assertFalse( is_serialized($case), "Test data: $case" );
	}

	function test_add_query_arg() {
		$old_req_uri = $_SERVER['REQUEST_URI'];

		$urls = array(
			'/',
			'/2012/07/30/',
			'edit.php',
			admin_url( 'edit.php' ),
			admin_url( 'edit.php', 'https' ),
		);

		$frag_urls = array(
			'/#frag',
			'/2012/07/30/#frag',
			'edit.php#frag',
			admin_url( 'edit.php#frag' ),
			admin_url( 'edit.php#frag', 'https' ),
		);

		foreach ( $urls as $url ) {
			$_SERVER['REQUEST_URI'] = 'nothing';

			$this->assertEquals( "$url?foo=1", add_query_arg( 'foo', '1', $url ) );
			$this->assertEquals( "$url?foo=1", add_query_arg( array( 'foo' => '1' ), $url ) );
			$this->assertEquals( "$url?foo=2", add_query_arg( array( 'foo' => '1', 'foo' => '2' ), $url ) );
			$this->assertEquals( "$url?foo=1&bar=2", add_query_arg( array( 'foo' => '1', 'bar' => '2' ), $url ) );

			$_SERVER['REQUEST_URI'] = $url;

			$this->assertEquals( "$url?foo=1", add_query_arg( 'foo', '1' ) );
			$this->assertEquals( "$url?foo=1", add_query_arg( array( 'foo' => '1' ) ) );
			$this->assertEquals( "$url?foo=2", add_query_arg( array( 'foo' => '1', 'foo' => '2' ) ) );
			$this->assertEquals( "$url?foo=1&bar=2", add_query_arg( array( 'foo' => '1', 'bar' => '2' ) ) );
		}

		foreach ( $frag_urls as $frag_url ) {
			$_SERVER['REQUEST_URI'] = 'nothing';
			$url = str_replace( '#frag', '', $frag_url );

			$this->assertEquals( "$url?foo=1#frag", add_query_arg( 'foo', '1', $frag_url ) );
			$this->assertEquals( "$url?foo=1#frag", add_query_arg( array( 'foo' => '1' ), $frag_url ) );
			$this->assertEquals( "$url?foo=2#frag", add_query_arg( array( 'foo' => '1', 'foo' => '2' ), $frag_url ) );
			$this->assertEquals( "$url?foo=1&bar=2#frag", add_query_arg( array( 'foo' => '1', 'bar' => '2' ), $frag_url ) );

			$_SERVER['REQUEST_URI'] = $frag_url;

			$this->assertEquals( "$url?foo=1#frag", add_query_arg( 'foo', '1' ) );
			$this->assertEquals( "$url?foo=1#frag", add_query_arg( array( 'foo' => '1' ) ) );
			$this->assertEquals( "$url?foo=2#frag", add_query_arg( array( 'foo' => '1', 'foo' => '2' ) ) );
			$this->assertEquals( "$url?foo=1&bar=2#frag", add_query_arg( array( 'foo' => '1', 'bar' => '2' ) ) );
		}

		$qs_urls = array(
			'/?baz',
			'/2012/07/30/?baz',
			'edit.php?baz',
			admin_url( 'edit.php?baz' ),
			admin_url( 'edit.php?baz', 'https' ),
			admin_url( 'edit.php?baz&za=1' ),
			admin_url( 'edit.php?baz=1&za=1' ),
			admin_url( 'edit.php?baz=0&za=0' ),
		);

		foreach ( $qs_urls as $url ) {
			$_SERVER['REQUEST_URI'] = 'nothing';

			$this->assertEquals( "$url&foo=1", add_query_arg( 'foo', '1', $url ) );
			$this->assertEquals( "$url&foo=1", add_query_arg( array( 'foo' => '1' ), $url ) );
			$this->assertEquals( "$url&foo=2", add_query_arg( array( 'foo' => '1', 'foo' => '2' ), $url ) );
			$this->assertEquals( "$url&foo=1&bar=2", add_query_arg( array( 'foo' => '1', 'bar' => '2' ), $url ) );

			$_SERVER['REQUEST_URI'] = $url;

			$this->assertEquals( "$url&foo=1", add_query_arg( 'foo', '1' ) );
			$this->assertEquals( "$url&foo=1", add_query_arg( array( 'foo' => '1' ) ) );
			$this->assertEquals( "$url&foo=2", add_query_arg( array( 'foo' => '1', 'foo' => '2' ) ) );
			$this->assertEquals( "$url&foo=1&bar=2", add_query_arg( array( 'foo' => '1', 'bar' => '2' ) ) );
		}

		$_SERVER['REQUEST_URI'] = $old_req_uri;
	}

	/**
	 * @ticket 21594
	 */
	function test_get_allowed_mime_types() {
		$mimes = get_allowed_mime_types();

		$this->assertInternalType( 'array', $mimes );
		$this->assertNotEmpty( $mimes );

		add_filter( 'upload_mimes', '__return_empty_array' );
		$mimes = get_allowed_mime_types();
		$this->assertInternalType( 'array', $mimes );
		$this->assertEmpty( $mimes );

		remove_filter( 'upload_mimes', '__return_empty_array' );
		$mimes = get_allowed_mime_types();
		$this->assertInternalType( 'array', $mimes );
		$this->assertNotEmpty( $mimes );
	}

	/**
	 * @ticket 21594
	 */
	function test_wp_get_mime_types() {
		$mimes = wp_get_mime_types();

		$this->assertInternalType( 'array', $mimes );
		$this->assertNotEmpty( $mimes );

		add_filter( 'mime_types', '__return_empty_array' );
		$mimes = wp_get_mime_types();
		$this->assertInternalType( 'array', $mimes );
		$this->assertEmpty( $mimes );

		remove_filter( 'mime_types', '__return_empty_array' );
		$mimes = wp_get_mime_types();
		$this->assertInternalType( 'array', $mimes );
		$this->assertNotEmpty( $mimes );

		// upload_mimes shouldn't affect wp_get_mime_types()
		add_filter( 'upload_mimes', '__return_empty_array' );
		$mimes = wp_get_mime_types();
		$this->assertInternalType( 'array', $mimes );
		$this->assertNotEmpty( $mimes );

		remove_filter( 'upload_mimes', '__return_empty_array' );
		$mimes2 = wp_get_mime_types();
		$this->assertInternalType( 'array', $mimes2 );
		$this->assertNotEmpty( $mimes2 );
		$this->assertEquals( $mimes2, $mimes );
	}
}
