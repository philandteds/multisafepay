multisafepay
===================

MultiSafepay eZ Publish payment gateway

This README is terrible it gives no info at all I worked out it was a multisafepay 
extension from the folder name.

Please be aware that Multisafepay require unique transaction IDs we have had issues 
with Rollbacks where transaction IDs and order numbers have been resent and the module
does not fail gracefully when receiving an error from the gateway.

If a rollback is performed you need to bump the Auto-increment ID of ezorder
and multisafepay_transactions as it will try and re-use previously used IDs
which causes a lot of issues.
