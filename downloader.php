<?php

require_once './script/CKANdownloader.php';

/*
 * examples
  $url = "https://opendata.plzen.eu";
  $url = "http://opendata.praha.eu";
 */

$url = "https://opendata.plzen.eu";

$downloader = new CKANdownloader($url);

foreach ($downloader->getPackages() as $package) {
    foreach ($package["resources"] as $resource) {
        if ($resource["format"] == "CSV") {
            $downloader->downloadCSVResource($resource);
        }
    }
}