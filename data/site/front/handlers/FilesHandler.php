<?php

namespace Site\Front\Handlers;


use Phalcon\Di\Injectable;

class FilesHandler extends Injectable
{
    private int $fileNodeId;


    public function __construct(int $fileNodeId)
    {
        $this->fileNodeId = $fileNodeId;
    }

    public function getFileById(int $fileId) {
        return $this->db->query(
            '
                SELECT f.file_id
                     , f.file_type_id
                     , f.file_hash
                     , f.file_extension
                     , (\'/uploads/files/\' || f.file_hash || \'.\' || f.file_extension) AS file_path
                     , f.file_create_date::date AS file_create_date
                FROM common.file AS f
                WHERE f.file_id = :fid
            ',
            [
                'fid' => $fileId
            ]
        )->fetch();
    }

    public function getFilesByTraits(array $traitsList) {
        return $this->db->query(
            '
                SELECT f.file_id
                     , f.file_type_id
                     , f.file_hash
                     , f.file_extension
                     , (\'/uploads/files/\' || f.file_hash || \'.\' || f.file_extension) AS file_path
                     , f.file_create_date::date AS file_create_date
                     , ft.file_trait_id
                FROM common.file AS f
                   , common.file_trait AS ft
                WHERE f.file_node_id = :nid
                  AND ft.file_trait_id IN ('.implode(',', $traitsList).')
            ',
            [
                'nid' => $this->fileNodeId
            ]
        )->fetchAll();
    }

    public function addFile(int $fileTypeId, $file) : int
    {
        $nameExpl = explode('.', $file['name']);
        $fileExtension = mb_strtolower($nameExpl[sizeof($nameExpl) - 1]);


        $filePath = DIR_PUBLIC.'uploads/files/';
        $fileHash = md5(mt_rand(0, 7000).date('U').$file['name']);
        $fileName = $fileHash.'.'.$fileExtension;

        $fileId = (int)$this->db->query(
            '
                SELECT common.file_create(:mid, :nid, :tid, :hash, :name, :ext, :size) AS res
            ',
            [
                'mid' => $this->user->getId(),
                'nid' => $this->fileNodeId,
                'tid' => $fileTypeId,
                'hash' => $fileHash,
                'name' => $file['name'],
                'ext' => $fileExtension,
                'size' => $file['size']
            ]
        )->fetch()['res'];

        if ($fileId > 0) {
            move_uploaded_file($file['tmp_name'], $filePath.$fileName);
        }

        return $fileId;
    }

    public function linkSingleTrait(int $fileId, int $fileTraitId) : int
    {
        return (int)$this->db->query(
            '
                SELECT common.file_trait_link(:fid, :tid, 1) As res
            ',
            [
                'fid' => $fileId,
                'tid' => $fileTraitId
            ]
        )->fetch()['res'];
    }

}