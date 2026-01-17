<?php

namespace Modules\Inventory\Observers;

use Modules\Inventory\Entities\Unit;

class UnitObserver
{

    public function creating(Unit $unit)
    {
        // Disabled: Units are now restaurant-scoped, not branch-scoped
        // if (branch()) {
        //     $unit->branch_id = branch()->id;
        // }
    }
}
