# CT-API-Tools
PHP scripts, that make imports via the ChurchTools API or similar very easy.

## helper.php
You need to do something special with the API of CT on your own?
Have a look, maybe I've already discovered, how some part of the API works. You might find the fitting API call in this file!

## ctimportsongs.php
Provides functionality to import a bunch of [OpenLyrics](https://openlyrics.org) xml song files automagically to ChurchTools.
All you need to do is:
- add your login data in the `ctimportsongs.php` file (search for `ToDo`
- have php installed (command line support is *highly* recommended
- copy your OpenLyrics files to the `songs` folder
- in the terminal (CMD on Windows) run: `php ctimportsongs.php`
- now all songs from the `songs` folder should be automagically uploaded to your ChurchTools instance

## ctinvtagstogroup.php
A utility to remove tags from persons and instead add them to a group... only use if you know what you're doing.
The tag and group ids have to be replaced in the file before using.

## calendarmerger.php
You want to subscribe two ical sources, but only have limited calenders booked @CT?
With the calendarmerger you can just host this file on a webserver, add your ical urls, you would like to subscribe to.
Now you can add the URL to the webserver you are hosting this on (e.g. `https://my-super-cool-domain.tld/calendarmerger.php`) to ChurchTools as an iCal source and voilá: CT shows both calendars as one.