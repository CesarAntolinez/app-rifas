<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rifas') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-64 bg-indigo-900 text-white flex flex-col flex-shrink-0 print:hidden">
                <div class="px-6 py-5 border-b border-indigo-800">
                    <a href="{{ route('dashboard') }}" class="text-lg font-bold text-white">🎲 Rifas</a>
                </div>

                <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                        📊 Dashboard
                    </a>

                    @auth
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('users.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('users.*') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                                👥 Organizadores
                            </a>
                            <a href="{{ route('raffles.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('raffles.*') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                                🎟️ Todos los sorteos
                            </a>
                        @else
                            <a href="{{ route('raffles.index') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('raffles.*') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                                🎟️ Mis sorteos
                            </a>
                        @endif

                        <a href="{{ route('analytics.dashboard') }}" class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('analytics.*') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-800 hover:text-white' }}">
                            📈 Analíticas
                        </a>
                    @endauth
                </nav>

                <div class="px-4 py-4 border-t border-indigo-800">
                    <a href="{{ route('profile') }}" class="flex items-center px-3 py-2 text-sm text-indigo-200 hover:bg-indigo-800 hover:text-white rounded-md">
                        ⚙️ Perfil
                    </a>
                </div>
            </aside>

            <!-- Main content -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Topbar -->
                <header class="bg-white shadow-sm flex items-center justify-between px-6 py-3 print:hidden">
                    @if (isset($header))
                        <div>{{ $header }}</div>
                    @else
                        <div></div>
                    @endif

                    <div class="flex items-center space-x-4">
                        @auth
                            <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Salir</button>
                            </form>
                        @endauth
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto">
                    @if (session('success'))
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                            <x-alert type="success" :message="session('success')" />
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                            <x-alert type="error" :message="session('error')" />
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>

