<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use App\Models\Chirp;
use Illuminate\Http\Request;
use Illuminate\View\View;


class ChirpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('chirps.index', [

            'chirps' => Chirp::with('user')->latest()->get(),

        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $chirpCount = $request->user()->chirps()->count();

        if ($chirpCount >= 10) {
            return redirect()->route('chirps.index')->withErrors([
                'message' => "Vous ne pouvez pas créer plus de 10 chips. Donnez la chance á d'autres utilisateur aussi."
            ]);
        }

        $validated = $request->validate([

            'message' => 'required|string|max:255',

        ]);



        $request->user()->chirps()->create($validated);



        return redirect()->route('chirps.index')->with('success', 'Chirp créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Chirp $chirp)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Chirp $chirp): View
    {
        //
        Gate::authorize('update', $chirp);



        return view('chirps.edit', [

            'chirp' => $chirp,

        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Chirp $chirp): RedirectResponse
    {
        //
        Gate::authorize('update', $chirp);



        $validated = $request->validate([

            'message' => 'required|string|max:255',

        ]);



        $chirp->update($validated);



        return redirect(route('chirps.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Chirp $chirp): RedirectResponse
    {

        Gate::authorize('delete', $chirp);

        $chirp->delete();

        return redirect(route('chirps.index'));
    }
}
