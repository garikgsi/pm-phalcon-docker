<?php
namespace Core\Extender;


use Core\Handler\MinifyHtml;

class MustacheLoaderWithMinifier extends \Mustache_Loader_FilesystemLoader {

    protected function loadFile($name)
    {
        $fileName = $this->getFileName($name);

        if (!file_exists($fileName)) {
            throw new \Mustache_Exception_UnknownTemplateException($name);
        }

        return (new MinifyHtml(file_get_contents($fileName)))->process();
    }
}