<?php
/**
 * @package snow-monkey-member-post
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Snow_Monkey\Plugin\SnowMonkeyMemberPost\App\Controller;

use Snow_Monkey\Plugin\SnowMonkeyMemberPost\App\Config;
use Snow_Monkey\Plugin\SnowMonkeyMemberPost\App\View;

class Content {

	public function __construct() {
		add_filter( 'the_content', [ $this, '_restrict_content' ] );
		add_filter( 'the_excerpt', [ $this, '_restrict_excerpt' ] );
	}

	/**
	 * Restrict content
	 *
	 * @param string $content
	 * @return string
	 */
	public function _restrict_content( $content ) {
		$post = get_post();

		if ( ! $this->_is_restricted( $post ) ) {
			return $content;
		}

		ob_start();

		View::render(
			'content',
			[
				'post'    => $post,
				'content' => $content,
			]
		);

		return ob_get_clean();
	}

	/**
	 * Restrict excerp
	 *
	 * @param string $content
	 * @return string
	 */
	public function _restrict_excerpt( $content ) {
		$post = get_post();

		if ( ! $this->_is_restricted( $post ) ) {
			return $content;
		}

		ob_start();

		View::render(
			'excerpt',
			[
				'post'    => $post,
				'excerpt' => $content,
			]
		);

		return ob_get_clean();
	}

	/**
	 * Return true when the post is restricted
	 *
	 * @param WP_Post $_post
	 * @return boolean
	 */
	protected function _is_restricted( $_post ) {
		$return = true;
		$restriction = (int) get_post_meta( $_post->ID, Config::get( 'restriction-key' ), true );

		if ( ! $_post || is_user_logged_in() || 1 !== $restriction ) {
			$return = false;
		}

		return apply_filters( 'snow_monkey_member_post_is_restricted', $return, $_post );
	}
}
