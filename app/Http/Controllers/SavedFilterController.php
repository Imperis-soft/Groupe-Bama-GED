<?php

namespace App\Http\Controllers;

use App\Models\SavedFilter;
use App\Support\DocumentFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SavedFilterController extends Controller
{
    // Enregistrer la combinaison de filtres courante sous un nom
    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:80',
                            Rule::unique('saved_filters', 'name')->where('organization_id', $user->organization_id)->where('user_id', $user->id)],
            'query'     => 'nullable|string|max:4000',
            'is_shared' => 'nullable|boolean',
        ], ['name.unique' => 'Vous avez déjà une vue portant ce nom.']);

        if (SavedFilter::where('user_id', $user->id)->count() >= SavedFilter::MAX_PER_USER) {
            return back()->with('error', 'Vous avez atteint le maximum de ' . SavedFilter::MAX_PER_USER . ' vues enregistrées. Supprimez-en une pour continuer.');
        }

        // Les paramètres sont repassés par DocumentFilters : seules des valeurs valides sont enregistrées
        parse_str((string) ($data['query'] ?? ''), $input);
        $params = (new DocumentFilters($input, $user))->filterParams();
        if (!$params) {
            return back()->with('error', 'Appliquez au moins un filtre avant d\'enregistrer une vue.');
        }

        $view = SavedFilter::create([
            'user_id'   => $user->id,
            'name'      => $data['name'],
            'params'    => $params,
            // Seul un administrateur peut partager une vue à toute l'entreprise
            'is_shared' => $user->hasRole('admin') && $request->boolean('is_shared'),
        ]);

        return redirect($view->url())->with('success', "Vue « {$view->name} » enregistrée.");
    }

    public function destroy(SavedFilter $savedFilter)
    {
        if (!$savedFilter->canManage(auth()->user())) {
            abort(403);
        }
        $savedFilter->delete();

        return redirect()->route('documents.index')->with('success', "Vue « {$savedFilter->name} » supprimée.");
    }
}
