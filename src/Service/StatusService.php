<?php

namespace Service;

class StatusService
{
    const STATUS_NEW = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_PAID = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_REJECTED = 5;

    /**
     * @param \stdClass $order
     *
     * @return array
     */
    public function getPossibleStatusesForOrder(\stdClass $order): array
    {
        switch ($order->o_status) {
            case self::STATUS_NEW:
                return [self::STATUS_ACCEPTED, self::STATUS_REJECTED];
            case self::STATUS_ACCEPTED:
                return [self::STATUS_PAID, self::STATUS_REJECTED];
            case self::STATUS_PAID:
                return [self::STATUS_COMPLETED];
        }

        return [];
    }
}
