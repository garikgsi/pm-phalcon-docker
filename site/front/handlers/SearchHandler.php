<?php
namespace Site\Front\Handlers;


use Phalcon\Di\Injectable;

class SearchHandler extends Injectable
{
    const ITEMS_PER_PAGE = 50;
    const PAGE_LEFT_RIGHT_DELTA = 3;

    private $EntityHandler;

    public function __construct()
    {

    }

    public function setEntityHandler($EntityHandler)
    {
        $this->EntityHandler = $EntityHandler;
    }

    public function performSearch($uri)
    {

        $this->EntityHandler->searchPrepareParams();
        $sortingMap = $this->EntityHandler->searchGetSorts();
        $columnsMap = $this->EntityHandler->searchGetColumns();
        $requestUri = $this->EntityHandler->searchGetRequestUri();
        $filterMap = $this->EntityHandler->searchGetFilterMap();

        $totalCount = $this->EntityHandler->searchGetTotalCount();

        $paginationData = $this->preparePagination($totalCount, sizeof($requestUri) ? '/'.$uri.'?'.implode('&', $requestUri) : null);
        $sqlOffset = $paginationData['sql_offset'];

        unset($paginationData['sql_offset']);

        $sqlOrder = $this->prepareSorting($sortingMap);

        if ($sqlOrder) {
            $sqlOrder = 'ORDER BY '.$sqlOrder;
        }
        else {
            $sqlOrder = '';
        }

        $this->prepareColumns($columnsMap);

        $this->view->setVar(
            'searchEngineContent',
            $this->EntityHandler->searchPerform(self::ITEMS_PER_PAGE, $sqlOrder, $sqlOffset)
        );



        $sortingList = $sortingMap;

        usort($sortingList, function ($item1, $item2) {
            return $item1['order'] <=> $item2['order'];
        });

        $columnsList = $columnsMap;

        usort($columnsList, function ($item1, $item2) {
            return $item1['order'] <=> $item2['order'];
        });

        $this->view->setVar('searchEngineColumns', $columnsList);
        $this->view->setVar('searchEngineSorting', $sortingMap);

        return [
            'filters' => $filterMap,
            'sorting' => $sortingMap,
            'columns' => $columnsMap,
            'sorting_list' => $sortingList,
            'columns_list' => $columnsList,
            'pagination' => $paginationData
        ];
    }

    private function preparePagination($itemsTotal, $searchUri = null) {
        $currentPage = (int)$this->request->get('page');

        $pagesTotal = ceil($itemsTotal / self::ITEMS_PER_PAGE);

        $pagesTotal = $pagesTotal < 1 ? 1 : $pagesTotal;

        $currentPage = $currentPage < 1 ? 1 : $currentPage;
        $currentPage = $currentPage > $pagesTotal ? $pagesTotal : $currentPage;

        $startEnabled = 0;
        $endEnabled = 0;

        $leftSide = [];
        $rightSide = [];

        if ($pagesTotal > 1) {
            $leftPage  = $currentPage - self::PAGE_LEFT_RIGHT_DELTA;
            $rightPage = $currentPage + self::PAGE_LEFT_RIGHT_DELTA;

            $startEnabled = $leftPage > 1;
            $endEnabled = $rightPage < $pagesTotal;

            for($a = 0, $len = self::PAGE_LEFT_RIGHT_DELTA; $a < $len; $a++) {
                $leftPageNumber = $currentPage - ($len - $a);
                $rightPageNumber = $currentPage + ($a + 1);

                if ($leftPageNumber >= 1) {
                    $leftSide[] = $leftPageNumber;
                }

                if ($rightPageNumber <= $pagesTotal) {
                    $rightSide[] = $rightPageNumber;
                }
            }
        }

        $paginationData = [
            'items_total' => $itemsTotal,
            'start_enabled' => $startEnabled,
            'end_enabled' => $endEnabled,
            'left_side' => $leftSide,
            'right_side' => $rightSide,
            'current_page' => $currentPage,
            'pages_total' => $pagesTotal,
            'pagination_enabled' =>  $pagesTotal > 1,
            'pagination_uri' => $searchUri ? $searchUri.'&page=' : '?page=',
            'sql_offset' => $currentPage == 1 ? '' : ' OFFSET '.(($currentPage - 1) * self::ITEMS_PER_PAGE)
        ];

        $this->view->setVar('searchEnginePagination', $paginationData);

        return $paginationData;
    }

    private function prepareSorting(&$sortingMap) {
        $allowedOrders = [
            'asc' => 1,
            'desc' => 1
        ];

        $sortingString = mb_strtolower(trim($this->request->get('sorting')));

        $orderList = [];

        if ($sortingString != '') {
            $sortParts = explode(',',$sortingString);

            $isCleared = 0;

            for($a = 0, $len = sizeof($sortParts); $a < $len; $a++) {
                $orderParts = explode('-', $sortParts[$a]);

                if (sizeof($orderParts) != 2) {
                    continue;
                }

                if (!(isset($sortingMap[$orderParts[0]]) &&
                    isset($allowedOrders[$orderParts[1]]) &&
                    $sortingMap[$orderParts[0]]['sql'])) {
                    continue;
                }

                if (!$isCleared) {
                    $orderList = [];
                    $isCleared = 1;
                }

                $orderList[] = $sortingMap[$orderParts[0]]['sql'].' '.$orderParts[1];

                $sortingMap[$orderParts[0]]['selected'] = 1;
                $sortingMap[$orderParts[0]]['order'] = $a + 1;
                $sortingMap[$orderParts[0]]['mode'] = $orderParts[1];
            }
        }

        return sizeof($orderList) ? implode(',', $orderList) : false;
    }

    private function prepareColumns(&$columnsMap) {
        $columnsString = mb_strtolower(trim($this->request->get('columns')));

        if ($columnsString == '') {
            return;
        }

        $columns = explode(',',$columnsString);

        foreach($columnsMap as $column => $data) {
            $columnsMap[$column]['active'] = 0;
        }

        for($a = 0, $len = sizeof($columns); $a < $len; $a++) {
            $column = $columns[$a];

            if (!isset($columnsMap[$column])) {
                continue;
            }

            $columnsMap[$column]['order'] = $a;
            $columnsMap[$column]['active'] = 1;
        }
    }
}