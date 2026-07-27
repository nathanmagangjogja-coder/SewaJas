<header class="h-[76px] lg:h-[88px] border-b flex items-center justify-between px-5 lg:px-8 gap-4 lg:gap-6 sticky top-0 z-40 transition-all duration-300"
        style="background: var(--bg-navbar); border-color: var(--border); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); box-shadow: 0 1px 8px rgba(16,24,40,0.06);">
    <div class="flex items-center gap-4 lg:gap-6 flex-1 min-w-0">
        <button @click="sidebarMobileOpen = !sidebarMobileOpen" class="lg:hidden p-3 rounded-2xl flex-shrink-0 transition-all duration-300 hover:bg-[var(--surf-primary)] hover:scale-105" style="background: var(--bg-soft)">
            <i data-lucide="menu" class="w-5 h-5" style="color: var(--text-soft)"></i>
        </button>
        
        <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:flex p-3 rounded-2xl flex-shrink-0 transition-all duration-300 hover:bg-[var(--surf-primary)] hover:scale-105" style="background: var(--bg-soft)">
            <i data-lucide="panel-left" class="w-5 h-5" style="color: var(--text-soft)"></i>
        </button>
        
        <div class="min-w-0">
            <h1 class="font-playfair text-xl lg:text-2xl font-semibold truncate" style="color: var(--text-dark)">
                @yield('page-title', 'Dashboard')
            </h1>
            @hasSection('subtitle')
            <p class="text-xs lg:text-sm mt-1 truncate hidden sm:block" style="color: var(--text-soft)">@yield('subtitle')</p>
            @endif
        </div>
    </div>
    
    <div class="flex items-center gap-3 lg:gap-4 flex-shrink-0">
        <!-- Search Bar -->
        <div class="hidden md:flex items-center gap-3 px-4 py-3 rounded-2xl border w-64 lg:w-80 transition-all duration-300" style="background: var(--bg-input); border-color: var(--border);" x-data="{ focused: false }" :class="focused ? 'border-[var(--primary)] shadow-[0_0_0_3px_var(--primary-glow)]' : ''">
            <i data-lucide="search" class="w-5 h-5 flex-shrink-0" style="color: var(--text-soft)"></i>
            <input type="text" placeholder="Cari invoice, customer..." class="flex-1 text-sm bg-transparent outline-none" style="color: var(--text-dark);" @focus="focused = true" @blur="focused = false" @keyup.enter="window.location.href = '/rentals?search=' + $event.target.value">
        </div>
        
        <!-- Overdue Alert -->
        @php $overdueCount = \App\Models\Rental::where('rental_status', 'overdue')->when(!auth()->user()->isSuperAdmin(), fn($q)=>$q->where('branch_id', auth()->user()->branch_id))->count(); @endphp
        @if($overdueCount > 0)
        <a href="{{ route('rentals.index', ['status' => 'overdue']) }}" class="hidden lg:flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-medium transition-all flex-shrink-0 animate-soft-pulse hover:scale-105" style="background: var(--surf-danger); color: var(--danger); border: 1px solid rgba(242,139,139,0.2);">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            <span>{{ $overdueCount }} Telat</span>
        </a>
        @endif
        
        
        <!-- Notification Bell -->
        <x-notification-bell />
        
        <!-- User Avatar -->
        <div class="relative flex-shrink-0" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-3 rounded-2xl transition-all duration-300 hover:shadow-lg hover:scale-105">
                <div class="w-11 h-11 lg:w-12 lg:h-12 rounded-2xl flex items-center justify-center flex-shrink-0 font-bold text-sm text-white" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 0 0 2px var(--primary-tint);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                
                <div class="hidden lg:block text-left">
                    <p class="text-sm font-semibold leading-tight" style="color: var(--text-dark)">
                        {{ Str::limit(auth()->user()->name, 16) }}
                    </p>
                    <p class="text-xs leading-tight" style="color: var(--text-soft)">
                        {{ auth()->user()->role }}
                    </p>
                </div>
                
                <i data-lucide="chevron-down" class="w-4 h-4 hidden lg:block" style="color: var(--text-soft)"></i>
            </button>
            
            <!-- User Dropdown -->
            <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="absolute right-0 mt-3 w-72 rounded-2xl shadow-xl border overflow-hidden z-50" style="background: var(--card-elevated); border-color: var(--border);">
                <div class="p-5 border-b" style="border-color: var(--border);">
                    <p class="text-sm font-semibold" style="color: var(--text-dark)">{{ auth()->user()->name }}</p>
                    <p class="text-xs mt-1" style="color: var(--text-soft)">{{ auth()->user()->email }}</p>
                </div>
                <div class="p-2">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm transition-all duration-200 hover:bg-[var(--surf-primary)] hover:translate-x-1" style="color: var(--text-dark)">
                        <i data-lucide="user" class="w-5 h-5" style="color: var(--text-soft)"></i>
                        Profil Saya
                    </a>
                    
                    @can('manage-branches')
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm transition-all duration-200 hover:bg-[var(--surf-primary)] hover:translate-x-1" style="color: var(--text-dark)">
                        <i data-lucide="settings" class="w-5 h-5" style="color: var(--text-soft)"></i>
                        Pengaturan
                    </a>
                    @endcan
                    
                    <div class="border-t my-2" style="border-color: var(--border)"></div>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm w-full text-left transition-all duration-200 hover:bg-[var(--surf-danger)] hover:translate-x-1" style="color: var(--danger)">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>