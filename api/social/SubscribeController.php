<?php

namespace Api\Social;

use Core\Extender\ApiAccessAjaxBasic;

class SubscribeController extends ApiAccessAjaxBasic
{
    public function toggle() {
        $memberId = $this->user->getId();
        $entityName = $this->request->getPost('entity_name');
        $entityInstanceId = $this->request->getPost('content_id');

        $likeResult = $this->db->query(
            'SELECT result, subscribe_total FROM social.subscribe_toggle(:name, :content, :member);',
            [
                'name' => $entityName,
                'member' => $memberId,
                'content' => $entityInstanceId
            ]
        )->fetch();


        $this->sendResponseAjax([
            'status' => 'yes',
            'subscribe' => [
                'result' => $likeResult['result'],
                'total' => (int)$likeResult['subscribe_total']
            ]
        ]);
    }

}


