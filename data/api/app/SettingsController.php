<?php

namespace Api\App;

use Core\Extender\ApiAccessAjaxBasic;
use Core\Handler\MailSender;
use Core\Lib\PasswordHash;
use DateTime;
use Exception;
use Phalcon\Translate\Adapter\NativeArray;

class SettingsController extends ApiAccessAjaxBasic
{
    private $avatarSizes = [24, 32, 48, 64, 96, 124, 156, 192, 200];

    public function getFullData() {
        $langId = $this->user->getLangId();

        $selects = $this->db->query(
            '
                SELECT setting_id,
                       select_id,
                       select_value,
                       select_title 
                FROM public.settings_selects_list 
                WHERE lang_id = :lid
            ',
            ['lid' => $langId]
        );

        $selectsList = [];

        while($row = $selects->fetch()) {
            $settingId = (int)$row['setting_id'];
            unset($row['setting_id']);

            $row['select_id'] = (int)$row['select_id'];

            $selectsList[$settingId] = $selectsList[$settingId] ?? [];

            $selectsList[$settingId][] = $row;
        }

        // -------------------------------------------------------------------------------------------------------------

        $groupsList = $this->db->query(
            'SELECT group_id, group_title FROM public.settings_packages_groups_list WHERE lang_id = :lid',
            ['lid' => $langId]
        )->fetchAll();

        // -------------------------------------------------------------------------------------------------------------

        $packages = $this->db->query(
            '
                SELECT group_id,
                       package_id,
                       package_color,
                       package_name,
                       package_title,
                       package_icon
                FROM public.settings_packages_list 
                WHERE lang_id = :lid
            ',
            ['lid' => $langId]
        );

        $packagesList = [];

        while($row = $packages->fetch()) {
            $groupId = (int)$row['group_id'];
            unset($row['group_id']);

            $row['package_id'] = (int)$row['package_id'];

            $packagesList[$groupId] = $packagesList[$groupId] ?? [];

            $packagesList[$groupId][] = $row;
        }

        // -------------------------------------------------------------------------------------------------------------

        $sets = $this->db->query(
            '
                SELECT set_id,
                       package_id,
                       set_title
                FROM public.settings_sets_list 
                WHERE lang_id = :lid
            ',
            ['lid' => $langId]
        );

        $setsList = [];

        while($row = $sets->fetch()) {
            $packageId = (int)$row['package_id'];
            unset($row['package_id']);

            $row['set_id'] = (int)$row['set_id'];

            if (!isset($setsList[$packageId])) {
                $setsList[$packageId] = [['set_id' => 0, 'set_title' => null]];
            }

            $setsList[$packageId][] = $row;
        }


        // -------------------------------------------------------------------------------------------------------------

        $settings = $this->db->query(
            '
                SELECT setting_id,
                       setting_name,
                       setting_config,
                       type_name,
                       setting_title,
                       setting_radio,
                       setting_description,
                       package_id,
                       set_id,
                       setting_icon
                FROM public.settings_list 
                WHERE lang_id = :lid
            ',
            ['lid' => $langId]
        );

        $settingsList = [];

        while($row = $settings->fetch()) {
            $packageId = (int)$row['package_id'];
            $setId     = (int)$row['set_id'];
            unset($row['package_id']);
            unset($row['set_id']);

            $row['setting_id'] = (int)$row['setting_id'];
            $row['setting_config'] = json_decode($row['setting_config'], true);

            $settingsList[$packageId] = $settingsList[$packageId] ?? [];
            $settingsList[$packageId][$setId] = $settingsList[$packageId][$setId] ?? [];

            $settingsList[$packageId][$setId][] = $row;
        }

        $memberRow = $this->db->query(
            'SELECT lang_id, member_date_birth FROM public.members WHERE member_id = :mid',
            ['mid' => $this->user->getId()]
        )->fetch();

        $passwordRow = $this->db->query(
            '
                SELECT 
                       count(*) AS num, 
                       CAST(max(log_date) AS date)  AS last_date
                FROM public.members_log_auth 
                WHERE member_id = :mid',
            ['mid' => $this->user->getId()]
        )->fetch();

        $passwordChangeCount = (int)$passwordRow['num'] - 1;
        $passwordChangeDate  = $passwordRow['last_date'];

        $newEmailRow = $this->db->query(
            'SELECT new_email FROM public.members_change_email WHERE member_id = :mid',
            ['mid' => $this->user->getId()]
        )->fetch();

        $avatarData = [
            'data' => null,
            'image' => null,
            'canvas' => null,
            'cropBox' => null,
        ];

        if ($this->user->getAvatar()) {
            $avatarRow = $this->db->query(
                'SELECT * FROM members_avatars WHERE member_id = :mid',
                ['mid' => $this->user->getId()]
            )->fetch();

            $avatarData['data']    = json_decode($avatarRow['avatar_data'],     true);
            $avatarData['image']   = json_decode($avatarRow['avatar_image'],    true);
            $avatarData['canvas']  = json_decode($avatarRow['avatar_canvas'],   true);
            $avatarData['cropBox'] = json_decode($avatarRow['avatar_crop_box'], true);
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'selects' => $selectsList,
            'groups' => $groupsList,
            'packages' => $packagesList,
            'settings' => $settingsList,
            'sets' => $setsList,
            'user' => [
                'email'   => $this->user->getEmail(),
                'avatar'  => $this->user->getAvatar(),
                'avatar_type'  => $this->user->getAvatarType(),
                'email_new' => $newEmailRow['new_email'] ?? '',
                'gender'  => $this->user->getGender(),
                'lang_id' => (int)$memberRow['lang_id'],
                'birth_date' => $memberRow['member_date_birth'],
                'years_old' => $this->user->getAge()
            ],
            'avatar' => $avatarData,
            'password' => [
                'count' => $passwordChangeCount <= 0 ? 0 : $passwordChangeCount,
                'date'  => $passwordChangeDate
            ]
        ]);
    }

    public function getSocial() {
        $discordTemplate = ['active' => 0, 'id' => '', 'discriminator' => '', 'name' => '', 'avatar' => ''];

        $discordIntegrations = [
            $discordTemplate, // discord_rpginferno
            $discordTemplate  // discord_freia
        ];


        $discord = $this->db->query(
            '
                SELECT
                    dua.oauth2_id,
                    du.discord_id,
                    du.discord_avatar,
                    du.discord_username,
                    du.discord_discriminator
                FROM
                    public.members_discord AS md,
                    public.discord_user AS du,
                    public.discord_user_oauth2 AS dua
                WHERE
                    md.member_id = :mid AND
                    du.discord_user_id = md.discord_user_id AND
                    dua.discord_user_id = du.discord_user_id
            ',
            ['mid' => $this->user->getId()]
        );

        while($row = $discord->fetch()) {
            $discordIntegrations[(int)$row['oauth2_id'] - 1] = [
                'active' => 1,
                'id' => $row['discord_id'],
                'discriminator' => $row['discord_discriminator'],
                'name' => $row['discord_username'],
                'avatar' => $row['discord_avatar']
            ];
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'integrations' => [
                'discord' => $discordIntegrations
            ]
        ]);
    }

    public function setSetting() {
        $settingId  = (int)$this->request->getPost('setting_id');
        $settingValue = $this->request->getPost('setting_val');

        $setting = $this->db->query(
            '
                SELECT st.type_name,
                       s.setting_config,
                       s.setting_name                      
                FROM public.settings AS s, 
                     public.settings_types AS st
                WHERE s.setting_id = :sid 
                  AND st.setting_type_id = s.setting_type_id       
            ',
            ['sid' => $settingId]
        )->fetch();

        if (!$setting) {
            $this->sendResponseAjax([
                'status' => 'no'
            ]);
        }

        $settingConfig = json_decode($setting['setting_config'], true);
        $settingName = $setting['setting_name'];
        $typeName = $setting['type_name'];

        $settingValue = $this->parseValue($settingConfig['type'], $settingConfig['default'], $settingValue);

        $needToEncode = 0;

        if ($typeName === 'sortable' || $typeName === 'select') {
            $prepareValue = [
                'sortable' => function($value) {
                    return (int)$value;
                },
                'select' => function($value) use($settingConfig) {
                    return $this->parseValue($settingConfig['type'], $settingConfig['default'], $value);
                }
            ][$typeName];

            $selects = $this->db->query(
                'SELECT select_value FROM public.settings_selects WHERE setting_id = :sid',
                ['sid' => $settingId]
            );

            $selectsCheckMap = [];
            $selectsCount = 0;

            while($row = $selects->fetch()) {
                $selectsCheckMap[$prepareValue($row['select_value'])] = true;
                $selectsCount++;
            }


            if ($typeName === 'sortable') {
                $overflowFilter = [];

                $filteredValue = [];

                for($a = 0, $len = sizeof($settingValue); $a < $len; $a++) {
                    $sortableId = $settingValue[$a];

                    // Здесь мы защищаемся от переполнения порядка сортировки, чтобы нам 100500 элементов не сунули
                    if (isset($overflowFilter[$sortableId])) {
                        continue;
                    }

                    // если есть айди, которого нет в списке - останавливаем машину
                    if (!isset($selectsCheckMap[$sortableId])) {
                        $this->sendResponseAjax([
                            'status' => 'no'
                        ]);
                    }

                    $overflowFilter[$sortableId] = true;
                    $filteredValue[] = $sortableId;
                }

                // Если количество элементов не соответствует количество селектов, то нас пытаюстя надурить
                if (sizeof($filteredValue) != $selectsCount) {
                    $this->sendResponseAjax([
                        'status' => 'no'
                    ]);
                }

                $settingValue = $filteredValue;
                $needToEncode = 1;
            }
            else {
                if (!isset($selectsCheckMap[$settingValue])) {
                    $settingValue = $settingConfig['default'];
                }
            }
        }

        $this->db->query(
            'SELECT public.dp_members_change_setting(:mid, :sid, :val) AS res',
            [
                'mid' => $this->user->getId(),
                'sid' => $settingId,
                'val' => $needToEncode ? json_encode($settingValue) : $settingValue
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'status' => 'yes',
            'value' => $settingValue
        ]);
    }

    public function setLang() {
        $langId = (int)$this->request->getPost('lang_id');

        $langId = ($langId < 1 || $langId > 2) ? 2 : $langId;

        $this->db->query(
            'SELECT public.dp_members_change_lang(:mid, :lid) AS res',
            [
                'mid' => $this->user->getId(),
                'lid' => $langId
            ]
        )->fetch()['res'];

        $this->user->refresh();

        $this->sendResponseAjax([
            'status' => 'yes'
        ]);
    }

    public function setNick() {
        $nick = trim($this->request->getPost('nick'));

        $nick = preg_replace("/\s+/", " ", $nick);
        $nick = preg_replace("/-+/",  "-", $nick);
        $nick = preg_replace("/_+/",  "_", $nick);

        $nick = htmlspecialchars($nick);

        $nickLen = mb_strlen($nick);

        $errName = 'none';

        if($nickLen < 3) {
            $errName = 'short';
        }

        if($nickLen > 98) {
            $errName = 'long';
        }

        if ($errName == 'none') {
            $resultCode = (int)$this->db->query(
                'SELECT public.dp_members_change_nick(:mid, :nick) AS res',
                ['mid'  => $this->user->getId(), 'nick' => $nick]
            )->fetch()['res'];

            if ($resultCode) {
                $errName = 'exist';
            }
            else {
                $this->user->refresh();
            }
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'error'  => $errName,
            'nick'   => $nick
        ]);
    }


    public function setPass() {
        $passw = $this->request->getPost('curr');
        $pass1 = $this->request->getPost('new1');
        $pass2 = $this->request->getPost('new2');
        $email = $this->user->getEmail();

        $errName = 'none';

        if (mb_strlen($pass1) < 5 || mb_strlen($pass2) < 5) {
            $errName = 'short';
        }

        if ($pass1 != $pass2) {
            $errName = 'equal';
        }

        if ($errName == 'none' && !PasswordHash::tryToLogIn($passw, $email)) {
            $errName = 'wrong';
        }

        if ($errName == 'none' && PasswordHash::comparePasswords($pass1, $this->user->getId())) {
            $errName = 'exist';
        }

        if ($errName == 'none') {
            $passSalt = PasswordHash::passGenSalt();
            $passHash = PasswordHash::passGenHash($pass1, $email, $passSalt);

            $this->db->query(
                'SELECT dp_members_change_password(:mid::bigint, :salt, :hash)',
                [
                    'mid' => $this->user->getId(),
                    'salt' => $passSalt,
                    'hash' => $passHash
                ]
            );
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'error'  => $errName,
            'date'   => date('Y-m-d')
        ]);
    }

    public function setMail() {
        $email1 = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email1')))));
        $email2 = preg_replace("/\s+/", '', mb_strtolower(trim(htmlspecialchars($this->request->getPost('email2')))));
        $pass = $this->request->getPost('pass');


        $errName = 'none';

        if(!preg_match( "/^.+@.+\..+$/ui", $email1)) {
            $errName = 'format';
        }

        if(!preg_match( "/^.+@.+\..+$/ui", $email2)) {
            $errName = 'format';
        }

        if ($email1 != $email2) {
            $errName = 'not_equal';
        }

        if ($errName == 'none'  && !PasswordHash::tryToLogIn($pass, $this->user->getEmail())) {
            $errName = 'pass';
        }

        if ($errName == 'none' && mb_strlen($email1) > 98) {
            $errName = 'long';
        }

        if ($errName == 'none' && $this->db->query(
                'SELECT 1 FROM members WHERE member_email = LOWER(:email)', ['email' => $email1]
            )->fetch()) {
            $errName = 'exist';
        }

        if ($errName == 'none') {
            $activationCode = md5(mt_rand(0, 999).md5(mt_rand(0, 999)).mt_rand(1000, 2000).md5($this->user->getEmail()));

            $passSalt = PasswordHash::passGenSalt();
            $passHash = PasswordHash::passGenHash($pass, $email1, $passSalt);

            $this->db->query(
                'SELECT public.dp_members_change_email_prepare(:mid, :salt, :hash, :mail, :key)',
                [
                    'mid'  => $this->user->getId(),
                    'salt' => $passSalt,
                    'hash' => $passHash,
                    'mail' => $email1,
                    'key'  => $activationCode
                ]
            );

            $mailSender = new MailSender('setts_emailchange', $this->site->getLangId());

            $mailSender->send(
                $this->user->getId(),
                ['activationLink' => 'https://'.$_SERVER['HTTP_HOST'].'/#auth/change_mail/'.$activationCode],
                $email1
            );
        }

        $this->sendResponseAjax([
            'status' => 'yes',
            'error'  => $errName
        ]);
    }

    public function uploadAvatar()
    {
        $allowedType = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png'  => 'png', 'gif'  => 'gif'];
        $allowedSize = 10485760 / 2;
        $imageFile   = $_FILES['file'];

        $imageHandler = new \Imagick();

        try {
            $imageHandler->readImage( $imageFile['tmp_name'] );
        } catch (Exception $e) {
            $this->sendResponseAjax(['status' => 'no', 'error'  => 'invalid']);
        }


        $imageSize   = $imageHandler->getImageLength();

        $imageFormat = strtolower($imageHandler->getImageFormat());

        if ($allowedSize < $imageSize) {
            $this->sendResponseAjax(['status' => 'no', 'error'  => 'size']);
        }

        if (!isset($allowedType[$imageFormat])) {
            $this->sendResponseAjax(['status' => 'no', 'error'  => 'type']);
        }

        $imageFormat = $allowedType[$imageFormat];

        $name = 'temp_avatar_uid'.$this->user->getId().'.'.$imageFormat;

        $path = DIR_PUBLIC.'uploads/avatars/temp/'.$name;

        move_uploaded_file($imageFile['tmp_name'], $path);

        $this->sendResponseAjax(['status' => 'yes', 'temp' => $name, 'type' => $imageFormat, 'error' => '']);
    }

    public function saveAvatar()
    {
        $imageBlob  = $this->request->getPost('blob');

        $jsonData    = json_decode($this->request->getPost('data'));
        $jsonImage   = json_decode($this->request->getPost('image'));
        $jsonCanvas  = json_decode($this->request->getPost('canvas'));
        $jsonCropBox = json_decode($this->request->getPost('cropBox'));
        $temporary = trim($this->request->getPost('temporary'));

        //[^A-Za-z0-9+\/=] - символы которые не могут встретиться в бейс 64
        if (preg_match('/[^A-Za-z0-9+\/=]/miu', $imageBlob)) {
            $this->sendResponseAjax(['status' => 'no']);
        }

        $allowedFormats = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png'  => 'png', 'gif'  => 'gif'];


        $fileNamePref = DIR_PUBLIC.'uploads/avatars/user_'.$this->user->getId();


        $ver = $this->user->getAvatar();

        $decodedImage = base64_decode($imageBlob);

        for($a = 0, $len = sizeof($this->avatarSizes); $a < $len; $a++) {
            @unlink($fileNamePref.'_'.$ver.'_'.$this->avatarSizes[$a].'.png');
        }

        $ver++;

        $avatarType = $this->user->getAvatarType();

        if (isset($allowedFormats[$temporary])) {
            @unlink($fileNamePref.'_'.($ver - 1).'.png');
            @unlink($fileNamePref.'_'.($ver - 1).'.jpg');
            @unlink($fileNamePref.'_'.($ver - 1).'.gif');

            $avatarType = $temporary;

            rename(
                DIR_PUBLIC.'uploads/avatars/temp/temp_avatar_uid'.$this->user->getId().'.'.$temporary,
                $fileNamePref.'_'.$ver.'.'.$temporary
            );
        }
        else {
            rename(
                $fileNamePref.'_'.($ver - 1).'.'.$this->user->getAvatarType(),
                $fileNamePref.'_'.$ver.'.'.$this->user->getAvatarType()
            );
        }

        for($a = 0, $len = sizeof($this->avatarSizes); $a < $len; $a++) {
            $size = $this->avatarSizes[$a];
            $fileName   = $fileNamePref.'_'.$ver.'_'.$size.'.png';



            file_put_contents($fileName,   $decodedImage);

            $imagickResize = new \Imagick($fileName);
            $imagickResize->setImageFormat('png');
            //$imagickCrop128->cropImage($crop['w'], $crop['h'], $crop['x'], $crop['y']);
            $imagickResize->resizeImage($size, $size, \Imagick::FILTER_LANCZOS, 1, 1);
            $imagickResize->writeImage();
        }

        unset($decodedImage);

        $this->db->query(
            'SELECT public.dp_members_change_avatar(:mid, :ver, :type, :data::json, :image::json, :canvas::json, :crop::json)',
            [
                'mid' => $this->user->getId(),
                'ver' => $ver,
                'type' => $avatarType,
                'data'   => json_encode($jsonData),
                'image'  => json_encode($jsonImage),
                'canvas' => json_encode($jsonCanvas),
                'crop'   => json_encode($jsonCropBox),
            ]
        )->fetch();

        $this->user->refresh();

        $this->sendResponseAjax(['status' => 'yes', 'ver' => $ver, 'type' => $avatarType]);
    }

    public function deleteAvatar() {
        $fileNamePref = DIR_PUBLIC.'uploads/avatars/user_'.$this->user->getId();

        $ver = $this->user->getAvatar();
        $avatarType = $this->user->getAvatarType();

        @unlink($fileNamePref.'_'.$ver.'.'.$avatarType);
        for($a = 0, $len = sizeof($this->avatarSizes); $a < $len; $a++) {
            @unlink($fileNamePref.'_'.$ver.'_'.$this->avatarSizes[$a].'.png');
        }

        $this->db->query(
            'SELECT public.dp_members_delete_avatar(:mid)', ['mid' => $this->user->getId()]
        )->fetch();

        $this->user->refresh();

        $this->sendResponseAjax(['status' => 'yes']);
    }

    private function parseValue($type, $default, $value) {
        $value = is_null($value) ? $default : $value;

        switch ($type) {
            case 'integer':
                $value = (int)$value;
                break;
            case 'bool':
                $value = (int)$value ? 1 : 0;
                break;
            case 'float':
                $value = (float)$value;
                break;
            case 'array':
                $value = json_decode($value, true);
                $value = !is_array($value) ? $default : $value;

                for ($a = 0, $len = sizeof($value); $a < $len; $a++) {
                    $value[$a] = (int)$value[$a];
                }

                break;
            case 'string':
                $value = preg_replace('/[^a-zA-Z0-9_-]/ui', '', $value);
                break;
        }

        return $value;
    }
}


