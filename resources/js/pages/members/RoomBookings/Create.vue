<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CalendarDate, DateFormatter, getLocalTimeZone, today } from '@internationalized/date';
import {
    Users,
    Clock,
    Calendar as CalendarIcon,
    DoorOpen,
    Info,
    MapPin,
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    rooms: any[];
    libraries: any[];
    existingBookings: any[];
    preselectedRoomId?: number | null;
    preselectedDate?: string | null;
    currentUser: any;
}>();

const breadcrumbs = [
    { title: 'Room Bookings', href: route('member.room-bookings.index') },
    { title: 'New Booking', href: '#' },
];

const selectedRoomId = ref(props.preselectedRoomId || (props.rooms.length > 0 ? props.rooms[0].id : null));
const selectedLibraryId = ref<string>('all');

const formatDistance = (dist?: number) => {
    if (dist === undefined || dist === null) return null;
    return dist < 1 ? (dist * 1000).toFixed(0) + ' m' : dist.toFixed(1) + ' km';
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('latitude') || !urlParams.has('longitude')) {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition((position) => {
                router.get(route('member.room-bookings.create'), {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                }, {
                    preserveScroll: true,
                    preserveState: true,
                    replace: true
                });
            }, (error) => {
                console.error("Error getting location:", error);
            });
        }
    }
});

const filteredRooms = computed(() => {
    if (selectedLibraryId.value === 'all') return props.rooms;
    return props.rooms.filter(r => r.library_id.toString() === selectedLibraryId.value);
});

watch(selectedLibraryId, () => {
    // Reset room selection when library changes
    const first = filteredRooms.value[0];
    selectedRoomId.value = first ? first.id : null;
});

const selectedRoom = computed(() => props.rooms.find(r => r.id === selectedRoomId.value));

const form = useForm({
    room_id: selectedRoomId.value,
    booking_date: props.preselectedDate || new Date().toISOString().split('T')[0],
    start_time: '',
    end_time: '',
    purpose: '',
    number_of_attendees: 1,
    special_requests: '',
    user_id: props.currentUser.id,
});

watch(selectedRoomId, (val) => {
    form.room_id = val;
});

const submit = () => {
    form.post(route('member.room-bookings.store'), {
        preserveScroll: true,
    });
};

const getRoomTypeColor = (type: string) => {
    const t = type.toLowerCase();
    if (t.includes('discussion')) return 'bg-[#c5a059]/10 text-[#c5a059]';
    if (t.includes('study')) return 'bg-emerald-50 text-emerald-600';
    if (t.includes('multimedia')) return 'bg-purple-50 text-purple-600';
    return 'bg-slate-50 text-slate-600';
};

// ── Date picker ──────────────────────────────────────────────────
const df = new DateFormatter('en-MY', { dateStyle: 'long' });

const initDate = () => {
    const d = props.preselectedDate ? new Date(props.preselectedDate) : new Date();
    return new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate());
};

const calendarValue = ref<CalendarDate>(initDate());
const datePickerOpen = ref(false);

watch(calendarValue, (val) => {
    if (val) {
        form.booking_date = `${val.year}-${String(val.month).padStart(2, '0')}-${String(val.day).padStart(2, '0')}`;
        datePickerOpen.value = false;
    }
});

// ── Time slots ───────────────────────────────────────────────────
const timeSlots = computed(() => {
    const slots: string[] = [];
    for (let h = 8; h <= 22; h++) {
        slots.push(`${String(h).padStart(2, '0')}:00`);
        if (h < 22) slots.push(`${String(h).padStart(2, '0')}:30`);
    }
    return slots;
});

const formatTime = (t: string) => {
    if (!t) return '';
    const [h, m] = t.split(':').map(Number);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
};

</script>

<template>
    <Head title="Book A Room" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            
            <!-- Hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6 mb-2">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member portal</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">Book a Room</h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">Select a room and choose your preferred time slot.</p>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Room Selection -->
                <div class="space-y-2">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Step 1: Select Room</h3>
                    
                    <div class="space-y-2 px-1">
                        <Label class="text-[10px] font-bold text-slate-400 uppercase">Filter by Library</Label>
                        <Select v-model="selectedLibraryId">
                            <SelectTrigger class="h-9 rounded-xl border-slate-200">
                                <SelectValue placeholder="All Libraries" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Libraries</SelectItem>
                                <SelectItem v-for="lib in libraries" :key="lib.id" :value="lib.id.toString()">
                                    {{ lib.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-2 max-h-[480px] overflow-y-auto pr-1 scrollbar-thin mt-4">
                        <button
                            v-for="room in filteredRooms"
                            :key="room.id"
                            @click="selectedRoomId = room.id"
                            class="text-left p-3 rounded-2xl border transition-all group relative overflow-hidden shrink-0"
                            :class="selectedRoomId === room.id
                                ? 'border-[#c5a059]/40 bg-[#c5a059]/5'
                                : 'border-slate-100 bg-white hover:bg-slate-50'"
                        >
                            <!-- Selected indicator: brass left accent bar -->
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 rounded-l-2xl transition-all duration-300"
                                :class="selectedRoomId === room.id ? 'bg-[#c5a059]' : 'bg-transparent'"
                            />

                            <div class="flex items-center gap-3 pl-2">
                                <!-- Room image or icon -->
                                <div class="h-12 w-12 rounded-xl overflow-hidden shrink-0 border border-slate-100">
                                    <img v-if="room.image_url" :src="room.image_url" class="h-full w-full object-cover" />
                                    <div v-else class="h-full w-full flex items-center justify-center bg-slate-100 text-slate-400">
                                        <DoorOpen class="h-5 w-5" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="font-bold text-sm truncate text-slate-800">{{ room.name }}</h4>
                                        <span
                                            v-if="selectedRoomId === room.id"
                                            class="text-[9px] font-black uppercase tracking-widest text-[#c5a059] shrink-0"
                                        >Selected</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                        <Badge variant="outline" class="px-1.5 py-0 h-4 text-[9px] font-bold border-0 uppercase" :class="getRoomTypeColor(room.type)">
                                            {{ room.type }}
                                        </Badge>
                                        <span class="text-[10px] font-bold text-slate-400 flex items-center gap-0.5">
                                            <Users class="h-2.5 w-2.5" /> {{ room.capacity }} pax
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 flex items-center gap-0.5">
                                            <MapPin class="h-2.5 w-2.5" /> {{ room.library?.name || 'Central Library' }}
                                        </span>
                                        <Badge v-if="room.distance !== undefined" variant="secondary" class="h-4 rounded-full px-1.5 text-[8px] bg-[#c5a059]/10 text-[#c5a059] border-0">
                                            {{ formatDistance(room.distance) }}
                                        </Badge>
                                    </div>
                                    <div class="mt-1">
                                        <span class="text-[10px] font-black text-[#c5a059]">RM {{ room.hourly_rate }}/h</span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Right: Booking Details -->
                <div class="lg:col-span-2 space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 ml-1">Step 2: Booking Details</h3>
                    <Card class="border-slate-200 rounded-3xl overflow-hidden">
                        <CardContent class="p-5 md:p-8">
                            <form @submit.prevent="submit" class="space-y-8">
                                <!-- Library + Room selectors -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Library</Label>
                                        <Select v-model="selectedLibraryId">
                                            <SelectTrigger class="h-12 rounded-xl border-slate-200 focus:ring-[#c5a059] gap-2">
                                                <MapPin class="h-4 w-4 text-slate-400 shrink-0" />
                                                <SelectValue placeholder="All Libraries" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">All Libraries</SelectItem>
                                                <SelectItem v-for="lib in libraries" :key="lib.id" :value="lib.id.toString()">
                                                    {{ lib.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Meeting Room</Label>
                                        <Select :model-value="selectedRoomId?.toString()" @update:model-value="val => selectedRoomId = Number(val)">
                                            <SelectTrigger class="h-12 rounded-xl border-slate-200 focus:ring-[#c5a059] gap-2">
                                                <DoorOpen class="h-4 w-4 text-slate-400 shrink-0" />
                                                <SelectValue placeholder="Select a room" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="room in filteredRooms" :key="room.id" :value="room.id.toString()">
                                                    {{ room.name }} — {{ room.library?.name }} ({{ room.capacity }} pax · RM {{ room.hourly_rate }}/h)
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                                <div v-if="selectedRoom" class="p-4 bg-[#0d1a14]/5 rounded-2xl border border-[#0d1a14]/10 flex flex-col sm:flex-row gap-4">
                                    <div class="h-24 sm:h-20 w-full sm:w-32 rounded-xl bg-slate-200 overflow-hidden shrink-0 border border-white">
                                        <img v-if="selectedRoom.image_url" :src="selectedRoom.image_url" class="h-full w-full object-cover" />
                                        <div v-else class="h-full w-full flex items-center justify-center bg-slate-100 text-slate-300">
                                            <DoorOpen class="h-8 w-8" />
                                        </div>
                                    </div>
                                    <div class="space-y-1 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4 class="font-black text-[#0d1a14]">{{ selectedRoom.name }}</h4>
                                            <div class="sm:hidden text-right">
                                                <span class="text-sm font-black text-[#c5a059] whitespace-nowrap">RM {{ selectedRoom.hourly_rate }}/h</span>
                                            </div>
                                        </div>
                                        <p class="text-xs text-[#c5a059]/80 font-medium flex items-center gap-1">
                                            <MapPin class="h-3 w-3" />
                                            {{ selectedRoom.room_number }} · Level {{ selectedRoom.floor }}
                                        </p>
                                        <p class="text-[10px] text-slate-500 leading-tight mt-1 line-clamp-2">
                                            {{ selectedRoom.description || 'A professional environment for group discussions and research.' }}
                                        </p>
                                    </div>
                                    <div class="ml-auto text-right hidden sm:block">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase">Rate</span>
                                        <span class="text-lg font-black text-[#c5a059] whitespace-nowrap">RM {{ selectedRoom.hourly_rate }}/h</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Date picker -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Date</Label>
                                        <Popover v-model:open="datePickerOpen">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    class="w-full h-12 rounded-xl border-slate-200 justify-start gap-2.5 font-medium text-sm hover:border-[#c5a059] focus-visible:ring-[#c5a059]"
                                                    :class="!calendarValue && 'text-muted-foreground'"
                                                >
                                                    <CalendarIcon class="h-4 w-4 text-slate-400 shrink-0" />
                                                    {{ calendarValue ? df.format(calendarValue.toDate(getLocalTimeZone())) : 'Pick a date' }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-auto p-0" align="start">
                                                <Calendar
                                                    v-model="calendarValue"
                                                    :min-value="today(getLocalTimeZone())"
                                                />
                                            </PopoverContent>
                                        </Popover>
                                        <div v-if="form.errors.booking_date" class="text-xs text-red-500 font-bold">{{ form.errors.booking_date }}</div>
                                    </div>

                                    <!-- Start time -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-black uppercase tracking-widest text-slate-400">Start Time</Label>
                                        <Select v-model="form.start_time">
                                            <SelectTrigger class="h-12 rounded-xl border-slate-200 focus:ring-[#c5a059] gap-2">
                                                <Clock class="h-4 w-4 text-slate-400 shrink-0" />
                                                <SelectValue placeholder="Select time" />
                                            </SelectTrigger>
                                            <SelectContent class="max-h-60">
                                                <SelectItem v-for="t in timeSlots" :key="t" :value="t">
                                                    {{ formatTime(t) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.start_time" class="text-xs text-red-500 font-bold">{{ form.errors.start_time }}</div>
                                    </div>

                                    <!-- End time -->
                                    <div class="space-y-2">
                                        <Label class="text-xs font-black uppercase tracking-widest text-slate-400">End Time</Label>
                                        <Select v-model="form.end_time">
                                            <SelectTrigger class="h-12 rounded-xl border-slate-200 focus:ring-[#c5a059] gap-2">
                                                <Clock class="h-4 w-4 text-slate-400 shrink-0" />
                                                <SelectValue placeholder="Select time" />
                                            </SelectTrigger>
                                            <SelectContent class="max-h-60">
                                                <SelectItem v-for="t in timeSlots" :key="t" :value="t">
                                                    {{ formatTime(t) }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <div v-if="form.errors.end_time" class="text-xs text-red-500 font-bold">{{ form.errors.end_time }}</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                    <div class="md:col-span-3 space-y-2">
                                        <Label for="purpose" class="text-xs font-black uppercase tracking-widest text-slate-400">Purpose</Label>
                                        <Input 
                                            id="purpose" 
                                            v-model="form.purpose" 
                                            placeholder="e.g. Group study for exam" 
                                            class="h-12 rounded-xl border-slate-200 focus-visible:ring-[#c5a059]"
                                        />
                                    </div>
                                    <div class="md:col-span-1 space-y-2">
                                        <Label for="attendees" class="text-xs font-black uppercase tracking-widest text-slate-400">Attendees</Label>
                                        <div class="relative">
                                            <Input 
                                                id="attendees" 
                                                type="number"
                                                v-model="form.number_of_attendees" 
                                                min="1"
                                                :max="selectedRoom?.capacity || 10"
                                                class="h-12 rounded-xl border-slate-200 focus-visible:ring-[#c5a059] pl-10"
                                                required
                                            />
                                            <Users class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="special_requests" class="text-xs font-black uppercase tracking-widest text-slate-400">Special Requests (Optional)</Label>
                                    <Textarea 
                                        id="special_requests" 
                                        v-model="form.special_requests" 
                                        placeholder="Any specific setup or equipment needed?" 
                                        class="min-h-[100px] rounded-2xl border-slate-200 focus-visible:ring-[#c5a059] py-3"
                                    />
                                </div>

                                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-6 border-t border-slate-100">
                                    <div class="flex items-start gap-3 w-full sm:w-auto">
                                        <div class="shrink-0 h-10 w-10 rounded-xl bg-[#c5a059]/10 flex items-center justify-center border border-[#c5a059]/20">
                                            <Info class="h-5 w-5 text-[#c5a059]" />
                                        </div>
                                        <div class="space-y-0.5">
                                            <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">Booking Policy</h4>
                                            <p class="text-[10px] text-slate-500 font-medium max-w-xs">Bookings are subject to approval. You will be notified via email.</p>
                                        </div>
                                    </div>
                                    
                                    <Button 
                                        type="submit" 
                                        class="w-full sm:w-auto bg-[#0d1a14] hover:bg-[#122010] text-[#f1f5f9] rounded-xl h-14 px-10 font-black transition-all active:scale-95 disabled:opacity-50 flex items-center gap-2"
                                        :disabled="form.processing"
                                    >
                                        <Check class="h-5 w-5" />
                                        {{ form.processing ? 'Booking...' : 'Confirm Reservation' }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
