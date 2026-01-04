<script setup lang="ts">
import { route } from "ziggy-js";
import { ref } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { QrCode , PlusIcon, Trash2Icon, DownloadIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Books',
        href: route('books.index'),
    },
    {
        title: 'Book Details',
        href: '#',
    },
];

/* =========================
   Types
========================= */
interface Genre {
    id: number;
    name: string;
}

interface BookCopy {
    id: number;
    copy_number: string;
    status: 'available' | 'borrowed' | 'maintenance' | 'lost';
    condition: string;
    location: string;
    qr_code_url?: string;
    borrowed_by?: {
        id: number;
        name: string;
        email: string;
    };
    borrowed_at?: string;
    due_at?: string;
    created_at: string;
}

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
    created_at: string;
    updated_at: string;
    genres: Genre[];
    category: {
        id: number;
        name: string;
    };
    publisher?: {
        id: number;
        name: string;
        country?: string;
    };
    copies: BookCopy[];
}

/* =========================
   Props
========================= */
const props = defineProps<{
    book: Book;
}>();

/* =========================
   State
========================= */
const activeTab = ref('details');
const showAddCopyDialog = ref(false);

/* =========================
   Methods
========================= */
const formatBookFormat = (format: string): string => {
    const formats: Record<string, string> = {
        hardcover: 'Hardcover',
        paperback: 'Paperback',
        ebook: 'E-book',
        audiobook: 'Audiobook',
    };
    return formats[format] ?? format;
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        available: 'default',
        borrowed: 'secondary',
        maintenance: 'destructive',
        lost: 'outline',
    };
    return colors[status] || 'default';
};

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        available: 'Available',
        borrowed: 'Borrowed',
        maintenance: 'Maintenance',
        lost: 'Lost',
    };
    return labels[status] || status;
};

const addCopy = () => {
    router.post(route('books.copies.store', props.book.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showAddCopyDialog.value = false;
        },
    });
};

const generateQRCode = (copyId: number) => {
    router.post(route('books.copies.generate-qr', [props.book.id, copyId]), {}, {
        preserveScroll: true,
    });
};

const downloadQRCode = (qrCodeUrl: string, copyNumber: string) => {
    const link = document.createElement('a');
    link.href = qrCodeUrl;
    link.download = `${props.book.title}-${copyNumber}-QR.png`;
    link.click();
};

const deleteCopy = (copyId: number) => {
    if (confirm('Are you sure you want to delete this copy?')) {
        router.delete(route('books.copies.destroy', [props.book.id, copyId]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="book.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <!-- Header with Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-foreground">
                        {{ book.title }}
                    </h1>
                    <p class="mt-1 text-lg text-muted-foreground">
                        by {{ book.author_name }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link :href="route('books.edit', book.id)">
                        <Button variant="outline">
                            Edit Book
                        </Button>
                    </Link>
                    <Link :href="route('books.index')">
                        <Button variant="secondary">
                            Back to List
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab" class="w-full">
                <TabsList class="grid w-full max-w-md grid-cols-2">
                    <TabsTrigger value="details">
                        Book Details
                    </TabsTrigger>
                    <TabsTrigger value="copies">
                        Copies ({{ book.copies?.length || 0 }})
                    </TabsTrigger>
                </TabsList>

                <!-- Book Details Tab -->
                <TabsContent value="details" class="mt-6">
                    <div class="grid gap-6 lg:grid-cols-3">

                        <!-- Cover Image -->
                        <div class="lg:col-span-1">
                            <Card>
                                <CardContent class="pt-6">
                                    <div class="aspect-[2/3] overflow-hidden rounded-lg bg-muted">
                                        <img
                                            v-if="book.cover_image_url"
                                            :src="book.cover_image_url"
                                            :alt="book.title"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-muted-foreground" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Quick Info Badges -->
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <Badge variant="secondary">
                                            {{ formatBookFormat(book.format) }}
                                        </Badge>
                                        <Badge variant="outline">
                                            {{ book.language }}
                                        </Badge>
                                        <Badge v-if="book.publication_year" variant="outline">
                                            {{ book.publication_year }}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Book Information -->
                        <div class="lg:col-span-2">
                            <Card>
                                <CardContent class="pt-6">

                                    <!-- Description -->
                                    <div v-if="book.description" class="mb-6">
                                        <h2 class="mb-2 text-lg font-semibold text-foreground">
                                            Description
                                        </h2>
                                        <p class="text-muted-foreground leading-relaxed">
                                            {{ book.description }}
                                        </p>
                                    </div>

                                    <Separator class="my-6" />

                                    <!-- Book Information Grid -->
                                    <div>
                                        <h2 class="mb-4 text-lg font-semibold text-foreground">
                                            Book Information
                                        </h2>

                                        <div class="grid gap-4 sm:grid-cols-2">

                                            <!-- Genres (Multiple) -->
                                            <div class="sm:col-span-2">
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Genres
                                                </p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <Badge
                                                        v-for="genre in book.genres"
                                                        :key="genre.id"
                                                        variant="secondary"
                                                    >
                                                        {{ genre.name }}
                                                    </Badge>
                                                </div>
                                            </div>

                                            <!-- Category -->
                                            <div>
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Category
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ book.category.name }}
                                                </p>
                                            </div>

                                            <!-- ISBN -->
                                            <div v-if="book.isbn">
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    ISBN
                                                </p>
                                                <p class="mt-1 font-mono text-base text-foreground">
                                                    {{ book.isbn }}
                                                </p>
                                            </div>

                                            <!-- Pages -->
                                            <div>
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Pages
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ book.pages }} pages
                                                </p>
                                            </div>

                                            <!-- Publisher -->
                                            <div v-if="book.publisher">
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Publisher
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ book.publisher.name }}
                                                    <span v-if="book.publisher.country" class="text-muted-foreground">
                                                        ({{ book.publisher.country }})
                                                    </span>
                                                </p>
                                            </div>

                                            <!-- Language -->
                                            <div>
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Language
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ book.language }}
                                                </p>
                                            </div>

                                            <!-- Format -->
                                            <div>
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Format
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ formatBookFormat(book.format) }}
                                                </p>
                                            </div>

                                            <!-- Publication Year -->
                                            <div v-if="book.publication_year">
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Publication Year
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ book.publication_year }}
                                                </p>
                                            </div>

                                        </div>
                                    </div>

                                    <Separator class="my-6" />

                                    <!-- Metadata -->
                                    <div>
                                        <h2 class="mb-4 text-lg font-semibold text-foreground">
                                            Metadata
                                        </h2>

                                        <div class="grid gap-4 sm:grid-cols-2">

                                            <!-- Added Date -->
                                            <div>
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Added to Library
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ formatDate(book.created_at) }}
                                                </p>
                                            </div>

                                            <!-- Last Updated -->
                                            <div>
                                                <p class="text-sm font-medium text-muted-foreground">
                                                    Last Updated
                                                </p>
                                                <p class="mt-1 text-base text-foreground">
                                                    {{ formatDate(book.updated_at) }}
                                                </p>
                                            </div>

                                        </div>
                                    </div>

                                </CardContent>
                            </Card>
                        </div>

                    </div>
                </TabsContent>

                <!-- Book Copies Tab -->
                <TabsContent value="copies" class="mt-6">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Book Copies</CardTitle>
                                    <CardDescription>
                                        Manage physical copies of this book with QR codes for tracking
                                    </CardDescription>
                                </div>
                                <Button @click="addCopy">
                                    <PlusIcon class="mr-2 h-4 w-4" />
                                    Add Copy
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>

                            <!-- Copies List -->
                            <div v-if="book.copies && book.copies.length > 0" class="space-y-4">
                                <Card
                                    v-for="copy in book.copies"
                                    :key="copy.id"
                                    class="overflow-hidden"
                                >
                                    <CardContent class="p-6">
                                        <div class="grid gap-6 md:grid-cols-[auto_1fr_auto]">

                                            <!-- QR Code -->
                                            <div class="flex flex-col items-center gap-2">
                                                <div class="h-32 w-32 overflow-hidden rounded-lg border-2 border-border bg-white p-2">
                                                    <img
                                                        v-if="copy.qr_code_url"
                                                        :src="copy.qr_code_url"
                                                        :alt="`QR Code for ${copy.copy_number}`"
                                                        class="h-full w-full object-contain"
                                                    />
                                                    <div v-else class="flex h-full w-full items-center justify-center bg-muted">
                                                        <QrCode class="h-12 w-12 text-muted-foreground" />
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <Button
                                                        v-if="!copy.qr_code_url"
                                                        size="sm"
                                                        variant="outline"
                                                        @click="generateQRCode(copy.id)"
                                                    >
                                                        <QrCode class="mr-2 h-3 w-3" />
                                                        Generate
                                                    </Button>
                                                    <Button
                                                        v-else
                                                        size="sm"
                                                        variant="outline"
                                                        @click="downloadQRCode(copy.qr_code_url, copy.copy_number)"
                                                    >
                                                        <DownloadIcon class="mr-2 h-3 w-3" />
                                                        Download
                                                    </Button>
                                                </div>
                                            </div>

                                            <!-- Copy Details -->
                                            <div class="space-y-4">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <h3 class="font-semibold text-foreground">
                                                            Copy #{{ copy.copy_number }}
                                                        </h3>
                                                        <p class="text-sm text-muted-foreground">
                                                            Added {{ formatDate(copy.created_at) }}
                                                        </p>
                                                    </div>
                                                    <Badge :variant="getStatusColor(copy.status)">
                                                        {{ getStatusLabel(copy.status) }}
                                                    </Badge>
                                                </div>

                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <div>
                                                        <p class="text-xs font-medium text-muted-foreground">
                                                            Condition
                                                        </p>
                                                        <p class="text-sm text-foreground">
                                                            {{ copy.condition || 'Good' }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-medium text-muted-foreground">
                                                            Location
                                                        </p>
                                                        <p class="text-sm text-foreground">
                                                            {{ copy.location || 'Main Library' }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Borrower Info (if borrowed) -->
                                                <div v-if="copy.status === 'borrowed' && copy.borrowed_by" class="rounded-lg bg-muted p-3">
                                                    <p class="text-xs font-medium text-muted-foreground mb-2">
                                                        Currently Borrowed By
                                                    </p>
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ copy.borrowed_by.name }}
                                                            </p>
                                                            <p class="text-xs text-muted-foreground">
                                                                {{ copy.borrowed_by.email }}
                                                            </p>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-xs text-muted-foreground">
                                                                Due Date
                                                            </p>
                                                            <p class="text-sm font-medium text-foreground">
                                                                {{ copy.due_at ? formatDate(copy.due_at) : 'N/A' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex flex-col gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="deleteCopy(copy.id)"
                                                >
                                                    <Trash2Icon class="h-4 w-4" />
                                                </Button>
                                            </div>

                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="py-12 text-center">
                                <QrCode class="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 class="mt-4 text-sm font-medium text-foreground">
                                    No copies yet
                                </h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Add physical copies of this book to track them with QR codes.
                                </p>
                                <Button class="mt-4" @click="addCopy">
                                    <PlusIcon class="mr-2 h-4 w-4" />
                                    Add First Copy
                                </Button>
                            </div>

                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

        </div>
    </AppLayout>
</template>
