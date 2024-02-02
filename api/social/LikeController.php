<?php

namespace Api\Social;

use Core\Extender\ApiAccessAjaxBasic;

class LikeController extends ApiAccessAjaxBasic
{
    public function toggle() {
        $memberId = $this->user->getId();
        $entityName = $this->request->getPost('entity_name');
        $entityInstanceId = $this->request->getPost('content_id');

        $likeResult = $this->db->query(
            'SELECT result, like_total FROM social.like_toggle(:name, :content, :member);',
            [
                'name' => $entityName,
                'member' => $memberId,
                'content' => $entityInstanceId
            ]
        )->fetch();


        $this->sendResponseAjax([
            'status' => 'yes',
            'like' => [
                'result' => $likeResult['result'],
                'total' => (int)$likeResult['like_total']
            ]
        ]);
    }

}


