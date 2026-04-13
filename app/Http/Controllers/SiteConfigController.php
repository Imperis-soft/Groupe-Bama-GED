<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteConfigController extends Controller
{

    // Afficher les paramètres du site
    public function index()
    {
        $settings = DB::table('settings')->pluck('value', 'key')->toArray();
        return view('settings.index', compact('settings'));
    }


    // Mettre à jour les paramètres du site
    public function update(Request $request)
    {
        $data = $request->except('_token');
        foreach ($data as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        return redirect()->route('settings.index')->with('success', 'Settings saved');
    }
}
