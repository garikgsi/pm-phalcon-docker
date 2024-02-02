<?php
namespace Core\Lib;

class AjaxFormResponse
{
    const ERROR   = 'error';
    const SUCCESS = 'success';
    const INFO    = 'info';
    const CLEAR   = 'clear';

    private $fieldsList = [];


    public function setStatus($inputName, $statusType, $statusText = '') {
        $this->fieldsList[$inputName] = [$statusType, $statusText];

        return $this;
    }

    public function getStatuses() {
        return $this->fieldsList;
    }

    /**
     * Заполняет чистыми статусами весь пост массив
     *
     * @return $this
     */
    public function fillPostWithClear() {
        $this->fillPostWithStatus(self::CLEAR);

        return $this;
    }

    public function fillPostWithSuccess() {
        $this->fillPostWithStatus(self::SUCCESS);

        return $this;
    }

    private function fillPostWithStatus($status) {
        foreach($_POST as $i => $v) {
            $this->setStatus($i, $status);
        }
    }
}