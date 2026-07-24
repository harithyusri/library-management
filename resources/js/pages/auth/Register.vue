<script setup lang="ts">
import { route } from "ziggy-js";
import { useForm, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

    <div class="min-h-screen flex bg-background">
        <!-- Left Side - Dark Panel -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary p-14 flex-col justify-between relative overflow-hidden">
            <!-- Subtle grid texture -->
            <div class="absolute inset-0 opacity-[0.04]"
                style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;">
            </div>

            <!-- Accent lines -->
            <div class="absolute top-0 left-0 w-px h-full bg-gradient-to-b from-transparent via-[#795553] to-transparent opacity-40"></div>
            <div class="absolute top-0 right-0 w-px h-full bg-gradient-to-b from-transparent via-[#4a2c2a] to-transparent opacity-20"></div>

            <!-- Decorative circles -->
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
                        Begin Your<br />
                        <span class="text-[#eabcb8]">Literary</span><br />
                        Adventure
                    </h1>

                    <p class="text-[#eabcb8] text-base mt-5 leading-relaxed max-w-sm">
                        Create your free account and start exploring our vast collection of books today.
                    </p>
                </div>

                <!-- Benefits -->
                <div class="space-y-2">
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#4a2c2a] transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0">
                            <BookOpenIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-primary-foreground">Free Membership</div>
                            <div class="text-xs text-[#eabcb8]">Access thousands of books at no cost</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#4a2c2a] transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0">
                            <LibraryIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-primary-foreground">Instant Access</div>
                            <div class="text-xs text-[#eabcb8]">Start borrowing books immediately</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-[#4a2c2a] transition-colors duration-200">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0">
                            <UserPlusIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <div class="text-sm font-medium text-primary-foreground">Personalized Experience</div>
                            <div class="text-xs text-[#eabcb8]">Get tailored book recommendations</div>
                        </div>
                    </div>
                </div>

                <!-- Welcome gift callout -->
                <div class="border border-[#4a2c2a] rounded-xl p-4 bg-[#4a2c2a]/50 mb-5">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-[#4a2c2a] flex items-center justify-center flex-shrink-0 mt-0.5">
                            <BookOpenIcon class="h-4 w-4 text-[#eabcb8]" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-primary-foreground mb-1">Welcome Gift</p>
                            <p class="text-xs text-[#eabcb8] leading-relaxed">
                                New members get instant access to 12,000+ books and can borrow up to 5 at a time.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-xs text-[#795553] relative z-10 tracking-wide">
                © 2026 City Library · Enriching minds since 1950
            </div>
        </div>

        <!-- Right Side - Register Form -->
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
                    <h2 class="text-3xl font-bold text-foreground tracking-tight">Create account</h2>
                    <p class="text-muted-foreground text-sm mt-1.5">Join our library community today</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label for="name" class="text-sm font-medium text-foreground">
                            Full Name
                        </Label>
                        <div class="relative">
                            <UserIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="John Doe"
                                required
                                autofocus
                                autocomplete="name"
                                class="pl-10 h-11 border border-input rounded-xl bg-card text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring transition-colors"
                                :class="{ 'border-destructive focus:border-destructive focus:ring-destructive': form.errors.name }"
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-xs text-destructive mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1.5">
                        <Label for="email" class="text-sm font-medium text-foreground">
                            Email Address
                        </Label>
                        <div class="relative">
                            <MailIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="john@example.com"
                                required
                                autocomplete="email"
                                class="pl-10 h-11 border border-input rounded-xl bg-card text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring transition-colors"
                                :class="{ 'border-destructive focus:border-destructive focus:ring-destructive': form.errors.email }"
                            />
                        </div>
                        <p v-if="form.errors.email" class="text-xs text-destructive mt-1">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <Label for="password" class="text-sm font-medium text-foreground">
                            Password
                        </Label>
                        <div class="relative">
                            <LockIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••••"
                                required
                                autocomplete="new-password"
                                class="pl-10 h-11 border border-input rounded-xl bg-card text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring transition-colors"
                                :class="{ 'border-destructive focus:border-destructive focus:ring-destructive': form.errors.password }"
                            />
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-destructive mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <Label for="password_confirmation" class="text-sm font-medium text-foreground">
                            Confirm Password
                        </Label>
                        <div class="relative">
                            <LockIcon class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="••••••••••"
                                required
                                autocomplete="new-password"
                                class="pl-10 h-11 border border-input rounded-xl bg-card text-foreground placeholder:text-muted-foreground focus:border-ring focus:ring-1 focus:ring-ring transition-colors"
                                :class="{ 'border-destructive focus:border-destructive focus:ring-destructive': form.errors.password_confirmation }"
                            />
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-xs text-destructive mt-1">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        class="w-full h-11 text-sm font-semibold bg-primary hover:opacity-90 text-primary-foreground rounded-xl transition-all duration-200 mt-2"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="flex items-center justify-center gap-2">
                            <LibraryIcon class="h-4 w-4 animate-pulse" />
                            Creating your account...
                        </span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <UserPlusIcon class="h-4 w-4" />
                            Create Account
                        </span>
                    </Button>

                    <!-- Sign In Link -->
                    <div class="text-center text-sm text-muted-foreground pt-1">
                        Already have an account?
                        <Link
                            :href="route('login')"
                            class="font-medium text-foreground hover:text-primary underline underline-offset-2 transition-colors ml-1"
                        >
                            Sign in
                        </Link>
                    </div>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-border"></div>
                    </div>
                </div>

                <!-- Terms -->
                <p class="text-center text-xs text-muted-foreground leading-relaxed">
                    By creating an account, you agree to our
                    <a href="#" class="font-medium text-foreground hover:text-primary underline underline-offset-2 transition-colors">
                        Terms of Service
                    </a>
                    and
                    <a href="#" class="font-medium text-foreground hover:text-primary underline underline-offset-2 transition-colors">
                        Privacy Policy
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>