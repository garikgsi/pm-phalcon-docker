
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <script> </script>
    <title>All Icons</title>
    <style>
        html, body {
            padding: 0;
            margin:  0;
            height: 100%;
            box-sizing: border-box;
        }

        html {
            overflow: auto;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            background-color: #eeeeee;
        }

        body {
            padding-top: 40px;
        }

        .notify {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: block;
            padding: 12px;
            font-size: 13px;
            text-align: center;
            min-height: 40px;
            line-height: 1.2;
            font-weight: bold;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);
            color: #ffffff;
            background-color: rgb(76, 174, 79);
            transition: 0.2s;
            box-sizing: border-box;
        }

        .notify.hide { opacity: 0; visibility: hidden; }

        .list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 100px), 1fr));
            gap: 8px;
            padding: 8px;
        }

        .icon {
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.25);
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
            background-color: rgba(255, 255, 255, 0.25);
            box-sizing: border-box;
            padding: 8px;
            height: 100px;
            cursor: pointer;
        }

        .icon:hover,
        .icon:active { border-color: rgba(0, 0, 0, 0.4); }

        .icon:active { opacity: 0.7; }

        .iconWrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width:  32px;
            height: 32px;
            margin: auto;
        }

        .iconWrap img {
            display: block;
            width:  auto;
            height: auto;
            max-width:  100%;
            max-height: 100%;
            object-fit: contain;
        }

        .iconTitle {
            display: block;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>

<div id="notificationLine" class="notify hide">Скопировано в буфер обмена</div>

<div class="list">

<?php

$dir    = __DIR__;
$icons = scandir($dir, 1);

for($a = 0, $lenA = sizeof($icons); $a < $lenA; $a++) {
    $fileName = $icons[$a];

    if (!is_file($dir.'/'.$fileName) || $fileName == 'index.php') {
        continue;
    }

?>
    <div class="icon clipboardIcon" data-clipboard-text="/design/icons/<?=$fileName?>">
        <div class="iconWrap">
            <img alt="<?=$fileName?>" src="<?=$fileName?>" />
        </div>
        <div class="iconTitle"><?=$fileName?></div>
    </div>

<?php
}
?>

</div>
<script src="/dev/elize/js/clipboard.js"></script>
<script>
  const notificationLine = document.getElementById('notificationLine');
  const clipboard = new ClipboardJS('.clipboardIcon');
  let notificationTimeout;

  clipboard.on('success', function(e) {
    notificationLine.classList.remove('hide');

    clearTimeout(notificationTimeout);
    notificationTimeout = setTimeout(() => notificationLine.classList.add('hide'), 2000);

    e.clearSelection();
  });
</script>
</body>
</html>
