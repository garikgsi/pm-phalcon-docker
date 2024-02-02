<?php
namespace Core\Extender;

use Phalcon\Mvc\View;

class ViewScopeFix extends View
{
    public function partial($partialPath, $params = [])
    {
        return (function() use($partialPath, $params) {
            return parent::partial($partialPath, $params);
        })();
    }

    public function jsonBase64Encode($dataArray = []) {
        return base64_encode(json_encode($dataArray));
    }
}