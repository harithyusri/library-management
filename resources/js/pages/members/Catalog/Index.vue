<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Search, BookOpen, LayoutGrid, Rows3, Filter, ChevronDown, Heart, Star, ShoppingBag, User, X, CheckCircle2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const props = defineProps<{
    books: { data: any[]; links: any[]; total: number };
    filters: { search?: string; genre?: string; category?: string; available_only?: boolean; sort?: string };
    genres: any[];
    categories: any[];
    is_member: boolean;
}>();

const breadcrumbs = [{ title: 'Catalog', href: route('member.catalog.index') }];

const search = ref(props.filters.search || '');
const selectedGenre = ref(props.filters.genre || 'all');
const selectedCategory = ref(props.filters.category || 'all');
const availableOnly = ref(props.filters.available_only ?? false);
const sortBy = ref(props.filters.sort || 'relevance');
const viewMode = ref<'grid' | 'list'>('grid');
const saved = ref<number[]>([]);

const updateFilters = debounce(() => {
    const params: any = {};
    if (search.value) params.search = search.value;
    if (selectedGenre.value !== 'all') params.genre = selectedGenre.value;
    if (selectedCategory.value !== 'all') params.category = selectedCategory.value;
    if (availableOnly.value) params.available_only = 1;
    if (sortBy.value !== 'relevance') params.sort = sortBy.value;
    router.get(route('member.catalog.index'), params, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch([search, selectedGenre, selectedCategory, availableOnly, sortBy], updateFilters);

const hasActiveFilters = () =>
    !!search.value || selectedGenre.value !== 'all' || selectedCategory.value !== 'all' || availableOnly.value || sortBy.value !== 'relevance';

const clearFilters = () => {
    search.value = '';
    selectedGenre.value = 'all';
    selectedCategory.value = 'all';
    availableOnly.value = false;
    sortBy.value = 'relevance';
};

const toggleSave = (id: number) =>
    saved.value.includes(id) ? (saved.value = saved.value.filter(i => i !== id)) : saved.value.push(id);

// Borrow
const borrowForm = useForm({});
const confirmDialogOpen = ref(false);
const pendingBook = ref<any | null>(null);

const requestBorrow = (book: any) => { pendingBook.value = book; confirmDialogOpen.value = true; };
const confirmBorrow = () => {
    if (!pendingBook.value) return;
    borrowForm.post(route('member.catalog.borrow', pendingBook.value.id), {
        preserveScroll: true,
        onFinish: () => { confirmDialogOpen.value = false; pendingBook.value = null; },
    });
};

// Spine colours for books without a cover
const spinePalette = [
    { bg: 'var(--ink)', text: 'var(--dust)' },
    { bg: 'var(--leather)', text: 'var(--dust)' },
    { bg: '#1f2e28', text: 'var(--sage)' },
    { bg: '#2a2436', text: 'var(--dust)' },
    { bg: 'var(--brass)', text: 'var(--ink)' },
];
const spineFor = (book: any) => {
    const seed = String(book.id ?? book.title ?? '').split('').reduce((a: number, c: string) => a + c.charCodeAt(0), 0);
    return spinePalette[seed % spinePalette.length];
};

const visibleGenres = (book: any) => (book.genres ?? []).slice(0, 2);

const statusStyle = (copies: number) =>
    copies > 0
        ? 'bg-primary/10 text-primary border-primary/25'
        : 'bg-muted text-muted-foreground border-border';
</script>

<template>
    <Head title="Book Catalog" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-0">
            <FlashAlert class="mb-4" />

            <!-- Editorial hero -->
            <section class="border-b border-border bg-[image:var(--gradient-warm)] -mx-4 px-4 sm:-mx-6 sm:px-6 py-6">
                <p class="text-[11px] uppercase tracking-[0.22em] text-muted-foreground">Member catalog</p>
                <h1 class="mt-3 font-serif text-3xl lg:text-4xl leading-[1.05]">
                    Wander the shelves,
                    <span class="italic" style="color: var(--leather)"> borrow what you love.</span>
                </h1>
                <p class="mt-3 max-w-lg text-sm text-muted-foreground leading-relaxed">
                    {{ books.total.toLocaleString() }} volumes across our collections. Search by title, author or genre — reserve online and collect at the front desk.
                </p>

                <!-- Search bar -->
                <label class="mt-6 flex items-center gap-3 rounded-full border border-border bg-card px-5 py-3.5 max-w-2xl">
                    <Search class="h-4 w-4 text-muted-foreground shrink-0" />
                    <input
                        v-model="search"
                        placeholder="Search titles, authors, genres…"
                        class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                    <span v-if="search" class="shrink-0">
                        <button @click="search = ''" class="text-muted-foreground hover:text-foreground transition">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </span>
                    <span v-else class="hidden sm:inline text-[11px] text-muted-foreground border-l border-border pl-3 shrink-0">
                        Enter to search
                    </span>
                </label>
            </section>

            <!-- Filter rail -->
            <div class="sticky top-0 z-20 border-b border-border bg-background/90 backdrop-blur -mx-4 px-4 sm:-mx-6 sm:px-6 py-3">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <Filter class="h-3.5 w-3.5 text-muted-foreground shrink-0" />

                    <!-- Genre pills -->
                    <button
                        @click="selectedGenre = 'all'"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-xs transition border"
                        :class="selectedGenre === 'all'
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'bg-card text-muted-foreground border-border hover:text-foreground'"
                    >
                        All genres
                    </button>
                    <button
                        v-for="genre in genres"
                        :key="genre.id"
                        @click="selectedGenre = String(genre.id)"
                        class="shrink-0 rounded-full px-3.5 py-1.5 text-xs transition border"
                        :class="selectedGenre === String(genre.id)
                            ? 'bg-primary text-primary-foreground border-primary'
                            : 'bg-card text-muted-foreground border-border hover:text-foreground'"
                    >
                        {{ genre.name }}
                    </button>

                    <!-- View toggle -->
                    <div class="ml-auto hidden sm:flex items-center gap-1 shrink-0">
                        <button
                            @click="viewMode = 'grid'"
                            class="h-8 w-8 grid place-items-center rounded-md border transition"
                            :class="viewMode === 'grid' ? 'border-primary text-primary' : 'border-border text-muted-foreground'"
                            aria-label="Grid view"
                        >
                            <LayoutGrid class="h-4 w-4" />
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            class="h-8 w-8 grid place-items-center rounded-md border transition"
                            :class="viewMode === 'list' ? 'border-primary text-primary' : 'border-border text-muted-foreground'"
                            aria-label="List view"
                        >
                            <Rows3 class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Secondary row -->
                <div class="flex items-center gap-3 flex-wrap mt-2">
                    <button
                        @click="availableOnly = !availableOnly"
                        class="flex items-center gap-1.5 h-7 px-3 rounded-full text-xs font-bold border transition-all"
                        :class="availableOnly
                            ? 'bg-emerald-500 border-emerald-500 text-white'
                            : 'bg-card border-border text-muted-foreground hover:text-foreground'"
                    >
                        <CheckCircle2 class="h-3.5 w-3.5" /> Available now
                    </button>

                    <button
                        v-if="hasActiveFilters()"
                        @click="clearFilters"
                        class="flex items-center gap-1 h-7 px-3 rounded-full text-xs text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <X class="h-3 w-3" /> Clear filters
                    </button>

                    <p class="ml-auto text-xs text-muted-foreground font-medium whitespace-nowrap">
                        <span class="font-bold text-foreground">{{ books.total }}</span> titles found
                    </p>
                </div>
            </div>

            <!-- Shelf heading -->
            <div class="flex items-end justify-between gap-4 pt-8 pb-4">
                <h2 class="font-serif text-2xl">
                    {{ selectedGenre === 'all' ? 'All shelves' : genres.find(g => String(g.id) === selectedGenre)?.name }}
                </h2>
                <span class="text-xs text-muted-foreground flex items-center gap-1 cursor-pointer">
                    Sorted by relevance <ChevronDown class="h-3.5 w-3.5" />
                </span>
            </div>

            <!-- Grid view -->
            <div v-if="books.data.length > 0 && viewMode === 'grid'"
                class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-5">
                <div v-for="book in books.data" :key="book.id" class="group flex flex-col">
                    <div class="relative aspect-[2/3] rounded-xl overflow-hidden border border-border transition-all duration-300 group-hover:-translate-y-0.5 group-hover:shadow-[var(--shadow-book)]">
                        <Link :href="route('member.catalog.show', book.id)" class="block w-full h-full">
                            <img v-if="book.cover_image" :src="book.cover_image" :alt="book.title" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-center p-4" :style="{ background: spineFor(book).bg }">
                                <BookOpen class="h-6 w-6 mb-3 opacity-40" :style="{ color: spineFor(book).text }" />
                                <p class="font-serif text-sm leading-snug line-clamp-4" :style="{ color: spineFor(book).text }">{{ book.title }}</p>
                            </div>
                        </Link>

                        <!-- Availability ribbon -->
                        <div
                            class="absolute top-0 right-3 px-2 pt-1 pb-1.5 text-[9px] font-black uppercase tracking-wider text-white shadow-sm"
                            :class="book.available_copies > 0 ? 'bg-emerald-500' : 'bg-slate-500'"
                            style="clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 82%, 0 100%);"
                        >
                            {{ book.available_copies > 0 ? 'Available' : 'Not Available' }}
                        </div>

                        <!-- Hover overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-3 gap-2">
                            <p class="text-white text-[10px] font-bold flex items-center gap-1 opacity-90">
                                <User class="h-3 w-3" /> {{ book.author_name }}
                            </p>
                            <div class="flex gap-1.5">
                                <Link :href="route('member.catalog.show', book.id)" class="flex-1">
                                    <Button size="sm" variant="outline" class="w-full bg-white/10 border-white/30 text-white hover:bg-white/20 rounded-lg h-8 text-xs text-white font-bold">
                                        Details
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2.5 px-0.5 space-y-0.5">
                        <h3 class="text-sm font-serif leading-snug line-clamp-2">
                            <Link :href="route('member.catalog.show', book.id)" class="hover:text-[color:var(--leather)] transition-colors">{{ book.title }}</Link>
                        </h3>
                        <p class="text-xs text-muted-foreground truncate">{{ book.author_name }}</p>
                        <div class="flex flex-wrap gap-1 pt-1">
                            <span v-for="genre in visibleGenres(book)" :key="genre.id" class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-secondary text-muted-foreground uppercase tracking-tighter">
                                {{ genre.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List view -->
            <div v-else-if="books.data.length > 0 && viewMode === 'list'" class="flex flex-col divide-y divide-border border border-border rounded-xl overflow-hidden bg-card">
                <article v-for="book in books.data" :key="book.id" class="relative flex items-center gap-4 p-4 hover:bg-secondary/40 transition-colors">
                    <!-- Spine accent -->
                    <span class="absolute left-0 top-0 h-full w-1" :style="{ background: spineFor(book).bg }" />

                    <Link :href="route('member.catalog.show', book.id)" class="shrink-0 w-10 aspect-[2/3] rounded overflow-hidden bg-secondary border border-border ml-2">
                        <img v-if="book.cover_image" :src="book.cover_image" :alt="book.title" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center" :style="{ background: spineFor(book).bg }">
                            <BookOpen class="h-3.5 w-3.5 opacity-50" :style="{ color: spineFor(book).text }" />
                        </div>
                    </Link>

                    <div class="flex-1 min-w-0">
                        <h3 class="font-serif text-base leading-tight truncate">
                            <Link :href="route('member.catalog.show', book.id)" class="hover:text-[color:var(--leather)] transition-colors">{{ book.title }}</Link>
                        </h3>
                        <p class="text-xs text-muted-foreground truncate mt-0.5">{{ book.author_name }} · {{ book.category?.name || 'Uncategorized' }}</p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="rounded-full border px-2.5 py-0.5 text-[11px]" :class="statusStyle(book.available_copies)">
                                {{ book.available_copies > 0 ? `${book.available_copies} available` : 'On loan' }}
                            </span>
                            <span class="flex items-center gap-0.5">
                                <Star v-for="i in 5" :key="i" class="h-3 w-3" :class="i <= 4 ? 'fill-[color:var(--brass)] text-[color:var(--brass)]' : 'text-border'" />
                            </span>
                        </div>
                    </div>

                    <button
                        @click="toggleSave(book.id)"
                        class="shrink-0 text-muted-foreground hover:text-[color:var(--leather)] transition"
                        aria-label="Save"
                    >
                        <Heart class="h-4 w-4" :class="saved.includes(book.id) ? 'fill-[color:var(--leather)] text-[color:var(--leather)]' : ''" />
                    </button>

                    <div class="flex items-center gap-2 shrink-0">
                        <Link :href="route('member.catalog.show', book.id)">
                            <Button size="sm" variant="outline" class="rounded-lg h-8 text-xs text-white font-bold">Details</Button>
                        </Link>
                    </div>
                </article>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center space-y-4 rounded-xl border border-dashed border-border bg-card">
                <div class="bg-secondary p-4 rounded-full w-20 h-20 mx-auto flex items-center justify-center border border-border">
                    <Search class="h-10 w-10 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-serif text-xl">No books found</h3>
                    <p class="text-sm text-muted-foreground">Try adjusting your filters or search terms.</p>
                </div>
                <Button variant="outline" @click="clearFilters" class="rounded-full px-6">Clear all filters</Button>
            </div>

            <!-- Quote footer -->
            <div class="mt-12 mb-4 rounded-xl border border-border bg-card p-6 text-center">
                <p class="font-serif italic text-lg">"Take a book, leave a little quieter than you came."</p>
                <p class="mt-2 text-[11px] uppercase tracking-[0.2em] text-muted-foreground">Library members' reading code</p>
            </div>

            <!-- Pagination -->
            <div v-if="books.data.length > 0" class="flex flex-col md:flex-row items-center justify-between gap-4 pt-8 border-t border-border">
                <p class="text-sm text-muted-foreground">
                    Showing <span class="font-bold text-foreground">{{ books.data.length }}</span> of {{ books.total }} results
                </p>
                <div class="flex items-center gap-1.5">
                    <Link
                        v-for="link in books.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="h-9 min-w-9 flex items-center justify-center rounded-full px-3.5 text-sm font-bold transition-all border"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card border-border text-muted-foreground hover:border-[color:var(--brass)] hover:text-[color:var(--leather)]',
                            !link.url && 'opacity-40 cursor-not-allowed pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Borrow confirmation -->
        <AlertDialog v-model:open="confirmDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Borrow this book?</AlertDialogTitle>
                    <AlertDialogDescription>
                        You're about to borrow <span class="font-bold text-foreground">"{{ pendingBook?.title }}"</span>.
                        Please return it by the due date to avoid late fees.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-xl">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        @click="confirmBorrow"
                        :disabled="borrowForm.processing"
                        class="rounded-xl"
                        style="background: var(--ink); color: var(--dust);"
                    >
                        {{ borrowForm.processing ? 'Borrowing…' : 'Confirm Borrow' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-4 {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
