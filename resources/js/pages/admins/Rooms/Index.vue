<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive, watch } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import {
    Eye,
    Users,
    DoorOpen,
    Layers,
    Search,
    RotateCcw,
    Plus,
    CircleDollarSign,
    Image as ImageIcon,
} from 'lucide-vue-next';

interface Room {
    id: number;
    name: string;
    room_number: string;
    type: string;
    capacity: number;
    description: string | null;
    amenities: string[];
    floor: number | null;
    status: 'available' | 'maintenance' | 'unavailable';
    hourly_rate: number;
    image_url: string | null;
    type_display: string;
    status_display: string;
}

interface PaginatedRooms {
    data: Room[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    rooms: PaginatedRooms;
    filters: Record<string, any>;
    types: Record<string, string>;
    statuses: Record<string, string>;
    amenitiesList: Record<string, string>;
    can: Record<string, boolean>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rooms', href: route('admin.rooms.index') },
];

const searchForm = reactive({
    search: props.filters?.search ?? '',
    type: props.filters?.type ?? 'all',
    status: props.filters?.status ?? 'all',
    min_capacity: props.filters?.min_capacity ?? '',
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const search = () => {
    router.get(route('admin.rooms.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

watch(() => [searchForm.type, searchForm.status], () => {
    search();
});

const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        search();
    }, 400);
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.type = 'all';
    searchForm.status = 'all';
    searchForm.min_capacity = '';
    search();
};

const statusConfig: Record<string, { variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    available:    { variant: 'default' },
    maintenance:  { variant: 'secondary' },
    unavailable:  { variant: 'destructive' },
};
</script>

<template>
    <Head title="Rooms" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 pt-2 pb-8 space-y-6">
            <FlashAlert />

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">Room Management <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Manage library rooms, study areas, and facility equipment.</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link v-if="can.createRooms" :href="route('admin.rooms.create')" class="contents">
                        <Button class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-bold shadow-lg shadow-indigo-100 dark:shadow-none flex items-center gap-2">
                            <Plus class="h-5 w-5" />
                            Add New Room
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Enhanced Filters -->
            <Card class="border-none shadow-sm bg-muted/30">
                <CardContent class="p-4 md:p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <!-- Search term -->
                        <div class="md:col-span-5 space-y-2">
                            <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Search Rooms</Label>
                            <div class="relative">
                                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchForm.search"
                                    placeholder="Search by name, number, or description..."
                                    class="pl-9 bg-background"
                                    @input="debounceSearch"
                                />
                            </div>
                        </div>

                        <!-- Type Filter -->
                        <div class="md:col-span-3 space-y-2">
                            <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Room Type</Label>
                            <Select v-model="searchForm.type">
                                <SelectTrigger class="bg-background">
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

                        <!-- Capacity Filter -->
                        <div class="md:col-span-2 space-y-2">
                            <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wider">Min. Capacity</Label>
                            <div class="relative">
                                <Users class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchForm.min_capacity"
                                    type="number"
                                    placeholder="Min pax"
                                    class="pl-9 bg-background"
                                    @input="debounceSearch"
                                />
                            </div>
                        </div>

                        <!-- Reset button -->
                        <div class="md:col-span-2">
                            <Button
                                variant="outline"
                                class="w-full gap-2 bg-background border-dashed"
                                @click="clearFilters"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Reset
                            </Button>
                        </div>
                    </div>

                    <Separator class="bg-border/50" />

                    <!-- Status Tabs -->
                    <Tabs v-model="searchForm.status" class="w-full">
                        <TabsList class="bg-background/50 border h-10 p-1">
                            <TabsTrigger value="all" class="px-6">All Statuses</TabsTrigger>
                            <TabsTrigger v-for="(label, key) in statuses" :key="key" :value="key" class="px-6">
                                {{ label }}
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </CardContent>
            </Card>

            <!-- Table Card -->
            <Card class="overflow-hidden border-none shadow-sm">
                <CardContent class="p-0">
                    <div v-if="rooms.data.length === 0" class="flex flex-col items-center justify-center py-24 text-center px-4">
                        <div class="bg-muted rounded-full p-4 mb-4">
                            <DoorOpen class="h-8 w-8 text-muted-foreground/60" />
                        </div>
                        <h3 class="text-lg font-medium">No rooms found</h3>
                        <p class="text-sm text-muted-foreground max-w-xs mt-1">
                            We couldn't find any rooms matching your current filters. Try resetting or adjusting your search.
                        </p>
                        <Button variant="outline" class="mt-6" @click="clearFilters">Clear all filters</Button>
                    </div>

                    <Table v-else>
                        <TableHeader class="bg-muted/50">
                            <TableRow>
                                <TableHead class="pl-6 w-16 px-4 py-3">Image</TableHead>
                                <TableHead class="font-medium">Room Info</TableHead>
                                <TableHead>Capacity & Floor</TableHead>
                                <TableHead>Hourly Rate</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right pr-6">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="room in rooms.data" :key="room.id" class="group transition-colors">
                                <!-- Image Column -->
                                <TableCell class="pl-6">
                                    <div class="h-10 w-12 rounded overflow-hidden bg-muted border">
                                        <img
                                            v-if="room.image_url"
                                            :src="room.image_url"
                                            alt=""
                                            class="h-full w-full object-cover transition-transform group-hover:scale-110"
                                        />
                                        <div v-else class="h-full w-full flex items-center justify-center text-muted-foreground/40">
                                            <ImageIcon class="h-4 w-4" />
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Room Info -->
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground group-hover:text-primary transition-colors cursor-pointer" @click="router.get(route('admin.rooms.show', room.id))">
                                            {{ room.name }}
                                        </span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Badge variant="outline" class="text-[10px] uppercase font-bold tracking-tight h-4 px-1 leading-none rounded-sm">
                                                {{ room.room_number }}
                                            </Badge>
                                            <span class="text-xs text-muted-foreground border-l pl-2 leading-none h-3 inline-flex items-center">
                                                {{ room.type_display }}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Capacity & Floor -->
                                <TableCell>
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-2 text-sm text-foreground">
                                            <Users class="h-3.5 w-3.5 text-muted-foreground" />
                                            {{ room.capacity }} pax
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <Layers class="h-3.5 w-3.5" />
                                            {{ room.floor === null ? 'N/A' : room.floor === 0 ? 'Ground' : `Level ${room.floor}` }}
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Hourly Rate -->
                                <TableCell>
                                    <div class="flex items-center gap-1.5 font-semibold text-foreground">
                                        <CircleDollarSign class="h-4 w-4 text-primary" />
                                        RM {{ Number(room.hourly_rate).toFixed(2) }}
                                    </div>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge :variant="statusConfig[room.status].variant" class="rounded-full px-2.5 font-medium">
                                        {{ room.status_display }}
                                    </Badge>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell class="text-right pr-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="route('admin.rooms.show', room.id)">
                                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-full">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Better Pagination -->
            <div v-if="rooms.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                <p class="text-sm text-muted-foreground">
                    Showing <span class="font-medium text-foreground">{{ rooms.data.length }}</span>
                    of <span class="font-medium text-foreground">{{ rooms.total }}</span> rooms
                </p>
                <div class="flex items-center gap-1 bg-background border rounded-lg p-1">
                    <Link
                        v-for="(link, index) in rooms.links"
                        :key="index"
                        :href="link.url || '#'"
                        class="h-8 min-w-[32px] px-2 flex items-center justify-center text-sm rounded-md transition-all"
                        :class="[
                            link.active
                                ? 'bg-primary text-primary-foreground font-semibold shadow-sm'
                                : link.url
                                    ? 'hover:bg-muted text-muted-foreground hover:text-foreground'
                                    : 'text-muted-foreground/30 pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>