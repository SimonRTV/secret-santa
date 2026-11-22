<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAppearanceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    /**
     * Show the user's appearance settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Appearance');
    }

    /**
     * Update the authenticated user's appearance preferences.
     */
    public function update(UpdateAppearanceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->admin_theme = $request->validated('admin_theme');
        $user->save();

        return response()->json(['admin_theme' => $user->admin_theme]);
    }
}
