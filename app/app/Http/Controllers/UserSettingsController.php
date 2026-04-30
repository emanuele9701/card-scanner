<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    private array $supportedLanguages = [
        'it' => 'Italiano',
        'en' => 'English',
    ];

    public function index(): View
    {
        $user = Auth::user();

        return view('settings', [
            'user' => $user,
            'currentLanguage' => $user->language ?? app()->getLocale(),
            'languages' => $this->supportedLanguages,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string', 'in:' . implode(',', array_keys($this->supportedLanguages))],
        ]);

        $user = Auth::user();
        $user->setSetting('language', $validated['language']);

        return redirect()->route('settings.index')->with('success', 'Lingua preferita aggiornata.');
    }
}
