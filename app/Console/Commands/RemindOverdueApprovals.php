<?php

namespace App\Console\Commands;

use App\Services\ApprovalWorkflow;
use App\Services\SignatureWorkflow;
use Illuminate\Console\Command;

class RemindOverdueApprovals extends Command
{
    protected $signature = 'approvals:remind';

    protected $description = 'Relance les validations et signatures en retard (une fois par jour, au plus ' . ApprovalWorkflow::MAX_REMINDERS . ' fois chacune)';

    public function handle(ApprovalWorkflow $workflow, SignatureWorkflow $signatures): int
    {
        $sent = $workflow->remindOverdue();
        $signed = $signatures->remindOverdue();
        $this->info("{$sent} relance(s) de validation, {$signed} relance(s) de signature envoyée(s).");

        return self::SUCCESS;
    }
}
