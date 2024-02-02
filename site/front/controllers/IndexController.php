<?php
namespace Site\Front\Controllers;


use Core\Extender\ControllerApp;

/**
 * Class IndexController
 * @package Site\Front\Controllers
 */
class IndexController extends ControllerApp
{
    public function initialize()
    {
        parent::initialize();

        $this->hapi->setHapiController('index');

        if (!$this->user->hasBasicRights()) {
            $this->view->setMainView('render_levels/main/auth');
            $this->hapi->setHapiAction('index_guest');

            return;
        }

        $this->view->setMainView('render_levels/main/index');
    }

    public function indexAction()
    {

    }

    public function logoutAction() {
        $this->user->logout();
        $this->response->redirect('/', true);
    }

    public function faviconAction() {
        $this->view->disable();

        $file = DIR_PUBLIC.'/uploads/icons/'.$this->site->getId().'_ico_result.ico';
        header('Content-Type: image/vnd.microsoft.icon');
        header('Content-Disposition: filename="/favicon.ico"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: no-cache');
        header('Content-Length: ' . filesize($file));
        readfile($file);

        exit;
    }

    public function robotstxtAction() {
        $this->view->disable();

        $this->response->setHeader('Content-Type', 'text/plain');

        $text = "User-agent: *\n".
        "sitemap: https://".$_SERVER['HTTP_HOST']."/sitemap.xml";

        $this->response->setContent($text);

        return $this->response;
    }

    public function prepareIconsAction() {
        if (!$this->user->isDeveloper()) {
            exit;
        }

        $testIconId = 326;

        $icons = $this->db->query(
            '
                SELECT * FROM rpginferno.rpge_icons WHERE icon_palette_max = 0
            '
        );

        echo '<pre>';

        $prepareColor = function($color) {
            $color = mb_strtolower($color);
            $color = str_replace('rgba(', '', $color);
            $color = str_replace('rgb(',  '', $color);
            $color = str_replace(')',     '', $color);

            $colorExplode = explode(',', $color);

            $colorRed   = floor($colorExplode[0]);
            $colorGreen = floor($colorExplode[1]);
            $colorBlue  = floor($colorExplode[2]);
            $colorAlpha =  trim($colorExplode[3] ?? '1.00');

            if ($colorAlpha > 1) {
                $colorAlpha = '1.00';
            }
            else if ($colorAlpha < 0) {
                $colorAlpha = '0.00';
            }
            else if (mb_strlen($colorAlpha) == 1) {
                $colorAlpha .= '.00';
            }
            else {
                $colorAlpha = str_pad($colorAlpha, 4, '0', STR_PAD_RIGHT);
            }

            return 'rgba('.implode(',', [$colorRed, $colorGreen, $colorBlue, $colorAlpha]).')';
        };

        while($icon = $icons->fetch()) {
            $iconId = (int)$icon['icon_id'];

            $paletteMax = (int)$icon['icon_palette_max'];
            $defaultPresetId = (int)$icon['default_preset_id'];

            $paletteMax = 1;
            $paletteMap = [
                1 => 'clear'
            ];
            $paletteList = [1];

            $paletteMapFields = [];

            $colorsMap = ['clear' => 1];

            $updatePackage = [];

            $updatePackage[$defaultPresetId] = [
                'icon_id'   => $iconId,
                'preset_id' => $defaultPresetId,
                'palette'   => [],
                'primitives' => [],
                'parts' => []
            ];

            $presetPrimitives = $this->db->query(
                'SELECT * FROM rpginferno.rpge_icons_presets_primitives WHERE preset_id = :pid',
                ['pid' => $defaultPresetId]
            );

            while($presetPrimitive = $presetPrimitives->fetch()) {
                $fillColor = $prepareColor($presetPrimitive['primitive_fill']);
                $strokeColor = $prepareColor($presetPrimitive['primitive_stroke_color']);

                if (isset($colorsMap[$fillColor])) {
                    $fillColor = $colorsMap[$fillColor];
                }
                else {
                    $paletteMax += 1;
                    $colorsMap[$fillColor] = $paletteMax;

                    $paletteList[] = $paletteMax;

                    $paletteMap[$paletteMax] = $fillColor;

                    $paletteMapFields[$presetPrimitive['primitive_in_icon_id'].'_fill'] = $paletteMax;

                    $fillColor = $paletteMax;
                }

                if (isset($colorsMap[$strokeColor])) {
                    $strokeColor = $colorsMap[$strokeColor];
                }
                else {
                    $paletteMax += 1;
                    $colorsMap[$strokeColor] = $paletteMax;

                    $paletteList[] = $paletteMax;

                    $paletteMap[$paletteMax] = $strokeColor;

                    $paletteMapFields[$presetPrimitive['primitive_in_icon_id'].'_stroke'] = $paletteMax;

                    $strokeColor = $paletteMax;
                }

                $updatePackage[$defaultPresetId]['primitives'][$presetPrimitive['primitive_in_icon_id']] = [
                    'fill' => $fillColor,
                    'stroke' => $strokeColor
                ];
            }

            $presetPrimitivesParts = $this->db->query(
                'SELECT * FROM rpginferno.rpge_icons_presets_primitives_parts WHERE preset_id = :pid',
                ['pid' => $defaultPresetId]
            );

            while($presetPrimitivePart = $presetPrimitivesParts->fetch()) {
                $fillColor = $prepareColor($presetPrimitivePart['part_fill']);

                if (isset($colorsMap[$fillColor])) {
                    $fillColor = $colorsMap[$fillColor];
                }
                else {
                    $paletteMax += 1;
                    $colorsMap[$fillColor] = $paletteMax;

                    $paletteList[] = $paletteMax;

                    $paletteMap[$paletteMax] = $fillColor;

                    $paletteMapFields[$presetPrimitivePart['primitive_in_icon_id'].'_'.$presetPrimitivePart['primitive_in_icon_part_id'].'_fill'] = $paletteMax;
                    $fillColor = $paletteMax;
                }

                $updatePackage[$defaultPresetId]['parts'][$presetPrimitivePart['primitive_in_icon_part_id']] = [
                    'fill' => $fillColor,
                    'slave' => (int)$presetPrimitivePart['part_slave'],
                    'hide'  => (int)$presetPrimitivePart['part_hide']
                ];
            }

            $updatePackage[$defaultPresetId]['palette'] = $paletteMap;

            $this->db->query(
                '
                    UPDATE rpginferno.rpge_icons
                    SET
                        icon_palette_max  = :max::integer,
                        icon_palette_list = :list::jsonb
                    WHERE
                        icon_id = :iid::integer    
                ',
                [
                    'max'  => $paletteMax,
                    'list' => json_encode($paletteList),
                    'iid'  => $iconId
                ]
            );

            foreach($updatePackage[$defaultPresetId]['primitives'] as $primitiveInIconId => $colors) {
                $this->db->query(
                    '
                        UPDATE rpginferno.rpge_icons_primitives_in_icons
                        SET
                            primitive_fill = :fill,
                            primitive_stroke = jsonb_set(primitive_stroke, \'{color}\', \''.$colors['stroke'].'\')
                        WHERE
                            primitive_in_icon_id = :piiid    
                    ',
                    [
                        'piiid'  => $primitiveInIconId,
                        'fill'   => $colors['fill']
                    ]
                );
            }

            foreach($updatePackage[$defaultPresetId]['parts'] as $primitiveInIconPartId => $colors) {
                $this->db->query(
                    '
                        UPDATE rpginferno.rpge_icons_primitives_in_icons_parts
                        SET
                            part_fill = :fill,
                            part_hide = :hide,
                            part_slave = :slave
                        WHERE
                            primitive_in_icon_part_id = :piiid    
                    ',
                    [
                        'piiid'  => $primitiveInIconPartId,
                        'fill'   => $colors['fill'],
                        'hide'   => $colors['hide'],
                        'slave'  => $colors['slave']
                    ]
                );
            }

            unset($updatePackage[$defaultPresetId]['primitives']);
            unset($updatePackage[$defaultPresetId]['parts']);


            echo '-------------------------------- ОБРАБАТЫВАЕМ ОСТАЛЬНЫЕ ПРЕСЕТЫ -------------------------------------';

            $restPresets = $this->db->query(
                '
                    SELECT * FROM rpginferno.rpge_icons_presets WHERE icon_id = :iid AND preset_id <> :pid
                ',
                ['iid' => $iconId, 'pid' => $defaultPresetId]
            );

            while($preset = $restPresets->fetch()) {
                $presetPaletteMap = $paletteMap;//json_decode(json_encode($paletteMap), true);

                $presetPrimitives = $this->db->query(
                    'SELECT * FROM rpginferno.rpge_icons_presets_primitives WHERE preset_id = :pid',
                    ['pid' => $preset['preset_id']]
                );

                while($presetPrimitive = $presetPrimitives->fetch()) {
                    $keyFill = $presetPrimitive['primitive_in_icon_id'].'_fill';
                    $keyStroke = $presetPrimitive['primitive_in_icon_id'].'_stroke';
                    $fillColor = $prepareColor($presetPrimitive['primitive_fill']);
                    $strokeColor = $prepareColor($presetPrimitive['primitive_stroke_color']);

                    if (isset($paletteMapFields[$keyFill])) {
                        $presetPaletteMap[$paletteMapFields[$keyFill]] = $fillColor;
                    }

                    if (isset($paletteMapFields[$keyStroke])) {
                        $presetPaletteMap[$paletteMapFields[$keyStroke]] = $strokeColor;
                    }
                }

                $presetPrimitivesParts = $this->db->query(
                    'SELECT * FROM rpginferno.rpge_icons_presets_primitives_parts WHERE preset_id = :pid',
                    ['pid' => $preset['preset_id']]
                );

                while($presetPrimitivePart = $presetPrimitivesParts->fetch()) {
                    $keyFill = $presetPrimitivePart['primitive_in_icon_id'].'_'.$presetPrimitivePart['primitive_in_icon_part_id'].'_fill';
                    $fillColor = $prepareColor($presetPrimitivePart['part_fill']);

                    if (isset($paletteMapFields[$keyFill])) {
                        $presetPaletteMap[$paletteMapFields[$keyFill]] = $fillColor;
                    }
                }

                $updatePackage[$preset['preset_id']] = [
                    'icon_id'   => $iconId,
                    'preset_id' => $preset['preset_id'],
                    'palette'   => $presetPaletteMap,
                ];
            }


            print_r($updatePackage);


            foreach($updatePackage as $presetId => $presetData) {
                $this->db->query(
                    '
                        UPDATE rpginferno.rpge_icons_presets
                        SET
                            preset_palette = :palette::jsonb
                        WHERE
                            preset_id = :pid    
                    ',
                    [
                        'pid'   => $presetId,
                        'palette' => json_encode($presetData['palette'])
                    ]
                );
            }


            print_r($paletteMapFields);

        }

        exit;
    }

    public function route404Action()
    {
        $this->hapi->setHapiController('index');
        $this->hapi->setHapiAction('index_404');
        $this->view->setLayout('index');
        $this->view->pick('index/error404');
        $this->view->setVar('notStandAlone', 1);

        $this->response->setStatusCode(404, 'Not Found');
    }
}