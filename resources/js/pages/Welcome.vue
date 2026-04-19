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
    <Head title="Welcome" />

    <div class="min-h-screen bg-white text-slate-900">

        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/90 backdrop-blur-sm">
            <div class="container mx-auto px-6 py-4 flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-slate-950 rounded-lg flex items-center justify-center">
                        <LibraryIcon class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <div class="text-base font-bold text-slate-900 tracking-tight">City Library</div>
                        <div class="text-[10px] text-slate-400 tracking-widest uppercase">Knowledge Hub</div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex items-center gap-2">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors"
                    >
                        Log in
                    </Link>
                    <Link v-if="canRegister" :href="route('register')">
                        <Button class="bg-slate-950 hover:bg-slate-800 text-white text-sm font-medium rounded-lg h-9 px-4">
                            Get Started
                        </Button>
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-6 pt-24 pb-28">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 border border-slate-200 rounded-full px-3 py-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-slate-600 tracking-wide">Where Stories Come Alive</span>
                    </div>

                    <h1 class="text-6xl lg:text-7xl font-bold text-slate-900 leading-[1.05] tracking-tight">
                        A World of<br />
                        <span class="text-slate-400">Knowledge</span><br />
                        Awaits You
                    </h1>

                    <p class="text-lg text-slate-500 max-w-md leading-relaxed">
                        Access thousands of books, connect with fellow readers, and discover your next favorite story.
                    </p>

                    <div class="flex gap-3 pt-2">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg" class="bg-slate-950 hover:bg-slate-800 text-white rounded-xl h-12 px-6 font-medium">
                                <BookOpenIcon class="mr-2 h-4 w-4" />
                                Start Reading
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline" class="border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl h-12 px-6 font-medium">
                                Sign In
                            </Button>
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 pt-6 border-t border-slate-100">
                        <div>
                            <div class="text-3xl font-bold text-slate-900">12K+</div>
                            <div class="text-sm text-slate-400 mt-0.5">Books</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-slate-900">850+</div>
                            <div class="text-sm text-slate-400 mt-0.5">Members</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-slate-900">50+</div>
                            <div class="text-sm text-slate-400 mt-0.5">Categories</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Book Cards -->
                <div class="relative hidden lg:flex items-center justify-center h-[480px]">
                    <!-- Background circle -->
                    <div class="absolute w-80 h-80 rounded-full border border-slate-100 bg-slate-50"></div>
                    <div class="absolute w-64 h-64 rounded-full border border-slate-100"></div>

                    <!-- Book 1 - left -->
                    <div class="absolute left-4 top-12 w-36 bg-slate-950 rounded-xl shadow-xl p-5 flex flex-col justify-between h-52 transform -rotate-6 hover:-rotate-3 transition-transform duration-300">
                        <BookOpenIcon class="h-6 w-6 text-slate-400" />
                        <div>
                            <div class="text-slate-500 text-[10px] uppercase tracking-widest mb-1">Fiction</div>
                            <div class="font-bold text-white text-sm">Classic Tales</div>
                        </div>
                    </div>

                    <!-- Book 2 - center/top -->
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-40 bg-white border-2 border-slate-200 rounded-xl shadow-xl p-5 flex flex-col justify-between h-60 transform rotate-2 hover:rotate-0 transition-transform duration-300 z-10">
                        <BookOpenIcon class="h-6 w-6 text-slate-400" />
                        <div>
                            <div class="text-slate-400 text-[10px] uppercase tracking-widest mb-1">Romance</div>
                            <div class="font-bold text-slate-900 text-sm">Love Stories</div>
                        </div>
                    </div>

                    <!-- Book 3 - right -->
                    <div class="absolute right-4 top-20 w-36 bg-slate-100 border border-slate-200 rounded-xl shadow-lg p-5 flex flex-col justify-between h-48 transform rotate-6 hover:rotate-3 transition-transform duration-300">
                        <BookOpenIcon class="h-6 w-6 text-slate-400" />
                        <div>
                            <div class="text-slate-400 text-[10px] uppercase tracking-widest mb-1">Mystery</div>
                            <div class="font-bold text-slate-700 text-sm">Thrillers</div>
                        </div>
                    </div>

                    <!-- Book 4 - bottom -->
                    <div class="absolute bottom-8 left-1/2 -translate-x-20 w-44 bg-slate-800 rounded-xl shadow-xl p-5 flex flex-col justify-between h-44 transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                        <BookOpenIcon class="h-6 w-6 text-slate-500" />
                        <div>
                            <div class="text-slate-500 text-[10px] uppercase tracking-widest mb-1">Academic</div>
                            <div class="font-bold text-white text-sm">Science & Tech</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="bg-slate-950 py-24">
            <div class="container mx-auto px-6">
                <div class="text-center mb-14">
                    <p class="text-slate-500 text-xs uppercase tracking-widest mb-3">Why choose us</p>
                    <h2 class="text-4xl font-bold text-white tracking-tight">
                        Everything for your<br />reading journey
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Feature 1 -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 hover:border-slate-700 transition-colors duration-200">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-6">
                            <BookOpenIcon class="h-5 w-5 text-slate-950" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">Extensive Collection</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Browse through thousands of books across fiction, non-fiction, academic, and more.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 hover:border-slate-700 transition-colors duration-200">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-6">
                            <ClockIcon class="h-5 w-5 text-slate-950" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">Easy Borrowing</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Simple checkout and renewal process with flexible borrowing periods.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 hover:border-slate-700 transition-colors duration-200">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-6">
                            <TrendingUpIcon class="h-5 w-5 text-slate-950" />
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2">Track Your Reading</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            Keep track of your reading history and discover personalized recommendations.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-6 py-24">
            <div class="bg-slate-950 rounded-3xl p-14 lg:p-20 text-center relative overflow-hidden">
                <!-- Subtle grid -->
                <div class="absolute inset-0 opacity-[0.04] rounded-3xl"
                    style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
                </div>
                <!-- Decorative circles -->
                <div class="absolute top-0 right-0 w-64 h-64 rounded-full border border-slate-800 translate-x-1/3 -translate-y-1/3 opacity-60"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full border border-slate-800 -translate-x-1/3 translate-y-1/3 opacity-60"></div>

                <div class="relative z-10">
                    <p class="text-slate-500 text-xs uppercase tracking-widest mb-4">Join today</p>
                    <h2 class="text-4xl lg:text-5xl font-bold text-white tracking-tight mb-5">
                        Ready to start your<br />reading journey?
                    </h2>
                    <p class="text-slate-400 text-base mb-10 max-w-xl mx-auto leading-relaxed">
                        Join our community of book lovers and unlock access to thousands of amazing titles.
                    </p>
                    <div class="flex gap-3 justify-center flex-wrap">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg" class="bg-white text-slate-950 hover:bg-slate-100 rounded-xl h-12 px-6 font-semibold">
                                <BookOpenIcon class="mr-2 h-4 w-4" />
                                Create Free Account
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline" class="border border-slate-700 hover:bg-slate-800 hover:text-white rounded-xl h-12 px-6 font-medium">
                                Sign In
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-100">
            <div class="container mx-auto px-6 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-slate-950 rounded-lg flex items-center justify-center">
                            <LibraryIcon class="h-4 w-4 text-white" />
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900">City Library</div>
                        </div>
                    </div>
                    <div class="text-xs text-slate-400">
                        © 2026 City Library. All rights reserved.
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>