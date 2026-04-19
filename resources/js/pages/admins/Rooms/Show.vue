<script setup lang="ts">
import { route } from "ziggy-js";
import { Head, Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { DoorOpen, Pencil, Trash2, Users, Layers, Tag, CircleDollarSign, ImageOff, History, Shield, PlusIcon } from 'lucide-vue-next';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

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
}

const props = defineProps<{
    room: Room;
    types: Record<string, string>;
    amenitiesList: Record<string, string>;
    audits?: any[];
    can?: {
        editRooms: boolean;
        deleteRooms: boolean;
        bookRooms: boolean;
        viewAudits: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Rooms', href: route('admin.rooms.index') },
    { title: props.room.name, href: '#' },
];

const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    available:    { label: 'Available',         variant: 'default' },
    maintenance:  { label: 'Under Maintenance', variant: 'secondary' },
    unavailable:  { label: 'Unavailable',       variant: 'destructive' },
};

const deleteRoom = () => {
    if (confirm(`Are you sure you want to delete "${props.room.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.rooms.destroy', props.room.id));
    }
};
</script>

<template>
    <Head :title="room.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-8 space-y-6">
            <FlashAlert />

            <!-- Page Header -->
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground flex items-center gap-2">
                        <DoorOpen class="h-6 w-6 shrink-0" />
                        {{ room.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Room {{ room.room_number }}
                        <span v-if="room.floor !== null"> &middot; Floor {{ room.floor === 0 ? 'Ground' : room.floor }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('admin.rooms.edit', room.id)">
                        <Button size="sm" variant="outline" class="gap-1.5">
                            <Pencil class="h-4 w-4" />
                            Edit
                        </Button>
                    </Link>
                    <Button size="sm" variant="destructive" class="gap-1.5" @click="deleteRoom">
                        <Trash2 class="h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Room Image -->
            <div v-if="room.image_url" class="rounded-xl overflow-hidden border h-64 bg-muted">
                <img :src="room.image_url" :alt="room.name" class="w-full h-full object-cover" />
            </div>
            <div v-else class="rounded-xl border h-48 bg-muted flex flex-col items-center justify-center gap-2 text-muted-foreground">
                <ImageOff class="h-8 w-8" />
                <p class="text-sm">No image available</p>
            </div>

            <!-- Tabs -->
            <Tabs default-value="details" class="w-full">
                <TabsList class="grid w-full max-w-xs grid-cols-2">
                    <TabsTrigger value="details">Details</TabsTrigger>
                    <TabsTrigger v-if="can?.viewAudits" value="history">History</TabsTrigger>
                </TabsList>

                <TabsContent value="details" class="space-y-6 pt-6">
                    <Card>
                        <CardHeader class="pt-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Room Details</CardTitle>
                                    <CardDescription>Full information about this room.</CardDescription>
                                </div>
                                <Badge :variant="statusConfig[room.status].variant">
                                    {{ statusConfig[room.status].label }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6 py-6">

                            <!-- Key Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="rounded-lg border bg-muted/40 p-4 space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium uppercase tracking-wide">
                                        <Tag class="h-3.5 w-3.5" /> Type
                                    </div>
                                    <p class="text-sm font-semibold">{{ types[room.type] ?? room.type }}</p>
                                </div>

                                <div class="rounded-lg border bg-muted/40 p-4 space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium uppercase tracking-wide">
                                        <Users class="h-3.5 w-3.5" /> Capacity
                                    </div>
                                    <p class="text-sm font-semibold">{{ room.capacity }} {{ room.capacity === 1 ? 'person' : 'people' }}</p>
                                </div>

                                <div class="rounded-lg border bg-muted/40 p-4 space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium uppercase tracking-wide">
                                        <Layers class="h-3.5 w-3.5" /> Floor
                                    </div>
                                    <p class="text-sm font-semibold">
                                        {{ room.floor === null ? '—' : room.floor === 0 ? 'Ground' : room.floor }}
                                    </p>
                                </div>

                                <div class="rounded-lg border bg-muted/40 p-4 space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground font-medium uppercase tracking-wide">
                                        <CircleDollarSign class="h-3.5 w-3.5" /> Hourly Rate
                                    </div>
                                    <p class="text-sm font-semibold">RM {{ Number(room.hourly_rate).toFixed(2) }}</p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div v-if="room.description" class="space-y-1.5">
                                <p class="text-sm font-medium text-foreground">Description</p>
                                <p class="text-sm text-muted-foreground leading-relaxed whitespace-pre-line">{{ room.description }}</p>
                            </div>

                            <Separator v-if="room.amenities?.length > 0" />

                            <!-- Amenities -->
                            <div v-if="room.amenities?.length > 0" class="space-y-3">
                                <p class="text-sm font-medium text-foreground">Amenities</p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="amenity in room.amenities"
                                        :key="amenity"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        {{ amenitiesList[amenity] ?? amenity }}
                                    </Badge>
                                </div>
                            </div>

                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="history" class="pt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Room History</CardTitle>
                            <CardDescription>
                                Timeline of all administrative changes to this room.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="audits && audits.length > 0" class="relative space-y-8 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 before:to-transparent">
                                <div v-for="audit in audits" :key="audit.id" class="relative flex items-center justify-between md:justify-normal group">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-100 shadow shrink-0 z-10">
                                        <History v-if="audit.event === 'updated'" class="h-4 w-4 text-blue-500" />
                                        <PlusIcon v-else-if="audit.event === 'created'" class="h-4 w-4 text-green-500" />
                                        <Trash2 v-else-if="audit.event === 'deleted'" class="h-4 w-4 text-red-500" />
                                        <Shield v-else class="h-4 w-4 text-slate-500" />
                                    </div>
                                    <div class="ml-4 w-full p-4 rounded border border-slate-200 bg-white shadow-sm">
                                        <div class="flex items-center justify-between gap-4 mb-2">
                                            <div class="font-bold text-slate-900 capitalize flex items-center gap-2">
                                                {{ audit.event }}
                                                <Badge variant="outline" class="text-[10px] h-4 font-mono shadow-none">#{{ audit.id }}</Badge>
                                            </div>
                                            <time class="font-mono text-[10px] text-muted-foreground whitespace-nowrap">{{ new Date(audit.created_at).toLocaleString() }}</time>
                                        </div>
                                        <div class="text-slate-500 text-xs mb-3">
                                            Action by <span class="font-semibold text-slate-700">{{ audit.user?.name || 'System' }}</span>
                                        </div>
                                        
                                        <div v-if="audit.new_values && Object.keys(audit.new_values).length > 0" class="mt-3 overflow-hidden rounded-md border border-slate-100 bg-slate-50/30">
                                            <table class="w-full text-left text-[10px]">
                                                <thead class="bg-slate-100/50 text-slate-500">
                                                    <tr>
                                                        <th class="px-2 py-1 font-medium italic">Field</th>
                                                        <th class="px-2 py-1 font-medium italic">Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 italic">
                                                    <tr v-for="(value, key) in audit.new_values" :key="key" class="text-slate-600">
                                                        <td class="px-2 py-1 font-mono font-bold">{{ key }}</td>
                                                        <td class="px-2 py-1 truncate max-w-[200px]">{{ value }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-12 text-center text-muted-foreground">
                                No history found for this room.
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>