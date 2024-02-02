<?php
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vue compiler</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your invoices">
    <meta name="author" content="Phalcon Team">
    <link href="/design/v2/apps/settings.css" rel="stylesheet" type="text/css" />
    <link href="/design/v2/_compilation.css" rel="stylesheet" type="text/css" />
    <link href="/design/v2/pm/_compilation.css" rel="stylesheet" type="text/css" />
    <link href="/design/v2/apps/settings.css" rel="stylesheet" type="text/css" />
</head>
<body>

        <div id="vueAppSelectors">      
            <select id="vueAppSelector">
                <option value="-1">Выберите Vue приложение</option>
            </select>
        </div>

        <div id="vueAppContainer"></div>
        
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="/js_dev/vue_apps.js"></script>
';


    $manifest = json_decode(file_get_contents(__DIR__."/../config/manifest.json"), true);

    // print_r($manifest);

    foreach($manifest as $key=>$value) {
        $assetFile = $value['file'];

        if (preg_match('/\.js$/', $assetFile)) {
            echo '<script type="module" src="/min/'.$assetFile.'"></script>';
        }
    }


echo '
    </body>
    </html>
';