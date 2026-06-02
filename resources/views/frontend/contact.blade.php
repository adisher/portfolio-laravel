@extends('layouts.app')

@section('title', 'Contact - Get In Touch')
@section('description', 'Get in touch to discuss your next project or collaboration opportunity')

@section('content')
{{-- Page Header --}}
<section class="relative bg-midnight dark:bg-midnight-dark text-soft-light section-padding overflow-hidden">
    {{-- Floating shapes --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 right-10 w-72 h-72 bg-teal/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-10 w-64 h-64 bg-sunset/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <span class="badge badge-teal mb-4 animate-up">Contact</span>
        <h1 class="animate-up text-4xl lg:text-5xl font-black mb-4">
            Let's Work <span class="text-gradient">Together</span>
        </h1>
        <p class="animate-up text-lg text-soft max-w-2xl mx-auto">
            Have a project in mind? I'd love to hear about it and discuss how we can bring your ideas to life.
        </p>
    </div>
</section>

{{-- Contact Content --}}
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            {{-- Contact Form --}}
            <div class="animate-up">
                <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-6">
                    Send Me a Message
                </h2>

                @if(session('success'))
                <div
                    class="mb-6 p-4 bg-teal/10 border border-teal/30 text-teal-dark dark:text-teal rounded-lg">
                    {{ session('success') }}
                </div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-midnight dark:text-soft mb-2">
                                Full Name *
                            </label>
                            <input type="text" id="name" name="name" required value="{{ old('name') }}"
                                class="w-full px-4 py-3 border border-soft dark:border-ocean-light rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent bg-white dark:bg-midnight-light text-midnight dark:text-soft-light @error('name') border-red-500 @enderror">
                            @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-midnight dark:text-soft mb-2">
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" required value="{{ old('email') }}"
                                class="w-full px-4 py-3 border border-soft dark:border-ocean-light rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent bg-white dark:bg-midnight-light text-midnight dark:text-soft-light @error('email') border-red-500 @enderror">
                            @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-midnight dark:text-soft mb-2">
                            Subject
                        </label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                            class="w-full px-4 py-3 border border-soft dark:border-ocean-light rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent bg-white dark:bg-midnight-light text-midnight dark:text-soft-light @error('subject') border-red-500 @enderror">
                        @error('subject')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-midnight dark:text-soft mb-2">
                            Message *
                        </label>
                        <textarea id="message" name="message" rows="6" required
                            class="w-full px-4 py-3 border border-soft dark:border-ocean-light rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent bg-white dark:bg-midnight-light text-midnight dark:text-soft-light resize-none @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                        @error('message')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full btn-primary text-lg py-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Send Message
                    </button>
                </form>
            </div>

            {{-- Contact Information --}}
            <div class="animate-up">
                <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-6">
                    Get In Touch
                </h2>

                <div class="space-y-6 mb-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div
                                class="w-12 h-12 bg-teal/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-teal" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-midnight dark:text-soft-light">Email</h3>
                            <p class="text-soft-dark dark:text-soft">
                                {{ $contactSettings['email'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-sunset/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-sunset" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-midnight dark:text-soft-light">Phone</h3>
                            <p class="text-soft-dark dark:text-soft">
                                {{ $contactSettings['phone'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-teal/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-midnight dark:text-soft-light">Location</h3>
                            <p class="text-soft-dark dark:text-soft">
                                {{ $contactSettings['location'] }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-sunset/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-sunset" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-midnight dark:text-soft-light">Response Time</h3>
                            <p class="text-soft-dark dark:text-soft">
                                {{ $contactSettings['response_time'] }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Social Links --}}
                <div class="border-t border-soft/20 dark:border-ocean pt-8">
                    <h3 class="text-lg font-medium text-midnight dark:text-soft-light mb-4">
                        Connect With Me
                    </h3>
                    <div class="flex space-x-4">
                        @if($contactSettings['social_twitter'])
                        <a href="{{ $contactSettings['social_twitter'] }}" target="_blank"
                            class="w-10 h-10 bg-midnight dark:bg-ocean text-soft-light rounded-lg flex items-center justify-center hover:bg-teal hover:text-midnight transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                            </svg>
                        </a>
                        @endif

                        @if($contactSettings['social_linkedin'])
                        <a href="{{ $contactSettings['social_linkedin'] }}" target="_blank"
                            class="w-10 h-10 bg-midnight dark:bg-ocean text-soft-light rounded-lg flex items-center justify-center hover:bg-teal hover:text-midnight transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                            </svg>
                        </a>
                        @endif

                        @if($contactSettings['social_github'])
                        <a href="{{ $contactSettings['social_github'] }}" target="_blank"
                            class="w-10 h-10 bg-midnight dark:bg-ocean text-soft-light rounded-lg flex items-center justify-center hover:bg-teal hover:text-midnight transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                            </svg>
                        </a>
                        @endif

                        @if($contactSettings['social_behance'])
                        <a href="{{ $contactSettings['social_behance'] }}" target="_blank"
                            class="w-10 h-10 bg-midnight dark:bg-ocean text-soft-light rounded-lg flex items-center justify-center hover:bg-teal hover:text-midnight transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M0 7.5V16.2C0 17.8 1.2 19 2.8 19H8.8C10.4 19 11.6 17.8 11.6 16.2V7.5C11.6 5.9 10.4 4.7 8.8 4.7H2.8C1.2 4.7 0 5.9 0 7.5ZM2.3 7.8C2.3 7.4 2.6 7.1 3 7.1H8.6C9 7.1 9.3 7.4 9.3 7.8V15.9C9.3 16.3 9 16.6 8.6 16.6H3C2.6 16.6 2.3 16.3 2.3 15.9V7.8ZM24 9.9C24 8.3 22.8 7.1 21.2 7.1H15.2C13.6 7.1 12.4 8.3 12.4 9.9V16.2C12.4 17.8 13.6 19 15.2 19H21.2C22.8 19 24 17.8 24 16.2V9.9ZM21.7 10.2C21.7 9.8 21.4 9.5 21 9.5H15.4C15 9.5 14.7 9.8 14.7 10.2V15.9C14.7 16.3 15 16.6 15.4 16.6H21C21.4 16.6 21.7 16.3 21.7 15.9V10.2Z" />
                            </svg>
                        </a>
                        @endif

                        @if($contactSettings['social_dribbble'])
                        <a href="{{ $contactSettings['social_dribbble'] }}" target="_blank"
                            class="w-10 h-10 bg-midnight dark:bg-ocean text-soft-light rounded-lg flex items-center justify-center hover:bg-teal hover:text-midnight transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 0C5.374 0 0 5.374 0 12s5.374 12 12 12 12-5.374 12-12S18.626 0 12 0zm7.568 5.302c1.4 1.5 2.252 3.5 2.299 5.691-.3-.1-3.3-.6-6.4-.3-.1-.3-.2-.5-.4-.8-.2-.3-.4-.7-.6-1C16.068 7.793 18.8 6.802 19.568 5.302zM12 2.2c2.4 0 4.6.9 6.3 2.4-.6 1.2-2.9 2.1-5.4 3-.8-1.5-1.7-2.7-2.7-3.7C11.2 2.3 11.6 2.2 12 2.2zM8.4 4.6c1 1 1.8 2.2 2.6 3.6-3.4.9-6.4 1-7.1 1C4.6 7.1 6.2 5.5 8.4 4.6zM2.1 12v-.4c.7 0 4.4-.1 8.1-1.1.2.4.4.7.5 1.1-3.4 1.1-5.7 3.5-6.9 5.4C2.6 15.9 2.1 14 2.1 12zM12 21.9c-2.1 0-4.1-.7-5.7-1.9 1-1.6 2.9-3.8 6-4.8 1 2.6 1.4 4.8 1.5 5.5C13.2 21.8 12.6 21.9 12 21.9zM15.7 20.2c-.1-.6-.4-2.6-1.3-5.2 2.8-.4 5.3.3 5.6.4C19.6 17.6 17.9 19.2 15.7 20.2z" />
                            </svg>
                        </a>
                        @endif

                        @if($contactSettings['social_instagram'])
                        <a href="{{ $contactSettings['social_instagram'] }}" target="_blank"
                            class="w-10 h-10 bg-midnight dark:bg-ocean text-soft-light rounded-lg flex items-center justify-center hover:bg-sunset hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                            </svg>
                        </a>
                        @endif
                    </div>
                </div>

                {{-- FAQ Section with Alpine.js --}}
                <div class="mt-12" x-data="{ openFaq: null }">
                    <h3 class="text-lg font-medium text-midnight dark:text-soft-light mb-6">
                        Frequently Asked Questions
                    </h3>
                    <div class="space-y-3">
                        <div class="card overflow-hidden">
                            <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full flex items-center justify-between p-4 text-left">
                                <h4 class="font-medium text-midnight dark:text-soft-light">
                                    What's your typical project timeline?
                                </h4>
                                <svg class="w-5 h-5 text-teal transition-transform duration-200" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="openFaq === 1" x-collapse>
                                <p class="px-4 pb-4 text-sm text-soft-dark dark:text-soft">
                                    Project timelines vary based on complexity, but most web applications take 2-8 weeks from start to finish.
                                </p>
                            </div>
                        </div>

                        <div class="card overflow-hidden">
                            <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full flex items-center justify-between p-4 text-left">
                                <h4 class="font-medium text-midnight dark:text-soft-light">
                                    Do you work with international clients?
                                </h4>
                                <svg class="w-5 h-5 text-teal transition-transform duration-200" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="openFaq === 2" x-collapse>
                                <p class="px-4 pb-4 text-sm text-soft-dark dark:text-soft">
                                    Absolutely! I work with clients worldwide and am flexible with different time zones for communication.
                                </p>
                            </div>
                        </div>

                        <div class="card overflow-hidden">
                            <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full flex items-center justify-between p-4 text-left">
                                <h4 class="font-medium text-midnight dark:text-soft-light">
                                    What technologies do you specialize in?
                                </h4>
                                <svg class="w-5 h-5 text-teal transition-transform duration-200" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="openFaq === 3" x-collapse>
                                <p class="px-4 pb-4 text-sm text-soft-dark dark:text-soft">
                                    I specialize in Laravel, Python, JavaScript, Vue.js, React.js, Next.js, Node.js, Express and other modern web development technologies.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="bg-gradient-to-br from-midnight via-ocean to-midnight-dark text-soft-light section-padding relative overflow-hidden">
    {{-- Background Elements --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="float-element absolute top-10 left-10 w-32 h-32 bg-teal/20 rounded-full blur-2xl"></div>
        <div class="float-element absolute bottom-10 right-10 w-40 h-40 bg-sunset/20 rounded-full blur-2xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="animate-up text-3xl lg:text-4xl font-bold mb-4">
            Ready to Start Your <span class="text-gradient">Project</span>?
        </h2>
        <p class="animate-up text-lg text-soft mb-8 max-w-2xl mx-auto">
            Let's discuss your requirements and see how I can help bring your vision to life.
        </p>
        <div class="animate-up flex flex-col sm:flex-row justify-center gap-4">
            <a href="#" class="btn-primary">
                Schedule a Call
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </a>
            <a href="{{ route('portfolio.index') }}" class="btn-secondary border-soft/30 text-soft-light hover:border-teal hover:text-teal">
                View My Work
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sending...
        `;

        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, 5000);
    });
});
</script>
@endpush