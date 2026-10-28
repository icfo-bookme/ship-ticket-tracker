<?php

namespace App\Services\Sales;

use App\Models\ShipTicketSale;
use App\Models\User;

class SaleStatusCountService
{
    public function __construct(private readonly SaleVisibilityService $visibility) {}

    /** @return array<string, int> */
    public function counts(User $user): array
    {
        if (! $user->can('sales.view')) {
            return [];
        }

        $counts = (new ShipTicketSale)->getConnection()->query();
        $hasStatuses = false;

        foreach (array_keys(config('sales.navbar_statuses', [])) as $status) {
            if (! $user->can('sales.status.'.$status)) {
                continue;
            }

            $query = ShipTicketSale::query();
            $this->visibility->applyStatusVisibility($query, $status);
            $this->visibility->excludeSalesWithActiveRefunds($query);
            $counts->selectSub($query->selectRaw('COUNT(*)'), $status);
            $hasStatuses = true;
        }

        return $hasStatuses ? array_map('intval', (array) $counts->first()) : [];
    }
}
