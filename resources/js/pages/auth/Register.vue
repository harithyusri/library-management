<script setup lang="ts">
import { route } from "ziggy-js";
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { BookOpenIcon, BookmarkIcon, SparklesIcon, UserPlusIcon, EyeIcon, EyeOffIcon } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { useLocale } from '@/composables/useLocale';

const { locale, toggle } = useLocale();
const showPassword = ref(false);
const showConfirm = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const copy = computed(() => ({
    tagline:      locale.value === 'ms' ? 'Sistem Perpustakaan' : 'Library System',
    newMember:    locale.value === 'ms' ? 'Ahli Baru' : 'New Member',
    h1a:          locale.value === 'ms' ? 'Mulakan' : 'Begin Your',
    h1b:          locale.value === 'ms' ? 'Perjalanan' : 'Reading',
    h1c:          locale.value === 'ms' ? 'Membaca Anda' : 'Journey',
    heroSub:      locale.value === 'ms'
        ? 'Daftar percuma dan mula meneroka koleksi buku kami yang luas hari ini.'
        : 'Register for free and start exploring our vast book collection today.',
    giftTitle:    locale.value === 'ms' ? 'Hadiah Selamat Datang' : 'Welcome Gift',
    giftBody:     locale.value === 'ms'
        ? 'Ahli baru mendapat akses segera kepada 12,000+ buku dan boleh meminjam sehingga 5 buku pada satu masa.'
        : 'New members get instant access to 12,000+ books and can borrow up to 5 books at a time.',
    portal:       locale.value === 'ms' ? 'Daftar Akaun' : 'Create Account',
    heading:      locale.value === 'ms' ? 'Selamat\ndatang.' : 'Join us\ntoday.',
    subheading:   locale.value === 'ms'
        ? 'Cipta akaun anda dan sertai komuniti pembaca kami.'
        : 'Create your account and join our reading community.',
    nameLabel:    locale.value === 'ms' ? 'Nama Penuh' : 'Full Name',
    namePH:       locale.value === 'ms' ? 'Ahmad bin Abdullah' : 'John Smith',
    emailLabel:   locale.value === 'ms' ? 'Alamat E-mel' : 'Email Address',
    emailPH:      locale.value === 'ms' ? 'anda@contoh.com' : 'you@example.com',
    passLabel:    locale.value === 'ms' ? 'Kata Laluan' : 'Password',
    confirmLabel: locale.value === 'ms' ? 'Sahkan Kata Laluan' : 'Confirm Password',
    submit:       locale.value === 'ms' ? 'Cipta Akaun' : 'Create Account',
    submitting:   locale.value === 'ms' ? 'Mencipta akaun…' : 'Creating account…',
    hasAccount:   locale.value === 'ms' ? 'Sudah ada akaun?' : 'Already have an account?',
    signIn:       locale.value === 'ms' ? 'Log masuk' : 'Sign in',
    terms:        locale.value === 'ms'
        ? 'Dengan mendaftar, anda bersetuju dengan'
        : 'By registering, you agree to our',
    tos:          locale.value === 'ms' ? 'Terma Perkhidmatan' : 'Terms of Service',
    and:          locale.value === 'ms' ? 'dan' : 'and',
    privacy:      locale.value === 'ms' ? 'Dasar Privasi' : 'Privacy Policy',
    termsEnd:     locale.value === 'ms' ? 'kami.' : '.',
}));

const benefits = computed(() => locale.value === 'ms' ? [
    { icon: BookOpenIcon, label: 'Keahlian Percuma',    sub: 'Akses ribuan buku tanpa sebarang kos' },
    { icon: SparklesIcon, label: 'Akses Segera',        sub: 'Mula meminjam buku dengan serta-merta' },
    { icon: UserPlusIcon, label: 'Pengalaman Peribadi', sub: 'Cadangan buku yang disesuaikan untuk anda' },
    { icon: BookmarkIcon, label: 'Tempahan Bilik',      sub: 'Tempah ruang belajar secara dalam talian' },
] : [
    { icon: BookOpenIcon, label: 'Free Membership',     sub: 'Access thousands of books at no cost' },
    { icon: SparklesIcon, label: 'Instant Access',      sub: 'Start borrowing books immediately' },
    { icon: UserPlusIcon, label: 'Personalised',        sub: 'Book recommendations tailored for you' },
    { icon: BookmarkIcon, label: 'Room Booking',        sub: 'Reserve study spaces online' },
]);
</script>

<template>
    <Head :title="locale === 'ms' ? 'Daftar — PinjamBuku' : 'Register — PinjamBuku'" />

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
                <div>
                    <p class="text-[#c5a059]/60 text-xs tracking-[0.25em] uppercase mb-4">{{ copy.newMember }}</p>
                    <h1 class="font-serif text-[#f1f5f9] text-5xl leading-[1.1] tracking-tight">
                        {{ copy.h1a }}<br />
                        <em class="not-italic text-[#c5a059]">{{ copy.h1b }}</em><br />
                        {{ copy.h1c }}
                    </h1>
                    <p class="text-[#f1f5f9]/40 text-sm mt-5 leading-relaxed max-w-xs">{{ copy.heroSub }}</p>
                </div>

                <!-- Benefits -->
                <div class="space-y-1">
                    <div v-for="item in benefits" :key="item.label"
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

                <!-- Welcome callout -->
                <div class="border border-[#c5a059]/20 rounded-xl p-4 bg-[#c5a059]/5">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-[#c5a059]/10 border border-[#c5a059]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <SparklesIcon class="h-3.5 w-3.5 text-[#c5a059]" />
                        </div>
                        <div>
                            <p class="text-[#c5a059] text-xs font-bold uppercase tracking-widest mb-1">{{ copy.giftTitle }}</p>
                            <p class="text-[#f1f5f9]/40 text-xs leading-relaxed">{{ copy.giftBody }}</p>
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

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label for="name" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                            {{ copy.nameLabel }}
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            :placeholder="copy.namePH"
                            required
                            autofocus
                            autocomplete="name"
                            class="h-11 rounded-xl border-border bg-card text-foreground placeholder:text-muted-foreground/50 focus:border-[#c5a059] focus:ring-1 focus:ring-[#c5a059]/30 transition-colors"
                            :class="{ 'border-destructive focus:border-destructive': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                            {{ copy.emailLabel }}
                        </Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :placeholder="copy.emailPH"
                            required
                            autocomplete="email"
                            class="h-11 rounded-xl border-border bg-card text-foreground placeholder:text-muted-foreground/50 focus:border-[#c5a059] focus:ring-1 focus:ring-[#c5a059]/30 transition-colors"
                            :class="{ 'border-destructive focus:border-destructive': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <Label for="password" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                            {{ copy.passLabel }}
                        </Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••••"
                                required
                                autocomplete="new-password"
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

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <Label for="password_confirmation" class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                            {{ copy.confirmLabel }}
                        </Label>
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="••••••••••"
                                required
                                autocomplete="new-password"
                                class="h-11 pr-10 rounded-xl border-border bg-card text-foreground placeholder:text-muted-foreground/50 focus:border-[#c5a059] focus:ring-1 focus:ring-[#c5a059]/30 transition-colors"
                                :class="{ 'border-destructive focus:border-destructive': form.errors.password_confirmation }"
                            />
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
                                <EyeOffIcon v-if="showConfirm" class="h-4 w-4" />
                                <EyeIcon v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-xs text-destructive mt-1">{{ form.errors.password_confirmation }}</p>
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

                    <!-- Sign in link -->
                    <p class="text-center text-sm text-muted-foreground pt-1">
                        {{ copy.hasAccount }}
                        <Link :href="route('login')"
                            class="font-medium text-foreground hover:text-[#c5a059] underline underline-offset-2 transition-colors ml-1">
                            {{ copy.signIn }}
                        </Link>
                    </p>
                </form>

                <!-- Divider -->
                <div class="my-6 border-t border-border"></div>

                <!-- Terms -->
                <p class="text-center text-xs text-muted-foreground leading-relaxed">
                    {{ copy.terms }}
                    <a href="#" class="text-foreground hover:text-[#c5a059] underline underline-offset-2 transition-colors">{{ copy.tos }}</a>
                    {{ copy.and }}
                    <a href="#" class="text-foreground hover:text-[#c5a059] underline underline-offset-2 transition-colors">{{ copy.privacy }}</a>{{ copy.termsEnd }}
                </p>

            </div>
        </div>
    </div>
</template>
