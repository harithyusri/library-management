<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button'
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/components/ui/dialog'
// import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FlashAlert from '@/components/FlashAlert.vue'; // Import FlashAlert
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PencilIcon, TrashIcon, FolderIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Genres',
        href: '/genres',
    },
];

defineProps<{
    genres: Array<{
        id: number;
        name: string;
    }>;
}>();

// Single Dialog for Add/Edit
const isOpen = ref(false);
const editingGenre = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
});

// Check if we're editing or adding
const isEditing = computed(() => editingGenre.value !== null);
const dialogTitle = computed(() => isEditing.value ? 'Edit Genre' : 'New Genre');
const dialogDescription = computed(() =>
    isEditing.value ? 'Update the genre information.' : 'Create a new genre for books.'
);
const submitButtonText = computed(() =>
    form.processing ? 'Processing...' : (isEditing.value ? 'Update Genre' : 'Add Genre')
);

// Open dialog for adding
const openAddDialog = () => {
    editingGenre.value = null;
    form.reset();
    form.clearErrors();
    isOpen.value = true;
};

// Open dialog for editing
const openEditDialog = (genre: any) => {
    editingGenre.value = genre.id;
    form.name = genre.name;
    form.description = genre.description || '';
    form.clearErrors();
    isOpen.value = true;
}

// Submit form (handles both add and edit)
const submitForm = () => {
    if (isEditing.value) {
        // Update existing genre
        form.put(`/genres/update/${editingGenre.value}`, {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
                editingGenre.value = null;
            },
        });
    } else {
        // Create new genre
        form.post('/genres/store', {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
            },
        });
    }
};

// Delete confirmation
const deleteDialogOpen = ref(false);
const genreToDelete = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);

const openDeleteDialog = (genre: any) => {
    genreToDelete.value = { id: genre.id, name: genre.name };
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (genreToDelete.value) {
        isDeleting.value = true;
        router.delete(`/genres/${genreToDelete.value.id}`, {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                genreToDelete.value = null;
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
            },
        });
    }
};
</script>

<template>

    <Head title="Genres" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Use FlashAlert component -->
            <FlashAlert />

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold text-foreground">
                    Genres
                </h1>

                <div class="flex gap-2">
                    <Button @click="openAddDialog">
                        New Genre
                    </Button>
                </div>
            </div>

            <div class="mt-6">
                <div class="rounded-lg border-none bg-card">
                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div v-for="genre in genres" :key="genre.id"
                            class="flex items-center justify-between p-4 border rounded-lg bg-card hover:bg-muted/50 transition-colors">
                            <span class="font-medium">{{ genre.name }}</span>
                            <div class="flex gap-2">
                                <Button variant="ghost" size="icon" class="h-8 w-8" @click="openEditDialog(genre)">
                                    <PencilIcon class="h-4 w-4" />
                                </Button>
                                <Button variant="ghost" size="icon"
                                    class="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                    @click="openDeleteDialog(genre)">
                                    <TrashIcon class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div v-if="genres.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="rounded-full bg-muted p-3 mb-4">
                            <FolderIcon class="h-6 w-6 text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-semibold">No genres yet</h3>
                        <p class="text-sm text-muted-foreground mt-1">Get started by creating your first genre.</p>
                    </div>
                </div>
            </div>

            <!-- Single Dialog for Add/Edit -->
            <Dialog v-model:open="isOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <form @submit.prevent="submitForm">
                        <DialogHeader>
                            <DialogTitle>{{ dialogTitle }}</DialogTitle>
                            <DialogDescription>
                                {{ dialogDescription }}
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-3">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" :class="{ 'border-red-500': form.errors.name }" />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">
                                {{ submitButtonText }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="deleteDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Delete Category</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete
                            <span class="font-semibold">"{{ genreToDelete?.name }}"</span>?
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="outline">Cancel</Button>
                        </DialogClose>
                        <Button @click="confirmDelete" variant="destructive" :disabled="isDeleting">
                            {{ isDeleting ? 'Deleting...' : 'Delete' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
