<?php
namespace Api\Utils;

use Core\Extender\ApiAccessAjax;
use Core\Handler\MinifyHtml;

class TemplatesController extends ApiAccessAjax
{
    const CACHE_PARTIAL_DIR = '../cache/templates/frontend/partial/';
    const TMPL_PARTIAL_DIR  = DIR_VIEWS.'partials/';

    public function package($package) {
        $templates = json_decode($this->request->getPost('templates'), true);

        $templatesList = [];

        foreach($templates as $i => $v) {
            $templatesList[] = ['name' => $i, 'html' => $this->getTemplate($i)];
        }


        $this->sendResponseAjax($templatesList);
    }


    private function getTemplate($template) {
        $partialPath = self::TMPL_PARTIAL_DIR.$template.'.hbs';
        if (!is_file($partialPath)) {
            $this->sendResponseAjax('template: '.$partialPath.' - not exists', 0);
        }

        $md5Name = md5($template);

        $cachedFileName = $md5Name.'.min';
        $cachedFilePath = self::CACHE_PARTIAL_DIR.$cachedFileName;


        if (!is_file($cachedFilePath)) {
            $minified = MinifyHtml::minify(file_get_contents($partialPath));
            file_put_contents($cachedFilePath, $minified);
        }
        else {
            $minified = file_get_contents($cachedFilePath);
        }

        return $minified;

    }
}