<?php
namespace Core\Builder;

use Core\Engine\Mustache;

class MustacheForms
{
    const PLT = 'elzPLT';

    private static $idCounter = 0;

    private static $defaultBGs = [
        'error' =>   ['color' => 'red',      'tone' => 'light'],
        'success' => ['color' => 'green',    'tone' => 'light'],
        'send' =>    ['color' => 'blue',     'tone' => 'light'],
        'default' => ['color' => 'grey 200', 'tone' => 'dark'],
        'gactive' => ['color' => 'blue-grey 600', 'tone' => 'light'],

        //------------------------------------------------

        'red' =>   ['color' => 'red',      'tone' => 'light'],
        'green' => ['color' => 'green',    'tone' => 'light'],
        'blue' =>  ['color' => 'blue',     'tone' => 'light'],
        'grey' =>  ['color' => 'grey 200', 'tone' => 'dark'],
        'gray' =>  ['color' => 'grey 200', 'tone' => 'dark']
    ];

    public static function getNextId() {
        $id = self::$idCounter;

        self::$idCounter++;

        return 'mstchIdPhp'.$id;
    }

    private static $fileInputText = [
        'file' =>    ['button' => 'Загрузить файл',        'text' => 'Выберите файл'  ],
        'archive' => ['button' => 'Загрузить архив',       'text' => 'Выберите архив' ],
        'icon' =>    ['button' => 'Загрузить иконку',      'text' => 'Выберите иконку'],
        'image' =>   ['button' => 'Загрузить изображение', 'text' => 'Выберите изображение']
    ];

    /*private static $mapping = [
        'type',
        'style',
        'size',
        'radius'
    ];*/
    

    private static function createHTMLAttribute($name, $value) {
        return $value ? ' '.$name.'="'.$value.'"' : '';
    }

    private static function createHTMLAttributeByArray($attrArray) {
        $resultArray = [];
        
        foreach ($attrArray as $name => $value) {
            $resultArray[] = self::createHTMLAttribute($name, $value); 
        }
        
        return $resultArray;
    }

    /**
     * Функция для генерации классов и дата аттрибутов, требуемых, для устанвки цвета фона и/или шрифта
     *
     * @param string $background - цветовой идентификатор для фона
     * @param string $font       - цветовой идентификатор для шрифта
     * @return array
     */
    private static function createColors($background = '', $font = '') {
        $plt  = '';
        $tone = '';
        $colorsData = [];

        if ($background) {
            $plt = self::PLT;

            $bgColors = self::$defaultBGs[$background];

            $tone = ' '.$bgColors['tone'];
            $colorsData['data-bg'] = $bgColors['color'];
        }

        if ($font) {
            $plt = self::PLT;

            $fnColors = self::$defaultBGs[$font];

            $colorsData['data-fn'] = $fnColors['color'];
        }


        return [
            'class' => $plt.$tone,
            'data'  => $colorsData
        ];

    }


    public static function textArea($placeholder = '', $title = '', $icon = '', $id = '', $name = '', $value = '', $row = 'row1', $size = 'medium') {
        $inputData  = [];
        $inputAttr  = [
            'id'    => $id,
            'name'  => $name,
            'placeholder' => $placeholder
        ];

        $inputClass = [];

        $inputClass[] = 'textarea';
        $inputClass[] = 'text';    // Тип: text|button|check|select|upload
        $inputClass[] = 'default'; // Стайл:  [empty]|default|lite
        $inputClass[] = $size;     // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle
        $inputClass[] = $row;      //

        return Mustache::renderWithBinds('forms/base_input', [
            'wclass'  => 'icRight'.($icon ? ' icLeft' : ''),
            'wcancel' => 0,
            'input'   => self::arrayCommonFInput($id, $inputClass, $inputAttr, $inputData, 0, $value, 'textarea', ''),
            'title'   => $title,
            'icon_left'  => self::arrayCommonFIcon('left', $icon),
            'icon_right' => self::arrayCommonFIcon('right')
        ]);
    }

    public static function input($type = 'text', $placeholder = '', $title = '', $icon = '', $id = '', $name = '', $value = '', $size = 'medium', $classes = '', $readOnly = '') {
        $inputData  = [];
        $inputAttr  = [
            'type'  => $type,
            'id'    => $id,
            'name'  => $name,
            'value' => $value,
            'placeholder' => $placeholder,
            'readonly' => $readOnly
        ];

        $inputClass = [];

        if ($classes) {
            $inputClass[] = $classes;
        }

        $bgColor = 'grey';

        if ($type == 'text') {
            $bgColor = '';
        }

        $inputClass[] = 'input';
        $inputClass[] = 'text';    // Тип: text|button|check|select|upload
        $inputClass[] = 'default'; // Стайл:  [empty]|default|lite
        $inputClass[] = $size;     // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle

        return Mustache::renderWithBinds('forms/base_input', [
            'wclass'  => 'icRight'.($icon ? ' icLeft' : ''),
            'wcancel' => 0,
            'input'   => self::arrayCommonFInput($id, $inputClass, $inputAttr, $inputData, 1, '', 'input', $bgColor),
            'title'   => $title,
            'icon_left'  => self::arrayCommonFIcon('left', $icon),
            'icon_right' => self::arrayCommonFIcon('right')
        ]);
    }


    public static function submit($id = '', $text = '', $icon = '', $variant = 'send', $name = '') {
        $inputData  = [];
        $inputClass = [];

        $inputAttr = [
            'type'  => 'submit',
            'id'    => $id,
            'value' => $text,
            'name'  => $name
        ];

        $inputClass[] = 'input';
        $inputClass[] = 'button';  // Тип: text|button|check|select|upload
        $inputClass[] = 'default'; // Стайл:  [empty]|default|lite
        $inputClass[] = 'medium';  // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle

        return Mustache::renderWithBinds('forms/base_input', [
            'wclass'  => $icon ? 'icLeft' : '',
            'wcancel' => 0,
            'input'   => self::arrayCommonFInput($id, $inputClass, $inputAttr, $inputData, 1, '', 'input', $variant),
            'title'   => '',
            'icon_left' => self::arrayCommonFIcon('left', $icon)
        ]);
    }

    public static function button($id = '', $text = '', $icon = '', $variant = 'send', $disabled = 0, $iconOnly = 0, $wclasses = '', $classes = '') {
        $iconOnly = (int)$iconOnly;
        $disabled = (int)$disabled;

        $inputData  = [];
        $inputClass = [];

        $inputAttr = [
            'id'    => $id
        ];

        if ($iconOnly) {
            $inputAttr['title'] = $text;

            $text = '';
        }

        $inputClass[] = 'input';
        $inputClass[] = 'button';  // Тип: text|button|check|select|upload
        $inputClass[] = 'default'; // Стайл:  [empty]|default|lite
        $inputClass[] = 'medium';  // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle

        if ($disabled) {
            $inputClass[] = 'disabled';
        }

        if ($classes) {
            $inputClass[] = $classes;
        }

        return Mustache::renderWithBinds('forms/base_input', [
            'wclass'  => ($icon ? 'icLeft'.($iconOnly ? ' icOnly' : '') : '').$wclasses,
            'wcancel' => 0,
            'input'   => self::arrayCommonFInput($id, $inputClass, $inputAttr, $inputData, 0, $text, 'div', $variant),
            'title'   => '',
            'icon_left' => self::arrayCommonFIcon('left', $icon)
        ]);
    }

    public static function buttonLink($text, $href, $variant = 'send', $title = '', $icon = '', $id = '', $classes = '', $data = [], $tag = 'a') {
        $inputData  = array_merge([], $data);
        $inputClass = [];

        $inputAttr = [
            'id'    => $id,
            'href'  => $href,
            'title' => $title
        ];

        $inputClass[] = 'input';
        $inputClass[] = 'button';  // Тип: text|button|check|select|upload
        $inputClass[] = 'default'; // Стайл:  [empty]|default|lite
        $inputClass[] = 'medium';  // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle
        $inputClass[] = $classes;

        return Mustache::renderWithBinds('forms/base_input', [
            'wclass'  => $icon ? 'icLeft' : '',
            'wcancel' => 0,
            'input'   => self::arrayCommonFInput($id, $inputClass, $inputAttr, $inputData, 0, $text, $tag, $variant),
            'title'   => '',
            'icon_left' => self::arrayCommonFIcon('left', $icon)
        ]);
    }


    public static function checkbox($id, $text = '', $classes = '', $name = '', $checked = '', $value = '', $txClass = 'right') {
        $id = $id ? $id : self::getNextId();

        $inputAttr = [
            'type'    => 'checkbox',
            'name'    => $name,
            'checked' => $checked ? 'checked' : '',
            'value'   => $value
        ];

        $itemClasses = [];

        $itemClasses[] = 'switch';
        $itemClasses[] = 'lite';
        $itemClasses[] = 'circle';
        $itemClasses[] = '';

        $itemAttr = [];

        $colors = self::createColors('send');
        $itemClasses[] = $colors['class'];

        $itemAttr = array_merge($itemAttr, $colors['data']);


        return Mustache::renderWithBinds('forms/checkbox_html', [
            'id'   => $id,
            'text' => $text,
            'tx_class' => ' '.$txClass,
            'input_attr'  => implode(' ', self::createHTMLAttributeByArray($inputAttr)),
            'input_class' => $classes ? ' '.$classes : '',
            'item_class'  => implode(' ', $itemClasses),
            'item_attr'   => implode(' ', self::createHTMLAttributeByArray($itemAttr)),
            'isbutton' => 0,
            'isradio'  => 0,
            'isbox'    => 0,
            'icon'     => 0,
        ]);
    }

    public static function radio() {

    }

    public static function select($value = '', $optionList = [], $id = '', $name = '', $icon = '', $classes = '', $size = 'medium', $classStyle = 'default', $selectTitle = '')
    {
        $html = '';
        $title = '';

        for($i = 0, $len = sizeof($optionList); $i < $len; $i++) {
            $option = $optionList[$i];


            if (isset($option['label']) && isset($option['list'])) {
                $html .= '<optgroup label="'.$option['label'].'">';

                for($a = 0, $lenA = sizeof($option['list']); $a < $lenA; $a++) {
                    $opt = $option['list'][$a];

                    $selected = '';

                    if ($value == $opt[0]) {
                        $selected = 'selected="selected"';
                        $title    = $opt[1];
                    }

                    $disabled = isset($opt[2]) && $opt[2] ? ' disabled="disabled"' : '';

                    $html .= '<option value="'.$opt[0].'" '.$selected.$disabled.'>'.$opt[1].'</option>';
                }

                $html .= '</optgroup>';

                continue;
            }


            $selected = '';
            if ($value == $option[0]) {
                $selected = 'selected="selected"';
                $title    = $option[1];
            }

            $disabled = isset($option[2]) && $option[2] ? ' disabled="disabled"' : '';

            $html .= '<option value="'.$option[0].'" '.$selected.$disabled.'>'.$option[1].'</option>';

        }


        $inputData  = [];
        $inputClass = [];


        $id = $id ? $id : self::getNextId();

        $inputAttr = self::createHTMLAttributeByArray([
            'id'    => $id,
            'name'  => $name
        ]);


        $inputClass[] = 'input';
        $inputClass[] = 'text';    // Тип: text|button|check|select|upload
        $inputClass[] = $classStyle; // Стайл:  [empty]|default|lite
        $inputClass[] = $size;  // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle




        $inputArray = [
            'id'  => $id,
            'tag' => 'div',
            'input_class' => implode(' ',$inputClass),
            'input_data'  => implode('', $inputData),
            'text'        => $title,
            'short'       => 0
        ];

        return Mustache::renderWithBinds('forms/file_select', [
            'input_base' => [
                'wclass'  => $icon ? 'icLeft' : '',
                'wcancel' => 0,
                'input'   => $inputArray,
                'title'   => $selectTitle,
                'icon_left'  => [
                    'tag' => 'span',
                    'icon_class' => 'left'.($icon ? '' : ' elzHide'),
                    'icon' => [
                        'icn_tag' => 'i',
                        'icn_class' => 'rad medium',
                        'icn_data'  => ''/*self::createHTMLAttribute('data-bg', 'black')*/,
                        'icn_icons' => [
                            'mn_ico_class' => 'smallest '.$icon,
                            'mn_ico_data'  => ''/*self::createHTMLAttribute('data-fn', 'black')*//*,
                            'mn_sico' => [
                                [
                                    'class' => 'ic-mail elzPLT',
                                    'data'  => self::createHTMLAttribute('data-bg', 'grey 200').' '.self::createHTMLAttribute('data-fn', 'blue')
                                ]
                            ]*/
                        ]

                    ]
                ]
            ],
            'select' => [
                'sl_attr'  => implode('', $inputAttr),
                'sl_class' => $classes ? ' '.$classes : '',
                'html'     => $html
            ]
        ]);
    }

    public static function file($value, $name = '', $id = '', $type = 'file', $icon = '', $size = 'medium')
    {
        $texts = self::$fileInputText[$type];

        $inputData  = [];
        $inputClass = [];

        $id = $id ? $id : self::getNextId();

        $inputClass[] = 'input';
        $inputClass[] = 'text';    // Тип: text|button|check|select|upload
        $inputClass[] = 'default'; // Стайл:  [empty]|default|lite
        $inputClass[] = $size;  // Сайз:   [empty]|small|medium
        $inputClass[] = 'rad';     // Радиус: [empty]|rad|circle




        $inputArray = [
            'tag' => 'div',
            'input_class' => implode(' ',$inputClass),
            'input_data'  => implode('', $inputData),
            'text'        => $value != '' ? $value : $texts['button'],
            'short'       => 0
        ];

        return Mustache::renderWithBinds('forms/file_select', [
            'input_base' => [
                'wclass'  => $icon ? 'icLeft' : '',
                'wcancel' => 0,
                'input'   => $inputArray,
                'title'   => '',
                'icon_left'  => [
                    'tag' => 'span',
                    'icon_class' => 'left'.($icon ? '' : ' elzHide'),
                    'icon' => [
                        'icn_tag' => 'i',
                        'icn_class' => 'rad medium',
                        'icn_data'  => ''/*self::createHTMLAttribute('data-bg', 'black')*/,
                        'icn_icons' => [
                            'mn_ico_class' => 'smallest '.$icon,
                            'mn_ico_data'  => ''/*self::createHTMLAttribute('data-fn', 'black')*//*,
                            'mn_sico' => [
                                [
                                    'class' => 'ic-mail elzPLT',
                                    'data'  => self::createHTMLAttribute('data-bg', 'grey 200').' '.self::createHTMLAttribute('data-fn', 'blue')
                                ]
                            ]*/
                        ]

                    ]
                ]
            ],
            'input_right' => self::arrayCommonFInput('', [
                'input',
                'button',
                'default',
                $size,
                'rad'
            ], [], [], 0,$texts['text'], $tag = 'div', 'grey', ''),
            'file' => [
                'id'   => $id,
                'name' => $name,
                'type' => $type
            ]
        ]);
    }

    public static function buildFromArray($data) {
        $html = '';

        switch ($data['field']) {
            case 'textarea':
                $html = self::textArea(@$data['placeholder'], @$data['title'], @$data['icon'], @$data['id'], @$data['name'], @$data['value'], $data['rows'] ?? 'row1', $size = 'medium');
                break;
            case 'select':
                $html = self::select(@$data['value'], @$data['optionList'], @$data['id'], @$data['name'], @$data['icon'], @$data['classes']);
                break;
            case 'input':
                $html = self::input(@$data['type'], @$data['placeholder'], @$data['title'], @$data['icon'], @$data['id'], @$data['name'], @$data['value'], 'medium', @$data['classes'], @$data['readonly']);
                break;
            case 'checkbox':
                $html = self::checkbox(@$data['id'], @$data['text'], @$data['classes'], @$data['name'], @$data['checked'], @$data['value'], isset($data['txClass']) ? $data['txClass'] : 'right');
                break;
        }

        return $html;
    }

    private static function arrayCommonFInput($id = '', $classes = [], $attributes = [], $data = [], $short = 1, $text = '', $tag = 'input', $bg = 'grey', $fn = '') {
        $id = $id ? $id : self::getNextId();

        // Если не указан цвет бекграунда, то мы не ставим никакие классы и аттрибуты для текста
        // этот режим нужен для текстовых инпутов
        if ($bg != '') {
            $colors = self::createColors($bg, $fn);
            $classes[] = $colors['class'];

            $data = array_merge($data, $colors['data']);
        }

        return [
            'id'  => $id,
            'tag' => $tag,
            'input_class' => implode(' ',$classes),
            'input_attr'  => implode('', self::createHTMLAttributeByArray($attributes)),
            'input_data'  => implode('', self::createHTMLAttributeByArray($data)),
            'text'        => $text,
            'short'       => $short
        ];
    }

    private static function arrayCommonFIcon($align = 'left', $icon = '') {
        return [
            'tag' => 'span',
            'icon_class' => $align.' rad'.($icon ? '' : ' elzHide'),
            'icon_attr'  => ''/*self::createHTMLAttribute('data-bg', 'green')*/,
            'icon' => [
                'icn_tag' => 'i',
                'icn_class' => 'rad medium',
                'icn_data'  => ''/*self::createHTMLAttribute('data-bg', 'green')*/,
                'icn_icons' => [
                    'mn_ico_class' => 'smallest '.$icon, // elzPLT ic-check
                    'mn_ico_data'  => '',/*self::createHTMLAttribute('data-fn', 'green')/*,
                        'mn_sico' => [
                            [
                                'class' => 'ic-mail elzPLT',
                                'data'  => self::createHTMLAttribute('data-bg', 'grey 200').' '.self::createHTMLAttribute('data-fn', 'blue')
                            ]
                        ]*/
                ]

            ]
        ];
    }
}