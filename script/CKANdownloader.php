<?php

require_once __DIR__ . '/parsecsv/parsecsv.lib.php';

function downloadFile($fileURL) {
    if (strpos($fileURL, "https") === 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fileURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    } else {
        return file_get_contents($fileURL);
    }
}

class CKANdownloader {

    var $url;
    var $name;
    var $time;

    function __construct($url, $name = "") {
        $this->time = time();

        $this->url = $url;
        if ($name) {
            $this->name = $name;
        } else {
            $this->name = preg_replace('/(https|http):\/\/(.*)/', '${2}', $this->url);
        }
        echo("Downloading: {$this->name}");

        @mkdir(__DIR__ . "/../data/{$this->name}");
        @mkdir(__DIR__ . "/../data/{$this->name}/sql");
        @mkdir(__DIR__ . "/../data/{$this->name}/csv");

        $this->my_sql("", false);
    }

    function getPackageList() {
        $filename = "{$this->url}/api/3/action/package_list";
        $json = downloadFile($filename);
        $obj = json_decode($json, TRUE);

        if ($obj["success"] == true) {
            return $obj["result"];
        } else {
            return [];
        }
    }

    function getPackageShow($packageID) {
        $filename = "{$this->url}/api/3/action/package_show?id={$packageID}";
        $json = downloadFile($filename);
        $obj = json_decode($json, TRUE);

        if ($obj["success"] == true) {
            return $obj["result"];
        } else {
            return [];
        }
    }

    function getPackages() {
        $packages = $this->getPackageList();
        $temp = [];

        foreach ($packages as $packageID) {
            $packageDetail = $this->getPackageShow($packageID);
            $temp[] = $packageDetail;
        }
        return $temp;
    }

    function my_sql($sql, $append = TRUE) {
        $filename = __DIR__ . "/../data/{$this->name}/sql/{$this->time}.sql";
        if ($append == FALSE) {
            file_put_contents($filename, "");
        }
        file_put_contents($filename, $sql, FILE_APPEND);
    }

    function downloadCSVResource($resource) {

        $csv_url = $resource["url"];
        $fileSave = __DIR__ . "/../data/{$this->name}/csv/{$resource["name"]}.csv";

        $data = downloadFile($csv_url);
        if (mb_detect_encoding($data, 'UTF-8', true)) {
            
        } else {
            $data = iconv("CP1250", "UTF-8", $data);
        }

        file_put_contents($fileSave, $data);

        $csv = new parseCSV();
        $csv->auto($fileSave);

        $row = 0;
        $names = [];
        $namesOrigin = [];

        foreach ($csv->data as $data) {
            if ($row == 0) {
                foreach (array_keys($data) as $name) {
                    $namesOrigin[] = $name;
                    $name = preg_replace("/(:.*)/", "", $name);
                    $name = str_replace("'", "\'", $name);
                    $name = substr($name, 0, 63);
                    $name = trim($name);
                    $names[] = $name;
                }

                $tableName = "{$resource["name"]}";
                $tableName = substr($tableName, 0, 63);
                $tableName = trim($tableName);
                $this->my_sql("DROP TABLE  IF EXISTS `{$tableName}` ;");
                $sqlCols = [];
                foreach ($names as $name) {
                    $sqlCols[] = "`{$name}` TEXT NOT NULL ";
                }
                $this->my_sql("CREATE TABLE `{$tableName}` ( " . implode(",", $sqlCols) . " ) ;");
                $rows = $names;
            }

            $values = [];
            foreach ($namesOrigin as $name) {
                $value = $data[$name];
                $value = str_replace("'", "\'", $value);
                $value = trim($value);
                $values[] = $value;
            }


            $sql = "INSERT INTO `{$tableName}` (`" . implode("`,`", $names) . "`) VALUES ('" . implode("','", $values) . "');";
            if (count($names) == count($values)) {
                $this->my_sql($sql);
            } else {
                $this->log("err cols.count != names.count: $sql");
            }

            $row++;
        }
        echo("table <b>$tableName</b>, items <b>$row</b><br>");
    }

    function log($text) {
        $filename = __DIR__ . "/../data/{$this->name}/log_{$this->time}.txt";

        file_put_contents($filename, $text, FILE_APPEND);
    }

}
