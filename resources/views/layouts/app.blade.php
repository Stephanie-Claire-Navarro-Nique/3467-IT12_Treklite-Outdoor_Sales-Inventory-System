<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Treklite Outdoor') &mdash; Sales &amp; Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 240px;
        }

        body {
            background: #f4f6f5;
        }

        /* --- Sidebar --- */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: #1e5b31;
            color: #fff;
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform 0.2s ease-in-out;
        }

        .sidebar .brand {
            padding: 1.25rem 1rem;
            font-weight: 700;
            font-size: 1.15rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        /* Toggle that lives INSIDE the sidebar header */
        .sidebar-toggle-in {
            background: transparent;
            border: 0;
            color: rgba(255, 255, 255, 0.85);
            padding: 0.25rem 0.5rem;
            border-radius: 0.4rem;
            line-height: 1;
            font-size: 1.1rem;
        }

        .sidebar-toggle-in:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.65rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border-left: 3px solid transparent;
        }

        .sidebar .nav-link i {
            font-size: 1.05rem;
            width: 1.2rem;
            text-align: center;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: #ffc107;
            color: #fff;
            font-weight: 600;
        }

        .sidebar .nav-section-label {
            padding: 0.9rem 1.25rem 0.3rem;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.55);
        }

        .sidebar .sidebar-footer {
            margin-top: auto;
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* --- Main content area --- */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.2s ease-in-out;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e3e6e4;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.75rem;
            min-height: 56px;
        }

        .sidebar-toggle {
            display: inline-flex;
            align-items: center;
        }

        /* --- Desktop: user can fold the sidebar away (remembered in this browser) --- */
        @media (min-width: 992px) {
            /* Sidebar open  -> no top bar at all (the toggle is inside the sidebar header and
                                 each page already shows its own heading).
               Sidebar folded -> the top bar appears with the toggle at the top-left. */
            html:not(.sidebar-collapsed) .topbar {
                display: none;
            }

            html.sidebar-collapsed .topbar .sidebar-toggle {
                animation: toggle-in 0.2s ease-in;
            }

            html.sidebar-collapsed .sidebar {
                transform: translateX(-100%);
            }

            html.sidebar-collapsed .main-wrapper {
                margin-left: 0;
            }
        }

        /* --- Mobile: collapse sidebar off-canvas --- */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: inline-flex;
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.35);
                z-index: 1030;
            }

            .sidebar-backdrop.show {
                display: block;
            }
        }
        /* My Profile block at the bottom of the sidebar */
        .sidebar .profile { display: flex; align-items: center; gap: .6rem; padding: .45rem .55rem; border-radius: .5rem; color: #fff; text-decoration: none; }
        .sidebar a.profile:hover, .sidebar .profile.active { background: rgba(255, 255, 255, .14); }
        .sidebar .profile .bi-person-circle { font-size: 2rem; line-height: 1; }

        @keyframes toggle-in {
            from { opacity: 0; transform: translateX(-8px); }
            to   { opacity: 1; transform: translateX(0); }
        }
    </style>
    <script>
        // Display preference only - nothing is saved to the database.
        try { if (localStorage.getItem('sidebarCollapsed') === '1') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}
    </script>
</head>
<body>
    
    @php
    $isOwner = auth()->user()?->hasFullAccess();

    // Dashboard first, then the major transactions (Sales, Inventory), then master data and reports
    $navItems = $isOwner
        ? [
            ['route' => 'dashboard',       'label' => 'Dashboard',     'icon' => 'bi-speedometer2', 'match' => 'dashboard'],
            ['route' => 'sales.create',    'label' => 'New Sale',      'icon' => 'bi-cart-plus',    'match' => 'sales.create'],
            ['route' => 'sales.index',     'label' => 'Sales Records', 'icon' => 'bi-receipt',      'match' => 'sales.index'],
            ['route' => 'products.index',  'label' => 'Products',      'icon' => 'bi-tags',         'match' => 'products.*'],
            ['route' => 'inventory.index', 'label' => 'Inventory',     'icon' => 'bi-box-seam',     'match' => 'inventory.*'],
            ['route' => 'reports.sales',   'label' => 'Reports',       'icon' => 'bi-graph-up',     'match' => 'reports.*'],
            ['route' => 'users.index',  'label' => 'Staff Accounts', 'icon' => 'bi-people', 'match' => 'users.*'],
            ['route' => 'activity.index', 'label' => 'Activity Log', 'icon' => 'bi-clock-history', 'match' => 'activity.*'],
            ['route' => 'backup.index', 'label' => 'Backup', 'icon' => 'bi-cloud-arrow-down','match' => 'backup.*'],
        ]
        : [
            ['route' => 'sales.create',    'label' => 'New Sale',      'icon' => 'bi-cart-plus',    'match' => 'sales.create'],
            ['route' => 'sales.index',     'label' => 'Sales Records', 'icon' => 'bi-receipt',      'match' => 'sales.index'],
            ['route' => 'inventory.index', 'label' => 'Stock Check',   'icon' => 'bi-box-seam',     'match' => 'inventory.*'],
        ];
@endphp

<div class="sidebar-backdrop" id="sidebar-backdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <span>Treklite Outdoor</span>
        <button class="sidebar-toggle-in" type="button" data-sidebar-toggle title="Hide menu" aria-label="Hide menu">
            <i class="bi bi-layout-sidebar"></i>
        </button>
    </div>
    <nav class="nav flex-column mt-2">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        {{-- My Profile: everyone opens their OWN profile here (Staff Accounts is a separate menu item for the Owner) --}}
        <a href="{{ route('profile.edit') }}" title="My profile"
           class="profile mb-2 {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i>
            <span class="flex-grow-1 lh-sm">
                <span class="d-block fw-semibold">{{ auth()->user()?->name }}</span>
                <span class="badge bg-light text-dark">{{ auth()->user()?->role }}</span>
            </span>
            <i class="bi bi-chevron-right small text-white-50"></i>
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right"></i> Log out
            </button>
        </form>
    </div>
</aside>

<div class="main-wrapper">
    <div class="topbar">
        <button class="btn btn-sm btn-outline-secondary sidebar-toggle" id="sidebar-toggle" type="button" data-sidebar-toggle title="Show menu" aria-label="Show menu">
            <i class="bi bi-layout-sidebar"></i>
        </button>
        <span class="fw-semibold">Treklite Outdoor</span>
    </div>

    <div class="container-fluid py-4 px-4">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggleBtns = document.querySelectorAll('[data-sidebar-toggle]');

    function toggleSidebar() {
        if (window.matchMedia('(min-width: 992px)').matches) {
            // Desktop: fold / unfold the sidebar and remember it
            const collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        } else {
            // Phone / tablet: slide the menu in and out (unchanged)
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        }
    }

    toggleBtns.forEach(btn => btn.addEventListener('click', toggleSidebar));
    backdrop?.addEventListener('click', toggleSidebar);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
