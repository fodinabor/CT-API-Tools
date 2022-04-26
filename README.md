# About this repo

I have pulled a fork to build it for my needs. There is not much in it yet, but it will come.

* [x] Namespaces for Integration into other systems (Contao)
* [x] Support of API V1 (ajax) as well as API V2 (REST)
    * Breaking change: It is a bit a problem that 'domain' already has the query in it.
      So I moved this into CTV1_sendRequest
    * provide two reequest Methods CTV1_SendRequest and CTV2_SendRequest
    * provide pagination CTV2_sendRequestWithPagination   
* [x] Playground to play around with the API
* [x] More documentation
* [x] Improve Handling of the credentials

# prerequisites

a local php executable

# usage

1. clone this repo
2. cd to `ctapishowcase`
3. create `CT-credentialstore.php` from `CT-credentialstore.php.template`
4. create the intendended outputfolder if you specify it in CT-credentialstore.php
5. run `php ctcli.php {script}`, e.g. `php ctcli.php v1_churchauth--all`
6. see the results in ouptufolder

# CT-API-Tools

PHP scripts, that make imports via the ChurchTools API pretty easy.

## ct_apitools--helper.inc.php

this holds the generic routines to interact with the CT api

## ct_apitools.inc.php

You need to do something special with the API of CT on your own?
Have a look, maybe I've already discovered, how some part of the API works. You might find the fitting API call in this file!

### ctapishowcase

This folder holds the experiments done by @bwl21

see [readme](ctapishowcase/readme.md) for details

### more examples

The following examples were take from the fodimator upstream.

## ctimportsongs.php

**untested**

Provides functionality to import a bunch of [OpenLyrics](https://openlyrics.org) xml song files automagically to ChurchTools.
All you need to do is:
- add your login data in the `ctimportsongs.php` file (search for `ToDo`
- have php installed (command line support is *highly* recommended
- copy your OpenLyrics files to the `songs` folder
- in the terminal (CMD on Windows) run: `php ctimportsongs.php`
- now all songs from the `songs` folder should be automagically uploaded to your ChurchTools instance

## ctinvtagstogroup.php

**untested**

A utility to remove tags from persons and instead add them to a group... only use if you know what you're doing.
The tag and group ids have to be replaced in the file before using.

## calendarmerger.php

**untested**

You want to subscribe two ical sources, but only have limited calenders booked @CT?
With the calendarmerger you can just host this file on a webserver, add your ical urls, you would like to subscribe to.
Now you can add the URL to the webserver you are hosting this on (e.g. `https://my-super-cool-domain.tld/calendarmerger.php`) 
to ChurchTools as an iCal source and voilá: CT shows both calendars as one.

# More information

* https://intern.church.tools/?q=churchwiki#WikiView/filterWikicategory_id:0/doc:API/follow_redirect:true/
* https://api.church.tools/


