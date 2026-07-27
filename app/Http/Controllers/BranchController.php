<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['users', 'products', 'rentals'])
            ->orderBy('name')
            ->get();

        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'code'      => 'required|string|max:20|unique:branches,code',
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'city'      => 'nullable|string|max:100',
            'province'  => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        Branch::create([
            'name'      => $request->name,
            'code'      => strtoupper($request->code),
            'address'   => $request->address,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'city'      => $request->city,
            'province'  => $request->province,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('branches.index')
            ->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function show(Branch $branch)
    {
        $branch->loadCount(['users', 'products', 'rentals']);
        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'code'      => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'address'   => 'nullable|string',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'city'      => 'nullable|string|max:100',
            'province'  => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $branch->update([
            'name'      => $request->name,
            'code'      => strtoupper($request->code),
            'address'   => $request->address,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'city'      => $request->city,
            'province'  => $request->province,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('branches.index')
            ->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->count() > 0) {
            return back()->with('error', 'Cabang tidak bisa dihapus karena masih memiliki pengguna.');
        }

        if ($branch->rentals()->count() > 0) {
            return back()->with('error', 'Cabang tidak bisa dihapus karena masih memiliki data rental.');
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }
}
