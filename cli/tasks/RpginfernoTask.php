<?php

use Phalcon\Cli\Task;

class RpginfernoTask extends Task
{
    public function mainAction()
    {
        echo 'Это задача по умолчанию и действие по умолчанию' . PHP_EOL;
    }

    /**
     * @param array $params
     * @cmd php cli.php rpginferno calculate_assets_use
     */
    public function calculateAssetsUseAction(array $params = [])
    {
        $hexLayers = $this->db->query(
            '
                SELECT mml.layer_id
                FROM rpginferno.mh_maps_layers AS mml
                WHERE mml.layer_date_updated >= mml.layer_date_calculated
                ORDER BY mml.layer_id ASC
            '
        );

        while($row = $hexLayers->fetch()) {
            $this->assetsIconInUseHex($row['layer_id']);
            $this->assetsTexturesBlends($row['layer_id'], 'hex');
            $this->assetsTexts($row['layer_id'], 'hex');
            $this->assetsRoadsHex($row['layer_id']);

            $this->db->query(
                '
                    UPDATE rpginferno.mh_maps_layers AS mml 
                    SET layer_fill_percent = rpginferno.dp_mh_maps_fill_calc(mml.layer_id) 
                      , layer_date_calculated = NOW()
                    WHERE layer_id = :lid',
                ['lid' => $row['layer_id']]
            );
        }


        $objectsQry = $this->db->query(
            'SELECT object_id, object_name FROM rpginferno.ml_objects'
        );

        $objectsRegistry = [];

        while ($row = $objectsQry->fetch()) {
            $objectsRegistry[$row['object_name']] = (int)$row['object_id'];
        }

        $squareLayers = $this->db->query(
            '
                SELECT mml.map_id, mml.layer_id
                FROM rpginferno.ml_maps_layers AS mml
                WHERE mml.layer_date_updated >= mml.layer_date_calculated
                ORDER BY mml.layer_id ASC
            '
        );

        while($row = $squareLayers->fetch()) {
            $this->assetsIconInUseSquare($row['layer_id']);
            $this->assetsObjectInUseSquare($row['map_id'], $row['layer_id'], $objectsRegistry);
            $this->assetsTexturesBlends($row['layer_id'], 'square');
            $this->assetsTexts($row['layer_id'], 'square');

            $this->db->query(
                '
                    UPDATE rpginferno.ml_maps_layers AS mml 
                    SET layer_fill_percent = rpginferno.dp_ml_maps_fill_calc(mml.layer_id) 
                      , layer_date_calculated = NOW()
                    WHERE layer_id = :lid',
                ['lid' => $row['layer_id']]
            );
        }
    }

    /**
     * @param array $params
     * @cmd php cli.php rpginferno hub_materialize_views
     */
    public function hubMaterializeViewsAction(array $params = []) {
        $this->db->query('REFRESH MATERIALIZED VIEW rpginferno.hub_index_stat');
        $this->db->query('REFRESH MATERIALIZED VIEW rpginferno.hub_index_content');
        $this->db->query('REFRESH MATERIALIZED VIEW rpginferno.hub_index_events');
        $this->db->query('REFRESH MATERIALIZED VIEW rpginferno.hub_icon_primitive_count');
    }

    private function assetsIconInUseHex($layerId) {
        $this->db->query(
            'DELETE FROM rpginferno.asset_use_icon_in_hex WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $this->db->query(
            'DELETE FROM rpginferno.asset_use_icon_preset_in_hex WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $icons = $this->db->query(
            '
                SELECT icon_id, icon_presets 
                FROM rpginferno.mh_maps_layers_icons 
                WHERE layer_id = :lid
            ',
            ['lid' => $layerId]
        );

        $this->db->begin();
        while ($icon = $icons->fetch()) {
            $iconId = (int)$icon['icon_id'];
            $iconsCount = 0;

            $presets = json_decode($icon['icon_presets'], true);
            $presetsSql = [];

            foreach ($presets as $presetId => $cells) {
                $cellsCount = sizeof($cells);
                $iconsCount += $cellsCount;
                $presetsSql[] = '('.$iconId.', '.$presetId.', '.$layerId.', '.$cellsCount.')';
            }

            $this->db->query('
                    INSERT INTO rpginferno.asset_use_icon_preset_in_hex
                    (icon_id, preset_id, layer_id, preset_count)
                    VALUES '.implode(',', $presetsSql)
            );

            $this->db->query(
                '
                    INSERT INTO rpginferno.asset_use_icon_in_hex
                    (icon_id, layer_id, icon_count)
                    VALUES (:iid, :lid, :ic)
                ',
                [
                    'iid' => $iconId,
                    'lid' => $layerId,
                    'ic' => $iconsCount
                ]
            );

            echo 'layer: '.$layerId. '; icon: '.$iconId.'; count: '.$iconsCount."\n";
        }
        $this->db->commit();
    }

    private function assetsIconInUseSquare($layerId) {
        $this->db->query(
            'DELETE FROM rpginferno.asset_use_icon_in_square WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $this->db->query(
            'DELETE FROM rpginferno.asset_use_icon_preset_in_square WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $icons = $this->db->query(
            '
                SELECT cell_icons 
                FROM rpginferno.ml_maps_layers_cells
                WHERE layer_id = :lid
                  AND cell_icons::varchar <> \'{}\'
            ',
            ['lid' => $layerId]
        );

        $iconsMap = [];
        while ($icon = $icons->fetch()) {
            $levels = json_decode($icon['cell_icons'], true);

            foreach ($levels as $levelId => $icon) {
                $iconId = (int)$icon['i'];
                $presetId = (int)$icon['p'];

                if (!isset($iconsMap[$iconId])) {
                    $iconsMap[$iconId] = [
                        'count' => 0,
                        'presets' => []
                    ];
                }

                $iconsMap[$iconId]['count']++;

                if (!isset($iconsMap[$iconId]['presets'][$presetId])) {
                    $iconsMap[$iconId]['presets'][$presetId] = 0;
                }

                $iconsMap[$iconId]['presets'][$presetId]++;
            }
        }

        $iconsSql = [];
        $presetsSql = [];

        $iconsCount = 0;

        foreach($iconsMap as $iconId => $icon) {
            if (!$this->db->query('SELECT 1 FROM rpginferno.rpge_icons WHERE icon_id = '.$iconId)->fetch()) {
                continue;
            }

            $iconsCount += $icon['count'];
            $iconsSql[] = '('.$iconId.', '.$layerId.', '.$icon['count'].')';

            foreach ($icon['presets'] as $presetId => $presetCount) {
                $presetsSql[] = '('.$iconId.', '.$presetId.', '.$layerId.', '.$presetCount.')';
            }
        }


        if (sizeof($iconsSql)) {

            $this->db->begin();

            $this->db->query(
                '
                    INSERT INTO rpginferno.asset_use_icon_in_square
                    (icon_id, layer_id, icon_count)
                    VALUES '.implode(',', $iconsSql)
            );

            $this->db->query('
                    INSERT INTO rpginferno.asset_use_icon_preset_in_square
                    (icon_id, preset_id, layer_id, preset_count)
                    VALUES '.implode(',', $presetsSql)
            );

            $this->db->commit();

        }

        echo 'square layer: '.$layerId. '; icons: '.$iconsCount."\n";
    }

    private function assetsObjectInUseSquare($mapId, $layerId, $objectsRegistry) {
        $this->db->query(
            'DELETE FROM rpginferno.asset_use_object_in_square WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $this->db->query(
            'DELETE FROM rpginferno.asset_use_object_scheme_in_square WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $objectsMap = [];

        $x4CellFileName = DIR_PUBLIC.'lmap/grid2x/map_'.$mapId.'_'.$layerId.'.json';

        if (file_exists($x4CellFileName)) {
            $grid2Cells = json_decode(file_get_contents($x4CellFileName), true);

            foreach ($grid2Cells as $cellKey => $objects) {

                foreach ($objects as $objectId => $object) {
                    if (isset($object['name'])) {
                        if (!isset($objectsRegistry[$object['name']]) || !isset($object['scheme'])) {
                            continue;
                        }

                        $objectId = $objectsRegistry[$object['name']];

                        $schemeId = (int)$object['scheme'];
                    }
                    if (isset($object['o']) && sizeof($object['o'])) {
                        foreach ($object['o'] as $oldName => $oldData) {
                            if (!isset($objectsRegistry[$oldData['name']]) || !isset($oldData['scheme'])) {
                                continue;
                            }

                            $objectId = $objectsRegistry[$oldData['name']];

                            $schemeId = (int)$oldData['scheme'];

                            if (!isset($objectsMap[$objectId])) {
                                $objectsMap[$objectId] = [
                                    'count' => 0,
                                    'schemes' => []
                                ];
                            }

                            $objectsMap[$objectId]['count']++;

                            if (!isset($objectsMap[$objectId]['schemes'][$schemeId])) {
                                $objectsMap[$objectId]['schemes'][$schemeId] = 0;
                            }

                            $objectsMap[$objectId]['schemes'][$schemeId]++;
                        }

                        continue;
                    }
                    else {
                        if (!isset($object['s'])) {
                            continue;
                        }

                        $schemeId = (int)$object['s'];
                    }

                    if (!isset($objectsMap[$objectId])) {
                        $objectsMap[$objectId] = [
                            'count' => 0,
                            'schemes' => []
                        ];
                    }

                    $objectsMap[$objectId]['count']++;

                    if (!isset($objectsMap[$objectId]['schemes'][$schemeId])) {
                        $objectsMap[$objectId]['schemes'][$schemeId] = 0;
                    }

                    $objectsMap[$objectId]['schemes'][$schemeId]++;
                }
            }
        }


        $objects = $this->db->query(
            '
                SELECT cell_objects 
                FROM rpginferno.ml_maps_layers_cells
                WHERE layer_id = :lid
                  AND cell_objects::varchar <> \'{}\'
            ',
            ['lid' => $layerId]
        );

        while ($cell = $objects->fetch()) {
            $objectsList = json_decode($cell['cell_objects'], true);

            foreach ($objectsList as $objectId => $object) {
                if (isset($object['name'])) {
                    if (!isset($objectsRegistry[$object['name']]) || !isset($object['scheme'])) {
                        continue;
                    }

                    $objectId = $objectsRegistry[$object['name']];

                    $schemeId = (int)$object['scheme'];
                }
                else {
                    $schemeId = (int)$object['s'];
                }

                if (!isset($objectsMap[$objectId])) {
                    $objectsMap[$objectId] = [
                        'count' => 0,
                        'schemes' => []
                    ];
                }

                $objectsMap[$objectId]['count']++;

                if (!isset($objectsMap[$objectId]['schemes'][$schemeId])) {
                    $objectsMap[$objectId]['schemes'][$schemeId] = 0;
                }

                $objectsMap[$objectId]['schemes'][$schemeId]++;
            }
        }

        $objectsSql = [];
        $schemesSql = [];

        $objectsCount = 0;

        $existsPairs = [];

        foreach($objectsMap as $objectId => $object) {
            if (!$this->db->query(
                'SELECT 1 FROM rpginferno.ml_objects WHERE object_id = :oid',
                ['oid' => (int)$objectId]
            )->fetch()) {
                continue;
            }

            $objectsCount += $object['count'];
            $objectsSql[] = '('.$objectId.', '.$layerId.', '.$object['count'].')';

            foreach ($object['schemes'] as $schemeId => $schemeCount) {
                if (!$this->db->query(
                    'SELECT 1 FROM rpginferno.ml_objects_schemes WHERE scheme_id = :sid',
                    ['sid' => (int)$schemeId]
                )->fetch()) {
                    continue;
                }

                if (isset($existsPairs[$schemeId.'-'.$layerId])) {
                    continue;
                }


                $schemesSql[] = '('.$objectId.', '.$schemeId.', '.$layerId.', '.$schemeCount.')';
                $existsPairs[$schemeId.'-'.$layerId] = 1;
            }
        }


        if (sizeof($objectsSql)) {

            $this->db->begin();

            $this->db->query(
                '
                    INSERT INTO rpginferno.asset_use_object_in_square
                    (object_id, layer_id, object_count)
                    VALUES '.implode(',', $objectsSql)
            );

            $this->db->query('
                    INSERT INTO rpginferno.asset_use_object_scheme_in_square
                    (object_id, scheme_id, layer_id, scheme_count)
                    VALUES '.implode(',', $schemesSql)
            );

            $this->db->commit();

        }

        echo 'square layer: '.$layerId. '; objects: '.$objectsCount."\n";
    }

    private function assetsTexturesBlends($layerId, $mapType) {
        $this->db->query(
            'DELETE FROM rpginferno.asset_use_texture_in_'.$mapType.' WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $this->db->query(
            'DELETE FROM rpginferno.asset_use_blending_in_'.$mapType.' WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $grounds = $this->db->query(
            '
                SELECT texture_id, ground_json
                FROM rpginferno.'.($mapType == 'hex' ? 'mh' : 'ml').'_maps_layers_grounds
                WHERE layer_id = :lid
            ',
            ['lid' => $layerId]
        );


        $texturesMap = [];
        $blendingsMap = [];

        $texturesTotal = 0;

        while ($texture = $grounds->fetch()) {
            $textureId = (int)$texture['texture_id'];
            $textureCount = 0;

            $blends = json_decode($texture['ground_json'], true);

            foreach ($blends as $blendingId => $cells) {
                $cellsCount = sizeof($cells);
                $textureCount += $cellsCount;

                if (!isset($blendingsMap[$blendingId])) {
                    $blendingsMap[$blendingId] = 0;
                }

                $blendingsMap[$blendingId] += $cellsCount;
            }

            if (!isset($texturesMap[$textureId])) {
                $texturesMap[$textureId] = 0;
            }

            $texturesMap[$textureId] += $textureCount;
            $texturesTotal += $textureCount;
        }

        $texturesSql = [];
        $blendingsSql = [];

        foreach ($texturesMap as $textureId => $textureCount) {
            $texturesSql[] = '('.$textureId.', '.$layerId.', '.$textureCount.')';
        }

        foreach ($blendingsMap as $blendingId => $blendingCount) {
            $blendingsSql[] = '('.$blendingId.', '.$layerId.', '.$blendingCount.')';
        }

        if (sizeof($texturesSql)) {
            $this->db->begin();

            $this->db->query(
                '
                    INSERT INTO rpginferno.asset_use_texture_in_'.$mapType.'
                    (texture_id, layer_id, texture_count)
                    VALUES '.implode(',', $texturesSql)
            );

            $this->db->query('
                    INSERT INTO rpginferno.asset_use_blending_in_'.$mapType.'
                    (blend_id, layer_id, blend_count)
                    VALUES '.implode(',', $blendingsSql)
            );

            $this->db->commit();
        }

        echo $mapType.' layer: '.$layerId. '; textures: '.$texturesTotal."\n";
    }

    private function assetsTexts($layerId, $mapType) {
        $this->db->query(
            'DELETE FROM rpginferno.asset_use_text_in_'.$mapType.' WHERE layer_id = :lid',
            ['lid' => $layerId]
        );

        $this->db->query(
            '
                INSERT INTO rpginferno.asset_use_text_in_'.$mapType.' (layer_id, txpreset_id, txpreset_count)
                SELECT mlt.layer_id
                     , rt.txpreset_id
                     , COUNT(*) AS txpreset_count
                FROM rpginferno.'.($mapType == 'hex' ? 'mh' : 'ml').'_maps_layers_texts AS mlt
                   , rpginferno.rpge_text AS rt
                WHERE rt.text_id = mlt.text_id
                  AND mlt.layer_id = :lid
                GROUP BY mlt.layer_id
                       , rt.txpreset_id  
            ',
            ['lid' => $layerId]
        );


        echo $mapType.' layer: '.$layerId. '; texts ready'."\n";
    }

    private function assetsRoadsHex($layerId) {
        $this->db->query(
            'DELETE FROM rpginferno.asset_use_road_in_hex WHERE layer_id = :lid',
            ['lid' => $layerId]
        );


        $this->db->query(
            '
                INSERT INTO rpginferno.asset_use_road_in_hex (layer_id, preset_id, preset_count)
                SELECT mlr.layer_id
                     , mlr.preset_id
                     , COUNT(*) AS preset_count
                FROM rpginferno.mh_maps_layers_roads AS mlr
                WHERE mlr.layer_id = :lid
                GROUP BY mlr.layer_id
                       , mlr.preset_id 
            ',
            ['lid' => $layerId]
        );

        echo 'hex layer: '.$layerId. '; roads ready'."\n";
    }
}