<script setup lang="ts">
import { ref, computed, watch, type Ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { index, store } from '@/routes/admin/room-bookings';
import { search as searchUsersRoute } from '@/routes/api/users';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { DateValue } from '@internationalized/date';
import { CalendarDate, fromDate, getLocalTimeZone, toCalendarDate } from '@internationalized/date';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { CalendarIcon, CalendarDays, Clock, DoorOpen, Users, CircleDollarSign, AlertCircle, ImageOff, Search, User as UserIcon, X } from 'lucide-vue-next';
import axios from 'axios';
import { cn } from '@/lib/utils';

interface Room {
    id: number;
    name: string;
    room_number: string;
    type: string;
    capacity: number;
    floor: number | null;
    hourly_rate: number;
    image_url: string | null;
    status: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface ExistingBooking {
    booking_date: string;
    start_time: string;
    end_time: string;
}

const props = defineProps<{
    rooms: Room[];
    existingBookings: ExistingBooking[];   // all confirmed/pending bookings for conflict check
    preselectedRoomId?: number;
    preselectedDate?: string;
    currentUser: { id: number; name: string; email: string; isStaff: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Room Bookings', href: index.url() },
    { title: 'New Booking', href: '#' },
];

// Time slot options (30 min increments, 8am–10pm)
const timeSlots: string[] = [];
for (let h = 8; h <= 22; h++) {
    timeSlots.push(`${String(h).padStart(2, '0')}:00`);
    if (h < 22) timeSlots.push(`${String(h).padStart(2, '0')}:30`);
}

const form = useForm({
    room_id:      props.preselectedRoomId ? String(props.preselectedRoomId) : '',
    user_id:      props.currentUser.id,
    booking_date: props.preselectedDate ?? '',
    start_time:   '',
    end_time:     '',
    purpose:      '',
    notes:        '',
});

// User Search state
const userSearchQ = ref('');
const userSearchResults = ref<User[]>([]);
const isSearchingUsers = ref(false);
const selectedUser = ref<User | null>(null);

const bookingDate = ref(
    props.preselectedDate 
        ? fromDate(new Date(props.preselectedDate), getLocalTimeZone()) 
        : fromDate(new Date(), getLocalTimeZone())
) as Ref<DateValue>;
const showBookingCalendar = ref(false);

// Today's date for disabling past dates
const today = fromDate(new Date(), getLocalTimeZone());
const isDateDisabled = (date: DateValue) => {
    return date.compare(today) < 0;
};

const searchUsers = async () => {
    if (userSearchQ.value.length < 2) {
        userSearchResults.value = [];
        return;
    }
    isSearchingUsers.value = true;
    try {
        const res = await axios.get(searchUsersRoute.url(), { params: { q: userSearchQ.value } });
        userSearchResults.value = res.data.data;
    } catch (err) {
        console.error('User search failed', err);
    } finally {
        isSearchingUsers.value = false;
    }
};

const selectUser = (u: User) => {
    selectedUser.value = u;
    form.user_id = u.id;
    userSearchResults.value = [];
    userSearchQ.value = '';
};

const clearUser = () => {
    selectedUser.value = null;
    form.user_id = props.currentUser.id;
};

const selectedRoom = computed<Room | null>(() =>
    props.rooms.find(r => String(r.id) === form.room_id) ?? null
);

// Duration and cost
const durationHours = computed(() => {
    if (!form.start_time || !form.end_time) return 0;
    const [sh, sm] = form.start_time.split(':').map(Number);
    const [eh, em] = form.end_time.split(':').map(Number);
    const mins = (eh * 60 + em) - (sh * 60 + sm);
    return mins > 0 ? mins / 60 : 0;
});

const estimatedCost = computed(() => {
    if (!selectedRoom.value || durationHours.value <= 0) return 0;
    return selectedRoom.value.hourly_rate * durationHours.value;
});

// Format date for display
const formatDateDisplay = (dateValue: DateValue | undefined): string => {
    if (!dateValue) return 'Pick a date';
    return new Date(dateValue.year, dateValue.month - 1, dateValue.day).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Convert DateValue to YYYY-MM-DD string
const dateValueToString = (dateValue: DateValue): string => {
    return `${dateValue.year}-${String(dateValue.month).padStart(2, '0')}-${String(dateValue.day).padStart(2, '0')}`;
};

// Handle borrowed date change
const handleBookingDateChange = (date: DateValue | undefined) => {
    if (date) {
        bookingDate.value = date;
        form.booking_date = dateValueToString(date);
        showBookingCalendar.value = false;
    }
};

// Conflict detection
const conflictsOnDate = computed(() => {
    if (!form.room_id || !form.booking_date) return [];
    return props.existingBookings.filter(b =>
        String(b.booking_date) === form.booking_date
    );
});

const hasTimeConflict = computed(() => {
    if (!form.start_time || !form.end_time || durationHours.value <= 0) return false;
    const toMin = (t: string) => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
    const newStart = toMin(form.start_time);
    const newEnd   = toMin(form.end_time);
    return conflictsOnDate.value.some(b => {
        const bs = toMin(b.start_time);
        const be = toMin(b.end_time);
        return newStart < be && newEnd > bs;
    });
});

const endTimeOptions = computed(() =>
    timeSlots.filter(t => {
        if (!form.start_time) return true;
        const [sh, sm] = form.start_time.split(':').map(Number);
        const [th, tm] = t.split(':').map(Number);
        return th * 60 + tm > sh * 60 + sm;
    })
);

// Reset end time if start time moves past it
watch(() => form.start_time, () => {
    if (form.end_time && form.start_time >= form.end_time) form.end_time = '';
});

// Minimum date = today
const todayStr = new Date().toISOString().split('T')[0];

const submit = () => {
    form.post(store.url());
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="New Booking" />
        
        <div class="px-6 pt-2 pb-8 space-y-6">
            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">New Room Booking <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Reserve a room for your session.</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Form -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader class="pt-4">
                            <CardTitle class="text-base">Booking Details</CardTitle>
                            <CardDescription>Select a room, date and time slot.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-5 py-2 pb-6">

                            <!-- Room select -->
                            <div class="space-y-2">
                                <Label>Room <span class="text-destructive">*</span></Label>
                                <Select v-model="form.room_id">
                                    <SelectTrigger :class="{ 'border-destructive': form.errors.room_id }">
                                        <SelectValue placeholder="Choose a room…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="room in rooms.filter(r => r.status === 'available')"
                                            :key="room.id"
                                            :value="String(room.id)"
                                        >
                                            {{ room.name }} ({{ room.room_number }}) — RM {{ Number(room.hourly_rate).toFixed(2) }}/hr
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.room_id" class="text-xs text-destructive">{{ form.errors.room_id }}</p>
                            </div>

                            <!-- Date -->
                            <div class="space-y-2">
                                <Label for="booking_date">Date <span class="text-destructive">*</span></Label>
                                <Popover v-model:open="showBookingCalendar">
                                    <PopoverTrigger as-child>
                                        <Button variant="outline" :class="cn(
                                            'w-full justify-start text-left font-normal mt-2',
                                            form.errors.booking_date && 'border-destructive'
                                        )">
                                            <CalendarIcon class="mr-2 h-4 w-4" />
                                            {{ formatDateDisplay(bookingDate) }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-auto p-0" align="start">
                                        <Calendar 
                                            v-model="bookingDate" 
                                            @update:model-value="handleBookingDateChange"
                                            :is-date-disabled="isDateDisabled"
                                            :max-value="new CalendarDate(2035, 12, 31)" 
                                            class="rounded-md border" 
                                        />
                                    </PopoverContent>
                                </Popover>
                                <p v-if="form.errors.booking_date" class="text-xs text-destructive">{{ form.errors.booking_date }}</p>
                            </div>

                            <!-- Existing bookings hint -->
                            <div v-if="form.booking_date && form.room_id && conflictsOnDate.length > 0"
                                class="rounded-lg border bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800 p-3 space-y-1.5">
                                <p class="text-xs font-medium text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                    <AlertCircle class="h-3.5 w-3.5" /> Existing bookings on this date
                                </p>
                                <div v-for="b in conflictsOnDate" :key="`${b.start_time}-${b.end_time}`"
                                    class="text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1">
                                    <Clock class="h-3 w-3" /> {{ b.start_time }} – {{ b.end_time }}
                                </div>
                            </div>

                            <!-- Start / End time -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label>Start Time <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.start_time">
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.start_time }">
                                            <SelectValue placeholder="Start" />
                                        </SelectTrigger>
                                        <SelectContent class="max-h-56">
                                            <SelectItem v-for="t in timeSlots" :key="t" :value="t">{{ t }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.start_time" class="text-xs text-destructive">{{ form.errors.start_time }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label>End Time <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.end_time" :disabled="!form.start_time">
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.end_time || hasTimeConflict }">
                                            <SelectValue placeholder="End" />
                                        </SelectTrigger>
                                        <SelectContent class="max-h-56">
                                            <SelectItem v-for="t in endTimeOptions" :key="t" :value="t">{{ t }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.end_time" class="text-xs text-destructive">{{ form.errors.end_time }}</p>
                                </div>
                            </div>

                            <div v-if="hasTimeConflict" class="rounded-lg border border-destructive bg-destructive/10 p-3 text-xs text-destructive flex items-center gap-2">
                                <AlertCircle class="h-4 w-4 shrink-0" />
                                This time slot overlaps with an existing booking. Please choose a different time.
                            </div>

                            <!-- User Search (Staff Only) -->
                            <div v-if="currentUser.isStaff" class="space-y-3 pt-2">
                                <Separator />
                                <div class="space-y-2">
                                    <Label>Book for</Label>
                                    
                                    <!-- Selected User Display -->
                                    <div v-if="selectedUser" class="flex items-center justify-between p-3 rounded-lg border bg-muted/30">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">
                                                {{ selectedUser.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">{{ selectedUser.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ selectedUser.email }}</p>
                                            </div>
                                        </div>
                                        <Button variant="ghost" size="icon" class="h-7 w-7" @click="clearUser">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>

                                    <!-- Search Input -->
                                    <div v-else class="relative">
                                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                            <Search class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <Input
                                            v-model="userSearchQ"
                                            @input="searchUsers"
                                            placeholder="Search by name or email…"
                                            class="pl-9"
                                        />
                                        
                                        <!-- Search Results Dropdown -->
                                        <div v-if="userSearchResults.length > 0" class="absolute z-50 w-full mt-1 bg-background border rounded-md shadow-lg overflow-hidden divide-y">
                                            <button
                                                v-for="u in userSearchResults"
                                                :key="u.id"
                                                type="button"
                                                @click="selectUser(u)"
                                                class="w-full text-left px-4 py-2.5 hover:bg-muted transition-colors flex flex-col"
                                            >
                                                <span class="text-sm font-medium">{{ u.name }}</span>
                                                <span class="text-xs text-muted-foreground">{{ u.email }}</span>
                                            </button>
                                        </div>
                                        <div v-else-if="userSearchQ.length >= 2 && !isSearchingUsers" class="absolute z-50 w-full mt-1 bg-background border rounded-md shadow-sm p-3 text-center text-sm text-muted-foreground">
                                            No users found.
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-muted-foreground">Defaults to you if no user is selected.</p>
                                </div>
                            </div>

                            <Separator />

                            <!-- Purpose -->
                            <div class="space-y-2">
                                <Label for="purpose">Purpose</Label>
                                <Input
                                    id="purpose"
                                    v-model="form.purpose"
                                    placeholder="e.g. Group study session, Team meeting…"
                                    :class="{ 'border-destructive': form.errors.purpose }"
                                />
                                <p v-if="form.errors.purpose" class="text-xs text-destructive">{{ form.errors.purpose }}</p>
                            </div>

                            <!-- Notes -->
                            <div class="space-y-2">
                                <Label for="notes">Additional Notes</Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Any special requirements or notes…"
                                    rows="3"
                                    :class="{ 'border-destructive': form.errors.notes }"
                                />
                                <p v-if="form.errors.notes" class="text-xs text-destructive">{{ form.errors.notes }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar: Summary -->
                <div class="space-y-5">
                    <!-- Room Preview -->
                    <Card>
                        <div v-if="selectedRoom?.image_url" class="h-32 overflow-hidden rounded-t-lg">
                            <img :src="selectedRoom.image_url" :alt="selectedRoom.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="h-32 bg-muted rounded-t-lg flex items-center justify-center">
                            <ImageOff class="h-6 w-6 text-muted-foreground opacity-30" />
                        </div>
                        <CardContent class="pt-4 pb-5 space-y-2">
                            <div v-if="selectedRoom">
                                <p class="font-semibold text-sm">{{ selectedRoom.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ selectedRoom.room_number }}</p>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 mt-3">
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Users class="h-3.5 w-3.5" /> {{ selectedRoom.capacity }} pax
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <CircleDollarSign class="h-3.5 w-3.5" /> RM {{ Number(selectedRoom.hourly_rate).toFixed(2) }}/hr
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-muted-foreground text-center py-2">No room selected</p>
                        </CardContent>
                    </Card>

                    <!-- Cost Summary -->
                    <Card>
                        <CardHeader class="py-4">
                            <CardTitle class="text-sm">Booking Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 pb-5 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Duration</span>
                                <span class="font-medium">{{ durationHours > 0 ? `${durationHours}h` : '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Rate</span>
                                <span class="font-medium">{{ selectedRoom ? `RM ${Number(selectedRoom.hourly_rate).toFixed(2)}/hr` : '—' }}</span>
                            </div>
                            <Separator />
                            <div class="flex justify-between font-semibold">
                                <span>Estimated Total</span>
                                <span class="text-primary">{{ estimatedCost > 0 ? `RM ${estimatedCost.toFixed(2)}` : '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pb-4">
                <Link :href="index.url()">
                    <Button type="button" variant="outline" :disabled="form.processing">Cancel</Button>
                </Link>
                <Button
                    type="button"
                    @click="submit"
                    :disabled="form.processing || hasTimeConflict || !form.room_id || !form.booking_date || !form.start_time || !form.end_time"
                    class="min-w-36"
                >
                    <span v-if="form.processing">Booking…</span>
                    <span v-else>Confirm Booking</span>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>