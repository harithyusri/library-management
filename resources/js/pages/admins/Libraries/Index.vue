<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
import { route } from "ziggy-js";
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Library as LibraryIcon, Plus, MoreHorizontal, Pencil, Trash2, MapPin, Phone, Mail, Clock, Activity } from 'lucide-vue-next';
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

            <PageHeader title="Libraries" description="Manage your library locations, contact information, and coordinates for proximity-based services.">
                <Link :href="route('admin.libraries.create')">
                    <Button class="bg-primary hover:opacity-90 text-primary-foreground rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2">
                        <Plus class="h-4 w-4" />
                        Add Library
                    </Button>
                </Link>
            </PageHeader>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-card border border-border rounded-xl p-5 flex items-center gap-4">
                    <div class="p-2.5 bg-primary/10 rounded-xl">
                        <LibraryIcon class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Total Libraries</p>
                        <p class="text-2xl font-serif text-foreground">{{ libraries.length }}</p>
                    </div>
                </div>
                <div class="bg-card border border-border rounded-xl p-5 flex items-center gap-4">
                    <div class="p-2.5 bg-emerald-500/10 rounded-xl">
                        <Activity class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Active Locations</p>
                        <p class="text-2xl font-serif text-foreground">{{ libraries.filter(l => l.is_active).length }}</p>
                    </div>
                </div>
                <div class="bg-card border border-border rounded-xl p-5 flex items-center gap-4">
                    <div class="p-2.5 bg-[#c5a059]/10 rounded-xl">
                        <MapPin class="h-5 w-5 text-[#c5a059]" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Geolocated</p>
                        <p class="text-2xl font-serif text-foreground">{{ libraries.filter(l => l.latitude && l.longitude).length }}</p>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-card border border-border rounded-xl overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="pl-6 text-[10px] font-bold uppercase tracking-widest">Library Details</TableHead>
                            <TableHead class="text-[10px] font-bold uppercase tracking-widest">Contact & Schedule</TableHead>
                            <TableHead class="text-[10px] font-bold uppercase tracking-widest">Geolocation</TableHead>
                            <TableHead class="text-[10px] font-bold uppercase tracking-widest text-center">Status</TableHead>
                            <TableHead class="text-[10px] font-bold uppercase tracking-widest text-right pr-6">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="library in libraries" :key="library.id"
                            class="group transition-colors hover:bg-muted/40">

                            <!-- Branch Details -->
                            <TableCell class="py-5 pl-6">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 p-2 bg-primary/10 rounded-lg group-hover:bg-primary group-hover:text-primary-foreground transition-all duration-200">
                                        <LibraryIcon class="h-4 w-4 text-primary group-hover:text-primary-foreground" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-foreground text-sm leading-none mb-1">{{ library.name }}</p>
                                        <p class="text-xs text-muted-foreground line-clamp-2 max-w-[220px]">{{ library.address }}</p>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Contact & Schedule -->
                            <TableCell>
                                <div class="space-y-1.5">
                                    <div v-if="library.phone" class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Phone class="h-3 w-3 text-[#c5a059]" />
                                        {{ library.phone }}
                                    </div>
                                    <div v-if="library.email" class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <Mail class="h-3 w-3 text-[#c5a059]" />
                                        {{ library.email }}
                                    </div>
                                    <div v-if="library.opening_hours" class="flex items-center gap-1.5 text-[11px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md w-fit">
                                        <Clock class="h-3 w-3" />
                                        {{ library.opening_hours }}
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Geolocation -->
                            <TableCell>
                                <div v-if="library.latitude" class="space-y-1">
                                    <div class="text-xs text-muted-foreground">
                                        <span class="font-medium">Lat:</span> {{ library.latitude }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        <span class="font-medium">Lon:</span> {{ library.longitude }}
                                    </div>
                                    <Badge variant="secondary" class="text-[9px] font-bold uppercase bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-0">
                                        Mapped
                                    </Badge>
                                </div>
                                <div v-else>
                                    <Badge variant="outline" class="text-[9px] font-bold border-dashed text-muted-foreground">
                                        No Coordinates
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Status -->
                            <TableCell class="text-center">
                                <button @click="toggleActive(library)" class="focus:outline-none transition-transform active:scale-95">
                                    <Badge
                                        :class="library.is_active
                                            ? 'bg-primary text-primary-foreground hover:opacity-90'
                                            : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                                        class="rounded-full px-3 py-0.5 text-[10px] font-bold uppercase tracking-wider border-0 cursor-pointer">
                                        {{ library.is_active ? 'Active' : 'Hidden' }}
                                    </Badge>
                                </button>
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="text-right pr-6">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0 rounded-lg">
                                            <MoreHorizontal class="h-4 w-4 text-muted-foreground" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-44 p-1.5 rounded-xl">
                                        <DropdownMenuItem as-child class="rounded-lg cursor-pointer">
                                            <Link :href="route('admin.libraries.edit', library.id)" class="flex items-center gap-2">
                                                <Pencil class="h-3.5 w-3.5" />
                                                Edit Library
                                            </Link>
                                        </DropdownMenuItem>
                                        <Separator class="my-1" />
                                        <DropdownMenuItem
                                            class="rounded-lg cursor-pointer text-destructive focus:text-destructive focus:bg-destructive/10"
                                            @click="confirmDelete(library.id)">
                                            <Trash2 class="mr-2 h-3.5 w-3.5" />
                                            Delete Library
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Empty State -->
                <div v-if="libraries.length === 0" class="py-24 text-center">
                    <div class="inline-flex p-5 bg-muted rounded-full mb-4">
                        <LibraryIcon class="h-10 w-10 text-muted-foreground" />
                    </div>
                    <p class="font-serif text-foreground text-xl mb-1">No libraries registered yet</p>
                    <p class="text-muted-foreground text-sm mb-6">Start by adding your first library.</p>
                    <Link :href="route('admin.libraries.create')">
                        <Button class="bg-primary hover:opacity-90 text-primary-foreground rounded-lg px-4 py-2 text-sm font-bold flex items-center gap-2 mx-auto">
                            <Plus class="h-4 w-4" />
                            Add Library
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation -->
        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <div class="w-12 h-12 bg-destructive/10 rounded-xl flex items-center justify-center text-destructive mb-2">
                        <Trash2 class="h-5 w-5" />
                    </div>
                    <AlertDialogTitle class="font-serif text-xl">Delete Library?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. All book copies and rooms associated with this library will be affected.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-xl">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete"
                        class="rounded-xl bg-destructive hover:bg-destructive/90 text-destructive-foreground">
                        Delete Library
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
