<script setup lang="ts">
import { route } from 'ziggy-js';
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { CalendarDays, Clock, Users, CircleDollarSign, AlertCircle, ImageOff, Search, User as UserIcon, X } from 'lucide-vue-next';
import axios from 'axios';

interface Room {
    id: number;
    name: string;
    room_number: string;
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

interface Booking {
    id: number;
    room_id: number;
    booking_date: string;
    start_time: string;
    end_time: string;
    purpose: string | null;
    notes: string | null;
    status: string;
    total_cost: number;
    user: { id: number; name: string; email: string };
}

interface ExistingBooking {
    id: number;
    booking_date: string;
    start_time: string;
    end_time: string;
}

const props = defineProps<{
    booking: Booking;
    rooms: Room[];
    existingBookings: ExistingBooking[];
    currentUser: { id: number; name: string; isStaff: boolean };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Room Bookings', href: route('admin.room-bookings.index') },
    { title: `Booking #${props.booking.id}`, href: route('admin.room-bookings.show', props.booking.id) },
    { title: 'Edit', href: '#' },
];

const timeSlots: string[] = [];
for (let h = 8; h <= 22; h++) {
    timeSlots.push(`${String(h).padStart(2, '0')}:00`);
    if (h < 22) timeSlots.push(`${String(h).padStart(2, '0')}:30`);
}

const form = useForm({
    room_id:      String(props.booking.room_id),
    user_id:      props.booking.user.id,
    booking_date: props.booking.booking_date,
    start_time:   props.booking.start_time,
    end_time:     props.booking.end_time,
    purpose:      props.booking.purpose ?? '',
    notes:        props.booking.notes ?? '',
    _method:      'PUT',
});

// User Search state
const userSearchQ = ref('');
const userSearchResults = ref<User[]>([]);
const isSearchingUsers = ref(false);
const selectedUser = ref<User | null>(props.booking.user);

const searchUsers = async () => {
    if (userSearchQ.value.length < 2) {
        userSearchResults.value = [];
        return;
    }
    isSearchingUsers.value = true;
    try {
        const res = await axios.get(route('api.users.search'), { params: { q: userSearchQ.value } });
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

const conflictsOnDate = computed(() => {
    if (!form.room_id || !form.booking_date) return [];
    return props.existingBookings.filter(b =>
        String(b.booking_date) === form.booking_date && b.id !== props.booking.id
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

watch(() => form.start_time, () => {
    if (form.end_time && form.start_time >= form.end_time) form.end_time = '';
});

const todayStr = new Date().toISOString().split('T')[0];

const submit = () => {
    const data = new FormData();
    data.append('_method', 'PUT');
    data.append('room_id', form.room_id);
    data.append('user_id', String(form.user_id));
    data.append('booking_date', form.booking_date);
    data.append('start_time', form.start_time);
    data.append('end_time', form.end_time);
    data.append('purpose', form.purpose ?? '');
    data.append('notes', form.notes ?? '');

    router.post(route('admin.room-bookings.update', props.booking.id), data, {
        onStart: () => { form.processing = true; },
        onFinish: () => { form.processing = false; },
        onError: (errors) => { form.errors = errors; },
    });
};
</script>

<template>
    <Head :title="`Edit Booking #${booking.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 space-y-6">
            <FlashAlert />

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                        <CalendarDays class="h-6 w-6" /> Edit Booking #{{ booking.id }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Modify the booking details below. Only pending bookings can be edited.
                    </p>
                </div>
                <Link :href="route('admin.room-bookings.show', booking.id)">
                    <Button variant="outline" size="sm">Cancel</Button>
                </Link>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Form -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader class="pt-4">
                            <CardTitle class="text-base">Booking Details</CardTitle>
                            <CardDescription>Update the room, date, and time for this booking.</CardDescription>
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
                                            v-for="room in rooms"
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
                                <Input
                                    id="booking_date"
                                    type="date"
                                    v-model="form.booking_date"
                                    :min="todayStr"
                                    :class="{ 'border-destructive': form.errors.booking_date }"
                                />
                                <p v-if="form.errors.booking_date" class="text-xs text-destructive">{{ form.errors.booking_date }}</p>
                            </div>

                            <!-- Existing bookings hint -->
                            <div v-if="form.booking_date && form.room_id && conflictsOnDate.length > 0"
                                class="rounded-lg border bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800 p-3 space-y-1.5">
                                <p class="text-xs font-medium text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                    <AlertCircle class="h-3.5 w-3.5" /> Other bookings on this date
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
                                This time slot overlaps with another booking. Please choose a different time.
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
                                        <div v-else-if="userSearchQ.length >= 2" class="absolute z-50 w-full mt-1 bg-background border rounded-md shadow-sm p-3 text-center text-sm text-muted-foreground">
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

                <!-- Sidebar -->
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
                        </CardContent>
                    </Card>

                    <!-- Cost Summary -->
                    <Card>
                        <CardHeader class="pt-4">
                            <CardTitle class="text-sm">Updated Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 pb-5 text-sm">
                            <div class="flex justify-between text-muted-foreground text-xs">
                                <span>Previous Total</span>
                                <span>RM {{ Number(booking.total_cost).toFixed(2) }}</span>
                            </div>
                            <Separator />
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
                                <span>New Total</span>
                                <span class="text-primary">{{ estimatedCost > 0 ? `RM ${estimatedCost.toFixed(2)}` : '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pb-4">
                <Link :href="route('admin.room-bookings.show', booking.id)">
                    <Button type="button" variant="outline" :disabled="form.processing">Cancel</Button>
                </Link>
                <Button
                    type="button"
                    @click="submit"
                    :disabled="form.processing || hasTimeConflict || !form.room_id || !form.booking_date || !form.start_time || !form.end_time"
                    class="min-w-36"
                >
                    <span v-if="form.processing">Saving…</span>
                    <span v-else>Save Changes</span>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>