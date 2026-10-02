<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemoRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DemoRequest::latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($formula = $request->input('formula')) {
            $query->where('formula', $formula);
        }

        $demoRequests = $query->paginate(25)->withQueryString();
        $counts       = DemoRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('super.demo-requests.index', compact('demoRequests', 'counts'));
    }

    public function update(Request $request, DemoRequest $demoRequest)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(DemoRequest::STATUSES))],
            'notes'  => 'nullable|string|max:5000',
        ]);

        $demoRequest->update($data);

        return back()->with('success', "Demande de {$demoRequest->company} mise à jour.");
    }
}
