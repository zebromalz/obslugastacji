<?php

namespace Service;

class StatusService
{
    const STATUS_NEW = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_PAID = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_REJECTED = 5;

    const STATUS_USER_NOTLOCKED = 0;
    const STATUS_USER_LOCKED = 1;
    const STATUS_USER_NOTACTIVE = 0;
    const STATUS_USER_ACTIVE = 1;

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

    /**
     * @param \stdClass $order
     *
     * @return array
     */
    public function getPossibleStatusesForUserLock(\stdClass $user): array
    {
        switch ($user->c_islocked) {
            case self::STATUS_USER_NOTLOCKED:
                return [self::STATUS_USER_LOCKED];
            case self::STATUS_USER_LOCKED:
                return [self::STATUS_USER_NOTLOCKED];
            }

        return [];
    }
    public function getPossibleStatusesForUserActivation(\stdClass $user): array
    {
        switch ($user->c_isactive) {
            case self::STATUS_USER_NOTACTIVE:
                return [self::STATUS_USER_ACTIVE];
            case self::STATUS_USER_ACTIVE:
                return [self::STATUS_USER_NOTACTIVE];
            }

        return [];

        return [];
    }
}
