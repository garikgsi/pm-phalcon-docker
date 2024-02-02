<?php
namespace Core\Lib;




class Typograph
{

    public static function simple($text)
    {
        $typograph = new \Emuravjev\Mdash\Typograph();

        $typograph->setup([
            'Text.paragraphs'=>'off',
            'OptAlign.oa_oquote'=>'off'
        ]);

        $typograph->set_text($text);

        return $typograph->apply();
    }

    public static function cleanString_and_rus2Lat($string)
    {
        $string = preg_replace('/\s+/ui', '-', $string);
        $string = preg_replace('/-+/ui',  '-', $string);
        $string = preg_replace('/_+/ui',  '_', $string);

        $string = preg_replace('/[^0-9a-zA-ZА-Я_\-]/ui', '', $string);

        $string = mb_strtoupper($string);

        $translit = [
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'Yo',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'J',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Ъ' => '',
            'Ы' => 'Y',
            'Ь' => '',
            'Э' => 'Eh',
            'Ю' => 'Yu',
            'Я' => 'Ya'
        ];

        foreach($translit as $i => $v) {
            $string = str_replace($i, $v, $string);
        }


        return mb_strtolower($string);
    }
}