== Changelog ==

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