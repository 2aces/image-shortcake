<?php

class Test_Img_Shortcode extends WP_UnitTestCase {

	function test_construct_ui() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}


	/*
	 * Simplest case: An [img] shortcode with a url passed as a src argument
	 * should just render an image with that src.
	 */
	function test_img_shortcode_with_src_tag() {
		$str = '[img src="http://example.com/example.jpg" align="alignleft" /]';
		$content = apply_filters( 'the_content', $str );

		$this->assertContains( '<img class="size-full alignleft" src="http://example.com/example.jpg" />', $content );
	}


	/**
	 * Create an actual attachment, and test that the link and caption elements
	 * are implemented correctly.
	 */
	function test_img_shortcode_from_attachment() {

		$attachment_id = $this->insert_attachment( null,
			dirname( __FILE__ ) . '/data/fusion_image_placeholder_16x9_h2000.png',
			array(
				'post_title'     => 'Post',
				'post_content'   => 'Post Content',
				'post_date'      => '2014-10-01 17:28:00',
				'post_status'    => 'publish',
				'post_type'      => 'attachment',
			)
		);

		$upload_dir = wp_upload_dir();

		// Test image src
		$content = apply_filters( 'the_content', '[img attachment="' . $attachment_id . '" /]' );

		$expected_src_attr = $upload_dir['url'] . '/fusion_image_placeholder_16x9_h2000.png';
		$this->assertContains( 'src="' . $expected_src_attr . '"', $content );

		// Test link href: linkto="file"
		$content = apply_filters( 'the_content', '[img attachment="' . $attachment_id . '" linkto="file" /]' );

		$expected_href_attr = $upload_dir['url'] . '/fusion_image_placeholder_16x9_h2000.png';
		$this->assertContains( 'href="' . $expected_href_attr . '"', $content );

		// Test link href: linkto="attachment"
		$content = apply_filters( 'the_content', '[img attachment="' . $attachment_id . '" linkto="attachment" /]' );

		$expected_href_attr = get_permalink( $attachment_id );
		$this->assertContains( 'href="' . $expected_href_attr . '"', $content );

		// Test caption attribute
		$caption = <<<EOL
This is a "<em>caption</em>". It should contain <abbr>HTML</abbr> and <span class="icon">markup</span>.
EOL;
		$content = apply_filters( 'the_content', '[img attachment="' . $attachment_id . '" caption="' . esc_attr( $caption ) . '" /]' );

		$expected_caption = esc_html( $caption );
		$this->assertContains( $expected_caption , $content );
	}


	/**
	 * Helper function: insert an attachment to test properties of.
	 *
	 * @param int $parent_post_id
	 * @param str path to image to use
	 * @param array $post_fields Fields, in the format to be sent to `wp_insert_post()`
	 * @return int Post ID of inserted attachment
	 */
	private function insert_attachment( $parent_post_id = 0, $image = null, $post_fields = array() ) {

		$filename = rand_str().'.jpg';
		$contents = rand_str();

		if ( $image ) {
			$filename = basename( $image );
			$contents = file_get_contents( $image );
		}

		$upload = wp_upload_bits( $filename, null, $contents );
		$this->assertTrue( empty( $upload['error'] ) );

		$type = '';
		if ( ! empty( $upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( $mime ) {
				$type = $mime['type'];
			}
		}

		$attachment = wp_parse_args( $post_fields,
			array(
				'post_title' => basename( $upload['file'] ),
				'post_content' => 'Test Attachment',
				'post_type' => 'attachment',
				'post_parent' => $parent_post_id,
				'post_mime_type' => $type,
				'guid' => $upload['url'],
			)
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;
	}

}

