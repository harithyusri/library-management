<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Calendar, Clock, CheckCircle2, AlertCircle, Plus, DoorClosed, Users, MapPin, ArrowRight } from 'lucide-vue-next';
import { route } from 'ziggy-js';

defineProps<{ bookings: any[] }>();

const breadcrumbs = [{ title: 'Room Bookings', href: route('member.room-bookings.index') }];

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });

const statusConfig = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'confirmed' || s === 'completed') return { label: s === 'confirmed' ? 'Confirmed' : 'Completed', class: 'bg-primary/10 text-primary border-primary/25', spine: 'var(--sage)' };
    if (s === 'cancelled' || s === 'rejected') return { label: 'Cancelled', class: 'bg-destructive/10 text-destructive border-destructive/25', spine: 'var(--dust)' };
    return { label: 'Pending', class: 'bg-[color:var(--brass)]/15 text-[color:var(--leather)] border-[color:var(--brass)]/40', spine: 'var(--brass)' };
};
</script>

<template>
    <Head title="Room Bookings" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-0">
            <FlashAlert class="mb-4" />

            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">
                    Room Bookings
                </h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">
                    Reserve and manage your library study room bookings.
                </p>
            </section>

            <!-- Shelf heading -->
            <div class="flex items-end justify-between gap-4 pt-8 pb-4">
                <h2 class="font-serif text-2xl">Your bookings</h2>
                <Link :href="route('member.room-bookings.create')">
                    <Button size="sm" class="rounded-full font-bold" style="background: var(--ink); color: var(--dust);">
                        <Plus class="h-3.5 w-3.5 mr-1.5" /> Book a Room
                    </Button>
                </Link>
            </div>

            <!-- List -->
            <div v-if="bookings.length > 0" class="flex flex-col gap-4">
                <article
                    v-for="booking in bookings"
                    :key="booking.id"
                    class="group relative border border-border rounded-xl overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[var(--shadow-book)] bg-card"
                >
                    <span class="absolute left-0 top-0 h-full w-1.5" :style="{ background: statusConfig(booking.status).spine }" />

                    <div class="flex flex-col lg:flex-row pl-2">
                        <!-- Icon panel -->
                        <div class="w-full lg:w-40 bg-secondary/40 flex flex-col items-center justify-center p-6 border-b lg:border-b-0 lg:border-r border-border">
                            <div class="bg-card p-3 rounded-xl border border-border mb-2 group-hover:scale-110 transition-transform duration-500">
                                <DoorClosed class="h-7 w-7" style="color: var(--brass)" />
                            </div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground text-center">{{ booking.room.room_number }}</p>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 p-6 space-y-4">
                            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-bold" :class="statusConfig(booking.status).class">
                                        {{ statusConfig(booking.status).label }}
                                    </span>
                                    <h3 class="font-serif text-xl leading-tight group-hover:text-[color:var(--leather)] transition-colors">
                                        {{ booking.room.name }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground flex items-center gap-1.5">
                                        <MapPin class="h-3.5 w-3.5" /> {{ booking.room.library?.name ?? 'Library' }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm shrink-0">
                                    <div class="space-y-0.5">
                                        <span class="block text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Date</span>
                                        <span class="font-bold flex items-center gap-1.5 whitespace-nowrap">
                                            <Calendar class="h-3.5 w-3.5 text-muted-foreground" /> {{ formatDate(booking.booking_date) }}
                                        </span>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="block text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Time</span>
                                        <span class="font-bold flex items-center gap-1.5 whitespace-nowrap">
                                            <Clock class="h-3.5 w-3.5 text-muted-foreground" /> {{ booking.start_time }} – {{ booking.end_time }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 pt-3 border-t border-border/50 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1.5"><Users class="h-3.5 w-3.5" /> {{ booking.number_of_attendees || 'N/A' }} attendees</span>
                                <span v-if="booking.purpose">{{ booking.purpose }}</span>
                                <span class="ml-auto font-bold" style="color: var(--brass)">RM {{ booking.total_cost }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center space-y-4 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center border border-border">
                    <DoorClosed class="h-10 w-10 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-xl">No bookings yet</h3>
                    <p class="text-sm text-muted-foreground max-w-sm mx-auto">Need a quiet place to study? Browse our available rooms and make a booking.</p>
                </div>
                <Link :href="route('member.room-bookings.create')">
                    <Button class="rounded-full px-8 font-bold" style="background: var(--ink); color: var(--dust);">
                        Book a Room <ArrowRight class="ml-2 h-4 w-4" />
                    </Button>
                </Link>
            </div>

            <!-- Quote -->
            <div class="mt-12 rounded-xl border border-border bg-card p-6 text-center">
                <p class="font-serif italic text-lg">"A room without books is like a body without a soul."</p>
                <p class="mt-2 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Marcus Tullius Cicero</p>
            </div>
        </div>
    </AppLayout>
</template>
