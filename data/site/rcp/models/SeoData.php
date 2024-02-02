<?
namespace Site\Rcp\Models;

use Phalcon\Di\Injectable;
use Phalcon\Translate\Adapter\NativeArray;

class SeoData extends Injectable
{
    private $langId  = 0;
    private $langTxt;

    private $ogtcImages = [
        ['Twitter',  'Small', ''],
        ['Facebook', 'Small', ''],
        ['Twitter',  'Large', ''],
        ['Facebook', 'Large', '']
    ];

    private $ogtcImagesMap;

    private $ogtcMeta = [
        'id' => 0,
        'title'       => '',
        'keywords'    => '',
        'description' => '',
        'ogtc_title'  => '',
        'ogtc_descr'  => ''
    ];

    public function __construct($seoId, $langId)
    {
        $langRow = $this->db->query(
            'SELECT * FROM languages WHERE lang_id = :lid',
            ['lid' => $langId]
        )->fetch();

        if (!$langRow) {
            trigger_error('no such language in SeoData Class', E_ERROR);
        }

        $this->langId = $langId;

        $this->langTxt = new NativeArray([
            'content' => require_once(__DIR__.'/../langs/0modules/SeoData_'.$langRow['lang_name'].'.php')
        ]);

        for($a = 0, $len = sizeof($this->ogtcImages); $a < $len; $a++) {
            $this->ogtcImages[$a][2] = $this->langTxt->_('ogtc_label'.$a);
        }

        $seoRow = $this->db->query(
            'SELECT * FROM cnt_seo_list_lang WHERE seo_id = :sid AND lang_id = :lid',
            [
                'sid' => $seoId,
                'lid' => $langId
            ]
        )->fetch();

        $twitterSmallId  = $seoRow['tw_sm_image_id'];
        $twitterLargeId  = $seoRow['tw_lr_image_id'];
        $facebookSmallId = $seoRow['fb_sm_image_id'];
        $facebookLargeId = $seoRow['fb_lr_image_id'];

        $this->ogtcImagesMap = $ogtcImagesMap = [
            $twitterSmallId  => [0, 'twitter_small',  800,  800],
            $twitterLargeId  => [2, 'twitter_large',  1600, 800],
            $facebookSmallId => [1, 'facebook_small', 600,  600],
            $facebookLargeId => [3, 'facebook_large', 1200, 630]
        ];

        $this->ogtcMeta['id'] = $seoId;

        $this->ogtcMeta['title']       = $seoRow['seo_meta_title'];
        $this->ogtcMeta['keywords']    = $seoRow['seo_meta_keywords'];
        $this->ogtcMeta['description'] = $seoRow['seo_meta_description'];
        $this->ogtcMeta['ogtc_title']  = $seoRow['seo_ogtc_title'];
        $this->ogtcMeta['ogtc_descr']  = $seoRow['seo_ogtc_description'];

        $images = $this->db->query(
            '
                SELECT 
                    * 
                FROM 
                    cnt_images 
                WHERE image_id IN (
                    '.$twitterSmallId.',
                    '.$twitterLargeId.',
                    '.$facebookSmallId.',
                    '.$facebookLargeId.'
                )
            '
        );

        while($row = $images->fetch()) {
            $map = $ogtcImagesMap[(int)$row['image_id']];

            $imageSrc = '';

            if ($row['image_name'] != '') {
                $imageSrc = '/uploads/images/'.$row['image_name'].'_'.$row['image_id'].'.'.$row['image_type'];
            }

            $this->ogtcImages[$map[0]][] = [
                'id'     => 'rcpSiteOGTCEditIcon_'.$map[2].'x'.$map[3],
                'name'   => 'icon_'.$map[2].'x'.$map[3],
                'width'  => $map[2],
                'height' => $map[3],
                'action' => '/tools/api/seo_ogtc_image?image_id='.$row['image_id'].'&size='.$map[2].'x'.$map[3],
                'imageSrc' => $imageSrc,
                'imageDefault' => ''
            ];
        }

        return $this;
    }

    public function getImages()
    {
        return $this->ogtcImages;
    }

    public function getData()
    {
        return $this->ogtcMeta;
    }

    public function prepareArrayForHtmlTemplate()
    {
        $arrayForHtml = [];

        for($a = 0, $len = sizeof($this->ogtcImages); $a < $len; $a++) {
            $demo = $this->ogtcImages[$a];

            $arrayForHtml[] = [
                'secWidth' => 'w640',
                'isFake'   => 1,
                'title'    => $demo[2],
                'formId' => 'rcpSiteOGTCDemo'.$demo[0].$demo[1],
                'formAction' => '/tools/api/seo_ogtc_demo?seo_id=1&size='.strtolower($demo[1]).'&type='.strtolower($demo[0]),
                'formData'   => 'data-site=""',
                'submitTitle' => $this->langTxt->_('ogtc_save'),
                'clsClasses'  => 'padMedium',
                'beforeTemplate' => [
                    'name' =>'sites/partials/ogtc_demo',
                    'data' => [
                        'classes' => strtolower($demo[0]).' '.strtolower($demo[1]),
                        'title' => $this->ogtcMeta['ogtc_title'],
                        'description' => $this->ogtcMeta['ogtc_descr'],
                        'site' => 'yoursite.com',
                        'icon' => $demo[3]
                    ]
                ],
                'fieldList' => []
            ];
        }

        return $arrayForHtml;
    }
}