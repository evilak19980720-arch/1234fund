<?php

namespace App\Observers;

use App\Models\Issue;
use App\Services\FundingService;

class IssueObserver
{
    public function saved(Issue $issue)
    {
        if ($issue->draw == 0 && $issue->portion == $issue->bought) {
            $service = new FundingService($issue);
            $service->getPlayer()->draw();
        }
    }
}
