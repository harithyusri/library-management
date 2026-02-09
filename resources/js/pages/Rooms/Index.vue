<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Eye, Users, DoorOpen, MapPin } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Rooms',
        href: route('rooms.index'),
    },
];

/* =========================
   Types
========================= */
interface Room {
    id: number;
    name: string;
    room_number: string;
    type: string;
    capacity: number;
    description?: string;
    amenities?: string[];
    floor?: string;
    status: 'available' | 'maintenance' | 'unavailable';
    hourly_rate: number;
    image?: string;
    type_display: string;
    status_display: string;
    is_free: boolean;
}

interface PaginatedRooms {
    data: Room[];
    links: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    rooms: PaginatedRooms;
    filters: Record<string, any>;
    types: Record<string, string>;
    statuses: Record<string, string>;
    can: Record<string, boolean>;
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    type: props.filters?.type ?? 'all',
    status: props.filters?.status ?? 'all',
    min_capacity: props.filters?.min_capacity ?? '',
    sort_by: props.filters?.sort_by ?? 'room_number',
    sort_order: props.filters?.sort_order ?? 'asc',
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

/* =========================
   Methods
========================= */
const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 300);
};

const search = () => {
    router.get(route('rooms.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.type = 'all';
    searchForm.status = 'all';
    searchForm.min_capacity = '';
    search();
};

const getStatusConfig = (status: string) => {
    switch (status) {
        case 'available':
            return {
                class: 'border-green-500 text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/20',
                dotClass: 'bg-green-500'
            };
        case 'maintenance':
            return {
                class: 'border-orange-500 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/20',
                dotClass: 'bg-orange-500'
            };
        case 'unavailable':
            return {
                class: 'border-red-500 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/20',
                dotClass: 'bg-red-500'
            };
        default:
            return {
                class: 'border-gray-500 text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-950/20',
                dotClass: 'bg-gray-500'
            };
    }
};
</script>

<template>
    <Head title="Rooms" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between px-4">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">Library Rooms</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage and book library rooms</p>
                </div>
                <Link v-if="can.createRooms" :href="route('rooms.create')">
                    <Button>
                        <DoorOpen class="mr-2 h-4 w-4" />
                        Add Room
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="px-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="grid gap-4 md:grid-cols-4">
                            <!-- Search -->
                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-foreground">Search</label>
                                <Input
                                    v-model="searchForm.search"
                                    @input="debounceSearch"
                                    placeholder="Room name or number..."
                                />
                            </div>

                            <!-- Type Filter -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground">Room Type</label>
                                <Select v-model="searchForm.type" @update:model-value="search">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem v-for="(label, key) in types" :key="key" :value="key">
                                            {{ label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Min Capacity -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground">Min. Capacity</label>
                                <Input
                                    v-model="searchForm.min_capacity"
                                    @input="debounceSearch"
                                    type="number"
                                    min="1"
                                    placeholder="e.g., 10"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <Button @click="clearFilters" variant="outline">
                                Reset Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Status Tabs -->
            <div class="px-4">
                <Tabs v-model="searchForm.status" @update:model-value="search">
                    <TabsList>
                        <TabsTrigger value="all">All Rooms</TabsTrigger>
                        <TabsTrigger v-for="(label, key) in statuses" :key="key" :value="key">
                            {{ label }}
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <!-- Rooms Table -->
            <div class="px-4">
                <Card>
                    <CardContent class="p-0">
                        <div v-if="rooms.data.length === 0" class="py-12 text-center">
                            <DoorOpen class="mx-auto h-12 w-12 text-muted-foreground" />
                            <h3 class="mt-4 text-sm font-medium text-foreground">No rooms found</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Try adjusting your filters.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Room Number</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Capacity</TableHead>
                                        <TableHead>Floor</TableHead>
                                        <TableHead>Rate/Hour</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="room in rooms.data" :key="room.id">
                                        <!-- Room Number -->
                                        <TableCell>
                                            <code class="rounded bg-muted px-2 py-1 text-xs font-mono">
                                                {{ room.room_number }}
                                            </code>
                                        </TableCell>

                                        <!-- Name -->
                                        <TableCell>
                                            <div class="font-medium">{{ room.name }}</div>
                                            <div v-if="room.amenities && room.amenities.length" class="text-xs text-muted-foreground mt-1">
                                                {{ room.amenities.slice(0, 3).join(', ') }}
                                                <span v-if="room.amenities.length > 3">+{{ room.amenities.length - 3 }}</span>
                                            </div>
                                        </TableCell>

                                        <!-- Type -->
                                        <TableCell>
                                            <Badge variant="outline">
                                                {{ room.type_display }}
                                            </Badge>
                                        </TableCell>

                                        <!-- Capacity -->
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Users class="h-4 w-4 text-muted-foreground" />
                                                <span>{{ room.capacity }}</span>
                                            </div>
                                        </TableCell>

                                        <!-- Floor -->
                                        <TableCell>
                                            <div v-if="room.floor" class="flex items-center gap-2">
                                                <MapPin class="h-4 w-4 text-muted-foreground" />
                                                <span>{{ room.floor }}</span>
                                            </div>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </TableCell>

                                        <!-- Rate -->
                                        <TableCell>
                                            <span v-if="room.is_free" class="text-green-600 dark:text-green-400 font-medium">
                                                Free
                                            </span>
                                            <span v-else>${{ room.hourly_rate }}</span>
                                        </TableCell>

                                        <!-- Status -->
                                        <TableCell>
                                            <Badge variant="outline" :class="getStatusConfig(room.status).class">
                                                <span class="relative flex h-2 w-2 mr-2">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                                                        :class="getStatusConfig(room.status).dotClass"
                                                    ></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-2 w-2"
                                                        :class="getStatusConfig(room.status).dotClass"
                                                    ></span>
                                                </span>
                                                {{ room.status_display }}
                                            </Badge>
                                        </TableCell>

                                        <!-- Actions -->
                                        <TableCell class="text-right">
                                            <Link :href="route('rooms.show', room.id)">
                                                <Button variant="outline" size="sm">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div v-if="rooms.last_page > 1" class="mx-4 rounded-xl border border-border bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing page {{ rooms.current_page }} of {{ rooms.last_page }}
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="(link, index) in rooms.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80',
                                !link.url && 'cursor-not-allowed opacity-50'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>