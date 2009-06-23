<?php
/*
Plugin Name: Allow Multiple Accounts
Version: 1.0
Plugin URI: http://coffee2code.com/wp-plugins/allow-multiple-accounts
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Allow multiple user accounts to be created from the same email address.

By default, WordPress only allows a single user account to be associated with a specific email address.  This plugin
removes that restriction.
    
An admin settings page (accessed via Users -> Multiple Accounts or via the Settings link next to the plugin on the
Manage Plugins page) is also provided to allow only certain email addresses the ability to have multiple accounts
(such as if you only want admins to have that ability).  You may also specify a limit to the number of accounts an
email address can have.

The settings page for the plugin also provides a table listing all user accounts, grouped by the email address (see
screenshot).

Compatible with WordPress 2.6+, 2.7+.

=>> Read the accompanying readme.txt file for more information.  Also, visit the plugin's homepage
=>> for more information and the latest updates

Installation:

1. Download the file http://coffee2code.com/wp-plugins/allow-multiple-accounts.zip and unzip it into your 
/wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' admin menu in WordPress
3. Optionally go to the Users -> Multiple Accounts admin settings page (which you can also get to via the Settings
link next to the plugin on the Manage Plugins page) and customize the settings.

*/

/*
Copyright (c) 2008-2009 by Scott Reilly (aka coffee2code)

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

if ( !class_exists('AllowMultipleAccounts') ) :

class AllowMultipleAccounts {
	var $admin_options_name = 'c2c_allow_multiple_accounts';
	var $nonce_field = 'update-allow_multiple_accounts';
	var $show_admin = true;	// Change this to false if you don't want the plugin's admin page shown.
	var $config = array();
	var $options = array(); // Don't use this directly

	var $allow_multiple_accounts = false;  // Used internally; not a setting!
	var $exceeded_limit = false;

	function AllowMultipleAccounts() {
		$this->config = array(
			'allow_for_everyone' => array('input' => 'checkbox', 'default' => true,
					'label' => 'Allow multiple accounts for everyone?',
					'help' => 'If not checked, only the emails listed below can have multiple accounts.'),
			'account_limit' => array('input' => 'text', 'default' => '',
					'label' => 'Account limit',
					'help' => 'The maximum number of accounts that can be associated with a single email address.  Leave blank
					to indicate no limit.'),
			'emails' => array('input' => 'inline_textarea', 'datatype' => 'array', 'default' => '',
					'input_attributes' => 'style="width:98%;" rows="6"',
					'label' => 'Multi-account emails',
					'help' => 'If the checkbox above is unchecked, then only the emails listed here will be allowed to have multiple accounts.  Define one per line.')
		);

		add_action('admin_menu', array(&$this, 'admin_menu'));
		
		add_action('register_post', array(&$this, 'register_post'), 1, 3);
		add_filter('registration_errors', array(&$this, 'registration_errors'), 1);
	}

	function install() {
		$this->options = $this->get_options();
		update_option($this->admin_options_name, $this->options);
	}

	function admin_menu() {
		static $plugin_basename;
		if ( $this->show_admin ) {
			global $wp_version;
			if ( current_user_can('edit_posts') ) {
				$plugin_basename = plugin_basename(__FILE__); 
				if ( version_compare( $wp_version, '2.6.999', '>' ) )
					add_filter( 'plugin_action_links_' . $plugin_basename, array(&$this, 'plugin_action_links') );
				add_users_page('Multiple Accounts', 'Multiple Accounts', 9, $plugin_basename, array(&$this, 'options_page'));
			}
		}
	}

	function plugin_action_links($action_links) {
		static $plugin_basename;
		if ( !$plugin_basename ) $plugin_basename = plugin_basename(__FILE__); 
		$settings_link = '<a href="users.php?page='.$plugin_basename.'">' . __('Settings') . '</a>';
		array_unshift( $action_links, $settings_link );

		return $action_links;
	}

	function get_options() {
		if ( !empty($this->options)) return $this->options;
		// Derive options from the config
		$options = array();
		foreach (array_keys($this->config) as $opt) {
			$options[$opt] = $this->config[$opt]['default'];
		}
        $existing_options = get_option($this->admin_options_name);
        if (!empty($existing_options)) {
            foreach ($existing_options as $key => $value)
                $options[$key] = $value;
        }            
		$this->options = $options;
        return $options;
	}

	function options_page() {
		static $plugin_basename;
		if ( !$plugin_basename ) $plugin_basename = plugin_basename(__FILE__); 
		$options = $this->get_options();
		// See if user has submitted form
		if ( isset($_POST['submitted']) ) {
			check_admin_referer($this->nonce_field);

			foreach (array_keys($options) AS $opt) {
				$options[$opt] = htmlspecialchars(stripslashes($_POST[$opt]));
				$input = $this->config[$opt]['input'];
				if (($input == 'checkbox') && !$options[$opt])
					$options[$opt] = 0;
				if ($this->config[$opt]['datatype'] == 'array') {
					if ($input == 'text')
						$options[$opt] = explode(',', str_replace(array(', ', ' ', ','), ',', $options[$opt]));
					else
						$options[$opt] = array_map('trim', explode("\n", trim($options[$opt])));
				}
				elseif ($this->config[$opt]['datatype'] == 'hash') {
					if ( !empty($options[$opt]) ) {
						$new_values = array();
						foreach (explode("\n", $options[$opt]) AS $line) {
							list($shortcut, $text) = array_map('trim', explode("=>", $line, 2));
							if (!empty($shortcut)) $new_values[str_replace('\\', '', $shortcut)] = str_replace('\\', '', $text);
						}
						$options[$opt] = $new_values;
					}
				}
			}
			// Remember to put all the other options into the array or they'll get lost!
			update_option($this->admin_options_name, $options);

			echo "<div id='message' class='updated fade'><p><strong>" . __('Settings saved') . '</strong></p></div>';
		}

		$action_url = $_SERVER[PHP_SELF] . '?page=' . $plugin_basename;
		$logo = get_option('siteurl') . '/wp-content/plugins/' . basename($_GET['page'], '.php') . '/c2c_minilogo.png';

		echo <<<END
		<div class='wrap'>
			<div class="icon32" style="width:44px;"><img src='$logo' alt='A plugin by coffee2code' /><br /></div>
			<h2>Allow Multiple Accounts Settings</h2>
			<p>Allow multiple user accounts to be created from the same email address.</p>

			<p>By default, WordPress only allows a single user account to be assigned to specific email address.  This plugin
			removes that restriction.  An admin settings page (under Users -> Multiple Accounts) is also provided to allow
			only certain email addresses the ability to have multiple accounts.  You may also specify a limit to the number
			of accounts an email address can have.</p>
			
			<p><a href="#multiaccount_list">View a list of user accounts grouped by email address.</a></p>

			<form name="allow_multiple_accounts" action="$action_url" method="post">	
END;
				wp_nonce_field($this->nonce_field);
		echo '<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform form-table"><tbody>';
				foreach (array_keys($options) as $opt) {
					$input = $this->config[$opt]['input'];
					if ($input == 'none') continue;
					$label = $this->config[$opt]['label'];
					$value = $options[$opt];
					if ($input == 'checkbox') {
						$checked = ($value == 1) ? 'checked=checked ' : '';
						$value = 1;
					} else {
						$checked = '';
					};
					if ($this->config[$opt]['datatype'] == 'array') {
						if (!is_array($value))
							$value = '';
						else {
							if ($input == 'textarea' || $input == 'inline_textarea')
								$value = implode("\n", $value);
							else
								$value = implode(', ', $value);
						}
					} elseif ($this->config[$opt]['datatype'] == 'hash') {
						if (!is_array($value))
							$value = '';
						else {
							$new_value = '';
							foreach ($value AS $shortcut => $replacement) {
								$new_value .= "$shortcut => $replacement\n";
							}
							$value = $new_value;
						}
					}
					echo "<tr valign='top'>";
					if ($input == 'textarea') {
						echo "<td colspan='2'>";
						if ($label) echo "<strong>$label</strong><br />";
						echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
					} else {
						echo "<th scope='row'>$label</th><td>";
						if ($input == "inline_textarea")
							echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
						elseif ($input == 'select') {
							echo "<select name='$opt' id='$opt'>";
							foreach ($this->config[$opt]['options'] as $sopt) {
								$selected = $value == $sopt ? " selected='selected'" : '';
								echo "<option value='$sopt'$selected>$sopt</option>";
							}
							echo "</select>";
						} else
							echo "<input name='$opt' type='$input' id='$opt' value='$value' $checked {$this->config[$opt]['input_attributes']} />";
					}
					if ($this->config[$opt]['help']) {
						echo "<br /><span style='color:#777; font-size:x-small;'>";
						echo $this->config[$opt]['help'];
						echo "</span>";
					}
					echo "</td></tr>";
				}
		echo <<<END
			</tbody></table>
			<input type="hidden" name="submitted" value="1" />
			<div class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes" /></div>
		</form>
			</div>
END;

		$this->list_multiple_accounts();

		echo <<<END
		<style type="text/css">
			#c2c {
				text-align:center;
				color:#888;
				background-color:#ffffef;
				padding:5px 0 0;
				margin-top:12px;
				border-style:solid;
				border-color:#dadada;
				border-width:1px 0;
			}
			#c2c div {
				margin:0 auto;
				padding:5px 40px 0 0;
				width:45%;
				min-height:40px;
				background:url('$logo') no-repeat top right;
			}
			#c2c span {
				display:block;
				font-size:x-small;
			}
		</style>
		<div id='c2c' class='wrap'>
			<div>
			This plugin brought to you by <a href="http://coffee2code.com" title="coffee2code.com">Scott Reilly, aka coffee2code</a>.
			<span><a href="http://coffee2code.com/donate" title="Please consider a donation">Did you find this plugin useful?</a></span>
			</div>
		</div>
END;
	}

	function list_multiple_accounts() {
		global $wpdb;
		$users = $wpdb->get_results("SELECT * FROM $wpdb->users ORDER BY user_login");
		$by_email = array();
		foreach ($users as $user) {	$by_email[$user->user_email][] = $user;	}
		$emails = array_keys($by_email);
		sort($emails);
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
				<h2>E-mails with Multiple User Accounts</h2>
				<table class="widefat">
				<thead>
				<tr class="thead">
					<th>Username</th>
					<th>Name</th>
					<th>E-mail</th>
					<th>Role</th>
					<th class="num">Posts</th>
				</tr>
				</thead>
				<tbody id="users" class="list:user user-list">
END;

		foreach ($emails as $email) {
			$email_users = $by_email[$email];
			$count = count($by_email[$email]);
			$account_text = $count > 1 ? 'accounts' : 'account';
			echo "<tr class='emailrow'><td colspan='6'>$email &#8212; $count $account_text</td></tr>";
			foreach ($by_email[$email] as $euser) {
		        $user_object = new WP_User($euser->ID);
		        $roles = $user_object->roles;
		        $role = array_shift($roles);

		        $style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
		        echo "\n\t" . user_row($user_object, $style, $role);
			}
		}

		echo <<<END
				</tbody>
				</table>
			</div>
END;
	}

	function has_exceeded_limit($email) {
		global $wpdb;
		$has = false;
		$options = $this->get_options();
		if ($options['account_limit']) {
			$limit = (int)$options['account_limit'];
			$count = $wpdb->get_col($wpdb->prepare("SELECT COUNT(*) AS count FROM $wpdb->users WHERE user_email = %s", $email));
			if ($count >= $limit)
				$has = true;
		}
		return $has;
	}

	function register_post($user_login, $user_email, $errors) {
		$options = $this->get_options();
		if ( $errors->get_error_message('email_exists') && 
			($options['allow_for_everyone'] || in_array($user_email, $options['emails'])) ) {
			if ($this->has_exceeded_limit($user_email))
				$this->exceeded_limit = true;
			else
				$this->allow_multiple_accounts = true;
		}
	}

	function registration_errors($errors) {
		if ($this->exceeded_limit)
			$errors->add('exceeded_limit',  __('<strong>ERROR</strong>: Too many accounts are associated with this email, please choose another one.'));
		if ($this->allow_multiple_accounts || $this->exceeded_limit)
			unset($errors->errors['email_exists']);
		return $errors;
	}

} // end AllowMultipleAccounts

endif; // end if !class_exists()
if ( class_exists('AllowMultipleAccounts') ) :
	// Get the ball rolling
	$allow_multiple_accounts = new AllowMultipleAccounts();
	// Actions and filters
	if (isset($allow_multiple_accounts)) {
		register_activation_hook( __FILE__, array(&$allow_multiple_accounts, 'install') );
	}
endif;

?>