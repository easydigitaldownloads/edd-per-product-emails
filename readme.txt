=== Easy Digital Downloads - Per Product Emails ===

Plugin URI: http://sumobi.com/shop/easy-digital-downloads-per-product-emails/
Author: Andrew Munro, Sumobi
Author URI: http://sumobi.com/

Custom purchase confirmation emails for your products

== Changelog ==
= 1.1.0 =
* Fix: Apostrophes not displaying correctly in email subject
* New: edd_ppe_capability_type filter to specify which capability can manage the emails
* New: {license_key} email tag

== Changelog ==
= 1.0.9 =
* Fix: XSS vulnerability in query args

= 1.0.8 =
* Fix: Special characters in subject line when using the {download_name} email tag were being converted to HTML

= 1.0.7 =
* Fix: Plugin became deactivated when EDD was updated

= 1.0.6 =
* Fix: Added backwards compatibility for olders EDD versions that aren't using the new EDD email class

= 1.0.5 =
* Fix: email tags not showing properly in custom emails
* New: edd_ppe_email_heading filter for showing the download's name as the email heading, similar to the default EDD purchase receipt. Example add_filter( 'edd_ppe_email_heading', '__return_true' );
* Tweak: Optimized email function code

= 1.0.4 =
* Tweak: Now uses EDD's email class introduced in EDD v2.1 for custom emails and test emails
* Tweak: Better activation class
* Tweak: Better handling of language files

= 1.0.3 =
* New: Custom emails are now sent when resending the purchase receipt from the Payment History

= 1.0.2 =
* Fix: Bug with license key activation.

= 1.0.1 =
* New: Prevent the standard purchase receipt from being sent to the customer. The customer will still receive the standard purchase receipt if there are downloads purchased that do not have custom emails configured.
* Fix: PHP 5.2 Compatibility
* Tweak: Different list creation messages for guest/logged in users

= 1.0 =
* Initial release
