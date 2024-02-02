<?php
namespace Core\Handler;

use Core\Controller\HttpDomain;
use Phalcon\Di\Injectable;

/**
 *
 * @property \Core\Handler\HistoryApi hapi
 * @property \Core\Handler\OpenGraph openGraph
 * @property \Core\Handler\TwitterCards twitterCards
 * @property \Core\Handler\User user
 * @property \Phalcon\Db\Adapter\Pdo\Postgresql db
 */
class Site extends Injectable
{
    private static $instance = null;

    private $data = [
        'id'   => 0,
        'dir'  => '',
        'name' => '',
        'lang' => 'ru',
        'lang_id' => 1,
        'min_enabled' => 0,
        'min_ver_css' => 0,
        'min_ver_js'  => 0,
        'min_ver_tmp' => 0,
        'meta' => [
            'title'       => '',
            'keywords'    => '',
            'description' => ''
        ],
        'email' => [
            'name' => '',
            'signature' => ''
        ],
        'html' => [

        ]
    ];

    private $siteConfig;
    private $siteRoutes = [];






    public function __construct() {
        if (self::$instance) {
            return self::$instance;
        }

        self::$instance = $this;

        return $this;
    }

    public function configureSiteData(HttpDomain $DomainHandler) {
        $domainName = $DomainHandler->getName();
        $domainLang = $DomainHandler->getLangName();


        $this->data['lang'] = $domainLang;
        $this->data['lang_id'] = $DomainHandler->getLangId();

        $siteConfig = require_once(DIR_CACHE_SITES.$domainName.'.php');
        $siteMeta   = require_once(DIR_CACHE_SITES.$domainName.'_meta_'.$domainLang.'.php');

        $emailName = @$siteMeta['email_name'];
        $emailSignature = @$siteMeta['email_signature'];

        MailSender::setEmailFrom( 'noreply@'.$_SERVER['HTTP_HOST'], $emailName, $emailSignature);
        MailSender::setEmailReply('noreply@'.$_SERVER['HTTP_HOST'], $emailName, $emailSignature);

        $siteId = $siteConfig['id'];

        $this->data['html'] = require_once(DIR_CACHE_SITES.$siteId.'_html_meta_'.$domainLang.'.php');

        $siteName = strtolower($siteConfig['name']);

        $this->data['id']   = $siteId;
        $this->data['dir']  = DIR_SITE.$siteName.'/';
        $this->data['name'] = $siteName;

        $this->data['min_enabled'] = $siteConfig['min']['enabled'];
        $this->data['min_ver_css'] = $siteConfig['min']['ver_css'];
        $this->data['min_ver_js' ] = $siteConfig['min']['ver_js' ];
        $this->data['min_ver_tmp'] = $siteConfig['min']['ver_tmp'];

        $this->data['meta'] = $siteMeta['meta'];

        $this->data['email']['name'] = $emailName;
        $this->data['email']['signature'] = $emailSignature;

        $this->siteConfig = $siteConfig;



        $routesFileName = DIR_CACHE_SITES.$siteConfig['id'].'_routes.php';

        if (file_exists($routesFileName)) {
            $this->siteRoutes = require_once($routesFileName);
        }
    }




    public function setMetaTitle($title) {
        $this->data['meta']['title'] = $title;
    }

    public function setMetaKeywords($keywords) {
        $this->data['meta']['keywords'] = $keywords;
    }

    public function setMetaDescription($description) {
        $this->data['meta']['description'] = $description;
    }

    public function useSeo($seoId, $contentTitle = '')
    {
        $imgDir = '/uploads/images/';

        $seoRow = $this->db->query(
            'SELECT * FROM cnt_seo_list_lang WHERE seo_id = :sid AND lang_id = :lid',
            [
                'sid' => $seoId,
                'lid' => $this->user->getLangId()
            ]
        )->fetch();


        if (!$seoRow) {
            return;
        }

        $twitterCards = $this->twitterCards;
        $openGraph    = $this->openGraph;

        if ($seoRow['tw_lr_image_name'] != '') {
            $twitterCards->setCardSummaryLarge();
            $twitterCards->setImage(
                $imgDir.$seoRow['tw_lr_image_name'].'_'.$seoRow['tw_lr_image_id'].'.'.$seoRow['tw_lr_image_type']
            );
        }
        else if ($seoRow['tw_sm_image_name'] != '') {
            $twitterCards->setCardSummary();
            $twitterCards->setImage(
                $imgDir.$seoRow['tw_sm_image_name'].'_'.$seoRow['tw_sm_image_id'].'.'.$seoRow['tw_sm_image_type']
            );
        }

        if ($seoRow['seo_ogtc_title']) {
            $twitterCards->setTitle($seoRow['seo_ogtc_title']);
            $openGraph->setTitle($seoRow['seo_ogtc_title']);
        }

        if ($seoRow['fb_lr_image_name'] != '') {
            $openGraph->setImage(
                $imgDir.$seoRow['fb_lr_image_name'].'_'.$seoRow['fb_lr_image_id'].'.'.$seoRow['fb_lr_image_type'],
                1200,
                630
            );
        }
        else if ($seoRow['fb_sm_image_name'] != ''){
            $openGraph->setImage(
                $imgDir.$seoRow['fb_sm_image_name'].'_'.$seoRow['fb_sm_image_id'].'.'.$seoRow['fb_sm_image_type'],
                600,
                600
            );
        }

        if ($seoRow['seo_ogtc_description']) {
            $twitterCards->setDescription($seoRow['seo_ogtc_description']);
            $openGraph->setDescription($seoRow['seo_ogtc_description']);
        }

        if ($seoRow['seo_meta_title'] != '' || $contentTitle != '') {
            $this->setMetaTitle($seoRow['seo_meta_title'] != '' ? $seoRow['seo_meta_title'] : $contentTitle);
        }

        if ($seoRow['seo_meta_keywords'] != '') {
            $this->setMetaKeywords($seoRow['seo_meta_keywords']);
        }

        if ($seoRow['seo_meta_description'] != '') {
            $this->setMetaDescription($seoRow['seo_meta_description']);
        }
    }

    public function getId() {
        return $this->data['id'];
    }

    public function getLangName() {
        return $this->data['lang'];
    }

    public function getLangId() {
        return $this->data['lang_id'];
    }

    public function getDir() {
        return $this->data['dir'];
    }

    public function getVerJS() {
        return $this->data['min_ver_js'];
    }

    public function getVerCSS() {
        return $this->data['min_ver_css'];
    }

    public function getModuleName() {
        return $this->siteConfig['name'];
    }

    public function getModuleRegistrationData() {
        return [$this->siteConfig['name'] => $this->siteConfig['module']];
    }

    public function getRoutes() {
        return $this->siteRoutes;
    }

    public function getTemplatesVersion() {
        return $this->data['min_ver_tmp'];
    }

    public function getJavascriptVersion() {
        return $this->data['min_ver_js'];
    }


    public function getMeta() {
        return $this->data['meta'];
    }

    public function getMetaHtml() {
        return $this->data['html'];
    }

    public function isDefaultRouterEnabled() {
        return $this->siteConfig['default_router'] === 1;
    }

    public function isMinificationEnabled() {
        return $this->data['min_enabled'] == 1 && $this->user->isDevCompress();
    }

    public function getResourcesCSSHtml() {
        $cssFile = '';

        if ($this->data['min_enabled'] && $this->user->isDevCompress()) {
            $cssFile = '<link href="/min/'.$this->data['name'].'_'.$this->data['min_ver_css'].'.css" rel="stylesheet" type="text/css" />';
        }
        else {
            $id = $this->data['id'];

            $resources = $this->db->query(
                'select * from dp_resources_get_list_css_compiled(:id)',
                ['id' => $id]
            );

            while($row = $resources->fetch()) {
                $cssFile .= '<link href="/'.$row['resource_dir'].$row['resource_name'].'" rel="stylesheet" type="text/css" />
';
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
                $cssFile .= '<link href="/'.$row['resource_dir'].$row['resource_name'].'" rel="stylesheet" type="text/css" />
';
            }
        }



        return $cssFile;
    }

    public function getResourcesJSHtml() {
        $id = $this->data['id'];


        if ($this->data['min_enabled'] && $this->user->isDevCompress()) {
            $javascriptFile = '<script src="/min/'.$this->data['name'].'/'.$this->data['name'].'_'.$this->data['min_ver_js'].'_'.$this->getLangName().'.js"></script>';
        }
        else {
            $resources = $this->db->query(
                'select * from dp_resources_get_list_js_compiled(:id)',
                ['id' => $id]
            );

            $javascriptFile = '';

            while($row = $resources->fetch()) {

                $row['resource_dir'] = preg_replace("/(^js\/)*/miu", "", $row['resource_dir']);
                $row['resource_name'] = preg_replace("/(\.js$)*/miu", "", $row['resource_name']);

                $javascriptFile .= '<script src="/api/utils/js/lang?js='.$row['resource_dir'].$row['resource_name'].'"></script>
';
                //' '.file_get_contents(DIR_PUBLIC.$row['filename']);
            }

        }


        return $javascriptFile;
    }
}