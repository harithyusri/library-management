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

    <div class="min-h-screen flex bg-white">
        <!-- Left Side - White Editorial Theme -->
        <div class="hidden lg:flex lg:w-1/2 bg-slate-950 p-14 flex-col justify-between relative overflow-hidden">
            <!-- Subtle grid texture -->
            <div class="absolute inset-0 opacity-[0.04]"
                style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <!-- Decorative accent lines -->
            <div class="absolute top-0 left-0 w-px h-full bg-gradient-to-b from-transparent via-slate-600 to-transparent opacity-40"></div>
            <div class="absolute top-0 right-0 w-px h-full bg-gradient-to-b from-transparent via-slate-700 to-transparent opacity-20"></div>

            <!-- Floating accent shapes -->
            <div class="absolute top-32 right-16 w-48 h-48 rounded-full border border-slate-700 opacity-30"></div>
            <div class="absolute top-36 right-20 w-36 h-36 rounded-full border border-slate-600 opacity-20"></div>
            <div class="absolute bottom-24 left-12 w-64 h-64 rounded-full border border-slate-700 opacity-20"></div>

            <!-- Logo -->
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <LibraryIcon class="h-5 w-5 text-slate-950" />
                    </div>
                    <div>
                        <div class="text-white text-lg font-bold tracking-tight">City Library</div>
                        <div class="text-slate-500 text-xs tracking-widest uppercase">Knowledge Hub</div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <div class="space-y-10 relative z-10">
                <div>
                    <h1 class="text-5xl font-bold text-white leading-[1.1] tracking-tight mt-6">
                        A World of<br />
                        <span class="text-slate-400">Knowledge</span><br />
                        Awaits You
                    </h1>

                    <p class="text-slate-400 text-base mt-5 leading-relaxed max-w-sm">
                        Access thousands of books, connect with fellow readers, and discover your next favorite story.
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="border border-slate-800 rounded-xl p-4 bg-slate-900/50">
                        <div class="text-2xl font-bold text-white">12K+</div>
                        <div class="text-slate-500 text-xs mt-1">Books</div>
                    </div>
                    <div class="border border-slate-800 rounded-xl p-4 bg-slate-900/50">
                        <div class="text-2xl font-bold text-white">850+</div>
                        <div class="text-slate-500 text-xs mt-1">Members</div>
                    </div>
                    <div class="border border-slate-800 rounded-xl p-4 bg-slate-900/50">
                        <div class="text-2xl font-bold text-white">50+</div>
                        <div class="text-slate-500 text-xs mt-1">Categories</div>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-2">
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-900 transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                            <BookOpenIcon class="h-4 w-4 text-slate-300" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-white">Extensive Collection</div>
                            <div class="text-xs text-slate-500">Fiction, Non-fiction, Academic & More</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-900 transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                            <BookmarkIcon class="h-4 w-4 text-slate-300" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-white">Easy Borrowing</div>
                            <div class="text-xs text-slate-500">Simple checkout & renewal process</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-900 transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                            <GraduationCapIcon class="h-4 w-4 text-slate-300" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-white">Digital Resources</div>
                            <div class="text-xs text-slate-500">E-books, journals & online databases</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-600 relative z-10 tracking-wide mt-5">
                © 2026 City Library
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-[420px]">
                <!-- Mobile Logo -->
                <div class="flex lg:hidden items-center justify-center gap-3 mb-10">
                    <div class="w-9 h-9 bg-slate-950 rounded-lg flex items-center justify-center">
                        <LibraryIcon class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">City Library</div>
                        <div class="text-xs text-slate-400 tracking-widest uppercase">Knowledge Hub</div>
                    </div>
                </div>

                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Welcome back</h2>
                    <p class="text-slate-500 text-sm mt-1.5">Sign in to access your library account</p>
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
                        <Label for="email" class="text-sm font-medium text-slate-700">
                            Email address
                        </Label>
                        <div class="relative">
                            <MailIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="your.email@example.com"
                                required
                                autofocus
                                autocomplete="email"
                                class="pl-10 h-11 border border-slate-200 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors"
                                :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-sm font-medium text-slate-700">
                                Password
                            </Label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium text-slate-500 hover:text-slate-900 transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <div class="relative">
                            <LockIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••••"
                                required
                                autocomplete="current-password"
                                class="pl-10 h-11 border border-slate-200 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 transition-colors"
                                :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.password }"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-500 flex items-center gap-1 mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center space-x-2.5 pt-1">
                        <Checkbox
                            id="remember"
                            v-model:checked="form.remember"
                            class="border border-slate-300 rounded data-[state=checked]:bg-slate-900 data-[state=checked]:border-slate-900"
                        />
                        <Label
                            for="remember"
                            class="text-sm text-slate-600 cursor-pointer font-normal"
                        >
                            Keep me signed in
                        </Label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        class="w-full h-11 text-sm font-semibold bg-slate-950 hover:bg-slate-800 text-white rounded-xl shadow-none transition-all duration-200 mt-2"
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
                        <div class="w-full border-t border-slate-100"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-3 text-xs text-slate-400">Demo accounts</span>
                    </div>
                </div>

                <!-- Demo Credentials -->
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 space-y-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-white border border-slate-200 rounded-lg p-3">
                            <div class="text-slate-500 mb-1 font-medium">Librarian</div>
                            <code class="text-slate-700 text-[11px] block break-all">sarah@library.com</code>
                        </div>
                        <div class="bg-white border border-slate-200 rounded-lg p-3">
                            <div class="text-slate-500 mb-1 font-medium">Member</div>
                            <code class="text-slate-700 text-[11px] block break-all">john.smith@example.com</code>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 text-center">
                        All passwords: <code class="font-mono text-slate-600 bg-white border border-slate-200 px-1.5 py-0.5 rounded">password</code>
                    </p>
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-400">
                        Need help?
                        <a href="#" class="font-medium text-slate-700 hover:text-slate-900 transition-colors underline underline-offset-2">
                            Contact our librarians
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>