<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * @package Site\Front\Controllers
 */
class AddressesController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('addresses');
    }

    public function indexAction()
    {



    }

    public function suggestionAction() {

        $boost = [[ "kladr_id" => "6300000700000" ]];
        $response = $result = $this->daData->suggest("address", "авто", 5, ["locations_boost" => $boost]);

        echo '<pre>';
        print_r($result);
        exit;
    }

    protected function apiAddressChange() {
        $nodeId = (int)$this->request->getPost('node_id');
        $typeId = (int)$this->request->getPost('type_id');
        $parts = json_decode($this->request->getPost('parts'), true);
        $house = json_decode($this->request->getPost('house'), true);

        $partsList = [];
        $codesList = [];

        $extension = [
            'entrance' => '',
            'floor' => '',
            'flat' => ''
        ];

        foreach($parts as $partName => $partData) {
            $value = trim($partData['value'] ?? '');

            if (isset($extension[$partName])) {
                $extension[$partName] = $value;
                continue;
            }

            $code = $partData['code'] ?? '';

            if ($code) {
                $codesList[] = ['name' => $partName, 'code' => $code];
            }

            if ($value) {
                $partsList[] = ['name' => $partName, 'part' => $value];
            }
        }

        $codesList[] = ['name' => 'address', 'code' => $house['fias_id']];
        $partsList[] = ['name' => 'address', 'part' => $house['address_unrestricted']];

        $addressDictId = (int)$this->db->query(
            'SELECT pm.address_dict_create_fias(:fias, :house, :parts, :codes) AS res',
            [
                'fias' => $house['fias_id'],
                'house' => json_encode($house),
                'parts' => json_encode($partsList),
                'codes' => json_encode($codesList),
            ]
        )->fetch()['res'];

        $addressId = (int)$this->db->query(
            'SELECT pm.address_create(:did::integer, :entrance::varchar, :floor::varchar, :flat::varchar) AS res',
            [
                'did' => $addressDictId,
                'flat' => $extension['flat'],
                'floor' => $extension['floor'],
                'entrance' => $extension['entrance'],
            ]
        )->fetch()['res'];

        $this->db->query(
            'SELECT pm.address_node_address_update(:nid, :aid, :tid)',
            [
                'nid' => $nodeId,
                'aid' => $addressId,
                'tid' => $typeId,
            ]
        );

        $addressFull = $this->db->query(
            '
                SELECT COALESCE(adp.part_value || COALESCE(NULLIF(\', \' || TRIM(
                         COALESCE(NULLIF(\' пд \' || a.address_entrance, \' пд \'), \'\') ||
                         COALESCE(NULLIF(\' эт \' || a.address_floor,    \' эт \'), \'\') ||
                         COALESCE(NULLIF(\' кв \' || a.address_flat,     \' кв \'), \'\')
                       ), \', \'), \'\'), \'\') AS address_full
                FROM pm.address AS a
                   , pm.address_dict_part AS adp    
                WHERE a.address_id = :aid
                  AND adp.address_dict_id = a.address_dict_id
                  AND adp.address_scope_id = 13
            ',
            [
                'aid' => $addressId
            ]
        )->fetch()['address_full'];

        $this->sendResponseAjax([
            'state' => 'yes',
            'address' => $addressId,
            'full' => $addressFull
        ]);
    }

    protected function apiAddressInfo() {
        $nodeId = (int)$this->request->getPost('node_id');
        $typeId = (int)$this->request->getPost('type_id');

        $address = $this->db->query(
            '
                SELECT a.address_dict_id
                     , a.address_entrance
                     , a.address_floor
                     , a.address_flat
                     , ad.dict_data
                FROM pm.address_node_address AS ana
                   , pm.address AS a
                   , pm.address_dict AS ad
                WHERE ana.address_node_id = :nid
                  AND ana.address_type_id = :tid
                  AND a.address_id = ana.address_id
                  AND ad.address_dict_id = a.address_dict_id
            ',
            [
                'nid' => $nodeId,
                'tid' => $typeId
            ]
        )->fetch();

        if (!$address) {
            $this->sendResponseAjax([
                'state' => 'no'
            ]);
        }

        $partsList = $this->db->query(
            '
                SELECT adp.part_value
                     , scp.scope_name
                FROM pm.address_dict_part AS adp
                   , pm.address_scope AS scp
                WHERE adp.address_dict_id = :did
                  AND scp.address_scope_id = adp.address_scope_id
                  AND scp.scope_input = 1
            ',
            [
                'did' => $address['address_dict_id']
            ]
        )->fetchAll();

        $codesList = $this->db->query(
            '
                SELECT ac.code_value
                     , scp.scope_name
                FROM pm.address_dict_code AS adc
                   , pm.address_code AS ac
                   , pm.address_scope AS scp
                WHERE adc.address_dict_id = :did
                  AND ac.address_code_id = adc.address_code_id
                  AND scp.address_scope_id = ac.address_scope_id
                  AND scp.scope_input = 1
            ',
            [
                'did' => $address['address_dict_id']
            ]
        )->fetchAll();

        if ($address['address_entrance']) {
            $partsList[] = ['scope_name' => 'entrance', 'part_value' => $address['address_entrance']];
        }

        if ($address['address_floor']) {
            $partsList[] = ['scope_name' => 'floor', 'part_value' => $address['address_floor']];
        }

        if ($address['address_flat']) {
            $partsList[] = ['scope_name' => 'flat', 'part_value' => $address['address_flat']];
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'parts' => $partsList,
            'codes' => $codesList,
            'house' => json_decode($address['dict_data'], true)
        ]);
    }

    protected function apiDictionaries() {
        $addressTypes = $this->db->query(
            '
                SELECT adt.address_type_id 
                     , adt.type_title
                FROM pm.address_type AS adt 
                ORDER BY adt.type_order
            '
        )->fetchAll();

        $addressScopes = $this->db->query(
            '
                SELECT ps.address_scope_id 
                     , ps.scope_name
                     , ps.scope_title
                     , ps.scope_auto
                     , ps.scope_bound
                FROM pm.address_scope AS ps 
                WHERE ps.scope_input = 1
                ORDER BY ps.scope_order
            '
        )->fetchAll();

        $this->sendResponseAjax([
            'state' => 'yes',
            'address_types' => $addressTypes,
            'address_scopes' => $addressScopes
        ]);
    }
}