<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login — SewaJas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif:  ['"Playfair Display"', 'Georgia', 'serif'],
                        sans:   ['"Inter"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        navy:  { DEFAULT:'#1A237E', light:'#3949AB', dark:'#0D1B47' },
                        purple: { DEFAULT:'#7B1FA2', light:'#AB47BC', dark:'#4A148C' },
                        gold:  { lt:'#FFE082', DEFAULT:'#FFC107', dk:'#FF8F00' },
                        cream: { DEFAULT:'#F5F7FF', soft:'#E8EAF6', sand:'#C5CAE9' },
                    },
                    boxShadow: {
                        'card':    '0 0 0 8px rgba(123,31,162,.06), 0 24px 60px rgba(26,35,126,.15)',
                        'card-sm': '0 4px 24px rgba(26,35,126,.10)',
                        'btn':     '0 4px 18px rgba(123,31,162,.35)',
                        'btn-h':   '0 8px 28px rgba(123,31,162,.45)',
                    },
                    screens: {
                        'xs': '400px',
                    }
                }
            }
        }
    </script>

    <style>
        /* ── base reset ── */
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { font-family: 'Inter', system-ui, sans-serif; }

        /* ── luxury dot pattern ── */
        .dot-pattern {
            background-image: radial-gradient(circle, rgba(123,31,162,.12) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* ── diagonal stripe ── */
        .stripe-pattern {
            background-image: repeating-linear-gradient(
                -45deg,
                transparent,
                transparent 12px,
                rgba(255,193,7,.05) 12px,
                rgba(255,193,7,.05) 24px
            );
        }

        /* ── glowing orbs ── */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
        }

        /* ── input focus ring ── */
        .inp {
            display: block;
            width: 100%;
            padding: .72rem 1rem .72rem 2.6rem;
            background: #F5F7FF;
            border: 1.5px solid #C5CAE9;
            border-radius: .75rem;
            font-family: 'Inter', sans-serif;
            font-size: .875rem;
            color: #0D1B47;
            outline: none;
            transition: border-color .18s, box-shadow .18s, background .18s;
            -webkit-appearance: none;
        }
        .inp:focus {
            border-color: #7B1FA2;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(123,31,162,.14);
        }
        .inp::placeholder { color: #9FA8DA; }

        /* ── animations ── */
        @keyframes float {
            0%,100% { transform: translateY(0) rotate(-.6deg); }
            50%      { transform: translateY(-10px) rotate(.6deg); }
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(16px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes spin { to { transform:rotate(360deg); } }

        .float-anim { animation: float 5s ease-in-out infinite; }
        .fade-up    { animation: fadeUp .5s ease both; }
        .fade-up-1  { animation: fadeUp .5s .1s ease both; }
        .fade-up-2  { animation: fadeUp .5s .2s ease both; }
        .fade-up-3  { animation: fadeUp .5s .3s ease both; }
        .spin-anim  { animation: spin 1s linear infinite; }

        /* ── particles drifting upward ── */
        @keyframes particleDrift {
            0%   { transform: translateY(0) translateX(0) scale(.6); opacity: 0; }
            12%  { opacity: .7; }
            88%  { opacity: .5; }
            100% { transform: translateY(-420px) translateX(var(--drift,18px)) scale(1); opacity: 0; }
        }
        .particle {
            position: absolute;
            bottom: -20px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,224,130,.9), rgba(255,193,7,.15) 70%);
            animation: particleDrift linear infinite;
            pointer-events: none;
        }

        /* ── slow-roaming gradient blobs (left panel) ── */
        @keyframes roam {
            0%,100% { transform: translate(0,0) scale(1); }
            33%     { transform: translate(30px,-24px) scale(1.12); }
            66%     { transform: translate(-20px,18px) scale(.94); }
        }
        .orb-roam { animation: roam 14s ease-in-out infinite; }

        /* ── shimmering gold text ── */
        @keyframes shimmerText {
            0%   { background-position: -200% center; }
            100% { background-position: 200% center; }
        }
        .shimmer-text {
            background: linear-gradient(100deg, #FFC107 20%, #FFF8E1 40%, #FFE082 50%, #FFC107 80%);
            background-size: 250% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            animation: shimmerText 3.5s linear infinite;
        }

        /* ── breathing card glow ── */
        @keyframes cardBreathe {
            0%,100% { box-shadow: 0 0 0 8px rgba(123,31,162,.06), 0 24px 60px rgba(26,35,126,.15); }
            50%     { box-shadow: 0 0 0 12px rgba(123,31,162,.11), 0 30px 74px rgba(123,31,162,.24); }
        }
        .card-breathe { animation: cardBreathe 5s ease-in-out infinite; }

        /* ── card entrance (settle-in with slight tilt) ── */
        @keyframes settleIn {
            0%   { opacity: 0; transform: translateY(28px) rotateX(6deg) scale(.97); }
            100% { opacity: 1; transform: translateY(0) rotateX(0) scale(1); }
        }
        .settle-in { animation: settleIn .7s cubic-bezier(.22,1,.36,1) both; transform-style: preserve-3d; }

        /* mouse-reactive tilt (updated via JS custom props) */
        .tilt-card {
            transform: perspective(1200px) rotateX(var(--ry,0deg)) rotateY(var(--rx,0deg));
            transition: transform .25s ease-out;
            will-change: transform;
        }

        /* cursor spotlight on right panel */
        .spotlight {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(500px circle at var(--sx,50%) var(--sy,20%), rgba(123,31,162,.10), transparent 42%);
            transition: background .15s ease-out;
        }

        /* logo pulse ring */
        @keyframes pulseRing {
            0%   { transform: scale(.9); opacity: .55; }
            70%  { transform: scale(1.35); opacity: 0; }
            100% { opacity: 0; }
        }
        .pulse-ring {
            position: absolute; inset: -14px; border-radius: 50%;
            border: 1.5px solid rgba(255,193,7,.5);
            animation: pulseRing 3s ease-out infinite;
        }

        /* input group micro-interaction */
        .inp-group { position: relative; }
        .inp-icon { transition: color .2s ease, transform .2s ease; }
        .inp:focus + .inp-underline,
        .inp-group:focus-within .inp-underline { transform: scaleX(1); }
        .inp-underline {
            position: absolute; left: 0; right: 0; bottom: -1px; height: 2px;
            background: linear-gradient(90deg, #7B1FA2, #FFC107);
            transform: scaleX(0);
            transform-origin: left;
            border-radius: 2px;
            transition: transform .3s ease;
        }

        /* dot pattern slow drift */
        @keyframes dotDrift {
            0%   { background-position: 0 0; }
            100% { background-position: 40px 40px; }
        }
        .dot-pattern-anim { animation: dotDrift 12s linear infinite; }

        /* demo card icon bounce */
        .demo-btn:hover .demo-icon { transform: translateY(-2px) scale(1.08); }
        .demo-icon { transition: transform .25s ease; }

        /* ripple */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.55);
            transform: scale(0);
            animation: rippleAnim .6s ease-out;
            pointer-events: none;
        }
        @keyframes rippleAnim {
            to { transform: scale(3); opacity: 0; }
        }

        /* ── left panel gradient overlay ── */
        .lp-overlay {
            background: linear-gradient(
                160deg,
                rgba(57,73,171,.15) 0%,
                rgba(13,27,71,.60)  60%,
                rgba(13,27,71,.90)  100%
            );
        }

        /* ── circle ring ── */
        .ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255,193,7,.20);
            pointer-events: none;
        }

        /* ── gold divider line ── */
        .gold-line {
            width: 44px;
            height: 1.5px;
            background: linear-gradient(90deg, transparent, #FFC107, transparent);
        }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="min-h-screen flex overflow-x-hidden bg-cream-DEFAULT" x-data="loginPage()" x-init="particles = Array.from({length:14}, (_,i) => ({ left: Math.random()*100, size: 3+Math.random()*5, delay: Math.random()*10, dur: 8+Math.random()*10, drift: (Math.random()*40-20)+'px' }))">

    <!-- ════════════════════════════════════════════════════
         MOBILE  HEADER  (visible xs→md, hidden lg+)
         Shows logo + brand name in a compact bar at top
    ════════════════════════════════════════════════════ -->
    <div class="lg:hidden fixed top-0 inset-x-0 z-30 flex items-center gap-3 px-4 py-3 bg-navy-dark border-b border-gold/20 shadow-md">
        <!-- mini jacket icon -->
        <svg width="32" height="32" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
            <path d="M5,20 L5,72 L38,72 L38,38 L5,20 Z"        fill="#1A237E"/>
            <path d="M75,20 L75,72 L42,72 L42,38 L75,20 Z"       fill="#1A237E"/>
            <path d="M5,20 L30,16 L38,38 L5,20 Z"                fill="#3949AB"/>
            <path d="M75,20 L50,16 L42,38 L75,20 Z"              fill="#3949AB"/>
            <path d="M30,16 L50,16 L42,38 L42,72 L38,72 L38,38 Z" fill="#F5F7FF"/>
            <path d="M38,38 L40,33 L42,38 L41,44 L39,44 Z"       fill="#FFC107"/>
            <path d="M39,44 L37,64 L40,68 L43,64 L41,44 Z"       fill="#FFC107"/>
            <circle cx="40" cy="54" r="2.2" fill="#FFC107"/>
        </svg>
        <div>
            <span class="font-serif text-lg font-bold text-cream-DEFAULT leading-none">
                Monsieur<span class="text-gold italic">Jas</span>
            </span>
            <p class="text-gold/50 text-[10px] tracking-widest uppercase leading-none mt-0.5">Premium Jas Rental</p>
        </div>
    </div>


    <!-- ════════════════════════════════════════════════════
         MAIN LAYOUT  — side by side on lg+
    ════════════════════════════════════════════════════ -->
    <div class="flex flex-col lg:flex-row w-full min-h-screen">

        <!-- ───────────────────────────────────────────────
             LEFT  PANEL  (branding)
             hidden on mobile, visible lg+
        ──────────────────────────────────────────────── -->
        <aside class="hidden lg:flex lg:w-5/12 xl:w-[42%] flex-col relative overflow-hidden bg-navy-dark flex-shrink-0">

            <!-- background image layer -->
            <div class="absolute inset-0 stripe-pattern opacity-60"></div>

            <!-- gradient overlay -->
            <div class="lp-overlay absolute inset-0"></div>

            <!-- decorative rings -->
            <div class="ring" style="width:320px;height:320px;top:-100px;left:-100px;"></div>
            <div class="ring" style="width:200px;height:200px;bottom:-60px;right:-60px;"></div>
            <div class="ring" style="width:100px;height:100px;top:40%;left:5%;"></div>

            <!-- gold glow orbs (slow roaming) -->
            <div class="orb orb-roam w-48 h-48 bg-gold/10 top-10 left-10"></div>
            <div class="orb orb-roam w-32 h-32 bg-gold/8 bottom-20 right-10" style="animation-delay:-6s;"></div>
            <div class="orb orb-roam w-24 h-24 bg-purple-light/20 top-1/2 right-1/4" style="animation-delay:-3s;"></div>

            <!-- drifting particles -->
            <template x-for="(p, i) in particles" :key="i">
                <span class="particle"
                      :style="`left:${p.left}%; width:${p.size}px; height:${p.size}px; animation-delay:${p.delay}s; animation-duration:${p.dur}s; --drift:${p.drift};`"></span>
            </template>

            <!-- vertical accent stripes -->
            <div class="absolute left-[18%] top-0 bottom-0 w-px bg-gradient-to-b from-transparent via-gold/25 to-transparent"></div>
            <div class="absolute right-[22%] top-0 bottom-0 w-px bg-gradient-to-b from-transparent via-gold/15 to-transparent"></div>

            <!-- ── CONTENT ── -->
            <div class="relative z-10 flex flex-col items-center justify-center flex-1 px-8 xl:px-12 py-12 text-center">

                <!-- Jacket SVG logo -->
                <div class="float-anim fade-up mb-6 relative">
                    <span class="pulse-ring"></span>
                    <span class="pulse-ring" style="animation-delay:1.5s;"></span>
                    <svg width="150" height="165" viewBox="0 0 80 88" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- drop shadow -->
                        <ellipse cx="40" cy="85" rx="28" ry="4.5" fill="rgba(0,0,0,0.25)"/>

                        <!-- ── LEFT JACKET BODY ── -->
                        <!-- Full left panel: shoulder→hem→center→neckline -->
                        <path d="M5,20 L3,78 L38,78 L38,40 L5,20 Z" fill="#0D1B47"/>
                        <!-- subtle inner shadow -->
                        <path d="M5,20 L38,40 L38,78 L30,78 L5,20 Z" fill="rgba(0,0,0,0.15)"/>

                        <!-- ── RIGHT JACKET BODY ── -->
                        <path d="M75,20 L77,78 L42,78 L42,40 L75,20 Z" fill="#0D1B47"/>
                        <path d="M75,20 L42,40 L42,78 L50,78 L75,20 Z" fill="rgba(0,0,0,0.15)"/>

                        <!-- ── LEFT LAPEL (lighter fold) ── -->
                        <!-- triangle: shoulder-pt → collar-inner → neckline -->
                        <path d="M5,20 L30,16 L38,40 Z" fill="#3949AB"/>
                        <!-- lapel crease line -->
                        <line x1="5" y1="20" x2="38" y2="40" stroke="rgba(255,193,7,0.2)" stroke-width="0.7"/>

                        <!-- ── RIGHT LAPEL ── -->
                        <path d="M75,20 L50,16 L42,40 Z" fill="#3949AB"/>
                        <line x1="75" y1="20" x2="42" y2="40" stroke="rgba(255,193,7,0.2)" stroke-width="0.7"/>

                        <!-- ── WHITE SHIRT FRONT ── -->
                        <path d="M30,16 L50,16 L42,40 L42,78 L38,78 L38,40 Z" fill="#F5F7FF"/>
                        <!-- shirt shadow -->
                        <path d="M38,40 L38,78 L40,78 L40,40 Z" fill="rgba(0,0,0,0.06)"/>

                        <!-- ── LEFT COLLAR POINT ── -->
                        <path d="M38,40 L33,26 L40,21 Z" fill="#F5F7FF"/>
                        <!-- ── RIGHT COLLAR POINT ── -->
                        <path d="M42,40 L47,26 L40,21 Z" fill="#F5F7FF"/>
                        <!-- collar shadow center -->
                        <path d="M33,26 L40,21 L47,26 L40,30 Z" fill="#E8EAF6"/>

                        <!-- ── NOTCH (dark V where lapel meets collar) ── -->
                        <path d="M30,16 L35,28 L38,40 Z" fill="#0D1B47" opacity="0.75"/>
                        <path d="M50,16 L45,28 L42,40 Z" fill="#0D1B47" opacity="0.75"/>

                        <!-- ── TIE KNOT ── -->
                        <path d="M38,40 L40,35 L42,40 L41,46 L39,46 Z" fill="#FFC107"/>
                        <!-- ── TIE BODY ── -->
                        <path d="M39,46 L37,70 L40,75 L43.5,70 L41,46 Z" fill="#FFC107"/>
                        <!-- tie highlight -->
                        <line x1="40" y1="37" x2="40" y2="72" stroke="rgba(255,255,255,0.15)" stroke-width="0.8" stroke-linecap="round"/>
                        <!-- tie fold shadow -->
                        <path d="M40,46 L41,46 L43.5,70 L40,75 Z" fill="rgba(0,0,0,0.18)"/>

                        <!-- ── BUTTONS ── -->
                        <circle cx="40" cy="56" r="2.5" fill="#FFC107"/>
                        <circle cx="40" cy="56" r="1"   fill="rgba(0,0,0,0.25)"/>
                        <circle cx="40" cy="66" r="2.5" fill="#FFC107"/>
                        <circle cx="40" cy="66" r="1"   fill="rgba(0,0,0,0.25)"/>

                        <!-- ── POCKET SQUARE (right chest) ── -->
                        <rect x="52" y="42" width="13" height="1.8" rx="0.9" fill="#FFC107" opacity="0.7"/>
                        <path d="M53,42 L56.5,36 L60,41.5 L63.5,36 L65,42"
                              stroke="#FFE082" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0.9"/>

                        <!-- ── SHOULDER SEAM HINTS ── -->
                        <path d="M5,20 C14,14 24,15 30,16" stroke="rgba(255,193,7,0.2)" stroke-width="0.7" fill="none"/>
                        <path d="M75,20 C66,14 56,15 50,16" stroke="rgba(255,193,7,0.2)" stroke-width="0.7" fill="none"/>
                    </svg>
                </div>

                <!-- Brand name -->
                <div class="fade-up-1">
                    <h1 class="font-serif text-4xl xl:text-5xl font-bold text-cream-DEFAULT tracking-wide leading-none">
                        Monsieur<span class="shimmer-text italic">Jas</span>
                    </h1>
                    <div class="flex items-center justify-center gap-3 mt-3">
                        <div class="gold-line"></div>
                        <p class="text-gold/55 text-[11px] tracking-[.22em] uppercase whitespace-nowrap">Premium Jas Rental</p>
                        <div class="gold-line"></div>
                    </div>
                </div>

                <!-- Feature pills -->
                <div class="flex flex-wrap gap-2 justify-center mt-8 fade-up-2">
                    @foreach(['Jas Pria','Gaun Wanita','Aksesoris','Seragam'] as $tag)
                    <span class="px-3.5 py-1.5 rounded-full border border-gold/25 text-gold/55 text-[10px] tracking-[.12em] uppercase">{{ $tag }}</span>
                    @endforeach
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-3 gap-4 mt-10 w-full max-w-xs fade-up-3">
                    @foreach([['500+','Koleksi'],['2K+','Pelanggan'],['5★','Rating']] as [$n,$l])
                    <div class="text-center">
                        <p class="font-serif text-2xl font-bold text-cream-DEFAULT">{{ $n }}</p>
                        <p class="text-gold/50 text-[10px] tracking-widest uppercase mt-0.5">{{ $l }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Bottom quote -->
            <div class="relative z-10 pb-6 px-8 text-center fade-up-3">
                <p class="font-serif italic text-sm text-gold/40 leading-relaxed">
                    "Tampil elegan di setiap momen<br>bersama koleksi terbaik kami."
                </p>
            </div>
        </aside>


        <!-- ───────────────────────────────────────────────
             RIGHT  PANEL  (login form)
        ──────────────────────────────────────────────── -->
        <main class="flex-1 flex items-center justify-center bg-cream-DEFAULT dot-pattern dot-pattern-anim px-4 xs:px-6 sm:px-8 pt-20 pb-6 lg:pt-6 lg:pb-6 min-h-screen lg:min-h-0 relative overflow-hidden"
              @mousemove="onCardMove($event)" @mouseleave="resetTilt()">

            <!-- cursor spotlight -->
            <div class="spotlight" :style="`--sx:${spot.x}px; --sy:${spot.y}px;`"></div>

            <!-- subtle corner triangle decoration -->
            <svg class="absolute bottom-0 right-0 opacity-[.06] pointer-events-none" width="200" height="200" viewBox="0 0 200 200" aria-hidden="true">
                <path d="M200 0 L200 200 L0 200 Z" fill="#7B1FA2"/>
                <path d="M200 60 L200 200 L60 200 Z" fill="#AB47BC"/>
            </svg>
            <svg class="absolute top-0 left-0 opacity-[.04] pointer-events-none" width="160" height="160" viewBox="0 0 160 160" aria-hidden="true">
                <path d="M0 160 L0 0 L160 0 Z" fill="#7B1FA2"/>
            </svg>

            <!-- glow blobs -->
            <div class="orb orb-roam w-64 h-64 bg-purple/6 -top-16 -right-16"></div>
            <div class="orb orb-roam w-48 h-48 bg-navy/5 -bottom-12 left-8" style="animation-delay:-5s;"></div>

            <!-- ══ FORM CARD ══ -->
            <div class="relative z-10 w-full max-w-[420px] settle-in tilt-card" :style="`--rx:${tilt.rx}deg; --ry:${tilt.ry}deg;`">

                <!-- Card -->
                <div class="bg-white rounded-2xl card-breathe border border-cream-sand/70 overflow-hidden">

                    <!-- gold top accent bar -->
                    <div class="h-1 w-full bg-gradient-to-r from-gold/30 via-gold to-gold/30"></div>

                    <div class="px-6 xs:px-8 pt-7 pb-8">

                        <!-- ── CARD HEADER ── -->
                        <div class="mb-6 fade-up-1">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="gold-line"></div>
                                <span class="text-gold text-[11px] tracking-[.18em] uppercase font-semibold whitespace-nowrap">
                                    Selamat Datang
                                </span>
                                <div class="gold-line"></div>
                            </div>
                            <h2 class="font-serif text-[1.85rem] xs:text-[2rem] font-bold text-navy-dark leading-tight">
                                Masuk ke<br><em class="text-purple not-italic font-bold">Dashboard</em>
                            </h2>
                            <p class="text-sm text-slate-400 mt-1.5">Kelola rental jas dan fashion premium Anda</p>
                        </div>

                        <!-- ── ALERTS ── -->
                        @if ($errors->any())
                        <div class="mb-5 flex gap-2.5 items-start bg-red-50 border border-red-200 rounded-xl p-3.5 text-sm text-red-600">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
                        </div>
                        @endif

                        @if (session('success'))
                        <div class="mb-5 bg-green-50 border border-green-200 rounded-xl p-3.5 text-sm text-green-700">
                            {{ session('success') }}
                        </div>
                        @endif

                        <!-- ── FORM ── -->
                        <form method="POST" action="{{ route('login.post') }}" @submit="loading = true" class="space-y-4 fade-up-2">
                            @csrf

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-[11px] font-semibold tracking-[.1em] uppercase text-navy-light mb-1.5">
                                    Email
                                </label>
                                <div class="relative inp-group">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-purple inp-icon"
                                          :class="emailFocus && 'text-gold-dk -translate-y-[calc(50%+1px)] scale-110'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </span>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="email@jasrental.id" autocomplete="email" required autofocus class="inp"
                                           @focus="emailFocus = true" @blur="emailFocus = false">
                                    <span class="inp-underline"></span>
                                </div>
                            </div>

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-[11px] font-semibold tracking-[.1em] uppercase text-navy-light mb-1.5">
                                    Password
                                </label>
                                <div class="relative inp-group">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none text-purple inp-icon"
                                          :class="passFocus && 'text-gold-dk -translate-y-[calc(50%+1px)] scale-110'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </span>
                                    <input :type="showPass ? 'text' : 'password'" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required class="inp pr-11"
                                           @focus="passFocus = true" @blur="passFocus = false">
                                    <button type="button" @click="showPass = !showPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy transition-colors p-1" aria-label="Toggle password">
                                        <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <svg x-show="showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                    <span class="inp-underline"></span>
                                </div>
                            </div>

                            <!-- Remember me -->
                            <div class="flex items-center gap-2 pt-0.5">
                                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 accent-purple rounded border-cream-sand cursor-pointer flex-shrink-0">
                                <label for="remember" class="text-sm text-slate-500 cursor-pointer select-none">
                                    Ingat saya
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" :disabled="loading" @click="spawnRipple($event)" class="relative w-full flex items-center justify-center gap-2 bg-purple text-cream-DEFAULT rounded-xl py-3.5 px-6 font-semibold text-sm tracking-wide shadow-btn transition-all duration-200 hover:bg-purple-dark hover:shadow-btn-h hover:-translate-y-0.5 active:translate-y-0 active:shadow-btn disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none overflow-hidden group">
                                <!-- shimmer overlay on hover -->
                                <span class="absolute inset-0 bg-gradient-to-r from-transparent via-gold/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 ease-in-out pointer-events-none"></span>
                                <!-- click ripples -->
                                <template x-for="r in ripples" :key="r.id">
                                    <span class="ripple" :style="`left:${r.x}px; top:${r.y}px; width:${r.size}px; height:${r.size}px;`"></span>
                                </template>

                                <template x-if="!loading">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                        </svg>
                                        Masuk ke Dashboard
                                    </span>
                                </template>
                                <template x-if="loading">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 spin-anim" fill="none" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"/>
                                            <path fill="currentColor" opacity="0.75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        Memproses...
                                    </span>
                                </template>
                            </button>
                        </form>

                        <!-- ── DEMO ACCOUNTS (local only) ── -->
                        @if (app()->environment('local'))
                        <div class="mt-5 fade-up-3">
                            <div class="flex items-center gap-2.5 mb-3">
                                <div class="flex-1 border-t border-dashed border-cream-sand"></div>
                                <span class="text-[11px] text-slate-400 whitespace-nowrap">🔑 Akun Demo</span>
                                <div class="flex-1 border-t border-dashed border-cream-sand"></div>
                            </div>

                            <!-- grid: 3 cols on sm+, 1 col on xs -->
                            <div class="grid grid-cols-1 xs:grid-cols-3 gap-2">
                                @foreach([
                                    ['Super Admin', 'superadmin@jasrental.id', 'M12 2l2.5 5.5L20 8l-4.5 4 1 6-4.5-3-4.5 3 1-6L3 8l5.5-.5z'],
                                    ['Admin Toko',  'admin.pusat@jasrental.id', 'M3 21h18M4 21V9l8-6 8 6v12M9 21v-6h6v6'],
                                    ['Sales',       'sales.budi@jasrental.id', 'M17 9V7a4 4 0 00-8 0v2M5 9h14l1 11H4L5 9z'],
                                ] as [$role, $email, $icon])
                                <button type="button" onclick="document.getElementById('email').value='{{ $email }}'; document.getElementById('password').value='password';" class="demo-btn group flex xs:flex-col items-center xs:items-start gap-3 xs:gap-1 text-left xs:text-center px-3.5 py-2.5 xs:px-2.5 border border-cream-sand rounded-xl bg-cream-DEFAULT hover:border-purple hover:bg-purple/5 hover:-translate-y-0.5 hover:shadow-card-sm transition-all duration-200 cursor-pointer">
                                    <svg class="demo-icon w-3.5 h-3.5 text-gold-dk xs:mb-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                                    </svg>
                                    <span class="flex flex-col xs:contents">
                                        <span class="text-[11px] xs:text-[11px] font-semibold text-navy-light group-hover:text-navy-dark transition-colors">{{ $role }}</span>
                                        <span class="text-[10px] text-slate-400 truncate max-w-full leading-tight">{{ $email }}</span>
                                    </span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                    </div><!-- /px padding -->
                </div><!-- /card -->

                <!-- footer -->
                <p class="text-center text-[11px] text-slate-400 mt-4">
                    &copy; {{ date('Y') }} SewaJas &middot; Premium Jas Rental Management
                </p>
            </div>
        </main>
    </div><!-- /flex layout -->

    <script>
        function loginPage() {
            return {
                showPass: false,
                loading: false,
                emailFocus: false,
                passFocus: false,
                particles: [],
                tilt: { rx: 0, ry: 0 },
                spot: { x: 0, y: 0 },
                ripples: [],
                rippleId: 0,

                onCardMove(e) {
                    // spotlight follows cursor within the right panel
                    const bounds = e.currentTarget.getBoundingClientRect();
                    this.spot.x = e.clientX - bounds.left;
                    this.spot.y = e.clientY - bounds.top;

                    // subtle 3D tilt for the form card, capped to a gentle range
                    const cx = bounds.left + bounds.width / 2;
                    const cy = bounds.top + bounds.height / 2;
                    const dx = (e.clientX - cx) / (bounds.width / 2);
                    const dy = (e.clientY - cy) / (bounds.height / 2);
                    this.tilt.rx = Math.max(-4, Math.min(4, dx * 4));
                    this.tilt.ry = Math.max(-3, Math.min(3, -dy * 3));
                },
                resetTilt() {
                    this.tilt.rx = 0;
                    this.tilt.ry = 0;
                },
                spawnRipple(e) {
                    const btn = e.currentTarget;
                    const bounds = btn.getBoundingClientRect();
                    const size = Math.max(bounds.width, bounds.height) * 1.4;
                    const id = this.rippleId++;
                    this.ripples.push({
                        id,
                        x: e.clientX - bounds.left - size / 2,
                        y: e.clientY - bounds.top - size / 2,
                        size,
                    });
                    setTimeout(() => {
                        this.ripples = this.ripples.filter(r => r.id !== id);
                    }, 650);
                },
            }
        }
    </script>
</body>
</html>