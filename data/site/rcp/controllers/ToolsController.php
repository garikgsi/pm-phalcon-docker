<?php
namespace Site\Rcp\Controllers;
use Core\Extender\ControllerAppRcp;
use Core\Lib\Typograph;
use ImageOptimizer\OptimizerFactory;
use Imagick;
use Phalcon\Filter;
use Site\Rcp\Models\CntImage;
use Site\Rcp\Models\SeoData;

/**
 * Class ToolsController
 * @package Site\Rcp\Controllers
 */
class ToolsController extends ControllerAppRcp
{



    protected function apiImage_upload_tmp()
    {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
            $this->sendResponseAjax(['state' => 'no', 'notification' => 'При загрузке возникли ошибки']);
        }

        $file = $_FILES['image'];

        

        $imageId  = strtolower(trim($this->request->get('image_id')));

        $imageRow = $this->db->query(
            'SELECT * FROM cnt_images WHERE image_id = :iid',
            ['iid' => $imageId]
        )->fetch();

        if (!$imageRow) {
            $this->sendResponseAjax(['state' => 'no', 'notification' => 'Изображение не существует']);
        }

        // Удаляем предыдущие временные файлы, если есть

        $tempDir = DIR_PUBLIC.'/uploads/temp/';

        if ($imageRow['image_temp_name'] != '') {
            @unlink($tempDir.$imageRow['image_temp_name'].'_'.$imageId.'.'.$imageRow['image_temp_type']);
        }

        $fileNameExpl = explode('.', $file['name']);

        $lastIdx = sizeof($fileNameExpl) - 1;

        $fileType = mb_strtolower($fileNameExpl[$lastIdx]);

        $fileType = $fileType == 'jpeg' ? 'jpg' : $fileType;

        $allowedTypes = [
            'jpg' => '',
            'png' => '',
            'gif' => ''
        ];

        if (!$allowedTypes[$fileType]) {
            $this->sendResponseAjax(['state' => 'no', 'notification' => 'Недопустимый формат']);
        }

        unset($fileNameExpl[$lastIdx]);

        // пересобранное и отчищенное имя
        $cleanFileName = Typograph::cleanString_and_rus2Lat(implode('.', $fileNameExpl));

        $this->db->query(
            '
                UPDATE cnt_images 
                SET 
                    image_temp_name = :name, 
                    image_temp_type = :type 
                WHERE image_id = :iid
            ',
            [
                'iid'  => $imageId,
                'name' => $cleanFileName,
                'type' => $fileType
            ]
        );

        move_uploaded_file($file['tmp_name'], $tempDir.$cleanFileName.'_'.$imageId.'.'.$fileType);

        $this->sendResponseAjax([
            'state' => 'yes',
            'image' => [
                'src'       => '/uploads/temp/'.$cleanFileName.'_'.$imageId.'.'.$fileType,
                'temp_name' => $cleanFileName,
                'temp_type' => $fileType
            ]
        ]);
    }


    protected function apiImage_optimization()
    {
        $imageId = (int)$this->request->getPost('image_id');

        $imageBlob = $this->request->getPost('svg_blob');
        $imageSvg  = $this->request->getPost('svg_text');
        $shapes    = (int)$this->request->getPost('shapes');

        $imageRow = $this->db->query(
            'SELECT * FROM cnt_images WHERE image_id = :iid', ['iid' => $imageId]
        )->fetch();

        if (!$imageRow) {
            $this->sendResponseAjax(['state' => 'no']);
        }

        $imageDir = DIR_PUBLIC.'uploads/images/';


        $data = base64_decode($imageBlob);

        $origPath = $imageDir.$imageRow['image_name'].'_'.$imageRow['image_id'].'.'.$imageRow['image_type'];
        $copyPath = $imageDir.'opti/'.$imageRow['image_name'].'_'.$imageRow['image_id'].'.'.$imageRow['image_type'];
        $svgoPath = $imageDir.'svg/'.$imageRow['image_name'].'_'.$imageRow['image_id'].'.png';

        file_put_contents($imageDir.'svg/'.$imageRow['image_name'].'_'.$imageRow['image_id'].'.png', $data);
        file_put_contents($imageDir.'svg/'.$imageRow['image_name'].'_'.$imageRow['image_id'].'.svg', $imageSvg);

        copy($origPath, $copyPath);

        $factory = new OptimizerFactory();

        $factory->get()->optimize($svgoPath);
        $factory->get()->optimize($copyPath);

        $this->db->query(
            '
                UPDATE cnt_images 
                SET 
                    image_optimized = 1, 
                    image_min_opti  = 1, 
                    image_min_svg   = 1, 
                    image_min_svgo  = 1 
                WHERE image_id = :iid
            ',
            ['iid' => $imageId]
        );


        $pathImg = 'uploads/images/';
        $pathSvg = 'uploads/images/svg/';
        $pathOpt = 'uploads/images/opti/';


        $dirImg = DIR_PUBLIC.$pathImg;
        $dirSvg = DIR_PUBLIC.$pathSvg;
        $dirOpt = DIR_PUBLIC.'uploads/images/opti/';


        $row = $imageRow;

        $imageName = $row['image_name'].'_'.$row['image_id'];

        $imageSize = filesize($dirImg.$imageName.'.'.$row['image_type']);


        $imgOrig = '/'.$pathImg.$imageName.'.'.$row['image_type'];
        $imgSvg  = '/'.$pathSvg.$imageName.'.svg';

        $imageSizeOpti    = filesize($dirOpt.$imageName.'.'.$row['image_type']);
        $imagePercentOpti = round(($imageSizeOpti / $imageSize) * 100);

        $imageSizeSvg     = filesize($dirSvg.$imageName.'.svg');
        $imagePercentSvg  = round(($imageSizeSvg / $imageSize) * 100);

        $imageSizeSvgo    = filesize($dirSvg.$imageName.'.png');
        $imagePercentSvgo = round(($imageSizeSvgo / $imageSize) * 100);


        $imageUrlOpti = '/'.$pathOpt.$imageName.'.'.$row['image_type'];
        $imageUrlSvg  = $imgSvg;
        $imageUrlSvgo = '/'.$pathSvg.$imageName.'.png';

        $this->sendResponseAjax(['state' => 'yes', 'html' => $this->view->getPartial(
            'grin/partials/optimization_item',
            [
                'img_id'    => $row['image_id'],
                'img_orig'  => $imgOrig,
                'img_svg'   => $imgSvg.'?rnd='.mt_rand(0, 10000),
                'width'     => $row['image_width'],
                'height'    => $row['image_height'],
                'size'      => $imageSize,
                'enabled'   => (int)!$row['image_optimized'],
                'suggest'   => $shapes,
                'optimizations' => [
                    [
                        'name'    => 'OptiPNG',
                        'size'    => $imageSizeOpti,
                        'percent' => $imagePercentOpti,
                        'url'     => $imageUrlOpti
                    ],
                    [
                        'name'    => 'SVG',
                        'size'    => $imageSizeSvg,
                        'percent' => $imagePercentSvg,
                        'url'     => $imageUrlSvg
                    ],
                    [
                        'name'    => 'SVG PNG',
                        'size'    => $imageSizeSvgo,
                        'percent' => $imagePercentSvgo,
                        'url'     => $imageUrlSvgo
                    ]
                ]
            ]
        )]);
    }

    protected function apiImage_uploader_demo()
    {

        

        $act = $this->request->get('act');
        $imageId = (int)$this->request->get('image_id');

        if ($act == 'delete') {
            $CntImage = CntImage::findFirstById($imageId);

            if ($CntImage->image_temp_name) {
                $CntImage->deleteTemporaryFile();
            }
            else if ($CntImage->image_name){
                $CntImage->deleteCurrentFile();
            }


            $this->sendResponseAjax(['state' => 'yes']);
        }


        if ($_FILES['image']['error'] != 0) {
            $this->sendResponseAjax(['state' => 'no']);
        }

        $result = CntImage::findFirstById($imageId)->uploadTemporaryFile($_FILES['image']);

        $this->sendResponseAjax([
            'state' => 'yes',
            'image' => [
                'src' => $result['src'],
                'temp_name' => 'exists'
            ]
        ]);
    }

    protected function apiSeo_ogtc_save()
    {
        

        $seoId  = (int)$this->request->get('seo_id');
        $langId = (int)$this->request->getPost('lang');

        $title       = trim($this->request->getPost('title'));
        $keywords    = trim($this->request->getPost('keywords'));
        $description = trim($this->request->getPost('description'));

        $ogttcTitle       = trim($this->request->getPost('ogttc_title'));
        $ogttcDescription = trim($this->request->getPost('ogttc_description'));

        $this->db->query(
            'SELECT cnt_seo_save(:sid, :lid, :tit, :key, :desc, :otit, :odesc)',
            [
                'sid'   => $seoId,
                'lid'   => $langId,
                'tit'   => $title,
                'key'   => $keywords,
                'desc'  => $description,
                'otit'  => $ogttcTitle,
                'odesc' => $ogttcDescription
            ]
        );

        $this->sendResponseAjax(['state' => 'yes']);
    }

    protected function apiSeo_ogtc_lang()
    {
        

        $seoId  = (int)$this->request->get('seo_id');
        $langId = (int)$this->request->getPost('lang');

        $SeoData  = new SeoData($seoId, $langId);
        $metaData = $SeoData->getData();

        $this->sendResponseAjax([
            'state'  => 'yes',
            'fields' => [
                ['title',       $metaData['title']],
                ['keywords',    $metaData['keywords']],
                ['description', $metaData['description']],
                ['ogttc_title', $metaData['ogtc_title']],
                ['ogttc_description', $metaData['ogtc_descr']]
            ]
        ]);
    }

    protected function apiSeo_ogtc_image()
    {
        

        $act = $this->request->get('act');
        $imageId = (int)$this->request->get('image_id');

        if ($act == 'delete') {
            $CntImage = CntImage::findFirstById($imageId);

            if ($CntImage->image_temp_name) {
                $CntImage->deleteTemporaryFile();
            }
            else if ($CntImage->image_name){
                $CntImage->deleteCurrentFile();
            }


            $this->sendResponseAjax(['state' => 'yes']);
        }


        if ($_FILES['image']['error'] != 0) {
            $this->sendResponseAjax(['state' => 'no']);
        }


        list($width, $height) = explode(
            'x',
            trim(strtolower($this->request->get('size')))
        );

        $imageInfo = (new Imagick($_FILES['image']['tmp_name']))->identifyImage();

        if ($width != $imageInfo['geometry']['width'] && $height != $imageInfo['geometry']['height']) {
            $this->sendResponseAjax(['state' => 'no']);
        }

        $result = CntImage::findFirstById($imageId)->uploadTemporaryFile($_FILES['image']);

        $this->sendResponseAjax([
            'state' => 'yes',
            'image' => [
                'src' => $result['src'],
                'temp_name' => 'exists'
            ]
        ]);
    }

    protected function apiSeo_ogtc_demo()
    {
        $seoId = (int)$this->request->get('seo_id');
        $size  = trim($this->request->get('size'));
        $type  = trim($this->request->get('type'));

        $field = 'seo_'.$type.'_'.$size.'_image_id';

        $sizes = [
            'twitter_small'  => 'icon_800x800',
            'facebook_small' => 'icon_600x600',
            'twitter_large'  => 'icon_1600x800',
            'facebook_large' => 'icon_1200x630'
        ];

        $check = $this->request->getPost($sizes[$type.'_'.$size]);

        if ($check == 'exists') {
            $imageId = $this->db->query(
                'SELECT '.$field.' FROM cnt_seo WHERE seo_id = :sid',
                ['sid' => $seoId]
            )->fetch()[$field];

            $CntImage = CntImage::findFirstById($imageId);

            if ($CntImage->image_temp_name) {
                $CntImage->acceptTemporaryFile();
                $this->sendResponseAjax(['state' => 'yes', 'notification' => 'Данные сохранены']);
            }
        }



        $this->sendResponseAjax(['state' => 'yes']);
    }
}