<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Daftar role tersedia (tanpa Spatie)
    private array $roles = ['super_admin', 'admin_toko', 'sales'];

    public function index(Request $request)
    {
        $users = User::with('branch')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role,   fn($q) => $q->where('role', $request->role))       // ← kolom langsung
            ->when($request->branch, fn($q) => $q->where('branch_id', $request->branch))
            ->latest()
            ->paginate(15)->withQueryString();

        $branches = Branch::where('is_active', true)->get();
        $roles    = $this->roles;

        // FIX: hitung akun non-super_admin yang belum punya cabang, supaya
        // admin diberi tahu sebelum sales/admin_toko yang bersangkutan
        // komplain tidak bisa login (diblokir oleh EnsureBranchScope).
        $brokenUsersCount = User::whereNull('branch_id')
            ->where('role', '!=', User::ROLE_SUPER_ADMIN)
            ->count();

        return view('users.index', compact('users', 'branches', 'roles', 'brokenUsersCount'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $roles    = $this->roles;
        return view('users.create', compact('branches', 'roles'));
    }

public function store(Request $request)
{
    $data = $request->validate([
        'name'      => 'required|string|max:150',
        'email'     => 'required|email|unique:users,email',
        'password'  => 'required|string|min:8|confirmed',
        'phone'     => 'nullable|string|max:20',
        // FIX: branch_id WAJIB diisi untuk role admin_toko & sales.
        // Tanpa ini, user dengan role tsb bisa tersimpan dengan branch_id NULL,
        // dan akan diblokir (403) oleh EnsureBranchScope saat login karena
        // dianggap "belum dikaitkan dengan cabang manapun".
        'branch_id' => 'required_if:role,admin_toko,sales|nullable|exists:branches,id',
        'role'      => 'required|in:super_admin,admin_toko,sales',
        'avatar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // ← photo → avatar
    ], [
        'branch_id.required_if' => 'Cabang wajib dipilih untuk role Admin Toko dan Sales.',
    ]);

    $avatarPath = null;
    if ($request->hasFile('avatar')) { // ← photo → avatar
        $avatarPath = $request->file('avatar')->store('avatars', 'public'); // ← photo → avatar
    }

    $user = User::create([
        'name'      => $data['name'],
        'email'     => $data['email'],
        'password'  => Hash::make($data['password']),
        'phone'     => $data['phone'] ?? null,
        'branch_id' => $data['role'] === 'super_admin' ? null : $data['branch_id'],
        'role'      => $data['role'],
        'avatar'    => $avatarPath, // ← photo → avatar
        'is_active' => true,
    ]);

    return redirect()->route('users.index')
        ->with('success', "User {$user->name} berhasil ditambahkan!");
}
    public function edit(User $user)
    {
        $branches = Branch::where('is_active', true)->get();
        $roles    = $this->roles;
        return view('users.edit', compact('user', 'branches', 'roles'));
    }

public function update(Request $request, User $user)
{
    $data = $request->validate([
        'name'      => 'required|string|max:150',
        'email'     => "required|email|unique:users,email,{$user->id}",
        'password'  => 'nullable|string|min:8|confirmed',
        'phone'     => 'nullable|string|max:20',
        // FIX: sama seperti store() — branch_id wajib untuk admin_toko & sales
        'branch_id' => 'required_if:role,admin_toko,sales|nullable|exists:branches,id',
        'role'      => 'required|in:super_admin,admin_toko,sales',
        'avatar'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // ← tambah ini
    ], [
        'branch_id.required_if' => 'Cabang wajib dipilih untuk role Admin Toko dan Sales.',
    ]);

    // ← Ganti foto lama
    $avatarPath = $user->avatar;
    if ($request->hasFile('avatar')) {
        if ($user->avatar) {
            \Storage::disk('public')->delete($user->avatar); // ← hapus foto lama
        }
        $avatarPath = $request->file('avatar')->store('avatars', 'public');
    }

    $user->update([
        'name'      => $data['name'],
        'email'     => $data['email'],
        'phone'     => $data['phone'] ?? null,
        'branch_id' => $data['role'] === 'super_admin' ? null : $data['branch_id'],
        'role'      => $data['role'],
        'avatar'    => $avatarPath, // ← tambah ini
        'password'  => !empty($data['password']) ? Hash::make($data['password']) : $user->password,
    ]);

    return redirect()->route('users.index')
        ->with('success', "User {$user->name} berhasil diperbarui!");
}

    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "User {$user->name} berhasil {$status}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $user->delete();
        return back()->with('success', "User {$user->name} berhasil dihapus.");
    }
}