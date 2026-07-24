<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button'
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/components/ui/dialog'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import FlashAlert from '@/components/FlashAlert.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PencilIcon, TrashIcon, FolderIcon, Plus } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '#',
    },
];

const props = defineProps<{
    categories: Array<{
        id: number;
        name: string;
        code: string;
        description: string;
        library_id: number | null;
        library_name: string | null;
    }>;
    libraries: Array<{ id: number; name: string }>;
}>();

const selectedLibrary = ref<string>('all');

const filteredCategories = computed(() => {
    if (selectedLibrary.value === 'all') return props.categories;
    if (selectedLibrary.value === 'none') return props.categories.filter(c => !c.library_id);
    return props.categories.filter(c => c.library_id === Number(selectedLibrary.value));
});

// Single Dialog for Add/Edit
const isOpen = ref(false);
const editingCategory = ref<number | null>(null);

const form = useForm({
    library_id: 'none' as string,
    name: '',
    code: '',
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
    form.library_id = category.library_id ? String(category.library_id) : 'none';
    form.name = category.name;
    form.code = category.code;
    form.description = category.description || '';
    form.clearErrors();
    isOpen.value = true;
}

// Submit form (handles both add and edit)
const submitForm = () => {
    const payload = { ...form.data(), library_id: form.library_id !== 'none' ? Number(form.library_id) : null };
    if (isEditing.value) {
        form.transform(() => payload).put(`/admin/categories/update/${editingCategory.value}`, {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
                editingCategory.value = null;
            },
        });
    } else {
        form.transform(() => payload).post('/admin/categories/store', {
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
        router.delete(`/admin/categories/${categoryToDelete.value.id}`, {
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
        <div class="space-y-6">
            <!-- Use FlashAlert component -->
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-border">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-foreground">Book Categories <span class="text-primary text-6xl leading-none">.</span></h1>
                    <p class="text-muted-foreground font-medium italic">Organize library assets by classifications and sections.</p>
                </div>

                <div class="flex items-center gap-3">
                    <Select v-model="selectedLibrary">
                        <SelectTrigger class="w-48 h-11 rounded-xl border-input bg-background">
                            <SelectValue placeholder="All Libraries" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Libraries</SelectItem>
                            <SelectItem value="none">No Library</SelectItem>
                            <SelectItem v-for="lib in libraries" :key="lib.id" :value="String(lib.id)">
                                {{ lib.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button v-if="$page.props.auth.can?.create_categories" @click="openAddDialog" class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl px-6 h-11 font-bold flex items-center gap-2">
                        <Plus class="h-5 w-5" />
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
                                <TableHead class="font-semibold">Code</TableHead>
                                <TableHead class="font-semibold">Description</TableHead>
                                <TableHead class="text-right font-semibold">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in filteredCategories" :key="category.id"
                                class="hover:bg-muted/50 transition-colors">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        {{ category.name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ category.code }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ category.description || 'No description provided' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button v-if="$page.props.auth.can?.edit_categories" variant="outline" size="icon" @click="openEditDialog(category)">
                                            <PencilIcon class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="$page.props.auth.can?.delete_categories" variant="outline" size="icon"
                                            class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                            @click="openDeleteDialog(category)">
                                            <TrashIcon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="filteredCategories.length === 0"
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
                                <Label>Library</Label>
                                <Select v-model="form.library_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.library_id }">
                                        <SelectValue placeholder="Select a library (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None</SelectItem>
                                        <SelectItem v-for="lib in libraries" :key="lib.id" :value="String(lib.id)">
                                            {{ lib.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.library_id" class="text-sm text-destructive">{{ form.errors.library_id }}</p>
                            </div>
                            <div class="grid gap-3">
                                <Label for="name">Name</Label>
                                <Input id="name" v-model="form.name" :class="{ 'border-red-500': form.errors.name }" />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>
                            <div class="grid gap-3">
                                <Label for="name">Code</Label>
                                <Input id="name" v-model="form.code" maxlength="3" :class="{ 'border-red-500': form.errors.code }" />
                                <p v-if="form.errors.code" class="text-sm text-red-500">
                                    {{ form.errors.code }}
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
