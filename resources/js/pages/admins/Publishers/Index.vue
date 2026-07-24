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
        title: 'Publishers',
        href: '#',
    },
];

const props = defineProps<{
    publishers: Array<{
        id: number;
        name: string;
        description: string;
        library_id: number | null;
        library_name: string | null;
    }>;
    libraries: Array<{ id: number; name: string }>;
}>();

const selectedLibrary = ref<string>('all');

const filteredPublishers = computed(() => {
    if (selectedLibrary.value === 'all') return props.publishers;
    if (selectedLibrary.value === 'none') return props.publishers.filter(p => !p.library_id);
    return props.publishers.filter(p => p.library_id === Number(selectedLibrary.value));
});

// Single Dialog for Add/Edit
const isOpen = ref(false);
const editingPublisher = ref<number | null>(null);

const form = useForm({
    library_id: 'none' as string,
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
    form.library_id = publisher.library_id ? String(publisher.library_id) : 'none';
    form.name = publisher.name;
    form.description = publisher.description || '';
    form.clearErrors();
    isOpen.value = true;
}

// Submit form (handles both add and edit)
const submitForm = () => {
    const payload = { ...form.data(), library_id: form.library_id !== 'none' ? Number(form.library_id) : null };
    if (isEditing.value) {
        form.transform(() => payload).put(`/admin/publishers/update/${editingPublisher.value}`, {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
                editingPublisher.value = null;
            },
        });
    } else {
        form.transform(() => payload).post('/admin/publishers/store', {
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
        router.delete(`/admin/publishers/${publisherToDelete.value.id}`, {
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
        <div class="space-y-6">
            <!-- Use FlashAlert component -->
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-border">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-foreground">Publishers <span class="text-primary text-6xl leading-none">.</span></h1>
                    <p class="text-muted-foreground font-medium italic">Manage book publishing houses and distribution partners.</p>
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
                    <Button v-if="$page.props.auth.can?.create_publishers" @click="openAddDialog" class="bg-primary hover:opacity-90 text-primary-foreground rounded-xl px-6 h-11 font-bold flex items-center gap-2">
                        <Plus class="h-5 w-5" />
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
                            <TableRow v-for="publisher in filteredPublishers" :key="publisher.id"
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
                                        <Button v-if="$page.props.auth.can?.edit_publishers" variant="outline" size="icon" @click="openEditDialog(publisher)">
                                            <PencilIcon class="h-4 w-4" />
                                        </Button>
                                        <Button v-if="$page.props.auth.can?.delete_publishers" variant="outline" size="icon"
                                            class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                            @click="openDeleteDialog(publisher)">
                                            <TrashIcon class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div v-if="filteredPublishers.length === 0"
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
