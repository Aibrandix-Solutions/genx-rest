<?php

namespace App\Observers;

use App\Models\Kot;
use App\Models\KotItem;
use App\Events\KotUpdated;

class KotItemObserver
{
    /** @var array<int, true> */
    private static array $queuedKotIds = [];

    public function saved(KotItem $kotItem)
    {
        $this->queueKotBroadcast((int) $kotItem->kot_id);
    }

    public function deleted(KotItem $kotItem)
    {
        $this->queueKotBroadcast((int) $kotItem->kot_id);
    }

    private function queueKotBroadcast(int $kotId): void
    {
        if ($kotId <= 0 || isset(self::$queuedKotIds[$kotId])) {
            return;
        }

        // One broadcast per KOT after the response — avoid N Pusher calls for N items.
        self::$queuedKotIds[$kotId] = true;

        dispatch(function () use ($kotId) {
            unset(self::$queuedKotIds[$kotId]);

            $kot = Kot::query()->find($kotId);
            if ($kot) {
                event(new KotUpdated($kot));
            }
        })->afterResponse();
    }
}
