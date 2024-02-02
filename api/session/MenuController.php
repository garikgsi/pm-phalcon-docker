<?php
namespace Api\Session;

use Core\Extender\ApiAccessAjax;
use Core\Lib\AjaxFormResponse;
use Phalcon\Translate\Adapter\NativeArray;

class MenuController extends ApiAccessAjax
{
    public $langTable = [
        1 => 'ru',
        2 => 'en'
    ];

    public function getRoot() {
        $menuList = [];

        $t = new NativeArray([
            'content' => require_once($this->site->getDir().'langs/0modules/umenu_'.$this->langTable[$this->user->getLangId()].'.php')
        ]);

        if (!$this->user->isLogged()) {
            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#auth"',
                'title' => $t->_('menu_auth_title'),
                'description' => $t->_('menu_auth_descr'),
                'icon' => [
                    'name'  => 'ic-enter',
                    'color' => 'grey 400'
                ]
            ];

            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#auth/register"',
                'title' => $t->_('menu_register_title'),
                'description' => $t->_('menu_register_descr'),
                'icon' => [
                    'name'  => 'ic-user',
                    'color' => 'grey 400'
                ],
                'sicon' => [
                    'name'  => 'ic-plus',
                    'color' => 'blue'
                ]
            ];

            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#auth/remind"',
                'title' => $t->_('menu_remind_title'),
                'description' => $t->_('menu_remind_descr'),
                'icon' => [
                    'name'  => 'ic-key',
                    'color' => 'grey 400'
                ]
            ];
        }
        else  {
            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#settings"',
                'title' => $t->_('menu_settings_title'),
                'description' => $t->_('menu_settings_descr'),
                'icon' => [
                    'name'  => 'ic-cog',
                    'color' => 'grey 400'
                ]
            ];


            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#tpedit"',
                'title' => $t->_('menu_tpedit_title'),
                'description' => $t->_('menu_tpedit_descr'),
                'icon' => [
                    'name'  => 'ic-font',
                    'color' => 'grey 400'
                ]
            ];

            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#isedit"',
                'title' => $t->_('menu_isedit_title'),
                'description' => $t->_('menu_isedit_descr'),
                'icon' => [
                    'name'  => 'ic-mapcity',
                    'color' => 'grey 400'
                ]
            ];

            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#rdedit"',
                'title' => $t->_('menu_rdedit_title'),
                'description' => $t->_('menu_rdedit_descr'),
                'icon' => [
                    'name'  => 'ic-road',
                    'color' => 'grey 400'
                ]
            ];


            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#txedit"',
                'title' => $t->_('menu_txedit_title'),
                'description' => $t->_('menu_txedit_descr'),
                'icon' => [
                    'name'  => 'ic-maptextures',
                    'color' => 'grey 400'
                ]
            ];


            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#bledit"',
                'title' => $t->_('menu_bledit_title'),
                'description' => $t->_('menu_bledit_descr'),
                'icon' => [
                    'name'  => 'ic-blend',
                    'color' => 'grey 400'
                ]
            ];

            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#icedit"',
                'title' => $t->_('menu_icedit_title'),
                'description' => $t->_('menu_icedit_descr'),
                'icon' => [
                    'name'  => 'ic-insertpicture',
                    'color' => 'grey 400'
                ]
            ];





            $menuList[] = [
                'tag'   => 'a',
                'data'  => 'href="#auth/logout"',
                'title' => $t->_('menu_logout_title'),
                'description' => $t->_('menu_logout_descr'),
                'icon' => [
                    'name'  => 'ic-exit'
                ]
            ];
        }

        $this->sendResponseAjax($menuList);
    }

}