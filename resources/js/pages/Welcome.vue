<script setup lang="ts">
import { route } from "ziggy-js";
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LibraryIcon, UsersIcon, BookmarkIcon, TrendingUpIcon, ClockIcon, DoorOpenIcon } from 'lucide-vue-next';

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

    <div class="min-h-screen bg-background text-foreground">

        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-border bg-background/90 backdrop-blur-sm">
            <div class="container mx-auto px-6 py-4 flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-primary rounded-lg flex items-center justify-center">
                        <LibraryIcon class="h-5 w-5 text-primary-foreground" />
                    </div>
                    <div>
                        <div class="text-base font-bold text-foreground tracking-tight">City Library</div>
                        <div class="text-[10px] text-muted-foreground tracking-widest uppercase">Knowledge Hub</div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex items-center gap-2">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Log in
                    </Link>
                    <Link v-if="canRegister" :href="route('register')">
                        <Button class="bg-primary hover:opacity-90 text-primary-foreground text-sm font-medium rounded-lg h-9 px-4">
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
                    <div class="inline-flex items-center gap-2 border border-border rounded-full px-3 py-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-medium text-muted-foreground tracking-wide">Your Complete Library System</span>
                    </div>

                    <h1 class="text-6xl lg:text-7xl font-bold text-foreground leading-[1.05] tracking-tight">
                        A Smarter<br />
                        <span class="text-muted-foreground">Library</span><br />
                        Experience
                    </h1>

                    <p class="text-lg text-muted-foreground max-w-md leading-relaxed">
                        Manage loans, reserve rooms, and explore thousands of books — all in one place.
                    </p>

                    <div class="flex gap-3 pt-2">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg" class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl h-12 px-6 font-medium">
                                <BookOpenIcon class="mr-2 h-4 w-4" />
                                Get Started
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline" class="rounded-xl h-12 px-6 font-medium">
                                Sign In
                            </Button>
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-4 pt-6 border-t border-border">
                        <div>
                            <div class="text-3xl font-bold text-foreground">12K+</div>
                            <div class="text-sm text-muted-foreground mt-0.5">Books</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-foreground">850+</div>
                            <div class="text-sm text-muted-foreground mt-0.5">Members</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-foreground">50+</div>
                            <div class="text-sm text-muted-foreground mt-0.5">Categories</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold text-foreground">10+</div>
                            <div class="text-sm text-muted-foreground mt-0.5">Rooms</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Book Cards -->
                <div class="relative hidden lg:flex items-center justify-center h-[480px]">
                    <div class="absolute w-80 h-80 rounded-full border border-border bg-muted"></div>
                    <div class="absolute w-64 h-64 rounded-full border border-border"></div>

                    <div class="absolute left-4 top-12 w-36 bg-primary rounded-xl shadow-xl p-5 flex flex-col justify-between h-52 transform -rotate-6 hover:-rotate-3 transition-transform duration-300">
                        <BookOpenIcon class="h-6 w-6 text-[#eabcb8]" />
                        <div>
                            <div class="text-[#eabcb8] text-[10px] uppercase tracking-widest mb-1">Fiction</div>
                            <div class="font-bold text-primary-foreground text-sm">Classic Tales</div>
                        </div>
                    </div>

                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-40 bg-card border-2 border-border rounded-xl shadow-xl p-5 flex flex-col justify-between h-60 transform rotate-2 hover:rotate-0 transition-transform duration-300 z-10">
                        <BookOpenIcon class="h-6 w-6 text-muted-foreground" />
                        <div>
                            <div class="text-muted-foreground text-[10px] uppercase tracking-widest mb-1">Romance</div>
                            <div class="font-bold text-foreground text-sm">Love Stories</div>
                        </div>
                    </div>

                    <div class="absolute right-4 top-20 w-36 bg-muted border border-border rounded-xl shadow-lg p-5 flex flex-col justify-between h-48 transform rotate-6 hover:rotate-3 transition-transform duration-300">
                        <BookOpenIcon class="h-6 w-6 text-muted-foreground" />
                        <div>
                            <div class="text-muted-foreground text-[10px] uppercase tracking-widest mb-1">Mystery</div>
                            <div class="font-bold text-foreground text-sm">Thrillers</div>
                        </div>
                    </div>

                    <div class="absolute bottom-8 left-1/2 -translate-x-20 w-44 bg-[#4a2c2a] rounded-xl shadow-xl p-5 flex flex-col justify-between h-44 transform -rotate-3 hover:rotate-0 transition-transform duration-300">
                        <BookOpenIcon class="h-6 w-6 text-[#eabcb8]" />
                        <div>
                            <div class="text-[#eabcb8] text-[10px] uppercase tracking-widest mb-1">Academic</div>
                            <div class="font-bold text-primary-foreground text-sm">Science & Tech</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="bg-primary py-24">
            <div class="container mx-auto px-6">
                <div class="text-center mb-14">
                    <p class="text-[#eabcb8] text-xs uppercase tracking-widest mb-3">What we offer</p>
                    <h2 class="text-4xl font-bold text-primary-foreground tracking-tight">
                        Everything your library<br />needs in one system
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-[#4a2c2a] border border-[#795553] rounded-2xl p-8 hover:border-[#eabcb8] transition-colors duration-200">
                        <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center mb-6">
                            <BookOpenIcon class="h-5 w-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-primary-foreground mb-2">Book Catalog</h3>
                        <p class="text-[#eabcb8] text-sm leading-relaxed">
                            Browse and search thousands of books across categories, genres, and publishers.
                        </p>
                    </div>

                    <div class="bg-[#4a2c2a] border border-[#795553] rounded-2xl p-8 hover:border-[#eabcb8] transition-colors duration-200">
                        <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center mb-6">
                            <ClockIcon class="h-5 w-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-primary-foreground mb-2">Loan Management</h3>
                        <p class="text-[#eabcb8] text-sm leading-relaxed">
                            Borrow books, track due dates, and manage renewals with ease.
                        </p>
                    </div>

                    <div class="bg-[#4a2c2a] border border-[#795553] rounded-2xl p-8 hover:border-[#eabcb8] transition-colors duration-200">
                        <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center mb-6">
                            <DoorOpenIcon class="h-5 w-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-primary-foreground mb-2">Room Reservations</h3>
                        <p class="text-[#eabcb8] text-sm leading-relaxed">
                            Reserve study rooms and spaces online, anytime.
                        </p>
                    </div>

                    <div class="bg-[#4a2c2a] border border-[#795553] rounded-2xl p-8 hover:border-[#eabcb8] transition-colors duration-200">
                        <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center mb-6">
                            <TrendingUpIcon class="h-5 w-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-primary-foreground mb-2">Fines & Tracking</h3>
                        <p class="text-[#eabcb8] text-sm leading-relaxed">
                            Stay on top of overdue loans and outstanding fines automatically.
                        </p>
                    </div>

                    <div class="bg-[#4a2c2a] border border-[#795553] rounded-2xl p-8 hover:border-[#eabcb8] transition-colors duration-200">
                        <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center mb-6">
                            <BookmarkIcon class="h-5 w-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-primary-foreground mb-2">Announcements</h3>
                        <p class="text-[#eabcb8] text-sm leading-relaxed">
                            Stay updated with the latest library news, events, and notices.
                        </p>
                    </div>

                    <div class="bg-[#4a2c2a] border border-[#795553] rounded-2xl p-8 hover:border-[#eabcb8] transition-colors duration-200">
                        <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center mb-6">
                            <UsersIcon class="h-5 w-5 text-primary" />
                        </div>
                        <h3 class="text-lg font-bold text-primary-foreground mb-2">Maintenance Requests</h3>
                        <p class="text-[#eabcb8] text-sm leading-relaxed">
                            Report facility issues directly through the system for quick resolution.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="container mx-auto px-6 py-24">
            <div class="bg-primary rounded-3xl p-14 lg:p-20 text-center relative overflow-hidden">
                <!-- Subtle grid -->
                <div class="absolute inset-0 opacity-[0.04] rounded-3xl"
                    style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
                </div>
                <!-- Decorative circles -->
                <div class="absolute top-0 right-0 w-64 h-64 rounded-full border border-[#4a2c2a] translate-x-1/3 -translate-y-1/3 opacity-60"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full border border-[#4a2c2a] -translate-x-1/3 translate-y-1/3 opacity-60"></div>

                <div class="relative z-10">
                    <p class="text-[#eabcb8] text-xs uppercase tracking-widest mb-4">Get started today</p>
                    <h2 class="text-4xl lg:text-5xl font-bold text-primary-foreground tracking-tight mb-5">
                        Who are you<br />visiting us as?
                    </h2>
                    <p class="text-[#eabcb8] text-base mb-10 max-w-xl mx-auto leading-relaxed">
                        Members can browse books and reserve rooms. Librarians have full access to manage the system.
                    </p>
                    <div class="flex gap-3 justify-center flex-wrap">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg" variant="outline" class="text-primary hover:bg-[#4a2c2a] hover:text-primary-foreground rounded-xl h-12 px-6 font-semibold">
                                <BookOpenIcon class="mr-2 h-4 w-4" />
                                I'm a Member
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline" class="text-primary hover:bg-[#4a2c2a] hover:text-primary-foreground rounded-xl h-12 px-6 font-medium">
                                <UsersIcon class="mr-2 h-4 w-4" />
                                I'm a Librarian
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border">
            <div class="container mx-auto px-6 py-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                            <LibraryIcon class="h-4 w-4 text-primary-foreground" />
                        </div>
                        <div>
                            <div class="text-sm font-bold text-foreground">City Library</div>
                        </div>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        © 2026 City Library. All rights reserved.
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
