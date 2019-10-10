<?php
/**
 * @package snow-monkey-member-post
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Snow_Monkey\Plugin\SnowMonkeyMemberPost\App\Shortcode;

use Snow_Monkey\Plugin\SnowMonkeyMemberPost\App\Config;
use Snow_Monkey\Plugin\SnowMonkeyMemberPost\App\View;

class LoginForm {

	protected $in_the_view = false;
	protected $view_slug;

	public function __construct() {
		add_filter( 'inc2734_wp_view_controller_view', [ $this, '_set_in_the_view' ] );

		// For under Snow Monkey v7
		add_action( 'inc2734_view_controller_get_template_part_post_render', [ $this, '_unset_in_the_view' ] );

		// For over Snow Monkey v8
		add_action( 'inc2734_wp_view_controller_get_template_part_post_render', [ $this, '_unset_in_the_view' ] );

		add_shortcode( 'snow_monkey_member_post_login_form', [ $this, '_view' ] );
		add_filter( 'authenticate', [ $this, '_redirect' ], 101, 3 );
	}

	public function _set_in_the_view( $view ) {
		$this->in_the_view = true;
		$this->view_slug   = $view['slug'];
		return $view;
	}

	public function _unset_in_the_view( $args ) {
		if ( $this->view_slug === $args['slug'] ) {
			$this->in_the_view = false;
		}
	}

	/**
	 * Register shortcode
	 *
	 * @param array $atts
	 * @return string
	 * @see https://core.trac.wordpress.org/browser/trunk/src/wp-login.php
	 */
	public function _view( $atts ) {
		if ( is_user_logged_in() && $this->in_the_view ) {
			return;
		}

		$atts = shortcode_atts(
			[
				'redirect_to' => $this->_get_current_url(),
			],
			$atts
		);

		ob_start();
		View::render(
			is_user_logged_in() ? 'shortcode/logout/index' : 'shortcode/login-form/index',
			$atts
		);
		return ob_get_clean();
	}

	/**
	 * Authenticates a user using the username and password.
	 *
	 * @param WP_User|WP_Error|null $user
	 * @param string $username
	 * @param string $password
	 * @return WP_User|WP_Error
	 */
	public function _redirect( $user, $username, $password ) {
		$nonce_key = Config::get( 'login-form-nonce-key' );
		$nonce     = filter_input( INPUT_POST, $nonce_key );
		if ( ! $nonce ) {
			return $user;
		}

		if ( ! wp_verify_nonce( $nonce, $nonce_key ) ) {
			return $user;
		}

		if ( ! is_wp_error( $user ) ) {
			return $user;
		}

		$redirect_to = filter_input( INPUT_POST, 'redirect_to' );
		if ( ! $redirect_to ) {
			return $user;
		}

		$error_codes = implode( ',', $user->get_error_codes() );

		if ( empty( $error_codes ) ) {
			wp_safe_redirect( $redirect_to );
		}

		$referer = $this->_get_http_referer();
		if ( $referer ) {
			$redirect_to = add_query_arg( 'login_error_codes', $error_codes, $referer );
			wp_safe_redirect( $redirect_to );
		}

		$redirect_to = add_query_arg( 'login_error_codes', $error_codes, $redirect_to );
		wp_safe_redirect( $redirect_to );

		return $user;
	}

	/**
	 * Return current URL
	 *
	 * @return string
	 */
	protected function _get_current_url() {
		$path = filter_input( INPUT_SERVER, 'REQUEST_URI' );
		$path = remove_query_arg( 'login_error_codes', $path );
		$path = remove_query_arg( 'register_error_codes', $path );
		return home_url( $path );
	}

	/**
	 * Return HTTP_REFERER
	 *
	 * @return string
	 */
	protected function _get_http_referer() {
		$referer = null;

		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$referer = $_SERVER['HTTP_REFERER'];
			$referer = remove_query_arg( 'login_error_codes', $referer );
			$referer = remove_query_arg( 'register_error_codes', $referer );
		}

		return $referer;
	}
}
