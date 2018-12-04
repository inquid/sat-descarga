Yii Descarga SAT
================
Extensión para descarga de XML desde le SAT

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist inquid/yii-sat-descarga "*"
```

or add

```
"inquid/yii-sat-descarga": "*"
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
            'password' => '...password...'
        ],
```

```php
<?=         $login = Yii::$app->descarga_sat->login();
            if ($login) {
                $recibidos = Yii::$app->descarga_sat->buscarRecibidos('2018', '12');
                $descargar = Yii::$app->descarga_sat->descargarXml($recibidos);
            } ?>
```
