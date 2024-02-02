<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;
use Site\Front\Handlers\ContactsHandler;
use Site\Front\Handlers\FilesHandler;

/**
 * Class CounteragentsController
 * @package Site\Front\Controllers
 */
class ContactsController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('contacts');
    }

    public function indexAction()
    {
        $this->response->redirect('/contacts/roles', true);
    }

    public function rolesAction()
    {
        if ($this->request->isPost()) {
            $act = $this->request->get('act');

            if ($act == 'role_create') {
                $this->roleCreate();
            }
            else if ($act == 'role_update') {
                $this->roleUpdate();
            }
            else if ($act == 'role_delete') {
                $this->roleDelete();
            }
        }
        
        $this->view->setVar('mainMenuData', [
            'active' => 'pm_dict_contacts_roles',
            'overlay' => 1,
            'template' => 'partials/menu/menu_dicts',
            'icon' => 'users1.svg',
            'title' => 'Роли контактов',
        ]);

        $this->hapi->setHapiAction('roles');

        $types = $this->db->query(
            '
                SELECT ct.contact_type_id
                     , ct.type_title
                FROM pm.contact_type AS ct
                WHERE ct.type_hide = 0
                ORDER BY ct.type_order
            '
        );

        $typesMap = [];

        while ($row = $types->fetch()) {
            $typesMap[$row['contact_type_id']] = [
                'contact_type_id' => $row['contact_type_id'],
                'type_title' => $row['type_title'],
                'can_edit' => 1,
                'counts' => [
                    'contacts' => 0,
                    'roles' => 0
                ],
                'list' => []
            ];
        }

        $roles = $this->db->query(
            '
               SELECT cr.contact_role_id
                    , cr.contact_type_id
                    , cr.role_title
                    , COUNT(DISTINCT c.contact_id) AS contact_count
               FROM pm.contact_role AS cr
                    LEFT JOIN pm.contact AS c 
                         ON   c.contact_role_id = cr.contact_role_id
               GROUP BY cr.contact_role_id
                      , cr.contact_type_id
                      , cr.role_title
               ORDER BY cr.role_title
            '
        );

        while ($row = $roles->fetch()) {
            $typesMap[$row['contact_type_id']]['counts']['contacts']++;
            $typesMap[$row['contact_type_id']]['counts']['roles'] += $row['contact_count'];
            $typesMap[$row['contact_type_id']]['list'][] = $row;
        }

        $this->view->setVar('contactTypesMap', $typesMap);
    }

    private function roleCreate()
    {
        $roleTypeId = trim($this->request->get('type'));
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.contact_role_create(:tid, :mid, :title) AS res',
            [
                'tid' => $roleTypeId,
                'mid' => $this->user->getId(),
                'title' => $title
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Указанный тип не существует',
                    -3 => 'Название занято'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result,
            'html' => $this->view->getPartial(
                'contacts/partials/role_item',
                [
                    'contact_role_id' => $result,
                    'role_title' => $title,
                    'contact_count' => 0
                ]
            )
        ]);
    }

    private function roleUpdate()
    {
        $id = (int)$this->request->get('id');
        $title = trim($this->request->getPost('title'));

        $result = (int)$this->db->query(
            'SELECT pm.contact_role_update(:id, :mid, :title) AS res',
            [
                'id' => $id,
                'mid' => $this->user->getId(),
                'title' => $title
            ]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Название не может быть пустым',
                    -2 => 'Указанный тип не существует',
                    -3 => 'Название занято'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function roleDelete()
    {
        $id = (int)$this->request->get('contact_role_id');

        $result = (int)$this->db->query(
            'SELECT pm.contact_role_delete(:id) AS res',
            ['id' => $id]
        )->fetch()['res'];

        if ($result < 0) {
            $this->sendResponseAjax([
                'state' => 'no',
                'result' => $result,
                'notification' => [
                    -1 => 'Роль связана с контактами'
                ][$result]
            ]);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'result' => $result
        ]);
    }

    private function signerFileLink($contactId, $isSigner) {
        $signerFile = $_FILES['signer_file'] ?? [];

        if (!($signerFile['error'] ?? 1)) {
            $fileHandler = new FilesHandler((int)$this->db->query(
                'SELECT file_node_id FROM pm.contact WHERE contact_id = :cid',
                ['cid' => $contactId]
            )->fetch()['file_node_id']);

            $fileId = $fileHandler->addFile(1, $signerFile);

            $fileHandler->linkSingleTrait($fileId, 1);
        }

        $this->db->query(
            '
                SELECT pm.contact_update_sign_right(:cid, :sign)
            ',
            [
                'cid' => $contactId,
                'sign' => $isSigner ? 1 : 0,
            ]
        );
    }

    protected function apiDictionaries() {
        $contactTypes = $this->db->query(
            '
                SELECT ct.contact_type_id 
                     , ct.type_title
                     , ct.type_name
                FROM pm.contact_type AS ct 
                WHERE ct.type_hide = 0
                ORDER BY ct.type_title
            '
        )->fetchAll();

        $contactRoles = $this->db->query(
            '
                SELECT cr.contact_role_id
                     , cr.contact_type_id
                     , cr.role_title
                FROM pm.contact_role AS cr 
                ORDER BY cr.role_title
            '
        )->fetchAll();

        $infoTypes = $this->db->query(
            '
                SELECT cit.contact_info_type_id
                     , cit.type_title
                     , cit.type_input
                FROM pm.contact_info_type AS cit 
                ORDER BY cit.type_order
            '
        )->fetchAll();

        $contactTypeInfos = $this->db->query(
            '
                SELECT cti.contact_type_id
                     , cti.contact_info_type_id
                FROM pm.contact_type_info AS cti 
            '
        )->fetchAll();

        $genders = $this->db->query(
            '
                SELECT cpg.contact_personal_gender_id
                     , cpg.gender_title
                FROM pm.contact_personal_gender AS cpg 
            '
        )->fetchAll();

        $traits = $this->db->query(
            '
                SELECT ct.contact_trait_id
                     , ct.trait_title
                     , ct.trait_description
                     , ct.trait_icon
                     , ct.trait_color_bg
                     , ct.trait_color_fn
                FROM pm.contact_trait AS ct 
                ORDER BY trait_order
            '
        )->fetchAll();

        $this->sendResponseAjax([
            'state' => 'yes',
            'contact_types' => $contactTypes,
            'contact_type_infos' => $contactTypeInfos,
            'contact_roles' => $contactRoles,
            'info_types' => $infoTypes,
            'genders' => $genders,
            'traits' => $traits
        ]);
    }

    protected function apiTemplate_item() {
        $contactId = (int)$this->request->getPost('contact_id');
        $contactNodeId = (int)$this->request->getPost('contact_node_id');

        $this->sendResponseAjax([
            'state' => 'yes',
            'html' => $this->view->getPartial(
                'contacts/partials/contact_item',
                (new ContactsHandler($contactNodeId))->getContact($contactId)
            )
        ]);
    }

    protected function apiContact_create() {
        $contactNodeId = (int)$this->request->getPost('contact_node_id');
        $contactTypeId = (int)$this->request->getPost('contact_type_id');
        $contactData = json_decode($this->request->getPost('contact_data'), true);


        $contactRoleId = (int)$contactData['role'];

        $payload = [
            'comment' => trim($contactData['comment']),
            'info' => []
        ];

        if ($contactTypeId == 3) { // службы
            $payload['nickname'] = trim($contactData['nickname']);
        }
        else if ($contactTypeId == 4) { // сотрудник
            $payload['personal'] = [
                'name' => trim($contactData['name']),
                'surname' => trim($contactData['surname']),
                'patronymic' => trim($contactData['patronymic']),
                'birthdate' => trim($contactData['birthdate']),
                'gender' => (int)$contactData['gender']
            ];
        }

        $infoList = $contactData['info'];

        for($a = 0, $len = sizeof($infoList); $a < $len; $a++) {
            $info = $infoList[$a];
            $val = trim($info['val']);
            $extra = trim($info['extra']);

            if ($val == '') {
                continue;
            }

            $payload['info'][] = [
                'tid' => (int)$info['tid'],
                'val' => $val,
                'extra' => $extra
            ];
        }

        $contactId = (int)$this->db->query(
            '
                SELECT pm.contact_create(:mid, :nid, :tid, :rid, :json) AS res
            ',
            [
                'mid' => $this->user->getId(),
                'nid' => $contactNodeId,
                'tid' => $contactTypeId,
                'rid' => $contactRoleId,
                'json' => json_encode($payload)
            ]
        )->fetch()['res'];

        if ($contactData['signer'] ?? 0) {
            $this->signerFileLink($contactId, (int)$contactData['signer']);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'contact_id' => $contactId
        ]);
    }

    protected function apiContact_update() {
        $contactId = (int)$this->request->getPost('contact_id');
        $contactNodeId = (int)$this->request->getPost('contact_node_id');
        $contactTypeId = (int)$this->request->getPost('contact_type_id');
        $contactData = json_decode($this->request->getPost('contact_data'), true);


        $contactRoleId = (int)$contactData['role'];

        $payload = [
            'comment' => trim($contactData['comment']),
            'info' => [],
            'info_update' => [],
            'info_delete' => []
        ];

        if ($contactTypeId == 3) { // службы
            $payload['nickname'] = trim($contactData['nickname']);
            $payload['work'] = [
                'begin' => $contactData['work_begin'].':00',
                'end'   => $contactData['work_end'].':00',
                'days'   => [],
            ];

            $workDays = json_decode($contactData['work_days'] ?? '[]', true);

            for($a = 0, $len = sizeof($workDays); $a < $len; $a++) {
                $payload['work']['days'][] = ['day' => (int)$workDays[$a]];
            }
        }
        else if ($contactTypeId == 4) { // сотрудник
            $payload['personal'] = [
                'name' => trim($contactData['name']),
                'surname' => trim($contactData['surname']),
                'patronymic' => trim($contactData['patronymic']),
                'birthdate' => trim($contactData['birthdate']),
                'gender' => (int)$contactData['gender']
            ];
        }

        $infoList = $contactData['info'];

        for($a = 0, $len = sizeof($infoList); $a < $len; $a++) {
            $info = $infoList[$a];
            $val = trim($info['val']);
            $extra = trim($info['extra']);

            if ($val == '') {
                continue;
            }

            $payload['info'][] = [
                'tid' => (int)$info['tid'],
                'val' => $val,
                'extra' => $extra
            ];
        }

        $infoUpdateList = $contactData['info_update'];

        for($a = 0, $len = sizeof($infoUpdateList); $a < $len; $a++) {
            $info = $infoUpdateList[$a];
            $val = trim($info['val']);
            $extra = trim($info['extra']);

            if ($val == '') {
                continue;
            }

            $payload['info_update'][] = [
                'id' => (int)$info['id'],
                'tid' => (int)$info['tid'],
                'val' => $val,
                'extra' => $extra
            ];
        }

        $infoDeleteList = $contactData['info_delete'];

        for($a = 0, $len = sizeof($infoDeleteList); $a < $len; $a++) {
            $payload['info_delete'][] = [
                'id' => (int)$infoDeleteList[$a]
            ];
        }

        $result = (int)$this->db->query(
            '
                SELECT pm.contact_update(:mid, :cid, :nid, :tid, :rid, :json) AS res
            ',
            [
                'mid' => $this->user->getId(),
                'cid' => $contactId,
                'nid' => $contactNodeId,
                'tid' => $contactTypeId,
                'rid' => $contactRoleId,
                'json' => json_encode($payload)
            ]
        )->fetch()['res'];

        if ($contactData['signer'] ?? 0) {
            $this->signerFileLink($contactId, (int)$contactData['signer']);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'contact_id' => $contactId,
            'result' => $result
        ]);
    }

    protected function apiContact_get() {
        $contactNodeId = (int)$this->request->getPost('contact_node_id');
        $contactId = (int)$this->request->getPost('contact_id');

        $ContactHandler = new ContactsHandler($contactNodeId);

        $contactData = $ContactHandler->getContact($contactId);

        if (!$contactData) {
            $this->sendResponseAjax(['state' => 'no']);
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'contact' => $contactData,
            'dict' => []
        ]);
    }

    protected function apiContact_trait_toggle() {
        $action = $this->request->getPost('action') === 'unlink' ? 'unlink' : 'link';
        $contactId = (int)$this->request->getPost('contact_id');
        $contactNodeId = (int)$this->request->getPost('contact_node_id');
        $traitId = (int)$this->request->getPost('trait_id');
        $traitType = $this->request->getPost('trait_type') === 'node' ? 'node' : 'contact';

        $result = (int)$this->db->query(
            'SELECT pm.contact_trait_'.$action.'(:nid, :cid, :tid, :type) AS res',
            [
                'nid' => $contactNodeId,
                'cid' => $contactId,
                'tid' => $traitId,
                'type' => $traitType,
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state'  => 'yes',
            'result' => $result
        ]);
    }

    protected function apiContact_active_change() {
        $contactId = (int)$this->request->getPost('contact_id');
        $contactActive = (int)$this->request->getPost('contact_active');

        $result = (int)$this->db->query(
            'SELECT pm.contact_active_change(:mid, :cid, :active) AS res',
            [
                'mid' => $this->user->getId(),
                'cid' => $contactId,
                'active' => $contactActive,
            ]
        )->fetch()['res'];

        $this->sendResponseAjax([
            'state'  => 'yes',
            'result' => $result
        ]);
    }
}