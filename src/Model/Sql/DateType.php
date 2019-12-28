<?php

namespace Model\Sql;

class DateType
{
    private $value;

    public function __construct(\DateTime $value)
    {
        $this->value = $value;
    }

    /**
     * @return \DateTime
     */
    public function getValue(): \DateTime
    {
        return $this->value;
    }

    /**
     * @param \DateTime $value
     *
     * @return DateType
     */
    public function setValue(\DateTime $value)
    {
        $this->value = $value;
        return $this;
    }
}
