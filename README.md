premium-api
===========

Client API for Sales.lv **[Premium](http://www.sales.lv/lv/risinajumi/premium/)** service. In its essence **Premium** is a platform for
implementing services based on mobile-originated (incoming from users) SMS message processing. In practice there are a number of applications
built upon this platform, for example, user message processing, SMS micropayment handling, lotteries, etc. (Lotteries are a special case
as there is a lot of lottery-specific functionality implemented in Premium that goes above and beyond SMS processing).

This is a simple HTTP API where data is requested or manipulated with HTTP requests. We are also providing client libraries
(currently only in PHP) to make work easier.

There is a specification provided in the [wiki here](https://github.com/Sales-LV/premium-api/wiki) about making the API calls yourself,
as well as examples of using our libraries

A quick start guide
------------
- Sign up for the [Premium service with Sales.lv](http://www.sales.lv/lv/risinajumi/premium/). Once you have done that, you will be provided with an API key and a campaign code to use for API requests.
- Take a look at the [API documentation](https://github.com/Sales-LV/premium-api/wiki) and the client libraries.

PHP client library
------------
PHP client library is located in `lib/php/premium-api.php`. An usage example is provided in `lib/php/example.php`.

Requirements:
* [PHP 5.2 or newer](http://www.php.net/)
* One of these:
    * [pecl_http](http://pecl.php.net/package/pecl_http) extension is recommended but not mandatory.
    * enabled [cURL library](http://www.php.net/manual/en/book.curl.php).
    * [allow_url_fopen](http://php.net/manual/en/filesystem.configuration.php) set to true.

Library usage is [described in the wiki](https://github.com/Sales-LV/premium-api/wiki/PHP-API-library).

Feedback, support & questions
------------
Please write to support@sales.lv with any feedback, questions or suggestions that might arise.