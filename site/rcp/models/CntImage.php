<?php
namespace Site\Rcp\Models;

use Core\Lib\Typograph;
use Imagick;
use Phalcon\DI;
use Phalcon\Mvc\Model;

/**
 *
 * @method CntImage findFirstByImageId
 *
 * @property integer $image_id
 * @property string $image_name
 * @property string $image_type
 * @property integer $image_optimized
 * @property string $image_temp_name
 * @property string $image_temp_type
 * @property integer $image_width
 * @property integer $image_height
 * @property integer $image_min_opti
 * @property integer $image_min_svg
 * @property integer $image_min_svgo
 */
class CntImage extends Model
{
    private static $imagesDir;
    private static $tempDir;
    private static $allowedTypes = [
        'jpg' => 1,
        'png' => 1,
        'gif' => 1
    ];

    public function initialize()
    {
        $this->setSource('cnt_images');

        self::$imagesDir = DIR_PUBLIC.'uploads/images/';
        self::$tempDir   = DIR_PUBLIC.'uploads/temp/';
    }



    /**
     *
     * @access public
     * @param integer $id Идентификатор мембера
     * @return CntImage
     */
    public static function findFirstById($id)
    {
        return self::findFirstByImageId($id);
    }

    public static function addNewImage($file)
    {
        $fileCheck = self::checkFile($file);

        if ($fileCheck['status'] != 'yes') {
            return 0;
        }

        $CntImage = CntImage::findFirstById(
            (int)DI::getDefault()['db']->query('SELECT cnt_images_create() AS id')->fetch()['id']
        );

        $CntImage->uploadFile($file);

        return $CntImage;
    }

    public function deleteTemporaryFile()
    {
        $this->unlinkTempFile();
        $this->save();
    }

    public function deleteCurrentFile()
    {
        $this->unlinkCurrFile();
        $this->save();
    }

    public function uploadFile($fileFromFILES)
    {
        $file = $fileFromFILES;

        $fileCheck = self::checkFile($file);

        if ($fileCheck['status'] != 'yes') {
            return ['status' => $fileCheck['status']];
        }

        $fileType      = $fileCheck['type'];
        $cleanFileName = $fileCheck['name'];

        $dir = self::$imagesDir;

        $this->unlinkCurrFile();

        move_uploaded_file($file['tmp_name'], $dir.$cleanFileName.'_'.$this->image_id.'.'.$fileType);

        $imageInfo = (new Imagick($dir.$cleanFileName.'_'.$this->image_id.'.'.$fileType))->identifyImage();

        $this->image_name = $cleanFileName;
        $this->image_type = $fileType;
        $this->image_width  = $imageInfo['geometry']['width'];
        $this->image_height = $imageInfo['geometry']['height'];

        $this->save();

        return [
            'status'   => 'uploaded',
            'src'      => '/uploads/images/'.$cleanFileName.'_'.$this->image_id.'.'.$fileType
        ];
    }

    public function uploadTemporaryFile($fileFromFILES)
    {
        $file = $fileFromFILES;

        $fileCheck = self::checkFile($file);

        if ($fileCheck['status'] != 'yes') {
            return ['status' => $fileCheck['status']];
        }

        $fileType      = $fileCheck['type'];
        $cleanFileName = $fileCheck['name'];

        $tmp = self::$tempDir;

        $this->unlinkTempFile();

        move_uploaded_file($file['tmp_name'], $tmp.$cleanFileName.'_'.$this->image_id.'.'.$fileType);

        $this->image_temp_name = $cleanFileName;
        $this->image_temp_type = $fileType;

        $this->save();

        return [
            'status' => 'uploaded',
            'src'    => '/uploads/temp/'.$cleanFileName.'_'.$this->image_id.'.'.$fileType
        ];
    }

    public function acceptTemporaryFile()
    {
        if ($this->image_temp_name == '') {
            return;
        }

        $this->unlinkCurrFile();

        $imageInfo = (new Imagick(self::$tempDir.$this->image_temp_name.'_'.$this->image_id.'.'.$this->image_temp_type))->identifyImage();

        rename(
            self::$tempDir   .$this->image_temp_name.'_'.$this->image_id.'.'.$this->image_temp_type,
            self::$imagesDir.$this->image_temp_name.'_'.$this->image_id.'.'.$this->image_temp_type
        );

        $this->image_name = $this->image_temp_name;
        $this->image_type = $this->image_temp_type;
        $this->image_width  = $imageInfo['geometry']['width'];
        $this->image_height = $imageInfo['geometry']['height'];

        $this->image_temp_name = '';
        $this->image_temp_type = '';

        $this->save();
    }


    public function unlinkCurrFile()
    {
        if ($this->image_name != '') {
            @unlink(self::$imagesDir.$this->image_name.'_'.$this->image_id.'.'.$this->image_type);
        }

        if ($this->image_optimized != 0) {
            @unlink(self::$imagesDir.'opti/'.$this->image_name.'_'.$this->image_id.'.'.$this->image_type);

            @unlink(self::$imagesDir.'svg/'.$this->image_name.'_'.$this->image_id.'.svg');
            @unlink(self::$imagesDir.'svg/'.$this->image_name.'_'.$this->image_id.'.png');
        }

        $this->image_name   = '';
        $this->image_type   = '';
        $this->image_width  = 0;
        $this->image_height = 0;

        $this->image_optimized = 0;
        $this->image_min_opti  = 0;
        $this->image_min_svg   = 0;
        $this->image_min_svgo  = 0;

        return $this;
    }

    public function unlinkTempFile()
    {
        if ($this->image_temp_name != '') {
            @unlink(self::$tempDir.$this->image_temp_name.'_'.$this->image_id.'.'.$this->image_temp_type);
        }

        $this->image_temp_name = '';
        $this->image_temp_type = '';

        return $this;
    }


    private static function checkFile($file) {
        if ($file['error'] != 0) {
            return ['status' => 'errors'];
        }

        $fileName = $file['name'];

        $fileExpl = explode('.', $fileName);

        $lastIdx  = sizeof($fileExpl) - 1;

        $fileType = mb_strtolower($fileExpl[$lastIdx]);

        $fileType = $fileType == 'jpeg' ? 'jpg' : $fileType;

        if (!self::$allowedTypes[$fileType]) {
            return ['status' => 'type'];
        }


        unset($fileExpl[$lastIdx]);

        return [
            'status' => 'yes',
            'name'   => Typograph::cleanString_and_rus2Lat(implode('.', $fileExpl)),
            'type'   => $fileType
        ];
    }
}