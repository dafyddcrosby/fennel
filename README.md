Holy crap, what is this?!
=========================
Fennel was created to fill a rather specific niche - something with the simplicity and portability of TiddlyWiki, a database backend (something really tidy), and in a language that most webhosts provide (or can even be run from a thumb drive).

So with the magic of SQLite, PHP, and a freezing Calgary weekend, Fennel was created.

Requirements
============
* PHP 5 or higher (with SQLite enabled!)
* Approximately one minute of your time to set up

Installation
============
Copy fennel.php to your web root directory. Then, access the file from your browser. That should be it. For realsies.

If you have issues upon install, the error messages should help you out. Basically, upon the first running of the script it will create the database and config file automatically. They'll be named after fennel.php (or whatever you rename the file to), so it shouldn't be too hard a search.

Alternatively, if you've got the fancy new PHP 5.4, you should be able to just run the script with the new internal HTTP server by doing this:

        php -S localhost:8000 fennel.php

(NB - I haven't tested this yet, but unless I hear otherwise, this should do it.)

The Fennel Promise
==================
Don't you hate it when a project strays from its intended purpose and goals? I hate it, too! That's why I've outlined these goals to guide Fennel:

* No external libraries, just PHP standard library, s'il vous plait.
* The install should never be more difficult than copying a lone PHP file.
* Keep the project under 1000 lines of source (excludes comments and license).
* Keep the thing readable and well-commented, so that even non-PHP programmers can read along.

Defects
=======
None known, but that doesn't mean there isn't a few hiding in there :-P

FAQ
===
Wait, where's the user login?
-----------------------------
There's currently none so far. What I've done for myself is use htaccess for my remote Fennel installations to secure them. As Fennel was designed to be a simple personal wiki, adding this would need to be done as unobtrusively as possible.

So the database tables are stable?
--------------------------------
They're going to be the way they are for the foreseeable future. Patches messing with the database tables will not be accepted unless there's a SQL update script along with it.

What kind of markup does Fennel use?
------------------------------------
The plan is for the markup to be identical to TiddlyWiki, since it's pretty intuitive. So far, only basic formatting is available.

ZOMG! I want to add plugin XYZ!
-------------------------------
In order to keep Fennel simple, there's no plan on adding plugins. If you want something that will handle 2000 users and make a pot of coffee, look into the more fully-featured and extensible wiki software like Mediawiki or MoinMoin - they're pretty great, too.

TODO
====
* Finish the markup function
* Make the CSS pretty
