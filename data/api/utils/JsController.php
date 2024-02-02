<?php
namespace Api\Utils;

use Core\Extender\ControllerSender;

class JsController extends ControllerSender
{


    public function initialize() {

    }

    public function lang()
    {
        $jsFilePath = $this->request->get('js');

        $javascriptFilePath = DIR_PUBLIC.'js/'.$jsFilePath.'.js';
        $languageFilePath = DIR_PUBLIC.'js_lang/'.$jsFilePath.'.'.$this->user->getLang().'.json';


        if (!is_file($javascriptFilePath)) {
            die('no such file');
        }

        $jsFile = file_get_contents($javascriptFilePath);

        $langStrings = [];

        if (is_file($languageFilePath)) {
            $langStrings = json_decode(file_get_contents($languageFilePath), true);
        }



        foreach ($langStrings as $langKey => $langValue) {
            $jsFile = preg_replace("/\'\{".$langKey."\}\'/miu", "'".$langValue."'", $jsFile);
        }

        $this->response->setRawHeader('HTTP/1.1 200 OK');
        $this->response->setHeader('Content-Type', 'application/javascript');
        $this->response->setContent($jsFile);
        $this->response->send();
        return;
    }

    public function package() {
        $packageName = $this->request->get('package');

        $resources = $this->db->query(
            '
                SELECT
                    r.resource_dir,
                    r.resource_name,
                    r.resource_minified   
                FROM
                    resources AS r 
                WHERE
                    r.package_id = (
                        SELECT 
                            rp.package_id 
                        FROM 
                            resources_packages AS rp 
                        WHERE 
                            rp.package_name = :name AND 
                            rp.package_compress_group = 1
                    )
                ORDER BY r.resource_order
            ',
            [
                'name' => $packageName
            ]
        );

        $resourcesHtml = '';

        while($row = $resources->fetch()) {
            $resourcesHtml .= ' ; '.$this->translateFile($row['resource_dir'], $row['resource_name']);//file_get_contents(DIR_PUBLIC.$row['resource_dir'].$row['resource_name']);
        }

        $this->response->setRawHeader('HTTP/1.1 200 OK');
        $this->response->setHeader('Content-Type', 'application/javascript');
        $this->response->setContent($resourcesHtml);
        $this->response->send();
        return;
    }


    private function translateFile($filePath, $fileName) {
        $javascriptFilePath = DIR_PUBLIC.$filePath.$fileName;
        $languageFilePath = DIR_PUBLIC.'js_lang/'.str_replace('js/', '', $filePath).str_replace('.js', '.'.$this->user->getLang().'.json', $fileName);

        $langStrings = [];

        if (is_file($languageFilePath)) {
            $langStrings = json_decode(file_get_contents($languageFilePath), true);
        }

        $jsFile = file_get_contents($javascriptFilePath);


        foreach ($langStrings as $langKey => $langValue) {
            $jsFile = preg_replace("/\'\{".$langKey."\}\'/miu", "'".$langValue."'", $jsFile);
        }

        return $jsFile;
    }
}