<!DOCTYPE html>

<script>
    (function () {
        try {
            var t = localStorage.getItem('maisonTheme');
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
</script>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: (localStorage.getItem('maisonTheme') !== null)
              ? localStorage.getItem('maisonTheme') === 'dark'
              : {{ auth()->user()?->theme === 'dark' ? 'true' : 'false' }},
          sidebarOpen: window.innerWidth >= 1024,
          sidebarMobileOpen: false,
          notifOpen: false,
          toggleDark() {
              this.darkMode = !this.darkMode;
              var val = this.darkMode ? 'dark' : 'light';
              localStorage.setItem('maisonTheme', val);
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          },
          init() {
              this.$watch('darkMode', function(val) {
                  if (val) {
                      document.documentElement.classList.add('dark');
                  } else {
                      document.documentElement.classList.remove('dark');
                  }
                  localStorage.setItem('maisonTheme', val ? 'dark' : 'light');
              });
              if (localStorage.getItem('maisonTheme') === null) {
                  var serverTheme = '{{ auth()->user()?->theme ?? 'light' }}';
                  localStorage.setItem('maisonTheme', serverTheme);
              }
          }
      }"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'MonsieurJas Premium') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#101E4E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════════════════
           MAISON SEWA — REDESIGNED: Bright, Modern, Colorful
           Inspired by Apple, Stripe, Linear, and French contemporary
        ═══════════════════════════════════════════════════════════════ */

        :root {
            /* ── Surfaces ── */
            --bg-main:        #F8F9FF;
            --bg-soft:        #F0F3FF;
            --secondary:      #E8EEFF;
            --card:           #FFFFFF;
            --card-elevated:  #FFFFFF;
            --border:         #D1DAF0;
            --divider:        #E0E7FF;
            --bg-navbar:      rgba(255,255,255,0.90);
            --bg-input:       #FFFFFF;
            --bg-dropdown:    #FFFFFF;

            /* ── Primary Brand – Vibrant Indigo ── */
            --primary:        #6366F1;
            --primary-dark:   #4F46E5;
            --primary-light:  #818CF8;
            --primary-tint:   #EEF2FF;
            --primary-glow:   rgba(99,102,241,0.25);

            /* ── Accent Palette – Vibrant & Colorful ── */
            --color-emerald:  #10B981;
            --color-sky:      #0EA5E9;
            --color-violet:   #8B5CF6;
            --color-rose:     #F43F5E;
            --color-amber:    #F59E0B;
            --color-cyan:     #06B6D4;
            --color-fuchsia:  #D946EF;

            /* ── Semantic Status ── */
            --success:        #10B981;
            --warning:        #F59E0B;
            --danger:         #EF4444;
            --info:           #3B82F6;

            /* ── Text ── */
            --text-dark:      #1F2937;
            --text-mid:       #4B5563;
            --text-soft:      #6B7280;
            --text-muted:     #9CA3AF;
            --text-disabled:  #D1D5DB;

            /* ── Surface tints (hover backgrounds) ── */
            --surf-primary:   rgba(99,102,241,0.10);
            --surf-emerald:   rgba(16,185,129,0.10);
            --surf-sky:       rgba(14,165,233,0.10);
            --surf-violet:    rgba(139,92,246,0.10);
            --surf-rose:      rgba(244,63,94,0.10);
            --surf-amber:     rgba(245,158,11,0.10);
            --surf-cyan:      rgba(6,182,212,0.10);
            --surf-fuchsia:   rgba(217,70,239,0.10);

            /* ── Semantic surface tints ── */
            --surf-success:   rgba(16,185,129,0.15);
            --surf-warning:   rgba(245,158,11,0.15);
            --surf-danger:    rgba(239,68,68,0.15);
            --surf-info:      rgba(59,130,246,0.15);

            /* ═══════════════════════════════════════════════
               DASHBOARD ACCENT ALIASES
               The super-admin/admin/sales dashboard blades
               reference these names directly (var(--accent-...),
               var(--color-sapphire), var(--color-teal), etc).
               They were previously UNDEFINED, which silently
               dropped every coloured icon-box/accent border and
               made the dashboard look flat/blue. Aliasing them to
               the palette above keeps one source of truth and
               automatically follows dark mode.
            ═══════════════════════════════════════════════ */
            --accent-emerald:      var(--color-emerald);
            --accent-emerald-tint: var(--surf-emerald);
            --accent-coral:        var(--color-rose);
            --accent-coral-tint:   var(--surf-rose);
            --accent-purple:       var(--color-violet);
            --accent-purple-tint:  var(--surf-violet);
            --accent-pink:         var(--color-fuchsia);
            --accent-pink-tint:    var(--surf-fuchsia);

            --color-sapphire:      var(--info);
            --color-sapphire-tint: var(--surf-info);
            --color-teal:          var(--color-cyan);
            --color-teal-tint:     var(--surf-cyan);
            --color-rouge:         var(--danger);
            --color-rouge-tint:    var(--surf-danger);

            --border-soft:         var(--divider);

            /* ── Shadows ── */
            --shadow-sm:      0 1px 3px rgba(15,23,42,0.08), 0 1px 2px rgba(15,23,42,0.06);
            --shadow-md:      0 4px 16px rgba(15,23,42,0.12), 0 2px 6px rgba(15,23,42,0.08);
            --shadow-lg:      0 10px 40px rgba(15,23,42,0.15), 0 4px 16px rgba(15,23,42,0.10);
            --shadow-xl:      0 20px 60px rgba(15,23,42,0.20), 0 8px 32px rgba(15,23,42,0.12);

            /* ── Border radius ── */
            --radius-sm:      8px;
            --radius-md:      14px;
            --radius-lg:      18px;
            --radius-xl:      22px;

            /* ── Sidebar ── */
            --sidebar-w: 256px;
            --sidebar-w-collapsed: 64px;

            /* ═══════════════════════════════════════════════
               SIDEBAR — FIXED PALETTE
            ═══════════════════════════════════════════════ */
            --sidebar-bg:          linear-gradient(180deg, #1E1B4B 0%, #171733 100%);
            --sidebar-border:      rgba(255,255,255,0.08);
            --sidebar-divider:     rgba(255,255,255,0.10);
            --sidebar-shadow:      4px 0 40px rgba(15,23,42,0.40);

            --sidebar-text:        #FFFFFF;
            --sidebar-text-mid:    #E5E7EB;
            --sidebar-text-muted:  #CBD5E1;

            --sidebar-hover-bg:    rgba(255,255,255,0.08);
            --sidebar-active-glow: 0 2px 16px rgba(139,92,246,0.40);

            --sidebar-logo-grad:   linear-gradient(135deg, #8B5CF6, #6366F1);
            --sidebar-accent:      #A78BFA;
            --sidebar-tooltip-bg: #FFFFFF;
            --sidebar-tooltip-text: #111827;
            --sidebar-tooltip-border: #D6DCE8;
            --sidebar-tooltip-shadow: 0 10px 28px rgba(15,23,42,.18);

            /* Fixed accent tints for sidebar */
            --sb-sky:        #38BDF8; --sb-sky-surf:      rgba(56,189,248,0.18);
            --sb-mint:       #34D399; --sb-mint-surf:      rgba(52,211,153,0.18);
            --sb-seafoam:    #22D3EE; --sb-seafoam-surf:  rgba(34,211,238,0.18);
            --sb-lavender:   #A78BFA; --sb-lavender-surf: rgba(167,139,250,0.18);
            --sb-coral:      #FB7185; --sb-coral-surf:    rgba(251,113,133,0.18);
            --sb-danger:     #F87171; --sb-danger-surf:   rgba(248,113,113,0.20);
            --sb-warning:    #FBBF24; --sb-warning-surf:  rgba(251,191,36,0.18);
        }

        /* ── DARK MODE — Modern Deep Indigo ── */
        .dark {
            --bg-main:        #0B0C1E;
            --bg-soft:        #111328;
            --secondary:      #1A1D3E;
            --card:           #1F2147;
            --card-elevated:  #262A56;
            --border:         #2E325F;
            --divider:        #363A6B;
            --bg-navbar:      rgba(11,12,30,0.92);
            --bg-input:       #1F2147;
            --bg-dropdown:    #1F2147;

            --text-dark:      #F9FAFB;
            --text-mid:       #E5E7EB;
            --text-soft:      #9CA3AF;
            --text-muted:     #6B7280;
            --text-disabled:  #4B5563;

            --primary:        #818CF8;
            --primary-dark:   #6366F1;
            --primary-light:  #A5B4FC;
            --primary-tint:   rgba(99,102,241,0.18);
            --primary-glow:   rgba(99,102,241,0.35);

            --color-emerald:  #34D399;
            --color-sky:      #38BDF8;
            --color-violet:   #A78BFA;
            --color-rose:     #FB7185;
            --color-amber:    #FBBF24;
            --color-cyan:     #22D3EE;
            --color-fuchsia:  #E879F9;

            --success:        #34D399;
            --warning:        #FBBF24;
            --danger:         #F87171;
            --info:           #60A5FA;

            --surf-primary:   rgba(99,102,241,0.15);
            --surf-emerald:   rgba(52,211,153,0.15);
            --surf-sky:       rgba(56,189,248,0.15);
            --surf-violet:    rgba(167,139,250,0.15);
            --surf-rose:      rgba(251,113,133,0.15);
            --surf-amber:     rgba(251,191,36,0.15);
            --surf-cyan:      rgba(34,211,238,0.15);
            --surf-fuchsia:   rgba(232,121,249,0.15);
            --surf-success:   rgba(52,211,153,0.18);
            --surf-warning:   rgba(251,191,36,0.18);
            --surf-danger:    rgba(248,113,113,0.18);
            --surf-info:      rgba(96,165,250,0.18);

            --shadow-sm:      0 1px 3px rgba(0,0,0,0.4);
            --shadow-md:      0 4px 16px rgba(0,0,0,0.5);
            --shadow-lg:      0 10px 40px rgba(0,0,0,0.6);
            --shadow-xl:      0 20px 60px rgba(0,0,0,0.7);
        }

        /* ── BASE ── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', 'Inter', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-dark);
            margin: 0;
            overflow-x: hidden;
        }

        /* ── Typography ── */
        .font-playfair   { font-family: 'Playfair Display', 'Cormorant Garamond', serif; }
        .font-cormorant  { font-family: 'Cormorant Garamond', serif; }
        .font-editorial  { font-family: 'Cormorant Garamond', serif; font-style: italic; }

        /* ═══════════════════════════════════════════════
           SIDEBAR — Light, Glass, Modern
        ═══════════════════════════════════════════════ */
        .sidebar {
            /* Fixed palette: intentionally NOT theme-dependent.
               Uses --sidebar-* variables declared once in :root
               so the sidebar always stays visually distinct from
               the dashboard, in both light and dark mode. */
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid var(--sidebar-border);
            box-shadow: var(--sidebar-shadow);
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            width: var(--sidebar-w);
            transform: translateX(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        .main-wrap {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @media (min-width: 1024px) {
            .main-wrap { margin-left: var(--sidebar-w); }
            .main-wrap.sidebar-collapsed { margin-left: var(--sidebar-w-collapsed); }
        }
        @media (max-width: 1023px) {
            .sidebar { width: var(--sidebar-w) !important; transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-wrap { margin-left: 0 !important; }
        }

        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 39;
            backdrop-filter: blur(4px);
        }
        @media (max-width: 1023px) {
            .sidebar-overlay.active { display: block; }
        }

        /* ── Sidebar Items ──
           All colours below use the fixed --sidebar-*/--sb-* variables
           (declared once in :root) so hover/active states never shift
           when the light/dark theme toggle is used. */
        .sidebar * {
            color: inherit !important;
        }
        .sidebar {
            color: var(--sidebar-text) !important;
        }
        .sidebar-item {
            transition: all 0.2s ease;
            border-radius: var(--radius-md);
            margin: 2px 8px;
            position: relative;
            color: var(--sidebar-text-mid) !important;
        }
        .sidebar-item:hover {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-text) !important;
        }
        .sidebar-item.active {
            background: var(--sidebar-hover-bg);
            color: var(--sidebar-accent) !important;
            font-weight: 600;
            box-shadow: var(--sidebar-active-glow);
        }
        /* Per-section coloured active states (keep as per section colours) */
        .sidebar-item.active-blue   { background: var(--sb-sky-surf);      color: var(--sb-sky) !important; }
        .sidebar-item.active-green  { background: var(--sb-mint-surf);    color: var(--sb-mint) !important; }
        .sidebar-item.active-teal   { background: var(--sb-seafoam-surf); color: var(--sb-seafoam) !important; }
        .sidebar-item.active-rose   { background: var(--sb-coral-surf);   color: var(--sb-coral) !important; }
        .sidebar-item.active-ameth  { background: var(--sb-lavender-surf);color: var(--sb-lavender) !important; }
        .sidebar-item.active-rouge  { background: var(--sb-danger-surf);  color: var(--sb-danger) !important; }

        /* Sidebar icon colours */
        .nav-icon-blue     { color: var(--sb-sky) !important; }
        .nav-icon-green    { color: var(--sb-mint) !important; }
        .nav-icon-teal     { color: var(--sb-seafoam) !important; }
        .nav-icon-rouge    { color: var(--sb-danger) !important; }
        .nav-icon-amethyst { color: var(--sb-lavender) !important; }
        .nav-icon-gold     { color: var(--sb-warning) !important; }
        .nav-icon-jade     { color: var(--sb-mint) !important; }
        .nav-icon-sapphire { color: var(--sb-sky) !important; }
        .nav-icon-terracot { color: var(--sb-coral) !important; }

        .sidebar-tooltip {
            display: none;
            position: absolute;
            left: calc(var(--sidebar-w-collapsed) - 4px);
            top: 50%;
            transform: translateY(-50%);
            background: var(--sidebar-tooltip-bg);
            border: 1px solid var(--sidebar-tooltip-border);
            color: var(--sidebar-text);
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: var(--radius-sm);
            white-space: nowrap;
            z-index: 50;
            box-shadow: var(--shadow-md);
        }
        @media (min-width: 1024px) {
            .sidebar:not(.collapsed) .sidebar-tooltip { display: none !important; }
        }

        .nav-section-label {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--sidebar-text-mid);
        }

        /* ═══════════════════════════════════════════════
           CARDS
        ═══════════════════════════════════════════════ */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            transition: box-shadow 0.25s ease, transform 0.2s ease, border-color 0.2s ease;
        }
        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: var(--primary-light);
        }
        .dark .card { background: var(--card); border-color: var(--border); }

        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: var(--radius-xl);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -24px; right: -24px;
            width: 80px; height: 80px;
            border-radius: 50%;
            background: var(--primary-tint);
            opacity: 0.25;
        }
        .dark .stat-card { background: var(--card); border-color: var(--border); }

        /* ── Stat card colour variants ──
           Add class stat-card-{blue|green|purple|orange|teal|pink}
           on any .stat-card so the dashboard reads as colourful
           instead of a single monotone blue. */
        .stat-card-blue   { border-top-color: var(--primary); }
        .stat-card-blue::after   { background: var(--primary-tint); }

        .stat-card-green  { border-top-color: var(--color-emerald); }
        .stat-card-green::after  { background: var(--surf-emerald); }

        .stat-card-purple { border-top-color: var(--color-violet); }
        .stat-card-purple::after { background: var(--surf-violet); }

        .stat-card-orange { border-top-color: var(--color-amber); }
        .stat-card-orange::after { background: var(--surf-amber); }

        .stat-card-teal   { border-top-color: var(--color-cyan); }
        .stat-card-teal::after   { background: var(--surf-cyan); }

        .stat-card-pink   { border-top-color: var(--color-rose); }
        .stat-card-pink::after   { background: var(--surf-rose); }

        .stat-card-gold   { border-top-color: var(--warning); }
        .stat-card-gold::after   { background: var(--surf-warning); }

        /* ── Icon box variants ──
               Small rounded square used for the stat-card icon (e.g. the
               building/users/shirt icon). Pair with an <i data-lucide>
               coloured via the matching text colour below. */
            .icon-box {
                width: 44px; height: 44px;
                border-radius: var(--radius-md);
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            }
            .icon-box-blue   { background: var(--surf-info);     color: var(--info); }
            .icon-box-green  { background: var(--surf-emerald);  color: var(--color-emerald); }
            .icon-box-purple { background: var(--surf-violet);   color: var(--color-violet); }
            .icon-box-orange { background: var(--surf-amber);    color: var(--color-amber); }
            .icon-box-teal   { background: var(--surf-cyan);     color: var(--color-cyan); }
            .icon-box-pink   { background: var(--surf-rose);     color: var(--color-rose); }
            .icon-box-gold   { background: var(--surf-warning);  color: var(--warning); }
            .icon-box-fuchsia{ background: var(--surf-fuchsia); color: var(--color-fuchsia); }

        /* ═══════════════════════════════════════════════
           TABLES
        ═══════════════════════════════════════════════ */
        .elegant-table { width: 100%; border-collapse: collapse; }
        .elegant-table thead {
            background: var(--bg-soft);
        }
        .elegant-table thead th {
            color: var(--text-soft);
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 10px 14px;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }
        .elegant-table tbody tr {
            border-bottom: 1px solid var(--divider);
            transition: background 0.15s ease;
        }
        .elegant-table tbody tr:hover { background-color: var(--bg-soft); }
        .elegant-table tbody td { padding: 10px 14px; font-size: 0.875rem; }
        .dark .elegant-table thead { background: var(--bg-soft); }
        .dark .elegant-table tbody tr { border-color: var(--divider); }
        .dark .elegant-table tbody tr:hover { background-color: var(--secondary); }

        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* ═══════════════════════════════════════════════
           BUTTONS — Modern & Soft
        ═══════════════════════════════════════════════ */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #FFFFFF;
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: var(--radius-md);
            transition: all 0.25s ease;
            box-shadow: 0 2px 12px rgba(108,143,245,0.30);
            display: inline-flex; align-items: center; gap: 0.5rem;
            border: none; cursor: pointer; text-decoration: none;
            font-size: 0.875rem; letter-spacing: 0.01em;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #4B6ED6);
            box-shadow: 0 4px 20px rgba(108,143,245,0.45);
            transform: translateY(-2px);
            color: #FFFFFF;
        }

        .btn-secondary {
            background: var(--bg-soft);
            color: var(--text-dark);
            border: 1px solid var(--border);
            font-weight: 500;
            padding: 0.6rem 1.4rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 0.5rem;
            cursor: pointer; text-decoration: none; font-size: 0.875rem;
        }
        .btn-secondary:hover {
            background: var(--secondary);
            border-color: var(--primary);
            color: var(--primary-dark);
            box-shadow: var(--shadow-sm);
        }

        .btn-danger {
            background: var(--surf-danger);
            color: var(--danger);
            border: 1px solid rgba(242,139,139,0.3);
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 0.5rem;
            cursor: pointer; text-decoration: none; font-size: 0.875rem;
        }
        .btn-danger:hover {
            background: rgba(242,139,139,0.18);
            transform: translateY(-2px);
        }

        .btn-info {
            background: var(--surf-info);
            color: var(--info);
            border: 1px solid rgba(120,200,255,0.3);
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 0.5rem;
            cursor: pointer; text-decoration: none; font-size: 0.875rem;
        }
        .btn-info:hover {
            background: rgba(120,200,255,0.18);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--surf-success);
            color: var(--success);
            border: 1px solid rgba(123,199,164,0.3);
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 0.5rem;
            cursor: pointer; text-decoration: none; font-size: 0.875rem;
        }
        .btn-success:hover {
            background: rgba(123,199,164,0.18);
            transform: translateY(-2px);
        }

        .btn-sm { padding: 0.3rem 0.75rem; font-size: 0.78rem; border-radius: var(--radius-sm); }

        /* ═══════════════════════════════════════════════
           FORM INPUTS
        ═══════════════════════════════════════════════ */
        .form-input {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 0.6rem 0.875rem;
            font-size: 0.875rem;
            background: var(--bg-input);
            color: var(--text-dark);
            transition: all 0.2s ease;
            outline: none;
            font-family: 'DM Sans', 'Inter', sans-serif;
        }
        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }
        .dark .form-input {
            background: var(--bg-input);
            border-color: var(--border);
            color: var(--text-dark);
        }

        /* ═══════════════════════════════════════════════
           BADGES — Colorful & Semantic
        ═══════════════════════════════════════════════ */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-blue    { background: var(--surf-info); color: var(--info); border: 1px solid rgba(96,165,250,0.3); }
            .badge-green   { background: var(--surf-success); color: var(--success); border: 1px solid rgba(52,211,153,0.3); }
            .badge-teal    { background: var(--surf-cyan); color: var(--color-cyan); border: 1px solid rgba(34,211,238,0.3); }
            .badge-red     { background: var(--surf-danger); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); }
            .badge-yellow  { background: var(--surf-warning); color: var(--warning); border: 1px solid rgba(251,191,36,0.3); }
            .badge-amber   { background: var(--surf-warning); color: var(--warning); border: 1px solid rgba(251,191,36,0.3); }
            .badge-purple  { background: var(--surf-violet); color: var(--color-violet); border: 1px solid rgba(167,139,250,0.3); }
            .badge-rose    { background: var(--surf-rose); color: var(--color-rose); border: 1px solid rgba(251,113,133,0.3); }
            .badge-gold    { background: var(--surf-warning); color: var(--warning); border: 1px solid rgba(251,191,36,0.3); }
            .badge-gray    { background: var(--bg-soft); color: var(--text-muted); border: 1px solid var(--border); }
            .badge-indigo  { background: var(--surf-primary); color: var(--primary); border: 1px solid rgba(99,102,241,0.3); }
            .badge-coral   { background: var(--surf-rose); color: var(--color-rose); border: 1px solid rgba(251,113,133,0.3); }
            .badge-emerald { background: var(--surf-emerald); color: var(--color-emerald); border: 1px solid rgba(52,211,153,0.3); }
            .badge-sapphire{ background: var(--surf-info); color: var(--info); border: 1px solid rgba(96,165,250,0.3); }
            .badge-rouge   { background: var(--surf-danger); color: var(--danger); border: 1px solid rgba(248,113,113,0.3); }
            .badge-fuchsia { background: var(--surf-fuchsia); color: var(--color-fuchsia); border: 1px solid rgba(232,121,249,0.3); }

        /* Rental status mapping */
        .badge-waiting          { @extend .badge-amber; }
        .badge-active           { @extend .badge-blue; }
        .badge-overdue          { @extend .badge-red; }
        .badge-returned         { @extend .badge-green; }
        .badge-menunggu_laundry { @extend .badge-amber; }
        .badge-dalam_laundry    { @extend .badge-teal; }
        .badge-siap_disewakan   { @extend .badge-purple; }
        .badge-cancelled        { @extend .badge-gray; }

        /* ═══════════════════════════════════════════════
           MODAL
        ═══════════════════════════════════════════════ */
        .modal-overlay {
            backdrop-filter: blur(4px);
            background: rgba(16,24,40,0.45);
        }
        .modal-box {
            background: var(--card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        @media (max-width: 639px) {
            .modal-box {
                width: 100% !important; max-width: 100% !important;
                max-height: 92vh !important; margin: 0 !important;
                border-radius: var(--radius-xl) var(--radius-xl) 0 0 !important;
                padding-bottom: max(1.5rem, env(safe-area-inset-bottom)) !important;
            }
        }
        .dark .modal-box { background: var(--card); border-color: var(--border); }

        /* ═══════════════════════════════════════════════
           GLASS
        ═══════════════════════════════════════════════ */
        .glass {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(232,237,243,0.6);
        }
        .dark .glass {
            background: rgba(15,23,42,0.85);
            border-color: rgba(45,58,78,0.6);
        }

        /* ═══════════════════════════════════════════════
           ANIMATIONS
        ═══════════════════════════════════════════════ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.28s ease forwards; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-spinner {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid currentColor;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }
        .btn-loading { opacity: 0.75 !important; cursor: not-allowed !important; pointer-events: none; }

        /* ═══════════════════════════════════════════════
           SCROLLBAR
        ═══════════════════════════════════════════════ */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        /* ═══════════════════════════════════════════════
           BOTTOM NAV (Mobile)
        ═══════════════════════════════════════════════ */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: var(--card);
            border-top: 1px solid var(--border);
            padding: 8px 16px calc(8px + env(safe-area-inset-bottom));
            z-index: 35;
            gap: 4px;
            backdrop-filter: blur(10px);
        }
        @media (max-width: 1023px) { .bottom-nav { display: flex; } }
        @media (max-width: 1023px) { main { padding-bottom: 80px !important; } }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            padding: 6px 4px;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-soft);
            font-size: 10px;
            font-weight: 500;
            transition: all 0.2s;
            position: relative;
        }
        .bottom-nav-item.active { color: var(--primary); }
        .bottom-nav-item.active svg { color: var(--primary); }
        .bottom-nav-item svg { transition: transform 0.2s; }
        .bottom-nav-item:active svg { transform: scale(0.9); }
        .bottom-nav-badge {
            position: absolute;
            top: 2px; right: calc(50% - 18px);
            min-width: 16px; height: 16px;
            background: var(--danger); color: #fff;
            font-size: 9px; font-weight: 700;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 3px;
        }

        /* ═══════════════════════════════════════════════
           NOTIFICATION PANEL (updated colors)
        ═══════════════════════════════════════════════ */
        :root {
            --notif-cream: #FFFFFF;
            --notif-cream2: #F7F9FC;
            --notif-gold: var(--primary);
            --notif-gold-light: var(--primary-light);
            --notif-charcoal: var(--text-dark);
            --notif-charcoal2: var(--bg-soft);
            --notif-charcoal3: var(--secondary);
            --notif-border: var(--border);
            --notif-muted: var(--text-soft);
            --notif-light: var(--text-muted);
        }
        .dark {
            --notif-cream: #1E293B;
            --notif-cream2: #2D3A4E;
            --notif-charcoal: #F1F5F9;
            --notif-charcoal2: #111827;
            --notif-charcoal3: #1E293B;
            --notif-border: #2D3A4E;
            --notif-muted: #94A3B8;
            --notif-light: #64748B;
        }
        .notif-bell-btn {
            position: relative; width: 40px; height: 40px; border-radius: 50%;
            background: var(--bg-soft); border: 1.5px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s; flex-shrink: 0;
        }
        .notif-bell-btn:hover, .notif-bell-btn.bell-active {
            background: var(--surf-primary); border-color: var(--primary);
        }
        .notif-badge {
            position: absolute; top: -4px; right: -4px;
            min-width: 18px; height: 18px;
            background: var(--danger); color: #fff; font-size: 10px; font-weight: 500;
            border-radius: 9px; display: flex; align-items: center; justify-content: center;
            padding: 0 4px; border: 2px solid var(--card);
        }
        .notif-panel {
            position: fixed; right: 12px; top: 80px;
            width: min(400px, calc(100vw - 24px));
            background: var(--notif-cream); border: 1px solid var(--notif-border);
            border-radius: var(--radius-xl); box-shadow: var(--shadow-lg);
            z-index: 999; display: flex; flex-direction: column; max-height: 70vh; overflow: hidden;
        }
        @media (max-width: 480px) { .notif-panel { right: 8px; left: 8px; width: auto; } }
        .notif-panel-head {
            background: var(--notif-charcoal2); padding: 14px 18px;
            display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0;
        }
        .notif-panel-title { color: var(--notif-charcoal); font-size: 17px; font-weight: 500; font-family: 'Playfair Display', serif; }
        .notif-panel-sub { color: var(--notif-light); font-size: 11px; margin-top: 2px; }
        .notif-panel-actions { display: flex; align-items: center; gap: 8px; }
        .notif-btn-markall {
            display: flex; align-items: center; gap: 5px;
            background: var(--surf-primary); border: 1px solid rgba(108,143,245,0.2);
            color: var(--primary); font-size: 11px; padding: 4px 10px; border-radius: var(--radius-sm);
            cursor: pointer; transition: background 0.2s;
        }
        .notif-btn-markall:hover { background: var(--primary-tint); }
        .notif-btn-close {
            width: 28px; height: 28px; border-radius: 50%; background: rgba(0,0,0,0.04);
            border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--text-soft); transition: background 0.2s;
        }
        .notif-btn-close:hover { background: rgba(0,0,0,0.08); }
        .dark .notif-btn-close { background: rgba(255,255,255,0.05); }
        .dark .notif-btn-close:hover { background: rgba(255,255,255,0.12); }

        .notif-filter-tabs {
            display: flex; background: var(--notif-cream2); border-bottom: 1px solid var(--notif-border);
            overflow-x: auto; flex-shrink: 0; scrollbar-width: none;
        }
        .notif-filter-tabs::-webkit-scrollbar { display: none; }
        .notif-tab {
            padding: 8px 12px; font-size: 12px; color: var(--notif-muted); cursor: pointer;
            border-bottom: 2px solid transparent; transition: all 0.15s; white-space: nowrap;
            display: flex; align-items: center; gap: 5px; background: none;
            border-top: none; border-left: none; border-right: none;
        }
        .notif-tab-active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 500; }
        .notif-tab-count { font-size: 10px; background: var(--notif-cream2); border: 1px solid var(--notif-border); color: var(--notif-muted); padding: 0 6px; border-radius: 9px; }
        .notif-tab-count-active { font-size: 10px; background: var(--surf-primary); border: 1px solid rgba(108,143,245,0.2); color: var(--primary); padding: 0 6px; border-radius: 9px; }
        .notif-list { overflow-y: auto; flex: 1; scrollbar-width: thin; scrollbar-color: var(--notif-border) transparent; }
        .notif-list::-webkit-scrollbar { width: 4px; }
        .notif-list::-webkit-scrollbar-thumb { background: var(--notif-border); border-radius: 2px; }
        .notif-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; padding: 40px; color: var(--notif-muted); font-size: 13px; }
        .notif-spinner { width: 24px; height: 24px; border: 2px solid var(--notif-border); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.6s linear infinite; }
        .notif-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; gap: 8px; }
        .notif-empty-text { font-size: 14px; color: var(--notif-muted); }
        .notif-empty-sub { font-size: 12px; color: var(--notif-light); }
        .notif-item {
            position: relative; display: flex; gap: 12px; padding: 13px 18px;
            border-bottom: 0.5px solid var(--notif-border); cursor: pointer; transition: background 0.15s;
        }
        .notif-item:hover { background: var(--notif-cream2); }
        .notif-item.unread { background: var(--surf-primary); }
        .notif-unread-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--primary); border-radius: 0 2px 2px 0; }
        .notif-icon-wrap { flex-shrink: 0; width: 38px; height: 38px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
        .notif-icon-emoji { font-size: 17px; }
        .ni-blue    { background: var(--surf-info); }
        .ni-green   { background: var(--surf-success); }
        .ni-red     { background: var(--surf-danger); }
        .ni-emerald { background: var(--surf-success); }
        .ni-amber   { background: var(--surf-warning); }
        .ni-slate   { background: var(--surf-lavender); }
        .notif-body { flex: 1; min-width: 0; }
        .notif-row-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .notif-title { font-size: 13px; font-weight: 500; color: var(--text-dark); line-height: 1.3; }
        .notif-time { font-size: 11px; color: var(--text-muted); white-space: nowrap; flex-shrink: 0; }
        .notif-msg { font-size: 12px; color: var(--text-soft); margin-top: 3px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .notif-tag { display: inline-block; margin-top: 5px; font-size: 10px; background: var(--bg-soft); border: 1px solid var(--border); color: var(--text-soft); padding: 1px 7px; border-radius: 5px; }
        .notif-delete-btn { position: absolute; right: 10px; top: 10px; opacity: 0; padding: 5px; border-radius: var(--radius-sm); border: none; background: none; color: var(--text-soft); cursor: pointer; transition: opacity 0.15s, color 0.15s; }
        .notif-item:hover .notif-delete-btn { opacity: 1; }
        .notif-delete-btn:hover { color: var(--danger); background: var(--surf-danger); }
        .notif-panel-footer { background: var(--notif-cream2); border-top: 1px solid var(--notif-border); padding: 10px 18px; display: flex; justify-content: center; flex-shrink: 0; }
        .notif-see-all { display: flex; align-items: center; gap: 5px; color: var(--primary); font-size: 13px; font-weight: 500; text-decoration: none; transition: gap 0.2s; }
        .notif-see-all:hover { gap: 9px; }
    </style>

    @stack('styles')
</head>

<body class="antialiased">

    <div class="sidebar-overlay" :class="{ 'active': sidebarMobileOpen }" @click="sidebarMobileOpen = false"></div>

    <div class="flex h-screen overflow-hidden" id="app">

        <aside class="sidebar"
            :class="{ 'collapsed': !sidebarOpen && window.innerWidth >= 1024, 'mobile-open': sidebarMobileOpen }">
            @include('layouts.sidebar')
        </aside>

        <div class="main-wrap flex-1 flex flex-col overflow-hidden"
            :class="{ 'sidebar-collapsed': !sidebarOpen && window.innerWidth >= 1024 }">

            @include('layouts.navbar')

            <main class="flex-1 overflow-y-auto p-4 lg:p-6" style="background: var(--bg-main)">

                @include('components.flash-messages')

                @hasSection('breadcrumb')
                    <div class="mb-4">@yield('breadcrumb')</div>
                @endif

                @hasSection('page-header')
                    <div class="mb-6">@yield('page-header')</div>
                @endif

                <div class="animate-fade-in">
                    @yield('content')
                </div>

                @auth
                    @include('layouts.footer')
                @endauth
            </main>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="{{ route('dashboard') }}"
           class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('rentals.index') }}"
           class="bottom-nav-item {{ request()->routeIs('rentals.index') ? 'active' : '' }}">
            @php $ov = \App\Models\Rental::where('rental_status','overdue')->when(!auth()->user()->isSuperAdmin(), fn($q)=>$q->where('branch_id',auth()->user()->branch_id))->count(); @endphp
            @if($ov > 0)
                <span class="bottom-nav-badge">{{ $ov > 9 ? '9+' : $ov }}</span>
            @endif
            <i data-lucide="shirt" class="w-5 h-5"></i>
            <span>Sewa</span>
        </a>
        <a href="{{ route('rentals.create') }}" class="bottom-nav-item">
            <div class="w-10 h-10 rounded-2xl flex items-center justify-center -mt-5"
                 style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: 0 4px 18px rgba(108,143,245,0.45)">
                <i data-lucide="plus" class="w-5 h-5" style="color:#FFFFFF"></i>
            </div>
            <span style="margin-top: 2px">Buat</span>
        </a>
        <a href="{{ route('rentals.scan') }}"
           class="bottom-nav-item {{ request()->routeIs('rentals.scan') ? 'active' : '' }}">
            <i data-lucide="scan-qr-code" class="w-5 h-5"></i>
            <span>Scan</span>
        </a>
        <a href="#" @click.prevent="sidebarMobileOpen = true" class="bottom-nav-item">
            <i data-lucide="menu" class="w-5 h-5"></i>
            <span>Menu</span>
        </a>
    </nav>

    <div id="toast-container" class="fixed top-4 right-4 z-[60] flex flex-col gap-2" x-data="toastManager()"></div>

    <script>
        lucide.createIcons();

        function toastManager() {
            return {
                toasts: [],
                add(msg, type = 'success') {
                    const id = Date.now();
                    this.toasts.push({ id, msg, type });
                    setTimeout(() => this.remove(id), 4000);
                },
                remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); }
            }
        }

        window.showToast = function(msg, type = 'success') {
            document.dispatchEvent(new CustomEvent('show-toast', { detail: { msg, type } }));
        }

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                document.querySelectorAll('[x-data]').forEach(el => {
                    if (el.__x) el.__x.$data.sidebarMobileOpen = false;
                });
            }
        });
    </script>

    @stack('scripts')

    <script>
    (function () {
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            var btn = form.querySelector('button[type="submit"]');
            if (!btn) return;
            if (btn.hasAttribute('data-no-loading')) return;
            if (btn.hasAttribute('disabled')) return;
            btn.setAttribute('disabled', '');
            btn.classList.add('btn-loading');
            var spinner = document.createElement('span');
            spinner.className = 'btn-spinner';
            btn.innerHTML = '';
            btn.appendChild(spinner);
            btn.appendChild(document.createTextNode('\u00A0Memproses...'));
        }, true);
    })();
    </script>
</body>
</html>