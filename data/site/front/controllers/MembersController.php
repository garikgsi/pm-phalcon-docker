<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class MembersController
 * @package Site\Front\Controllers
 */
class MembersController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('members');
    }

    public function indexAction()
    {

    }

    protected function apiMember_search()
    {
        $name = '%'.trim(mb_strtolower($this->request->getPost('name'))).'%';

        $membersList = [];

        if ($name != '%%') {
            $members = $this->db->query(
                '
                    SELECT m.member_id
                         , m.member_nick
                         , m.member_gender
                    FROM public.members AS m
                    WHERE m.member_nick_lower LIKE :name
                    ORDER BY m.member_nick
                    LIMIT 10
                ',
                [
                    'name' => $name
                ]
            );

            while($row = $members->fetch()) {
                $row['member_nick_short'] = \Core\Handler\User::shortNick($row['member_nick']);
                $membersList[] = $row;
            }
        }

        $this->sendResponseAjax([
            'state' => 'yes',
            'members' => $membersList
        ]);
    }

}