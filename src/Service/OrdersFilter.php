<?php

namespace Service;

use Model\Sql\DateType;
use Model\Sql\InType;
use Model\Sql\IntType;
use Model\Sql\LikeType;
use Model\Sql\StringType;

class OrdersFilter extends FilterService
{
    const FILTER_FIELDS = [
        'status' => 'o_status',
        'name' => 'o_name',
        'date' => 'o_datetime',
        'invoice' => 'o_f_id' 
    ];

    public function __construct(string $alias, int $perPage)
    {
        parent::__construct($alias, $perPage);
        $this->idField = 'o_id';
    }

    /**
     * @param array $filters
     *
     * @throws \Exception
     */
    public function bindFilter(array $filters)
    {
        $filterFields = $this->getFilterFields();

        foreach ($filters as $key => $filter) {
            if (array_key_exists($key, $filterFields) && !empty($filter)) {
                switch ($key) {
                    case 'name':
                        $this->filters[$key] = new LikeType($filter);
                        break;
                    case 'invoice':
                        $this->filters[$key] = new IntType($filter);
                        break;
                    case 'status':
                        $this->filters[$key] = new InType($filter);
                        break;
                    case 'date':
                        $this->filters[$key] = new DateType(new \DateTime($filter));
                        break;
                    default:
                        continue;
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function getFilterFields(): array
    {
        return static::FILTER_FIELDS;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        if (isset($this->filters[self::FILTER_FIELDS['name']])) {
            /** @var LikeType $type */
            $type = $this->filters[self::FILTER_FIELDS['name']];
            $type->getValue();
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getStatus()
    {
        if (isset($this->filters[self::FILTER_FIELDS['status']])) {
            /** @var StrictType $type */
            $type = $this->filters[self::FILTER_FIELDS['status']];
            $type->getValue();
        }

        return null;
    }
    public function getInvoice()
    {
        if (isset($this->filters[self::FILTER_FIELDS['invoice']])) {
            /** @var StrictType $type */
            $type = $this->filters[self::FILTER_FIELDS['invoice']];
            $type->getValue();
        }

        return null;
    }
}
