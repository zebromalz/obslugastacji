<?php

namespace Service;

use Model\Sql\DateType;
use Model\Sql\InType;
use Model\Sql\LikeType;

abstract class FilterService
{
    /**
     * @var string
     */
    private $alias;
    /**
     * @var array
     */
    protected $filters;
    /**
     * @var int
     */
    public $page;
    /**
     * @var int
     */
    public $perPage;
    /**
     * @var int
     */
    public $maxPages;
    /**
     * @var int
     */
    public $itemsCount;

    /**
     * FilterService constructor.
     *
     * @param string $alias
     * @param int $perPage
     */
    public function __construct(string $alias, int $perPage)
    {
        $this->filters = [];
        $this->alias = $alias;
        $this->perPage = $perPage;
        $this->page = 1;
        $this->idField = 'id';
    }

    abstract protected function getFilterFields(): array;

    /**
     * @return int
     */
    private function calculateOffset()
    {
        if ($this->maxPages <= $this->page) {
            $this->page = $this->maxPages;
        }

        if ($this->page < 1) {
            $this->page = 1;
        }

        return (int) ($this->perPage * ($this->page - 1));
    }

    /**
     * @param int $itemsCount
     *
     * @return $this
     */
    public function setItemsCount(int $itemsCount)
    {
        $this->itemsCount = $itemsCount;
        $this->maxPages = (int) ceil($this->itemsCount / $this->perPage);

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxPages(): int
    {
        if ( ! is_null($this->MaxPages) )
            return $this->maxPages;
        else 
            return 0;
    }

    /**
     * @param int $page
     *
     * @return $this
     */
    public function setPage(int $page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    private function prepareArraySql(string $prefix, int $count)
    {
        $result = [];

        foreach (range(0, $count - 1) as $item) {
            $result[] = ':' . $prefix . $item;
        }

        return implode(', ', $result);
    }

    /**
     * @return array
     */
    public function getFilterValues(): array
    {
        $result = [];
        foreach ($this->filters as $key => $filter) {
            switch (get_class($filter)) {
                case InType::class:
                    foreach ($filter->getValues() as $index => $value) {
                        $result[$key . $index] = $value;
                    }
                    break;
                case LikeType::class:
                    $result[$key] = '%' . $filter->getValue() . '%';
                    break;
                case DateType::class:
                    $result[$key] = $filter->getValue()->format('Y-m-d');
                    break;
                default:
                    $result[$key] = $filter->getValue();
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        $result = [];
        $filters = [];
        $filterFields = $this->getFilterFields();
        foreach ($this->filters as $key => $filter) {
            $databaseKey = $filterFields[$key];
            switch (get_class($filter)) {
                case InType::class:
                    $filters[] = sprintf(
                        "%s.%s IN (%s)",
                        $this->alias,
                        $databaseKey,
                        $this->prepareArraySql($key, count($filter->getValues()))
                    );
                    break;
                case LikeType::class:
                    $filters[] = sprintf(
                        "%s.%s LIKE :%s",
                        $this->alias,
                        $databaseKey,
                        $key
                    );
                    break;
                case DateType::class:
                    $filters[] = sprintf(
                        "DATE_FORMAT(%s.%s, '%%Y-%%m-%%d') = :%s",
                        $this->alias,
                        $databaseKey,
                        $key
                    );
                    break;
                default:
                    $filters[] = sprintf(
                        "%s.%s = :%s",
                        $this->alias,
                        $databaseKey,
                        $key
                    );
                    break;
            }
        }

        $result[] = implode(' AND ', $filters);
        $result[] = 'ORDER BY '.$this->idField.' DESC';

        return implode(' ', $result);
    }

    /**
     * @return string
     */
    public function getLimitSql()
    {
        return sprintf(" LIMIT %s, %s", $this->calculateOffset(), $this->perPage);
    }
}