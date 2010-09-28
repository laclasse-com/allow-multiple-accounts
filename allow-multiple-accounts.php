<?php
/**
 * @package Allow_Multiple_Accounts
 * @author Scott Reilly
 * @version 2.0.1
 */
/*
Plugin Name: Allow Multiple Accounts
Version: 2.0.1
Plugin URI: http://coffee2code.com/wp-plugins/allow-multiple-accounts/
Author: Scott Reilly
Author URI: http://coffee2code.com
Text Domain: allow-multiple-accounts
Description: Allow multiple user accounts to be created from the same email address.

Compatible with WordPress 2.8+, 2.9+, 3.0+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/allow-multiple-accounts/

*/

/*
Copyright (c) 2008-2010 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if ( !class_exists( 'AllowMultipleAccounts' ) ) :

require_once( 'c2c-plugin.php' );

class AllowMultipleAccounts extends C2C_Plugin_016 {

	var $allow_multiple_accounts = false;  // Used internally; not a setting!
	var $exceeded_limit = false;
	var $retrieve_password_for = '';
	var $during_user_creation = false; // part of a hack

	/**
	 * Constructor
	 */
	function AllowMultipleAccounts() {
		$this->C2C_Plugin_016( '2.0.1', 'allow-multiple-accounts', 'c2c', __FILE__, array( 'settings_page' => 'users' ) );
	}

	/**
	 * Initializes the plugin's config data array.
	 *
	 * @return void
	 */
	function load_config() {
		$this->name = __( 'Allow Multiple Accounts', $this->textdomain );
		$this->menu_name = __( 'Multiple Accounts', $this->textdomain );

		$this->config = array(
			'allow_for_everyone' => array('input' => 'checkbox', 'default' => true,
					'label' => __( 'Allow multiple accounts for everyone?', $this->textdomain ),
					'help' => __( 'If not checked, only the emails listed below can have multiple accounts.', $this->textdomain ) ),
			'account_limit' => array( 'input' => 'text', 'default' => '',
					'label' => __( 'Account limit', $this->textdomain ),
					'help' => __( 'The maximum number of accounts that can be associated with a single email address.  Leave blank to indicate no limit.', $this->textdomain ) ),
			'emails' => array( 'input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => 'style="width:98%;" rows="6"',
					'label' => __( 'Multi-account emails', $this->textdomain ),
					'help' => __( 'If the checkbox above is unchecked, then only the emails listed here will be allowed to have multiple accounts.  Define one per line.', $this->textdomain ) )
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually actions against filters.
	 *
	 * @return void
	 */
	function register_filters() {
		add_action( 'check_passwords', array( &$this, 'hack_check_passwords' ) );
		add_filter( 'pre_user_display_name', array( &$this, 'hack_pre_user_email' ) );
		add_filter( 'pre_user_email', array( &$this, 'hack_pre_user_email' ) );
		add_action( 'register_post', array( &$this, 'register_post' ), 1, 3 );
		add_filter( 'registration_errors', array( &$this, 'registration_errors' ), 1 );
		add_action( 'retrieve_password', array( &$this, 'retrieve_password' ) );
		add_filter( 'retrieve_password_message', array( &$this, 'retrieve_password_message' ) );
		add_action( 'user_profile_update_errors', array( &$this, 'user_profile_update_errors' ), 1, 3 );
		add_action( $this->get_hook( 'after_settings_form' ), array( &$this, 'list_multiple_accounts' ) );
	}

	/**
	 * Outputs the text above the setting form
	 *
	 * @return void (Text will be echoed.)
	 */
	function options_page_description() {
		$options = $this->get_options();
		parent::options_page_description( __( 'Allow Multiple Accounts Settings', $this->textdomain ) );
		echo '<p>' . __( 'Allow multiple user accounts to be created from the same email address.', $this->textdomain ) . '</p>';
		echo '<p>' . __( 'By default, WordPress only allows a single user account to be assigned to a specific email address.  This plugin removes that restriction.  A setting is also provided to allow only certain email addresses the ability to have multiple accounts.  You may also specify a limit to the number of accounts an email address can have.', $this->textdomain ) . '</p>';
		echo '<p><a href="#multiaccount_list">' . __( 'View a list of user accounts grouped by email address.', $this->textdomain ) . '</a></p>';
	}

	/**
	 * This is a HACK because WP 3.0 introduced a change that made it impossible to suppress the unique email check when creating a new user.
	 *
	 * For the hack, this filter is invoked just after wp_insert_user() checks for the uniqueness of the email address.  What this
	 * is doing is unsetting the flag by the get_user_by_email() overridden by this plugin, so that when called in any other context than
	 * wp_insert_user(), it'll actually get the user by email.
	 *
	 * @since 2.0
	 *
	 * @param string $display_name Display name for user
	 * @return string The same value as passed to the function
	 */
	function hack_pre_user_display_name( $display_name ) {
		$this->during_user_creation = false;
		return $display_name;
	}

	/**
	 * This is a HACK because WP 3.0 introduced a change that made it impossible to suppress the unique email check when creating a new user.
	 *
	 * For the hack, this filter is invoked just before wp_insert_user() checks for the uniqueness of the email address.  What this
	 * is doing is setting a flag so that the get_user_by_email() overridden by this plugin, when called in the wp_insert_user() context,
	 * knows to return false, making WP think the email address isn't in use.
	 *
	 * @since 2.0
	 *
	 * @param string $email Email for the user
	 * @return string The same value as passed to the function
	 */
	function hack_pre_user_email( $email ) {
		$this->during_user_creation = true;
		return $email;
	}

	/**
	 * This is a HACK because WP 3.0 introduced a change that made it impossible to suppress the unique email check when creating a new user.
	 *
	 * For the hack, this filter is invoked just before edit_user() does a bunch of error checks.  What this
	 * is doing is setting a flag so that the get_user_by_email() overridden by this plugin, when called in the edit_user() context,
	 * knows to return false, making WP think the email address isn't in use.
	 *
	 * @since 2.0
	 *
	 * @param string $user_login User login
	 * @return void
	 */
	function hack_check_passwords( $user_login ) {
		$this->during_user_creation = true;
	}

	/**
	 * Outputs a list of all user email addresses and their associated accounts.
	 *
	 * @return void (Text is echoed.)
	 */
	function list_multiple_accounts() {
		global $wpdb;
		$users = $wpdb->get_results( "SELECT ID, user_email FROM $wpdb->users ORDER BY user_login" );
		$by_email = array();
		foreach ( $users as $user )
			$by_email[$user->user_email][] = $user;
		$emails = array_keys( $by_email );
		sort( $emails );
		$style = '';

		echo <<<END
			<style type="text/css">
				.emailrow {
					background-color:#ffffef;
				}
				.check-column {
					display:none;
				}
			</style>
			<div class='wrap'><a name='multiaccount_list'></a>
				<h2>

END;
		echo __( 'E-mail Addresses with Multiple User Accounts', $this->textdomain );
		echo <<<END
				</h2>
				<table class="widefat">
				<thead>
				<tr class="thead">

END;
		echo '<th>' . __( 'Username', $this->textdomain ) . '</th>' .
			 '<th>' . __( 'Name', $this->textdomain ) . '</th>' .
			 '<th>' . __( 'E-mail', $this->textdomain ) . '</th>' .
			 '<th>' . __( 'Role', $this->textdomain ) . '</th>' .
			 '<th class="num">' . __( 'Posts', $this->textdomain ) . '</th>';
		echo <<<END
				</tr>
				</thead>
				<tbody id="users" class="list:user user-list">

END;

		foreach ( $emails as $email ) {
			$email_users = $by_email[$email];
			$count = count( $by_email[$email] );
			echo '<tr class="emailrow"><td colspan="6">';
			printf( _n( '%1$s &#8212; %2$d account', '%1$s &#8212; %2$d accounts', $count, $this->textdomain ), $email, $count );
			echo '</td></tr>';
			foreach ( $by_email[$email] as $euser ) {
				$user_object = new WP_User($euser->ID);
				$roles = $user_object->roles;
				$role = array_shift( $roles );
				$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
				echo "\n\t" . user_row( $user_object, $style, $role );
			}
		}

		echo <<<END
				</tbody>
				</table>
			</div>

END;
	}

	/**
	 * Indicates if the specified email address has exceeded its allowable number of accounts.
	 *
	 * @param string $email Email address
	 * @param int $user_id (optional) ID of existing user, if updating a user
	 * @return boolean True if the email address has exceeded its allowable number of accounts; false otherwise
	 */
	function has_exceeded_limit( $email, $user_id = null ) {
		$has = false;
		$options = $this->get_options();
		if ( $options['account_limit'] ) {
			$limit = (int) $options['account_limit'];
			$count = $this->count_multiple_accounts( $email, $user_id );
			if ( $count >= $limit )
				$has = true;
		}
		return $has;
	}

	/**
	 * Returns a count of the number of users associated with the given email.
	 *
	 * @param string $email The email account
	 * @param int $user_id (optional) ID of existing user, if updating a user
	 * @return int The number of users associated with the given email
	 */
	function count_multiple_accounts( $email, $user_id =  null ) {
		global $wpdb;
		$sql = "SELECT COUNT(*) AS count FROM $wpdb->users WHERE user_email = %s";
		if ( $user_id )
			$sql .= ' AND ID != %d';
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $email, $user_id ) );
	}

	/**
	 * Returns the users associated with the given email.
	 *
	 * @param string $email The email account
	 * @return array All of the users associated with the given email
	 */
	function get_users_by_email( $email ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_email = %s", $email ) );
	}

	/**
	 * Returns a boolean indicating if the given email is associated with more than one user account.
	 *
	 * @param string $email The email account
	 * @return bool True if the given email is associated with more than one user account; false otherwise
	 */
	function has_multiple_accounts( $email ) {
		return $this->count_multiple_accounts( $email ) > 1 ? true : false;
	}

	/**
	 * Handler for 'register_post' action.  Intercepts potential 'email_exists' error and sets flags for later use, pertaining to if
	 * multiple accounts are authorized for the email and/or if the email has exceeded its allocated number of accounts.
	 *
	 * @param string $user_login User login
	 * @param string $user_email User email
	 * @param WP_Error $errors Error object
	 * @param int $user_id (optional) ID of existing user, if updating a user
	 * @return void
	 */
	function register_post( $user_login, $user_email, $errors, $user_id = null ) {
		$options = $this->get_options();
		if ( $errors->get_error_message( 'email_exists' ) &&
			( $options['allow_for_everyone'] || in_array( $user_email, $options['emails'] ) ) ) {
			if ( $this->has_exceeded_limit( $user_email, $user_id ) )
				$this->exceeded_limit = true;
			else
				$this->allow_multiple_accounts = true;
		}
	}

	/**
	 * Handler for 'registration_errors' action to add and/or remove registration errors as needed.
	 *
	 * @param WP_Error $errors Error object
	 * @return WP_Error The potentially modified error object
	 */
	function registration_errors( $errors ) {
		if ( $this->exceeded_limit )
			$errors->add( 'exceeded_limit', __( '<strong>ERROR</strong>: Too many accounts are associated with this email, please choose another one.', $this->textdomain ) );
		if ( $this->allow_multiple_accounts || $this->exceeded_limit ) {
			unset( $errors->errors['email_exists'] );
			unset( $errors->error_data['email_exists'] );
		}
		return $errors;
	}

	/**
	 * Roundabout way of determining what user account a password retrieval is being requested for since some of the actions/filters don't specify.
	 *
	 * @param string $user_login User login
	 * @return string The same value as passed to the function
	 */
	function retrieve_password( $user_login ) {
		$this->retrieve_password_for = $user_login;
		return $user_login;
	}

	/**
	 * Appends text at the end of a 'retrieve password' email to remind users what accounts they have associated with their email address.
	 *
	 * @param string $message The original email message
	 * @return string Potentially modified email message
	 */
	function retrieve_password_message( $message ) {
		$user = get_user_by( 'login', $this->retrieve_password_for );
		if ( $this->has_multiple_accounts( $user->user_email ) ) {
			$message .= "\r\n\r\n";
			$message .= __( 'For your information, your e-mail address is also associated with the following accounts:', $this->textdomain ) . "\r\n\r\n";
			foreach ( $this->get_users_by_email( $user->user_email ) as $user ) {
				$message .= "\t" . $user->user_login . "\r\n";
			}
			$message .= "\r\n";
			$message .= __( 'In order to reset the password for any of these (if you aren\'t already successfully in the middle of doing so already), you should specify the login when requesting a password reset rather than using your e-mail.', $this->textdomain ) . "\r\n\r\n";
		}
		return $message;
	}

	/**
	 * Intercept possible email_exists errors during user updating, and also possibly add errors.
	 *
	 * @param WP_Error $errors Error object
	 * @param boolean $update Is this being invoked due to a user being updated?
	 * @param WP_User $user User object
	 */
	function user_profile_update_errors( $errors, $update, $user ) {
		$this->during_user_creation = false; // Part of HACK to work around WP3.0.0 bug
		$user_id = $update ? $user->ID : null;
		$this->register_post( $user->user_login, $user->user_email, $errors, $user_id );
		$errors = $this->registration_errors( $errors );
	}
} // end AllowMultipleAccounts

$GLOBALS['c2c_allow_multiple_accounts'] = new AllowMultipleAccounts();

endif; // end if !class_exists()


	//
	/**
	 * *******************
	 * TEMPLATE FUNCTIONS
	 *
	 * Functions suitable for use in other themes and plugins
	 * *******************
	 */

	/**
	 * Returns a count of the number of users associated with the given email.
	 *
	 * @since 2.0
	 *
	 * @param string $email The email account
	 * @return int The number of users associated with the given email
	 */
	if ( !function_exists( 'c2c_count_multiple_accounts' ) ) {
		function c2c_count_multiple_accounts( $email ) { return $GLOBALS['c2c_allow_multiple_accounts']->count_multiple_accounts( $email ); }
	}

	/**
	 * Returns the users associated with the given email.
	 *
	 * @since 2.0
	 *
	 * @param string $email The email account
	 * @return array All of the users associated with the given email
	 */
	if ( !function_exists( 'c2c_get_users_by_email' ) ) {
		function c2c_get_users_by_email( $email ) { return $GLOBALS['c2c_allow_multiple_accounts']->get_users_by_email( $email ); }
	}

	/**
	 * Returns a boolean indicating if the given email is associated with more than one user account.
	 *
	 * @since 2.0
	 *
	 * @param string $email The email account
	 * @return bool True if the given email is associated with more than one user account; false otherwise
	 */
	if ( !function_exists( 'c2c_has_multiple_accounts' ) ) {
		function c2c_has_multiple_accounts( $email ) { return $GLOBALS['c2c_allow_multiple_accounts']->has_multiple_accounts( $email ); }
	}

	/**
	 * This is only overridden as part of a HACK solution to a bug in WP 3.0 not allowing suppression of the duplicate email check.
	 *
	 * What it does: Replaces WP's get_user_by_email(). If during the user creation process (hackily determined by the plugin's instance)
	 * AND the email has not exceeded the account limit, then return false.  wp_insert_user() calls this function simply to check if the
	 * email is already associated with an account.  So in that instance, if we know that's where the request is originating and that the
	 * email in question is allowed to have multiple accounts, then trick the check into thinking the email isn't in use so that an error
	 * isn't generated.
	 *
	 * @since 2.0
	 *
	 * @param string $email User email
	 * @return string User associated with the email
	 */
	if ( !function_exists( 'get_user_by_email' ) ) {
		function get_user_by_email( $email ) {
			if ( $GLOBALS['c2c_allow_multiple_accounts']->during_user_creation && !$GLOBALS['c2c_allow_multiple_accounts']->has_exceeded_limit( $email ) )
				return false;
			return get_user_by('email', $email);
		}
	}

	/**
	 * *******************
	 * DEPRECATED FUNCTIONS
	 * *******************
	 */
	if ( !function_exists( 'count_multiple_accounts' ) ) {
		function count_multiple_accounts( $email ) { return c2c_count_multiple_accounts( $email ); }
	}
	if ( !function_exists( 'get_users_by_email' ) ) {
		function get_users_by_email( $email ) { return c2c_get_users_by_email( $email ); }
	}
	if ( !function_exists( 'has_multiple_accounts' ) ) {
		function has_multiple_accounts( $email ) { return c2c_has_multiple_accounts( $email ); }
	}

?>