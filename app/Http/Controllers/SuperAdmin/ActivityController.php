<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformActivityLog::with('user', 'organization')->latest();

        if ($orgId = $request->input('organization')) {
            $query->where('organization_id', $orgId);
        }

        $logs          = $query->paginate(50)->withQueryString();
        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return view('super.activity.index', compact('logs', 'organizations'));
    }
}
