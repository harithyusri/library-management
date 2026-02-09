<script setup lang="ts">
import { route } from "ziggy-js";
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LibraryIcon, UsersIcon, BookmarkIcon, TrendingUpIcon, ClockIcon } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canLogin: boolean;
        canRegister: boolean;
    }>(),
    {
        canLogin: true,
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <div class="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-rose-50 dark:from-slate-950 dark:to-slate-900">
        <!-- Header -->
        <header class="border-b border-amber-200/50 dark:border-amber-900/50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
            <div class="container mx-auto px-4 py-4 flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-amber-700 to-orange-700 rounded-lg">
                        <LibraryIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-amber-900 dark:text-amber-100">City Library</div>
                        <div class="text-xs text-amber-700 dark:text-amber-400">Knowledge Hub</div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-4 py-2 text-sm font-medium text-amber-900 dark:text-amber-100 hover:text-amber-700 dark:hover:text-amber-300 transition-colors"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                    >
                        <Button class="bg-gradient-to-r from-amber-700 to-orange-700 hover:from-amber-800 hover:to-orange-800 text-white">
                            Get Started
                        </Button>
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-20 lg:py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-full px-4 py-2 border border-amber-300/50 dark:border-amber-700/50">
                        <BookmarkIcon class="h-4 w-4 text-amber-700 dark:text-amber-400" />
                        <span class="text-sm font-medium text-amber-900 dark:text-amber-100">Where Stories Come Alive</span>
                    </div>

                    <h1 class="text-5xl lg:text-7xl font-bold text-amber-900 dark:text-amber-100 leading-tight">
                        A World of<br />
                        <span class="bg-gradient-to-r from-amber-700 to-orange-700 bg-clip-text text-transparent">Knowledge</span><br />
                        Awaits You
                    </h1>

                    <p class="text-xl text-amber-800 dark:text-amber-300 max-w-lg">
                        Access thousands of books, connect with fellow readers, and discover your next favorite story.
                    </p>

                    <div class="flex gap-4">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg" class="bg-gradient-to-r from-amber-700 to-orange-700 hover:from-amber-800 hover:to-orange-800 text-white shadow-lg">
                                <BookOpenIcon class="mr-2 h-5 w-5" />
                                Start Reading
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline" class="border-2 border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-100 hover:bg-amber-100 dark:hover:bg-amber-900/20">
                                Sign In
                            </Button>
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 pt-8">
                        <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-amber-200/50 dark:border-amber-800/50">
                            <BookOpenIcon class="h-6 w-6 text-amber-700 dark:text-amber-400 mb-2" />
                            <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">12K+</div>
                            <div class="text-xs text-amber-700 dark:text-amber-400">Books</div>
                        </div>
                        <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-amber-200/50 dark:border-amber-800/50">
                            <UsersIcon class="h-6 w-6 text-amber-700 dark:text-amber-400 mb-2" />
                            <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">850+</div>
                            <div class="text-xs text-amber-700 dark:text-amber-400">Members</div>
                        </div>
                        <div class="bg-white/60 dark:bg-slate-800/60 backdrop-blur-sm rounded-xl p-4 border border-amber-200/50 dark:border-amber-800/50">
                            <LibraryIcon class="h-6 w-6 text-amber-700 dark:text-amber-400 mb-2" />
                            <div class="text-2xl font-bold text-amber-900 dark:text-amber-100">50+</div>
                            <div class="text-xs text-amber-700 dark:text-amber-400">Categories</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Decorative -->
                <div class="relative hidden lg:block">
                    <div class="relative">
                        <!-- Decorative book pattern -->
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Book 1 -->
                            <div class="bg-gradient-to-br from-amber-600 to-orange-600 rounded-lg h-64 shadow-xl transform rotate-6 hover:rotate-12 transition-transform">
                                <div class="h-full p-6 flex flex-col justify-between text-white">
                                    <BookOpenIcon class="h-8 w-8" />
                                    <div>
                                        <div class="text-xs opacity-80">Fiction</div>
                                        <div class="font-bold mt-1">Classic Tales</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Book 2 -->
                            <div class="bg-gradient-to-br from-rose-600 to-pink-600 rounded-lg h-72 shadow-xl transform -rotate-3 hover:rotate-0 transition-transform mt-8">
                                <div class="h-full p-6 flex flex-col justify-between text-white">
                                    <BookOpenIcon class="h-8 w-8" />
                                    <div>
                                        <div class="text-xs opacity-80">Romance</div>
                                        <div class="font-bold mt-1">Love Stories</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Book 3 -->
                            <div class="bg-gradient-to-br from-orange-700 to-amber-700 rounded-lg h-60 shadow-xl transform rotate-3 hover:rotate-6 transition-transform mt-4">
                                <div class="h-full p-6 flex flex-col justify-between text-white">
                                    <BookOpenIcon class="h-8 w-8" />
                                    <div>
                                        <div class="text-xs opacity-80">Mystery</div>
                                        <div class="font-bold mt-1">Thrillers</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="container mx-auto px-4 py-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-sm">
            <div class="text-center mb-12">
                <h2 class="text-3xl lg:text-4xl font-bold text-amber-900 dark:text-amber-100 mb-4">
                    Why Choose Our Library?
                </h2>
                <p class="text-lg text-amber-700 dark:text-amber-400">
                    Everything you need for your reading journey
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg border-2 border-amber-200/50 dark:border-amber-800/50 hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-700 to-orange-700 rounded-xl flex items-center justify-center mb-4">
                        <BookOpenIcon class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="text-xl font-bold text-amber-900 dark:text-amber-100 mb-2">Extensive Collection</h3>
                    <p class="text-amber-700 dark:text-amber-400">
                        Browse through thousands of books across fiction, non-fiction, academic, and more.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg border-2 border-amber-200/50 dark:border-amber-800/50 hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-700 to-amber-700 rounded-xl flex items-center justify-center mb-4">
                        <ClockIcon class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="text-xl font-bold text-amber-900 dark:text-amber-100 mb-2">Easy Borrowing</h3>
                    <p class="text-amber-700 dark:text-amber-400">
                        Simple checkout and renewal process with flexible borrowing periods.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-8 shadow-lg border-2 border-amber-200/50 dark:border-amber-800/50 hover:shadow-xl transition-shadow">
                    <div class="w-12 h-12 bg-gradient-to-br from-rose-700 to-orange-700 rounded-xl flex items-center justify-center mb-4">
                        <TrendingUpIcon class="h-6 w-6 text-white" />
                    </div>
                    <h3 class="text-xl font-bold text-amber-900 dark:text-amber-100 mb-2">Track Your Reading</h3>
                    <p class="text-amber-700 dark:text-amber-400">
                        Keep track of your reading history and discover personalized recommendations.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-4 py-20">
            <div class="bg-gradient-to-r from-amber-700 via-orange-700 to-rose-700 rounded-3xl p-12 lg:p-20 text-center shadow-2xl">
                <h2 class="text-4xl lg:text-5xl font-bold text-white mb-6">
                    Ready to Start Your<br />Reading Journey?
                </h2>
                <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                    Join our community of book lovers today and unlock access to thousands of amazing titles.
                </p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <Link v-if="canRegister" :href="route('register')">
                        <Button size="lg" class="bg-white text-amber-900 hover:bg-amber-50 shadow-lg">
                            <BookOpenIcon class="mr-2 h-5 w-5" />
                            Create Free Account
                        </Button>
                    </Link>
                    <Link v-if="canLogin" :href="route('login')">
                        <Button size="lg" variant="outline" class="border-2 border-white text-white hover:bg-white/10">
                            Sign In
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-amber-200/50 dark:border-amber-900/50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
            <div class="container mx-auto px-4 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gradient-to-br from-amber-700 to-orange-700 rounded-lg">
                            <LibraryIcon class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <div class="text-sm font-bold text-amber-900 dark:text-amber-100">City Library</div>
                            <div class="text-xs text-amber-700 dark:text-amber-400">Enriching minds since 1950</div>
                        </div>
                    </div>
                    <div class="text-sm text-amber-700 dark:text-amber-400">
                        © 2026 City Library. All rights reserved.
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>