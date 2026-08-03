<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = Customer::with('branch')
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                   ->orWhere('phone', 'like', "%{$request->search}%")
                   ->orWhere('id_number', 'like', "%{$request->search}%");
            }))
            ->when($request->blacklisted !== null, fn($q) => $q->where('is_blacklisted', $request->blacklisted === '1'))
            ->latest();

        $customers = $query->paginate(15)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:150',
            'phone'        => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address'      => 'nullable|string',
            'chest'        => 'nullable|string|max:10',
            'waist'        => 'nullable|string|max:10',
            'hip'          => 'nullable|string|max:10',
            'height'       => 'nullable|string|max:10',
            'weight'       => 'nullable|string|max:10',
            'suit_size'    => 'nullable|string|max:10',
            'shirt_size'   => 'nullable|string|max:10',
            'trouser_size' => 'nullable|string|max:10',
            'shoe_size'    => 'nullable|string|max:10',
            'body_notes'   => 'nullable|string',
            'photo'        => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'Nomor HP hanya boleh berisi angka.',
        ]);

        $duplicate = Customer::findDuplicate($request->name, $request->phone);
        if ($duplicate) {
            return back()->withInput()->withErrors([
                'duplicate' => 'Customer dengan nama atau nomor HP ini sudah terdaftar.',
                'existing_id' => $duplicate->id,
                'existing_name' => $duplicate->name,
                'existing_phone' => $duplicate->phone,
                'existing_url' => route('customers.show', $duplicate),
            ]);
        }

        $data['branch_id'] = Auth::user()->branch_id;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('customers/photos', 'public');
        }

        // NOTE: `dd($data);` yang sebelumnya ada di sini SUDAH DIHAPUS.
        // Baris itu menghentikan setiap request store() dengan var_dump
        // dan mem-block seluruh fitur tambah customer di production.

        try {
            $customer = Customer::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // MySQL duplicate entry code
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'unique_customer_phone') || str_contains($msg, 'phone_normalized')) {
                    return back()->withInput()->withErrors(['phone' => 'Nomor HP sudah terdaftar.']);
                }
                if (str_contains($msg, 'unique_customer_name') || str_contains($msg, 'name_normalized')) {
                    return back()->withInput()->withErrors(['name' => 'Nama customer sudah terdaftar.']);
                }
                if (str_contains($msg, 'unique_customer_idnumber') || str_contains($msg, 'id_number_normalized')) {
                    return back()->withInput()->withErrors(['id_number' => 'Nomor identitas sudah terdaftar.']);
                }
                if (str_contains($msg, 'unique_customer')) {
                    return back()->withInput()->withErrors(['duplicate' => 'Customer dengan nama dan nomor telepon sudah terdaftar.']);
                }
            }
            throw $e;
        }

        ActivityLog::record('create_customer', 'Menambah customer baru', $customer, null, $customer->toArray());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer berhasil ditambahkan!');
    }

    public function show(Customer $customer)
    {
        // Batasi sales/admin hanya bisa lihat customer cabangnya sendiri
        if (!Auth::user()->isSuperAdmin() && $customer->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Anda tidak memiliki akses ke data customer ini.');
        }

        $customer->load(['branch', 'rentals' => fn($q) => $q->with(['items'])->latest()->limit(10)]);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        if (!Auth::user()->isSuperAdmin() && $customer->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Anda tidak memiliki akses ke data customer ini.');
        }

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        if (!Auth::user()->isSuperAdmin() && $customer->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Anda tidak memiliki akses ke data customer ini.');
        }

        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'phone'       => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'email'       => 'nullable|email|max:100',
            'address'     => 'nullable|string',
            'chest'       => 'nullable|string|max:10',
            'waist'       => 'nullable|string|max:10',
            'suit_size'   => 'nullable|string|max:10',
            'photo'       => 'nullable|image|max:2048',
        ], [
            'phone.regex' => 'Nomor HP hanya boleh berisi angka.',
        ]);

        $duplicate = Customer::findDuplicate($request->name, $request->phone);
        if ($duplicate && $duplicate->id !== $customer->id) {
            return back()->withInput()->withErrors([
                'duplicate' => 'Customer dengan nama atau nomor HP ini sudah terdaftar.',
                'existing_id' => $duplicate->id,
                'existing_name' => $duplicate->name,
                'existing_phone' => $duplicate->phone,
                'existing_url' => route('customers.show', $duplicate),
            ]);
        }

        if ($request->hasFile('photo')) {
            if ($customer->photo) Storage::disk('public')->delete($customer->photo);
            $data['photo'] = $request->file('photo')->store('customers/photos', 'public');
        }

        $old = $customer->getOriginal();
        try {
            $customer->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
                $msg = $e->getMessage();
                if (str_contains($msg, 'unique_customer_phone') || str_contains($msg, 'phone_normalized')) {
                    return back()->withInput()->withErrors(['phone' => 'Nomor HP sudah terdaftar.']);
                }
                if (str_contains($msg, 'unique_customer_name') || str_contains($msg, 'name_normalized')) {
                    return back()->withInput()->withErrors(['name' => 'Nama customer sudah terdaftar.']);
                }
                if (str_contains($msg, 'unique_customer_idnumber') || str_contains($msg, 'id_number_normalized')) {
                    return back()->withInput()->withErrors(['id_number' => 'Nomor identitas sudah terdaftar.']);
                }
                if (str_contains($msg, 'unique_customer')) {
                    return back()->withInput()->withErrors(['duplicate' => 'Customer dengan nama dan nomor telepon sudah terdaftar.']);
                }
            }
            throw $e;
        }

        ActivityLog::record('update_customer', 'Memperbarui data customer', $customer, $old, $customer->getChanges());

        return redirect()->route('customers.show', $customer)->with('success', 'Data customer berhasil diperbarui!');
    }

    public function toggleBlacklist(Request $request, Customer $customer)
    {
        if (!Auth::user()->isSuperAdmin() && $customer->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Anda tidak memiliki akses ke data customer ini.');
        }

        $customer->update([
            'is_blacklisted'   => !$customer->is_blacklisted,
            'blacklist_reason' => $request->reason,
        ]);

        $msg = $customer->is_blacklisted
            ? 'Customer berhasil di-blacklist.'
            : 'Customer berhasil dihapus dari blacklist.';

        return back()->with('success', $msg);
    }

    public function destroy(Customer $customer)
    {
        // Super admin can delete any customer. Admin toko can delete only customers in their branch.
        if (Auth::user()->isSuperAdmin()) {
            // allowed
        } elseif (Auth::user()->isAdminToko() && $customer->branch_id === Auth::user()->branch_id) {
            // allowed
        } else {
            abort(403);
        }

        // Don't allow deletion if customer has active/ongoing rentals
        $hasActive = $customer->rentals()
            ->whereIn('rental_status', [
                'waiting',
                'active',
                'overdue'
            ])
            ->exists();
        if ($hasActive) {
            return back()->withErrors(['delete' => 'Customer memiliki transaksi aktif; hapus tidak diperbolehkan.']);
        }

        // Record activity and perform soft-delete (Customer model uses SoftDeletes)
        ActivityLog::record('delete_customer', 'Menghapus customer', $customer, $customer->getOriginal(), null);

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer berhasil dihapus.');
    }

    /**
     * Halaman arsip: customer yang di-blacklist ATAU yang sudah dihapus
     * sementara (soft-deleted, belum permanen). Dua tab dalam satu halaman.
     */
    public function archive(Request $request)
    {
        $user = Auth::user();

        $blacklisted = Customer::where('is_blacklisted', true)
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                   ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->latest('updated_at')
            ->paginate(10, ['*'], 'blacklist_page')
            ->withQueryString();

        $trashed = Customer::onlyTrashed()
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                   ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->latest('deleted_at')
            ->paginate(10, ['*'], 'trashed_page')
            ->withQueryString();

        return view('customers.archive', compact('blacklisted', 'trashed'));
    }

    /**
     * Pulihkan customer yang sudah di-soft-delete (belum permanen).
     */
    public function restore($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);

        if (!Auth::user()->isSuperAdmin() && $customer->branch_id !== Auth::user()->branch_id) {
            abort(403, 'Anda tidak memiliki akses ke data customer ini.');
        }

        $customer->restore();

        ActivityLog::record('restore_customer', 'Memulihkan customer dari sampah', $customer, null, $customer->toArray());

        return back()->with('success', "Customer \"{$customer->name}\" berhasil dipulihkan.");
    }

    /**
     * Permanently delete a customer and clean storage - only super_admin.
     *
     * FIX (root cause bug "Attempt to read property photo_url on null"):
     * Guard sebelumnya memakai `$customer->rentals()->exists()`, yang
     * SECARA DEFAULT tidak menghitung rental yang sudah soft-deleted
     * (trait SoftDeletes otomatis menambahkan whereNull('deleted_at')).
     * Akibatnya customer yang seluruh rental-nya sudah di-soft-delete
     * lolos guard ini dan ter-force-delete, padahal row rental (trashed)
     * masih ada di DB dengan customer_id yang sekarang menunjuk ke
     * customer yang sudah tidak ada → orphan record.
     *
     * FIX: tambahkan withTrashed() supaya SEMUA rental (termasuk yang
     * sudah soft-deleted) ikut dihitung dalam pengecekan ini.
     */
    public function forceDestroy(Request $request, Customer $customer)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        // Require explicit reason/confirmation for permanent deletion
        $data = $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        // Prevent accidental permanent delete if ANY rental record exists,
        // termasuk yang sudah soft-deleted (trashed).
        $hasRentals = $customer->rentals()->withTrashed()->exists();
        if ($hasRentals) {
            return back()->withErrors(['force_delete' => 'Customer memiliki riwayat transaksi (termasuk yang sudah dihapus). Hapus permanen tidak diperbolehkan.']);
        }

        DB::transaction(function () use ($customer, $data) {
            // Delete stored files if present
            if ($customer->photo) {
                try { Storage::disk('public')->delete($customer->photo); } catch (\Throwable $e) {}
            }
            if ($customer->id_photo) {
                try { Storage::disk('public')->delete($customer->id_photo); } catch (\Throwable $e) {}
            }

            // Record activity including the supplied reason for audit
            ActivityLog::record(
                'delete_customer_force',
                'Menghapus customer secara permanen. Alasan: ' . substr($data['reason'], 0, 250),
                $customer,
                $customer->getOriginal(),
                ['force_reason' => $data['reason']]
            );

            $customer->forceDelete();
        });

        return redirect()->route('customers.index')->with('success', 'Customer dihapus permanen dan file dibersihkan.');
    }

    public function checkDuplicate(Request $request)
    {
        $duplicate = Customer::findDuplicate(
            $request->name ?? '',
            $request->phone ?? ''
        );

        if (!$duplicate || $duplicate->id === (int) ($request->exclude_id ?? 0)) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'id' => $duplicate->id,
            'name' => $duplicate->name,
            'phone' => $duplicate->phone,
            'url' => route('customers.show', $duplicate),
        ]);
    }

    public function search(Request $request)
    {
        $customers = Customer::where('branch_id', Auth::user()->branch_id)
            ->where('is_blacklisted', false)
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('phone', 'like', "%{$request->q}%");
            })
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'phone'    => $c->phone,
                'photo'    => $c->photo_url,
                'nik'      => $c->id_number,
                'id_photo' => $c->id_photo ? asset('storage/' . $c->id_photo) : null,
                'id_photo_type' => $c->id_photo_type,
                'notes'    => $c->notes,
            ]);

        return response()->json($customers);
    }
}