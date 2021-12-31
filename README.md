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

Se requiere que se configure en tu archivo de configuración web :
```
        'descarga_sat' => [
            'class' => 'inquid\yii_sat\DownloadHandler',
            'cerFile' => '...path to cer...',
            'keyFile' => '...path to key...',
            'password' => '...password...',
            'downloadPath' => '...xml path...'
        ],
```

```php
        $login = Yii::$app->descarga_sat->login();
        if ($login) {
            $recibidos = Yii::$app->descarga_sat->buscarRecibidos('2018', '12');
            $descargar = Yii::$app->descarga_sat->descargarXml($recibidos);
        }
```
