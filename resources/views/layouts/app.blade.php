<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IGS Project Management - @yield('title')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Responsive CSS -->
    <link href="{{ asset('css/responsive.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h2>IGS Project</h2>
    </div>
    <ul class="nav-menu">
        <li>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
        </li>
        
        @if(auth()->user()->isAdmin())
            <li>
                <a href="{{ route('resources.index') }}" class="nav-link {{ request()->routeIs('resources.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    Gestione Risorse
                </a>
            </li>

            <li>
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') && !request()->routeIs('users.profile') ? 'active' : '' }}">
                    <i class="fas fa-user-cog"></i>
                    Gestione Utenti
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('hours.index') ? 'active' : '' }}" href="{{ route('hours.index') }}">
                    <i class="fas fa-clock"></i>
                    <span>Gestione Orario</span>
                </a>
            </li>

            <!-- 🆕 NUOVO MENU per Ore Giornaliere -->
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('daily-hours.*') ? 'active' : '' }}" href="{{ route('daily-hours.index') }}">
                    <i class="fas fa-calendar-day"></i>
                    <span>Ore Giornaliere</span>
                </a>
            </li>

            <li>
                <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    Gestione Clienti
                </a>
            </li>
            
            <li>
                <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram"></i>
                    Gestione Progetti
                </a>
            </li>
            
            <li>
                <a href="{{ route('areas.index') }}" class="nav-link {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i>
                    Gestione Aree
                </a>
            </li>
        @endif
        
        <li>
            <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i>
                Gestione Attività
            </a>
        </li>
        
        <li>
            <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i>
                Gestione Task
            </a>
        </li>
        
        <li>
            <a href="{{ route('calendar.index') }}" class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i>
                Calendario
            </a>
        </li>
    </ul>
</div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">
                    <h1>@yield('title')</h1>
                </div>
                <div class="header-actions">
                    @if(auth()->check())
                    <div class="dropdown">
                        <button class="btn btn-link dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> {{ auth()->user()->name }}
                            @if(auth()->user()->resource)
                            <small>({{ auth()->user()->resource->name }})</small>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('users.profile') }}">
                                    <i class="fas fa-user"></i> Profilo
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                                <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Content -->
            <div class="content-container">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
    <!-- Responsive JavaScript -->
    <script src="{{ asset('js/responsive.js') }}"></script>
    
    <!-- CSRF Token per AJAX -->
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    @stack('scripts')
    @yield('scripts')
</body>
</html>