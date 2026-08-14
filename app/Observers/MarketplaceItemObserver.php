<?php

namespace App\Observers;

use App\Models\MarketplaceItem;
use App\Services\MarketplaceItemAuditService;

class MarketplaceItemObserver
{
    public function __construct(
        protected MarketplaceItemAuditService $audits
    ) {}

    public function created(MarketplaceItem $item): void
    {
        $this->audits->logCreated($item);
    }

    public function updating(MarketplaceItem $item): void
    {
        $this->audits->logUpdated($item);
    }

    public function deleted(MarketplaceItem $item): void
    {
        $this->audits->logArchived($item);
    }

    public function restored(MarketplaceItem $item): void
    {
        $this->audits->logRestored($item);
    }
}
