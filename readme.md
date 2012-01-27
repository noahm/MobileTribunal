Tribunal Mobile
===============

An unofficial mobile webapp and proxy to the League of Legends Tribunal

Features
--------

* Web interface designed for both small phone screens and tablets. Works just fine on the desktop too!
* Tested on Mobile Safari, Android, Mobile IE (WP7), and more!
* Chat filters let you easily follow conversations and quickly check the reported player's language
* More intuitive information layout than the official Tribunal
* The easiest way to earn IP while you poop!

Running MobTrib for yourself
----------------------------

If you don't trust using the app on our host, that's just fine. You can easily run it yourself on any
web server equiped with PHP 5, but we worked with Apache. Just check out this repository into any path within
your web root and you should be good to go. Web host SSL is enabled by default, but this and some
other configuration can be changed in the support/config.php file.

Depends on:

* PHP 5 (for [DomDocument](http://us.php.net/manual/en/class.domdocument.php))
* libcurl + [cURL](http://us.php.net/manual/en/book.curl.php) for PHP
* [OpenSSL](http://us.php.net/manual/en/book.openssl.php) for PHP (NOTE: OpenSSL is required for the proxy to send login information to Riot Games servers. The http host does not need SSL)

Reporting Issues
----------------

We use the issue tracker here on github and through the reddit community
of [/r/mobiletribunal](http://www.reddit.com/r/mobiletribunal). Please report
bugs in either of the two places.

As this product depends on Riot's servers to deliver the game information,
in some cases there is only so much we can do. With that said, we want to make
this a high quality product and will try to continue improving it over time.

License
-------

League of Legends and the Tribunal are products of [Riot Games](http://riotgames.com/)
and are unaffiliated with this app. You may use this code free of charge, but at your own risk.

This code is released under some open-source license that reserves some rights to us but
lets you take it and build awesome things as long as you give us credit.

Credits
-------

* kaysond ([kayson](http://www.reddit.com/user/kayson))
	* Proxy
	* Founder
* noahm ([psoplayer](http://www.reddit.com/user/psoplayer))
	* User Interface
	* Tribunal Parsing
