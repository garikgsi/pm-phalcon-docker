<?php
namespace Core\Builder;

/**
 * Class Bookmarks
 *
 * Класс - генератор данных для рендеринга пенелей закладок в пользовательском интерфейсе
 *
 * @package Core\Builder
 */
class Bookmarks
{
    /**
     * @var array - Список закладок
     */
    private $bookMarksList = [];

    /**
     * @var null|string - Идентификатор активной закладки
     */
    private $activeName = null;

    /**
     * Возвращает список закладок
     *
     * @return array
     */
    public function getBookMarks() {
        return $this->bookMarksList;
    }

    /**
     * Добавление новой закладки
     *
     * @param string $name    - идентификатор закладки
     * @param string $title   - название закладки
     * @param string $href    - ссылка закладки
     * @param int    $level   - уровень рендера для хистори апи
     * @param int    $active  - активна ли закладка
     * @param string $num     - цифра на закладке
     * @param string $classes - дополнительные классны
     * @param string $attrs   - дополнительные аттрибуты
     */
    public function addBookMark($name, $title, $href, $level = 4, $active = 0, $num = '', $classes = '', $attrs = '') {
        if ($active) { // Если добавляется активная закладка, надо сбросить статус предыдущей
            $this-> unsetActive();
        }

        $this->bookMarksList[$name] = [
            'title'   => $title,
            'href'    => $href,
            'level'   => $level,
            'active'  => $active,
            'num'     => $num,
            'classes' => $classes,
            'attrs'   => $attrs,
            'res_classes' => $this->genResClasses($active, $classes)
        ];
    }

    /**
     * Добавление закладок через массив
     *
     * @param array $array - массив с закладками
     */
    public function addBookMarksFromArray($array = []) {
        for($a = 0, $len = sizeof($array); $a < $len; $a++) {
            $item = $array[$a];

            $this->addBookMark(
                $item[0], // $name
                $item[1], // $title
                $item[2], // $href
                $item[3], // $level
                $item[4], // $active
                $item[5], // $num
                $item[6], // $classes
                $item[7]  // $attrs
            );

            if ($item[4]) {
                $this->setActive($item[0]);
            }
        }

    }

    /**
     * Устанавливает статус "активна" на закладку, попутно синмая статус с закладки, которая активна на данный момент
     *
     * @param string $bookName - идентификатор закладки
     */
    public function setActive($bookName) {
        $this->unsetActive(); // Перед установкой статуса, сбрасываем статус предыдущей

        $this->activeName = $bookName;

        $this->setActiveState($bookName, 1);
    }

    /**
     * Снимает выделение с текущей активной закладки, также снимается сам статус "активна"
     */
    public function unsetActive() {
        if ($this->activeName === null) {
            return;
        }

        $this->setActiveState($this->activeName, 0);

        $this->activeName = null;
    }

    /**
     * Проверка существования закладки
     *
     * @param string $bookName - идентификатор закладки
     * @return bool
     */
    public function isBookMarkExists($bookName) {
        return isset($this->bookMarksList[$bookName]);
    }


    /**
     * Смена данных закладки в соотвествии с текущим статусом "активности"
     *
     * @param string $bookName - идентификатор закладки
     * @param int    $active   - активна ли закладка
     */
    private function setActiveState($bookName, $active) {
        $bookData = $this->bookMarksList[$bookName];

        $bookData['active'] = $active;

        $bookData['res_classes'] = $this->genResClasses($active, $bookData['classes']);

        $this->bookMarksList[$bookName] = $bookData;
    }

    /**
     * Генерация классов на закладку
     *
     * @param int    $active  - активна ли закладка
     * @param string $classes - дополнительные классы
     * @return string
     */
    private function genResClasses($active, $classes) {
        return ' elizaHApi'.($active ? ' active' : '').($classes != '' ? ' '.$classes : '');
    }
}