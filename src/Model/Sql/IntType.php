<?php

namespace Model\Sql;

class IntType
{
    /**
     * @var string
     */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     *
     * @return $this
     */
    public function setValue(int $value)
    {
        $this->value = $value;
        return $this;
    }

    public function __toString()
    {
        return $this->value;
    }
}
