<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Calendar, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    History,
    Plus,
    DoorClosed,
    Users,
    MapPin,
    ArrowRight
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    bookings: any[];
}>();

const breadcrumbs = [
    { title: 'Room Bookings', href: route('member.room-bookings.index') },
];

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusConfig = (status: string) => {
    const s = status.toLowerCase();
    if (s === 'confirmed' || s === 'completed') {
        return {
            label: s === 'confirmed' ? 'Confirmed' : 'Completed',
            icon: CheckCircle2,
            bgColor: 'bg-emerald-50 text-emerald-600 border-emerald-100',
            progressColor: 'bg-emerald-500'
        };
    }
    if (s === 'cancelled' || s === 'rejected') {
        return {
            label: 'Cancelled',
            icon: AlertCircle,
            bgColor: 'bg-red-50 text-red-600 border-red-100',
            progressColor: 'bg-red-500'
        };
    }
    return {
        label: 'Pending',
        icon: Clock,
        bgColor: 'bg-amber-50 text-amber-600 border-amber-100',
        progressColor: 'bg-amber-500'
    };
};

</script>

<template>
    <Head title="Room Bookings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Room Bookings <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Reserve and manage your library room bookings.</p>
                </div>

                <Link :href="route('member.room-bookings.create')">
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-6 h-11 font-bold shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2">
                        <Plus class="h-5 w-5" />
                        Book a Room
                    </Button>
                </Link>
            </div>

            <!-- List Grid -->
            <div v-if="bookings.length > 0" class="grid grid-cols-1 gap-4">
                <Card v-for="booking in bookings" :key="booking.id" class="group border-slate-200 overflow-hidden hover:border-indigo-200 transition-all duration-300 shadow-sm hover:shadow-md rounded-2xl">
                    <CardContent class="p-0">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Room Visual Info -->
                            <div class="w-full lg:w-48 bg-slate-50 flex flex-col items-center justify-center p-6 border-b lg:border-b-0 lg:border-r border-slate-100 group-hover:bg-indigo-50/30 transition-colors">
                                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 mb-3 group-hover:scale-110 transition-transform duration-500">
                                    <DoorClosed class="h-8 w-8 text-indigo-500" />
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 text-center">
                                    {{ booking.room.room_number }}
                                </div>
                            </div>

                            <!-- Main Content -->
                            <div class="flex-1 p-6 space-y-6">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <Badge variant="outline" :class="getStatusConfig(booking.status).bgColor" class="px-2 py-0 h-5 text-[10px] uppercase font-bold tracking-wider rounded-md border-0">
                                                {{ getStatusConfig(booking.status).label }}
                                            </Badge>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ booking.room.name }}
                                        </h3>
                                        <p class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                                            <MapPin class="h-3.5 w-3.5 text-slate-400" />
                                            Library Level 2, Wing B
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                                        <div class="space-y-1">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Date</span>
                                            <span class="font-bold text-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                                                <Calendar class="h-3.5 w-3.5 text-slate-300" />
                                                {{ formatDate(booking.booking_date) }}
                                            </span>
                                        </div>
                                        <div class="space-y-1">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Time Slot</span>
                                            <span class="font-bold text-slate-700 flex items-center gap-1.5 whitespace-nowrap">
                                                <Clock class="h-3.5 w-3.5 text-slate-300" />
                                                {{ booking.start_time }} - {{ booking.end_time }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-y-2 gap-x-6 pt-2 border-t border-slate-50">
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4 text-slate-400" />
                                        <span class="text-xs font-bold text-slate-600">Attendees: {{ booking.number_of_attendees || 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2" v-if="booking.purpose">
                                        <Plus class="h-4 w-4 text-slate-400 rotate-45" />
                                        <span class="text-xs font-bold text-slate-600">{{ booking.purpose }}</span>
                                    </div>
                                    <div class="ml-auto flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-400">Total:</span>
                                        <span class="text-sm font-black text-indigo-600">RM {{ booking.total_cost }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="py-24 text-center space-y-6 bg-slate-50/50 rounded-3xl border-2 border-dashed border-slate-200">
                <div class="bg-white p-6 rounded-full w-24 h-24 mx-auto shadow-sm flex items-center justify-center border border-slate-100">
                    <DoorClosed class="h-10 w-10 text-slate-300" />
                </div>
                <div class="space-y-2">
                    <h3 class="text-2xl font-black text-slate-900">No bookings yet</h3>
                    <p class="text-slate-500 font-medium max-w-sm mx-auto">
                        Need a quiet place to study or a room for a discussion? Browse our available rooms and make a booking today!
                    </p>
                </div>
                <div>
                    <Link :href="route('member.room-bookings.create')">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 rounded-xl px-10 font-bold shadow-lg shadow-indigo-200/50">
                            Book a Room
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
