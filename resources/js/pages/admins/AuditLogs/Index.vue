<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItemType } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { ref, watch } from 'vue';
import { Search, RotateCcw, User as UserIcon, Shield, Clock, Hash, Globe, Monitor } from 'lucide-vue-next';
import { debounce } from 'lodash';

const props = defineProps<{
    audits: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        user_id?: string;
        event?: string;
        auditable_type?: string;
    };
    events: string[];
    users: { id: number, name: string }[];
}>();

const breadcrumbs: BreadcrumbItemType[] = [
    {
        title: 'Audit Logs',
        href: '/audits',
    },
];

const selectedEvent = ref(props.filters.event || 'all');
const selectedUser = ref(props.filters.user_id || 'all');
const auditableType = ref(props.filters.auditable_type || '');

const handleFilter = debounce(() => {
    router.get('/audits', {
        event: selectedEvent.value === 'all' ? undefined : selectedEvent.value,
        user_id: selectedUser.value === 'all' ? undefined : selectedUser.value,
        auditable_type: auditableType.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch([selectedEvent, selectedUser, auditableType], () => {
    handleFilter();
});

const resetFilters = () => {
    selectedEvent.value = 'all';
    selectedUser.value = 'all';
    auditableType.value = '';
};

const getEventColor = (event: string) => {
    switch (event) {
        case 'created': return 'bg-green-500 hover:bg-green-600';
        case 'updated': return 'bg-blue-500 hover:bg-blue-600';
        case 'deleted': return 'bg-red-500 hover:bg-red-600';
        case 'login': return 'bg-indigo-500 hover:bg-indigo-600';
        case 'logout': return 'bg-gray-500 hover:bg-gray-600';
        case 'failed_login': return 'bg-orange-600 hover:bg-orange-700';
        default: return 'bg-slate-500 hover:bg-slate-600';
    }
};

const formatModelName = (type: string) => {
    if (!type) return 'N/A';
    const parts = type.split('\\');
    return parts[parts.length - 1];
};

const expandedRows = ref<number[]>([]);

const toggleRow = (id: number) => {
    if (expandedRows.value.includes(id)) {
        expandedRows.value = expandedRows.value.filter(rowId => rowId !== id);
    } else {
        expandedRows.value.push(id);
    }
};

const hasChanges = (audit: any) => {
    return (audit.old_values && Object.keys(audit.old_values).length > 0) || 
           (audit.new_values && Object.keys(audit.new_values).length > 0);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Audit Logs" />

        <div class="px-6 pt-2 pb-8 space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900">System Audit Logs <span class="text-indigo-600 text-6xl leading-none">.</span></h1>
                    <p class="text-slate-500 font-medium">Track all sensitive activities and history across the system.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Event</label>
                    <Select v-model="selectedEvent">
                        <SelectTrigger>
                            <SelectValue placeholder="All Events" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Events</SelectItem>
                            <SelectItem v-for="event in events" :key="event" :value="event">
                                {{ event.charAt(0).toUpperCase() + event.slice(1) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">User</label>
                    <Select v-model="selectedUser">
                        <SelectTrigger>
                            <SelectValue placeholder="All Users" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Users</SelectItem>
                            <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                {{ user.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Resource Type</label>
                    <div class="relative">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input 
                            v-model="auditableType" 
                            placeholder="e.g. Loan, Book..." 
                            class="pl-9"
                        />
                    </div>
                </div>

                <Button @click="resetFilters" class="gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                    <RotateCcw class="h-4 w-4" />
                    Reset Filters
                </Button>
            </div>

            <Card class="pt-4">
                <CardHeader>
                    <CardTitle>Activity History</CardTitle>
                    <CardDescription>
                        Displaying {{ audits.total }} activities found.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-[80px]">ID</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Resource</TableHead>
                                    <TableHead>Time</TableHead>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-for="audit in audits.data" :key="audit.id">
                                    <TableRow class="cursor-pointer hover:bg-muted/50" @click="toggleRow(audit.id)">
                                        <TableCell class="font-mono text-xs">#{{ audit.id }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="bg-primary/10 p-1.5 rounded-full">
                                                    <UserIcon class="h-3.5 w-3.5 text-primary" />
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-sm">{{ audit.user?.name || 'System / Guest' }}</span>
                                                    <span class="text-[10px] text-muted-foreground">{{ audit.user?.email || 'automated' }}</span>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="getEventColor(audit.event) + ' text-white border-0 shadow-none capitalize'">
                                                {{ audit.event }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1.5 font-medium">
                                                <Shield class="h-3.5 w-3.5 text-muted-foreground" />
                                                <span>{{ formatModelName(audit.auditable_type) }}</span>
                                                <span class="text-muted-foreground text-xs">#{{ audit.auditable_id }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                                <Clock class="h-3.5 w-3.5" />
                                                <span class="text-xs">{{ new Date(audit.created_at).toLocaleString() }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                                <Globe class="h-3.5 w-3.5" />
                                                <span class="text-xs">{{ audit.ip_address }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="sm" @click.stop="toggleRow(audit.id)">
                                                {{ expandedRows.includes(audit.id) ? 'Hide' : 'View Changes' }}
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    
                                    <!-- Expandable Details Row -->
                                    <TableRow v-if="expandedRows.includes(audit.id)" class="bg-muted/50 border-t-0 shadow-inner">
                                        <TableCell colspan="7" class="p-0">
                                            <div class="p-6 space-y-6">
                                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                    <div class="space-y-3">
                                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-2">
                                                            <div class="h-1.5 w-1.5 rounded-full bg-red-400"></div> Before
                                                        </h4>
                                                        <div class="bg-card rounded-lg border p-4 text-xs font-mono overflow-auto max-h-[300px]">
                                                            <div v-if="!audit.old_values || Object.keys(audit.old_values).length === 0" class="text-muted-foreground italic">
                                                                No previous data available.
                                                            </div>
                                                            <pre v-else>{{ JSON.stringify(audit.old_values, null, 2) }}</pre>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-3">
                                                        <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-2">
                                                            <div class="h-1.5 w-1.5 rounded-full bg-green-400"></div> After
                                                        </h4>
                                                        <div class="bg-card rounded-lg border p-4 text-xs font-mono overflow-auto max-h-[300px]">
                                                            <div v-if="!audit.new_values || Object.keys(audit.new_values).length === 0" class="text-muted-foreground italic">
                                                                No new data changed.
                                                            </div>
                                                            <pre v-else>{{ JSON.stringify(audit.new_values, null, 2) }}</pre>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center gap-4 text-[10px] text-muted-foreground pt-4 border-t">
                                                    <div class="flex items-center gap-1">
                                                        <Monitor class="h-3 w-3" />
                                                        <span>{{ audit.user_agent }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                
                                <TableRow v-if="audits.data.length === 0">
                                    <TableCell colspan="7" class="h-32 text-center text-muted-foreground">
                                        No logs found matching your criteria.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Simple Pagination -->
                    <div class="flex items-center justify-between space-x-2 py-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ audits.data.length }} of {{ audits.total }} logs
                        </div>
                        <div class="space-x-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="audits.current_page === 1"
                                @click="router.get(audits.links[0].url)"
                            >
                                Previous
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="audits.current_page === audits.last_page"
                                @click="router.get(audits.links[audits.links.length-1].url)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
