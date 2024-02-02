<?php
namespace Core\Lib;


class BreadCrumbs
{
    private $crumbs = [];

    public function addCrumb($link, $label, $level) {
        $this->crumbs[] = [
            'link'  => $link,
            'label' => $label,
            'level' => $level
        ];


    }

    public function getHtml($version = 1) {
        if ($version == 1) {
            $templ = '<li class="elz item"><a class="elz itemLink elizaHApi" title="{{label}}" href="{{link}}" data-level="3"><span class="elz text">{{label}}</span></a></li>';
        }
        else {
            $templ = '
            <li class="elz p-rel d-flex a-H pL16 cTextBef pBef-abs pBef-L" data-text-bef="»">
                <a class="elz d-block pV8 cur-pointer opAct07 underlineHov fnHov fnHov-primary-t fnHovL-10 fnHovLInvD" title="{{label}}" href="{{link}}">{{label}}</a>
            </li>
            ';
        }

        $html = '';

        $crumbs = $this->crumbs;

        for($i = 0, $len = sizeof($crumbs); $i < $len; $i++) {
            $itemHtml = $templ;

            foreach($crumbs[$i] as $field => $value) {
                $itemHtml = str_replace('{{'.$field.'}}', $value, $itemHtml);
            }

            $html .= $itemHtml;
        }



        return $html;
    }
}