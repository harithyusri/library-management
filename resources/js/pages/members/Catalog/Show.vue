<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { BookOpen, User, Globe, Calendar, MapPin, ShoppingBag, Bell, Star, Trash2, BookMarked, Building2, ChevronLeft, Quote, Library } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { onMounted, onUnmounted, ref, computed } from 'vue';

const props = defineProps<{
    book: any;
    available_copies_count: number;
    is_member: boolean;
    has_active_reservation: boolean;
    has_borrowed: boolean;
    user_review: any | null;
    recommended_books: any[];
}>();

const breadcrumbs = [
    { title: 'Catalog', href: route('member.catalog.index') },
    { title: props.book.title, href: route('member.catalog.show', props.book.id) },
];

const borrowForm = useForm({ library_id: null as number | null });
const reserveForm = useForm({});
const reviewForm = useForm({ rating: props.user_review?.rating ?? 0, body: props.user_review?.body ?? '' });

const showLibraryDialog = ref(false);

const availableCopiesByLibrary = computed(() => {
    const grouped: Record<number, { library: any; copies: any[]; distance?: number }> = {};
    for (const copy of props.book.copies) {
        if (copy.status !== 'available') continue;
        const libId = copy.library?.id ?? 0;
        if (!grouped[libId]) {
            grouped[libId] = { library: copy.library, copies: [], distance: distances.value[copy.id] };
        }
        grouped[libId].copies.push(copy);
        if (distances.value[copy.id] !== undefined) {
            grouped[libId].distance = Math.min(grouped[libId].distance ?? Infinity, distances.value[copy.id]);
        }
    }
    return Object.values(grouped).sort((a, b) => (a.distance ?? Infinity) - (b.distance ?? Infinity));
});

const openBorrowDialog = () => { showLibraryDialog.value = true; };

const selectLibrary = (libraryId: number) => {
    borrowForm.library_id = libraryId;
    showLibraryDialog.value = false;
    borrowForm.post(route('member.catalog.borrow', props.book.id));
};

const reserveBook = () => {
    if (!confirm('Join the waitlist for this book?')) return;
    reserveForm.post(route('member.reservations.store', props.book.id));
};

const submitReview = () => {
    reviewForm.post(route('member.catalog.review.store', props.book.id), { preserveScroll: true });
};

const deleteReview = () => {
    if (!confirm('Delete your review?')) return;
    reviewForm.delete(route('member.catalog.review.destroy', props.user_review.id), { preserveScroll: true });
};

const avgRating = computed(() => {
    if (!props.book.reviews?.length) return 0;
    return props.book.reviews.reduce((s: number, r: any) => s + r.rating, 0) / props.book.reviews.length;
});

const formatDistance = (dist?: number) => {
    if (dist === undefined || dist === null) return null;
    return dist < 1 ? (dist * 1000).toFixed(0) + ' m' : dist.toFixed(1) + ' km';
};

const haversine = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

const distances = ref<Record<number, number>>({});

const sortedCopies = computed(() => {
    const hasDist = Object.keys(distances.value).length > 0;
    return [...props.book.copies].map(copy => ({ ...copy, distance: distances.value[copy.id] })).sort((a, b) => {
        if (hasDist) return (a.distance ?? Infinity) - (b.distance ?? Infinity);
        if (a.status === 'available' && b.status !== 'available') return -1;
        if (b.status === 'available' && a.status !== 'available') return 1;
        return 0;
    });
});

const updateDistances = (position: GeolocationPosition) => {
    const { latitude: userLat, longitude: userLng } = position.coords;
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
    watcherId = navigator.geolocation.watchPosition(updateDistances, () => {}, { enableHighAccuracy: false, maximumAge: 10000, timeout: 10000 });
});

onUnmounted(() => { if (watcherId !== null) navigator.geolocation.clearWatch(watcherId); });
</script>

<template>
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-6xl mx-auto px-5 lg:px-8 py-8 space-y-10">
            <FlashAlert />

            <!-- Back link -->
            <Link :href="route('member.catalog.index')" class="hidden sm:inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition">
                <ChevronLeft class="h-4 w-4" /> Back to catalog
            </Link>

            <!-- Hero -->
            <section class="rounded-2xl border border-border bg-gradient-to-br from-amber-50/60 to-stone-100/40 p-6 md:p-10">
                <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                    <!-- Spine plate cover -->
                    <div class="lg:w-64 shrink-0 mx-auto lg:mx-0">
                        <div class="relative">
                            <span class="absolute inset-0 translate-x-2 translate-y-2 rounded-xl opacity-20 bg-amber-700" />
                            <div class="relative aspect-[2/3] overflow-hidden rounded-xl border border-border bg-card shadow-xl">
                                <template v-if="book.cover_image">
                                    <img :src="book.cover_image" :alt="book.title" class="w-full h-full object-cover" />
                                </template>
                                <template v-else>
                                    <span class="absolute left-0 top-0 h-full w-3 bg-amber-700" />
                                    <div class="h-full flex flex-col justify-between pl-7 pr-5 py-7">
                                        <div>
                                            <p class="text-[10px] uppercase tracking-[0.24em] text-muted-foreground">{{ book.category?.name || 'Book' }}</p>
                                            <h2 class="mt-3 font-serif text-2xl leading-tight">{{ book.title }}</h2>
                                            <p class="mt-2 text-xs text-muted-foreground">{{ book.author_name }}</p>
                                        </div>
                                        <BookOpen class="h-7 w-7 text-amber-600" />
                                    </div>
                                </template>
                            </div>
                            <span :class="available_copies_count > 0 ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-muted text-muted-foreground border-border'"
                                class="absolute -top-3 -right-3 rounded-full border px-3 py-1 text-[11px] shadow-md">
                                {{ available_copies_count > 0 ? 'Available' : 'All out' }}
                            </span>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 space-y-5">
                        <div class="flex flex-wrap gap-2">
                            <Badge class="bg-amber-700/20 border border-amber-700/40 text-amber-900 text-[11px]">{{ book.category?.name || 'Uncategorized' }}</Badge>
                            <Badge v-for="genre in book.genres" :key="genre.id" variant="outline" class="bg-card/70 text-[11px]">{{ genre.name }}</Badge>
                        </div>

                        <h1 class="font-serif text-4xl lg:text-5xl leading-[1.05] text-foreground">{{ book.title }}</h1>

                        <div class="flex items-center gap-3">
                            <span class="h-10 w-10 rounded-full bg-[#0d1a14] grid place-items-center">
                                <User class="h-5 w-5 text-white" />
                            </span>
                            <span>
                                <span class="block text-[10px] uppercase tracking-[0.2em] text-muted-foreground">Author</span>
                                <span class="block text-base font-medium">{{ book.author_name }}</span>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5 text-sm">
                            <span v-if="book.published_year" class="flex items-center gap-2 rounded-full bg-card px-3 py-1.5 shadow-sm">
                                <Calendar class="h-4 w-4 text-amber-600" />
                                <span class="text-xs">{{ book.published_year }}</span>
                            </span>
                            <span v-if="book.pages" class="flex items-center gap-2 rounded-full bg-card px-3 py-1.5 shadow-sm">
                                <BookMarked class="h-4 w-4 text-amber-600" />
                                <span class="text-xs">{{ book.pages }} pages</span>
                            </span>
                            <span v-if="book.language" class="flex items-center gap-2 rounded-full bg-card px-3 py-1.5 shadow-sm">
                                <Globe class="h-4 w-4 text-amber-600" />
                                <span class="text-xs uppercase">{{ book.language }}</span>
                            </span>
                            <span v-if="book.reviews?.length" class="flex items-center gap-1.5 rounded-full bg-card px-3 py-1.5 shadow-sm">
                                <Star class="h-4 w-4 fill-amber-500 text-amber-500" />
                                <span class="text-xs font-semibold">{{ avgRating.toFixed(1) }}</span>
                                <span class="text-xs text-muted-foreground">({{ book.reviews.length }})</span>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-border">
                            <p class="flex items-baseline gap-2">
                                <span class="font-serif text-4xl" :class="available_copies_count > 0 ? 'text-emerald-600' : 'text-muted-foreground'">{{ available_copies_count }}</span>
                                <span class="text-sm text-muted-foreground">/ {{ book.copies?.length || 0 }} copies available</span>
                            </p>
                            <div class="flex-1" />
                            <Button v-if="is_member && available_copies_count > 0" size="lg" @click="openBorrowDialog" :disabled="borrowForm.processing"
                                class="bg-[#0d1a14] hover:bg-[#1a2a1f] rounded-xl px-8 shadow-md transition">
                                <ShoppingBag class="h-4 w-4 mr-2" />
                                {{ borrowForm.processing ? 'Processing...' : 'Borrow Now' }}
                            </Button>
                            <Button v-else-if="is_member && available_copies_count === 0 && !has_active_reservation" size="lg" @click="reserveBook"
                                :disabled="reserveForm.processing" class="bg-amber-600 hover:bg-amber-700 rounded-xl px-8 shadow-md">
                                <Bell class="h-4 w-4 mr-2" />
                                {{ reserveForm.processing ? 'Processing...' : 'Join Waitlist' }}
                            </Button>
                            <div v-else-if="is_member && has_active_reservation" class="flex items-center gap-2 px-4 py-2 bg-amber-50 rounded-xl border border-amber-200">
                                <Bell class="h-4 w-4 text-amber-600" />
                                <span class="font-medium text-amber-700 text-sm">You're on the waitlist</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Content Grid -->
            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Left column -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Synopsis -->
                    <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h3 class="flex items-center gap-2 font-serif text-xl">
                            <Quote class="h-4 w-4 text-amber-600" /> Synopsis
                        </h3>
                        <p class="mt-4 text-sm leading-7 text-muted-foreground first-letter:font-serif first-letter:text-4xl first-letter:leading-none first-letter:mr-1 first-letter:float-left first-letter:text-amber-800">
                            {{ book.description || 'No description available for this book.' }}
                        </p>
                    </section>

                    <!-- Copies -->
                    <section v-if="book.copies?.length" class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-serif text-xl">
                                <MapPin class="h-4 w-4 text-amber-600" /> Find a copy
                            </h3>
                            <span class="text-xs text-muted-foreground">{{ book.copies.length }} locations</span>
                        </div>
                        <ul class="divide-y divide-border">
                            <li v-for="copy in sortedCopies" :key="copy.id" class="flex items-center gap-4 px-6 py-3.5 hover:bg-muted/30 transition">
                                <span class="h-10 w-10 shrink-0 rounded-lg bg-muted grid place-items-center">
                                    <Building2 class="h-4 w-4 text-amber-700" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm truncate font-medium">{{ copy.library?.name || 'Central Library' }}</span>
                                    <span class="block text-[11px] text-muted-foreground">
                                        {{ copy.location }}<template v-if="copy.distance !== undefined"> · {{ formatDistance(copy.distance) }} away</template>
                                    </span>
                                </span>
                                <code class="hidden sm:block font-mono text-[11px] text-muted-foreground">{{ copy.call_number }}</code>
                                <span :class="copy.status === 'available' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-muted text-muted-foreground border-border'"
                                    class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] capitalize">
                                    {{ copy.status }}
                                </span>
                            </li>
                        </ul>
                    </section>

                    <!-- Reviews -->
                    <section class="rounded-2xl border border-border bg-card p-6 shadow-sm space-y-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="flex items-center gap-2 font-serif text-xl">
                                <Star class="h-4 w-4 text-amber-600" /> Reader notes
                            </h3>
                            <span v-if="book.reviews?.length" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Star class="h-3.5 w-3.5 fill-amber-500 text-amber-500" />
                                {{ avgRating.toFixed(1) }} ({{ book.reviews.length }})
                            </span>
                        </div>

                        <!-- Write Review -->
                        <div v-if="is_member && has_borrowed" class="rounded-xl border border-border bg-muted/40 p-5 space-y-3">
                            <p class="text-sm">{{ user_review ? 'Edit your note' : 'Leave a note in the margin' }}</p>
                            <div class="flex items-center gap-1">
                                <button v-for="i in 5" :key="i" @click="reviewForm.rating = i" class="p-0.5 transition hover:scale-110">
                                    <Star class="h-6 w-6" :class="i <= reviewForm.rating ? 'fill-amber-500 text-amber-500' : 'text-border'" />
                                </button>
                            </div>
                            <Textarea v-model="reviewForm.body" placeholder="What did you think of this volume?" rows="3"
                                class="resize-none rounded-lg border-border bg-card text-sm" />
                            <div class="flex items-center gap-3">
                                <Button :disabled="reviewForm.processing || reviewForm.rating === 0"
                                    class="bg-[#0d1a14] hover:bg-[#1a2a1f] rounded-lg text-xs px-4 py-2" @click="submitReview">
                                    {{ user_review ? 'Update note' : 'Post note' }}
                                </Button>
                                <Button v-if="user_review" variant="ghost" class="text-destructive hover:bg-destructive/10 rounded-lg text-xs" @click="deleteReview">
                                    <Trash2 class="h-3.5 w-3.5 mr-1.5" /> Delete
                                </Button>
                            </div>
                            <p v-if="reviewForm.errors.rating" class="text-xs text-destructive">{{ reviewForm.errors.rating }}</p>
                        </div>

                        <!-- Reviews List -->
                        <ul v-if="book.reviews?.length" class="space-y-5">
                            <li v-for="review in book.reviews" :key="review.id" class="flex gap-4">
                                <span class="h-9 w-9 shrink-0 rounded-full bg-[#0d1a14] grid place-items-center text-xs font-semibold text-white">
                                    {{ review.user?.name?.charAt(0).toUpperCase() }}
                                </span>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-3">
                                        <span class="text-sm font-medium">{{ review.user?.name }}</span>
                                        <span class="flex items-center gap-0.5">
                                            <Star v-for="i in 5" :key="i" class="h-3 w-3" :class="i <= review.rating ? 'fill-amber-500 text-amber-500' : 'text-border'" />
                                        </span>
                                    </span>
                                    <span v-if="review.body" class="mt-1.5 block text-sm leading-6 text-muted-foreground">{{ review.body }}</span>
                                </span>
                            </li>
                        </ul>
                        <p v-else class="text-center text-muted-foreground py-4 text-sm">No notes yet. Be the first to share your thoughts!</p>
                    </section>
                </div>

                <!-- Sidebar -->
                <aside class="space-y-6">
                    <!-- Catalogue record -->
                    <section class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h3 class="font-serif text-lg">Catalogue record</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div v-for="[k, v] in [['ISBN', book.isbn], ['Publisher', book.publisher?.name], ['Published', book.published_year], ['Pages', book.pages], ['Language', book.language]]"
                                :key="k" class="flex items-baseline justify-between gap-4 border-b border-dashed border-border pb-2 last:border-0">
                                <dt class="text-[11px] uppercase tracking-[0.16em] text-muted-foreground">{{ k }}</dt>
                                <dd class="text-right font-mono text-xs">{{ v || 'N/A' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <!-- Shelved nearby -->
                    <section v-if="recommended_books.length" class="rounded-2xl border border-border bg-card p-6 shadow-sm">
                        <h3 class="flex items-center gap-2 font-serif text-lg">
                            <Library class="h-4 w-4 text-amber-600" /> Shelved nearby
                        </h3>
                        <ul class="mt-4 space-y-3">
                            <li v-for="rec in recommended_books.slice(0, 4)" :key="rec.id">
                                <Link :href="route('member.catalog.show', rec.id)"
                                    class="group flex items-center gap-3 rounded-lg border border-transparent p-2 transition hover:border-border hover:bg-muted/40">
                                    <span class="h-14 w-10 shrink-0 rounded-sm border border-border overflow-hidden bg-muted">
                                        <img v-if="rec.cover_image" :src="rec.cover_image" :alt="rec.title" class="w-full h-full object-cover" />
                                        <span v-else class="w-full h-full flex items-center justify-center">
                                            <BookOpen class="h-4 w-4 text-muted-foreground" />
                                        </span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-serif text-sm group-hover:text-amber-700 transition">{{ rec.title }}</span>
                                        <span class="block truncate text-[11px] text-muted-foreground">{{ rec.author_name }}</span>
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Desk note -->
                    <section class="rounded-xl border border-amber-700/30 bg-gradient-to-br from-amber-50/60 to-stone-100/40 p-6">
                        <p class="font-serif italic leading-relaxed text-sm text-foreground/80">
                            "A good book is the precious life-blood of a master spirit."
                        </p>
                        <p class="mt-3 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Desk note</p>
                    </section>
                </aside>
            </div>

            <!-- More like this -->
            <section v-if="recommended_books.length > 4">
                <h3 class="font-serif text-2xl">More like this</h3>
                <div class="mt-5 grid gap-5 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                    <Link v-for="rec in recommended_books.slice(4)" :key="rec.id" :href="route('member.catalog.show', rec.id)"
                        class="relative rounded-2xl border border-border bg-card overflow-hidden p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="mb-3 aspect-[2/3] rounded-lg overflow-hidden bg-muted">
                            <img v-if="rec.cover_image" :src="rec.cover_image" :alt="rec.title" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">
                                <BookOpen class="h-8 w-8" />
                            </div>
                        </div>
                        <p class="font-serif text-sm leading-tight line-clamp-2 group-hover:text-amber-700 transition">{{ rec.title }}</p>
                        <p class="mt-1 text-xs text-muted-foreground truncate">{{ rec.author_name }}</p>
                    </Link>
                </div>
            </section>
        </div>

        <!-- Library picker overlay -->
        <Teleport to="body">
            <div v-if="showLibraryDialog" class="fixed inset-0 z-30 grid place-items-center bg-black/50 p-5" role="dialog" aria-modal="true">
                <div class="w-full max-w-md rounded-2xl border border-border bg-card p-6 shadow-xl">
                    <h4 class="flex items-center gap-2 font-serif text-xl">
                        <Building2 class="h-4 w-4 text-amber-600" /> Choose a branch
                    </h4>
                    <p class="mt-1 text-xs text-muted-foreground">Collect at the front desk within 24 hours.</p>
                    <div class="mt-4 space-y-2 max-h-[300px] overflow-y-auto">
                        <button v-for="item in availableCopiesByLibrary" :key="item.library?.id ?? 0"
                            @click="selectLibrary(item.library?.id)"
                            class="flex w-full items-center gap-3 rounded-xl border border-border p-4 text-left transition hover:border-amber-600/60 hover:bg-amber-50/40">
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">{{ item.library?.name ?? 'Central Library' }}</span>
                                <span class="block text-[11px] text-muted-foreground">{{ item.copies.length }} available</span>
                            </span>
                            <span v-if="item.distance !== undefined" class="text-[11px] text-amber-700 font-medium">{{ formatDistance(item.distance) }}</span>
                        </button>
                    </div>
                    <button @click="showLibraryDialog = false"
                        class="mt-4 w-full rounded-lg border border-border py-2 text-xs text-muted-foreground transition hover:text-foreground">
                        Cancel
                    </button>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
