<?php
namespace Site\Rcp\Controllers;

use Core\Builder\Bookmarks;
use Core\Extender\ControllerAppRcp;
use Core\Handler\SiteUtils;
use Phalcon\Db\ResultInterface;
use Phalcon\Filter;
use Phalcon\Mvc\View;

/**
 * Class IndexController
 * @package Site\Rcp\Controllers
 */
class ResourcesController extends ControllerAppRcp
{
    private $allowedModes = [];

    const CSS_ID = 1;
    const JS_ID  = 2;

    private $fileTypes = [
        'js' => [
            'color' => 'orange',
            'name'  => 'js',
            'id'    => 2
        ],
        'css' => [
            'color' => 'blue',
            'name'  => 'css',
            'id'    => 1
        ]
    ];

    public function initialize()
    {
        parent::initialize();

        $this->view->setLayout('content_wrap');

        $this->hapi->setHapiController('resources');

        $this->allowedModes = [
            ''         => ['sites', 'blue', $this->t->_('rcp_res_book_1'),     '', 'editSite',         'edit'],
            'explorer' => ['meta',  'blue', $this->t->_('rcp_res_book_2'),     '', 'editSiteMeta',     'meta']
        ];
    }

    public function indexAction() {

    }

    public function cssAction($mode = '', $id = 0)
    {

        $this->createLocalBookmarks($mode, 'css');
        //$this->setBaseData('css');

        $this->setBaseData('css', $id > 0 ? $mode.'/'.$id : $mode, $id);

        $this->attachHapiCallback(2, 'defaultBookmarksClick', '', []);

        $modesForAdd = [
            'group_add' => [
                'check_sql' => 'SELECT package_name AS name FROM resources_packages WHERE package_id = :id',
                'redirect'  => 'resources/css',
                'title'     => 'Подключение файлов к группе',
                'action'    => 'css_group_add'
            ],
            'site_add'  => [
                'check_sql' => 'SELECT site_name AS name FROM sites WHERE site_id = :id',
                'redirect'  => 'resources/css/sites',
                'title'     => 'Подключение файлов к сайту',
                'action'    => 'css_site_add'
            ],
            'app_add'   => [
                'check_sql' => 'SELECT app_name AS name FROM apps WHERE app_id = :id',
                'redirect'  => 'resources/css/apps',
                'title'     => 'Подключение файлов к приложению',
                'action'    => 'css_app_add'
            ]
        ];


        if ($mode == 'explorer') {
            $this->explorerSystem('explorer', 'design/', 'css', $id = 0);

            $this->hapi->setHapiAction('css_explorer');
            return;
        }
        else if($mode == 'sites' || $mode == 'apps') {
            $itemsList = $this->showFilesForCSSSitesApps($mode);

            $this->hapi->setHapiAction('css_'.$mode);
        }
        else if (isset($modesForAdd[$mode]) && $id > 0) {
            $modeAdd = $modesForAdd[$mode];

            $checkRow = $this->db->query(
                $modeAdd['check_sql'],
                ['id' => $id]
            )->fetch();

            if (!$checkRow) {
                $this->errorNoPage404($modeAdd['redirect']);
                return;
            }

            $this->explorerAddMode(
                $mode,
                $id,
                $modeAdd['title'].' «<b id="rcpResourcesFilesName" data-id="'.$id.'">'.$checkRow['name'].'</b>» (<b id="rcpResourcesFilesCount">0</b>)',
                'design/',
                'css'
            );

            $this->hapi->setHapiAction($modeAdd['action']);
            return;
        }
        else {
            $itemsList = $this->showFilesFolderForGroups('css');

            $this->hapi->setHapiAction('css_groups');
        }

        $this->view->setVar('packagesList', $itemsList);
    }

    public function jsAction($mode = '', $id = 0)
    {
        $t = $this->t;

        $this->createLocalBookmarks($mode, 'js');

        $this->setBaseData('js', $id > 0 ? $mode.'/'.$id : $mode, $id);


        $this->attachHapiCallback(2, 'defaultBookmarksClick', '', []);

        $itemsList = [];

        if ($mode == 'explorer') {
            $this->explorerSystem('explorer', 'js/', 'js', $id);
            $this->hapi->setHapiAction('js_explorer');
            return;
        }
        else if ($mode == 'sites') {
            $packages = $this->db->query(
                'SELECT * FROM resources_packages_sites_js'
            );

            $packagesIds = [];

            $packagesMap = [];

            $idx = 0;

            while($row = $packages->fetch()) {
                $packagesMap[$row['package_id']] = $idx;

                $itemsList[] = [
                    'type'  => 'category',
                    'count' => (int)$row['num'],
                    'list'  => [],
                    'dir'   => '',
                    'name'  => $row['package_name'],
                    'controls' => ''
                ];

                $packagesIds[] = $row['package_id'];

                $idx++;
            }

            $resources = $this->db->query(
                'SELECT * FROM resources_sites_js'
            );

            while($row = $resources->fetch()) {
                if (isset($packagesMap[$row['package_id']]) && $row['resource_id'] > 0) {
                    $categoryPointer = &$itemsList[$packagesMap[$row['package_id']]]['list'];
                }
                else {
                    $categoryPointer = &$itemsList;
                    $row['resource_dir'] = '';
                }

                $categoryPointer[] = [
                    'type' => 'item',
                    'dir'  => $row['resource_dir'],
                    'move' => '',
                    'file_color' => 'orange',
                    'file_name'  => $row['resource_dir'].$row['resource_name'],
                    'file_type'  => $row['type_file_extension'],
                    'controls' => ''
                ];
            }
            $this->hapi->setHapiAction('js_sites');
        }
        else if ($mode == 'requires') {
            $apps = $this->db->query(
                'SELECT * FROM resources_require_js'
            );

            while($row = $apps->fetch()) {
                $itemsList[] = [
                    'type' => 'item',
                    'dir'  => $row['resource_dir'],
                    'move' => '',
                    'file_color' => 'orange',
                    'file_name'  => $row['resource_dir'].$row['resource_name'],
                    'file_type'  => $row['type_file_extension'],
                    'controls' => $this->getActionLabel('Отвязать файл',
                        'ic-cross', 'red', ' data-resource="'.$row['resource_id'].'" ', ' link rcpResourcesRemoveFileFromPackage')
                ];
            }

            $this->view->setVar('res_local_footer', [
                [
                    'element_type'  => 'button_link',
                    'element_width' => '',
                    'text' => $t->_('rcp_res_clear_files_req'),
                    'href'   => '/resources/js/require_add',
                    'variant' => 'send',
                    'classes' => 'elizaHApi',
                    'data' => ['data-level' => 3]
                ]
            ]);

            $this->hapi->setHapiAction('js_require');
        }
        else if ($mode == 'apps') {
            $apps = $this->db->query(
                'SELECT * FROM resources_apps_js'
            );

            while($row = $apps->fetch()) {
                $itemsList[] = [
                    'type' => 'item',
                    'dir'  => $row['resource_dir'],
                    'move' => '',
                    'file_color' => 'orange',
                    'file_name'  => $row['resource_dir'].$row['resource_name'],
                    'file_type'  => $row['type_file_extension'],
                    'controls' => ''
                ];
            }
            $this->hapi->setHapiAction('js_apps');
        }
        else if ($mode == 'group_add' && $id > 0) {
            $packageRow = $this->db->query(
                'SELECT * FROM resources_packages WHERE package_id = :id',
                ['id' => $id]
            )->fetch();

            if (!$packageRow) {
                $this->errorNoPage404('resources/js');
                return;
            }

            $this->explorerAddMode(
                'group_add',
                $id,
                'Подключение файлов к группе «<b id="rcpResourcesFilesName" data-id="'.$id.'">'.$packageRow['package_name'].'</b>» (<b id="rcpResourcesFilesCount">0</b>)',
                'js/',
                'js'
            );

            $this->hapi->setHapiAction('js_group_add');
            return;
        }
        else if ($mode == 'require_add') {

            $this->explorerAddMode(
                'require_add',
                $id,
                'Добавление файлов к удаленной загрузке (<b id="rcpResourcesFilesCount">0</b>)',
                'js/',
                'js'
            );
            $this->hapi->setHapiAction('js_require_add');
            return;
        }
        else {
            $itemsList = $this->showFilesFolderForGroups('js');

            $this->hapi->setHapiAction('js_groups');
        }


        $this->view->setVar('packagesList', $itemsList);
    }


    private function explorerSystem($mode, $dir, $type, $groupId = 0) {
        $itemsList = $this->explorerScanDir($mode, $dir, $this->fileTypes[$type], $groupId);

        $this->view->setVar('explorerList',  $itemsList);
        $this->view->setVar('explorerGroup', ' data-group="'.$groupId.'" ');
        $this->view->pick('resources/explorer');

        $this->view->setTemplateBefore('localbookmarks_wrap');
    }



    public function apiExplorer_scan() {
        

        $dir   = trim($this->request->getPost('dir'));
        $type  = trim($this->request->getPost('type'));
        $mode  = trim($this->request->getPost('mode'));
        $group = $this->request->getPost('group');

        $dirList = $this->explorerScanDir($mode, $dir, $this->fileTypes[$type], $group);

        $this->sendResponseAjax([
            'state' => sizeof($dirList) ? 'yes' : 'no',
            'html'  => $this->view->getPartial('partials/explorer/items', ['list' => $dirList])
        ]);
    }

    public function apiExplorer_delete() {
        

        $dir  = trim($this->request->getPost('dir'));
        $name = trim($this->request->getPost('name'));

        $expl = explode('.', $name);

        $fileType = strtolower($expl[sizeof($expl) - 1]);

        if ($fileType != 'js' && $fileType != 'css') {
            $this->sendResponseAjax([
                'state'        => 'no',
                'notification' => 'Удаление файлов .'.$fileType.' невозможно'
            ]);
        }

        // Проверка на существование привязки файла
        $check = $this->db->query(
            'SELECT resource_id FROM resources WHERE (resource_dir, resource_name) = (:dir, :name)',
            ['dir' => $dir, 'name' => $name]
        )->fetch();

        if ($check) {
            $this->sendResponseAjax([
                'state'        => 'no',
                'notification' => 'Невозможно удалить привязанный ресурс'
            ]);
        }

        @unlink(DIR_PUBLIC.$dir.$name);

        $this->sendResponseAjax([
            'state'        => 'yes'
        ]);
    }

    public function apiReplace_file() {
        

        $dir  = trim($this->request->getPost('dir'));
        $name = trim($this->request->getPost('name'));
        $file = $_FILES['file'];

        $expl = explode('.', $name);

        $fileType = strtolower($expl[sizeof($expl) - 1]);

        if ($file['error']) {
            $this->sendResponseAjax([
                'state'        => 'no',
                'notification' => 'Ошибки при загрузке'
            ]);
        }

        $fileExpl   = explode('.', $file['name']);
        $loadedType = strtolower($fileExpl[sizeof($fileExpl) - 1]);

        if ($fileType != $loadedType) {
            $this->sendResponseAjax([
                'state'        => 'no',
                'notification' => 'Несовпадение форматов файла'
            ]);
        }

        if (!$this->db->query(
            'SELECT resource_id FROM resources WHERE (resource_dir, resource_name) = (:dir, :name)',
            ['dir' => $dir, 'name' => $name]
        )->fetch()) {
            $this->sendResponseAjax([
                'state'        => 'no',
                'notification' => 'Файл не зарегистрирован'
            ]);
        }


        file_put_contents(DIR_PUBLIC.$dir.$name, file_get_contents($file['tmp_name']));

        $this->sendResponseAjax([
            'state'        => 'yes',
            'notification' => 'Содержимое файла заменено'
        ]);
    }

    public function apiUpload_files() {
        

        $dir  = trim($this->request->getPost('dir'));
        $type = trim($this->request->get('type'));

        if ($type != 'js' && $type != 'css') {
            echo 'type mismatch';
            exit;
        }

        $html = '';

        $files = $_FILES['files'];

        $filesCount = sizeof($files['name']);

        for($i = 0; $i < $filesCount; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i]
            ];

            $controls = '';

            if ($file['error']) {
                continue;
            }

            $fileExpl   = explode('.', $file['name']);
            $loadedType = strtolower($fileExpl[sizeof($fileExpl) - 1]);

            if ($loadedType != $type) {
                continue;
            }

            // Если файл существует - происходит его замена, не надо генерировать органы управления
            if (!file_exists(DIR_PUBLIC.$dir.$file['name'])) {
                $controls .= $this->getActionLabel('Заменить содержимое',
                    'ic-upload', 'indigo 900', '', ' link replaceFileButton');

                $controls .= $this->getActionLabel('Удалить',
                    'ic-cross', 'red', '', ' link deleteFileButton');

                $html .= $this->view->getPartial('partials/explorer/item_item', [
                    'dir' => $dir,
                    'file_name'  => $file['name'],
                    'file_type'  => $type,
                    'file_color' => 'orange',
                    'controls'   => $controls
                ]);
            }

            move_uploaded_file($file['tmp_name'], DIR_PUBLIC.$dir.$file['name']);
        }

        $this->sendResponseAjax([
            'state'        => 'yes',
            'notification' => 'Файлы загружены',
            'html'         => $html
        ]);
    }

    public function apiPackage_add_files() {
        

        $packageId = $this->request->get('package_id');
        $mode      = trim($this->request->getPost('mode'));
        $type      = trim($this->request->getPost('type'));
        $files     = json_decode($this->request->get('files'), true);

        $this->db->begin();

        if ($mode == 'group_add') {
            foreach($files as $filePath => $fileData ) {
                $this->db->query(
                    'SELECT dp_resources_package_add_file(:pid, :type, :dir, :name) AS res',
                    [
                        'pid'  => $packageId,
                        'type' => $type,
                        'dir'  => $fileData['dir'],
                        'name' => $fileData['name']
                    ]
                )->fetch()['res'];
            }
        }
        else if ($mode == 'require_add') {
            foreach($files as $filePath => $fileData ) {
                $this->db->query(
                    'SELECT dp_resources_package_add_require(:type, :dir, :name) AS res',
                    [
                        'type' => $type,
                        'dir'  => $fileData['dir'],
                        'name' => $fileData['name']
                    ]
                )->fetch()['res'];
            }
        }
        else if ($mode == 'site_add') {
            foreach($files as $filePath => $fileData ) {
                $this->db->query(
                    'SELECT dp_resources_site_add_file(:pid, :type, :dir, :name) AS res',
                    [
                        'pid'  => $packageId,
                        'type' => $type,
                        'dir'  => $fileData['dir'],
                        'name' => $fileData['name']
                    ]
                )->fetch()['res'];
            }
        }
        else if ($mode == 'app_add') {
            foreach($files as $filePath => $fileData ) {
                $this->db->query(
                    'SELECT dp_resources_app_add_file(:pid, :type, :dir, :name) AS res',
                    [
                        'pid'  => $packageId,
                        'type' => $type,
                        'dir'  => $fileData['dir'],
                        'name' => $fileData['name']
                    ]
                )->fetch()['res'];
            }
        }

        $this->db->commit();

        $this->sendResponseAjax([
            'state' => 'yes',
            'msg'   => 'Файлы прикреплены'
        ]);
    }

    public function apiPackage_remove_file() {
        

        $resourceId = $this->request->get('resource_id');

        $resArr = ['rid' => $resourceId];

        $resource = $this->db->query('SELECT * FROM resources WHERE resource_id = :rid', $resArr)->fetch();


        if ($resource) {
            $this->db->begin();
            // Если тип CSS и отсутсвует айди пакета, то этот CSS скорее всего прикрепляется либо к сайтам, либо к
            // приложениям, удаляем сразу и оттуда и оттуда, просто потому, что каждый ресурс уникален для каждой связки
            if ($resource['type_id'] == self::CSS_ID && $resource['package_id'] == '') {
                $this->db->query('DELETE FROM resources_to_apps  WHERE resource_id = :rid', $resArr);
                $this->db->query('DELETE FROM resources_to_sites WHERE resource_id = :rid', $resArr);
            }

            $this->db->query('DELETE FROM resources WHERE resource_id = :rid', $resArr);

            $this->db->commit();
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'msg'   => 'Файл отключен'
        ]);
    }

    public function apiPackage_compress_file() {
        

        $this->db->query(
            '
                UPDATE 
                  resources 
                SET
                  resource_minified = :min
                WHERE 
                  resource_id = :rid
            ',
            [
                'rid' => $this->request->get('resource_id'),
                'min' => $this->request->get('minified')
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
        ]);
    }

    public function apiPackage_change_name() {
        

        $title = trim($this->request->get('package_title'));

        $result = 3;

        if ($title != '') {
            $result = (int)$this->db->query('SELECT  dp_resources_package_change_name(:pid, :name) AS res', [
                'pid'  => $this->request->get('package_id'),
                'name' => $title
            ])->fetch()['res'];
        }

        $results = [
            0 => ['state' => 'yes'],
            1 => [
                'state' => 'no',
                'msg'   => 'Группа не существует'
            ],
            2 => [
                'state' => 'no',
                'msg'   => 'Такое имя уже занято'
            ],
            3 => [
                'state' => 'no',
                'msg'   => 'Название не может быть пустым'
            ]
        ];

        $this->sendResponseAjax($results[$result]);
    }

    public function apiPackage_file_sort() {
        $orderList = json_decode($this->request->get('order'), true);

        $this->db->begin();

        for($a = 0, $len = sizeof($orderList); $a < $len; $a++) {
            $resourceId = (int)$orderList[$a];

            $this->db->query(
                '
                    UPDATE resources 
                    SET resource_order = :order 
                    WHERE resource_id = :rid 
                ',
                [
                    'rid'   => $resourceId,
                    'order' => $a
                ]
            );
        }

        $this->db->commit();

        $this->sendResponseAjax(['state' => 'yes']);
    }

    public function apiPackage_add() {
        

        $typeId = (int)$this->request->getPost('type');
        $name   = mb_substr(trim($this->request->getPost('name')), 0, 32);

        if ($name == '') {
            $this->sendResponseAjax([
                'state' => 'no',
                'msg'   => 'Необходимо указать имя категории'
            ]);
        }

        $result = (int)$this->db->query(
            'SELECT dp_resources_package_add(:name, :type) AS res',
            [
                'name' => $name,
                'type' => $typeId
            ]
        )->fetch()['res'];

        $categoryHtml = '';
        $state = 'yes';
        $msg = '';

        if ($result > 0) {
            $packageId = $result;

            $controls = '';

            $controls .= $this->getActionLabel('Переместить пакет в конец списка',
                'ic-arrow6 deg180', 'grey', ' data-package="'.$packageId.'" ', ' link rcpResourcesPackageEnd', 'div', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Редактировать название группы',
                'ic-pencil', 'blue', ' data-package="'.$packageId.'" ', ' link rcpResourcesRenamePackage', 'div', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Прикрепить файлы к группе',
                'ic-plus', 'blue', ' href="/resources/js/group_add/'.$packageId.'" data-level="3"', ' link rcpRenameOut elizaHApi', 'a', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Удалить группу',
                'ic-cross', 'red', ' data-package="'.$packageId.'" ', ' link rcpResourcesDeletePackage', 'div', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Сохранить название',
                'ic-check', 'green', '', ' link rcpResourcesRenameSave', 'div', ' rcpRenameCls hide');

            $controls .= $this->getActionLabel('Отменить изменение',
                'ic-cross', 'red', '', ' link rcpResourcesRenameCancel', 'div', ' rcpRenameCls hide');

            $categoryHtml = $this->view->getPartial('partials/explorer/item_category', [
                'type'  => 'category',
                'count' => 0,
                'list'  => [],
                'dir'   => '',
                'move'  => ' move',
                'data'  => ' data-package="'.$packageId.'" ',
                'name'  => $name,
                'controls' => $controls
            ]);
        }
        else {
            $state = 'no';
            $msg   = 'Данное имя занято';
        }

        $this->sendResponseAjax([
            'state' => $state,
            'msg'   => $msg,
            'html'  => $categoryHtml
        ]);
    }

    public function apiPackage_end() {
        

        $this->db->query(
            'UPDATE resources_packages SET package_end = :state WHERE package_id = :pid',
            [
                'state' => (int)$this->request->getPost('state'),
                'pid'   => (int)$this->request->getPost('package_id')
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
        ]);
    }

    public function apiPackage_compress() {
        

        $this->db->query(
            'UPDATE resources_packages SET package_compress_group = :state WHERE package_id = :pid',
            [
                'state' => (int)$this->request->getPost('state'),
                'pid'   => (int)$this->request->getPost('package_id')
            ]
        );

        $this->sendResponseAjax([
            'state' => 'yes'
        ]);
    }

    public function apiPackage_delete() {
        

        $data = [
            'state' => 'yes',
            'msg'   => ''
        ];

        $res = (int)$this->db->query(
            'SELECT dp_resources_package_delete(:pid) AS res',
            ['pid' => (int)$this->request->getPost('package_id')]
        )->fetch()['res'];

        if ($res > 0) {
            $errors = [
                1 => 'Такого пакета не существует',
                2 => 'Данный пакет используется в приложениях',
                3 => 'Данный пакет используется в сайтах'
            ];

            $data['state'] = 'no';
            $data['msg']   = $errors[$res];
        }

        $this->sendResponseAjax($data);
    }

    protected function apiPackage_sort() {
        $orderList = json_decode($this->request->get('order'), true);

        $this->db->begin();

        for($a = 0, $len = sizeof($orderList); $a < $len; $a++) {
            $packageIdId = (int)$orderList[$a];

            $this->db->query(
                '
                    UPDATE resources_packages 
                    SET package_order = :order 
                    WHERE package_id = :pid
                ',
                [
                    'pid'   => $packageIdId,
                    'order' => $a
                ]
            );
        }

        $this->db->commit();

        $this->sendResponseAjax(['state' => 'yes']);

    }

    private function showFilesFolderForGroups($type) {
        $typeData = $this->fileTypes[$type];

        $t = $this->t;

        $itemsList = [];

        $packages = $this->db->query(
            'SELECT * FROM resources_packages_system WHERE type_id=:tid',
            ['tid' => $typeData['id']]
        );

        $packagesIds = [];

        $packagesMap = [];

        $idx = 0;

        while($row = $packages->fetch()) {
            $packagesMap[$row['package_id']] = $idx;

            $endColor  = 'grey';
            $endActive = '';

            $compressColor  = 'grey';
            $compressActive = '';

            if ($row['package_end'] == 1) {
                $endColor  = 'green';
                $endActive = ' active';
            }

            if ($row['package_compress_group'] == 1) {
                $compressColor  = 'purple';
                $compressActive = ' active';
            }

            $controls = '';

            $controls .= $this->getActionLabel('Сжать группу и подключать асинхронно',
                'ic-box', $compressColor, ' data-package="'.$row['package_id'].'" ', ' link rcpResourcesPackageCompress'.$compressActive, 'div', ' rcpRenameCls');


            $controls .= $this->getActionLabel('Переместить пакет в конец списка',
                'ic-arrow6 deg180', $endColor, ' data-package="'.$row['package_id'].'" ', ' link rcpResourcesPackageEnd'.$endActive, 'div', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Редактировать название группы',
                'ic-pencil', 'blue', ' data-package="'.$row['package_id'].'" ', ' link rcpResourcesRenamePackage', 'div', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Прикрепить файлы к группе',
                'ic-plus', 'blue', ' href="/resources/'.$typeData['name'].'/group_add/'.$row['package_id'].'" data-level="3"', ' link rcpRenameOut elizaHApi', 'a', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Удалить группу',
                'ic-cross', 'red', ' data-package="'.$row['package_id'].'" ', ' link rcpResourcesDeletePackage', 'div', ' rcpRenameCls');

            $controls .= $this->getActionLabel('Сохранить название',
                'ic-check', 'green', '', ' link rcpResourcesRenameSave', 'div', ' rcpRenameCls hide');

            $controls .= $this->getActionLabel('Отменить изменение',
                'ic-cross', 'red', '', ' link rcpResourcesRenameCancel', 'div', ' rcpRenameCls hide');

            $itemsList[] = [
                'type'  => 'category',
                'count' => 0,
                'list'  => [],
                'dir'   => '',
                'move'  => ' move',
                'data'  => ' data-package="'.$row['package_id'].'" ',
                'name'  => $row['package_name'],
                'controls' => $controls
            ];

            $packagesIds[] = $row['package_id'];

            $idx++;
        }

        $resources = $this->db->query(
            '
                SELECT * FROM resources_system 
                WHERE type_id=:tid AND package_id IN ('.implode(',', $packagesIds).')
            ',
            ['tid' => $typeData['id']]
        );

        $compressControls = function() {
            return '';
        };

        if ($type == 'js') {
            $compressControls = function($row) {
                return $this->getActionLabel('Файл минимизирован разработчиком',
                    'ic-contract',
                    $row['resource_minified'] ? 'green' : 'grey',
                    ' data-resource="'.$row['resource_id'].'" ',
                    ' link rcpResourcesCompressFile'.($row['resource_minified'] ? ' active' : '')
                );
            };
        }

        while($row = $resources->fetch()) {
            $idx = $packagesMap[$row['package_id']];

            $controls = '';

            $controls .= $compressControls($row);

            $controls .= $this->getActionLabel('Отвязать файл',
                'ic-cross', 'red', ' data-resource="'.$row['resource_id'].'" ', ' link rcpResourcesRemoveFileFromPackage');

            $itemsList[$idx]['count'] += 1;
            $itemsList[$idx]['list'][] = [
                'type' => 'item',
                'dir'  => $row['resource_dir'],
                'move' => ' move',
                'data'  => ' data-package="'.$row['package_id'].'" data-resource="'.$row['resource_id'].'" ',
                'file_color' => $typeData['color'],
                'file_name'  => $row['resource_dir'].$row['resource_name'],
                'file_type'  => $row['type_file_extension'],
                'controls' => $controls
            ];
        }

        $this->view->setVar('res_local_footer', [
            [
                'element_type'  => 'input',
                'element_width' => 'w300',
                'type' => 'text',
                'placeholder' => $t->_('rcp_res_group_add_ph'),
                'id' => 'rcpResourcesGroupAddName'
            ],
            [
                'element_type'  => 'button',
                'element_width' => '',
                'text' => $t->_('rcp_res_group_add_bt'),
                'id' => 'rcpResourcesGroupAddButton',
                'variant' => 'send'
            ]
        ]);

        return $itemsList;
    }

    private function showFilesForCSSSitesApps($mode) {
        $typeData = $this->fileTypes['css'];

        $modes = [
            'sites' => [
                'id'    => 'site_id',
                'name'  => 'site_name',
                'label' => 'Прикрепить файлы к сайту',
                'adding' => 'site_add',
                'pck_db' => 'select * from sites order by site_id asc',
                'res_db' => 'select * from resources_sites_css'

            ],
            'apps'  => [
                'id'    => 'app_id',
                'name'  => 'app_name',
                'label' => 'Прикрепить файлы к приложению',
                'adding' => 'app_add',
                'pck_db' => 'select * from apps order by app_id asc',
                'res_db' => 'select * from resources_apps_css'
            ]
        ];

        $mode = $modes[$mode];

        $itemsList = [];

        $packages = $this->db->query($mode['pck_db']);

        $packagesMap = [];

        $idx = 0;


        while($row = $packages->fetch()) {
            $itemId   = $row[$mode['id']];
            $itemName = $row[$mode['name']];

            $packagesMap[$itemId] = $idx;

            $controls = '';

            if ($mode['id'] == 'site_id') {
                $controls .= $this->getActionLabel('Перейти в раздел сайта',
                    'ic-earth', 'green', ' href="/sites/edit/'.$itemId.'/resources" data-level="3"', ' link rcpRenameOut elizaHApi', 'a', ' rcpRenameCls');
            }

            $controls .= $this->getActionLabel($mode['label'],
                'ic-plus', 'blue', ' href="/resources/'.$typeData['name'].'/'.$mode['adding'].'/'.$itemId.'" data-level="3"', ' link rcpRenameOut elizaHApi', 'a', ' rcpRenameCls');


            $itemsList[] = [
                'type'  => 'category',
                'count' => 0,
                'list'  => [],
                'dir'   => '',
                'move'  => '',
                'data'  => ' data-package="'.$itemId.'" ',
                'name'  => $itemName,
                'controls' => $controls
            ];


            $idx++;
        }

        $resources = $this->db->query($mode['res_db']);

        while($row = $resources->fetch()) {
            $itemId   = $row[$mode['id']];

            $idx = $packagesMap[$itemId];

            $controls = '';
            $controls .= $this->getActionLabel('Отвязать файл',
                'ic-cross', 'red', ' data-resource="'.$row['resource_id'].'" ', ' link rcpResourcesRemoveFileFromPackage');

            $itemsList[$idx]['count'] += 1;
            $itemsList[$idx]['list'][] = [
                'type' => 'item',
                'dir'  => $row['resource_dir'],
                'move' => ' move',
                'data'  => ' data-package="'.$itemId.'" data-resource="'.$row['resource_id'].'" ',
                'file_color' => $typeData['color'],
                'file_name'  => $row['resource_dir'].$row['resource_name'],
                'file_type'  => $row['type_file_extension'],
                'controls' => $controls
            ];
        }

        return $itemsList;
    }


    private function explorerScanDir($mode, $dir, $typeData, $groupId = 0) {
        $handle = opendir(DIR_PUBLIC.$dir);

        $typeColor = $typeData['color'];
        $typeName  = $typeData['name'];

        if (!$handle) {
            return [];
        }

        $explorerSql = [
            'css' => 'SELECT * FROM resources_explorer_css_blocks WHERE file_dir = :dir',
            'js'  => 'SELECT * FROM resources_explorer_js_blocks  WHERE file_dir = :dir'
        ];

        // Получаем список блокируемых файлов для указанной папки
        $blocks = $this->db->query($explorerSql[$typeName], ['dir' => $dir]);

        if ($typeData['name'] == 'css') {
            return $this->explorerScanDirCss($mode, $dir, $typeData, $groupId, $blocks, $handle);
        }

        $blockList   = []; // Список файлов, на которые накладываются ограничения по функциям
        $blockGroups = []; // Список пользовательски групп, в которых зарегистрирован файл

        while($row = $blocks->fetch()) {
            $blockList[$row['file_name']] = [
                'color' => $row['block_color'],
                'name'  => $row['block_name'],
                'level' => $row['file_block']
            ];

            $blockGroups[$row['file_name']] = [];
            $packages = explode(',', $row['package_ids']);

            // Получаем список пользовательских групп файла
            for($a = 0, $len = sizeof($packages); $a < $len; $a++) {
                $blockGroups[$row['file_name']][$packages[$a]] = 1;
            }
        }

        $allBlock = 0; // Заблокировать все файл в папке

        // Если в списке блокируемых находится только 1 файл и он называется *.fileType - значит надо заблокировать все
        // файлы внутри папки
        if (sizeof($blockList) == 1 && isset($blockList['*.'.$typeName])) {
            $allBlock = 1;
        }

        // Извлечение фалой в и папок из сканируемой папки
        //--------------------------------------------------------------------------------------------------------------
        $dirList  = []; // Список папок
        $fileList = []; // Список файлов

        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $address = DIR_PUBLIC.$dir.$entry;

            if (is_dir($address)) {
                $dirList[] = $entry;
            }
            else if (is_file($address)) {
                $exp = explode('.', $entry);
                $type = $exp[sizeof($exp) - 1];

                if ($type != $typeName) {
                    continue;
                }

                $fileList[] = $entry;
            }
        }

        closedir($handle);

        sort($dirList);  // Сортируем папки
        sort($fileList); // Сортируем файлы


        // Заполняем список эллементов для проводника
        //--------------------------------------------------------------------------------------------------------------
        $itemsList = []; // Эллементы проводника

        $dirControls = [
            'explorer' => function() use ($typeName) {
                // Файлы можно добавлять только тогда, когда мы в обычно проводнике
                $controls = '';
                $controls .= $this->getActionLabel('Загрузить файлы в директорию',
                    'ic-upload', 'green', ' data-type="'.$typeName.'"', ' link uploadFilesButton');

                return $controls;
            },
            'group_add'   => function() {return '';},
            'require_add' => function() {return '';}
        ];

        // Если блокируются все файлы в папке, то мы для описания сущности блокировки мы берем данные по блокировке
        // псевдофайла *.fileType, иначе, мы ищем наличие какой либо блокировки для указанного файла
        // если никаких блокировок нет, будет возвращен FALSE
        $fileBlockStandard = function($allBlock, $blockList, $item) use ($typeName) {
            return $blockList[$allBlock ? '*.'.$typeName : $item] ?? false;
        };

        $fileBlockers = [
            'explorer' => $fileBlockStandard,
            'group_add'   => function($allBlock, $blockList, $item) use ($fileBlockStandard, $blockGroups, $groupId) {
                $block = $fileBlockStandard($allBlock, $blockList, $item);

                // если стандартная проверка вернула блокировку, но уровень блокировки 2 и группа, для которой запущен
                // проводник не присутствует у файла, то мы считаем что этот файл не обладает блокировкой для указанной
                // группы
                return $block && $block['level'] == 2 && !isset($blockGroups[$item][$groupId]) ? false : $block;
            },
            'require_add' => $fileBlockStandard
        ];

        $fileControlBlock = function($fileBlocked) {
            return $fileBlocked ?
                $this->getActionLabel($fileBlocked['name'],
                    'ic-link', $fileBlocked['color'], '', '')
                : '';
        };
        $fileControlAttach = function($fileBlocked) {
            return !$fileBlocked ?
                $this->getActionLabel('Выбрать файл',
                    'ic-check', 'grey', '', ' link selectFileToGroup')
            : '';
        };

        $fileControlAdding = function($fileBlocked) use($fileControlBlock, $fileControlAttach) {
            return $fileControlBlock($fileBlocked).$fileControlAttach($fileBlocked);
        };

        $fileControls = [
            'explorer' => function($fileBlocked) use ($fileControlBlock) {
                $controls = $fileControlBlock($fileBlocked);

                $controls .= $this->getActionLabel('Заменить содержимое',
                    'ic-upload', 'indigo 900', '', ' link replaceFileButton');

                $deleteLink = ' link deleteFileButton';
                $deleteColor = 'red';
                $deleteTitle = 'Удалить файл';

                if ($fileBlocked) {
                    $deleteLink = '';
                    $deleteColor = 'grey';
                    $deleteTitle = 'Удаление невозможно: файл используется';
                }

                $controls .= $this->getActionLabel($deleteTitle,
                    'ic-cross', $deleteColor, '', $deleteLink);

                return $controls;
            },
            'group_add'   => $fileControlAdding,
            'require_add' => $fileControlAdding
        ];

        // Выбираем реализацию функции для инициализации эллементов управления папки
        $dirControl  = $dirControls[$mode];

        // Выбираем реализацию функции для определения блокировки файла
        $fileBlocker = $fileBlockers[$mode];

        // Выбираем реализацию функции для инициализации эллементов управления файлом
        $fileControl = $fileControls[$mode];

        // Сначала добавляем папки
        for($i = 0, $len = sizeof($dirList); $i < $len; $i++) {
            $itemsList[] = [
                'type'  => 'category',
                'count' => 1,
                'list'  => [],
                'dir'   => $dir,
                'name'  => $dirList[$i],
                'controls' => $dirControl()
            ];
        }

        for($i = 0, $len = sizeof($fileList); $i < $len; $i++) {
            $item = $fileList[$i];

            $itemsList[] = [
                'type' => 'item',
                'dir'  => $dir,
                'move' => '',
                'file_color' => $typeColor,
                'file_name'  => $item,
                'file_type'  => $typeName,
                'controls' => $fileControl($fileBlocker($allBlock, $blockList, $item))
            ];
        }

        return $itemsList;
    }

    private function explorerScanDirCss($mode, $dir, $typeData, $groupId, ResultInterface $blocks, $handle) {
        $typeColor = $typeData['color'];
        $typeName  = $typeData['name'];

        $modes = [
            'group_add' => 'package',
            'site_add'  => 'site',
            'app_add'   => 'app',
            'explorer'  => 'explorer'
        ];

        $blockType = $modes[$mode];


        $blockList   = []; // Список файлов, на которые накладываются ограничения по функциям
        $blockGroups = []; // Список пользовательски групп, в которых зарегистрирован файл

        while($row = $blocks->fetch()) {
            if (!isset($blockList[$row['file_name']])) {
                $blockList[$row['file_name']] = [
                    'package' => [],
                    'site'    => [],
                    'app'     => []
                ];
            }

            $blockList[$row['file_name']][$row['block_type']] = [
                'color' => $row['block_color'],
                'name'  => $row['block_name'],
                'level' => $row['file_block']
            ];

            // Если текущий режим соотвестввует режиму блокировки записи
            // тогда добавляем файлу группы, в которых он заблокирован
            if ($blockType == $row['block_type']) {
                $blockGroups[$row['file_name']] = [];
                $packages = explode(',', $row['package_ids']);

                // Получаем список пользовательских групп файла
                for($a = 0, $len = sizeof($packages); $a < $len; $a++) {
                    $blockGroups[$row['file_name']][(int)$packages[$a]] = 1;
                }
            }
            else if($mode == 'explorer') { // Если режим проводника, то любой вариант блокирует файл
                $blockGroups[$row['file_name']] = 1;
            }
        }

        // Извлечение фалой в и папок из сканируемой папки
        //--------------------------------------------------------------------------------------------------------------
        $dirList  = []; // Список папок
        $fileList = []; // Список файлов

        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $address = DIR_PUBLIC.$dir.$entry;

            if (is_dir($address)) {
                $dirList[] = $entry;
            }
            else if (is_file($address)) {
                $exp = explode('.', $entry);
                $type = $exp[sizeof($exp) - 1];

                if ($type != $typeName) {
                    continue;
                }

                $fileList[] = $entry;
            }
        }

        closedir($handle);

        sort($dirList);  // Сортируем папки
        sort($fileList); // Сортируем файлы



        // Заполняем список эллементов для проводника
        //--------------------------------------------------------------------------------------------------------------
        $itemsList = []; // Эллементы проводника

        $controls = [
            'add' => function($blockGroups, $item, $groupId) {
                return !isset($blockGroups[$item][$groupId]) ? $this->getActionLabel('Выбрать файл',
                    'ic-check', 'grey', '', ' link selectFileToGroup') : '';
            },
            'explorer' => function($blockGroups, $item) {


                $controls = $this->getActionLabel('Заменить содержимое',
                    'ic-upload', 'indigo 900', '', ' link replaceFileButton');

                $deleteLink = ' link deleteFileButton';
                $deleteColor = 'red';
                $deleteTitle = 'Удалить файл';

                if (isset($blockGroups[$item])) {
                    $deleteLink = '';
                    $deleteColor = 'grey';
                    $deleteTitle = 'Удаление невозможно: файл используется';
                }

                $controls .= $this->getActionLabel($deleteTitle,
                    'ic-cross', $deleteColor, '', $deleteLink);

                return $controls;
            }
        ];

        $controlFunction = $controls[$mode == 'explorer' ? 'explorer' : 'add'];

        // Сначала добавляем папки
        for($i = 0, $len = sizeof($dirList); $i < $len; $i++) {
            $itemsList[] = [
                'type'  => 'category',
                'count' => 1,
                'list'  => [],
                'dir'   => $dir,
                'name'  => $dirList[$i],
                'controls' => ''
            ];
        }

        for($i = 0, $len = sizeof($fileList); $i < $len; $i++) {
            $item = $fileList[$i];

            $controlsHtml = '';

            if (isset($blockList[$item])) {
                foreach($blockList[$item] as $fileBlocked) {
                    if (isset($fileBlocked['name'])) {
                        $controlsHtml .= $this->getActionLabel($fileBlocked['name'],
                            'ic-link', $fileBlocked['color'], '', '');

                    }
                }
            }

            $controlsHtml .= $controlFunction($blockGroups, $item, $groupId);


            $itemsList[] = [
                'type' => 'item',
                'dir'  => $dir,
                'move' => '',
                'file_color' => $typeColor,
                'file_name'  => $item,
                'file_type'  => $typeName,
                'controls'   => $controlsHtml
            ];
        }

        return $itemsList;
    }

    private function explorerAddMode($mode, $groupId, $localTitle, $dir, $type) {
        $this->explorerSystem($mode, $dir, $type, $groupId);

        $this->view->setVar('res_local_title', $localTitle);

        $this->view->setVar('res_local_footer', [
            [
                'element_type'  => 'button',
                'element_width' => '',
                'text' => $this->t->_('rcp_res_clear_files_add'),
                'id' => 'rcpResourcesFilesAddToGroup',
                'disabled' => 1,
                'variant'  => 'success'
            ],
            [
                'element_type'  => 'button',
                'element_width' => '',
                'text' => $this->t->_('rcp_res_clear_files'),
                'id' => 'rcpResourcesFilesClearButton',
                'variant' => 'default',
                'icon' => 'ic-selectremove',
                'disabled'  => 1,
                'icon_only' => 1
            ]
        ]);

    }


    private function createLocalBookmarks($currentPage, $type) {
        $Bookmarks = new Bookmarks();

        $t = $this->t;

        $lng = function($num) use ($t) {
            return $t->_('rcp_res_sbook_'.$num);
        };

        $hrefBase = '/resources/'.$type;

        $n2 = 'sites';
        $n3 = 'apps';
        $n4 = 'requires';

        $books = [
            ['',  $lng(1), $hrefBase,         2, 0, '', '', ''],
            [$n2, $lng(2), $hrefBase.'/'.$n2, 2, 0, '', '', ''],
            [$n3, $lng(3), $hrefBase.'/'.$n3, 2, 0, '', '', '']
        ];

        if ($type == 'js') {
            $books[] = [$n4, $lng(4), $hrefBase.'/'.$n4, 2, 0, '', '', ''];
        }

        $Bookmarks->addBookMarksFromArray($books);

        if (!$Bookmarks->isBookMarkExists($currentPage)) {
            return;
        }

        $Bookmarks->setActive($currentPage);

        $this->attachHapiCallback(1, 'defaultBookmarksClick', '', []);
        $this->view->setVar('res_local_books', $Bookmarks->getBookMarks());
        $this->view->setTemplateBefore('localbookmarks_wrap');
    }

    /**
     * @param string $type
     * @param string $addLink
     * @param $groupId
     */
    private function setBaseData($type, $addLink = '', $groupId = 0) {
        $groupId = (int)$groupId;

        $title = $this->t->_('rcp_res_title', ['name' => strtoupper($type)]);

        $editUriBase = '/resources/'.$type;

        $Bookmarks = new Bookmarks();

        $t = $this->t;

        $lng = function($num) use ($t) {
            return $t->_('rcp_res_book_'.$num);
        };

        $hrefBase = '/resources/'.$type;

        $n2 = 'explorer';

        $books = [
            ['',  $lng(1), $hrefBase,         3, 1, '', ' elzPLT', ' data-bg="blue" data-fn="white"'],
            [$n2, $lng(2), $hrefBase.'/'.$n2, 3, 0, '', ' elzPLT', ' data-bg="blue" data-fn="white"']
        ];

        $allowed = [
            '' => 1,
            $n2 => 1,
            'require_add' => 1,
            'group_add/'.$groupId => 1,
            'site_add/'.$groupId  => 1,
            'app_add/'.$groupId   => 1
        ];

        if ($groupId > 0 || $addLink == 'require_add') {
            $books[] = [$addLink, $lng(3), $hrefBase.'/'.$addLink, 3, 0, '', ' elzPLT', ' data-bg="red" data-fn="white"'];
        }

        $Bookmarks->addBookMarksFromArray($books);

        if (isset($allowed[$addLink])) {
            $Bookmarks->setActive($addLink);
        }

        $this->view->setVar('bookMarks', $Bookmarks->getBookMarks());

        $this->view->setVar('content_wrap_title', $title);

        $this->breadcrumbs->addCrumb($editUriBase, $title, View::LEVEL_LAYOUT);

        $this->site->setMetaTitle($title);

        $this->attachHapiCallback(3, 'defaultMainMenuClick', '', []);

        $this->view->setVar('rcpMainMenuActiveItem', 'rcp_menu_resources_'.$type);

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