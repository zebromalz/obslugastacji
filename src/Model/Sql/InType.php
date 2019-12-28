<?php

namespace Model\Sql;

class InType
{
    /**
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     *
     * @return InType
     */
    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }
}
