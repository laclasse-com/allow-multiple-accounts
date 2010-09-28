=== Allow Multiple Accounts ===
Contributors: coffee2code
Donate link: http://coffee2code.com/donate
Tags: multiple accounts, registration, email, e-mail, signup, account, user, users, restrictions, login, admin, debug, test, coffee2code
Requires at least: 2.8
Tested up to: 3.0.1
Stable tag: 2.0.1
Version: 2.0.1

Allow multiple user accounts to be created from the same email address.


== Description ==

Allow multiple user accounts to be created from the same email address.

By default, WordPress only allows a single user account to be associated with a specific email address.  This plugin removes that restriction.

An admin settings page (accessed via Users -> Multiple Accounts or via the Settings link next to the plugin on the Manage Plugins page) is also provided to allow only certain email addresses the ability to have multiple accounts (such as if you only want admins to have that ability).  You may also specify a limit to the number of accounts an email address can have.

The settings page for the plugin also provides a table listing all user accounts, grouped by the email address (see screenshot).


== Installation ==

1. Unzip `allow-multiple-accounts.zip` inside the `/wp-content/plugins/` directory for your site (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. Go to the Users -> Multiple Accounts admin settings page (which you can also get to via the Settings link next to the plugin on the Manage Plugins page) and configure settings.


== Screenshots ==

1. A screenshot of the plugin's admin settings page.
2. A screenshot of a registration attempt failing due to exceeding the limit on the number of allowed multiple accounts.


== Template Tags ==

The plugin provides one optional template tag for use in your theme templates.

= Functions =

* `<?php c2c_count_multiple_accounts( $email ); ?>`

Returns a count of the number of users associated with the given email.

* `<?php c2c_get_users_by_email( $email ); ?>`

Returns the users associated with the given email.

* `<?php c2c_has_multiple_accounts( $email ); ?>`

Returns a boolean indicating if the given email is associated with more than one user account.

= Arguments =

* `$email` (string)
An email address.


== Frequently Asked Questions ==

= Why would I want to allow multiple accounts to be associated with one email address? =

Maybe your site is one that doesn't mind if users can sign up for multiple accounts from the same email address.  More likely, you as an admin, plugin developer, and/or theme developer would like to be able to create multiple accounts on a blog to test various permissions or just want to test the blog having numerous users and don't want to have to assign unique emails for each account.

= Can I limit who can create multiple accounts for an email? =

Yes.  You can specify a limit on how many accounts can be created per email address.  You can also explicitly list the email addresses which are allowed to create multiple accounts (useful for just allowing admins to have multiple accounts).


== Changelog ==

= 2.0.1 =
* Update plugin framework to C2C_Plugin_016 (fixes WP 2.9.2 compatibility issues)

= 2.0 =
* Fix compatibility with MU/Multi-site
* Fix bug preventing admins from editing the profile of an account
* Re-implementation by extending C2C_Plugin_011, which among other things adds support for:
    * Reset of options to default values
    * Better sanitization of input values
    * Offload of core/basic functionality to generic plugin framework
    * Additional hooks for various stages/places of plugin operation
    * Easier localization support
* Full localization support
* Move count_multiple_accounts() to c2c_count_multiple_accounts()
* Deprecate count_multiple_accounts(), but retain it (for now) for backwards compatibility
* Move get_users_by_email() to c2c_get_users_by_email()
* Deprecate get_users_by_email(), but retain it (for now) for backwards compatibility
* Move has_multiple_accounts() to c2c_has_multiple_accounts()
* Deprecate has_multiple_accounts(), but retain it (for now) for backwards compatibility
* Rename global instance variable from allow_multiple_accounts to c2c_allow_multiple_accounts
* Explicitly ensure $allow_multiple_accounts is global when instantiating plugin object
* Note compatibility with WP 3.0+
* Add 'Text Domain' header tag
* Add omitted word in string
* Minor string variable formatting changes
* Update .pot file
* Minor code reformatting (spacing)
* Add PHPDoc documentation
* Add package info to top of plugin file
* Remove docs from top of plugin file (all that and more are in readme.txt)
* Remove trailing whitespace in header docs
* Add Template Tags and Upgrade Notice sections to readme.txt

= 1.5 =
* Fixed bug causing 'Too many accounts...' error to be incorrectly triggered
* For retrieve password request emails, if the account is one associated with multiple accounts, list those account names in the email for informational purposes
* Added class functions: count_multiple_emails(), get_users_by_email(), has_multiple_emails()
* Exposed new class functions for external use via globally defined functions: count_multiple_emails(), get_users_by_email(), has_multiple_emails()
* Changed invocation of plugin's install function to action hooked in constructor rather than in global space
* Update object's option buffer after saving changed submitted by user
* Finalized full support for localization
* Parameterized textdomain name
* Used _n() instead of deprecated __ngettext()
* Supported swappable arguments in translatable string
* Miscellaneous tweaks to update plugin to my current plugin conventions
* Noted compatibility with WP2.9.1
* Dropped compatibility with versions of WP older than 2.8

= 1.1 =
* Added handling for admin creation of users for WP2.8
* Improved query
* Changed permission check
* More localization-related work
* Removed hardcoded path
* Noted WP2.8 compatibility

= 1.0 =
* Initial release


== Upgrade Notice ==

= 2.0.1 =
Recommended minor bugfix release.  Updated plugin framework to fix WP 2.9.2 compatibility issue.

= 2.0 =
Major update! This release fixes WP 3.0 + MU compatibility. Also includes major re-implementation, bug fixes, localization support, deprecation of all existing template tags (they've been renamed), and more.