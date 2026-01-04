<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button'
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/components/ui/dialog'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FlashAlert from '@/components/FlashAlert.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PencilIcon, TrashIcon, FolderIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

defineProps<{
    categories: Array<{
        id: number;
        name: string;
        description: string;
    }>;
}>();

// Single Dialog for Add/Edit
const isOpen = ref(false);
const editingCategory = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
});

// Check if we're editing or adding
const isEditing = computed(() => editingCategory.value !== null);
const dialogTitle = computed(() => isEditing.value ? 'Edit Category' : 'New Category');
const dialogDescription = computed(() =>
    isEditing.value ? 'Update the category information.' : 'Create a new category for books.'
);
const submitButtonText = computed(() =>
    form.processing ? 'Processing...' : (isEditing.value ? 'Update Category' : 'Add Category')
);

// Open dialog for adding
const openAddDialog = () => {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    isOpen.value = true;
};

// Open dialog for editing
const openEditDialog = (category: any) => {
    editingCategory.value = category.id;
    form.name = category.name;
    form.description = category.description || '';
    form.clearErrors();
    isOpen.value = true;
}

// Submit form (handles both add and edit)
const submitForm = () => {
    if (isEditing.value) {
        // Update existing category
        form.put(`/categories/update/${editingCategory.value}`, {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
                editingCategory.value = null;
            },
        });
    } else {
        // Create new category
        form.post('/categories/store', {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
            },
        });
    }
};

// Delete confirmation
const deleteDialogOpen = ref(false);
const categoryToDelete = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);

const openDeleteDialog = (category: any) => {
    categoryToDelete.value = { id: category.id, name: category.name };
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (categoryToDelete.value) {
        isDeleting.value = true;
        router.delete(`/categories/${categoryToDelete.value.id}`, {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                categoryToDelete.value = null;
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
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Use FlashAlert component -->
            <FlashAlert />

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold text-foreground">
                    Categories
                </h1>

                <div class="flex gap-2">
                    <Button @click="openAddDialog">
                        New Category
                    </Button>
                </div>
            </div>

            <div class="mt-6">
                <div class="rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow class="hover:bg-transparent">
                                <TableHead class="font-semibold">Name</TableHead>
                                <TableHead class="font-semibold">Description</TableHead>
                                <TableHead class="text-right font-semibold">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in categories" :key="category.id"
                                class="hover:bg-muted/50 transition-colors">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        {{ category.name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ category.description || 'No description provided' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="icon" @click="openEditDialog(category)">
                                            <PencilIcon class="h-4 w-4" />
                                        </Button>
                                        <Button variant="outline" size="icon"
                                            class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                            @click="openDeleteDialog(category)">
                                            <TrashIcon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="categories.length === 0"
                        class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="rounded-full bg-muted p-3 mb-4">
                            <FolderIcon class="h-6 w-6 text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-semibold">No categories yet</h3>
                        <p class="text-sm text-muted-foreground mt-1">Get started by creating your first category.</p>
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
                            <div class="grid gap-3">
                                <Label for="description">Description</Label>
                                <Input id="description" v-model="form.description"
                                    :class="{ 'border-red-500': form.errors.description }" />
                                <p v-if="form.errors.description" class="text-sm text-red-500">
                                    {{ form.errors.description }}
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
                            <span class="font-semibold">"{{ categoryToDelete?.name }}"</span>?
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
