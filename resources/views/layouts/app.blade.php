<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Portfolio - Full Stack Developer')</title>
    <meta name="description"
        content="@yield('description', 'Professional portfolio showcasing web development projects and technical expertise')">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- SEO Meta Tags -->
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">

    <!-- Sitemap reference -->
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">

    <!-- Open Graph Meta Tags -->
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Portfolio - Full Stack Developer')">
    <meta property="og:description"
        content="@yield('description', 'Professional portfolio showcasing web development projects and technical expertise')">
    <meta property="og:image" content="@yield('og_image', asset('og-image.png'))">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @hasSection('og_published_time')
    <meta property="article:published_time" content="@yield('og_published_time')">
    @endif

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', 'Portfolio - Full Stack Developer')">
    <meta name="twitter:description" content="@yield('description', 'Professional portfolio showcasing web development projects and technical expertise')">
    <meta name="twitter:image" content="@yield('og_image', asset('og-image.png'))">

    @stack('meta')

    <!-- Dark Mode Flash Prevention -->
    <script>
        if (localStorage.theme === 'dark' || (!localStorage.theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    <!-- Structured Data -->
    <x-schema.organization />
    <x-schema.website />
    @stack('schema')
</head>

<body class="font-satoshi antialiased bg-soft-light dark:bg-midnight text-midnight dark:text-soft-light">
    @include('partials.announcement-banner')
    <!-- Navigation -->
    <nav class="navbar fixed w-full z-50 top-0 bg-soft-light/80 dark:bg-midnight/80 backdrop-blur-md border-b border-soft dark:border-ocean">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('home') }}" class="text-2xl font-bold text-midnight dark:text-soft-light hover:text-teal dark:hover:text-teal transition-colors">
                        Portfolio
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                            Home
                        </a>
                        @feature('nav.portfolio')
                        <a href="{{ route('portfolio.index') }}"
                            class="nav-link {{ request()->routeIs('portfolio.*') ? 'active' : '' }}">
                            Portfolio
                        </a>
                        @endfeature
                        @feature('nav.blog')
                        <a href="{{ route('blog.index') }}"
                            class="nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}">
                            Blog
                        </a>
                        @endfeature
                        @feature('nav.sports')
                        <a href="{{ route('sports.index') }}"
                            class="nav-link {{ request()->routeIs('sports.*') ? 'active' : '' }}">
                            Sports
                        </a>
                        @endfeature
                        <a href="{{ route('about') }}"
                            class="nav-link {{ request()->routeIs('about.*') ? 'active' : '' }}">
                            About Me
                        </a>
                        @feature('nav.contact')
                        <a href="{{ route('contact') }}"
                            class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">
                            Contact
                        </a>
                        @endfeature
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="nav-link">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute right-0 mt-2 w-80 bg-soft-light dark:bg-ocean rounded-lg shadow-lg py-4 px-4 z-50 border border-soft dark:border-ocean-light">
                                <form method="GET" action="{{ route('blog.search') }}">
                                    <input type="text" name="q" placeholder="Search articles..."
                                        class="w-full px-4 py-2 border border-soft dark:border-ocean-light rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent bg-white dark:bg-midnight text-midnight dark:text-soft-light placeholder-soft-dark"
                                        autocomplete="off">
                                </form>
                            </div>
                        </div>

                        {{-- Dark Mode Toggle --}}
                        @feature('feature.dark_mode_toggle')
                        <button x-data="darkMode" @click="toggle()" class="nav-link" aria-label="Toggle dark mode">
                            {{-- Sun icon (shown in dark mode) --}}
                            <svg x-show="isDark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            {{-- Moon icon (shown in light mode) --}}
                            <svg x-show="!isDark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        </button>
                        @endfeature
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button"
                        class="mobile-menu-button bg-soft dark:bg-ocean inline-flex items-center justify-center p-2 rounded-md text-midnight dark:text-soft-light hover:bg-soft-dark dark:hover:bg-ocean-light transition-colors">
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="mobile-menu hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-soft-light dark:bg-midnight border-t border-soft dark:border-ocean">
                <a href="{{ route('home') }}" class="mobile-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                    Home
                </a>
                @feature('nav.portfolio')
                <a href="{{ route('portfolio.index') }}"
                    class="mobile-nav-link {{ request()->routeIs('portfolio.*') ? 'active' : '' }}">
                    Portfolio
                </a>
                @endfeature
                @feature('nav.blog')
                <a href="{{ route('blog.index') }}"
                    class="mobile-nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}">
                    Blog
                </a>
                @endfeature
                @feature('nav.sports')
                <a href="{{ route('sports.index') }}"
                    class="mobile-nav-link {{ request()->routeIs('sports.*') ? 'active' : '' }}">
                    Sports
                </a>
                @endfeature
                <a href="{{ route('about') }}"
                    class="mobile-nav-link {{ request()->routeIs('about.*') ? 'active' : '' }}">
                    About Me
                </a>
                @feature('nav.contact')
                <a href="{{ route('contact') }}"
                    class="mobile-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">
                    Contact
                </a>
                @endfeature

                {{-- Mobile Dark Mode Toggle --}}
                @feature('feature.dark_mode_toggle')
                <button x-data="darkMode" @click="toggle()" class="mobile-nav-link flex items-center justify-between w-full">
                    <span>Dark Mode</span>
                    <span x-text="isDark ? 'On' : 'Off'" class="text-sm text-soft-dark"></span>
                </button>
                @endfeature
            </div>
        </div>
    </nav>

    @if(request()->attributes->get('_flag_hidden'))
    <div class="sticky top-0 z-50 bg-sunset/90 backdrop-blur-sm text-midnight text-center text-xs font-semibold py-2 px-4">
        &#9888; This page is hidden from the public &mdash; you're previewing it as admin
    </div>
    @endif

    <!-- Main Content -->
    <main class="pt-16">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-midnight text-soft-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-2xl font-bold mb-4">Let's Work Together</h3>
                    <p class="text-soft mb-6">
                        Ready to bring your ideas to life? Let's discuss your next project.
                    </p>
                    <a href="{{ route('contact') }}"
                        class="inline-flex items-center px-6 py-3 bg-teal hover:bg-teal-dark text-midnight font-semibold rounded-full transition-all duration-300 hover:shadow-glow">
                        Get In Touch
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>

                <div>
                    <h4 class="text-lg font-semibold mb-4 text-soft-light">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-soft hover:text-teal transition-colors">Home</a></li>
                        @feature('nav.portfolio')
                        <li><a href="{{ route('portfolio.index') }}" class="text-soft hover:text-teal transition-colors">Portfolio</a></li>
                        @endfeature
                        @feature('nav.blog')
                        <li><a href="{{ route('blog.index') }}" class="text-soft hover:text-teal transition-colors">Blog</a></li>
                        @endfeature
                        @feature('nav.sports')
                        <li><a href="{{ route('sports.index') }}" class="text-soft hover:text-teal transition-colors">Live Sports</a></li>
                        @endfeature
                        @feature('nav.contact')
                        <li><a href="{{ route('contact') }}" class="text-soft hover:text-teal transition-colors">Contact</a></li>
                        @endfeature
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-semibold mb-4 text-soft-light">Connect</h4>
                    <div class="flex flex-wrap gap-4">
                        @if(setting('social_twitter'))
                        <a href="{{ setting('social_twitter') }}" target="_blank" rel="noopener" aria-label="Twitter / X" class="text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        @endif
                        @if(setting('social_linkedin'))
                        <a href="{{ setting('social_linkedin') }}" target="_blank" rel="noopener" aria-label="LinkedIn" class="text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                        @endif
                        @if(setting('social_github'))
                        <a href="{{ setting('social_github') }}" target="_blank" rel="noopener" aria-label="GitHub" class="text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        @endif
                        @if(setting('social_youtube'))
                        <a href="{{ setting('social_youtube') }}" target="_blank" rel="noopener" aria-label="YouTube" class="text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-ocean mt-8 pt-8 text-center text-soft">
                <p>&copy; {{ date('Y') }} Portfolio. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>

</html>
