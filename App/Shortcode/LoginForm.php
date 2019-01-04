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

	public function __construct() {
		add_shortcode( 'snow_monkey_member_post_login_form', [ $this, '_view' ] );
		add_filter( 'authenticate', [ $this, '_redirect' ], 101, 3 );
	}

	/**
	 * Register shortcode
	 *
	 * @param array $atts
	 * @return string
	 * @see https://core.trac.wordpress.org/browser/trunk/src/wp-login.php
	 */
	public function _view( $atts ) {
		if ( is_user_logged_in() ) {
			ob_start();
			View::render( 'shortcode/login-form/loggedin' );
			return ob_get_clean();
		}

		$atts = shortcode_atts(
			[
				'redirect_to' => $this->_get_current_url(),
			],
			$atts
		);

		ob_start();
		View::render( 'shortcode/login-form/index', $atts );
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
		$redirect_to = add_query_arg( 'login', $error_codes, $redirect_to );

		wp_safe_redirect( $redirect_to );
	}

	/**
	 * Return current URL
	 *
	 * @return string
	 */
	protected function _get_current_url() {
		$path = filter_input( INPUT_SERVER, 'REQUEST_URI' );
		return home_url( $path );
	}
}
