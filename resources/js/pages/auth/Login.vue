<script setup lang="ts">
import { route } from "ziggy-js";
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { BookOpenIcon, BookmarkIcon, UsersIcon, LayersIcon, EyeIcon, EyeOffIcon } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useLocale } from '@/composables/useLocale';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const { locale, toggle } = useLocale();
const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const copy = computed(() => ({
    tagline:    locale.value === 'ms' ? 'Sistem Perpustakaan' : 'Library System',
    heading:    locale.value === 'ms' ? 'Selamat\nkembali.' : 'Welcome\nback.',
    subheading: locale.value === 'ms' ? 'Log masuk untuk akses akaun perpustakaan anda.' : 'Sign in to access your library account.',
    portal:     locale.value === 'ms' ? 'Portal Kakitangan & Ahli' : 'Staff & Member Portal',
    h1:         locale.value === 'ms' ? 'Di Mana Setiap Cerita Menemui Pembacanya' : 'Where Every Story Finds Its Reader',
    h1a:        locale.value === 'ms' ? 'Di Mana Setiap' : 'Where Every',
    h1b:        locale.value === 'ms' ? 'Cerita' : 'Story',
    h1c:        locale.value === 'ms' ? 'Menemui Pembacanya' : 'Finds Its Reader',
    heroSub:    locale.value === 'ms'
        ? 'Koleksi ilmu yang dikurasi dengan teliti untuk minda yang ingin tahu.'
        : 'A curated collection of knowledge, thoughtfully organised for curious minds.',
    volumes:    locale.value === 'ms' ? 'Jilid' : 'Volumes',
    members:    locale.value === 'ms' ? 'Ahli' : 'Members',
    genres:     locale.value === 'ms' ? 'Genre' : 'Genres',
    emailLabel: locale.value === 'ms' ? 'Alamat E-mel' : 'Email Address',
    passLabel:  locale.value === 'ms' ? 'Kata Laluan' : 'Password',
    forgot:     locale.value === 'ms' ? 'Lupa kata laluan?' : 'Forgot password?',
    remember:   locale.value === 'ms' ? 'Kekalkan log masuk' : 'Keep me signed in',
    submit:     locale.value === 'ms' ? 'Log Masuk' : 'Sign In',
    submitting: locale.value === 'ms' ? 'Sedang log masuk…' : 'Signing in…',
    demoLabel:  locale.value === 'ms' ? 'Akaun demo' : 'Demo accounts',
    librarian:  locale.value === 'ms' ? 'Pustakawan' : 'Librarian',
    member:     locale.value === 'ms' ? 'Ahli' : 'Member',
    passHint:   locale.value === 'ms' ? 'Kata laluan:' : 'Password:',
    noAccount:  locale.value === 'ms' ? 'Belum ada akaun?' : "Don't have an account?",
    register:   locale.value === 'ms' ? 'Daftar sekarang' : 'Register now',
    help:       locale.value === 'ms' ? 'Perlukan bantuan?' : 'Need help?',
    contact:    locale.value === 'ms' ? 'Hubungi pustakawan kami' : 'Contact our librarians',
}));

const features = computed(() => locale.value === 'ms' ? [
    { icon: BookOpenIcon, label: 'Koleksi Terpilih',      sub: 'Fiksyen, bukan fiksyen, akademik & lain-lain' },
    { icon: BookmarkIcon, label: 'Pinjaman Mudah',         sub: 'Proses daftar keluar & pembaharuan yang ringkas' },
    { icon: UsersIcon,    label: 'Komuniti Pembaca',       sub: 'Kumpulan membaca & acara ahli' },
    { icon: LayersIcon,   label: 'Sumber Digital',         sub: 'E-buku, jurnal & pangkalan data' },
] : [
    { icon: BookOpenIcon, label: 'Curated Collection',    sub: 'Fiction, non-fiction, academic & more' },
    { icon: BookmarkIcon, label: 'Easy Borrowing',         sub: 'Simple checkout & renewal process' },
    { icon: UsersIcon,    label: 'Community Driven',       sub: 'Reading groups & member events' },
    { icon: LayersIcon,   label: 'Digital Resources',      sub: 'E-books, journals & databases' },
]);
</script>

<template>
    <Head :title="locale === 'ms' ? 'Log Masuk — PinjamBuku' : 'Sign In — PinjamBuku'" />

    <div class="min-h-screen flex bg-[#0d1a14]">

        <!-- Left Panel -->
        <div class="hidden lg:flex lg:w-[52%] flex-col justify-between relative overflow-hidden p-14"
            style="background: linear-gradient(160deg, #0d1a14 0%, #122010 50%, #0a1510 100%);">

            <div class="absolute inset-0 opacity-[0.15]"
                style="background-image: radial-gradient(#c5a059 0.5px, transparent 0.5px); background-size: 24px 24px;">
            </div>
            <div class="absolute top-0 right-0 w-px h-full bg-gradient-to-b from-transparent via-[#c5a059]/30 to-transparent"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full border border-[#c5a059]/10"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 rounded-full border border-[#c5a059]/10"></div>
            <div class="absolute top-24 right-24 w-48 h-48 rounded-full border border-[#1e3828]/80"></div>

            <!-- Logo -->
            <div class="relative z-10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#c5a059]/10 border border-[#c5a059]/30 flex items-center justify-center">
                    <BookOpenIcon class="h-5 w-5 text-[#c5a059]" />
                </div>
                <div>
                    <div class="font-serif text-[#f1f5f9] text-lg tracking-tight leading-none">PinjamBuku</div>
                    <div class="text-[#c5a059]/70 text-[10px] tracking-[0.2em] uppercase mt-0.5">{{ copy.tagline }}</div>
                </div>
            </div>

            <!-- Main copy -->
            <div class="relative z-10 space-y-8">
                <div class="mt-5">
                    <h1 class="font-serif text-[#f1f5f9] text-5xl leading-[1.1] tracking-tight">
                        {{ copy.h1a }}<br />
                        <em class="not-italic text-[#c5a059]">{{ copy.h1b }}</em><br />
                        {{ copy.h1c }}
                    </h1>
                    <p class="text-[#f1f5f9]/40 text-sm mt-5 leading-relaxed max-w-xs">{{ copy.heroSub }}</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="border border-[#1e3828] rounded-xl p-4 bg-[#1a2e26]/40">
                        <div class="font-serif text-2xl text-[#c5a059]">12K+</div>
                        <div class="text-[#f1f5f9]/40 text-[11px] mt-1 tracking-wide">{{ copy.volumes }}</div>
                    </div>
                    <div class="border border-[#1e3828] rounded-xl p-4 bg-[#1a2e26]/40">
                        <div class="font-serif text-2xl text-[#c5a059]">850+</div>
                        <div class="text-[#f1f5f9]/40 text-[11px] mt-1 tracking-wide">{{ copy.members }}</div>
                    </div>
                    <div class="border border-[#1e3828] rounded-xl p-4 bg-[#1a2e26]/40">
                        <div class="font-serif text-2xl text-[#c5a059]">50+</div>
                        <div class="text-[#f1f5f9]/40 text-[11px] mt-1 tracking-wide">{{ copy.genres }}</div>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-1">
                    <div v-for="item in features" :key="item.label"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#1a2e26]/60 transition-colors duration-200 group">
                        <div class="w-7 h-7 rounded-lg bg-[#1a2e26] border border-[#1e3828] flex items-center justify-center flex-shrink-0 group-hover:border-[#c5a059]/30 transition-colors">
                            <component :is="item.icon" class="h-3.5 w-3.5 text-[#c5a059]/70" />
                        </div>
                        <div>
                            <div class="text-[#f1f5f9]/80 text-xs font-medium">{{ item.label }}</div>
                            <div class="text-[#f1f5f9]/30 text-[11px]">{{ item.sub }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-[#f1f5f9]/20 text-[11px] tracking-widest uppercase">© 2026 PinjamBuku</div>
        </div>

        <!-- Right Panel — Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-[oklch(0.96_0.012_120)]">
            <div class="w-full max-w-[400px]">

                <!-- Mobile logo + lang toggle -->
                <div class="flex items-center justify-between mb-10">
                    <div class="flex lg:hidden items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#0d1a14] flex items-center justify-center">
                            <BookOpenIcon class="h-4 w-4 text-[#c5a059]" />
                        </div>
                        <div>
                            <div class="font-serif text-foreground text-base leading-none">PinjamBuku</div>
                            <div class="text-muted-foreground text-[10px] tracking-[0.2em] uppercase mt-0.5">{{ copy.tagline }}</div>
                        </div>
                    </div>
                    <div class="hidden lg:block"></div>
                    <button @click="toggle"
                        class="px-3 py-1.5 text-xs font-bold uppercase tracking-widest border border-border rounded-lg text-muted-foreground hover:border-[#c5a059]/40 hover:text-[#c5a059] transition-colors">
                        {{ locale === 'ms' ? 'EN' : 'BM' }}
                    </button>
                </div>

                <!-- Heading -->
                <div class="mb-8">
                    <p class="text-[#c5a059] text-[10px] tracking-[0.25em] uppercase font-medium mb-2">{{ copy.portal }}</p>
                    <h2 class="font-serif text-foreground text-4xl tracking-tight leading-tight" style="white-space: pre-line">{{ copy.heading }}</h2>
                    <p class="text-muted-foreground text-sm mt-2">{{ copy.subheading }}</p>
                </div>

                <!-- Status -->
                <div v-if="status"
                    class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700 flex items-center gap-2">
                    <BookmarkIcon class="h-4 w-4 flex-shrink-0" />
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                            {{ copy.emailLabel }}
                        </Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="you@example.com"
                            required
                            autofocus
                            autocomplete="email"
                            class="h-11 rounded-xl border-border bg-card text-foreground placeholder:text-muted-foreground/50 focus:border-[#c5a059] focus:ring-1 focus:ring-[#c5a059]/30 transition-colors"
                            :class="{ 'border-destructive focus:border-destructive': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                                {{ copy.passLabel }}
                            </Label>
                            <Link v-if="canResetPassword" :href="route('password.request')"
                                class="text-xs text-muted-foreground hover:text-[#c5a059] transition-colors">
                                {{ copy.forgot }}
                            </Link>
                        </div>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••••"
                                required
                                autocomplete="current-password"
                                class="h-11 pr-10 rounded-xl border-border bg-card text-foreground placeholder:text-muted-foreground/50 focus:border-[#c5a059] focus:ring-1 focus:ring-[#c5a059]/30 transition-colors"
                                :class="{ 'border-destructive focus:border-destructive': form.errors.password }"
                            />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                                <EyeOffIcon v-if="showPassword" class="h-4 w-4" />
                                <EyeIcon v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-destructive mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center gap-2.5">
                        <Checkbox id="remember" v-model:checked="form.remember"
                            class="border-border data-[state=checked]:bg-[#0d1a14] data-[state=checked]:border-[#0d1a14]" />
                        <Label for="remember" class="text-sm text-muted-foreground cursor-pointer font-normal">
                            {{ copy.remember }}
                        </Label>
                    </div>

                    <!-- Submit -->
                    <Button type="submit"
                        class="w-full h-11 rounded-xl bg-[#0d1a14] hover:bg-[#122010] text-[#f1f5f9] font-semibold text-sm transition-all duration-200 border border-[#1e3828] shadow-none mt-1"
                        :disabled="form.processing">
                        <span v-if="form.processing" class="flex items-center gap-2">
                            <BookOpenIcon class="h-4 w-4 animate-pulse text-[#c5a059]" />
                            {{ copy.submitting }}
                        </span>
                        <span v-else>{{ copy.submit }}</span>
                    </Button>
                </form>

                <!-- Divider -->
                <div class="relative my-7">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-border"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-[oklch(0.96_0.012_120)] px-3 text-[11px] text-muted-foreground tracking-widest uppercase">{{ copy.demoLabel }}</span>
                    </div>
                </div>

                <!-- Demo credentials -->
                <div class="rounded-xl border border-border bg-card p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div class="rounded-lg border border-border bg-background p-3">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1.5">{{ copy.librarian }}</div>
                            <code class="text-[11px] text-foreground block break-all leading-relaxed">sarah@library.com</code>
                        </div>
                        <div class="rounded-lg border border-border bg-background p-3">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1.5">{{ copy.member }}</div>
                            <code class="text-[11px] text-foreground block break-all leading-relaxed">john.smith@example.com</code>
                        </div>
                    </div>
                    <p class="text-[11px] text-muted-foreground text-center">
                        {{ copy.passHint }} <code class="font-mono text-foreground bg-muted border border-border px-1.5 py-0.5 rounded text-[11px]">password</code>
                    </p>
                </div>

                <!-- Register link -->
                <p class="mt-6 text-center text-sm text-muted-foreground">
                    {{ copy.noAccount }}
                    <Link :href="route('register')"
                        class="font-medium text-foreground hover:text-[#c5a059] underline underline-offset-2 transition-colors ml-1">
                        {{ copy.register }}
                    </Link>
                </p>

                <!-- Footer -->
                <!-- <p class="mt-3 text-center text-xs text-muted-foreground">
                    {{ copy.help }}
                    <a href="#" class="text-foreground hover:text-[#c5a059] transition-colors underline underline-offset-2">
                        {{ copy.contact }}
                    </a>
                </p> -->

            </div>
        </div>
    </div>
</template>
