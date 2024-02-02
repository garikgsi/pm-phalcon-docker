<?php
namespace Core\Handler;

use Phalcon\Di\Injectable;
/**
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 */
class SiteUtils extends Injectable
{
    private $siteRow;
    
    public function __construct($siteId, $siteRow = [])
    {
        if (!sizeof($siteRow)) {
            $siteRow = $this->db->query(
                'SELECT * FROM sites WHERE site_id = :sid', ['sid' => $siteId]
            )->fetch();
        }
        
        $this->siteRow = $siteRow;
    }

    public function performAll() {
        $this->generateConfigFile();
        $this->generateRoutesFile();
        $this->generateMetaFile();
        $this->generateHeadOgTcFile();
        $this->generateHeadHtmlFile();
        $this->generateBrowserConfigFile();
        $this->generateManifestFile();

        return $this;
    }

    public function generateDomainsFile()
    {
        $cacheDir = DIR_ROOT.'cache/sites/';

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

        return $this;
    }

    /**
     * Генерация первичного конфиг-файла
     */
    public function generateConfigFile() 
    {
        $siteId   = $this->siteRow['site_id'];
        $siteName = strtolower($this->siteRow['site_name']);
        $defaultRouter = (int)$this->siteRow['site_routing_default'];

        $minEnabled = (int)$this->siteRow['site_min_enabled'];
        $minVerCss  = (int)$this->siteRow['site_min_ver_css'];
        $minVerJs   = (int)$this->siteRow['site_min_ver_js'];
        $minVerTmp  = (int)$this->siteRow['site_min_ver_templates'];

        $ucSiteName = ucfirst($siteName);    // делаем первую букву заглавной

        $this->savePHPArrayToFile(
            DIR_ROOT.'cache/sites/'.$siteName.'.php',
            [
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
            ]
        );

        return $this;
    }

    /**
     * Генерация файла с маршрутами URL
     */
    public function generateRoutesFile() 
    {
        $siteId = $this->siteRow['site_id'];
        
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
            DIR_ROOT.'cache/sites/'.$siteId.'_routes.php',
            $routesList
        );

        return $this;
    }

    /**
     * Генерация классических мета-тегов
     */
    public function generateMetaFile() 
    {
        $meta = $this->db->query(
            '
                SELECT
                    l.lang_name,
                    s.site_name,
                    sm.meta_title,
                    sm.meta_keywords,
                    sm.meta_description,
                    sm.email_name,
                    sm.email_signature
                FROM
                    languages AS l,
                    sites AS s,
                    sites_meta AS sm 
                WHERE
                    s.site_id = sm.site_id AND 
                    l.lang_id = sm.lang_id AND 
                    s.site_id=:sid
            ',
            ['sid' => $this->siteRow['site_id']]
        );


        while($row = $meta->fetch()) {
            $this->savePHPArrayToFile(DIR_ROOT.'cache/sites/'.strtolower($row['site_name']).'_meta_'.$row['lang_name'].'.php', [
                'meta' => [
                    'title' => $row['meta_title'],
                    'keywords'    => $row['meta_keywords'],
                    'description' => $row['meta_description'],
                    'email_name' => $row['email_name'],
                    'email_signature' => $row['email_signature']
                ]
            ]);
        }

        return $this;
    }

    /**
     * Генерация специальной разметки для OpenGraph и TwitterCards
     */
    public function generateHeadOgTcFile() 
    {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_ogtc_icons WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
        );

        $icoNames = [
            '600x600'  => 'twitter_small',
            '800x800'  => 'facebook_small',
            '1600x800' => 'twitter_large',
            '1200x630' => 'facebook_large'
        ];

        $dataIcons = [];

        while($row = $icons->fetch()) {
            unset($row['site_id']);

            if (isset($icoNames[$row['icon_size']])) {
                $dataIcons[$icoNames[$row['icon_size']]] = $row;
            }
        }

        $meta = $this->db->query(
            'SELECT * FROM sites_manifests_ogtc_meta WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
        );

        while($row = $meta->fetch()) {
            $this->savePHPArrayToFile(
                DIR_ROOT.'cache/sites/'.$this->siteRow['site_id'].'_ogtc_meta_'.$row['lang'].'.php',
                array_merge(
                    $row,
                    $dataIcons
                )
            );
        }

        return $this;
    }

    /**
     * Генерация специальной разметки для иконок
     */
    public function generateHeadHtmlFile() 
    {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_html_icons WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
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
            unset($row['site_id']);

            if (isset($icoSizes[$row['icon_size']])) {
                $dataIcoSizes[] = $row['icon_size'];
            }

            if (isset($icoNames[$row['icon_size']])) {
                $dataIcons[$icoNames[$row['icon_size']]] = $row;
            }
        }

        $meta = $this->db->query(
            'SELECT * FROM sites_manifests_html_meta WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
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
                $metaData['ico'] = implode(' ', $dataIcoSizes);
            }

            if ($name) {
                $metaData['name'] = $name;
            }

            if ($addrChrome) {$metaData['addr_chrome'] = $addrChrome;}
            if ($addrSafari) {$metaData['addr_safari'] = $addrSafari;}
            if ($touchColor) {$metaData['touch_color'] = $touchColor;}
            if ($tileColor)  {$metaData['tile_color']  = $tileColor; }

            if (isset($dataIcons[$siteId])) {
                $metaData = array_merge($metaData, $dataIcons);
            }

            if ($row['lang']) {
                $this->savePHPArrayToFile(DIR_ROOT.'cache/sites/'.$siteId.'_html_meta_'.$row['lang'].'.php', $metaData);
            }
        }

        return $this;
    }

    /**
     * Генерация конфиг файла для интернет эксплореров
     */
    public function generateBrowserConfigFile() 
    {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_ms_icons WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
        );

        $iconsXml = '';

        while($row = $icons->fetch()) {
            $iconsXml .= $row['icon_xml'];
        }

        $manifest = $this->db->query(
            'SELECT site_id, manifest_ms_color_tile FROM sites_manifests WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
        )->fetch();

        $tileColor = trim($manifest['manifest_ms_color_tile']);

        $xml  = '<?xml version="1.0" encoding="utf-8"?><browserconfig><msapplication><tile>';
        $xml .= isset($iconsXml[$manifest['site_id']]) ? $iconsXml[$manifest['site_id']] : '';
        $xml .= $tileColor ? '<TileColor>'.$tileColor.'</TileColor>' : '';
        $xml .= '</tile></msapplication></browserconfig>';

        file_put_contents(DIR_PUBLIC.'meta/'.$this->siteRow['site_id'].'_browserconfig.xml', $xml);

        return $this;
    }

    /**
     * Генерация маничест файла
     */
    public function generateManifestFile() 
    {
        $icons = $this->db->query(
            'SELECT * FROM sites_manifests_json_icons WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
        );

        $iconsList = [];

        while($row = $icons->fetch()) {
            unset($row['site_id']);

            $iconsList[] = $row;
        }

        $manifests = $this->db->query(
            'SELECT * FROM sites_manifests_json WHERE site_id = :sid',
            ['sid' => $this->siteRow['site_id']]
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

            if (sizeof($iconsList)) {
                $manifestArray['icons'] = $iconsList;
            }

            file_put_contents(DIR_PUBLIC.'meta/'.$siteId.'_manifest_'.$row['lang'].'.json', json_encode($manifestArray));
        }

        return $this;
    }


    private function generatePHPArrayFileContent($array) 
    {
        return '<?return ' . var_export($array, true) . ';';
    }

    private function savePHPArrayToFile($fileName, $fileArray) 
    {
        file_put_contents($fileName, $this->generatePHPArrayFileContent($fileArray));
    }
}