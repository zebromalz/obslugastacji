<?php

namespace Service;

use Model\Sql\DateType;
use Model\Sql\InType;
use Model\Sql\LikeType;
use Model\Sql\StringType;

class UsersFilter extends FilterService
{
    const FILTER_FIELDS = [
        'locked' => 'c_islocked',
        'active' => 'c_isactive',
        'name' => 'c_name',
        'surname' => 'c_surname',
        'registered' => 'c_registered',
        'email' => 'c_email'
    ];

    public function __construct(string $alias, int $perPage)
    {
        parent::__construct($alias, $perPage);
        $this->idField = 'c_id';
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
                    case 'surname':
                            $this->filters[$key] = new LikeType($filter);
                        break;
                    case 'active':
                            $this->filters[$key] = new InType($filter);
                        break;
                    case 'locked':
                        $this->filters[$key] = new InType($filter);
                        break;
                    case 'registered':
                        $this->filters[$key] = new DateType(new \DateTime($filter));
                        break;
                    case 'email':
                        $this->filters[$key] = new LikeType($filter);
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
     * @return string|null
     */
    public function getSurname()
    {
        if (isset($this->filters[self::FILTER_FIELDS['surname']])) {
            /** @var LikeType $type */
            $type = $this->filters[self::FILTER_FIELDS['surname']];
            $type->getValue();
        }

        return null;
    }
        /**
     * @return string|null
     */
    public function getEmail()
    {
        if (isset($this->filters[self::FILTER_FIELDS['email']])) {
            /** @var LikeType $type */
            $type = $this->filters[self::FILTER_FIELDS['email']];
            $type->getValue();
        }

        return null;
    }
    /**
     * @return int|null
     */
    public function getLocked()
    {
        if (isset($this->filters[self::FILTER_FIELDS['locked']])) {
            /** @var StrictType $type */
            $type = $this->filters[self::FILTER_FIELDS['locked']];
            $type->getValue();
        }

        return null;
    }
        /**
     * @return int|null
     */
    public function getActive()
    {
        if (isset($this->filters[self::FILTER_FIELDS['active']])) {
            /** @var StrictType $type */
            $type = $this->filters[self::FILTER_FIELDS['active']];
            $type->getValue();
        }

        return null;
    }
}
