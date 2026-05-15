<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        $locations = Location::query()->orderBy('name')->get();
        return view('mista.index', compact('locations'));
    }

    public function create(): View
    {
        return view('mista.create', ['types' => Location::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        Location::create($this->validateData($request));
        return redirect()->route('mista.index')->with('status', 'Místo přidáno.');
    }

    public function edit(Location $mistum): View
    {
        return view('mista.edit', ['location' => $mistum, 'types' => Location::TYPES]);
    }

    public function update(Request $request, Location $mistum): RedirectResponse
    {
        $mistum->update($this->validateData($request));
        return redirect()->route('mista.index')->with('status', 'Místo upraveno.');
    }

    public function destroy(Location $mistum): RedirectResponse
    {
        $mistum->delete();
        return redirect()->route('mista.index')->with('status', 'Místo smazáno.');
    }

    protected function validateData(Request $request): array
    {
        $request->merge(['active' => $request->boolean('active')]);
        return $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'type'          => ['required', 'string', Rule::in(array_keys(Location::TYPES))],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
            'color'         => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'active'        => ['boolean'],
        ]);
    }
}
