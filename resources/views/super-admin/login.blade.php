<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MonsieurJas</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['"Playfair Display"', 'Georgia', 'serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        navy:  { DEFAULT:'#1A237E', light:'#3949AB', dark:'#0D1B47' },
                        purple: { DEFAULT:'#7B1FA2', light:'#AB47BC', dark:'#4A148C' },
                        gold:  { lt:'#FFE082', DEFAULT:'#FFC107', dk:'#FF8F00' },
                        cream: { DEFAULT:'#F5F7FF', soft:'#E8EAF6', sand:'#C5CAE9' },
                    },
                    boxShadow: {
                        'lux': '0 18px 60px rgba(26, 35, 126, 0.18)',
                        'btn': '0 6px 22px rgba(123, 31, 162, 0.32)',
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes spin { to { transform: rotate(360deg); } }

        .float-anim { animation: float 5s ease-in-out infinite; }
        .fade-up { animation: fadeUp 0.5s ease both; }
        .fade-up-1 { animation: fadeUp 0.5s 0.08s ease both; }
        .fade-up-2 { animation: fadeUp 0.5s 0.16s ease both; }
        .fade-up-3 { animation: fadeUp 0.5s 0.24s ease both; }
        .spin-anim { animation: spin 1s linear infinite; }

        .ornament {
            height: 2px; width: 44px;
            background: linear-gradient(90deg, transparent, #FFC107, transparent);
        }

        .bg-lux {
            background-image:
                radial-gradient(circle at 10% 15%, rgba(123, 31, 162, 0.10) 0%, transparent 40%),
                radial-gradient(circle at 90% 85%, rgba(26, 35, 126, 0.08) 0%, transparent 45%),
                linear-gradient(135deg, #F5F7FF 0%, #E8EAF6 100%);
        }

        .input-lux {
            transition: all 0.2s ease;
            border: 1.5px solid #C5CAE9;
            background-color: #F5F7FF;
        }
        .input-lux:focus {
            border-color: #7B1FA2;
            box-shadow: 0 0 0 4px rgba(123, 31, 162, 0.12);
            background-color: #ffffff;
            outline: none;
        }

        .card-glass {
            background-color: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
        }
    </style>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-lux flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-md">
        <!-- Decorative floating jacket icon -->
        <div class="text-center mb-6 fade-up">
            <div class="inline-flex float-anim">
                <svg width="120" height="120" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5,20 L5,72 L38,72 L38,38 L5,20 Z"        fill="#1A237E"/>
                    <path d="M75,20 L75,72 L42,72 L42,38 L75,20 Z"       fill="#1A237E"/>
                    <path d="M5,20 L30,16 L38,38 L5,20 Z"                fill="#3949AB"/>
                    <path d="M75,20 L50,16 L42,38 L75,20 Z"              fill="#3949AB"/>
                    <path d="M30,16 L50,16 L42,38 L42,72 L38,72 L38,38 Z" fill="#F5F7FF"/>
                    <path d="M38,38 L40,33 L42,38 L41,44 L39,44 Z"       fill="#FFC107"/>
                    <path d="M39,44 L37,64 L40,68 L43,64 L41,44 Z"       fill="#FFC107"/>
                    <circle cx="40" cy="54" r="2.2" fill="#FFC107"/>
                </svg>
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-7 fade-up-1">
            <h1 class="font-playfair text-3xl font-bold text-navy-dark mb-1">MonsieurJas</h1>
            <div class="ornament mb-3"></div>
            <p class="text-sm text-slate-500 font-light tracking-wide">Premium Jas Rental Management</p>
        </div>

        <!-- Card -->
        <div class="card-glass rounded-2xl p-8 shadow-lux fade-up-2">
            <!-- Errors -->
            @if ($errors->any())
                <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('superadmin.login.post') }}" x-data="{ loading: false, show: false }" @submit="loading = true">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-navy-light mb-1.5">Email</label>
                    <input type="email" name="email" class="input-lux w-full px-4 py-3 rounded-xl text-navy-dark placeholder:text-slate-400" placeholder="superadmin@jasrental.id" required autofocus>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-navy-light mb-1.5">Password</label>
                    <div class="relative">
                        <input :type="show ? 'text' : 'password'" name="password" class="input-lux w-full px-4 py-3 pr-11 rounded-xl text-navy-dark placeholder:text-slate-400" placeholder="••••••••" required>
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy-dark transition-colors">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-purple hover:bg-purple-dark text-cream-DEFAULT font-semibold py-3.5 rounded-xl transition-all shadow-btn hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <template x-if="!loading">
                        <span>Masuk ke Super Admin</span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 spin-anim" fill="none" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"/>
                                <path fill="currentColor" opacity="0.75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Memproses...
                        </span>
                    </template>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-slate-400 mt-6 fade-up-3">
            &copy; {{ date('Y') }} MonsieurJas &middot; Premium Jas Rental Management
        </p>
    </div>
</body>
</html>
