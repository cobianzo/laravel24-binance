<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information from Edit profile page.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        // Our extra fields:
        if ($request->has('binance_token')) {
            $request->user()->binance_token = $request->input('binance_token');
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * POST: Adds a new favorite ticker to the authenticated user's list of favorite tickers.
     * body: {
     *  ticker: 'BTCUSDT'
     * }
     *
     * @param Request $request The incoming HTTP request containing the ticker to add.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the updated list of favorite tickers.
     */
    public function addFavTicker(Request $request)
    {
        $user   = auth()->user(); // Obtener el usuario autenticado
        $ticker = $request->input('ticker');

        // Verificar si el usuario ya tiene tickers favoritos
        $favTickers = json_decode( $user->fav_tickers ?? '[]', true );

        // Evitar duplicados
        if (!in_array($ticker, $favTickers)) {
            $favTickers[] = $ticker; // Añadir el nuevo ticker
        }

        // Guardar los tickers favoritos actualizados en la base de datos
        $user->fav_tickers = $favTickers;
        $user->save();

        return response()->json(['success' => true, 'fav_tickers' => $favTickers]);
    }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
