<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next'
import { route } from "ziggy-js";
import { ref } from 'vue';
import { router, usePage, useForm, Head } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/components/ui/dialog'

import { PlusIcon } from 'lucide-vue-next'

import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'

import FlashAlert from '@/components/FlashAlert.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Books',
        href: route('books.index'),
    },
    {
        title: 'Add New Book',
        href: route('books.create'),
    },
];

interface FlashMessages {
    success?: string;
    error?: string;
    created_genre?: Genre;
    created_category?: Category;
    created_publisher?: Publisher;
}

const page = usePage<PageProps & { flash?: FlashMessages }>();

/* =========================
   Types
========================= */
interface Genre {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
}

interface Publisher {
    id: number;
    name: string;
    country?: string;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    genres: Genre[];
    categories: Category[];
    publishers: Publisher[];
    formatOptions: Record<string, string>;
    languageOptions: Record<string, string>;
}>();

/* =========================
   State
========================= */
const form = useForm({
    title: '',
    author_name: '',
    isbn: '',
    genre_ids: [] as string[],
    category_id: '',
    publisher_id: '',
    published_year: '',
    format: '',
    pages: '',
    language: '',
    description: '',
    cover_image: null as File | null,
});

const coverImagePreview = ref<string | null>(null);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const openGenre = ref(false);
const openCategory = ref(false);
const openPublisher = ref(false);

/* =========================
   Methods
========================= */
const handleCoverImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.cover_image = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            coverImagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeCoverImage = () => {
    form.cover_image = null;
    coverImagePreview.value = null;
};

const submitForm = () => {
    // useForm automatically handles FormData when a File is present
    form.post(route('books.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const cancel = () => {
    router.visit(route('books.index'));
};

const isCategoryDialogOpen = ref(false);
const categoryForm = useForm({
    name: '',
    code: '',
    description: '',
});

const openAddCategory = () => {
    categoryForm.reset();
    categoryForm.clearErrors();
    isCategoryDialogOpen.value = true;
};

const submitCategory = () => {
    categoryForm.post('/categories/store', {
        preserveScroll: true,
        onSuccess: () => {
            const created = page.props.flash?.created_category;

            if (created && !form.category_id.includes(String(created.id))) {
                form.category_id = String(created.id);
            }

            isCategoryDialogOpen.value = false;
            categoryForm.reset();
        },
    });
};

const isGenreDialogOpen = ref(false);
const genreForm = useForm({
    name: '',
    description: '',
});

const openAddGenre = () => {
    genreForm.reset();
    genreForm.clearErrors();
    isGenreDialogOpen.value = true;
};

const submitGenre = () => {
    genreForm.post('/genres/store', {
        preserveScroll: true,
        onSuccess: () => {
            const created = page.props.flash?.created_genre;

            if (created && !form.genre_ids.includes(String(created.id))) {
                form.genre_ids.push(String(created.id));
            }

            isGenreDialogOpen.value = false;
            genreForm.reset();
        },
    });
};

const isPublisherDialogOpen = ref(false);
const publisherForm = useForm({
    name: '',
    description: '',
});

const openAddPublisher = () => {
    publisherForm.reset();
    publisherForm.clearErrors();
    isPublisherDialogOpen.value = true;
};

const submitPublisher = () => {
    publisherForm.post('/publishers/store', {
        preserveScroll: true,
        onSuccess: () => {
            // Auto-select logic
            const created = page.props.flash?.created_publisher;
            if (created) form.publisher_id = String(created.id);

            isPublisherDialogOpen.value = false;
            publisherForm.reset();
            // No toast code needed here; FlashAlert.vue handles it!
        },
    });
};

</script>

<template>

    <Head title="Add New Book" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />
            <!-- Header -->
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold text-foreground">
                    Add New Book
                </h1>
                <p class="text-sm text-muted-foreground">
                    Fill in the details below to add a new book to your library
                </p>
            </div>

            <hr>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6">

                <!-- Basic Information Card -->

                <h2 class="mb-4 text-lg font-semibold text-foreground">Basic Information</h2>

                <div class="grid gap-6 md:grid-cols-2">

                    <!-- Title -->
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Title <span class="text-destructive">*</span>
                        </label>
                        <input v-model="form.title" type="text" placeholder="Enter book title" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                    focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.title }" />
                        <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <!-- Author -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Author <span class="text-destructive">*</span>
                        </label>
                        <input v-model="form.author_name" type="text" placeholder="Enter author name" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                    focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.author_name }" />
                        <p v-if="form.errors.author_name" class="mt-1 text-xs text-destructive">
                            {{ form.errors.author_name }}
                        </p>
                    </div>

                    <!-- ISBN -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            ISBN
                        </label>
                        <input v-model="form.isbn" type="text" placeholder="Enter ISBN" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                    focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.isbn }" />
                        <p v-if="form.errors.isbn" class="mt-1 text-xs text-destructive">
                            {{ form.errors.isbn }}
                        </p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Category <span class="text-destructive">*</span>
                        </label>
                        <Popover v-model:open="openCategory">
                            <PopoverTrigger as-child>
                                <Button variant="outline" role="combobox" :class="cn(
                                    'w-full justify-between font-normal',
                                    !form.category_id && 'text-muted-foreground',
                                )">
                                    {{form.category_id ? props.categories.find(c => c.id ===
                                        Number(form.category_id))?.name : 'Select category'}}
                                    <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Search category..." />
                                    <CommandList>
                                        <CommandEmpty>No category found.</CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem v-for="category in props.categories" :key="category.id"
                                                :value="category.name"
                                                @select="() => { form.category_id = String(category.id); openCategory = false; }">
                                                <CheckIcon
                                                    :class="cn('mr-2 h-4 w-4', Number(form.category_id) === category.id ? 'opacity-100' : 'opacity-0')" />
                                                {{ category.name }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>

                        <p class="mt-2 text-xs text-muted-foreground">
                            Cannot find the category you're looking for?
                            <button type="button" @click="openAddCategory"
                                class="text-primary font-medium hover:underline">
                                Create a new category
                            </button>
                        </p>

                            <Dialog v-model:open="isCategoryDialogOpen">
                            <DialogContent class="sm:max-w-[425px]">
                                <form @submit.prevent="submitCategory">
                                    <DialogHeader>
                                        <DialogTitle>New Category</DialogTitle>
                                        <DialogDescription>
                                            Add a new category to the list.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-4 py-4">
                                        <div class="grid gap-2">
                                            <Label for="name">Name</Label>
                                            <Input id="name" v-model="categoryForm.name"
                                                :class="{ 'border-destructive': categoryForm.errors.name }" />
                                            <span v-if="categoryForm.errors.name" class="text-xs text-destructive">
                                                {{ categoryForm.errors.name }}
                                            </span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="name">Code</Label>
                                            <Input id="name" v-model="categoryForm.code" @input="categoryForm.code = categoryForm.code.toUpperCase()"
                                                :class="{ 'border-destructive': categoryForm.errors.code }" />
                                            <span v-if="categoryForm.errors.code" class="text-xs text-destructive">
                                                {{ categoryForm.errors.code }}
                                            </span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="description">Description</Label>
                                            <Input id="description" v-model="categoryForm.description" />
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isCategoryDialogOpen = false">
                                            Cancel
                                        </Button>
                                        <Button type="submit" :disabled="categoryForm.processing">
                                            Save Category
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>

                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-destructive">
                            {{ form.errors.category_id }}
                        </p>
                    </div>

                    <!-- Genre -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Genres <span class="text-destructive">*</span>
                        </label>
                        <Popover v-model:open="openGenre">
                            <PopoverTrigger as-child>
                                <Button variant="outline" role="combobox" :class="cn(
                                    'w-full justify-between font-normal h-auto min-h-10',
                                    form.genre_ids.length === 0 && 'text-muted-foreground',
                                )">
                                    <div class="flex flex-wrap gap-1">
                                        <template v-if="form.genre_ids.length === 0">
                                            Select genres
                                        </template>

                                        <template v-else>
                                            <Badge v-for="id in form.genre_ids" :key="id" variant="secondary"
                                                class="rounded-sm px-1 font-normal">
                                                {{
                                                    props.genres.find(g => g.id === Number(id))
                                                        ? props.genres.find(g => g.id === Number(id))!.name
                                                        : 'Loading...'
                                                }}
                                            </Badge>

                                        </template>
                                    </div>
                                    <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Search genre..." />
                                    <CommandList>
                                        <CommandEmpty>
                                            <div class="py-4 text-center">
                                                <p class="text-sm">No genre found.</p>
                                                <Button variant="ghost" size="sm" class="mt-2 text-primary"
                                                    @click="openAddGenre">
                                                    <PlusIcon class="mr-2 h-4 w-4" /> Create this genre
                                                </Button>
                                            </div>
                                        </CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem v-for="genre in props.genres" :key="genre.id"
                                                :value="genre.name" @select="() => {
                                                    const id = String(genre.id);
                                                    const index = form.genre_ids.indexOf(id);
                                                    if (index > -1) {
                                                        form.genre_ids.splice(index, 1); // Remove if exists
                                                    } else {
                                                        form.genre_ids.push(id); // Add if not exists
                                                    }
                                                }">
                                                <CheckIcon :class="cn(
                                                    'mr-2 h-4 w-4',
                                                    form.genre_ids.includes(String(genre.id)) ? 'opacity-100' : 'opacity-0'
                                                )" />
                                                {{ genre.name }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>

                        <p class="mt-2 text-xs text-muted-foreground">
                            Cannot find the genre you're looking for?
                            <button type="button" @click="openAddGenre"
                                class="text-primary font-medium hover:underline">
                                Create a new genre
                            </button>
                        </p>

                        <!-- Add Genre Dialog (Global) -->
                        <Dialog v-model:open="isGenreDialogOpen">
                            <DialogContent class="sm:max-w-[425px]">
                                <form @submit.prevent="submitGenre">
                                    <DialogHeader>
                                        <DialogTitle>New Genre</DialogTitle>
                                        <DialogDescription>
                                            Add a new genre to the list.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-4 py-4">
                                        <div class="grid gap-2">
                                            <Label for="name">Name</Label>
                                            <Input id="name" v-model="genreForm.name"
                                                :class="{ 'border-destructive': genreForm.errors.name }" />
                                            <span v-if="genreForm.errors.name" class="text-xs text-destructive">
                                                {{ genreForm.errors.name }}
                                            </span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="description">Description</Label>
                                            <Input id="description" v-model="genreForm.description" />
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isGenreDialogOpen = false">
                                            Cancel
                                        </Button>
                                        <Button type="submit" :disabled="genreForm.processing">
                                            Save Genre
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>

                        <p v-if="form.errors.genre_ids" class="mt-1 text-xs text-destructive">
                            {{ form.errors.genre_ids }}
                        </p>
                    </div>

                    <!-- Publisher -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Publisher <span class="text-destructive">*</span>
                        </label>
                        <Popover v-model:open="openPublisher">
                            <PopoverTrigger as-child>
                                <Button variant="outline" role="combobox" :class="cn(
                                    'w-full justify-between font-normal',
                                    !form.publisher_id && 'text-muted-foreground',
                                )">
                                    {{form.publisher_id ? props.publishers.find(p => p.id ===
                                        Number(form.publisher_id))?.name : 'Select publisher'}}
                                    <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent class="w-[--radix-popover-trigger-width] p-0" align="start">
                                <Command>
                                    <CommandInput placeholder="Search publisher..." />
                                    <CommandList>
                                        <CommandEmpty>No publisher found.</CommandEmpty>
                                        <CommandGroup>
                                            <CommandItem v-for="publisher in props.publishers" :key="publisher.id"
                                                :value="publisher.name"
                                                @select="() => { form.publisher_id = String(publisher.id); openPublisher = false; }">
                                                <CheckIcon
                                                    :class="cn('mr-2 h-4 w-4', Number(form.publisher_id) === publisher.id ? 'opacity-100' : 'opacity-0')" />
                                                {{ publisher.name }}
                                            </CommandItem>
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>

                        <p class="mt-2 text-xs text-muted-foreground">
                            Cannot find the publisher you're looking for?
                            <button type="button" @click="openAddPublisher"
                                class="text-primary font-medium hover:underline">
                                Create a new publisher
                            </button>
                        </p>

                            <Dialog v-model:open="isPublisherDialogOpen">
                            <DialogContent class="sm:max-w-[425px]">
                                <form @submit.prevent="submitPublisher">
                                    <DialogHeader>
                                        <DialogTitle>New Publisher</DialogTitle>
                                        <DialogDescription>
                                            Add a new publisher to the list.
                                        </DialogDescription>
                                    </DialogHeader>

                                    <div class="grid gap-4 py-4">
                                        <div class="grid gap-2">
                                            <Label for="name">Name</Label>
                                            <Input id="name" v-model="publisherForm.name"
                                                :class="{ 'border-destructive': publisherForm.errors.name }" />
                                            <span v-if="publisherForm.errors.name" class="text-xs text-destructive">
                                                {{ publisherForm.errors.name }}
                                            </span>
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="description">Description</Label>
                                            <Input id="description" v-model="publisherForm.description" />
                                        </div>
                                    </div>

                                    <DialogFooter>
                                        <Button type="button" variant="outline" @click="isPublisherDialogOpen = false">
                                            Cancel
                                        </Button>
                                        <Button type="submit" :disabled="publisherForm.processing">
                                            Save Publisher
                                        </Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>

                        <p v-if="form.errors.publisher_id" class="mt-1 text-xs text-destructive">
                            {{ form.errors.publisher_id }}
                        </p>
                    </div>

                    <!-- Published Year -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Published Year
                        </label>
                        <input v-model="form.published_year" type="number" min="1000"
                            :max="new Date().getFullYear()" placeholder="YYYY" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                    focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.published_year }" />
                        <p v-if="form.errors.published_year" class="mt-1 text-xs text-destructive">
                            {{ form.errors.published_year }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Description
                        </label>
                        <textarea v-model="form.description" rows="4" placeholder="Enter book description" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                    focus:border-ring focus:outline-none resize-none"
                            :class="{ 'border-destructive': form.errors.description }"></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                </div>

                <hr>
                <!-- Book Details Card -->

                <h2 class="mb-4 text-lg font-semibold text-foreground">Book Details</h2>

                <div class="grid gap-6 md:grid-cols-3">

                    <!-- Format -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Format <span class="text-destructive">*</span>
                        </label>
                        <Select v-model="form.format">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a format" />
                            </SelectTrigger>
                            <SelectContent class="z-50">
                                <SelectGroup>
                                    <SelectItem v-for="(label, key) in props.formatOptions" :key="key" :value="key">
                                        {{ label }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.format" class="mt-1 text-xs text-destructive">
                            {{ form.errors.format }}
                        </p>
                    </div>

                    <!-- Language -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Language <span class="text-destructive">*</span>
                        </label>
                        <Select v-model="form.language">
                            <SelectTrigger>
                                <SelectValue placeholder="Select a language" />
                            </SelectTrigger>
                            <SelectContent class="z-50">
                                <SelectGroup>
                                    <SelectItem v-for="(label, key) in props.languageOptions" :key="key"
                                        :value="key">
                                        {{ label }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.language" class="mt-1 text-xs text-destructive">
                            {{ form.errors.language }}
                        </p>
                    </div>

                    <!-- Number of Pages -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">
                            Number of Pages <span class="text-destructive">*</span>
                        </label>
                        <input v-model.number="form.pages" type="number" min="1" placeholder="Enter number of pages"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground
                                    focus:border-ring focus:outline-none"
                            :class="{ 'border-destructive': form.errors.pages }" />
                        <p v-if="form.errors.pages" class="mt-1 text-xs text-destructive">
                            {{ form.errors.pages }}
                        </p>
                    </div>
                </div>

                <!-- Cover Image Card -->
                <h2 class="mb-4 text-lg font-semibold text-foreground">Cover Image</h2>

                <div class="space-y-4">

                    <!-- Image Preview -->
                    <div v-if="coverImagePreview" class="relative inline-block">
                        <img :src="coverImagePreview" alt="Cover preview"
                            class="h-48 w-auto rounded-lg border border-border object-cover" />
                        <button type="button" @click="removeCoverImage" class="absolute -right-2 -top-2 rounded-full bg-destructive p-1.5 text-destructive-foreground
                                    hover:bg-destructive/90 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- Upload Button -->
                    <div v-if="!coverImagePreview">
                        <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed
                                        border-border bg-muted/50 px-6 py-8 hover:bg-muted"
                            :class="{ 'border-destructive': errors.cover_image }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-muted-foreground"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="mb-2 text-sm font-medium text-foreground">
                                Click to upload cover image
                            </span>
                            <span class="text-xs text-muted-foreground">
                                PNG, JPG, WEBP up to 10MB
                            </span>
                            <input type="file" accept="image/*" @change="handleCoverImageChange" class="hidden" />
                        </label>
                    </div>

                    <p v-if="errors.cover_image" class="text-xs text-destructive">
                        {{ errors.cover_image }}
                    </p>

                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 bg-background p-6">
                    <button type="button" @click="cancel" :disabled="processing" class="rounded-md border border-border bg-background px-6 py-2 text-sm font-medium text-foreground
                               hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="processing" class="rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground
                               hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50">
                        <span v-if="processing">Creating...</span>
                        <span v-else>Create Book</span>
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
