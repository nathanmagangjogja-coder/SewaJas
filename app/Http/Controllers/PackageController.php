<?php

namespace App\Http\Controllers;

use App\Models\RentalPackage;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Daftar Paket
     */
    public function index(Request $request)
    {
        $packages = RentalPackage::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('description', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('duration_days')
            ->paginate(15)
            ->withQueryString();

        return view('packages.index', compact('packages'));
    }

    /**
     * Form Tambah
     */
    public function create()
    {
        $package = new RentalPackage();

        return view('packages.form', [
            'package' => $package,
            'mode' => 'create'
        ]);
    }

    /**
     * Simpan
     */
    public function store(Request $request)
    {
        RentalPackage::create($this->validatedData($request));

        return redirect()
            ->route('packages.index')
            ->with('success', 'Paket berhasil ditambahkan.');
    }

    /**
     * Form Edit
     */
    public function edit(RentalPackage $package)
    {
        return view('packages.form', [
            'package' => $package,
            'mode' => 'edit'
        ]);
    }

    /**
     * Update
     */
    public function update(Request $request, RentalPackage $package)
    {
        $package->update($this->validatedData($request));

        return redirect()
            ->route('packages.index')
            ->with('success', 'Paket berhasil diperbarui.');
    }

    /**
     * Hapus
     */
    public function destroy(RentalPackage $package)
    {
        if (method_exists($package, 'rentals') &&
            $package->rentals()->exists()) {

            return back()->with(
                'error',
                'Paket sedang digunakan pada transaksi penyewaan sehingga tidak dapat dihapus.'
            );
        }

        $package->delete();

        return back()->with(
            'success',
            'Paket berhasil dihapus.'
        );
    }

    /**
     * Preview Denda
     */
    public function penaltyPreview(RentalPackage $package)
    {
        $result = [];

        for ($day = 1; $day <= 30; $day++) {

            $percent = min(
                $day * $package->penalty_percent,
                $package->max_penalty_percent
            );

            $result[] = [
                'day' => $day,
                'penalty_percent' => $percent,
            ];
        }

        return response()->json($result);
    }

    /**
     * Validasi Data
     */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:100',
            'description'          => 'nullable|string',

            'duration_days'        => 'required|integer|min:0',

            'sort_order'           => 'nullable|integer|min:0',

            'penalty_percent'      => 'required|numeric|min:0|max:100',

            'max_penalty_percent'  => 'nullable|numeric|min:0',

            'is_active'            => 'nullable|boolean',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 1;

        $data['is_custom'] = ((int)$data['duration_days']) === 0;

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}