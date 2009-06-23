=== Allow Multiple Accounts ===
Contributors: Scott Reilly
Donate link: http://coffee2code.com/donate
Tags: multiple accounts, registration, email, signup, account, user, users, restrictions, login, admin, debug, test, coffee2code
Requires at least: 2.6
Tested up to: 2.8.1
Stable tag: 1.1
Version: 1.1

Allow multiple user accounts to be created from the same email address.

== Description ==

Allow multiple user accounts to be created from the same email address.

By default, WordPress only allows a single user account to be associated with a specific email address.  This plugin removes that restriction.

An admin settings page (accessed via Users -> Multiple Accounts or via the Settings link next to the plugin on the Manage Plugins page) is also provided to allow only certain email addresses the ability to have multiple accounts (such as if you only want admins to have that ability).  You may also specify a limit to the number of accounts an email address can have.

The settings page for the plugin also provides a table listing all user accounts, grouped by the email address (see screenshot).


== Installation ==

1. Unzip `allow-multiple-accounts-.zip` inside the `/wp-content/plugins/` directory for your site (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. Optionally go to the Users -> Multiple Accounts admin settings page (which you can also get to via the Settings link next to the plugin on the Manage Plugins page) and configure settings.

== Screenshots ==

1. A screenshot of the plugin's admin settings page.
2. A screenshot of a registration attempt failing due to exceeding the limit on the number of allowed multiple accounts.

== Frequently Asked Questions ==

= Why would I want to allow multiple accounts to be associated with one email address? =

Maybe your site is one that doesn't mind if users can sign up for multiple accounts from the same email address.  More likely, you as an admin, plugin developer, and/or theme developer would like to be able to create multiple accounts on a blog to test various permissions or just want to test the blog having numerous users and don't want to have to assign unique emails for each account.

= Can I limit who can create multiple accounts for an email? =

Yes.  You can specify a limit on how many accounts can be created for an email.  You can also explicitly list the email addresses which are allowed to create multiple accounts (useful for just allowing admins to have multiple accounts).

== Changelog ==

= 1.1 =
* Added handling for admin creation of users for WP2.8
* Improved query
* Changed permission check
* More localization-related work
* Removed hardcoded path
* Noted WP2.8 compatibility

= 1.0 =
* Initial release