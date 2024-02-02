<?php
namespace Core\Builder;

use Core\Engine\Mustache;
use Core\Handler\User;

class MustacheBuilders
{
    public static function guestSocialsPanel(User $user, $patreon) {
        return $user->isLogged() ? '' :'
            <div class="elz tbIt tbGroup left appSocials infernoSocials">
                <div class="elz epBF tbIt tbGroupIn">
    
                    
    
                    <a class="elz tbIt tbTool default large link txt" href="https://www.patreon.com/rpginferno" title="'.$patreon['title'].'">
                        <div class="elz elzIcon rad medium">
                            <i class="elz main smallest elzIc ic-patreon"></i>
                        </div>
                        <div class="elz tbText right">'.$patreon['name'].'</div>
                        <div class="elz elzTTwrap dtb dtc">
                            <div class="elz elzTooltip nowrap">'.$patreon['title'].'</div>
                        </div>
                    </a>
    
                </div>
            </div>
        ';
    }

    public static function userPanelContentCenter(User $user, $authString, $registerString) {
        return $user->isLogged() ? '' : Mustache::renderWithBinds('common/tool_group', [
            'group_class' => 'right',
            'tool_list' => [
                [
                    'tool_tag'    => 'a',
                    'tool_class'  => 'default large link txt bordered brBottom elzPLT elizaAppAuthBtn',
                    'tool_data'   => 'data-br="green" href="#auth"',
                    'tool_text'   => $authString,
                    'tool_tclass' => 'left',
                    'icon' => [
                        'icn_tag'   => 'div',
                        'icn_class' => 'rad medium',
                        'icn_icons' => [
                            'mn_ico_class' => ' smallest elzIc ic-enter'
                        ]
                    ]
                ],
                [
                    'tool_tag'    => 'a',
                    'tool_class'  => 'default large link txt bordered brBottom elzPLT elizaAppAuthBtn',
                    'tool_data'   => 'data-br="blue" href="#auth/register"',
                    'tool_text'   => $registerString,
                    'tool_tclass' => 'left',
                    'icon' => [
                        'icn_tag'   => 'div',
                        'icn_class' => 'rad medium',
                        'icn_icons' => [
                            'mn_ico_class' => ' smallest elzIc ic-user',
                            'mn_sico' => [
                                'class' => ' ic-plus elzPLT shD',
                                'data'  => 'data-bg="blue" data-fn="white"'
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }
}