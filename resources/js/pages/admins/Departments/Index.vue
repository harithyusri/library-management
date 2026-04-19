<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive, ref } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Building2, Plus, Edit, Trash2, Search, XCircle } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface Department {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    staff_count: number;
}

interface PaginatedDepartments {
    data: Department[];
    links: any[];
    current_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    departments: PaginatedDepartments;
    filters: { search?: string };
}>();

const breadcrumbs = [
    { title: 'Settings', href: '#' },
    { title: 'Departments', href: route('admin.departments.index') },
];

const searchForm = reactive({
    search: props.filters.search ?? '',
});

const isDialogOpen = ref(false);
const isEditing = ref(false);
const editingDepartment = ref<Department | null>(null);

const form = reactive({
    name: '',
    code: '',
    description: '',
});

const openCreateDialog = () => {
    isEditing.value = false;
    editingDepartment.value = null;
    form.name = '';
    form.code = '';
    form.description = '';
    isDialogOpen.value = true;
};

const openEditDialog = (dept: Department) => {
    isEditing.value = true;
    editingDepartment.value = dept;
    form.name = dept.name;
    form.code = dept.code ?? '';
    form.description = dept.description ?? '';
    isDialogOpen.value = true;
};

const saveDepartment = () => {
    if (isEditing.value && editingDepartment.value) {
        router.put(route('admin.departments.update', editingDepartment.value.id), form, {
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        router.post(route('admin.departments.store'), form, {
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

const deptToDelete = ref<Department | null>(null);
const showDeleteConfirm = ref(false);

const confirmDelete = (dept: Department) => {
    deptToDelete.value = dept;
    showDeleteConfirm.value = true;
};

const deleteDepartment = () => {
    if (deptToDelete.value) {
        router.delete(route('admin.departments.destroy', deptToDelete.value.id), {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                deptToDelete.value = null;
            },
        });
    }
};

const handleSearch = () => {
    router.get(route('admin.departments.index'), { search: searchForm.search }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearSearch = () => {
    searchForm.search = '';
    handleSearch();
};
</script>

<template>
    <Head title="Manage Departments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-6">
            <FlashAlert />

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Departments <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Organize staff members into functional departments.</p>
                </div>

                <Button @click="openCreateDialog" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-bold shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2">
                    <Plus class="h-5 w-5" />
                    Add Department
                </Button>
            </div>

            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                <Input 
                    v-model="searchForm.search" 
                    @keyup.enter="handleSearch"
                    placeholder="Search by name or code..." 
                    class="pl-10"
                />
                <button 
                    v-if="searchForm.search" 
                    @click="clearSearch"
                    class="absolute right-3 top-1/2 -translate-y-1/2"
                >
                    <XCircle class="h-4 w-4 text-slate-300 hover:text-slate-500" />
                </button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div v-if="departments.data.length === 0" class="py-12 text-center">
                        <Building2 class="mx-auto h-12 w-12 text-slate-200" />
                        <h3 class="mt-4 text-sm font-black text-slate-900">No departments found</h3>
                        <p class="mt-1 text-xs text-slate-500 font-medium">Start by adding a new department for your staff.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[100px]">Code</TableHead>
                                    <TableHead>Department Name</TableHead>
                                    <TableHead>Staff Count</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="dept in departments.data" :key="dept.id" class="group transition-colors">
                                    <TableCell class="font-mono text-xs font-bold text-slate-500">
                                        {{ dept.code || 'N/A' }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="font-black text-slate-900">{{ dept.name }}</div>
                                        <div v-if="dept.description" class="text-[10px] text-slate-500 mt-0.5 line-clamp-1 truncate max-w-xs">{{ dept.description }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                            {{ dept.staff_count }} Staff
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1 group-hover:opacity-100 transition-opacity">
                                            <Button @click="openEditDialog(dept)" variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                <Edit class="h-4 w-4 text-slate-600" />
                                            </Button>
                                            <Button @click="confirmDelete(dept)" variant="ghost" size="sm" class="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <div v-if="departments.last_page > 1" class="flex items-center justify-between">
                <p class="text-xs text-slate-500 font-medium tracking-tight">
                    Showing page {{ departments.current_page }} of {{ departments.last_page }} ({{ departments.total }} total)
                </p>
                <div class="flex gap-2">
                    <Link
                        v-for="(link, index) in departments.links"
                        :key="index"
                        :href="link.url || '#'"
                        class="px-3 py-1 text-xs font-bold rounded-lg transition-all"
                        :class="[
                            link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50',
                            !link.url && 'opacity-30 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Add/Edit Dialog -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-md rounded-3xl">
                <DialogHeader>
                    <DialogTitle class="text-xl font-black">{{ isEditing ? 'Edit Department' : 'Add New Department' }}</DialogTitle>
                    <DialogDescription class="text-xs font-medium text-slate-500">
                        {{ isEditing ? 'Update the details for this department.' : 'Create a new functional area for staff organization.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Department Name</Label>
                        <Input v-model="form.name" placeholder="e.g. Information Technology" class="rounded-xl" />
                    </div>
                    <div class="space-y-2">
                        <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Code (Optional)</Label>
                        <Input v-model="form.code" placeholder="e.g. IT" class="rounded-xl font-mono" />
                    </div>
                    <div class="space-y-2">
                        <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Description</Label>
                        <textarea 
                            v-model="form.description" 
                            rows="3" 
                            placeholder="What does this department do?"
                            class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        ></textarea>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="ghost" @click="isDialogOpen = false" class="rounded-xl font-bold">Cancel</Button>
                    <Button 
                        @click="saveDepartment" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold px-6"
                    >
                        {{ isEditing ? 'Update' : 'Create' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation -->
        <AlertDialog v-model:open="showDeleteConfirm">
            <AlertDialogContent class="rounded-3xl border-0 shadow-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle class="text-xl font-black text-slate-900">Are you absolutely sure?</AlertDialogTitle>
                    <AlertDialogDescription class="text-sm font-medium text-slate-500">
                        This will permanently delete the <span class="font-black text-slate-900 underline underline-offset-4 decoration-red-200">{{ deptToDelete?.name }}</span> department. This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-xl font-bold">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="deleteDepartment" class="bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold shadow-lg shadow-red-100">
                        Delete Department
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
