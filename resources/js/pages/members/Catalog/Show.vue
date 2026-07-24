<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { BookOpen, User, Hash, Globe, Calendar, Layers, MapPin, BookOpenText, ShoppingBag, ArrowLeft, Bookmark } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { onMounted, onUnmounted, ref, computed } from 'vue';

const props = defineProps<{
    book: any;
    available_copies_count: number;
    is_member: boolean;
}>();

const breadcrumbs = [
    { title: 'Catalog', href: route('member.catalog.index') },
    { title: props.book.title, href: route('member.catalog.show', props.book.id) },
];

const borrowForm = useForm({});

const borrowBook = () => {
    if (!confirm('Are you sure you want to borrow this book now?')) return;
    borrowForm.post(route('member.catalog.borrow', props.book.id));
};

const formatLabel = (key: string) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatDistance = (dist?: number) => {
    if (dist === undefined || dist === null) return null;
    return dist < 1 ? (dist * 1000).toFixed(0) + ' m' : dist.toFixed(1) + ' km';
};

// Haversine formula — returns distance in km between two lat/lng points
const haversine = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

// Reactive distances keyed by copy id
const distances = ref<Record<number, number>>({});

const sortedCopies = computed(() => {
    const hasDist = Object.keys(distances.value).length > 0;
    return [...props.book.copies].map(copy => ({
        ...copy,
        distance: distances.value[copy.id] ?? undefined,
    })).sort((a, b) => {
        if (hasDist) {
            const da = a.distance ?? Infinity;
            const db = b.distance ?? Infinity;
            return da - db;
        }
        // fallback: available first
        if (a.status === 'available' && b.status !== 'available') return -1;
        if (b.status === 'available' && a.status !== 'available') return 1;
        return 0;
    });
});

const updateDistances = (position: GeolocationPosition) => {
    const userLat = position.coords.latitude;
    const userLng = position.coords.longitude;

    const result: Record<number, number> = {};
    for (const copy of props.book.copies) {
        const lib = copy.library;
        if (lib?.latitude && lib?.longitude) {
            result[copy.id] = haversine(userLat, userLng, Number(lib.latitude), Number(lib.longitude));
        }
    }
    distances.value = result;
};

let watcherId: number | null = null;

onMounted(() => {
    if (!('geolocation' in navigator)) return;

    watcherId = navigator.geolocation.watchPosition(
        updateDistances,
        (err) => {
            // Blocked on HTTP — run with a debug mock position so the UI can be tested
            // Remove this fallback once HTTPS is enabled (run: herd secure library-management)
            if (import.meta.env.DEV) {
                console.warn('[Distance] Geolocation blocked (HTTP). Run `herd secure` for real distances.');
                // Mock: use the first library's own coords so you see "0 m away"
                const firstLib = props.book.copies?.[0]?.library;
                if (firstLib?.latitude && firstLib?.longitude) {
                    updateDistances({
                        coords: {
                            latitude: Number(firstLib.latitude),
                            longitude: Number(firstLib.longitude),
                            accuracy: 0, altitude: null, altitudeAccuracy: null,
                            heading: null, speed: null,
                        },
                        timestamp: Date.now(),
                    } as GeolocationPosition);
                }
            }
        },
        { enableHighAccuracy: false, maximumAge: 10000, timeout: 10000 }
    );
});

onUnmounted(() => {
    if (watcherId !== null) {
        navigator.geolocation.clearWatch(watcherId);
    }
});
</script>

<template>
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8">
            <FlashAlert />

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Book Details <span class="text-emerald-600 text-6xl leading-none">.</span></h1>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <!-- Left: Visual Column -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-100 aspect-[3/4]">
                        <div class="h-full w-full flex items-center justify-center p-8 bg-slate-50">
                            <img 
                                v-if="book.cover_image" 
                                :src="book.cover_image" 
                                :alt="book.title"
                                class="h-full object-contain shadow-2xl skew-y-1 group-hover:skew-y-0 transition-transform duration-700"
                            />
                        </div>
                        <div v-if="!book.cover_image" class="w-full h-full flex flex-col items-center justify-center p-12 text-slate-300">
                            <BookOpen class="h-24 w-24 mb-4" />
                            <span class="text-lg font-black uppercase tracking-widest">No Cover Image</span>
                        </div>
                        
                        <div class="absolute top-4 right-4 animate-in fade-in zoom-in duration-500">
                            <Badge 
                                variant="default" 
                                class="px-4 py-1.5 shadow-xl border-0 text-xs font-bold"
                                :class="available_copies_count > 0 ? 'bg-emerald-500' : 'bg-slate-500'"
                            >
                                {{ available_copies_count > 0 ? 'Available Now' : 'Currently Borrowed' }}
                            </Badge>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Pages</span>
                            <div class="flex items-center gap-2">
                                <Layers class="h-3.5 w-3.5 text-indigo-500" />
                                <span class="text-sm font-black text-slate-900">{{ book.pages || 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-1">Language</span>
                            <div class="flex items-center gap-2">
                                <Globe class="h-3.5 w-3.5 text-purple-500" />
                                <span class="text-sm font-black text-slate-900 uppercase">{{ book.language || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Content Column -->
                <div class="lg:col-span-8 space-y-10">
                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <Badge variant="secondary" class="bg-indigo-50 text-indigo-600 border-indigo-100 font-bold uppercase tracking-widest text-[10px] px-3 py-1">
                                {{ book.category?.name || 'Uncategorized' }}
                            </Badge>
                            <Badge v-for="genre in book.genres" :key="genre.id" variant="outline" class="text-[10px] border-slate-200 text-slate-500 font-bold tracking-widest uppercase">
                                {{ genre.name }}
                            </Badge>
                        </div>
                        <h1 class="text-4xl md:text-6xl font-black text-slate-900 tracking-tight leading-[1.1]">
                            {{ book.title }}
                        </h1>
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 flex items-center justify-center bg-indigo-600 rounded-full text-white shadow-lg shadow-indigo-100">
                                <User class="h-5 w-5" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">Author</span>
                                <span class="text-xl font-bold text-slate-800">{{ book.author_name }}</span>
                            </div>
                        </div>
                    </div>

                    <Separator class="bg-slate-100" />

                    <div class="prose prose-slate max-w-none">
                        <h3 class="text-xl font-black text-slate-900 mb-4 flex items-center gap-2">
                            <BookOpenText class="h-5 w-5 text-indigo-500" />
                            Synopsis
                        </h3>
                        <p class="text-slate-600 leading-relaxed text-lg">
                            {{ book.description || 'No description provided for this book.' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-slate-50 p-6 rounded-3xl space-y-4 border border-slate-100">
                            <h3 class="font-black text-slate-900 tracking-tight flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-purple-500" />
                                Edition Details
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">ISBN</span>
                                    <span class="text-xs font-black text-slate-800 font-mono">{{ book.isbn || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Publisher</span>
                                    <span class="text-xs font-black text-slate-800">{{ book.publisher?.name || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400 font-bold uppercase tracking-widest">Published</span>
                                    <span class="text-xs font-black text-slate-800">{{ book.published_year || 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-4 rounded-3xl border-4 border-indigo-600/10 space-y-6 shadow-xl shadow-indigo-50 flex flex-col items-center text-center justify-center">
                            <div class="space-y-2">
                                <p class="text-sm font-black text-slate-900">Current Availability</p>
                                <div class="flex items-center justify-center gap-2">
                                    <span class="text-4xl font-black text-indigo-600">{{ available_copies_count }}</span>
                                    <span class="text-slate-400 text-sm font-bold uppercase tracking-widest">Available</span>
                                </div>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest italic">
                                    Out of {{ book.copies?.length || 0 }} total copies
                                </p>
                            </div>

                            <Button 
                                v-if="is_member && available_copies_count > 0"
                                size="lg" 
                                @click="borrowBook"
                                :disabled="borrowForm.processing"
                                class="w-4xs h-9 bg-indigo-600 hover:bg-slate-900 rounded-xl font-black text-lg shadow-2xl shadow-indigo-200 transition-all active:scale-95 group"
                            >
                                <ShoppingBag v-if="!borrowForm.processing" class="h-6 w-6 mr-1 group-hover:animate-bounce" />
                                {{ borrowForm.processing ? 'Processing...' : 'Borrow' }}
                            </Button>
                            <p v-else-if="!is_member" class="text-sm font-bold text-slate-400 max-w-[200px]">
                                Staff and Librarians can manage copies in the admin dashboard.
                            </p>
                            <p v-else class="text-sm font-black text-red-500 animate-pulse uppercase tracking-tight">
                                This book is currently fully checked out.
                            </p>
                        </div>
                    </div>

                    <!-- Fine Details -->
                    <Card v-if="book.copies && book.copies.length > 0" class="rounded-3xl shadow-sm border-slate-100 overflow-hidden">
                        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h4 class="text-sm font-black text-slate-900 flex items-center gap-2">
                                <Bookmark class="h-4 w-4 text-indigo-600" />
                                Copy Register
                            </h4>
                            <span class="text-[10px] bg-white px-2 py-0.5 rounded-full border border-slate-200 font-bold uppercase text-slate-500">
                                {{ book.copies.length }} Copies
                            </span>
                        </div>
                        <CardContent class="pt-0 px-2">
                            <!-- Desktop Table View -->
                            <div class="hidden md:block">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-100/50 text-slate-400 text-[10px] font-black tracking-widest uppercase">
                                        <tr>
                                            <th class="px-6 py-3 text-start">Library / Location</th>
                                            <th class="px-6 py-3 text-start">Book Number</th>
                                            <th class="px-6 py-3 text-start">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="copy in sortedCopies" :key="copy.id" class="text-sm group hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex flex-col">
                                                    <span class="font-black text-slate-800">{{ copy.library?.name || 'Central Library' }}</span>
                                                    <div class="flex items-center gap-2 mt-0.5">
                                                        <span v-if="copy.location" class="text-xs text-slate-500 flex items-center gap-1">
                                                            <MapPin class="h-3 w-3" /> {{ copy.location }}
                                                        </span>
                                                        <Badge v-if="copy.distance !== undefined" variant="secondary" class="h-5 rounded-full px-2 text-[10px] bg-indigo-50 text-indigo-600 border-0">
                                                            {{ formatDistance(copy.distance) }} away
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <Badge 
                                                    variant="outline"
                                                >
                                                    {{ copy.call_number }}
                                                </Badge>
                                            </td>
                                            <td class="px-6 py-4">
                                                <Badge 
                                                    variant="outline" 
                                                    class="text-[10px] border-0 font-bold uppercase"
                                                    :class="copy.status === 'available' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
                                                >
                                                    {{ copy.status }}
                                                </Badge>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="md:hidden divide-y divide-slate-100">
                                <div v-for="copy in sortedCopies" :key="copy.id" class="p-4 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Barcode</span>
                                            <span class="text-sm font-mono font-bold text-slate-600">{{ copy.barcode }}</span>
                                        </div>
                                        <Badge 
                                            variant="outline" 
                                            class="text-[10px] border-0 font-bold uppercase px-3 py-1"
                                            :class="copy.status === 'available' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
                                        >
                                            {{ copy.status }}
                                        </Badge>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Library / Location</span>
                                        <div class="flex items-center flex-wrap gap-2 mt-0.5">
                                            <div class="flex items-center gap-1">
                                                <MapPin class="h-3 w-3 text-indigo-500" />
                                                <span class="text-xs font-black text-slate-700">{{ copy.library?.name || 'Central Library' }}</span>
                                            </div>
                                            <span v-if="copy.location" class="text-[10px] text-slate-500">({{ copy.location }})</span>
                                            <Badge v-if="copy.distance !== undefined" variant="secondary" class="h-4 rounded-full px-2 text-[9px] bg-indigo-50 text-indigo-600 border-0">
                                                {{ formatDistance(copy.distance) }} away
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
