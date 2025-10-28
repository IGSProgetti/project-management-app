<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2196F3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
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
        <div class="sidebar">
            <div class="logo">
                <h2><i class="fas fa-briefcase"></i> IGS Project</h2>
            </div>
            <ul class="nav-menu">
                <li>
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                @if(auth()->user()->isAdmin())
                    <li>
                        <a href="{{ route('resources.index') }}" class="nav-link {{ request()->routeIs('resources.*') ? 'active' : '' }}">
                            <i class="fas fa-users"></i>
                            <span>Gestione Risorse</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('manager.dashboard') }}" class="nav-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Manager Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Separatore visivo -->
                    <li class="nav-separator">
                        <hr class="my-2">
                    </li>

                    <li>
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') && !request()->routeIs('users.profile') ? 'active' : '' }}">
                            <i class="fas fa-user-cog"></i>
                            <span>Gestione Utenti</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('hours.index') }}" class="nav-link {{ request()->routeIs('hours.index') ? 'active' : '' }}">
                            <i class="fas fa-clock"></i>
                            <span>Gestione Orario</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('daily-hours.index') }}" class="nav-link {{ request()->routeIs('daily-hours.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar-day"></i>
                            <span>Ore Giornaliere</span>
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                            <i class="fas fa-building"></i>
                            <span>Gestione Clienti</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                            <i class="fas fa-project-diagram"></i>
                            <span>Gestione Progetti</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="{{ route('areas.index') }}" class="nav-link {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                            <i class="fas fa-layer-group"></i>
                            <span>Gestione Aree</span>
                        </a>
                    </li>
                @endif
                
                <li>
                    <a href="{{ route('activities.index') }}" class="nav-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">
                        <i class="fas fa-tasks"></i>
                        <span>Gestione Attività</span>
                    </a>
                </li>
                
                <li>
                    <a href="{{ route('tasks.index') }}" class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Gestione Task</span>
                    </a>
                </li>
                
                <li>
                    <a href="{{ route('calendar.index') }}" class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Calendario</span>
                    </a>
                </li>

                <!-- Separatore prima del profilo -->
                <li class="nav-separator">
                    <hr class="my-2">
                </li>
                
                <li>
                    <a href="{{ route('users.profile') }}" class="nav-link {{ request()->routeIs('users.profile') ? 'active' : '' }}">
                        <i class="fas fa-user"></i>
                        <span>Il Mio Profilo</span>
                    </a>
                </li>
                
                <li>
                    <a href="{{ route('logout') }}" class="nav-link"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header con user info -->
            <div class="header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-0">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="user-info d-none d-md-flex align-items-center">
                        <span class="me-2">
                            <i class="fas fa-user-circle"></i>
                            {{ auth()->user()->name }}
                        </span>
                        @if(auth()->user()->isAdmin())
                            <span class="badge bg-primary">Admin</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-wrapper">
                <!-- Messaggi di successo/errore -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attenzione!</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Main Content Yield -->
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Responsive JS - IMPORTANTE: caricare prima di altri script -->
<script src="{{ asset('js/responsive.js') }}"></script>

@stack('scripts')
</body>
</html>