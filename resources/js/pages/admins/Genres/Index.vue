<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button'
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import FlashAlert from '@/components/FlashAlert.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PencilIcon, TrashIcon, FolderIcon, Plus, Globe2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Genres', href: '#' },
];

const props = defineProps<{
    genres: Array<{
        id: number;
        name: string;
        library_id: number | null;
        library_name: string | null;
    }>;
    libraries: Array<{ id: number; name: string }>;
}>();

const selectedLibrary = ref<string>('all');
const searchQuery = ref('');

// Search first, then filter by library
const searchedGenres = computed(() => {
    if (!searchQuery.value.trim()) return props.genres;
    const q = searchQuery.value.toLowerCase();
    return props.genres.filter(g => g.name.toLowerCase().includes(q));
});

const filteredGenres = computed(() => {
    if (selectedLibrary.value === 'all') return searchedGenres.value;
    if (selectedLibrary.value === 'none') return searchedGenres.value.filter(g => !g.library_id);
    return searchedGenres.value.filter(g => g.library_id === Number(selectedLibrary.value));
});

// Group genres by library so "shared/national" genres are visually distinct
// from library-specific ones — important once this scales to many libraries.
interface GenreGroup {
    key: string;
    label: string | null;
    isShared: boolean;
    genres: typeof props.genres;
}

const groupedGenres = computed<GenreGroup[]>(() => {
    // When a specific library (or "none") is selected, no need to group — flat list.
    if (selectedLibrary.value !== 'all') {
        return [{ key: selectedLibrary.value, label: null, isShared: selectedLibrary.value === 'none', genres: filteredGenres.value }];
    }

    const groups = new Map<string, GenreGroup>();
    groups.set('none', { key: 'none', label: 'Shared Across All Libraries', isShared: true, genres: [] });

    for (const genre of filteredGenres.value) {
        const key = genre.library_id ? String(genre.library_id) : 'none';
        if (!groups.has(key)) {
            groups.set(key, {
                key,
                label: genre.library_name ?? 'Unknown Library',
                isShared: false,
                genres: [],
            });
        }
        groups.get(key)!.genres.push(genre);
    }

    // Shared group first, then libraries alphabetically
    return Array.from(groups.values())
        .filter(g => g.genres.length > 0)
        .sort((a, b) => {
            if (a.isShared) return -1;
            if (b.isShared) return 1;
            return (a.label ?? '').localeCompare(b.label ?? '');
        });
});

const totalCount = computed(() => filteredGenres.value.length);

// Single Dialog for Add/Edit
const isOpen = ref(false);
const editingGenre = ref<number | null>(null);

const form = useForm({
    library_id: 'none' as string,
    name: '',
    description: '',
});

const isEditing = computed(() => editingGenre.value !== null);
const dialogTitle = computed(() => isEditing.value ? 'Edit Genre' : 'New Genre');
const dialogDescription = computed(() =>
    isEditing.value ? 'Update the genre information.' : 'Create a new genre for books.'
);
const submitButtonText = computed(() =>
    form.processing ? 'Processing...' : (isEditing.value ? 'Update Genre' : 'Add Genre')
);

const openAddDialog = () => {
    editingGenre.value = null;
    form.reset();
    form.clearErrors();
    isOpen.value = true;
};

const openEditDialog = (genre: any) => {
    editingGenre.value = genre.id;
    form.library_id = genre.library_id ? String(genre.library_id) : 'none';
    form.name = genre.name;
    form.description = genre.description || '';
    form.clearErrors();
    isOpen.value = true;
}

const submitForm = () => {
    const payload = { ...form.data(), library_id: form.library_id !== 'none' ? Number(form.library_id) : null };
    if (isEditing.value) {
        form.transform(() => payload).put(`/admin/genres/update/${editingGenre.value}`, {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
                editingGenre.value = null;
            },
        });
    } else {
        form.transform(() => payload).post('/admin/genres/store', {
            onSuccess: () => {
                isOpen.value = false;
                form.reset();
            },
        });
    }
};

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
        router.delete(`/admin/genres/${genreToDelete.value.id}`, {
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
        <div class="space-y-6">
            <FlashAlert />

            <PageHeader title="Book Genres" description="Explore and manage literary genres and thematic tags.">
                <Button v-if="$page.props.auth.can?.create_genres" @click="openAddDialog"
                    class="bg-primary hover:opacity-90 text-primary-foreground rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2">
                    <Plus class="h-5 w-5" />
                    New Genre
                </Button>
            </PageHeader>

            <!-- Filter bar -->
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                    <Input v-model="searchQuery" placeholder="Search genres..."
                        class="h-11 rounded-xl w-full sm:w-64" />
                    <Select v-model="selectedLibrary">
                        <SelectTrigger class="w-48 h-11 rounded-xl border-input bg-background">
                            <SelectValue placeholder="All Libraries" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Libraries</SelectItem>
                            <SelectItem value="none">Shared Only</SelectItem>
                            <SelectItem v-for="lib in libraries" :key="lib.id" :value="String(lib.id)">
                                {{ lib.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ totalCount }} genre{{ totalCount === 1 ? '' : 's' }}
                </p>
            </div>

            <!-- Grouped genre chips -->
            <div class="space-y-8">
                <div v-for="group in groupedGenres" :key="group.key" class="space-y-3">
                    <div v-if="selectedLibrary === 'all'" class="flex items-center gap-2">
                        <Globe2 v-if="group.isShared" class="h-4 w-4 text-primary" />
                        <h3 class="text-sm font-semibold" :class="group.isShared ? 'text-primary' : 'text-foreground'">
                            {{ group.label }}
                        </h3>
                        <Badge variant="secondary" class="rounded-full text-xs">
                            {{ group.genres.length }}
                        </Badge>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <div v-for="genre in group.genres" :key="genre.id"
                            class="group flex items-center gap-2 pl-4 pr-2 py-2 rounded-full border transition-colors"
                            :class="group.isShared
                                ? 'bg-primary/5 border-primary/20 hover:bg-primary/10'
                                : 'bg-card border-border hover:bg-muted/60'">
                            <span class="text-sm font-medium">{{ genre.name }}</span>

                            <div class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <Button v-if="$page.props.auth.can?.edit_genres" variant="ghost" size="icon"
                                    class="h-6 w-6" @click="openEditDialog(genre)">
                                    <PencilIcon class="h-3.5 w-3.5" />
                                </Button>
                                <Button v-if="$page.props.auth.can?.delete_genres" variant="ghost" size="icon"
                                    class="h-6 w-6 text-destructive hover:text-destructive hover:bg-destructive/10"
                                    @click="openDeleteDialog(genre)">
                                    <TrashIcon class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="groupedGenres.length === 0"
                    class="flex flex-col items-center justify-center py-12 text-center border rounded-lg">
                    <div class="rounded-full bg-muted p-3 mb-4">
                        <FolderIcon class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-semibold">No genres found</h3>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ searchQuery ? 'Try a different search term.' : 'Get started by creating your first genre.' }}
                    </p>
                </div>
            </div>

            <!-- Add/Edit Dialog -->
            <Dialog v-model:open="isOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <form @submit.prevent="submitForm">
                        <DialogHeader>
                            <DialogTitle>{{ dialogTitle }}</DialogTitle>
                            <DialogDescription>{{ dialogDescription }}</DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-3">
                                <Label>Library</Label>
                                <Select v-model="form.library_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.library_id }">
                                        <SelectValue placeholder="Select a library (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None (Shared/National)</SelectItem>
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
                                <p v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</p>
                            </div>
                        </div>
                        <DialogFooter>
                            <DialogClose as-child>
                                <Button type="button" variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="form.processing">{{ submitButtonText }}</Button>
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