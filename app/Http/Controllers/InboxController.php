<?php

namespace App\Http\Controllers;

use App\Services\InboxService;

class InboxController extends Controller
{
    public function index(InboxService $inbox)
    {
        $user = auth()->user();

        return view('inbox.index', [
            'approvals'          => $inbox->approvals($user),
            'upcomingApprovals'  => $inbox->upcomingApprovalsCount($user),
            'signatures'         => $inbox->signatures($user),
            'toFix'              => $inbox->toFix($user),
            'shares'             => $inbox->shares($user),
            'myApprovals'        => $inbox->myApprovalsInProgress($user),
            'mySignatureRequests'=> $inbox->mySignatureRequests($user),
        ]);
    }
}
