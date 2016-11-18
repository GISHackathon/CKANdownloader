# CKAN Downloader

Download CSV data from CKAN API servers

Example of available data servers

- City Prague http://opendata.praha.eu
- City Plzen https://opendata.plzen.eu

Output

- all CSV data from server
- MySQL insert script based on CSV data

```
require_once './script/CKANdownloader.php';

/*
 * examples
  $url = "https://opendata.plzen.eu";
  $url = "http://opendata.praha.eu";
 */

$url = "http://opendata.praha.eu";

$downloader = new CKANdownloader($url);

foreach ($downloader->getPackages() as $package) {
    foreach ($package["resources"] as $resource) {
        if ($resource["format"] == "CSV") {
            $downloader->downloadCSVResource($resource);
        }
    }
}