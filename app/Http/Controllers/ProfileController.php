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
        if ($request->has('binance_public_key')) {
            $request->user()->binance_public_key = $request->input('binance_public_key');
        }
        if ($request->has('binance_secret_key')) {
            $request->user()->binance_secret_key = $request->input('binance_secret_key');
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * CRUD for `fav_tickers` for User Table
     * Endpoint: `/user/fav-tickers`
     *      PUT: retrieve list
     *      POST: add the given ticker
     *      DELETE: delete the given ticker
     * 
     * @TODO: do a clean , specially before saving the list on update and delete. Clean null ...
     * 
     */



    /**
     * Adds a new favorite ticker to the authenticated user's list of favorite tickers.
     * Usage: 
     * POST /user/fav-tickers
     * body: {
     *  ticker: 'BTCUSDT'
     * }
     * or replace the whole list.
     * body: {
     *  list: 'BTCUSDT,ETCUSDT,DOGEBTC'
     * }
     *
     * @param Request $request The incoming HTTP request containing the ticker to add.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the updated list of favorite tickers.
     */
    public function addFavTicker(Request $request)
    {
        $user       = auth()->user(); // Obtener el usuario autenticado
        $favTickers = json_decode( $user->fav_tickers ?? '[]', true );

        $ticker = $request->input('ticker');
        $list   = $request->input('list');
        
        $updated = false;
        if ( !empty($ticker) ) {
            // Evitar duplicados
            if (!in_array($ticker, $favTickers)) {
                $favTickers[] = $ticker; // Añadir el nuevo ticker
            }
            $updated = true;
        }
        elseif ( $list === '' || !empty($list) ) {
            $favTickers = explode(',', $list);
            if ( !empty($list) && !count($favTickers) ) {
                // error
                return response()->json(['error' => 'Invalid list'], 400);
            }  
            $updated = true;         
        }

        if ($updated) {
            // Guardar los tickers favoritos actualizados en la base de datos
            $user->fav_tickers = array_filter( $favTickers, fn($item) => ! empty($item) );
            $user->save();
            return response()->json(['success' => true, 'fav_tickers' => $favTickers]);
        }

        return response()->json(['error' => 'Not udpdated for some reason'], 400);
    }

    /**
     * Retrieves the authenticated user's list of favorite tickers.
     * PUT /user/fav-tickers
     * 
     * @param Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of favorite tickers.
     */
    public function getFavTicker(Request $request) {
        $user       = auth()->user();
        $favTickers = json_decode( $user->fav_tickers ?? '[]', true );
        $favTickers = array_filter( $favTickers, fn($item) => ! empty($item) );
        return response()->json(array_values($favTickers));
    }

    
    /**
     * Delete one ticker from the user's list of favorite tickers.
     * DELETE /user/fav-ticker/BTCUSDT
     * 
     * @param string $ticker
     * @return \Illuminate\Http\JsonResponse A JSON response containing the updated list of favorite tickers.
     */
    public function deleteFavTicker( string $ticker ) {
        $user       = auth()->user();
        $favTickers = json_decode( $user->fav_tickers ?? '[]', true );

        if (in_array($ticker, $favTickers)) {
            $favTickers = array_diff($favTickers, [$ticker]);
        } else {
            return response()->json(['success' => true, 'note' => 'ticker not found', 'fav_tickers' => $favTickers]);
        }

        $user->fav_tickers = array_filter( $favTickers, fn($item) => ! empty($item) );
        $user->save();

        return response()->json(['success' => true, 'fav_tickers' => array_values($favTickers)]);
    }


    // functions for the creation of trades from selecting 2/3 orders (tripleorders)
    // Fetch trade group data for the given symbol
    public function loadTradeGroupsForSymbol($symbol)
    {
        $user = Auth::user();
        $tradeGroups = json_decode($user->trade_group_links, true);

        if (isset($tradeGroups[$symbol])) {
            return response()->json($tradeGroups[$symbol]);
        }

        return response()->json([]);
    }

    // Save trade group data for the given symbol
    public function saveTradeGroupsForSymbol(Request $request, $symbol)
    {
        $user = Auth::user();
        $tradeGroups = json_decode($user->trade_group_links, true) ?? [];

        // Update the trade groups for the given symbol
        $tradeGroups[$symbol] = $request->input('trade_groups');

        // Save back to the user's trade_group_links field
        $user->trade_group_links = json_encode($tradeGroups);
        $user->save();

        return response()->json(['status' => 'success']);
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
