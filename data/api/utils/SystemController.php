<?php
namespace Api\Utils;


use Core\Extender\ControllerSender;
use Core\Handler\SiteUtils;
use Phalcon\Filter;

class SystemController extends ControllerSender
{
    public function initialize()
    {
        ;
    }

    public function configs() {
        $cacheDir = DIR_ROOT.'/cache/sites/';

        $Filter = new Filter();

        $siteId = (int)$this->request->get('site');

        //$this->generateDomainsConfig();

        $this->generateAndroidManifestsJSON($siteId); // Создаем манифест
        $this->generateMSBrowserConfigXML($siteId);   // Создаем конфиги для интернет эксплореров
        $this->generateMetaHTML($siteId);             // Мета данные для занесения в хтмл
        $this->generateMetaOGTC($siteId);             // Опен граф и твиттер кардс

        $this->generateRoutes($siteId);

        if ($siteId > 0) {
            (new SiteUtils($siteId))->performAll()->generateDomainsFile();
        }
        else {
            $sites = $this->db->query('SELECT * FROM sites AS s');

            while($row = $sites->fetch()) {
                $SiteUtils = new SiteUtils($row['site_id'], $row);

                $SiteUtils->performAll();
            }

            $SiteUtils->generateDomainsFile();
        }

/*
        while($row = $sites->fetch()) {
            $this->savePHPArrayToFile(
                $cacheDir.strtolower($row['site_name']).'.php',
                $this->generateSiteConfig($row)
            );
        }

        $meta = $this->db->query(
            '
                SELECT
                    l.lang_name,
                    s.site_name,
                    sm.meta_title,
                    sm.meta_keywords,
                    sm.meta_description
                FROM
                    languages AS l,
                    sites AS s,
                    sites_meta AS sm 
                WHERE
                    s.site_id = sm.site_id AND 
                    l.lang_id = sm.lang_id
            '.($siteId ? ' AND s.site_id='.$siteId : '')
        );

        while($row = $meta->fetch()) {
            $this->savePHPArrayToFile($cacheDir.strtolower($row['site_name']).'_meta_'.$row['lang_name'].'.php', [
                'meta' => [
                    'title' => $row['meta_title'],
                    'keywords'    => $row['meta_keywords'],
                    'description' => $row['meta_description']
                ]
            ]);
        }*/

        $this->sendResponseAjax('generated');
    }


    private function generateSiteConfig($row) {
        $siteId   = $row['site_id'];
        $siteName = $row['site_name'];
        $defaultRouter = (int)$row['site_routing_default'];

        $minEnabled = (int)$row['site_min_enabled'];
        $minVerCss  = (int)$row['site_min_ver_css'];
        $minVerJs   = (int)$row['site_min_ver_js'];
        $minVerTmp  = (int)$row['site_min_ver_templates'];


        $siteName   = strtolower($siteName); // на всякий случай приводим к нижнему регистру
        $ucSiteName = ucfirst($siteName);    // делаем первую букву заглавной


        return [
            'id'     => (int)$siteId,
            'dir'    => '../site/'.$siteName.'/',
            'name'   => $ucSiteName,
            'default_router' => $defaultRouter,
            'min' => [
                'enabled' => $minEnabled,
                'ver_css' => $minVerCss,
                'ver_js'  => $minVerJs,
                'ver_tmp' => $minVerTmp
            ],
            'module' => [
                'className' => 'Site\\'.$ucSiteName.'\\Module',
                'path'      => '../site/'.$siteName.'/Module.php',
            ],
            'trackers' => [

            ]
        ];
    }

    private function generateDomainsConfig() {
        $cacheDir = DIR_ROOT.'/cache/sites/';

        $domains = $this->db->query(
            '
                SELECT 
                    s.site_name,
                    sd.domain_name,
                    l.lang_name,
                    l.lang_id                           
                FROM 
                    sites AS s, 
                    sites_domains AS sd,
                    languages AS l
                WHERE
                    s.site_id=sd.site_id AND 
                    l.lang_id = sd.lang_id
            '
        );

        $domainsList = [];

        while($row = $domains->fetch()) {
            $domainsList[$row['domain_name']] = [
                'name' => $row['site_name'],
                'lang' => $row['lang_name'],
                'lang_id' => $row['lang_id']
            ];
        }

        $this->savePHPArrayToFile($cacheDir.'domains.php', $domainsList);
    }

    private function generateMetaHTML($siteId = 0) {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_html_icons'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        $icoSizes = ['16x16' => 1, '32x32' => 1, '48x48' => 1];
        $icoNames = [
            '16x16'     => 'icon_16',
            '32x32'     => 'icon_32',
            '170x170'   => 'apple_mask',
            '180x180'   => 'apple_touch',
            '192x192'   => 'android',
            '1024x1024' => 'apple_splash'
        ];

        $dataIcoSizes = [];
        $dataIcons = [];

        while($row = $icons->fetch()) {
            $siteId = $row['site_id'];
            unset($row['site_id']);

            if (!isset($dataIcoSizes[$siteId])) {
                $dataIcoSizes[$siteId] = [];
            }

            if (!isset($dataIcons[$siteId])) {
                $dataIcons[$siteId] = [];
            }

            if (isset($icoSizes[$row['icon_size']])) {
                $dataIcoSizes[$siteId][] = $row['icon_size'];
            }

            if (isset($icoNames[$row['icon_size']])) {
                $dataIcons[$siteId][$icoNames[$row['icon_size']]] = $row;
            }
        }

        $meta = $this->db->query(
            'SELECT * FROM sites_manifests_html_meta'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        while($row = $meta->fetch()) {
            $metaData = [];

            $siteId = $row['site_id'];

            $name = trim($row['name']);
            $addrChrome = trim($row['addr_chrome']);
            $addrSafari = trim($row['addr_safari']);
            $touchColor = trim($row['touch_color']);
            $tileColor  = trim($row['tile_color']);

            // Разрешения для картинки .ico
            if (isset($dataIcoSizes[$siteId])) {
                $metaData['ico'] = implode(' ', $dataIcoSizes[$siteId]);
            }

            if ($name) {
                $metaData['name'] = $name;
            }

            if ($addrChrome) {$metaData['addr_chrome'] = $addrChrome;}
            if ($addrSafari) {$metaData['addr_safari'] = $addrSafari;}
            if ($touchColor) {$metaData['touch_color'] = $touchColor;}
            if ($tileColor)  {$metaData['tile_color']  = $tileColor; }

            if (isset($dataIcons[$siteId])) {
                $metaData = array_merge($metaData, $dataIcons[$siteId]);
            }

            if ($row['lang']) {
                $this->savePHPArrayToFile(DIR_ROOT.'/cache/sites/'.$siteId.'_html_meta_'.$row['lang'].'.php', $metaData);
            }
        }
    }

    private function generateMetaOGTC($siteId = 0) {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_ogtc_icons'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        $icoNames = [
            '600x600'  => 'twitter_small',
            '800x800'  => 'facebook_small',
            '1600x800' => 'twitter_large',
            '1200x630' => 'facebook_large'
        ];

        $dataIcons = [];

        while($row = $icons->fetch()) {
            $siteId = $row['site_id'];
            unset($row['site_id']);

            if (!isset($dataIcons[$siteId])) {
                $dataIcons[$siteId] = [];
            }

            if (isset($icoNames[$row['icon_size']])) {
                $dataIcons[$siteId][$icoNames[$row['icon_size']]] = $row;
            }
        }

        $meta = $this->db->query('SELECT * FROM sites_manifests_ogtc_meta');

        while($row = $meta->fetch()) {
            $iconsArray = isset($dataIcons[$row['site_id']]) ? $dataIcons[$row['site_id']] : [];

            $this->savePHPArrayToFile(
                DIR_ROOT.'/cache/sites/'.$siteId.'_ogtc_meta_'.$row['lang'].'.php',
                array_merge(
                    $row,
                    $iconsArray
                )
            );
        }
    }

    private function generateAndroidManifestsJSON($siteId = 0) {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_json_icons'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        $iconsList = [];

        while($row = $icons->fetch()) {
            $siteId = $row['site_id'];
            unset($row['site_id']);

            if (!isset($iconsList[$siteId])) {
                $iconsList[$siteId] = [];
            }

            $iconsList[$siteId][] = $row;
        }

        $manifests = $this->db->query(
            'SELECT * FROM sites_manifests_json'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        $manifestFields = [
            'lang',
            'display',
            'orientation',
            'dir',
            'name',
            'short_name',
            'start_url',
            'scope',
            'background_color',
            'theme_color',
            'description'
        ];

        $fieldsLen = sizeof($manifestFields);

        while($row = $manifests->fetch()) {
            $siteId = $row['site_id'];
            unset($row['site_id']);

            $manifestArray = [];

            for($i = 0; $i < $fieldsLen; $i++) {
                $fieldName = $manifestFields[$i];
                $fieldVal  = trim($row[$fieldName]);

                if ($fieldVal) {
                    $manifestArray[$fieldName] = $fieldVal;
                }
            }

            if (isset($iconsList[$siteId]) && sizeof($iconsList[$siteId])) {
                $manifestArray['icons'] = $iconsList[$siteId];
            }

            file_put_contents(DIR_PUBLIC.'meta/'.$siteId.'_manifest_'.$row['lang'].'.json', json_encode($manifestArray));
        }
    }

    private function generateMSBrowserConfigXML($siteId = 0) {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_ms_icons'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        $iconsXml = [];

        while($row = $icons->fetch()) {
            $siteId = $row['site_id'];
            unset($row['site_id']);

            if (!isset($iconsXml[$siteId])) {
                $iconsXml[$siteId] = '';
            }

            $iconsXml[$siteId] .= $row['icon_xml'];
        }

        $sites = $this->db->query(
            'SELECT site_id, manifest_ms_color_tile FROM sites_manifests'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        while($row = $sites->fetch()) {
            $siteId    = $row['site_id'];
            $tileColor = trim($row['manifest_ms_color_tile']);

            $xml  = '<?xml version="1.0" encoding="utf-8"?><browserconfig><msapplication><tile>';
            $xml .= isset($iconsXml[$row['site_id']]) ? $iconsXml[$row['site_id']] : '';
            $xml .= $tileColor ? '<TileColor>'.$tileColor.'</TileColor>' : '';
            $xml .= '</tile></msapplication></browserconfig>';

            file_put_contents(DIR_PUBLIC.'meta/'.$siteId.'_browserconfig.xml', $xml);
        }
    }

    private function generateRoutes($siteId = 0) {
        $sites = $this->db->query(
            'SELECT * FROM sites'.($siteId ? ' WHERE site_id='.$siteId : '')
        );

        $siteList = [];

        while($row = $sites->fetch()) {
            $siteList[] = $row['site_id'];
        }

        for($a = 0, $len = sizeof($siteList); $a < $len; $a++) {
            $siteId = $siteList[$a];

            $routes = $this->db->query(
                '
                    SELECT 
                        * 
                    FROM sites_routes 
                    WHERE 
                        site_id = :sid AND 
                        route_disabled = 0 
                    ORDER BY route_order DESC
                ',
                ['sid' => $siteId]
            );

            $routesList = [];

            while($row = $routes->fetch()) {
                $routesList[] = [
                    'patterns' => $row['route_pattern'],
                    'paths'    => json_decode($row['route_json'], true)
                ];
            }

            $this->savePHPArrayToFile(
                DIR_ROOT.'/cache/sites/'.$siteId.'_routes.php',
                $routesList
            );
        }
    }

    private function generatePHPArrayFileContent($array) {
        return '<?return ' . var_export($array, true) . ';';
    }

    private function savePHPArrayToFile($fileName, $fileArray) {
        file_put_contents($fileName, $this->generatePHPArrayFileContent($fileArray));
    }
}