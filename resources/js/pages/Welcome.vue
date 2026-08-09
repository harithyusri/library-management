<script setup lang="ts">
import { route } from "ziggy-js";
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, ClockIcon, DoorOpenIcon, TrendingUpIcon, BookmarkIcon, UsersIcon, SparklesIcon, ArrowRightIcon } from 'lucide-vue-next';
import { useLocale } from '@/composables/useLocale';
import { computed } from 'vue';

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

const { locale, toggle } = useLocale();

const ms = locale.value === 'ms';

const copy = computed(() => ({
    tagline:    locale.value === 'ms' ? 'Sistem Perpustakaan Digital' : 'Digital Library System',
    navLogin:   locale.value === 'ms' ? 'Log Masuk' : 'Sign In',
    navReg:     locale.value === 'ms' ? 'Daftar Sekarang' : 'Register Now',
    badge:      locale.value === 'ms' ? 'Sistem Perpustakaan Digital' : 'Digital Library System',
    h1a:        locale.value === 'ms' ? 'Pinjam.' : 'Borrow.',
    h1b:        locale.value === 'ms' ? 'Baca.' : 'Read.',
    h1c:        locale.value === 'ms' ? 'Kembali.' : 'Return.',
    hero:       locale.value === 'ms'
        ? 'Urus pinjaman, tempah bilik, dan terokai ribuan buku — semuanya di satu tempat.'
        : 'Manage loans, book rooms, and explore thousands of books — all in one place.',
    ctaStart:   locale.value === 'ms' ? 'Mulakan Sekarang' : 'Get Started',
    ctaLogin:   locale.value === 'ms' ? 'Log Masuk' : 'Sign In',
    statBooks:  locale.value === 'ms' ? 'Buku' : 'Books',
    statMembers:locale.value === 'ms' ? 'Ahli' : 'Members',
    statCats:   locale.value === 'ms' ? 'Kategori' : 'Categories',
    statRooms:  locale.value === 'ms' ? 'Bilik' : 'Rooms',
    featHeadSub:locale.value === 'ms' ? 'Apa yang kami tawarkan' : 'What we offer',
    featHead:   locale.value === 'ms' ? 'Semua yang perpustakaan anda perlukan' : 'Everything your library needs',
    ctaSub:     locale.value === 'ms' ? 'Mulakan hari ini' : 'Start today',
    ctaHead:    locale.value === 'ms' ? 'Anda siapa hari ini?' : 'Who are you today?',
    ctaBody:    locale.value === 'ms'
        ? 'Ahli boleh melayari buku dan menempah bilik. Pustakawan mempunyai akses penuh untuk menguruskan sistem.'
        : 'Members can browse books and book rooms. Librarians have full access to manage the system.',
    ctaMember:  locale.value === 'ms' ? 'Saya Ahli' : "I'm a Member",
    ctaLib:     locale.value === 'ms' ? 'Saya Pustakawan' : "I'm a Librarian",
    footer:     locale.value === 'ms' ? 'Hak cipta terpelihara.' : 'All rights reserved.',
}));

const features = computed(() => locale.value === 'ms' ? [
    { icon: BookOpenIcon,    title: 'Katalog Buku',            desc: 'Layari dan cari ribuan buku merentas kategori, genre, dan penerbit.' },
    { icon: ClockIcon,       title: 'Pengurusan Pinjaman',     desc: 'Pinjam buku, pantau tarikh akhir, dan urus pembaharuan dengan mudah.' },
    { icon: DoorOpenIcon,    title: 'Tempahan Bilik',          desc: 'Tempah bilik belajar dan ruang secara dalam talian, bila-bila masa.' },
    { icon: TrendingUpIcon,  title: 'Denda & Penjejakan',      desc: 'Pantau pinjaman tertunggak dan denda yang belum dijelaskan secara automatik.' },
    { icon: BookmarkIcon,    title: 'Pengumuman',              desc: 'Ikuti berita, acara, dan notis terkini daripada perpustakaan anda.' },
    { icon: UsersIcon,       title: 'Laporan Penyelenggaraan', desc: 'Laporkan isu kemudahan terus melalui sistem untuk penyelesaian pantas.' },
] : [
    { icon: BookOpenIcon,    title: 'Book Catalogue',          desc: 'Browse and search thousands of books across categories, genres, and publishers.' },
    { icon: ClockIcon,       title: 'Loan Management',         desc: 'Borrow books, track due dates, and manage renewals with ease.' },
    { icon: DoorOpenIcon,    title: 'Room Booking',            desc: 'Reserve study rooms and spaces online, anytime.' },
    { icon: TrendingUpIcon,  title: 'Fines & Tracking',        desc: 'Automatically monitor overdue loans and outstanding fines.' },
    { icon: BookmarkIcon,    title: 'Announcements',           desc: 'Stay updated with the latest news, events, and notices from your library.' },
    { icon: UsersIcon,       title: 'Maintenance Reports',     desc: 'Report facility issues directly through the system for quick resolution.' },
]);
</script>

<template>
    <Head :title="locale === 'ms' ? 'PinjamBuku — Sistem Perpustakaan Digital' : 'PinjamBuku — Digital Library System'" />

    <div class="min-h-screen bg-[oklch(0.96_0.012_120)] text-foreground">

        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-border bg-[oklch(0.96_0.012_120)]/90 backdrop-blur-sm">
            <div class="container mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-[#0d1a14] rounded-xl flex items-center justify-center">
                        <BookOpenIcon class="h-4 w-4 text-[#c5a059]" />
                    </div>
                    <div>
                        <div class="font-serif text-foreground text-base leading-none tracking-tight">PinjamBuku</div>
                        <div class="text-[10px] text-muted-foreground tracking-[0.2em] uppercase mt-0.5">{{ copy.tagline }}</div>
                    </div>
                </div>

                <nav class="flex items-center gap-2">
                    <!-- Language toggle -->
                    <button @click="toggle"
                        class="px-3 py-1.5 text-xs font-bold uppercase tracking-widest border border-border rounded-lg text-muted-foreground hover:border-[#c5a059] hover:bg-[#c5a059]/10 hover:text-[#c5a059] transition-colors">
                        {{ locale === 'ms' ? 'EN' : 'BM' }}
                    </button>
                    <Link v-if="canLogin" :href="route('login')"
                        class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                        {{ copy.navLogin }}
                    </Link>
                    <Link v-if="canRegister" :href="route('register')">
                        <Button class="bg-[#0d1a14] hover:bg-[#122010] text-[#f1f5f9] text-sm font-medium rounded-xl h-9 px-4 border border-[#1e3828]">
                            {{ copy.navReg }}
                        </Button>
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="container mx-auto px-6 pt-24 pb-28">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                <!-- Left -->
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 border border-[#c5a059]/30 bg-[#c5a059]/5 rounded-full px-3 py-1.5">
                        <SparklesIcon class="h-3 w-3 text-[#c5a059]" />
                        <span class="text-xs font-medium text-[#c5a059] tracking-wide">{{ copy.badge }}</span>
                    </div>

                    <h1 class="font-serif text-6xl lg:text-7xl text-foreground leading-[1.05] tracking-tight">
                        {{ copy.h1a }}<br />
                        <em class="not-italic text-[#c5a059]">{{ copy.h1b }}</em><br />
                        {{ copy.h1c }}
                    </h1>

                    <p class="text-lg text-muted-foreground max-w-md leading-relaxed">{{ copy.hero }}</p>

                    <div class="flex gap-3 pt-2">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg"
                                class="bg-[#0d1a14] hover:bg-[#122010] text-[#f1f5f9] rounded-xl h-12 px-6 font-medium border border-[#1e3828] gap-2">
                                <BookOpenIcon class="h-4 w-4 text-[#c5a059]" />
                                {{ copy.ctaStart }}
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline"
                                class="rounded-xl h-12 px-6 font-medium border-border hover:border-[#c5a059] hover:bg-[#c5a059]/10 hover:text-[#c5a059] transition-colors gap-2">
                                {{ copy.ctaLogin }}
                                <ArrowRightIcon class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-4 gap-4 pt-6 border-t border-border">
                        <div v-for="stat in [
                            { val: '12K+', label: copy.statBooks },
                            { val: '850+', label: copy.statMembers },
                            { val: '50+',  label: copy.statCats },
                            { val: '10+',  label: copy.statRooms },
                        ]" :key="stat.label">
                            <div class="font-serif text-3xl text-foreground">{{ stat.val }}</div>
                            <div class="text-sm text-muted-foreground mt-0.5">{{ stat.label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Right — decorative book cards -->
                <div class="relative hidden lg:flex items-center justify-center h-[480px]">
                    <div class="absolute w-80 h-80 rounded-full border border-border bg-muted/40"></div>
                    <div class="absolute w-56 h-56 rounded-full border border-[#c5a059]/10"></div>

                    <div class="absolute left-4 top-12 w-36 rounded-2xl shadow-xl p-5 flex flex-col justify-between h-52 transform -rotate-6 hover:-rotate-3 transition-transform duration-300"
                        style="background: linear-gradient(160deg, #0d1a14, #122010);">
                        <BookOpenIcon class="h-5 w-5 text-[#c5a059]" />
                        <div>
                            <div class="text-[#c5a059]/60 text-[10px] uppercase tracking-widest mb-1">{{ locale === 'ms' ? 'Fiksyen' : 'Fiction' }}</div>
                            <div class="font-serif text-[#f1f5f9] text-sm">{{ locale === 'ms' ? 'Kisah Klasik' : 'Classic Tales' }}</div>
                        </div>
                    </div>

                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-40 bg-card border border-border rounded-2xl shadow-xl p-5 flex flex-col justify-between h-60 transform rotate-2 hover:rotate-0 transition-transform duration-300 z-10">
                        <BookOpenIcon class="h-5 w-5 text-muted-foreground" />
                        <div>
                            <div class="text-muted-foreground text-[10px] uppercase tracking-widest mb-1">{{ locale === 'ms' ? 'Romantik' : 'Romance' }}</div>
                            <div class="font-serif text-foreground text-sm">{{ locale === 'ms' ? 'Cerita Cinta' : 'Love Stories' }}</div>
                        </div>
                    </div>

                    <div class="absolute right-4 top-20 w-36 bg-muted border border-border rounded-2xl shadow-lg p-5 flex flex-col justify-between h-48 transform rotate-6 hover:rotate-3 transition-transform duration-300">
                        <BookOpenIcon class="h-5 w-5 text-muted-foreground" />
                        <div>
                            <div class="text-muted-foreground text-[10px] uppercase tracking-widest mb-1">{{ locale === 'ms' ? 'Misteri' : 'Mystery' }}</div>
                            <div class="font-serif text-foreground text-sm">Thriller</div>
                        </div>
                    </div>

                    <div class="absolute bottom-8 left-1/2 -translate-x-20 w-44 rounded-2xl shadow-xl p-5 flex flex-col justify-between h-44 transform -rotate-3 hover:rotate-0 transition-transform duration-300"
                        style="background: linear-gradient(160deg, #1a2e26, #0d1a14);">
                        <BookOpenIcon class="h-5 w-5 text-[#c5a059]/70" />
                        <div>
                            <div class="text-[#c5a059]/50 text-[10px] uppercase tracking-widest mb-1">{{ locale === 'ms' ? 'Akademik' : 'Academic' }}</div>
                            <div class="font-serif text-[#f1f5f9] text-sm">{{ locale === 'ms' ? 'Sains & Teknologi' : 'Science & Tech' }}</div>
                        </div>
                    </div>

                    <div class="absolute bottom-16 right-8 w-3 h-3 rounded-full bg-[#c5a059]/40"></div>
                    <div class="absolute top-16 left-20 w-2 h-2 rounded-full bg-[#c5a059]/30"></div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section style="background: linear-gradient(160deg, #0d1a14 0%, #122010 50%, #0a1510 100%);" class="py-24 relative overflow-hidden">
            <div class="absolute inset-0 opacity-[0.12]"
                style="background-image: radial-gradient(#c5a059 0.5px, transparent 0.5px); background-size: 24px 24px;">
            </div>
            <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full border border-[#c5a059]/10"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full border border-[#c5a059]/10"></div>

            <div class="container mx-auto px-6 relative z-10">
                <div class="text-center mb-14">
                    <p class="text-[#c5a059]/70 text-[10px] uppercase tracking-[0.25em] mb-3">{{ copy.featHeadSub }}</p>
                    <h2 class="font-serif text-[#f1f5f9] text-4xl lg:text-5xl tracking-tight">{{ copy.featHead }}</h2>
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div v-for="feature in features" :key="feature.title"
                        class="border border-[#1e3828] rounded-2xl p-7 bg-[#1a2e26]/30 hover:border-[#c5a059]/30 hover:bg-[#1a2e26]/50 transition-all duration-200 group">
                        <div class="w-9 h-9 rounded-xl bg-[#c5a059]/10 border border-[#c5a059]/20 flex items-center justify-center mb-5 group-hover:border-[#c5a059]/40 transition-colors">
                            <component :is="feature.icon" class="h-4 w-4 text-[#c5a059]" />
                        </div>
                        <h3 class="font-serif text-[#f1f5f9] text-lg mb-2">{{ feature.title }}</h3>
                        <p class="text-[#f1f5f9]/40 text-sm leading-relaxed">{{ feature.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="container mx-auto px-6 py-24">
            <div class="relative rounded-3xl overflow-hidden p-14 lg:p-20 text-center"
                style="background: linear-gradient(160deg, #0d1a14, #122010);">
                <div class="absolute inset-0 opacity-[0.12]"
                    style="background-image: radial-gradient(#c5a059 0.5px, transparent 0.5px); background-size: 24px 24px;">
                </div>
                <div class="absolute top-0 right-0 w-72 h-72 rounded-full border border-[#c5a059]/10 translate-x-1/3 -translate-y-1/3"></div>
                <div class="absolute bottom-0 left-0 w-56 h-56 rounded-full border border-[#c5a059]/10 -translate-x-1/3 translate-y-1/3"></div>

                <div class="relative z-10">
                    <p class="text-[#c5a059]/70 text-[10px] uppercase tracking-[0.25em] mb-4">{{ copy.ctaSub }}</p>
                    <h2 class="font-serif text-[#f1f5f9] text-4xl lg:text-5xl tracking-tight mb-5">{{ copy.ctaHead }}</h2>
                    <p class="text-[#f1f5f9]/40 text-base mb-10 max-w-lg mx-auto leading-relaxed">{{ copy.ctaBody }}</p>
                    <div class="flex gap-3 justify-center flex-wrap">
                        <Link v-if="canRegister" :href="route('register')">
                            <Button size="lg"
                                class="bg-[#c5a059] hover:bg-[#b8924a] text-[#0d1a14] rounded-xl h-12 px-7 font-bold gap-2 transition-colors">
                                <BookOpenIcon class="h-4 w-4" />
                                {{ copy.ctaMember }}
                            </Button>
                        </Link>
                        <Link v-if="canLogin" :href="route('login')">
                            <Button size="lg" variant="outline"
                                class="border-[#1e3828] text-[#f1f5f9]/70 hover:border-[#c5a059]/40 hover:text-[#c5a059] rounded-xl h-12 px-7 font-medium gap-2 bg-transparent transition-colors">
                                <UsersIcon class="h-4 w-4" />
                                {{ copy.ctaLib }}
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border">
            <div class="container mx-auto px-6 py-8 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-[#0d1a14] rounded-xl flex items-center justify-center">
                        <BookOpenIcon class="h-3.5 w-3.5 text-[#c5a059]" />
                    </div>
                    <div class="font-serif text-sm text-foreground">PinjamBuku</div>
                </div>
                <div class="text-xs text-muted-foreground">© 2026 PinjamBuku. {{ copy.footer }}</div>
            </div>
        </footer>

    </div>
</template>
