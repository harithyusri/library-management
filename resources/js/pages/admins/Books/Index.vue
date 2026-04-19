<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Plus, BookOpen, LayoutGrid, List, User, Tag, Calendar, Globe, Layers, Book as BookIcon, Eye } from 'lucide-vue-next';
import { Badge } from "@/components/ui/badge";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Books',
        href: route('admin.books.index'),
    },
];

/* =========================
   Types
========================= */
interface Book {
    id: number;
    title: string;
    author_name: string;
    isbn?: string;
    format: string;
    pages: number;
    language: string;
    published_year?: number;
    description?: string;
    cover_image?: string;
    genres: {
        id: number;
        name: string;
    }[];
    category: {
        id: number;
        name: string;
    };
    publisher?: {
        id: number;
        name: string;
    };
}

interface PaginatedBooks {
    data: Book[];
    links: any[];
}

interface Genre {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    books: PaginatedBooks;
    filters: Record<string, any>;
    genres: Genre[];
    categories: Category[];
    formatOptions: Record<string, string>;
    languageOptions: Record<string, string>;
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    genre: props.filters?.genre ?? 'all',
    category: props.filters?.category ?? 'all',
    format: props.filters?.format ?? 'all',
    language: props.filters?.language ?? 'all',
    sort_by: props.filters?.sort_by ?? 'created_at',
    sort_order: props.filters?.sort_order ?? 'desc',
});

const viewMode = ref(localStorage.getItem('admin_books_view_mode') || 'grid');

watch(viewMode, (newMode) => {
    localStorage.setItem('admin_books_view_mode', newMode);
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

/* =========================
   Methods
========================= */
const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

const search = () => {
    router.get(route('admin.books.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatBookFormat = (format: string): string => {
    const formats: Record<string, string> = {
        hardcover: 'Hardcover',
        paperback: 'Paperback',
        ebook: 'E-book',
        audiobook: 'Audiobook',
    };

    return formats[format] ?? format;
};

</script>

<template>

    <Head title="Books" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto px-6 pt-2 pb-8">

            <FlashAlert />
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Books Inventory <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Manage the complete library collection and stock levels.</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center rounded-lg border bg-white px-2 py-1 text-sm gap-1 shadow-sm mr-2">
                        <Button
                            variant="ghost" size="sm"
                            class="px-2 rounded-lg font-bold flex items-center gap-2 transition-all"
                            :class="viewMode === 'grid' ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'text-slate-400 hover:bg-slate-50'"
                            @click="viewMode = 'grid'"
                        >
                            <LayoutGrid class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost" size="sm"
                            class="px-2 rounded-lg font-bold flex items-center gap-2 transition-all"
                            :class="viewMode === 'list' ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' : 'text-slate-400 hover:bg-slate-50'"
                            @click="viewMode = 'list'"
                        >
                            <List class="h-4 w-4" />
                        </Button>
                    </div>

                    <Link :href="route('member.catalog.index')" class="contents">
                        <Button variant="outline" class="border-slate-200 hover:bg-slate-50 rounded-lg px-4 py-2 text-sm font-bold text-slate-600 flex items-center gap-2">
                            <BookOpen class="h-4 w-4" />
                            View Catalog
                        </Button>
                    </Link>
                    <Link v-if="$page.props.auth.can?.create_books" :href="route('admin.books.create')" class="contents">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-bold shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2">
                            <Plus class="h-4 w-4" />
                            Add New Book
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="grid gap-4 md:grid-cols-5">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-foreground">
                        Search
                    </label>
                    <input v-model="searchForm.search" @input="debounceSearch" type="text"
                        placeholder="Search by title, author, or ISBN"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:border-ring focus:ring-ring" />
                </div>

                <!-- Genre -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">
                        Genre
                    </label>
                    <Select v-model="searchForm.genre" @update:model-value="search">
                        <SelectTrigger>
                            <SelectValue placeholder="All Genres" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Genres</SelectItem>
                            <SelectItem v-for="genre in genres" :key="genre.id" :value="String(genre.id)">
                                {{ genre.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Category -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">
                        Category
                    </label>
                    <Select v-model="searchForm.category" @update:model-value="search">
                        <SelectTrigger>
                            <SelectValue placeholder="All Categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Categories</SelectItem>
                            <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Format -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-foreground">
                        Format
                    </label>
                    <Select v-model="searchForm.format" @update:model-value="search">
                        <SelectTrigger>
                            <SelectValue placeholder="All Formats" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Formats</SelectItem>
                            <SelectItem v-for="(label, key) in formatOptions" :key="key" :value="key">
                                {{ label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Books View -->
            <div v-if="books.data.length">
                <!-- Grid View (Smaller Cards) -->
                <div v-if="viewMode === 'grid'" class="grid gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    <div v-for="book in books.data" :key="book.id"
                        class="group overflow-hidden rounded-xl border border-slate-100 bg-white transition hover:shadow-xl hover:shadow-indigo-50/50">
                        <!-- Cover -->
                        <div class="relative h-48 bg-slate-50 overflow-hidden">
                            <img v-if="book.cover_image" :src="book.cover_image" :alt="book.title"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-300">
                                <BookIcon class="h-10 w-10" />
                            </div>

                            <div class="absolute right-1 top-1">
                                <Badge class="text-[8px] h-5 px-1.5 font-black uppercase bg-white/90 backdrop-blur-sm text-indigo-600 border-0 shadow-sm">
                                    {{ formatBookFormat(book.format) }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Details (Smaller Cards) -->
                        <div class="p-3 space-y-2">
                            <div>
                                <h3 class="line-clamp-1 text-[11px] font-black text-slate-900 group-hover:text-indigo-600 transition-colors uppercase tracking-tight">
                                    <Link :href="route('admin.books.show', book.id)">{{ book.title }}</Link>
                                </h3>
                                <p class="text-[9px] text-slate-500 font-bold truncate">
                                    by {{ book.author_name }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-1">
                                <Badge variant="secondary" class="text-[8px] h-4 px-1.5 border-0 bg-slate-100 text-slate-500 font-bold uppercase">
                                    {{ book.category.name }}
                                </Badge>
                            </div>

                            <div class="pt-2 flex items-center justify-between border-t border-slate-50">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ book.language }}</span>
                                <Link :href="route('admin.books.show', book.id)" class="text-[9px] font-black text-indigo-600 hover:text-indigo-800 uppercase tracking-widest">
                                    View Book
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- List View (Row Style) -->
                <div v-else class="space-y-3">
                    <div v-for="book in books.data" :key="book.id"
                        class="group flex items-center gap-4 p-3 rounded-xl border border-slate-100 bg-white hover:border-indigo-100 hover:shadow-lg hover:shadow-indigo-50/50 transition-all">
                        <!-- Tiny Thumbnail -->
                        <Link :href="route('admin.books.show', book.id)" class="shrink-0 h-16 w-12 rounded-lg overflow-hidden bg-slate-50 border border-slate-100 transition-transform group-hover:scale-105">
                            <img v-if="book.cover_image" :src="book.cover_image" :alt="book.title"
                                class="h-full w-full object-cover" />
                            <div v-else class="flex h-full w-full items-center justify-center text-slate-300">
                                <BookIcon class="h-5 w-5" />
                            </div>
                        </Link>

                        <!-- Main Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <Badge variant="outline" class="h-4 px-1.5 text-[8px] font-black border-0 bg-emerald-50 text-emerald-600 uppercase tracking-widest">
                                    {{ book.category.name }}
                                </Badge>
                                <div class="flex gap-1 overflow-hidden">
                                     <span v-for="genre in book.genres.slice(0, 2)" :key="genre.id" class="text-[8px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 uppercase whitespace-nowrap">
                                        {{ genre.name }}
                                    </span>
                                </div>
                            </div>
                            <h3 class="text-sm font-black text-slate-900 truncate">
                                <Link :href="route('admin.books.show', book.id)">{{ book.title }}</Link>
                            </h3>
                            <div class="flex items-center gap-3 text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                <span>{{ book.author_name }}</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ book.isbn }}</span>
                            </div>
                        </div>

                        <!-- Stats & Details -->
                        <div class="hidden md:flex items-center gap-8 px-6 border-x border-slate-50 h-10">
                            <div class="flex flex-col">
                                <span class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Format</span>
                                <span class="text-[10px] font-bold text-slate-700">{{ formatBookFormat(book.format) }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Published</span>
                                <span class="text-[10px] font-bold text-slate-700">{{ book.published_year ?? 'N/A' }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Language</span>
                                <span class="text-[10px] font-bold text-slate-700 uppercase">{{ book.language }}</span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="shrink-0 flex items-center pl-2">
                             <Link :href="route('admin.books.show', book.id)">
                                <Button variant="ghost" size="sm" class="h-9 w-9 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50">
                                    <Eye class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border border-border bg-background p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-muted-foreground" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-4 text-sm font-medium text-foreground">
                    No books found
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Get started by adding a new book to your library.
                </p>
                <Link v-if="$page.props.auth.can?.create_books" :href="route('admin.books.create')"
                    class="mt-4 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Add Book
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="books.data.length" class="bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ books.data.length }} books
                    </div>
                    <div class="flex gap-2">
                        <Link v-for="(link, index) in books.links" :key="index" :href="link.url || ''" :class="[
                            'rounded-md px-3 py-2 text-sm',
                            link.active ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80',
                            !link.url && 'pointer-events-none opacity-50'
                        ]" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
