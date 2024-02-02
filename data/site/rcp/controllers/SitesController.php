<?php
namespace Site\Rcp\Controllers;

use Core\Builder\Bookmarks;
use Core\Extender\ControllerAppRcp;
use Core\Handler\SiteUtils;
use Core\Lib\AjaxFormResponse;
use Core\Lib\PhpIco;
use enshrined\svgSanitize\Sanitizer;
use Imagick;
use Phalcon\Filter;
use Phalcon\Mvc\View;

/**
 * Class IndexController
 * @package Site\Rcp\Controllers
 * @property \Core\Lib\BreadCrumbs $breadcrumbs
 */
class SitesController extends ControllerAppRcp
{
    public function initialize()
    {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->breadcrumbs->addCrumb('/sites', $this->t->_('rcp_sites'), View::LEVEL_LAYOUT);

        $this->hapi->setHapiController('sites');
        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_sites');
    }

    //todo: расставить проверку на пустоту

    public function indexAction()
    {
        $sites = $this->db->query('SELECT site_id, site_name, domains, site_min_enabled FROM sites_list');

        $sitesList = [];

        while($row = $sites->fetch()) {
            $sitesList[] = [
                'id'       => $row['site_id'],
                'name'     => $row['site_name'],
                'domains'  => $row['domains'],
                'compress' => $row['site_min_enabled'],
                'dir'      => '/site/'.$row['site_name'].'/',
                'design'   => '/public/design/'.$row['site_name'].'/'
            ];
        }

        $this->view->setVar('content_wrap_title_buttons', [
            [
                'text'    => $this->t->_('rcp_sites_list_add'),
                'href'    => '/sites/add',
                'variant' => 'send',
                'title'   => $this->t->_('rcp_sites_list_add_d'),
                'icon'    => '',
                'id'      => '',
                'classes' => 'elizaHApi',
                'data'    => ['data-level' => 3]
            ]
        ]);


        $this->site->setMetaTitle($this->t->_('rcp_sites_list_title'));
        $this->view->setVar('content_wrap_title', $this->t->_('rcp_sites_list_title'));
        $this->view->setVar('sitesList', $sitesList);


        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);
    }

    public function addAction() {
        $this->view->setVar('content_wrap_title', $this->t->_('rcp_site_add'));
        $this->breadcrumbs->addCrumb('/sites/add', $this->t->_('rcp_site_add'), View::LEVEL_LAYOUT);
        $this->hapi->setHapiAction('add');
    }

    public function editAction($siteId, $mode = '', $subMode = '')
    {
        $Bookmarks = new Bookmarks();

        $t = $this->t;

        $mode   = mb_strtolower($mode);

        $hrefBase = '/sites/edit/'.$siteId;


        $n2 = 'meta';
        $n3 = 'manifest';
        $n4 = 'ogtc';
        $n5 = 'resources';
        $n6 = 'controllers';
        $n7 = 'routes';
        $n8 = 'rights';

        $Bookmarks->addBookMarksFromArray([
            ['',  $t->_('rcp_sites_books_site'),        $hrefBase,         2, 1, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n2, $t->_('rcp_sites_books_meta'),        $hrefBase.'/'.$n2, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n3, $t->_('rcp_sites_books_manifest'),    $hrefBase.'/'.$n3, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n4, $t->_('rcp_sites_books_ogtc'),        $hrefBase.'/'.$n4, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n5, $t->_('rcp_sites_books_resources'),   $hrefBase.'/'.$n5, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n6, $t->_('rcp_sites_books_controllers'), $hrefBase.'/'.$n6, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n7, $t->_('rcp_sites_books_routes'),      $hrefBase.'/'.$n7, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n8, $t->_('rcp_sites_books_rights'),      $hrefBase.'/'.$n8, 2, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"']
        ]);

        if (!$Bookmarks->isBookMarkExists($mode)) {
            $this->redirect($hrefBase);
            return;

        }

        $Bookmarks->setActive($mode);

        $allowedModes = [
            ''            => ['sites',       'editSite',            'edit'],
            'meta'        => ['meta',        'editSiteMeta',        'meta'],
            'manifest'    => ['manifest',    'editSiteManifest',    'manifest'],
            'ogtc'        => ['og_tc',       'editSiteOGTC',        'ogtc'],
            'resources'   => ['resources',   'editSiteResources',   'resources'],
            'controllers' => ['controllers', 'editSiteControllers', 'controllers'],
            'routes'      => ['routes',      'editSiteRoutes',      'routes'],
            'rights'      => ['rights',      'editSiteRights',      'rights']
        ];


        $siteId = (int)$siteId;

        $site = $this->db->query(
            '
                SELECT 
                    site_id,
                    site_name,
                    site_routing_default,
                    site_min_enabled 
                FROM sites 
                WHERE site_id = :sid
            ',
            ['sid' => $siteId]
        )->fetch();

        if (!$site) {
            $this->redirect('sites');
            return;
        }

        $compressText    = 'Сжатие выключено';
        $compressVariant = 'default';
        $compressTitle   = 'Включить сжатие сайта';
        $compressIcon    = 'ic-cross';
        $compressClass   = '';

        if ($site['site_min_enabled'] == 1) {
            $compressText    = 'Сжатие включено';
            $compressVariant = 'gactive';
            $compressTitle   = 'Выключить сжатие сайта';
            $compressIcon    = 'ic-check';
            $compressClass   = 'active';
        }

        $this->view->setVar('content_wrap_title_buttons', [
            [
                'text'    => $compressText,
                'href'    => '',
                'variant' => $compressVariant,
                'title'   => $compressTitle,
                'icon'    => $compressIcon,
                'id'      => 'rcpSiteCompressActivation',
                'classes' => $compressClass,
                'data'    => ['data-site' => $siteId],
                'tag'     => 'div'
            ],
            [
                'text'    => 'Сжать CSS',
                'href'    => '',
                'variant' => 'send',
                'title'   => 'Сжать весь CSS для сайта',
                'icon'    => 'ic-contract',
                'id'      => 'rcpSiteMinimizationCSS',
                'classes' => '',
                'data'    => ['data-site' => $siteId],
                'tag'     => 'div'
            ],
            [
                'text'    => 'Сжать JS',
                'href'    => '',
                'variant' => 'success',
                'title'   => 'Сжать весь JavaScript для сайта',
                'icon'    => 'ic-contract',
                'id'      => 'rcpSiteMinimizationJS',
                'classes' => '',
                'data'    => ['data-site' => $siteId],
                'tag'     => 'div'
            ],
            [
                'text'    => 'Обновить шаблоны',
                'href'    => '',
                'variant' => 'gactive',
                'title'   => 'Сбросить кэш шаблонов для сайта',
                'icon'    => 'ic-archive',
                'id'      => 'rcpSiteMinimizationTemplates',
                'classes' => '',
                'data'    => ['data-site' => $siteId],
                'tag'     => 'div'
            ]
        ]);

        $selectedMode = $allowedModes[$mode];

        $this->view->setVar('rcp_edit_site_id', $site['site_id']);

        $editTitle = $this->t->_('rcp_sites_edit_title', ['name' => $site['site_name']]);

        $this->view->setVar('content_wrap_title', $editTitle);
        $this->site->setMetaTitle($this->t->_($editTitle));
        $this->breadcrumbs->addCrumb($hrefBase, $editTitle, View::LEVEL_LAYOUT);

        $this->hapi->setHapiAction($selectedMode[0]);
        $actionName = $selectedMode[1];
        $this->view->pick('sites/'.$selectedMode[2]);


        $this->view->setVar('bookMarks', $Bookmarks->getBookMarks());
        $this->view->setVar('site', $site);


        $this->$actionName($site, $subMode);

        $this->attachHapiCallback(2, 'defaultBookmarksClick', '', []);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $site
     */
    private function editSite($site)
    {
        $siteName = $site['site_name'];


        $checkFolders = [
            'public/design/'.$siteName,
            'public/js/site/'.$siteName,
            'public/min/'.$siteName,
            'site/'.$siteName
        ];

        $checkFiles = [
            'site/'.$siteName.'/Module.php',
            'site/'.$siteName.'/controllers/IndexController.php',
            'site/'.$siteName.'/langs/index/ru.php',
            'site/'.$siteName.'/views/index.phtml',
            'public/js/site/'.$siteName.'.js'
        ];

        $statuses = [['ic-blocked', 'red'], ['ic-check', 'green']];

        $checkList = [];

        for($i = 0, $len = sizeof($checkFolders); $i < $len; $i++) {
            $dir = $checkFolders[$i];

            $checkList[] = [$dir, $statuses[(int)is_dir(DIR_ROOT.$dir)]];
        }

        $checkList[] = $this->t->_('rcp_sites_site_files');

        for($i = 0, $len = sizeof($checkFiles); $i < $len; $i++) {
            $file = $checkFiles[$i];

            $checkList[] = [$file, $statuses[(int)file_exists(DIR_ROOT.$file)]];
        }

        $domains = $this->db->query(
            '
                SELECT 
                    domain_name,
                    domain_cross,
                    lang_id                 
                FROM sites_domains
                WHERE site_id = :sid
                ORDER BY domain_name ASC
            ',
            ['sid' => $site['site_id']]
        );

        $domainList = [];

        while($row = $domains->fetch()) {
            $row['site_id'] = $site['site_id'];

            $domainList[] = $row;
        }


        $this->view->setVar('domainList', $domainList);
        $this->view->setVar('checkList', $checkList);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $site
     */
    private function editSiteMeta($site) {
        $counters = $this->db->query(
            '
                SELECT
                    site_mailru_counter,
                    site_yandex_master,
                    site_google_analytics,
                    site_google_publisher
                FROM
                    sites
                WHERE
                    site_id = :sid
            ',
            ['sid' => $site['site_id']]
        )->fetch();

        $this->view->setVar('metaIcons',   $this->getSiteIcons(
            $site['site_id'],
            'meta_icon',
            'rcpSiteMetaEditIcon',
            'ico',
            ['16x16', '32x32', '48x48'])
        );

        $this->view->setVar('metaCounter', $counters);
        $this->view->setVar('metaData',    $this->getMetaLang($site['site_id'], 1));
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $site
     */
    private function editSiteManifest($site) {
        $appAndroidIconPref   = '/design/admin/devicepreview/android/';
        $appMicrosoftIconPref = '/design/admin/devicepreview/ms/';
        $appAppleIconPref     = '/design/admin/devicepreview/ios/';

        $iconTitle = $this->t->_('rcp_sites_manifest_icon');

        $manifestData = $this->db->query(
            '
                SELECT
                    sm.display_id     AS display,
                    sm.orientation_id AS orientation,
                    sm.manifest_url   AS url,
                    sm.manifest_addr_color  AS addr_color,
                    sm.manifest_theme_color AS theme_color,
                    sm.manifest_scope       AS scope,
                    sm.manifest_ios_color_addr  AS ios_color_addr,
                    sm.manifest_ios_color_touch AS ios_color_touch,
                    sm.manifest_ms_color_tile   AS ms_color_tile
                FROM sites_manifests AS sm 
                WHERE sm.site_id = :sid
            ',
            ['sid' => $site['site_id']]
        )->fetch();

        $manifestLang = $this->getManifestLang($site['site_id'], 1);

        $androidIcons = $this->getSiteIcons(
            $site['site_id'],
            'meta_icon',
            'rcpSiteManifestAndroidEditIcon',
            'android',
            ['192x192', '512x512'],
            '.rcpManifestAndroid'
        );

        $microsoftIcons = $this->getSiteIcons(
            $site['site_id'],
            'meta_icon',
            'rcpSiteManifestMicrosoftEditIcon',
            'microsoft',
            ['310x310', '310x150', '150x150', '70x70'],
            '.rcpManifestMicrosoft',
            'DESC',
            'DESC',
            1,
            [['contSize' => 'large'], ['contSize' => 'wide'], ['contSize' => 'medium'], ['contSize' => 'small']]
        );

        $appleIcons = $this->getSiteIcons(
            $site['site_id'],
            'meta_icon',
            'rcpSiteManifestAppleEditIcon',
            'apple',
            ['1024x1024', '180x180', '170x170'],
            '.rcpManifestApple',
            'DESC',
            'DESC',
            -1,
            [['contSize' => 'splash'], ['contSize' => 'x180x180'], ['contSize' => 'svg rcpSVGImage']]
        );

        $svgSrc  = explode('?', $appleIcons[2]['imageSrc'])[0];
        $svgHtml = $svgSrc ? file_get_contents(DIR_ROOT.'public'.$svgSrc) : '';

        $appListAndroid = [
            ['title' => 'Chrome', 'src' => $appAndroidIconPref.'chrome.png', 'class' => '', 'data' => ''],
            ['title' => 'Gmail',  'src' => $appAndroidIconPref.'gmail.png',  'class' => '', 'data' => ''],
            ['title' => 'Google', 'src' => $appAndroidIconPref.'google.png', 'class' => '', 'data' => ''],
            ['title' => 'Maps',   'src' => $appAndroidIconPref.'maps.png',   'class' => '', 'data' => ''],
            [
                'title' => $manifestLang['name_short'],
                'src'   => $androidIcons[0]['imageSrc'],
                'class' => ' rcpManifestShortName',
                'data'  => ' title="Так будет выглядеть ваша иконка"',
                'itemClass' => ' yourapp helpinfo dtb',
                'itemData'  => ' data-elz-title="192×192"',
                'imgClass'  => 'rcpManifestAndroid_192x192',
                'titleClass' => ' rcpManifestNameShort'
            ],
            ['title' => 'Play Store', 'src' => $appAndroidIconPref.'store.png',   'class' => '', 'data' => ''],
            ['title' => 'Youtube',    'src' => $appAndroidIconPref.'youtube.png', 'class' => '', 'data' => ''],
            ['title' => 'Drive',      'src' => $appAndroidIconPref.'drive.png',   'class' => '', 'data' => ''],
            ['title' => 'Google+',    'src' => $appAndroidIconPref.'gplus.png',   'class' => '', 'data' => '']
        ];

        $booksListAndroid = [
            ['title' => 'Gmail',  'src' => $appAndroidIconPref.'gmail.png',  'class' => '', 'data' => '', 'style' => '', 'switch' => ''],
            ['title' => 'Chrome', 'src' => $appAndroidIconPref.'chrome.png', 'class' => '', 'data' => '', 'style' => '', 'switch' => ''],
            [
                'title'  => $manifestLang['name_short'],
                'src'    => $androidIcons[0]['imageSrc'],
                'switch' => ' yourapp rcpManifestAndroidAddrColor',
                'style'  => ' style="background-color: '.$manifestData['addr_color'].';"',
                'class'  => ' helpinfo dtb dtl rcpManifestShortName',
                'data'   => ' data-elz-title="192×192" title="'.$iconTitle.'"',
                'imgClass' => 'rcpManifestAndroid_192x192',
                'titleClass' => ' rcpManifestNameShort'
            ]
        ];


        $appListMs = [
            [
                'title' => $this->t->_('rcp_sites_manifest_ms_small'),
                'size'  => 'small',
                'app'   => [
                    ['class' => 'small green',     'attr' => '', 'src' => $appMicrosoftIconPref.'xbox.png',  'name'  => 'Xbox'],
                    ['class' => 'small lightblue', 'attr' => '', 'src' => $appMicrosoftIconPref.'store.png', 'name'  => 'Store'],
                    ['class' => 'small darkblue',  'attr' => '', 'src' => $appMicrosoftIconPref.'edge.png',  'name'  => 'Microsoft edge'],
                    [
                        'imgClass' => ' rcpManifestMicrosoft_70x70',
                        'class'    => 'small yourapp helpinfo dtb microsoftColor',
                        'attr'     => ' data-elz-title="70×70" title="'.$iconTitle.'" style="background-color: '.$manifestData['ms_color_tile'].';background-image:url('.$microsoftIcons[1][2]['imageSrc'].');"',
                        'name'     => $manifestLang['name_short'],
                        'titleClass' => ' rcpManifestNameShort'
                    ]
                ]
            ],
            [
                'title' => $this->t->_('rcp_sites_manifest_ms_medium'),
                'size'  => '',
                'app'   => [
                    ['class' => 'medium green',     'attr' => '', 'src' => $appMicrosoftIconPref.'xbox.png',  'name'  => 'Xbox'],
                    ['class' => 'medium lightblue', 'attr' => '', 'src' => $appMicrosoftIconPref.'store.png', 'name'  => 'Store'],
                    ['class' => 'medium darkblue',  'attr' => '', 'src' => $appMicrosoftIconPref.'edge.png',  'name'  => 'Microsoft edge'],
                    [
                        'imgClass' => ' rcpManifestMicrosoft_150x150',
                        'class' => 'medium yourapp helpinfo dtb microsoftColor',
                        'attr'  => ' data-elz-title="150×150" title="'.$iconTitle.'" style="background-color: '.$manifestData['ms_color_tile'].';background-image:url('.$microsoftIcons[1][1]['imageSrc'].');"',
                        'name'  => $manifestLang['name_short'],
                        'titleClass' => ' rcpManifestNameShort'
                    ]
                ]
            ],
            [
                'title' => $this->t->_('rcp_sites_manifest_ms_wide'),
                'size'  => '',
                'app'   => [
                    ['class' => 'medium green',     'attr' => '', 'src' => $appMicrosoftIconPref.'xbox.png',  'name' => 'Xbox'],
                    ['class' => 'medium lightblue', 'attr' => '', 'src' => $appMicrosoftIconPref.'store.png', 'name' => 'Store'],
                    [
                        'imgClass' => ' rcpManifestMicrosoft_310x150',
                        'class' => 'wide yourapp helpinfo dtb microsoftColor',
                        'attr'  => ' data-elz-title="310×150" title="'.$iconTitle.'" style="background-color: '.$manifestData['ms_color_tile'].';background-image:url('.$microsoftIcons[1][0]['imageSrc'].');"',
                        'name'  => $manifestLang['name_short'],
                        'titleClass' => ' rcpManifestNameShort'
                    ]
                ]
            ],
            [
                'title' => $this->t->_('rcp_sites_manifest_ms_large'),
                'size'  => '',
                'app'   => [
                    [
                        'imgClass' => ' rcpManifestMicrosoft_310x310',
                        'class' => 'large yourapp helpinfo dtb microsoftColor',
                        'attr'  => ' data-elz-title="310×310" title="'.$iconTitle.'" style="background-color: '.$manifestData['ms_color_tile'].';background-image:url('.$microsoftIcons[0]['imageSrc'].');"',
                        'name'  => $manifestLang['name_short'],
                        'titleClass' => ' rcpManifestNameShort'
                    ]
                ]
            ]
        ];


        $appListApple = [
            ['title' => 'Messages',  'src' => $appAppleIconPref.'messages.png', 'class' => '', 'data' => ''],
            ['title' => 'Calendar',  'src' => $appAppleIconPref.'calendar.png', 'class' => '', 'data' => ''],
            ['title' => 'Photos',    'src' => $appAppleIconPref.'photos.png',   'class' => '', 'data' => ''],
            ['title' => 'Weather',   'src' => $appAppleIconPref.'weather.png',  'class' => '', 'data' => ''],
            [
                'title' => $manifestLang['name_short'],
                'src'   => $appleIcons[1]['imageSrc'],
                'class' => ' rcpManifestShortName',
                'data'  => ' title="'.$iconTitle.'"',
                'itemClass' => ' yourapp helpinfo dtb',
                'itemData'  => ' data-elz-title="180×180"',
                'imgClass' => 'rcpManifestApple_180x180',
                'titleClass' => ' rcpManifestNameShort'
            ],
            ['title' => 'Maps',      'src' => $appAppleIconPref.'maps.png',      'class' => '', 'data' => ''],
            ['title' => 'Notes',     'src' => $appAppleIconPref.'notes.png',     'class' => '', 'data' => ''],
            ['title' => 'Reminders', 'src' => $appAppleIconPref.'reminders.png', 'class' => '', 'data' => ''],
            ['title' => 'Stocks',    'src' => $appAppleIconPref.'stocks.png',    'class' => '', 'data' => '']
        ];


        $booksAttr = ' title="'.$iconTitle.'" data-elz-title="SVG"';

        $booksListApple = [
            [
                'title'      => $this->t->_('rcp_sites_manifest_ios_line1'),
                'line_class' => ' mac',
                'li_class'   => ' rcpManifestApple_170x170',
                'li_attr'    => $booksAttr,
                'svg'        => $svgHtml
            ],
            [
                'title'      => $this->t->_('rcp_sites_manifest_ios_line2'),
                'line_class' => ' mac',
                'li_class'   => ' focus rcpManifestAppleTouchColor rcpManifestApple_170x170',
                'li_attr'    => $booksAttr.' style="color: '.$manifestData['ios_color_touch'].';"',
                'svg'        => $svgHtml
            ],
            [
                'title'      => $this->t->_('rcp_sites_manifest_ios_line3'),
                'line_class' => ' touch',
                'li_class'   => ' rcpManifestAppleTouchColorBG rcpManifestApple_170x170',
                'li_attr'    => $booksAttr.' style="background-color: '.$manifestData['ios_color_touch'].';"',
                'svg'        => $svgHtml
            ]
        ];

        $displays = $this->db->query(
            'SELECT display_id, display_name FROM sites_manifests_display ORDER BY display_id ASC'
        );

        $displayList = [];
        while($row = $displays->fetch()) {
            $displayList[] = [$row['display_id'], $row['display_name']];
        }

        $orientations = $this->db->query(
            'SELECT orientation_id, orientation_name FROM sites_manifests_orientation ORDER BY orientation_id ASC'
        );

        $orientationList = [];
        while($row = $orientations->fetch()) {
            $orientationList[] = [$row['orientation_id'], $row['orientation_name']];
        }
        $this->view->setVar('appListApple',     $appListApple);
        $this->view->setVar('appListMs',        $appListMs);
        $this->view->setVar('appListAndroid',   $appListAndroid);
        $this->view->setVar('booksListApple',   $booksListApple);
        $this->view->setVar('booksListAndroid', $booksListAndroid);
        $this->view->setVar('displayList',      $displayList);
        $this->view->setVar('orientationList',  $orientationList);
        $this->view->setVar('manifestData',     $manifestData);


        $this->view->setVar('manifestMicrosoftIcons', $microsoftIcons);
        $this->view->setVar('manifestAndroidIcons',   $androidIcons);
        $this->view->setVar('manifestAppleIcons',     $appleIcons);

        $this->view->setVar('manifestMeta', $manifestLang);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $site
     */
    private function editSiteOGTC($site) {
        $ogtcLang =  $this->getOgtcLang($site['site_id'], 1);

        $OGTCIcons =  $this->getSiteIcons(
            $site['site_id'],
            'meta_icon',
            'rcpSiteOGTCEditIcon',
            'ogtc',
            [
                '600x600',  // Квадрат ОГ (маленький)
                '1200x630', // Прямоугольник ОГ (большой)
                '800x800',  // Квадрат Твиттер (маленький)
                '1600x800'  // Прямоугольник Твиттер (большой)
            ]
        );

        $toSet = [
            '600x600'  => 0,
            '1200x630' => 3,
            '800x800'  => 1,
            '1600x800' => 2
        ];

        $demoList = [
            ['Twitter',  'Small', $this->t->_('rcp_sites_ogtc_label3')],
            ['Facebook', 'Small', $this->t->_('rcp_sites_ogtc_label4')],
            ['Twitter',  'Large', $this->t->_('rcp_sites_ogtc_label5')],
            ['Facebook', 'Large', $this->t->_('rcp_sites_ogtc_label6')]
        ];

        for($a = 0, $len = sizeof($OGTCIcons); $a < $len; $a++) {
            $icon = $OGTCIcons[$a];

            $demoList[$toSet[$icon['width'].'x'.$icon['height']]][] = $icon;
        }


        $this->view->setVar('ogtcData', $this->db->query(
            '
                SELECT ogtc_og_site, ogtc_twitter_account, ogtc_twitter_mode FROM sites_manifests WHERE site_id=:sid
            ',
            ['sid' => $site['site_id']]
        )->fetch());

        $this->view->setVar('ogtcDemo', $demoList);
        $this->view->setVar('ogtcLang', $ogtcLang);
        $this->view->setVar('ogtcSite', $site);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $site
     * @param $subMode
     */
    private function editSiteResources($site, $subMode) {
        $this->createLocalResourcesBookmarks($site, $subMode);

        if ($subMode == 'js') {
            $packagesLists    = [];
            $prepareItems = [
                'controller' => [],
                'site' => []
            ];


            $itemsList = [];

            $resourcesPackages = $this->db->query(
                'SELECT * FROM dp_resources_get_list_js(:sid)',
                ['sid' => $site['site_id']]
            );

            $createItem = function($row, $controls) {
                return [
                    'type' => 'item',
                    'dir'  => $row['resource_dir'],
                    'move' => '',
                    'data'  => '',
                    'file_color' => 'orange',
                    'file_name'  => $row['resource_dir'].$row['resource_name'],
                    'file_type'  => 'js',
                    'controls' => $controls
                ];
            };

            while($row = $resourcesPackages->fetch()) {

                $controls = '';

                if ($row['resource_type'] == 'package') {
                    if (!isset($packagesLists[$row['resource_type_id']])) {
                        $packagesLists[$row['resource_type_id']] = [];
                    }

                    $controls .= $this->getActionLabel(
                        'Файл минимизирован разработчиком',
                        'ic-contract',
                        $row['resource_minified'] ? 'green' : 'grey',
                        '',
                        ''
                    );

                    $excluded = $row['resource_id'] == $row['exclude_id'];

                    $controls .= $this->getActionLabel(
                        'Исключить подключение файла',
                        'ic-crosscircle',
                        $excluded ? 'red' : 'grey',
                        ' data-resource="'.$row['resource_id'].'"  data-site="'.$site['site_id'].'" ',
                        ' link rcpResourcesExcludeFile'.($excluded ? ' active' : '')
                    );

                    $packagesLists[$row['resource_type_id']][] = $createItem($row, $controls);
                }
                else {
                    $prepareItems[$row['resource_type']][] = $createItem($row, $controls);
                }
            }
            $packages = $this->db->query(
                'SELECT * FROM dp_resources_get_list_js_groups(:sid)',
                ['sid' => $site['site_id']]
            );

            $itemsListEnd = [];

            while($row = $packages->fetch()) {
                $connected = $row['package_id'] == $row['connect_id'];

                $controls = '';

                $controls .= $this->getActionLabel(
                    'Подключить пакет',
                    'ic-check',
                    $connected ? 'blue' : 'grey',
                    ' data-package="'.$row['package_id'].'"  data-site="'.$site['site_id'].'" ',
                    ' link rcpResourcesAttachPackage'.($connected ? ' active' : '')
                );

                $list = $packagesLists[$row['package_id']] ?? [];

                $item = [
                    'type'  => 'category',
                    'opened' =>  0,
                    'count' => sizeof($list),
                    'list'  => $list,
                    'dir'   => '',
                    'name'  => $row['package_name'],
                    'controls' => $controls
                ];

                if ($row['package_end'] == 1) {
                    $itemsListEnd[] = $item;
                }
                else {
                    $itemsList[] = $item;
                }
            }

            $this->view->setVar('packagesList', array_merge(
                $itemsList,
                $prepareItems['controller'],
                $prepareItems['site'],
                $itemsListEnd
            ));
        }
        else {
            $packagesLists = [
                'package'     => [],
                'package_app' => []
            ];

            $prepareItems = [
                'site' => [],
                'app'  => []
            ];


            $resourcesPackages = $this->db->query(
                'SELECT * FROM dp_resources_get_list_css(:sid)',
                ['sid' => $site['site_id']]
            );

            $createItem = function($row, $controls, $exist) {
                if ($exist) {
                    $controls .= $this->getActionLabel(
                        'Файл уже загружен ранее',
                        'ic-contract',
                        'purple',
                        '',
                        ''
                    );
                }

                return [
                    'type' => 'item',
                    'dir'  => $row['resource_dir'],
                    'move' => '',
                    'data'  => '',
                    'file_color' => 'blue',
                    'file_name'  => $row['resource_dir'].$row['resource_name'],
                    'file_type'  => 'css',
                    'controls' => $controls
                ];
            };

            while($row = $resourcesPackages->fetch()) {

                $controls = '';

                $exist = 0;

                /*if (isset($existFiles[$row['resource_dir'].$row['resource_name']])) {
                    $exist = 1;
                }
                else {
                    $exist = 0;
                    $existFiles[$row['resource_dir'].$row['resource_name']] = 1;
                }*/

                if ($row['resource_type'] == 'package' || $row['resource_type'] == 'package_app') {
                    if (!isset($packagesLists[$row['resource_type']][$row['resource_type_id']])) {
                        $packagesLists[$row['resource_type']][$row['resource_type_id']] = [];
                    }

                    $controls .= $this->getActionLabel(
                        'Файл минимизирован разработчиком',
                        'ic-contract',
                        $row['resource_minified'] ? 'green' : 'grey',
                        '',
                        ''
                    );

                    $excluded = $row['resource_id'] == $row['exclude_id'];

                    $controls .= $this->getActionLabel(
                        'Исключить подключение файла',
                        'ic-crosscircle',
                        $excluded ? 'red' : 'grey',
                        ' data-resource="'.$row['resource_id'].'"  data-site="'.$site['site_id'].'" ',
                        ' link rcpResourcesExcludeFile'.($excluded ? ' active' : '')
                    );

                    $packagesLists[$row['resource_type']][$row['resource_type_id']][] = $createItem($row, $controls, $exist);
                }
                else {
                    $prepareItems[$row['resource_type']][] = $createItem($row, $controls, $exist);
                }
            }



            $packages = $this->db->query(
                'SELECT * FROM dp_resources_get_list_css_groups(:sid)',
                ['sid' => $site['site_id']]
            );

            $itemsListEnd = [
                'package' => [],
                'app'     => []
            ];

            $itemsList = [
                'package' => [],
                'app'     => []
            ];

            while($row = $packages->fetch()) {
                $connected = $row['package_id'] == $row['connect_id'];

                $controls = '';

                $controls .= $this->getActionLabel(
                    'Подключить пакет',
                    'ic-check',
                    $connected ? 'blue' : 'grey',
                    ' data-package="'.$row['package_id'].'"  data-site="'.$site['site_id'].'" ',
                    ' link rcpResourcesAttachPackage'.($connected ? ' active' : '')
                );

                $localList = $packagesLists[$row['package_type'] == 'package' ? 'package' : 'package_app'][$row['package_id']] ?? [];

                $item = [
                    'type'  => 'category',
                    'opened' => $connected ? 1 : 0,
                    'count' => sizeof($localList),
                    'list'  => $localList,
                    'dir'   => '',
                    'name'  => $row['package_name'],
                    'controls' => $controls
                ];

                if ($row['package_end'] == 1) {
                    $itemsListEnd[$row['package_type']][] = $item;
                }
                else {
                    $itemsList[$row['package_type']][] = $item;
                }
            }

            $this->view->setVar('packagesList', array_merge(
                $itemsList['package'],
                $prepareItems['site'],
                $itemsListEnd['package']/*,


                $itemsList['app'],
                $prepareItems['app'],

                $itemsListEnd['app']*/
            ));

        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @param $site
     */
    private function editSiteControllers($site) {
        $siteId = $site['site_id'];

        $controllersList = [
            'active' => [],
            'others' => []
        ];

        $selected = $this->db->query(
            'SELECT * FROM dp_sites_controllers_activated(:sid, :lid)',
            ['sid' => $siteId, 'lid' => $this->user->getLangId()]
        );

        while($row = $selected->fetch()) {
            if ((int)$row['active'] == 0) {
                $controllersList['others'][] = $row;
                continue;
            }

            $controllersList['active'][] = array_merge(
                $row,
                $this->checkControllerActivation($row, $site)
            );
        }

        /*echo '<pre>';
        print_r($controllersList);
        exit;*/

        $this->view->setVar('controllersList', $controllersList);
    }

    private function editSiteRoutes($site) {
        $routes = $this->db->query(
            'SELECT * FROM sites_routes WHERE site_id = :sid ORDER BY route_order ASC',
            ['sid' => $site['site_id']]
        );

        $routesList = [];

        while($row = $routes->fetch()) {
            $routesList[] = $row;
        }


        $this->view->setVar('routesList', $routesList);
    }

    private function editSiteRights($site) {
        $this->view->setVar('rightsList', (new RightsController())->getRightsForSite($site['site_id']));
    }



    private function createLocalResourcesBookmarks($site, $currentPage = '') {
        $Bookmarks = new Bookmarks();

        $t = $this->t;

        $lng = function($num) use ($t) {
            return $t->_('rcp_sites_resources_book_'.$num);
        };

        $hrefBase = '/sites/edit/'.$site['site_id'].'/resources';

        $n2 = 'js';

        $Bookmarks->addBookMarksFromArray([
            ['',  $lng(1), $hrefBase,         1, 0, '', '', ''],
            [$n2, $lng(2), $hrefBase.'/'.$n2, 1, 0, '', '', '']
        ]);

        if (!$Bookmarks->isBookMarkExists($currentPage)) {
            return;
        }

        $Bookmarks->setActive($currentPage);

        $this->attachHapiCallback(1, 'defaultBookmarksClick', '', []);
        $this->view->setVar('res_local_books', $Bookmarks->getBookMarks());
        $this->view->setTemplateBefore('localbookmarks_wrap');
    }

    private function checkControllerActivation($controller, $site) {
        $txtFolder = $this->t->_('rcp_sites_controller_check_folder');
        $txtFile   = $this->t->_('rcp_sites_controller_check_file');

        $txtFolderErr = $this->t->_('rcp_sites_controller_check_folder_err');
        $txtFileErr   = $this->t->_('rcp_sites_controller_check_file_err');

        $siteName = $site['site_name'];

        $controllerName = $controller['controller_name'];

        // Папки для проверки
        $checkFolders = [
            'site/'.$siteName.'/views/'.$controllerName,
            'site/'.$siteName.'/langs/'.$controllerName
        ];

        // Файлы для проверки
        $checkFiles = [
            'site/'.$siteName.'/controllers/'.ucfirst($controllerName).'Controller.php',
            'site/'.$siteName.'/langs/'.$controllerName.'/ru.php',
            'site/'.$siteName.'/views/'.$controllerName.'/index.phtml',
            'public/js/site/'.$siteName.'/'.$controllerName.'.js'
        ];

        $statuses = [['ic-blocked', 'red'], ['ic-check', 'green']];

        $statusList = [
            'folder' => 0,
            'file'   => 0
        ];

        $checkList = [];

        for($i = 0, $len = sizeof($checkFolders); $i < $len; $i++) {
            $dir = $checkFolders[$i];

            $status = (int)is_dir(DIR_ROOT.$dir);

            $checkList[] = [$txtFolder, $dir, $statuses[$status]];

            $statusList['folder'] += $status;
        }

        for($i = 0, $len = sizeof($checkFiles); $i < $len; $i++) {
            $file = $checkFiles[$i];

            $status = (int)file_exists(DIR_ROOT.$file);

            $checkList[] = [$txtFile, $file, $statuses[$status]];

            $statusList['file'] += $status;
        }

        $statusIcons = [];

        $statusList['folder'] == 0 ? $statusIcons[] = ['ic-folder', 'red', $txtFolderErr] : [];
        $statusList['file']   == 0 ? $statusIcons[] = ['ic-box',    'red', $txtFileErr  ] : [];

        return [
            'check_list'  => $checkList,
            'status_list' => $statusIcons,
            'controller_active' => 1,
            'controller_status' => $statusList['file'] == 0 && $statusList['folder'] == 0 ? 'red' : 'green'
        ];
    }

    //------------------------------------------------------------------------------------------------------------------
    // API РАЗДЕЛ
    //------------------------------------------------------------------------------------------------------------------

    /**
     * Добавление нового сайта
     *
     * {int}    $_POST['sid']  - идентификатор сайта
     * {string} $_POST['name'] - название сайта
     *
     * @api
     */
    protected function apiSite_add()
    {
        

        $name   = mb_strtolower(trim($this->request->getPost('name')));
        $domain = mb_strtolower(trim($this->request->getPost('domain')));

        $addState   = 'no';
        $phpErrors  = 0;
        $resultCode = 0;

        $errors = [
            -1 => ['domain', $this->t->_('error_domain_exists')],
            -2 => ['lang',   $this->t->_('error_domain_no_lang')],
            -3 => ['name',   $this->t->_('error_sites_exists')],
            -4 => ['name',   $this->t->_('error_empty')],
            -5 => ['domain', $this->t->_('error_empty')]
        ];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();


        $phpErrors = $this->validateMaxLen($domain, 'domain', 100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($name,   'name',   32,  $statusManager) ? 1 : $phpErrors;

        if (trim($name) == '') {
            $phpErrors = 1;
            $status = $errors[-4];

            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        if (trim($domain) == '') {
            $phpErrors = 1;
            $status = $errors[-5];

            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }


        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_add(:name, :domain)',
                ['name' => $name, 'domain' => $domain]
            )->fetch()['dp_sites_add'];

            if ($resultCode > 0) {
                $addState = 'yes';
                $statusManager->fillPostWithSuccess();
            }
            else if ($resultCode < 0 ){
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'sid'    => $resultCode,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Смена названия сайта в разделе "Сайт"
     *
     * {int}    $_POST['sid']  - идентификатор сайта
     * {string} $_POST['name'] - название сайта
     *
     * @api
     */
    protected function apiSite_change()
    {
        

        $siteId = (int)$this->request->get('sid');
        $name   = mb_strtolower(trim($this->request->getPost('name')));


        $addState   = 'no';
        $phpErrors  = 0;
        $resultCode = 0;

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($name,   'name',   32,  $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['name', $this->t->_('error_sites_exists')],
            2 => ['name', $this->t->_('error_empty')],
            3 => ['name', $this->t->_('error_block')]
        ];

        if (trim($name) == '') {
            $phpErrors = 1;
            $status = $errors[2];

            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }


        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_change(:sid, :name)',
                ['sid' => $siteId, 'name' => $name]
            )->fetch()['dp_sites_change'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $statusManager->fillPostWithSuccess();
            }
            else if ($resultCode > 0 ){
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'sid'    => $resultCode,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Процедура добавления нового домена к сайту
     */
    protected function apiDomain_add()
    {
        

        $siteId = (int)$this->request->getPost('site');
        $langId = (int)$this->request->getPost('lang');
        $cross  = (int)$this->request->getPost('cross');
        $domain = mb_strtolower(trim($this->request->getPost('domain')));

        $cross = $cross ? 1 : 0;

        $phpErrors = 0;
        $addState = 'no';

        $errors = [
            1 => ['domain', $this->t->_('error_domain_exists')],
            2 => ['lang',   $this->t->_('error_domain_no_lang')],
            3 => ['site',   $this->t->_('error_domain_no_site')],
            4 => ['domain', $this->t->_('error_empty')]
        ];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($domain, 'domain', 100, $statusManager) ? 1 : $phpErrors;



        if (trim($domain) == '') {
            $phpErrors = 1;
            $status = $errors[4];

            $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
        }

        $htmlTemplate = '';

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_domain_add(:sid, :domain, :lid::smallint, :cross)',
                ['sid' => $siteId, 'domain' => $domain, 'lid' => $langId, 'cross' => $cross]
            )->fetch()['dp_sites_domain_add'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $statusManager->fillPostWithSuccess();

                $htmlTemplate = $this->view->getPartial('sites/edit_domain', [
                    'domain' => [
                        'domain_name'  => $domain,
                        'domain_cross' => $cross,
                        'site_id' => $siteId,
                        'lang_id' => $langId
                    ],
                    'langList' => [[1, 'RU'], [2, 'EN']]
                ]);

                (new SiteUtils($siteId))->generateDomainsFile();
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'fields' => $statusManager->getStatuses(),
            'html'   => $htmlTemplate
        ]);
    }

    /**
     * Удаление домена в разделе "Сайт"
     *
     * {string} $_POST['domain'] - доменное имя
     *
     * @api
     */
    protected function apiDomain_delete()
    {
        $domain = mb_strtolower(trim($this->request->getPost('domain')));

        $resultCode = (int)$this->db->query(
            'SELECT dp_sites_domain_delete(:domain)',
            ['domain' => $domain]
        )->fetch()['dp_sites_domain_delete'];

        $this->sendResponseAjax(['state' => $resultCode == 0 ? 'yes' : 'no']);
    }

    /**
     * Включение/выключение кросс-доменной авторизации у домена в разделе "Сайт"
     *
     * {int}    $_POST['site']   - идентификатор сайта
     * {int}    $_POST['cross']  - состояние кросс-доменной авторизации 0/1 (выключена/включена)
     * {string} $_POST['domain'] - доменное имя
     *
     * @api
     */
    protected function apiDomain_cross()
    {
        

        $siteId = (int)$this->request->getPost('site');
        $domain = mb_strtolower(trim($this->request->getPost('domain')));
        $cross  = (int)$this->request->getPost('cross');

        $cross = $cross ? 1 : 0;

        $this->db->query(
            'UPDATE sites_domains SET domain_cross = :cross WHERE site_id = :sid AND domain_name = :name',
            ['sid' => $siteId, 'name' => $domain, 'cross' => $cross]
        );

        $this->sendResponseAjax();
    }

    /**
     * Изменение языка домена для раздела "Мета"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int}    $_POST['lang'] - идентификатор языка
     * {string} $_POST['domain'] - доменное имя
     *
     * @api
     */
    protected function apiDomain_lang()
    {
        

        $siteId = (int)$this->request->getPost('site');
        $domain = mb_strtolower(trim($this->request->getPost('domain')));
        $lang   = (int)$this->request->getPost('lang');

        $this->db->query(
            'UPDATE sites_domains SET lang_id = :lang WHERE site_id = :sid AND domain_name = :name',
            ['sid' => $siteId, 'name' => $domain, 'lang' => $lang]
        );

        $this->sendResponseAjax();
    }

    /**
     * Редактирование SEO данных для раздела "Мета"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int} $_POST['lang'] - идентификатор языка
     *
     * {string} $_POST['title']       - Заголовок сайта
     * {string} $_POST['keywords']    - Ключевые слова
     * {string} $_POST['description'] - Описание
     *
     *
     * {string} $_POST['icon_16x16'] - .ico размером 16 на 16
     * {string} $_POST['icon_32x32'] - .ico размером 32 на 32
     * {string} $_POST['icon_48x48'] - .ico размером 48 на 48
     *
     * @api
     */
    protected function apiMeta_edit()
    {
        

        $siteId = (int)$this->request->get('site');
        $langId = (int)$this->request->getPost('lang');

        $title       = trim($this->request->getPost('title'));
        $keywords    = trim($this->request->getPost('keywords'));
        $description = trim($this->request->getPost('description'));
        $emailName   = trim($this->request->getPost('email_name'));
        $emailSignature = trim($this->request->getPost('email_signature'));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($title,       'title',      100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($keywords,    'keywords',   150, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($description, 'description',150, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($emailName,    'email_name',   64, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($emailSignature, 'email_signature',64, $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['lang',   $this->t->_('error_domain_no_lang')],
            2 => ['site',   $this->t->_('error_domain_no_site')]
        ];

        $iconsArray = [
            'icon_16x16' => '',
            'icon_32x32' => '',
            'icon_48x48' => ''
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_meta_edit(:site, :lang::smallint, :title, :keywords, :description, :email_name, :email_signature)',
                [
                    'site' => $siteId,
                    'lang' => $langId,
                    'title'       => $title,
                    'keywords'    => $keywords,
                    'description' => $description,
                    'email_name' => $emailName,
                    'email_signature' => $emailSignature
                ]
            )->fetch()['dp_sites_meta_edit'];

            $iconsArray['icon_16x16'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_16x16', 'ico');
            $iconsArray['icon_32x32'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_32x32', 'ico');
            $iconsArray['icon_48x48'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_48x48', 'ico');

            $this->generateIcoFile($siteId);

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();
                $SiteUtils = new SiteUtils($siteId);

                $SiteUtils->generateMetaFile();
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'        => $addState,
            'notification' => $notification,
            'fields'       => $statusManager->getStatuses(),
            'icons'        => $iconsArray
        ]);
    }


    /**
     * Получение языковой информации для раздела "Мета"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int} $_POST['lang'] - идентификатор языка
     *
     * @api
     */
    protected function apiMeta_lang()
    {
        

        $siteId = (int)$this->request->get('site');
        $langId = (int)$this->request->getPost('lang');

        $meta = $this->getMetaLang($siteId, $langId);

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['title', $meta['title']],
                ['keywords', $meta['keywords']],
                ['description', $meta['description']],
                ['email_name', $meta['email_name']],
                ['email_signature', $meta['email_signature']]
            ]
        ]);
    }

    /**
     * Универсальный API метод для загрузки и удаления иконок
     * - Создает временные иконки
     * - Удаляет существующие
     *
     * {int}    $_GET['site'] - идентификатор сайта
     * {string} $_GET['act']  - идентификатор выполняемого дествия
     * {string} $_GET['size'] - размер иконки вида [width]x[height]
     * {string} $_GET['pref'] - префикс в названии иконки
     * {string} $_GET['svg_color']  - цвет для замены в СВГ
     * {int}    $_GET['svg_hashed'] - флаг указывает на то, что в цвете СВГ должен быть #
     *
     * @api
     */
    protected function apiMeta_icon()
    {
        

        $act  = mb_strtolower(trim($this->request->get('act')));
        $size = mb_strtolower(trim($this->request->get('size')));
        $pref = mb_strtolower(trim($this->request->get('pref')));
        $site = (int)$this->request->get('site');

        $svgColor  = mb_strtolower(trim($this->request->get('svg_color')));
        $svgHashed = (int)$this->request->get('svg_hashed');

        list($width, $height) = explode('x', $size);

        $allowedMime = [
            'image/png'     => 'png',
            'image/jpeg'    => 'jpg',
            'image/svg+xml' => 'svg'
        ];


        if ($act == 'delete') { // Удаляем текущую иконку
            $icoName  = $pref.'_'.$size;

            // TODO: вынести в пердварительное удаление
            $icoExists = $this->db->query(
                '
                    SELECT si.icon_type 
                    FROM sites_icons AS si 
                    WHERE si.site_id = :sid AND si.icon_name = :name',
                ['sid' => $site, 'name' => $icoName]
            )->fetch();

            if ($icoExists) {
                @unlink(DIR_PUBLIC.'uploads/icos/'.$site.'_'.$icoName.'.'.$icoExists['icon_type']);

                $this->db->query(
                    'SELECT dp_sites_icon_delete(:sid, :name)',
                    ['sid' => $site, 'name' => $icoName]
                )->fetch();
            }

            $this->generateIcoFile($site);
        }
        else if (isset($_FILES['image']) && $_FILES['image']['error'] == 0){ // Загрузка нового временного файла
            if (!isset($allowedMime[$_FILES['image']['type']])) {
                $this->sendResponseAjax(['state' => 'no', 'notification' => 'Недопустимый формат']);
            }

            $fileType = $allowedMime[$_FILES['image']['type']];

            // Перехват управления загрузкой на тот случай, если загружается СВГ
            if ($fileType == 'svg') {

                // Пытаемся отчистить СВГ от мусора
                $cleanSVG  = (new Sanitizer())->sanitize(file_get_contents($_FILES['image']['tmp_name']));

                if (trim($cleanSVG) == '') {
                    $this->sendResponseAjax(['state' => 'no', 'notification' => 'Недопустимый SVG']);
                }

                // Заменям цвета в СВГ, если требуется
                if ($svgColor) {
                    $svgColor = ($svgHashed ? "#" : "").$svgColor;
                    $cleanSVG = $this->svgReplaceColors($cleanSVG, $svgColor);
                }

                $tempFileName = date('U').'_'.md5(date('U').rand(0, 1000)).'.'.$fileType;

                file_put_contents(DIR_PUBLIC.'uploads/temp/'.$tempFileName, $cleanSVG);

                $this->sendResponseAjax([
                    'state' => 'yes',
                    'image' => [
                        'src'       => '/uploads/temp/'.$tempFileName,
                        'temp_name' => $tempFileName,
                        'svg'       => $cleanSVG
                    ]
                ]);
            }

            try {
                $Image = new \Imagick($_FILES['image']['tmp_name']);
            }
            catch(\Exception $e) {
                $this->sendResponseAjax(['state' => 'no', 'notification' => 'Недопустимый формат']);
            }

            /** @noinspection PhpUndefinedVariableInspection */
            $imageInfo = $Image->identifyImage();

            $imageMime = $imageInfo['mimetype'];


            if (!isset($allowedMime[$imageMime])) {
                $this->sendResponseAjax(['state' => 'no', 'notification' => 'Недопустимый формат']);
            }

            $imageWidth  = $imageInfo['geometry']['width'];
            $imageHeight = $imageInfo['geometry']['height'];



            if ($pref == 'ogtc') {
                if ($imageWidth < $width || $imageHeight < $height) {
                    $this->sendResponseAjax(['state' => 'no', 'notification' => 'Минимальное разрешение: '.$width.'×'.$height]);
                }

                $OGTCDimensions = [
                    '600x600'  => [1,     1],
                    '800x800'  => [1,     1],
                    '1600x800' => [0.5,   2],
                    '1200x630' => [0.525, 1.91]
                ];

                $scale = $OGTCDimensions[$size];

                if ($imageWidth != $width || $imageHeight != $height) {
                    $centreX = round($imageWidth / 2);
                    $centreY = round($imageHeight / 2);

                    if ($imageHeight >= $imageWidth * $scale[0]) {
                        $cropWidth  = $imageWidth;
                        $cropHeight = $imageWidth * $scale[0];
                    }
                    else {
                        $cropWidth  = $imageHeight * $scale[1];
                        $cropHeight = $imageHeight;
                    }

                    $Image->cropImage(
                        $cropWidth,
                        $cropHeight,
                        $centreX - $cropWidth  / 2,
                        $centreY - $cropHeight / 2
                    );

                    $Image->resizeImage($width, $height,Imagick::FILTER_LANCZOS,1);
                }
            }
            else if ($imageWidth != $width || $imageHeight != $height) {
                $this->sendResponseAjax(['state' => 'no', 'notification' => 'Допустимое разрешение: '.$width.'×'.$height]);
            }

            $tempFileName = date('U').'_'.md5(date('U').rand(0, 1000)).'.'.$allowedMime[$imageMime];

            $Image->writeImage(DIR_PUBLIC.'uploads/temp/'.$tempFileName);

            //move_uploaded_file($_FILES['image']['tmp_name'], DIR_PUBLIC.'uploads/temp/'.$tempFileName);



            $this->sendResponseAjax([
                'state' => 'yes',
                'image' => [
                    'src'       => '/uploads/temp/'.$tempFileName,
                    'temp_name' => $tempFileName
                ]
            ]);
        }

        $this->sendResponseAjax(['state' => 'yes']);
    }

    /**
     * Редактирование счетчиков и верификаторов для раздела "Мета"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {string} $_POST['mailru_counter']   - иднетификатор счетчика МейлРу
     * {string} $_POST['yandex_master']    - идентификатор для подключения Яндекс.Метрика
     * {string} $_POST['google_analytics'] - идентификатор для подключения Гугл Аналитикса
     * {string} $_POST['google_publisher'] - идентификатор группы/пользователя/страницы публикатора контента
     *
     * @api
     */
    protected function apiCounter_edit()
    {
        

        $siteId = (int)$this->request->get('site');

        $mailRuCounter   = trim($this->request->getPost('mailru_counter'  ));
        $yandexMaster    = trim($this->request->getPost('yandex_master'   ));
        $googleAnalytics = trim($this->request->getPost('google_analytics'));
        $googlePublisher = trim($this->request->getPost('google_publisher'));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($mailRuCounter,   'mailru_counter',  100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($yandexMaster,    'yandex_master',   100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($googleAnalytics, 'google_analytics',100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($googlePublisher, 'google_publisher',100, $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_sites_exists')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_counters_change(:sid, :mailru, :yamaster, :analytics, :publisher)',
                [
                    'sid'       => $siteId,
                    'mailru'    => $mailRuCounter,
                    'yamaster'  => $yandexMaster,
                    'analytics' => $googleAnalytics,
                    'publisher' => $googlePublisher
                ]
            )->fetch()['dp_sites_counters_change'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'        => $addState,
            'notification' => $notification,
            'fields'       => $statusManager->getStatuses()
        ]);
    }

    /**
     * Редактирование подраздела Android для раздела "Манифест"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int} $_POST['display']     - идентификатор режима отображения дисплея
     * {int} $_POST['Orientation'] - идентификатор режима ориентации дисплея
     *
     * {string} $_POST['addr_color']  - цвет адресной строки
     * {string} $_POST['theme_color'] - цвет приложения
     *
     * {string} $_POST['url']   - стартовый урл
     * {string} $_POST['scope'] - пространство имен
     *
     * {string} $_POST['icon_192x192'] - имя временного файла с иконкой приложения
     * {string} $_POST['icon_512x512'] - имя временного файла с иконкой для экрана загрузки
     *
     * @api
     */
    protected function apiManifest_android()
    {
        

        $siteId    = (int)$this->request->get('site');
        $displayId = (int)$this->request->getPost('display');
        $orientId  = (int)$this->request->getPost('orientation');


        $addrColor  = trim(htmlspecialchars($this->request->getPost('addr_color')));
        $themeColor = trim(htmlspecialchars($this->request->getPost('theme_color')));

        $dir   = 'ltr';
        $url   = trim($this->request->getPost('url'));
        $scope = trim($this->request->getPost('scope'));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';
        $iconsArray = [];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($addrColor,  'addr_color',  32,  $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($themeColor, 'theme_color', 32,  $statusManager) ? 1 : $phpErrors;

        $phpErrors = $this->validateMaxLen($dir,   'dir',   8,   $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($url,   'url',   100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($scope, 'scope', 100, $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_domain_no_site')],
            2 => ['site',   $this->t->_('error_manifest_no_display')],
            3 => ['site',   $this->t->_('error_manifest_no_orient')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                '
                    SELECT dp_sites_manifest_android_edit(
                        :site, 
                        :display, 
                        :orientation, 
                        :scope,
                        :url, 
                        :addr_color, 
                        :theme_color, 
                        :dir
                    ) 
                ',
                [
                    'site'        => $siteId,
                    'display'     => $displayId,
                    'orientation' => $orientId,
                    'scope'       => $scope,
                    'url'         => $url,
                    'addr_color'  => $addrColor,
                    'theme_color' => $themeColor,
                    'dir'         => $dir
                ]
            )->fetch()['dp_sites_manifest_android_edit'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();

                $iconsArray['icon_192x192'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_192x192', 'android');
                $iconsArray['icon_512x512'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_512x512', 'android');
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'        => $addState,
            'notification' => $notification,
            'fields'       => $statusManager->getStatuses(),
            'icons'        => $iconsArray
        ]);
    }

    /**
     * Редактирование подраздела Microsoft для раздела "Манифест"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {string} $_POST['ms_color_tile'] - цвет плитки
     *
     * {string} $_POST['icon_310x310'] - имя временного файла с иконкой для большой плитки
     * {string} $_POST['icon_310x150'] - имя временного файла с иконкой для широкой плитки
     * {string} $_POST['icon_150x150'] - имя временного файла с иконкой для средней плитки
     * {string} $_POST['icon_70x70']   - имя временного файла с иконкой для маленькой плитки
     *
     * @api
     */
    protected function apiManifest_microsoft()
    {
        

        $siteId    = (int)$this->request->get('site');
        $tileColor = trim(htmlspecialchars($this->request->getPost('ms_color_tile')));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';
        $iconsArray = [];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($tileColor,  'ms_color_tile',32,  $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_domain_no_site')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_manifest_ms_edit(:site, :color)',
                ['site'  => $siteId, 'color' => $tileColor]
            )->fetch()['dp_sites_manifest_ms_edit'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();

                $iconsArray['icon_310x310'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_310x310', 'microsoft');
                $iconsArray['icon_310x150'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_310x150', 'microsoft');
                $iconsArray['icon_150x150'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_150x150', 'microsoft');
                $iconsArray['icon_70x70']   = $this->setSiteIcoFromTemPostField($siteId, 'icon_70x70',   'microsoft');
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'        => $addState,
            'notification' => $notification,
            'fields'       => $statusManager->getStatuses(),
            'icons'        => $iconsArray
        ]);
    }

    /**
     * Редактирование подраздела Apple для раздела "Манифест"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {string} $_POST['ios_color_addr']  - цвет адресной строки
     * {string} $_POST['ios_color_touch'] - цвет иконки тачбара
     *
     * {string} $_POST['icon_1024x1024'] - имя временного файла с иконкой
     * {string} $_POST['icon_180x180']   - имя временного файла иконки для iOS
     * {string} $_POST['icon_170x170']   - имя временного файла СВГ маски
     *
     * @api
     */
    protected function apiManifest_apple()
    {
        

        $siteId     = (int)$this->request->get('site');
        $addrColor  = trim(htmlspecialchars($this->request->getPost('ios_color_addr')));
        $touchColor = trim(htmlspecialchars($this->request->getPost('ios_color_touch')));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';
        $iconsArray = [];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($addrColor,  'ios_color_addr', 32,  $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($touchColor, 'ios_color_touch',32,  $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_domain_no_site')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                'SELECT dp_sites_manifest_ios_edit(:site, :addr, :touch)',
                ['site'  => $siteId, 'addr' => $addrColor, 'touch' => $touchColor]
            )->fetch()['dp_sites_manifest_ios_edit'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();

                $iconsArray['icon_1024x1024'] = $this->setSiteIcoFromTemPostField($siteId, 'icon_1024x1024', 'apple');
                $iconsArray['icon_180x180']   = $this->setSiteIcoFromTemPostField($siteId, 'icon_180x180',   'apple');
                $iconsArray['icon_170x170']   = $this->setSiteIcoFromTemPostField($siteId, 'icon_170x170',   'apple');

                $svgAddr = DIR_PUBLIC.'uploads/icos/'.$siteId.'_apple_170x170.svg';

                if (file_exists($svgAddr)) {
                    file_put_contents($svgAddr, $this->svgReplaceColors(file_get_contents($svgAddr), $touchColor));
                }
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'        => $addState,
            'notification' => $notification,
            'fields'       => $statusManager->getStatuses(),
            'icons'        => $iconsArray
        ]);
    }

    /**
     * Редактирование мета-данных для раздела "Манифест"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int} $_POST['lang'] - идентификатор языка
     *
     * {string} $_POST['name_long']   - Длинное название приложения
     * {string} $_POST['name_short']  - Короткое название приложения
     * {string} $_POST['description'] - Описание приложения
     *
     * @api
     */
    protected function apiManifest_meta()
    {
        

        $siteId      = (int)$this->request->get('site');
        $langId      = (int)$this->request->getPost('lang');
        $nameLong    = trim($this->request->getPost('name_long'));
        $nameShort   = trim($this->request->getPost('name_short'));
        $description = trim($this->request->getPost('description'));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($nameLong,   'name_long',   100, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($nameShort,  'name_short',  64,  $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($description,'description', 256, $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_domain_no_site')],
            2 => ['site',   $this->t->_('error_domain_no_lang')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                '
                    SELECT dp_sites_manifest_meta_edit(
                        :site, 
                        :lang::smallint,
                        :name, 
                        :name_short, 
                        :description
                    ) 
                ',
                [
                    'site'        => $siteId,
                    'lang'        => $langId,
                    'name'        => $nameLong,
                    'name_short'  => $nameShort,
                    'description' => $description
                ]
            )->fetch()['dp_sites_manifest_meta_edit'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'notification' => $notification,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Получение языковой информации для раздела "Манифест"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int} $_POST['lang'] - идентификатор языка
     *
     * @api
     */
    protected function apiManifest_lang()
    {
        

        $siteId = (int)$this->request->get('site');
        $langId = (int)$this->request->getPost('lang');

        $meta = $this->getManifestLang($siteId, $langId);

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['name_long',   $meta['name_long']],
                ['name_short',  $meta['name_short']],
                ['description', $meta['description']]
            ]
        ]);
    }

    /**
     * Изменение мета данных в разделе "OG и TC"
     *
     * {int}    $_GET['site'] - идентификатор сайта
     *
     * {int}    $_POST['lang'] - идентификатор языка
     *
     * {string} $_POST['title']       - Заголовок
     * {string} $_POST['description'] - Описание
     *
     * @api
     */
    protected function apiOgtc_meta() {
        

        $siteId      = (int)$this->request->get('site');
        $langId      = (int)$this->request->getPost('lang');
        $title       = trim($this->request->getPost('title'));
        $description = trim($this->request->getPost('description'));

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($title,       'title',        150, $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($description, 'description',  256, $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_domain_no_site')],
            2 => ['site',   $this->t->_('error_domain_no_lang')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                '
                    SELECT dp_sites_manifest_ogtc_meta(
                        :site, 
                        :lang::smallint,
                        :title, 
                        :description
                    ) 
                ',
                [
                    'site'  => $siteId,
                    'lang'  => $langId,
                    'title' => $title,
                    'description' => $description
                ]
            )->fetch()['dp_sites_manifest_ogtc_meta'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'notification' => $notification,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Изменение настроек в разделе "OG и TC"
     *
     * {int}    $_GET['site'] - идентификатор сайта
     *
     * {string} $_POST['og_site']    - идентификатор сайта для опен графа
     * {string} $_POST['tc_account'] - название аккаунта в твиттере
     * {int}    $_POST['tc_mode']    - режим отображения твиттер карда 0/1 (маленький/большой)
     *
     * @api
     */
    protected function apiOgtc_edit() {
        

        $siteId    = (int)$this->request->get('site');
        $ogSite    = trim($this->request->getPost('og_site'));
        $tcAccount = trim($this->request->getPost('tc_account'));
        $tcMode    = (int)$this->request->getPost('tc_mode') ? 1 : 0;

        $phpErrors = 0;
        $addState  = 'no';
        $notification = '';

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $phpErrors = $this->validateMaxLen($ogSite,    'og_site',    100,  $statusManager) ? 1 : $phpErrors;
        $phpErrors = $this->validateMaxLen($tcAccount, 'tc_account', 256,  $statusManager) ? 1 : $phpErrors;

        $errors = [
            1 => ['site',   $this->t->_('error_domain_no_site')]
        ];

        if (!$phpErrors) {
            $resultCode = (int)$this->db->query(
                '
                    SELECT dp_sites_manifest_ogtc_edit(
                        :site, 
                        :og_site,
                        :tc_account,
                        :tc_mode
                    ) 
                ',
                [
                    'site'       => $siteId,
                    'og_site'    => $ogSite,
                    'tc_account' => $tcAccount,
                    'tc_mode'    => $tcMode
                ]
            )->fetch()['dp_sites_manifest_ogtc_edit'];

            if ($resultCode == 0) {
                $addState = 'yes';
                $notification = 'Данные сохранены';
                $statusManager->fillPostWithClear();
            }
            else {
                $status = $errors[$resultCode];

                $statusManager->setStatus($status[0], AjaxFormResponse::ERROR, $status[1]);
            }
        }

        $this->sendResponseAjax([
            'state'  => $addState,
            'notification' => $notification,
            'fields' => $statusManager->getStatuses()
        ]);
    }

    /**
     * Изменение иконки в разделе "OG и TC"
     *
     * {int}    $_GET['site'] - идентификатор сайта
     * {string} $_GET['size'] - размер иконки: small/large
     * {string} $_GET['type'] - идентификатор типа: twitter/facebook
     *
     * {string} $_POST['icon_XXXXXXX'] - имя временного файла с иконкой
     *
     * @api
     */
    protected function apiOgtc_demo()
    {
        

        $siteId = (int)$this->request->get('site');
        $size   = trim(strtolower($this->request->get('size')));
        $type   = trim(strtolower($this->request->get('type')));

        $sizes = [
            'twitter'  => ['small' => '600x600', 'large' => '1600x800'],
            'facebook' => ['small' => '800x800', 'large' => '1200x630']
        ];

        $imageSize = $sizes[$type][$size];

        $iconsArray = [];

        $statusManager = (new AjaxFormResponse())->fillPostWithClear();

        $iconsArray['icon_'.$imageSize] = $this->setSiteIcoFromTemPostField($siteId, 'icon_'.$imageSize, 'ogtc');

        $this->sendResponseAjax([
            'state'        => 'yes',
            'notification' => 'Данные сохранены',
            'fields'       => $statusManager->getStatuses(),
            'icons'        => $iconsArray
        ]);
    }

    /**
     * Получение языковой информации для раздела "OG и TC"
     *
     * {int} $_GET['site'] - идентификатор сайта
     *
     * {int} $_POST['lang'] - идентификатор языка
     *
     * @api
     */
    protected function apiOgtc_lang()
    {
        

        $siteId = (int)$this->request->get('site');
        $langId = (int)$this->request->getPost('lang');

        $meta = $this->getOgtcLang($siteId, $langId);

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['title',        $meta['title']       ],
                ['description',  $meta['description'] ]
            ]
        ]);
    }

    /**
     * Подключение/отключение контроллеров с проверкой файлов/папок для раздела "Контроллеры"
     *
     * {int}    $_POST['site_id']       - идентификатор сайта
     * {int}    $_POST['controller_id'] - идентификатор контроллера
     * {string} $_POST['action']        - ключ действия
     *
     * @api
     */
    protected function apiController_toggle()
    {
        

        $siteId       = (int)$this->request->getPost('site_id');
        $controllerId = (int)$this->request->getPost('controller_id');
        $action       = trim($this->request->getPost('action'));

        $this->db->query(
            'SELECT dp_sites_controllers_'.$action.'(:sid, :cid)',
            ['sid' => $siteId, 'cid' => $controllerId]
        );




        $selected = $this->db->query(
            'SELECT * FROM dp_sites_controllers_activated(:sid, :lid) WHERE controller_id = :cid',
            ['sid' => $siteId, 'lid' => $this->user->getLangId(), 'cid' => $controllerId]
        );

        $row = $selected->fetch();

        if ((int)$row['active'] != 0) {
            $row = array_merge(
                $row,
                $this->checkControllerActivation($row, $this->db->query(
                    '
                        SELECT site_id, site_name 
                        FROM sites 
                        WHERE site_id=:sid
                    ',
                    ['sid' => $siteId]
                )->fetch())
            );
        }

        $this->sendResponseAjax($this->view->getPartial('sites/partials/controller_item', $row));
    }

    protected function apiResources_package_attach()
    {
        

        $siteId    = (int)$this->request->getPost('site_id');
        $packageId = (int)$this->request->getPost('package_id');
        $attach    = (int)$this->request->getPost('attach') ? 1 : 0;

        $sqlQueries = [
            'SELECT dp_resources_package_remove_site (:pid, :sid)',
            'SELECT dp_resources_package_add_site (:pid, :sid)'
        ];

        $this->db->query(
            $sqlQueries[$attach],
            [
                'pid' => $packageId,
                'sid' => $siteId
            ]
        );

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    protected function apiResources_file_exclude()
    {
        

        $siteId     = (int)$this->request->getPost('site_id');
        $resourceId = (int)$this->request->getPost('resource_id');
        $exclude    = (int)$this->request->getPost('exclude') ? 1 : 0;

        $sqlQueries = [
            'SELECT dp_resources_exclude_remove_site (:rid, :sid)',
            'SELECT dp_resources_exclude_add_site (:rid, :sid)'
        ];


        $this->db->query(
            $sqlQueries[$exclude],
            [
                'rid' => $resourceId,
                'sid' => $siteId
            ]
        );

        $this->sendResponseAjax([
            'state'  => 'yes'
        ]);
    }

    protected function apiRoutes_default_switch()
    {
        
        $siteId = (int)$this->request->getPost('site_id');

        $this->db->begin();
        $this->db->query(
            'SELECT dp_sites_route_default_switch(:sid, :enb)',
            [
                'sid' => $siteId,
                'enb' => (int)$this->request->getPost('enabled') ? 1 : 0
            ]
        )->fetch();
        $this->db->commit();

        (new SiteUtils($siteId))->generateConfigFile();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiRoutes_sort()
    {
        $orderList = json_decode($this->request->get('order'), true);

        $this->db->begin();

        for($a = 0, $len = sizeof($orderList); $a < $len; $a++) {
            $this->db->query(
                '
                    UPDATE sites_routes 
                    SET route_order = :order 
                    WHERE route_id = :rid
                ',
                [
                    'rid'   => (int)$orderList[$a],
                    'order' => $a
                ]
            );
        }

        $this->db->commit();

        (new SiteUtils(
            (int)$this->request->getPost('site_id')
        ))->generateRoutesFile();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiRoutes_delete()
    {
        $Filter  = new Filter();

        $siteId  = (int)$this->request->getPost('site_id');
        $routeId = (int)$this->request->getPost('route_id');

        $this->db->begin();
        $this->db->query('DELETE FROM sites_routes WHERE route_id = :rid', ['rid' => $routeId]);
        $this->db->commit();

        (new SiteUtils($siteId))->generateRoutesFile();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiRoutes_switch()
    {
        $Filter  = new Filter();

        $siteId     = (int)$this->request->getPost('site_id');
        $routeId    = (int)$this->request->getPost('route_id');
        $isDisabled = (int)$this->request->getPost('disabled');

        $this->db->begin();
        $this->db->query(
            'SELECT dp_sites_route_switch(:rid, :dis)',
            ['rid' => $routeId, 'dis' => $isDisabled]
        );
        $this->db->commit();

        (new SiteUtils($siteId))->generateRoutesFile();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiRoutes_add()
    {
        

        $siteId = (int)$this->request->getPost('site_id');

        $this->db->begin();

        $routeId = (int)$this->db->query(
            'SELECT dp_sites_route_add(:sid::INTEGER) AS route_id',
            ['sid' => $siteId]
        )->fetch()['route_id'];

        $this->db->commit();

        if ($routeId < 0) {
            $this->sendResponseAjax(['state' => 'no', 'message' => 'Уже существуют новый шаблон']);
        }

        $this->sendResponseAjax(['state' => 'yes', 'route' => $this->view->getPartial(
            'sites/partials/route_tr',
            $this->db->query(
                'SELECT * FROM sites_routes WHERE route_id = :rid',
                ['rid' => $routeId]
            )->fetch()
        )]);
    }

    protected function apiRoutes_save()
    {
        

        $siteId = (int)$this->request->getPost('site_id');
        $routeId = (int)$this->request->getPost('route_id');
        $pattern = trim($this->request->getPost('pattern'));
        $json    = json_decode(trim($this->request->getPost('json')), true);

        $errors = 0;

        $errorsStatuses = [
            'pattern' => '',
            'json'    => ''
        ];

        if ($pattern == '') {
            $errors += 1;

            $errorsStatuses['pattern'] = 'Необходимо указать шаблон';
        }

        if ($json == null) {
            $errors += 1;

            $errorsStatuses['json'] = 'Введен не валидный JSON';
        }

        $message = '';


        if ($errors == 0) {
            $states = [
                [0, ''],
                [1, 'Указанного маршрута не существует'],
                [1, 'Такой маршрут уже существует']
            ];

            $this->db->begin();

            $result = $states[(int)$this->db->query(
                'SELECT dp_sites_route_save(:rid::INTEGER, :pattern, :json::JSON) AS result',
                [
                    'rid'     => $routeId,
                    'pattern' => $pattern,
                    'json'    => json_encode($json)
                ]
            )->fetch()['result']];

            $this->db->commit();

            $message = $result[1];
            $errors += $result[0];
        }

        (new SiteUtils($siteId))->generateRoutesFile();

        $this->sendResponseAjax([
            'state'    => $errors ? 'no' : 'yes',
            'errors'   => $errors,
            'statuses' => $errorsStatuses,
            'message'  => $message
        ]);
    }

    protected function apiMin_css() {
        $siteRow = $this->getSiteForMinimization('css');

        $fileVer  = $siteRow['site_min_ver_css'];
        $fileName = $siteRow['site_name'].'_'.$fileVer;

        $delFileName = $siteRow['site_name'].'_'.($fileVer - 1);

        $resources = $this->db->query(
            'select * from dp_resources_get_list_css_compiled(:id)',
            ['id' => $siteRow['site_id']]
        );

        $cssFile = '';

        while($row = $resources->fetch()) {
            if (!file_exists(DIR_PUBLIC.$row['resource_dir'].$row['resource_name'])) {
                continue;
            }

            $cssFile .= \Minify_CSSmin::minify(
                file_get_contents(DIR_PUBLIC.$row['resource_dir'].$row['resource_name']),
                ['prependRelativePath' => '/'.$row['resource_dir']]
            );
        }

        $appResources = $this->db->query(
            '
                select 
                    r.resource_dir,
                    r.resource_name   
                from 
                    resources as r, 
                    resources_to_apps as ra 
                where 
                    r.type_id = 1 AND 
                    r.resource_id = ra.resource_id   
                order by 
                    ra.app_id ASC,
                    r.resource_order ASC     
            
            '
        );

        while($row = $appResources->fetch()) {
            if (!file_exists(DIR_PUBLIC.$row['resource_dir'].$row['resource_name'])) {
                continue;
            }

            $cssFile .= \Minify_CSSmin::minify(
                file_get_contents(DIR_PUBLIC.$row['resource_dir'].$row['resource_name']),
                ['prependRelativePath' => '/'.$row['resource_dir']]
            );
        }


        file_put_contents(DIR_PUBLIC.'min/'.$fileName.'.css', $cssFile);
        file_put_contents(DIR_PUBLIC.'min/'.$fileName.'.css.gz', gzencode($cssFile, 9));

        @unlink(DIR_PUBLIC.'min/'.$delFileName.'.css');
        @unlink(DIR_PUBLIC.'min/'.$delFileName.'.css.gz');

        (new SiteUtils(0, $siteRow))->generateConfigFile();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiMin_js() {
        (new SiteUtils(0, $this->getSiteForMinimization('js')))->generateConfigFile();
        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiMin_templates() {
        (new SiteUtils(0, $this->getSiteForMinimization('templates')))->generateConfigFile();

        $files = glob(DIR_CACHE.'templates/backend/partial/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }

        $files = glob(DIR_CACHE.'templates/frontend/partial/*'); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiMin_compress() {
        

        $siteId  = (int)$this->request->getPost('site_id');
        $enabled = (int)$this->request->getPost('enabled');

        $this->db->query(
            'UPDATE sites SET site_min_enabled = :enb WHERE site_id = :sid',
            [
                'sid' => $siteId,
                'enb' => $enabled
            ]
        );

        (new SiteUtils($siteId))->generateConfigFile();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    //------------------------------------------------------------------------------------------------------------------
    // PRIVATE
    //------------------------------------------------------------------------------------------------------------------


    private function getSiteForMinimization($fileType) {
        $fileType = mb_strtolower($fileType);

        if ($fileType != 'js' && $fileType != 'css' && $fileType != 'templates') {
            $this->sendResponseAjax(['state' => 'no']);
        }

        $siteId = (int)$this->request->getPost('site_id');

        $siteRow = $this->db->query(
            'SELECT * FROM sites WHERE site_id = :sid', ['sid' => $siteId]
        )->fetch();

        if (!$siteRow) {
            $this->sendResponseAjax(['state' => 'no']);
        }

        $rowField = 'site_min_ver_'.$fileType;

        $this->db->query(
            'UPDATE sites SET '.$rowField.' = '.$rowField.' + 1 WHERE site_id = :sid',
            ['sid' => $siteId]
        );

        $siteRow[$rowField] += 1;

        return $siteRow;
    }

    /**
     * Генерация составного изображения .ico в тех размерах, которые доступны в бае данных
     *
     * @param int $siteId
     */
    private function generateIcoFile($siteId) {
        //todo: Запрос вынести
        $icons = $this->db->query(
            '
                SELECT 
                    si.site_id || \'_\' || si.icon_name || \'.\' || si.icon_type AS file_name,
                    si.icon_width,
                    si.icon_height
                FROM sites_icons AS si
                WHERE
                    si.site_id = :sid AND 
                    si.icon_name LIKE \'ico_%\'                    
            ',
            ['sid' => $siteId]
        );
        $ico_lib = new PhpIco();


        while($row = $icons->fetch()) {
            $ico_lib->add_image(
                DIR_PUBLIC.'uploads/icos/'.$row['file_name'],
                [[(int)$row['icon_width'], (int)$row['icon_height']]]
            );

        }

        $ico_lib->save_ico( DIR_PUBLIC.'uploads/icos/'.$siteId.'_ico_result.ico' );
    }

    /**
     * Получение языковых данных для раздела "Мета"
     *
     * @param int $siteId - идентификатор сайта
     * @param int $langId - идентификатор языка
     * @return array
     */
    private function getMetaLang($siteId, $langId) {
        $return = [
            'title'       => '',
            'keywords'    => '',
            'description' => '',
            'email_name' => '',
            'email_signature' => ''
        ];

        $meta = $this->db->query(
            '
                SELECT 
                    sm.meta_title       AS title,
                    sm.meta_keywords    AS keywords,
                    sm.meta_description AS description,
                    sm.email_name,
                    sm.email_signature
                FROM sites_meta AS sm 
                WHERE 
                    sm.site_id = :sid AND 
                    sm.lang_id = CAST(:lang AS SMALLINT)
            ',
            ['sid' => $siteId, 'lang' => $langId]
        )->fetch();

        return $meta ? $meta : $return;
    }

    /**
     * Получение языковых данных для раздела "Манифест"
     *
     * @param int $siteId - идентификатор сайта
     * @param int $langId - идентификатор языка
     * @return array
     */
    private function getManifestLang($siteId, $langId) {
        $return = [
            'name_long'   => '',
            'name_short'  => '',
            'description' => ''
        ];

        $meta = $this->db->query(
            '
                SELECT 
                    smm.manifest_name        AS name_long,
                    smm.manifest_name_short  AS name_short,
                    smm.manifest_description AS description
                FROM sites_manifests_meta AS smm 
                WHERE 
                    smm.site_id = :sid AND 
                    smm.lang_id = CAST(:lang AS SMALLINT)
            ',
            ['sid' => $siteId, 'lang' => $langId]
        )->fetch();

        return $meta ? $meta : $return;
    }

    /**
     * Получение языковых данных для раздела "OG и TC"
     *
     * @param int $siteId - идентификатор сайта
     * @param int $langId - идентификатор языка
     * @return array
     */
    private function getOgtcLang($siteId, $langId) {
        $return = [
            'title'        => '',
            'description'  => ''
        ];

        $meta = $this->db->query(
            '
                SELECT 
                    smm.ogtc_title       AS title,
                    smm.ogtc_description AS description
                FROM sites_manifests_meta AS smm 
                WHERE 
                    smm.site_id = :sid AND 
                    smm.lang_id = CAST(:lang AS SMALLINT)
            ',
            ['sid' => $siteId, 'lang' => $langId]
        )->fetch();

        return $meta ? $meta : $return;
    }

    /**
     * @param int    $siteId - идентификатор сайта
     * @param string $action - URI по которому надо загружать иконку или делать другие дествия
     * @param string $iconHtmlId - идентификатор иконки для HTML
     * @param string $iconPref - префикс иконки в названии
     * @param array  $iconsResolutions - список размеров иконок
     * @param string $slavePref - префикс для jQuery селектора объекта, который необходим для демонстрации иконки
     * @param string $orderW - сортировка по ширине
     * @param string $orderH - сортировка по высоте
     * @param int    $groupFrom - индекс после которого надо группировать последующие иконки
     * @param array  $addFields - специальные поля, которые надо добавить к массивам, описывающих иконки
     *                            индекс в массиве соотвествует индексу в массиве иконок
     * @return array
     */
    private function getSiteIcons($siteId, $action, $iconHtmlId, $iconPref, $iconsResolutions, $slavePref = '', $orderW = 'ASC', $orderH = 'ASC', $groupFrom = -1, $addFields = []) {
        $order = 'ORDER BY si.icon_width  '.$orderW.', si.icon_height '.$orderH;

        $icons = $this->db->query(
            '
                SELECT 
                    si.site_id || \'_\' || si.icon_name || \'.\' || si.icon_type AS file_name,
                    si.icon_width || \'x\' || si.icon_height AS icon_size
                FROM sites_icons AS si
                WHERE
                    si.site_id = :sid AND 
                    si.icon_name LIKE \''.$iconPref.'_%\'                        
            '.$order,
            ['sid' => $siteId]
        );

        $iconsList = [];

        while($row = $icons->fetch()) {
            $iconsList[$row['icon_size']] = '/uploads/icos/'.$row['file_name'].'?cc='.date('U');
        }

        $actionUri   = '/sites/api/'.$action.'?site='.$siteId.'&pref='.$iconPref.'&size=';
        $imgDef      = '';
        $resultIcons = [];

        for($i = 0, $len = sizeof($iconsResolutions); $i < $len; $i++) {
            $res = $iconsResolutions[$i];

            list($width, $height) = explode('x', $res);

            $icon = [
                'id'     => $iconHtmlId.'_'.$res,
                'name'   => 'icon_'.$res,
                'width'  => $width,
                'height' => $height,
                'action' => $actionUri.$res,
                'imageSrc' => isset($iconsList[$res]) ? $iconsList[$res] : $imgDef,
                'imageDefault' => $imgDef
            ];

            // Прикрепляем дополнительные поля к массиву иконки, в том случае, если это требуется
            if (isset($addFields[$i])) {
                $icon = array_merge($icon, $addFields[$i]);
            }

            if ($slavePref) {
                $icon['imageSlave'] = $slavePref.'_'.$res;
            }

            // Группировка иконок
            if ($groupFrom > -1 && $groupFrom <= $i) {
                if ($i == $groupFrom) { // Если индекс группы совпал с текущим индексом иконки
                    $resultIcons[] = []; //Создаем массив для группы
                }

                $resultIcons[$groupFrom][] = $icon; // Добавляем иконку в группу
            }
            else {
                $resultIcons[] = $icon;
            }
        }

        return $resultIcons;
    }

    /**
     * Проверка на превышение максимальной длинны с добавление статуса ошибки, если превышение произошло
     *
     * @param string $value - проверяемая строка
     * @param string $fieldName - название проверяемого ПОСТ поля
     * @param int    $maxLen - максимальная длинна
     * @param AjaxFormResponse $statusManager - ссылка на контроллер статусов формы
     * @return int
     */
    private function validateMaxLen($value, $fieldName, $maxLen, AjaxFormResponse &$statusManager) {
        $toReturn = 0;

        if ($this->validateMaxStrLen($value, $maxLen)) {
            $toReturn = 1;

            $statusManager->setStatus(
                $fieldName,
                AjaxFormResponse::ERROR,
                $this->t->_('error_len', ['len' => $maxLen])
            );
        }

        return $toReturn;
    }

    /**
     * Проверка на превышение максимальной длинны
     *
     * @param string $str - проверяемая строка
     * @param int $maxLen - максимальная длинна
     * @return bool
     */
    private function validateMaxStrLen($str, $maxLen) {
        return mb_strlen($str) > $maxLen;
    }


    /**
     * Создание иконки для сайта, источником служит временная иконка, которая была создана ранее
     * Информация по иконке сохраняется в БД, с предварительным удалением старой версии, если она существует
     *
     * @param int    $siteId    - идентификатор сайта
     * @param string $postField - название POST поля, где хранится название временного файла с иконкой
     * @param string $iconPref  - префикс иконки
     * @return string - конечный адрес созданной иконки
     */
    private function setSiteIcoFromTemPostField($siteId, $postField, $iconPref) {
        

        $size = explode('_', $postField)[1];

        $tempName = trim($this->request->getPost($postField));

        if ($tempName == '') {
            return '';
        }

        $fileType = explode('.', $tempName)[1];
        $icoName  = $iconPref.'_'.$size;

        // Если загружается СВГ, ставим размеры 170х170
        if ($fileType == 'svg') {
            $imageInfo = ['geometry' => ['width' => 170, 'height' => 170]];
        }
        else {
            $imageInfo = ( new \Imagick(DIR_PUBLIC.'uploads/temp/'.$tempName))->identifyImage();
        }


        // TODO: вынести в пердварительное удаление
        $icoExists = $this->db->query(
            '
                    SELECT si.icon_name 
                    FROM sites_icons AS si 
                    WHERE si.site_id = :sid AND si.icon_name = :name',
            ['sid' => $siteId, 'name' => $icoName]
        )->fetch();

        if ($icoExists) {
            @unlink(DIR_PUBLIC.'uploads/icos/'.$siteId.'_'.$icoName.'.'.$icoExists['icon_name']);
        }

        $result = (int)$this->db->query(
            'SELECT dp_sites_icon_add(:sid, :name, :type, :width, :height)',
            [
                'sid'    => $siteId,
                'name'   => $icoName,
                'type'   => $fileType,
                'width'  => $imageInfo['geometry']['width'],
                'height' => $imageInfo['geometry']['height']
            ]
        )->fetch()['dp_sites_icon_add'];

        if (!$result) {
            $tempAddr = DIR_PUBLIC.'uploads/temp/'.$tempName;

            file_put_contents(
                DIR_PUBLIC.'uploads/icos/'.$siteId.'_'.$icoName.'.'.$fileType,
                file_get_contents($tempAddr)
            );

            @unlink($tempAddr);

            return '/uploads/icos/'.$siteId.'_'.$icoName.'.'.$fileType;
        }

        return '';
    }

    /**
     *  Замена во входящем СВГ тексте всех филов и сроуков на указанный цвет, кроме тех, где указано none
     *
     * @param string $svg   - СВГ, в котором надо перекрасить эллементы
     * @param string $color - цвет для замены
     * @return mixed
     */
    private function svgReplaceColors($svg, $color) {
        // Замена аттрибутов
        $svg = preg_replace('/(stroke:\s*(((?!none).)*)\s*;)+/Umiu', 'stroke:'.$color.';', $svg);
        $svg = preg_replace('/(fill:\s*(((?!none).)*)\s*;)+/Umiu',   'fill:'.$color.';',   $svg);

        // Замена стайлов
        $svg = preg_replace('/(stroke="(((?!none).)*)")+/Umiu', 'stroke="'.$color.'"', $svg);
        $svg = preg_replace('/(fill="(((?!none).)*)")+/Umiu',   'fill="'.$color.'"',   $svg);

        return $svg;
    }


    //TODO: вынести вкуданибудь
    private function getActionLabel($title, $icon, $bg, $data = '', $classes = '', $tag = 'div', $classesExp = '') {
        return '<div class="elz expSTR right marRSmall'.$classesExp.'">
                     <'.$tag.' class="elz elzCLSlabel small elzPLT '.$classes.'" data-bg="'.$bg.'" data-fn="white" title="'.$title.'" '.$data.'>
                         <i class="elz elzIc '.$icon.'"></i>
                     </'.$tag.'>
                 </div>';
    }
}