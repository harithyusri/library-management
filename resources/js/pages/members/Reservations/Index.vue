<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { BookOpen, ArrowRight, Library, Trash2, CheckCircle2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

defineProps<{
    reservations: { data: any[]; links: any[]; total: number };
}>();

const breadcrumbs = [{ title: 'My Reservations', href: route('member.reservations.index') }];

const statusConfig: Record<string, { label: string; class: string }> = {
    pending:   { label: 'In Queue',  class: 'bg-[color:var(--brass)]/15 text-[color:var(--leather)] border-[color:var(--brass)]/40' },
    ready:     { label: 'Ready',     class: 'bg-primary/10 text-primary border-primary/25' },
    fulfilled: { label: 'Fulfilled', class: 'bg-secondary text-muted-foreground border-border' },
    expired:   { label: 'Expired',   class: 'bg-destructive/10 text-destructive border-destructive/25' },
    cancelled: { label: 'Cancelled', class: 'bg-secondary text-muted-foreground border-border' },
};

const formatDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';

const cancel = (id: number) => {
    if (!confirm('Cancel this reservation?')) return;
    router.delete(route('member.reservations.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <Head title="My Reservations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-0">
            <FlashAlert class="mb-4" />

            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">
                    My Reservations
                </h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">
                    Track your place in the waitlist for books currently on loan.
                </p>
            </section>

            <!-- Shelf heading -->
            <div class="flex items-end justify-between gap-4 pt-8 pb-4">
                <h2 class="font-serif text-2xl">Waitlist</h2>
                <Link :href="route('member.catalog.index')">
                    <Button size="sm" class="rounded-full font-bold" style="background: var(--ink); color: var(--dust);">
                        Browse Catalog <ArrowRight class="ml-1.5 h-3.5 w-3.5" />
                    </Button>
                </Link>
            </div>

            <!-- List -->
            <div v-if="reservations.data.length > 0" class="flex flex-col gap-4">
                <article
                    v-for="res in reservations.data"
                    :key="res.id"
                    class="group relative border border-border rounded-xl overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[var(--shadow-book)] bg-card"
                >
                    <!-- Spine accent -->
                    <span class="absolute left-0 top-0 h-full w-1.5"
                        :style="{ background: res.status === 'ready' ? 'var(--sage)' : res.status === 'pending' ? 'var(--brass)' : 'var(--dust)' }" />

                    <div class="flex flex-col sm:flex-row pl-2">
                        <!-- Icon panel -->
                        <div class="w-full sm:w-32 bg-secondary/40 flex items-center justify-center p-6 border-b sm:border-b-0 sm:border-r border-border">
                            <div class="bg-card p-3 rounded-xl border border-border group-hover:scale-110 transition-transform duration-500">
                                <BookOpen class="h-7 w-7" style="color: var(--brass)" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="statusConfig[res.status]?.class ?? 'bg-secondary text-muted-foreground border-border'">
                                    {{ statusConfig[res.status]?.label ?? res.status }}
                                </span>
                                <h3 class="font-serif text-lg leading-tight group-hover:text-[color:var(--leather)] transition-colors">
                                    {{ res.book?.title }}
                                </h3>
                                <p class="text-sm text-muted-foreground">by {{ res.book?.author_name }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-6 text-sm shrink-0">
                                <div class="space-y-0.5">
                                    <span class="block text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Reserved</span>
                                    <span class="font-bold">{{ formatDate(res.reserved_date) }}</span>
                                </div>
                                <div v-if="res.status === 'ready'" class="space-y-0.5">
                                    <span class="block text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Expires</span>
                                    <span class="font-bold text-emerald-600">{{ formatDate(res.expiry_date) }}</span>
                                </div>
                                <div v-if="res.status === 'ready'" class="flex items-center gap-2 px-3 py-2 bg-primary/10 rounded-lg border border-primary/25">
                                    <CheckCircle2 class="h-4 w-4 text-primary" />
                                    <span class="text-xs font-bold text-primary">Ready to collect!</span>
                                </div>
                                <Button v-if="res.status === 'pending' || res.status === 'ready'" variant="ghost" size="sm"
                                    class="text-destructive hover:text-destructive hover:bg-destructive/10 rounded-full"
                                    @click="cancel(res.id)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center space-y-4 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center border border-border">
                    <Library class="h-10 w-10 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-xl">No reservations yet</h3>
                    <p class="text-sm text-muted-foreground max-w-sm mx-auto">When a book you want is fully checked out, you can join the waitlist from the catalog.</p>
                </div>
                <Link :href="route('member.catalog.index')">
                    <Button class="rounded-full px-8 font-bold" style="background: var(--ink); color: var(--dust);">
                        Browse Catalog <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </Link>
            </div>

            <!-- Quote -->
            <div class="mt-12 rounded-xl border border-border bg-card p-6 text-center">
                <p class="font-serif italic text-lg">"Not all those who wander are lost — some are just browsing the stacks."</p>
                <p class="mt-2 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Library wisdom</p>
            </div>

            <!-- Pagination -->
            <div v-if="reservations.data.length > 0 && reservations.links.length > 3" class="flex flex-col md:flex-row items-center justify-between gap-4 pt-8 border-t border-border">
                <p class="text-sm text-muted-foreground">Total: <span class="font-bold text-foreground">{{ reservations.total }}</span></p>
                <div class="flex items-center gap-1.5">
                    <Link v-for="link in reservations.links" :key="link.label" :href="link.url || '#'"
                        class="h-9 min-w-9 flex items-center justify-center rounded-full px-3.5 text-sm font-bold transition-all border"
                        :class="[link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border text-muted-foreground hover:border-[color:var(--brass)] hover:text-[color:var(--leather)]', !link.url && 'opacity-40 cursor-not-allowed pointer-events-none']"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
