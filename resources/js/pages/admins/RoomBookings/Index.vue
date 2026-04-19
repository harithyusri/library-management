<script setup lang="ts">
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Calendar as CalendarIcon, ChevronLeft, ChevronRight,
    Plus, Clock, DoorOpen, User, List, LayoutGrid,
} from 'lucide-vue-next';

interface Booking {
    id: number;
    room: { id: number; name: string; room_number: string };
    user: { id: number; name: string };
    booking_date: string;      // YYYY-MM-DD
    start_time: string;        // HH:MM
    end_time: string;          // HH:MM
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
    purpose: string | null;
    total_cost: number;
}

const props = defineProps<{
    bookings: Booking[];
    can: { createBookings: boolean; editBookings: boolean; deleteBookings: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Room Bookings', href: '#' },
];

// ── Calendar state ──────────────────────────────────────────────
const today = new Date();
const currentYear  = ref(today.getFullYear());
const currentMonth = ref(today.getMonth()); // 0-based

const monthNames = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];
const dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

const calendarDays = computed(() => {
    const year  = currentYear.value;
    const month = currentMonth.value;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrev  = new Date(year, month, 0).getDate();

    const days: Array<{ date: Date; isCurrentMonth: boolean; isToday: boolean }> = [];

    // prev month padding
    for (let i = firstDay - 1; i >= 0; i--) {
        days.push({ date: new Date(year, month - 1, daysInPrev - i), isCurrentMonth: false, isToday: false });
    }
    // current month
    for (let d = 1; d <= daysInMonth; d++) {
        const date = new Date(year, month, d);
        const isToday = date.toDateString() === today.toDateString();
        days.push({ date, isCurrentMonth: true, isToday });
    }
    // next month padding to fill 6 rows (42 cells)
    const remaining = 42 - days.length;
    for (let d = 1; d <= remaining; d++) {
        days.push({ date: new Date(year, month + 1, d), isCurrentMonth: false, isToday: false });
    }
    return days;
});

const prevMonth = () => {
    if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value--; }
    else currentMonth.value--;
};
const nextMonth = () => {
    if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++; }
    else currentMonth.value++;
};
const goToday = () => { currentYear.value = today.getFullYear(); currentMonth.value = today.getMonth(); };

// ── Booking helpers ─────────────────────────────────────────────
const toDateKey = (d: Date) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const bookingsByDate = computed(() => {
    const map: Record<string, Booking[]> = {};
    for (const b of props.bookings) {
        if (!map[b.booking_date]) map[b.booking_date] = [];
        map[b.booking_date].push(b);
    }
    return map;
});

const selectedDate = ref<string | null>(toDateKey(today));

const selectedBookings = computed(() =>
    selectedDate.value ? (bookingsByDate.value[selectedDate.value] ?? []) : []
);

const selectDay = (day: { date: Date; isCurrentMonth: boolean }) => {
    if (!day.isCurrentMonth) {
        currentYear.value  = day.date.getFullYear();
        currentMonth.value = day.date.getMonth();
    }
    selectedDate.value = toDateKey(day.date);
};

const dotColors: Record<string, string> = {
    confirmed: 'bg-emerald-500',
    pending:   'bg-amber-400',
    cancelled: 'bg-red-400',
    completed: 'bg-slate-400',
};

const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    confirmed: { label: 'Confirmed', variant: 'default' },
    pending:   { label: 'Pending',   variant: 'outline' },
    cancelled: { label: 'Cancelled', variant: 'destructive' },
    completed: { label: 'Completed', variant: 'secondary' },
};

const selectedDateFormatted = computed(() => {
    if (!selectedDate.value) return '';
    const d = new Date(selectedDate.value + 'T00:00:00');
    return d.toLocaleDateString('en-MY', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

// ── View toggle ────────────────────────────────────────────────
const view = ref<'calendar' | 'list'>('calendar');

const allSorted = computed(() =>
    [...props.bookings].sort((a, b) => (a.booking_date + a.start_time).localeCompare(b.booking_date + b.start_time))
);
</script>

<template>
    <Head title="Room Bookings" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-6">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Room Bookings <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Manage and view all room reservations and utilization.</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- View toggle -->
                    <div class="flex items-center rounded-lg border bg-white px-2 py-1 text-sm gap-1 shadow-sm">
                        <Button
                            variant="ghost" size="sm"
                            class="px-2 rounded-lg font-bold flex items-center gap-2 transition-all"
                            :class="view === 'calendar' ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'text-slate-400 hover:bg-slate-50'"
                            @click="view = 'calendar'"
                        >
                            <LayoutGrid class="h-4 w-4" />
                            Calendar
                        </Button>
                        <Button
                            variant="ghost" size="sm"
                            class="px-2 rounded-lg font-bold flex items-center gap-2 transition-all"
                            :class="view === 'list' ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'text-slate-400 hover:bg-slate-50'"
                            @click="view = 'list'"
                        >
                            <List class="h-4 w-4" />
                            List View
                        </Button>
                    </div>

                    <Link v-if="can.createBookings" :href="route('admin.room-bookings.create')" class="contents">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-bold shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2">
                            <Plus class="h-5 w-5" />
                            New Booking
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- ── CALENDAR VIEW ── -->
            <div v-if="view === 'calendar'" class="grid gap-6 lg:grid-cols-5">

                <!-- Calendar Panel -->
                <Card class="lg:col-span-3">
                    <CardHeader class="pt-4">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base font-semibold">
                                {{ monthNames[currentMonth] }} {{ currentYear }}
                            </CardTitle>
                            <div class="flex items-center gap-1">
                                <Button variant="ghost" size="icon" class="h-7 w-7" @click="prevMonth">
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="sm" class="h-7 text-xs px-2" @click="goToday">
                                    Today
                                </Button>
                                <Button variant="ghost" size="icon" class="h-7 w-7" @click="nextMonth">
                                    <ChevronRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="px-4 pb-4">
                        <!-- Day headers -->
                        <div class="grid grid-cols-7 mb-1">
                            <div
                                v-for="day in dayNames" :key="day"
                                class="text-center text-xs font-medium text-muted-foreground py-1.5"
                            >{{ day }}</div>
                        </div>
                        <!-- Day cells -->
                        <div class="grid grid-cols-7 gap-px bg-border rounded-lg overflow-hidden border">
                            <button
                                v-for="(day, idx) in calendarDays"
                                :key="idx"
                                class="relative bg-background flex flex-col items-center pt-1.5 pb-1 min-h-[56px] hover:bg-muted/60 transition-colors focus:outline-none"
                                :class="{
                                    'opacity-40': !day.isCurrentMonth,
                                    'ring-2 ring-inset ring-primary': selectedDate === toDateKey(day.date),
                                }"
                                @click="selectDay(day)"
                            >
                                <!-- Date number -->
                                <span
                                    class="text-xs font-medium w-6 h-6 flex items-center justify-center rounded-full transition-colors"
                                    :class="{
                                        'bg-primary text-primary-foreground': day.isToday,
                                        'text-foreground': !day.isToday,
                                    }"
                                >{{ day.date.getDate() }}</span>

                                <!-- Booking dots -->
                                <div class="flex flex-wrap justify-center gap-0.5 mt-0.5 px-1">
                                    <template v-for="booking in (bookingsByDate[toDateKey(day.date)] ?? []).slice(0, 3)" :key="booking.id">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="dotColors[booking.status] ?? 'bg-slate-400'" />
                                    </template>
                                    <span
                                        v-if="(bookingsByDate[toDateKey(day.date)] ?? []).length > 3"
                                        class="text-[9px] text-muted-foreground leading-none"
                                    >+{{ (bookingsByDate[toDateKey(day.date)] ?? []).length - 3 }}</span>
                                </div>
                            </button>
                        </div>

                        <!-- Legend -->
                        <div class="flex items-center gap-3 mt-3 flex-wrap">
                            <span v-for="(color, status) in dotColors" :key="status" class="flex items-center gap-1 text-xs text-muted-foreground capitalize">
                                <span class="h-2 w-2 rounded-full" :class="color" />{{ status }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Day Detail Panel -->
                <Card class="lg:col-span-2 flex flex-col">
                    <CardHeader class="pt-4">
                        <CardTitle class="text-sm font-semibold leading-snug">
                            {{ selectedDateFormatted || 'Select a date' }}
                        </CardTitle>
                        <CardDescription>
                            {{ selectedBookings.length === 0 ? 'No bookings' : `${selectedBookings.length} booking${selectedBookings.length > 1 ? 's' : ''}` }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1 overflow-y-auto max-h-[420px] space-y-2 pb-4">
                        <div v-if="selectedBookings.length === 0" class="flex flex-col items-center justify-center py-10 gap-2 text-muted-foreground">
                            <CalendarIcon class="h-7 w-7 opacity-30" />
                            <p class="text-sm">Nothing booked on this day.</p>
                            <Link v-if="can.createBookings" :href="route('admin.room-bookings.create')">
                                <Button variant="outline" size="sm" class="mt-1 gap-1.5 text-xs">
                                    <Plus class="h-3.5 w-3.5" /> Add Booking
                                </Button>
                            </Link>
                        </div>

                        <Link
                            v-else
                            v-for="booking in selectedBookings"
                            :key="booking.id"
                            :href="route('admin.room-bookings.show', booking.id)"
                            class="block"
                        >
                            <div class="rounded-lg border bg-card hover:bg-muted/50 transition-colors p-3 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium leading-tight">{{ booking.room.name }}</p>
                                    <Badge :variant="statusConfig[booking.status].variant" class="text-xs shrink-0">
                                        {{ statusConfig[booking.status].label }}
                                    </Badge>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1"><Clock class="h-3 w-3" />{{ booking.start_time }} – {{ booking.end_time }}</span>
                                    <span class="flex items-center gap-1"><User class="h-3 w-3" />{{ booking.user.name }}</span>
                                </div>
                                <div v-if="booking.purpose" class="text-xs text-muted-foreground truncate">
                                    {{ booking.purpose }}
                                </div>
                            </div>
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- ── LIST VIEW ── -->
            <Card v-else>
                <CardHeader class="pt-4">
                    <CardTitle class="text-base">All Bookings</CardTitle>
                    <CardDescription>{{ allSorted.length }} total booking{{ allSorted.length !== 1 ? 's' : '' }}</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="allSorted.length === 0" class="flex flex-col items-center justify-center py-16 gap-2 text-muted-foreground">
                        <DoorOpen class="h-8 w-8 opacity-30" />
                        <p class="text-sm">No bookings found.</p>
                    </div>
                    <div v-else class="divide-y">
                        <Link
                            v-for="booking in allSorted"
                            :key="booking.id"
                            :href="route('admin.room-bookings.show', booking.id)"
                            class="flex items-center gap-4 px-6 py-3.5 hover:bg-muted/40 transition-colors"
                        >
                            <div class="shrink-0 w-14 text-center">
                                <p class="text-xs text-muted-foreground">{{ new Date(booking.booking_date + 'T00:00:00').toLocaleDateString('en-MY', { month: 'short' }) }}</p>
                                <p class="text-xl font-bold leading-none">{{ new Date(booking.booking_date + 'T00:00:00').getDate() }}</p>
                            </div>
                            <Separator orientation="vertical" class="h-10" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ booking.room.name }} <span class="text-muted-foreground font-normal">({{ booking.room.room_number }})</span></p>
                                <p class="text-xs text-muted-foreground mt-0.5 flex items-center gap-2">
                                    <Clock class="h-3 w-3 shrink-0" />{{ booking.start_time }} – {{ booking.end_time }}
                                    <User class="h-3 w-3 shrink-0 ml-1" />{{ booking.user.name }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-sm font-medium">RM {{ Number(booking.total_cost).toFixed(2) }}</span>
                                <Badge :variant="statusConfig[booking.status]?.variant ?? 'outline'" class="text-xs capitalize">
                                    {{ statusConfig[booking.status]?.label ?? booking.status }}
                                </Badge>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>