<?php

class Test_Img_Shortcode_Data_Migration extends WP_UnitTestCase {

	private $attachment_id;
	private $image_src;

	public function setUp() {

		parent::setUp();

		$attachment_id = $this->insert_attachment( null,
			dirname( __FILE__ ) . '/data/fusion_image_placeholder_16x9_h2000a.png',
			array(
				'post_title'     => 'Post',
				'post_content'   => 'Post Content',
				'post_date'      => '2014-10-01 17:28:00',
				'post_status'    => 'publish',
				'post_type'      => 'attachment',
			)
		);

		$this->attachment_id = $attachment_id;

		$upload_dir = wp_upload_dir();

		$this->image_src = $upload_dir['url'] . '/fusion_image_placeholder_16x9_h2000a.png';
	}

	public function tearDown() {
		parent::tearDown();
	}


	/**
	 * Case: <img> tags where the src is an attachment
	 *
	 */
	function test_img_tag_from_attachment() {
		$img_tag =
			'<img class="size-large wp-image-' . $this->attachment_id . ' aligncenter" ' .
				'src="' . $this->image_src . '" ' .
				'alt="This is the alt attribute." ' .
				'width="1024" height="540" />';

		$img_tag_link_custom =
			'<a href="http://go.to/thislink/">' . $img_tag . '</a>';

		$img_tag_link_file =
			'<a href="' . $this->image_src . '">' . $img_tag . '</a>';

		$img_tag_link_attachment =
			'<a href="' . get_permalink( $this->attachment_id ) . '">' . $img_tag . '</a>';

		$post_content = "$img_tag\r\n$img_tag_link_custom\r\n$img_tag_link_file\r\n$img_tag_link_attachment\r\n";

		$post_id = wp_insert_post( array( 'post_content' => $post_content ) );

		$replacements = Img_Shortcode_Data_Migration::find_img_tags_for_replacement( $post_id );

		foreach ( array( $img_tag, $img_tag_link_custom, $img_tag_link_file, $img_tag_link_attachment ) as $should_be_matched ) {
			$this->assertContains( $should_be_matched, array_keys( $replacements ) );
		}

		$this->assertContains( 'attachment="' . $this->attachment_id .'"', $replacements[ $img_tag ] );
		$this->assertNotContains( 'src="', $replacements[ $img_tag ] );

		$this->assertContains( 'href="http://go.to/thislink/"', $replacements[ $img_tag_link_custom ] );
		$this->assertNotContains( 'linkto=', $replacements[ $img_tag_link_custom ] );

		$this->assertNotContains( 'href=', $replacements[ $img_tag_link_file ] );
		$this->assertContains( 'linkto="file"', $replacements[ $img_tag_link_file ] );

		$this->assertNotContains( 'href=', $replacements[ $img_tag_link_attachment ] );
		$this->assertContains( 'linkto="attachment"', $replacements[ $img_tag_link_attachment ] );
	}

	/**
	 * Case: <img> tags with an external src
	 *
	 */
	function test_img_tag_from_src() {
		$img_tag =
			'<a href="http://go.to/thislink/">' .
				'<img class="aligncenter" ' .
				'src="' . $this->image_src . '" ' .
				'alt="This is the alt attribute." ' .
				'width="1024" height="540" />' .
			'</a>';

		$post_id = wp_insert_post( array( 'post_content' => "\r\n\r\n$img_tag\r\nblah blah blah" ) );

		$replacements = Img_Shortcode_Data_Migration::find_img_tags_for_replacement( $post_id );

		$this->assertNotContains( 'attachment="', $replacements[ $img_tag ] );
		$this->assertContains( 'src="' . $this->image_src .'"', $replacements[ $img_tag ] );
	}

		/**
		 * Case: <img> tags wrapped in links
		 *
		 */
		/**
		 * Case: [caption] shortcodes containing any of the above items
		 *
		 */
		/*
		 * Simplest case: An [img] shortcode with a url passed as a src argument
		 * should just render an image with that src.
		 */

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

