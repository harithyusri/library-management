<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Books',
        href: '/books',
    },
];

/* =========================
   Types
========================= */
interface Book {
    id: number;
    title: string;
    author: string;
    status: string;
    format: string;
    quantity: number;
    available_quantity: number;
    rating?: number;
    genre?: string;
    cover_image_url?: string;
}

interface PaginatedBooks {
    data: Book[];
    links: any[];
}

/* =========================
   Props
========================= */
const props = defineProps<{
    books: PaginatedBooks;
    filters: Record<string, any>;
    genres: string[];
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? 'all',
    genre: props.filters?.genre ?? 'all',
    format: props.filters?.format ?? 'all',
    available_only: props.filters?.available_only ?? false,
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

const formatStatus = (status: string): string => {
    return status.charAt(0).toUpperCase() + status.slice(1);
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

const getStatusClass = (status: string): string => {
    const classes: Record<string, string> = {
        available: 'bg-green-100 text-green-800',
        borrowed: 'bg-blue-100 text-blue-800',
        reserved: 'bg-yellow-100 text-yellow-800',
        maintenance: 'bg-red-100 text-red-800',
    };

    return classes[status] ?? 'bg-gray-100 text-gray-800';
};

interface FlashMessages {
    success?: string;
    error?: string;
}

const page = usePage<AppPageProps & {
    flash?: FlashMessages;
}>();
</script>

<template>

    <Head title="Books" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold text-foreground">
                    Books Library
                </h1>

                <div class="flex gap-2">
                    <Link :href="route('books.my-books')"
                        class="rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground hover:bg-secondary/80">
                        My Books
                    </Link>

                    <Link :href="route('books.create')"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                        Add Book
                    </Link>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="page.props.flash?.success"
                class="rounded-md border border-border bg-muted px-4 py-3 text-sm text-foreground">
                {{ page.props.flash.success }}
            </div>

            <div v-if="page.props.flash?.error"
                class="rounded-md border border-destructive/50 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                {{ page.props.flash.error }}
            </div>


            <!-- Filters -->
            <div class="rounded-xl border border-border bg-background p-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-foreground">
                            Search
                        </label>
                        <input vgani="search" v-model="searchForm.search" @input="debounceSearch" type="text"
                            placeholder="Search by title or author" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                   focus:border-ring focus:ring-ring" />
                    </div>

                    <!-- Genre -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-foreground">
                            Genre
                        </label>
                        <select v-model="searchForm.genre" @change="search"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="all">All Genres</option>
                            <option v-for="genre in genres" :key="genre" :value="genre">
                                {{ genre }}
                            </option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-foreground">
                            Status
                        </label>
                        <select v-model="searchForm.status" @change="search"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="all">All Status</option>
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="reserved">Reserved</option>
                            <option value="maintenance">Maintenance</option>
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

                        <span :class="getStatusClass(book.status)"
                            class="absolute right-2 top-2 rounded-full px-2 py-1 text-xs font-medium">
                            {{ formatStatus(book.status) }}
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="flex flex-col gap-3 p-4">
                        <div>
                            <h3 class="line-clamp-2 font-semibold text-foreground">
                                {{ book.title }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                by {{ book.author }}
                            </p>
                        </div>

                        <div class="flex gap-2 text-xs">
                            <span class="rounded bg-muted px-2 py-1 text-muted-foreground">
                                {{ book.genre ?? 'General' }}
                            </span>
                            <span class="rounded bg-primary/10 px-2 py-1 text-primary">
                                {{ formatBookFormat(book.format) }}
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <Link :href="route('books.show', book.id)"
                                class="flex-1 rounded-md bg-muted px-3 py-2 text-center text-sm text-foreground hover:bg-muted/80">
                                View
                            </Link>
                            <Link :href="route('books.edit', book.id)"
                                class="flex-1 rounded-md bg-secondary px-3 py-2 text-center text-sm text-secondary-foreground hover:bg-secondary/80">
                                Edit
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-xl border border-border bg-background p-12 text-center">
                <h3 class="text-sm font-medium text-foreground">
                    No books found
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Get started by adding a new book.
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="books.data.length" class="rounded-xl border border-border bg-background p-4">
                <Pagination :links="books.links" />
            </div>

        </div>
    </AppLayout>
</template>
