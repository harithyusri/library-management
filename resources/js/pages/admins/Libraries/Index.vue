<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from "ziggy-js";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import {
    Library as LibraryIcon,
    Plus,
    MoreHorizontal,
    Pencil,
    Trash2,
    MapPin,
    Phone,
    Mail,
    Clock,
    Activity,
    Globe
} from 'lucide-vue-next';
import { ref } from 'vue';
import FlashAlert from '@/components/FlashAlert.vue';

interface LibraryData {
    id: number;
    name: string;
    address: string;
    phone: string | null;
    email: string | null;
    opening_hours: string | null;
    latitude: number | null;
    longitude: number | null;
    is_active: boolean;
    created_at: string;
}

const props = defineProps<{
    libraries: LibraryData[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Libraries', href: route('admin.libraries.index') },
];

const deleteId = ref<number | null>(null);
const showDeleteDialog = ref(false);

const confirmDelete = (id: number) => {
    deleteId.value = id;
    showDeleteDialog.value = true;
};

const executeDelete = () => {
    if (deleteId.value) {
        router.delete(route('admin.libraries.destroy', deleteId.value), {
            onSuccess: () => {
                showDeleteDialog.value = false;
                deleteId.value = null;
            },
        });
    }
};

const toggleActive = (library: LibraryData) => {
    router.put(route('admin.libraries.update', library.id), {
        ...library,
        is_active: !library.is_active,
    });
};
</script>

<template>
    <Head title="Library Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <FlashAlert />

            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Library Branches <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Manage your physical library locations, contact information, and coordinates for proximity-based services.</p>
                </div>

                <Link :href="route('admin.libraries.create')">
                    <Button class="bg-indigo-600 hover:bg-indigo-700 text-white font-black h-12 px-6 rounded-2xl shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2 transition-all transform hover:scale-[1.02]">
                        <Plus class="h-5 w-5" />
                        Add New Branch
                    </Button>
                </Link>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100 dark:border-slate-800 flex items-center gap-4">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-2xl">
                        <LibraryIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Branches</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ libraries.length }}</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100 dark:border-slate-800 flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-2xl">
                        <Activity class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Locations</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ libraries.filter(l => l.is_active).length }}</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 p-6 rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100 dark:border-slate-800 flex items-center gap-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 text-amber-600 rounded-2xl">
                        <MapPin class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Geolocated</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ libraries.filter(l => l.latitude && l.longitude).length }}</p>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="bg-white dark:bg-slate-950 rounded-xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 overflow-hidden">
                <Table>
                    <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50">
                        <TableRow>
                            <TableHead class="font-black text-slate-900 dark:text-slate-300 uppercase text-[10px] tracking-widest w-1/3 pl-6">Branch Details</TableHead>
                            <TableHead class="font-black text-slate-900 dark:text-slate-300 uppercase text-[10px] tracking-widest w-1/3">Contact & Schedule</TableHead>
                            <TableHead class="font-black text-slate-900 dark:text-slate-300 uppercase text-[10px] tracking-widest w-1/2">Geolocation</TableHead>
                            <TableHead class="font-black text-slate-900 dark:text-slate-300 uppercase text-[10px] tracking-widest w-1/3 text-center">Status</TableHead>
                            <TableHead class="font-black text-slate-900 dark:text-slate-300 uppercase text-[10px] tracking-widest w-1/3 text-right pr-6">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="library in libraries" :key="library.id" class="group transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-900/50">
                            <!-- Branch Details -->
                            <TableCell class="py-6 pl-6">
                                <div class="flex items-start gap-4">
                                    <div class="mt-1 p-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                                        <LibraryIcon class="h-5 w-5" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-black text-slate-900 dark:text-slate-100 text-base leading-none">
                                            {{ library.name }}
                                        </p>
                                        <p class="text-sm text-slate-500 font-medium line-clamp-2 max-w-[250px]">
                                            {{ library.address }}
                                        </p>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- contact & schedule -->
                            <TableCell>
                                <div class="space-y-2">
                                    <div v-if="library.phone" class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400">
                                        <Phone class="h-3 w-3 text-indigo-400" />
                                        {{ library.phone }}
                                    </div>
                                    <div v-if="library.email" class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-400">
                                        <Mail class="h-3 w-3 text-indigo-400" />
                                        {{ library.email }}
                                    </div>
                                    <div v-if="library.opening_hours" class="flex items-center gap-2 text-xs font-black text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-md inline-flex">
                                        <Clock class="h-3 w-3" />
                                        {{ library.opening_hours }}
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Geolocation -->
                            <TableCell>
                                <div v-if="library.latitude" class="space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-400 w-1/5 font-medium">Lat:</span> {{ library.latitude }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-400 w-1/5 font-medium">Lon:</span> {{ library.longitude }}
                                    </div>
                                    <Badge variant="secondary" class="bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-none text-[9px] font-black uppercase">
                                        Mapped
                                    </Badge>
                                </div>
                                <div v-else>
                                    <Badge variant="outline" class="text-slate-400 border-dashed text-[9px] font-bold">
                                        Pending Coordinates
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Status -->
                            <TableCell class="text-center">
                                <button @click="toggleActive(library)" class="focus:outline-none transition-transform active:scale-95">
                                    <Badge :variant="library.is_active ? 'default' : 'secondary'"
                                        :class="library.is_active ? 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'"
                                        class="rounded-full px-4 py-1 text-[10px] font-black uppercase tracking-wider border-none">
                                        {{ library.is_active ? 'Active' : 'Hidden' }}
                                    </Badge>
                                </button>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-right pr-6">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl">
                                            <MoreHorizontal class="h-5 w-5 text-slate-500" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48 p-2 rounded-2xl border-slate-100 dark:border-slate-800 shadow-xl">
                                        <DropdownMenuItem class="rounded-xl focus:bg-indigo-50 dark:focus:bg-indigo-900/20 focus:text-indigo-700 dark:focus:text-indigo-400 py-2.5 font-bold cursor-pointer transition-colors" as-child>
                                            <Link :href="route('admin.libraries.edit', library.id)">
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Edit Branch
                                            </Link>
                                        </DropdownMenuItem>
                                        <Separator class="my-1 bg-slate-50 dark:bg-slate-800" />
                                        <DropdownMenuItem class="rounded-xl focus:bg-red-50 dark:focus:bg-red-900/20 focus:text-red-700 dark:focus:text-red-400 py-2.5 font-bold cursor-pointer transition-colors text-red-600 dark:text-red-400" @click="confirmDelete(library.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete Branch
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Empty State -->
                <div v-if="libraries.length === 0" class="py-24 text-center">
                    <div class="inline-flex p-6 bg-slate-50 dark:bg-slate-900 rounded-full mb-4">
                        <LibraryIcon class="h-12 w-12 text-slate-300 dark:text-slate-700" />
                    </div>
                    <p class="text-slate-900 dark:text-slate-100 font-black text-xl">No branches registered yet</p>
                    <p class="text-slate-500 dark:text-slate-400 font-medium mb-8">Start by adding your first library location.</p>
                    <Link :href="route('admin.libraries.create')">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 font-black px-8 h-12 rounded-2xl">
                            Add First Branch
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation -->
        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent class="rounded-3xl border-none shadow-2xl p-8">
                <AlertDialogHeader>
                    <div class="w-16 h-16 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center text-red-600 dark:text-red-400 mb-4">
                        <Trash2 class="h-8 w-8" />
                    </div>
                    <AlertDialogTitle class="text-2xl font-black text-slate-900 dark:text-white">Delete Library Branch?</AlertDialogTitle>
                    <AlertDialogDescription class="text-base text-slate-500 dark:text-slate-400 font-medium">
                        This action cannot be undone. All book copies and rooms associated with this branch will be affected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter class="mt-8 gap-3">
                    <AlertDialogCancel class="h-12 rounded-2xl font-bold border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-900">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="h-12 rounded-2xl font-black bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-100 dark:shadow-none">
                        Confirm Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
