<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Books',
        href: route('books.index'),
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
    publication_year?: number;
    description?: string;
    cover_image_url?: string;
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
    router.get(route('books.index'), searchForm, {
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
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold text-foreground">
                    Library Books List
                </h1>

                <Link :href="route('books.create')"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Add Book
                </Link>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-border bg-background p-6">
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
                        <select v-model="searchForm.category" @change="search"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="all">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Format -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-foreground">
                            Format
                        </label>
                        <select v-model="searchForm.format" @change="search"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="all">All Formats</option>
                            <option v-for="(label, key) in formatOptions" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Books Grid -->
            <div v-if="books.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div v-for="book in books.data" :key="book.id"
                    class="overflow-hidden rounded-xl border border-border bg-background transition hover:shadow-sm">
                    <!-- Cover -->
                    <div class="relative h-60 bg-muted">
                        <img v-if="book.cover_image_url" :src="book.cover_image_url" :alt="book.title"
                            class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-muted-foreground" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>

                        <span
                            class="absolute right-2 top-2 rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                            {{ formatBookFormat(book.format) }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="flex flex-col gap-3 p-4">
                        <div>
                            <h3 class="line-clamp-2 font-semibold text-foreground">
                                <Link :href="route('books.show', book.id)">
                                    {{ book.title }}
                                </Link>

                            </h3>
                            <p class="text-sm text-muted-foreground">
                                by {{ book.author_name }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs">
                            <div class="flex flex-wrap gap-2 w-full">
                                <span v-for="genre in book.genres" :key="genre.id"
                                    class="rounded bg-muted px-2 py-1 text-muted-foreground">
                                    {{ genre.name }}
                                </span>
                            </div>

                            <span class="rounded bg-secondary/20 px-2 py-1 text-secondary-foreground">
                                {{ book.category.name }}
                            </span>
                            <span v-if="book.publication_year"
                                class="rounded bg-accent/20 px-2 py-1 text-accent-foreground">
                                {{ book.publication_year }}
                            </span>
                        </div>

                        <div class="flex gap-2 text-xs text-muted-foreground">
                            <span>{{ book.pages }} pages</span>
                            <span>•</span>
                            <span>{{ book.language }}</span>
                        </div>

                        <div class="flex gap-2">
                            <Link :href="route('books.show', book.id)"
                                class="flex-1 rounded-md bg-muted px-3 py-2 text-center text-sm text-foreground hover:bg-muted/80">
                                View
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
                <Link :href="route('books.create')"
                    class="mt-4 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Add Book
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="books.data.length" class="rounded-xl border border-border bg-background p-4">
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
