Descarga SAT
================
Extensión para descargar de CFDIs (XMLs) desde el SAT

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist inquid/sat-descarga "*"
```

or add

```
"inquid/sat-descarga": "*"
```

to the require section of your `composer.json` file.


Usage
-----
```
<?php

include ('vendor/autoload.php');

use inquid\SatDownload;

$downloadHandler = new SatDownload\DownloadHandler('artifacts/cer.cer', 'artifacts/key.key', 'password');
$downloadHandler->login();
$result = $downloadHandler->searchForIncomingCfdis(2021, 11);

print_r($result);

```
