<script setup lang="ts">
import { route } from "ziggy-js";
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BookOpenIcon, LibraryIcon, UserPlusIcon, MailIcon, LockIcon, UserIcon } from 'lucide-vue-next';

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
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex">
        <!-- Left Side - Library Theme -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-amber-800 via-orange-700 to-rose-800 p-12 flex-col justify-between text-white relative overflow-hidden">
            <!-- Decorative book pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-32 h-40 border-4 border-white rounded-sm transform rotate-12"></div>
                <div class="absolute top-40 right-20 w-28 h-36 border-4 border-white rounded-sm transform -rotate-6"></div>
                <div class="absolute bottom-20 left-32 w-36 h-44 border-4 border-white rounded-sm transform rotate-3"></div>
                <div class="absolute bottom-40 right-10 w-24 h-32 border-4 border-white rounded-sm transform -rotate-12"></div>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg border-2 border-white/30">
                        <LibraryIcon class="h-8 w-8" />
                    </div>
                    <div>
                        <div class="text-2xl font-bold">City Library</div>
                        <div class="text-sm text-white/80">Knowledge Hub</div>
                    </div>
                </div>
            </div>

            <div class="space-y-8 relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 border border-white/30">
                    <UserPlusIcon class="h-4 w-4" />
                    <span class="text-sm font-medium">Join Our Reading Community</span>
                </div>

                <h1 class="text-5xl font-bold leading-tight">
                    Begin Your<br />
                    Literary<br />
                    Adventure
                </h1>
                
                <p class="text-xl text-white/90 max-w-md">
                    Create your free account and start exploring our vast collection of books today.
                </p>

                <!-- Benefits -->
                <div class="space-y-3 mt-8">
                    <div class="flex items-center gap-3 bg-white/5 backdrop-blur-sm rounded-lg p-3 border border-white/10">
                        <div class="p-2 bg-amber-600/50 rounded-lg">
                            <BookOpenIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-semibold text-sm">Free Membership</div>
                            <div class="text-xs text-white/70">Access thousands of books at no cost</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 backdrop-blur-sm rounded-lg p-3 border border-white/10">
                        <div class="p-2 bg-orange-600/50 rounded-lg">
                            <LibraryIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-semibold text-sm">Instant Access</div>
                            <div class="text-xs text-white/70">Start borrowing books immediately</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 backdrop-blur-sm rounded-lg p-3 border border-white/10">
                        <div class="p-2 bg-rose-600/50 rounded-lg">
                            <UserPlusIcon class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="font-semibold text-sm">Personalized Experience</div>
                            <div class="text-xs text-white/70">Get tailored book recommendations</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-sm text-white/60 relative z-10">
                © 2026 City Library. Enriching minds since 1950.
            </div>
        </div>

        <!-- Right Side - Register Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-gradient-to-br from-amber-50 via-orange-50 to-rose-50 dark:from-slate-950 dark:to-slate-900">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="flex lg:hidden items-center justify-center gap-3 mb-8">
                    <div class="p-2 bg-gradient-to-br from-amber-700 to-orange-700 rounded-lg">
                        <LibraryIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <div class="text-xl font-bold text-amber-900 dark:text-amber-100">City Library</div>
                        <div class="text-xs text-amber-700 dark:text-amber-400">Knowledge Hub</div>
                    </div>
                </div>

                <Card class="border-2 border-amber-200/50 dark:border-amber-900/50 shadow-xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
                    <CardHeader class="space-y-2 text-center pb-6">
                        <div class="mx-auto w-16 h-16 bg-gradient-to-br from-amber-700 to-orange-700 rounded-xl flex items-center justify-center mb-2 shadow-lg">
                            <UserPlusIcon class="h-8 w-8 text-white" />
                        </div>
                        <CardTitle class="text-3xl font-bold text-amber-900 dark:text-amber-100">
                            Create Account
                        </CardTitle>
                        <CardDescription class="text-base text-amber-700 dark:text-amber-400">
                            Join our library community today
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-5">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="name" class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                    Full Name
                                </Label>
                                <div class="relative">
                                    <UserIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-amber-600 dark:text-amber-500" />
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="John Doe"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        class="pl-10 h-12 border-2 border-amber-200 dark:border-amber-800 focus:border-amber-600 focus:ring-amber-600 bg-white dark:bg-slate-900"
                                        :class="{ 'border-rose-500': form.errors.name }"
                                    />
                                </div>
                                <p v-if="form.errors.name" class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                    <span class="inline-block w-1 h-1 rounded-full bg-rose-600"></span>
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <Label for="email" class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                    Email Address
                                </Label>
                                <div class="relative">
                                    <MailIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-amber-600 dark:text-amber-500" />
                                    <Input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="john@example.com"
                                        required
                                        autocomplete="email"
                                        class="pl-10 h-12 border-2 border-amber-200 dark:border-amber-800 focus:border-amber-600 focus:ring-amber-600 bg-white dark:bg-slate-900"
                                        :class="{ 'border-rose-500': form.errors.email }"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                    <span class="inline-block w-1 h-1 rounded-full bg-rose-600"></span>
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <Label for="password" class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                    Password
                                </Label>
                                <div class="relative">
                                    <LockIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-orange-600 dark:text-orange-500" />
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="••••••••••"
                                        required
                                        autocomplete="new-password"
                                        class="pl-10 h-12 border-2 border-amber-200 dark:border-amber-800 focus:border-orange-600 focus:ring-orange-600 bg-white dark:bg-slate-900"
                                        :class="{ 'border-rose-500': form.errors.password }"
                                    />
                                </div>
                                <p v-if="form.errors.password" class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                    <span class="inline-block w-1 h-1 rounded-full bg-rose-600"></span>
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Confirm Password -->
                            <div class="space-y-2">
                                <Label for="password_confirmation" class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                    Confirm Password
                                </Label>
                                <div class="relative">
                                    <LockIcon class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-orange-600 dark:text-orange-500" />
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="••••••••••"
                                        required
                                        autocomplete="new-password"
                                        class="pl-10 h-12 border-2 border-amber-200 dark:border-amber-800 focus:border-orange-600 focus:ring-orange-600 bg-white dark:bg-slate-900"
                                        :class="{ 'border-rose-500': form.errors.password_confirmation }"
                                    />
                                </div>
                                <p v-if="form.errors.password_confirmation" class="text-xs text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                    <span class="inline-block w-1 h-1 rounded-full bg-rose-600"></span>
                                    {{ form.errors.password_confirmation }}
                                </p>
                            </div>

                            <!-- Submit Button -->
                            <Button
                                type="submit"
                                class="w-full h-12 text-base font-semibold bg-gradient-to-r from-amber-700 to-orange-700 hover:from-amber-800 hover:to-orange-800 text-white shadow-lg hover:shadow-xl transition-all duration-200"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <LibraryIcon class="h-5 w-5 animate-pulse" />
                                    Creating your account...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <UserPlusIcon class="h-5 w-5" />
                                    Create Account
                                </span>
                            </Button>

                            <!-- Sign In Link -->
                            <div class="text-center text-sm text-amber-800 dark:text-amber-300">
                                Already have an account?
                                <Link
                                    :href="route('login')"
                                    class="font-semibold text-amber-900 hover:text-amber-700 dark:text-amber-200 dark:hover:text-amber-100 underline underline-offset-2 transition-colors"
                                >
                                    Sign in
                                </Link>
                            </div>
                        </form>

                        <!-- Info Box -->
                        <div class="mt-6 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 border-2 border-amber-300/50 dark:border-amber-700/50 p-4">
                            <div class="flex items-start gap-3">
                                <BookOpenIcon class="h-5 w-5 text-amber-700 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-amber-900 dark:text-amber-100 mb-1">
                                        🎉 Welcome Gift
                                    </p>
                                    <p class="text-xs text-amber-800 dark:text-amber-200">
                                        New members get instant access to our collection of 12,000+ books and can borrow up to 5 books at a time!
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Footer -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-amber-700 dark:text-amber-400">
                        By creating an account, you agree to our 
                        <a href="#" class="font-semibold text-amber-800 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-200 underline">
                            Terms of Service
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>