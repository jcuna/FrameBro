<?php
/**
 * Author: Jon Garcia
 * Date: 2/24/16
 * Time: 7:08 PM
 */

namespace App\Core\Html;

/**
 * Class Pagination
 * @package App\Core\Html
 */
class Pagination
{
    /**
     * @var int
     */
    public $count;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var int
     */
    public $page;

    /**
     * @var int
     */
    public $paginationLength;

    /**
     * @var int
     */
    public $paginationEnd;

    /**
     * @var int
     */
    public $total;

    /**
     * @var int
     */
    public $paginationStart = 1;

    /**
     * @var bool
     */
    private $paginationProcessed = false;

    /**
     * Pagination constructor.
     * @param $count int|string the total number of elements to show
     * @param $limit int|string the limit of elements per page
     * @param $currentPage int|string the current page
     * @param int $paginationLength int|string the length of the pagination element. Or range default 10
     */
    public function __construct($count, $limit, $currentPage, $paginationLength = 10)
    {
        if (is_null($currentPage) || $currentPage === 0) {
            $currentPage = 1;
        }

        $this->count = intval($count);
        $this->limit = intval($limit);
        $this->page = intval($currentPage);
        $this->paginationEnd = $this->paginationLength = intval($paginationLength);
        $this->total = (int) ceil($count/$limit);

    }

    /**
     * @return int
     */
    public function getTotalPages() {
        return $this->total;
    }

    /**
     * @return int
     */
    public function getPage() {
        return $this->page;
    }


    /**
     * @return int
     */
    public function getOffset() {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Only do range pagination when total pages is larger than pagination length.
     */
    private function processPagination()
    {
        if ($this->total > $this->paginationLength) {
            $this->paginationEnd = (($this->total - $this->page) >= $this->paginationLength) ? $this->page + $this->paginationLength : $this->total;
            $this->paginationStart = ($this->page <= $this->paginationLength) ? 1 : ($this->page - $this->paginationLength);
        }

        $this->paginationProcessed = true;
    }

    /**
     * @return int
     */
    public function getPaginationEnd() {

        if ($this->paginationProcessed ) {
            return $this->paginationEnd;
        } else {
            $this->processPagination();
            return $this->paginationEnd;
        }
    }

    /**
     * @return int
     */
    public function getPaginationStart() {

        if ($this->paginationProcessed ) {
            return $this->paginationStart;
        } else {
            $this->processPagination();
            return $this->paginationStart;
        }
    }
}