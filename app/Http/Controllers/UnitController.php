<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index()
    {
        $units = Unit::where('shop_id', auth()->user()->shop_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Units/Index', [
            'units' => $units,
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
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['shop_id'] = auth()->user()->shop_id;

        Unit::create($data);

        return redirect()->route('units.index')
            ->with('success', 'Unit created successfully!');
    }

    public function update(Request $request, Unit $unit)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $unit->update($data);

        return redirect()->route('units.index')
            ->with('success', 'Unit updated successfully!');
    }

    public function destroy(Unit $unit)
    {
        // Check if unit has products
        if ($unit->products()->count() > 0) {
            return redirect()->route('units.index')
                ->with('error', 'Cannot delete unit with products!');
        }

        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'Unit deleted successfully!');
    }

    public function toggleStatus(unit $unit)
    {
        $unit->update(['is_active' => !$unit->is_active]);

        return redirect()->route('units.index')
            ->with('success', 'Unit status updated!');
    }
}
