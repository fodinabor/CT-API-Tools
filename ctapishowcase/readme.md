# This folder supports to generate a showcase

## Prerequisites

1. create a credential store

```bash

cd ctapshowcase
cp CT-credentialstroe.php.template CT-credentialstore.php
```

now edit `CT-credentialstore.php`. you see the placeholder which you should fill.

 

## usasge

```
php ctcli.php {showcase-glob}
```

where {showcase} matches a filename in folder `src`

If no showcase is specified as an argumnt, all showcases will be processed.

Please investigate the showcases to see the pattern applied:

* a showcase shall provide an array named `$report`. This array is stored
  in the `{showcase}.response.json`
* a showcase can provide any data of interest in this array.
* we advise to store the result in `$report['response]`
* we advise to place `responses` in a GIT of you own such that you can
  track changes.

every showcase produces at least the following file

* {showcase}.response.json

## v1-churchauth--all.php

this generates repoprts about access rights in your instance. It furtheron creates plantuml and a graphml 
files for your groups. Note that you need tools to visualize these files 

* [plantuml](https://plantuml.com), 
* [yed](https://yworks.com/products/yed)






