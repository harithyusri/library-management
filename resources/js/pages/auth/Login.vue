<script setup lang="ts">
import { route } from "ziggy-js";
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BookOpenIcon, LibraryIcon, BookmarkIcon, GraduationCapIcon, MailIcon, LockIcon } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

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
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen flex bg-background">
        <!-- Left Side -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary p-14 flex-col justify-between relative overflow-hidden">
            <!-- Subtle grid texture -->
            <div class="absolute inset-0 opacity-[0.04]"
                style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <!-- Decorative accent lines -->
            <div class="absolute top-0 left-0 w-px h-full bg-gradient-to-b from-transparent via-[#795553] to-transparent opacity-40"></div>
            <div class="absolute top-0 right-0 w-px h-full bg-gradient-to-b from-transparent via-[#4a2c2a] to-transparent opacity-20"></div>

            <!-- Floating accent shapes -->
            <div class="absolute top-32 right-16 w-48 h-48 rounded-full border border-[#4a2c2a] opacity-30"></div>
            <div class="absolute top-36 right-20 w-36 h-36 rounded-full border border-[#795553] opacity-20"></div>
            <div class="absolute bottom-24 left-12 w-64 h-64 rounded-full border border-[#4a2c2a] opacity-20"></div>

            <!-- Logo -->
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-primary-foreground rounded-lg flex items-center justify-center">
                        <LibraryIcon class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <div class="text-primary-foreground text-lg font-bold tracking-tight">City Library</div>
                        <div class="text-[#eabcb8] text-xs tracking-widest uppercase">Knowledge Hub</div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="space-y-10 relative z-10">
                <div>
                    <h1 class="text-5xl font-bold text-primary-foreground leading-[1.1] tracking-tight mt-6">
                        A World of<br />
                        <span class="text-[#eabcb8]">Knowledge</span><br />
                        Awaits You
                    </h1>

                    <p class="text-[#eabcb8] text-base mt-5 leading-relaxed max-w-sm">
                        Access thousands of books, connect with fellow readers, and discover your next favorite story.
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="border border-[#4a2c2a] rounded-xl p-4 bg-[#4a2c2a]/50">
                        <div class="text-2xl font-bold text-primary-foreground">12K+</div>
                        <div class="text-[#eabcb8] text-xs mt-1">Books</div>
                    </div>
                    <div class="border border-[#4a2c2a] rounded-xl p-4 bg-[#4a2c2a]/50">
                        <div class="text-2xl font-bold text-primary-foreground">850+</div>
                        <div class="text-[#eabcb8] text-xs mt-1">Members</div>
                    </div>
                    <div class="border border-[#4a2c2a] rounded-xl p-4 bg-[#4a2c2a]/50">
                        <div class="text-2xl font-bold text-primary-foreground">50+</div>
                        <div class="text-[#eabcb8] text-xs mt-1">Categories</div>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-2">
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#4a2c2a] transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0">
                            <BookOpenIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-primary-foreground">Extensive Collection</div>
                            <div class="text-xs text-[#eabcb8]">Fiction, Non-fiction, Academic & More</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#4a2c2a] transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0">
                            <BookmarkIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-primary-foreground">Easy Borrowing</div>
                            <div class="text-xs text-[#eabcb8]">Simple checkout & renewal process</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#4a2c2a] transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0">
                            <GraduationCapIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-primary-foreground">Digital Resources</div>
                            <div class="text-xs text-[#eabcb8]">E-books, journals & online databases</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-xs text-[#795553] relative z-10 tracking-wide mt-5">
                © 2026 City Library
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-background">
            <div class="w-full max-w-[420px]">
                <!-- Mobile Logo -->
                <div class="flex lg:hidden items-center justify-center gap-3 mb-10">
                    <div class="w-9 h-9 bg-primary rounded-lg flex items-center justify-center">
                        <LibraryIcon class="h-5 w-5 text-primary-foreground" />
                    </div>
                    <div>
                        <div class="text-lg font-bold text-foreground">City Library</div>
                        <div class="text-xs text-muted-foreground tracking-widest uppercase">Knowledge Hub</div>
                    </div>
                </div>

                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-foreground tracking-tight">Welcome back</h2>
                    <p class="text-muted-foreground text-sm mt-1.5">Sign in to access your library account</p>
                </div>

                <!-- Status Message -->
                <div
                    v-if="status"
                    class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700 flex items-center gap-2"
                >
                    <BookmarkIcon class="h-4 w-4 flex-shrink-0" />
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-sm font-medium text-foreground">
                            Email address
                        </Label>
                        <div class="relative">
                            <MailIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="your.email@example.com"
                                required
                                autofocus
                                autocomplete="email"
                                class="pl-10 h-11 border border-input rounded-xl bg-card text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring transition-colors"
                                :class="{ 'border-destructive focus:border-destructive focus:ring-destructive': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-xs text-destructive flex items-center gap-1 mt-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-sm font-medium text-foreground">
                                Password
                            </Label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <LockIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••••"
                                required
                                autocomplete="current-password"
                                class="pl-10 h-11 border border-input rounded-xl bg-card text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring transition-colors"
                                :class="{ 'border-destructive focus:border-destructive focus:ring-destructive': form.errors.password }"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-destructive flex items-center gap-1 mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center space-x-2.5 pt-1">
                        <Checkbox
                            id="remember"
                            v-model:checked="form.remember"
                            class="border border-input rounded data-[state=checked]:bg-primary data-[state=checked]:border-primary"
                        />
                        <Label
                            for="remember"
                            class="text-sm text-muted-foreground cursor-pointer font-normal"
                        >
                            Keep me signed in
                        </Label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        class="w-full h-11 text-sm font-semibold bg-primary hover:opacity-90 text-primary-foreground rounded-xl shadow-none transition-all duration-200 mt-2"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <LibraryIcon class="h-4 w-4 animate-pulse" />
                            Signing you in...
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <BookOpenIcon class="h-4 w-4" />
                            Sign In
                        </span>
                    </Button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-border"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-background px-3 text-xs text-muted-foreground">Demo accounts</span>
                    </div>
                </div>

                <!-- Demo Credentials -->
                <div class="rounded-xl bg-muted border border-border p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-card border border-border rounded-lg p-3">
                            <div class="text-muted-foreground mb-1 font-medium">Librarian</div>
                            <code class="text-foreground text-[11px] block break-all">sarah@library.com</code>
                        </div>
                        <div class="bg-card border border-border rounded-lg p-3">
                            <div class="text-muted-foreground mb-1 font-medium">Member</div>
                            <code class="text-foreground text-[11px] block break-all">john.smith@example.com</code>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground text-center">
                        All passwords: <code class="font-mono text-foreground bg-card border border-border px-1.5 py-0.5 rounded">password</code>
                    </p>
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-muted-foreground">
                        Need help?
                        <a href="#" class="font-medium text-foreground hover:text-primary transition-colors underline underline-offset-2">
                            Contact our librarians
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>