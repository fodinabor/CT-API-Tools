# This folder supports to generate a showcase

## usasge

```
php showcase.php {showcase-glob}
```

where {showcase} matches a filename in folder `src`

If now showcase is specified as an argumnt, all showcases will be processed.

Please investigate the showcases to see the pattern applied:

* a showcase shall provide an array named `$report`. This array is stored
  in the `{showcase}.response.json`
* a showcase can provide any data of interest in this array.
* we advise to store the result in `$report['response]`
* we advise to place `responses` in a GIT of you own such that you can
  track changes.

every showcase produces the following file

* {showcase}.response.json



