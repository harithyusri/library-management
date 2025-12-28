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
        title: 'Publishers',
        href: '/publishers',
    },
];

defineProps<{
    publishers: Array<{
        id: number;
        name: string;
        description: string;
    }>;
}>();

// Single Dialog for Add/Edit
const isOpen = ref(false);
const editingPublisher = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
});

// Check if we're editing or adding
const isEditing = computed(() => editingPublisher.value !== null);
const dialogTitle = computed(() => isEditing.value ? 'Edit Publisher' : 'New Publisher');
const dialogDescription = computed(() =>
    isEditing.value ? 'Update the publisher information.' : 'Create a new publisher for books.'
);
const submitButtonText = computed(() =>
    form.processing ? 'Processing...' : (isEditing.value ? 'Update Publisher' : 'Add Publisher')
);

// Open dialog for adding
const openAddDialog = () => {
    editingPublisher.value = null;
    form.reset();
    form.clearErrors();
    isOpen.value = true;
};

// Open dialog for editing
const openEditDialog = (publisher: any) => {
    editingPublisher.value = publisher.id;
    form.name = publisher.name;
    form.description = publisher.description || '';
    form.clearErrors();
    isOpen.value = true;
}

// Submit form (handles both add and edit)
const submitForm = () => {
    if (isEditing.value) {
        // Update existing publisher
        form.put(`/publishers/update/${editingPublisher.value}`, {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
                editingPublisher.value = null;
            },
        });
    } else {
        // Create new publisher
        form.post('/publishers/store', {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
            },
        });
    }
};

// Delete confirmation
const deleteDialogOpen = ref(false);
const publisherToDelete = ref<{ id: number; name: string } | null>(null);
const isDeleting = ref(false);

const openDeleteDialog = (publisher: any) => {
    publisherToDelete.value = { id: publisher.id, name: publisher.name };
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (publisherToDelete.value) {
        isDeleting.value = true;
        router.delete(`/publishers/${publisherToDelete.value.id}`, {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                publisherToDelete.value = null;
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
    <Head title="Publishers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Use FlashAlert component -->
            <FlashAlert />

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold text-foreground">
                    Publishers
                </h1>

                <div class="flex gap-2">
                    <Button @click="openAddDialog">
                        New Publisher
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
                            <TableRow v-for="publisher in publishers" :key="publisher.id"
                                class="hover:bg-muted/50 transition-colors">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        {{ publisher.name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ publisher.description || 'No description provided' }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="outline" size="icon" @click="openEditDialog(publisher)">
                                            <PencilIcon class="h-4 w-4" />
                                        </Button>
                                        <Button variant="outline" size="icon"
                                            class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                            @click="openDeleteDialog(publisher)">
                                            <TrashIcon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="publishers.length === 0"
                        class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="rounded-full bg-muted p-3 mb-4">
                            <FolderIcon class="h-6 w-6 text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-semibold">No publishers yet</h3>
                        <p class="text-sm text-muted-foreground mt-1">Get started by creating your first publisher.</p>
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
                            <span class="font-semibold">"{{ publisherToDelete?.name }}"</span>?
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
