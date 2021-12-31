<?php

include ('vendor/autoload.php');

use inquid\SatDownload;

$downloadHandler = new SatDownload\DownloadHandler('artifacts/cer.cer', 'artifacts/key.key', 'Artisalie15!');
$downloadHandler->login();
$result = $downloadHandler->searchForIncomingCfdis(2021, 11);

print_r($result);
