<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DemoRequestController extends Controller
{
    // Formulaire public de la page d'accueil
    public function store(Request $request)
    {
        // Champ piège invisible : rempli uniquement par les robots
        if ($request->filled('website')) {
            return redirect(url('/') . '#contact')->with('demo_success', true);
        }

        $data = $request->validate([
            'formula'      => ['required', Rule::in(array_keys(DemoRequest::FORMULAS))],
            'company'      => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'phone'        => 'nullable|string|max:50',
            'company_size' => ['nullable', Rule::in(DemoRequest::SIZES)],
            'sector'       => 'nullable|string|max:100',
            'message'      => 'nullable|string|max:3000',
        ]);

        $demo = DemoRequest::create($data + ['ip_address' => $request->ip()]);

        // Prévenir le super admin (notification + email via le SMTP de la plateforme)
        $service = app(NotificationService::class);
        foreach (User::where('is_super_admin', true)->get() as $superAdmin) {
            $service->notify(
                $superAdmin,
                'demo_request',
                "Nouvelle demande : {$demo->company}",
                "{$demo->contact_name} ({$demo->email}" . ($demo->phone ? ", {$demo->phone}" : '') . ") souhaite une démo — formule : {$demo->formulaLabel()}.",
                route('super.demo-requests.index')
            );
        }

        return redirect(url('/') . '#contact')->with('demo_success', true);
    }
}
