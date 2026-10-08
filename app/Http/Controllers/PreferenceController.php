<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Persists appearance preferences (mode, accent, dark theme) chosen in the theme menu.
 */
class PreferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['sometimes', 'in:light,dark,system'],
            'accent' => ['sometimes', 'in:indigo,blue,emerald,violet,rose,amber,teal'],
            'surface' => ['sometimes', 'in:gray,slate,zinc,stone'],
        ]);

        $request->user()->forceFill($data)->save();

        return response()->json(['ok' => true]);
    }
}
