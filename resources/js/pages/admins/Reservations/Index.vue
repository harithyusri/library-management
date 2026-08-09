<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { route } from 'ziggy-js';

const props = defineProps<{
    reservations: { data: any[]; links: any[]; current_page: number; last_page: number; total: number };
    filters: { status?: string; search?: string };
}>();

const breadcrumbs = [{ title: 'Reservations', href: route('admin.reservations.index') }];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const applyFilters = debounce(() => {
    router.get(route('admin.reservations.index'), {
        ...(search.value && { search: search.value }),
        ...(status.value !== 'all' && { status: status.value }),
    }, { preserveState: true, replace: true });
}, 300);

watch([search, status], applyFilters);

const statusConfig: Record<string, string> = {
    pending:   'border-amber-500 text-amber-600 bg-amber-50',
    ready:     'border-emerald-500 text-emerald-600 bg-emerald-50',
    fulfilled: 'border-slate-300 text-slate-500 bg-slate-50',
    expired:   'border-red-400 text-red-500 bg-red-50',
    cancelled: 'border-slate-300 text-slate-400 bg-slate-50',
};

const readyModal = ref(false);
const readyReservation = ref<any | null>(null);
const readyForm = useForm({ book_copy_id: '' });

const openReadyModal = (res: any) => {
    readyReservation.value = res;
    readyForm.book_copy_id = '';
    readyModal.value = true;
};

const submitReady = () => {
    readyForm.patch(route('admin.reservations.ready', readyReservation.value.id), {
        onSuccess: () => { readyModal.value = false; },
    });
};

const cancelReservation = (id: number) => {
    if (!confirm('Cancel this reservation?')) return;
    router.patch(route('admin.reservations.cancel', id), {}, { preserveScroll: true });
};

const formatDate = (d: string | null) =>
    d ? new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—';
</script>

<template>
    <Head title="Reservations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <FlashAlert />

            <PageHeader title="Reservations" description="Manage member book reservation queue." />

            <!-- Filters -->
            <div class="grid gap-4 md:grid-cols-5 items-end">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-foreground">Search</label>
                    <Input v-model="search" placeholder="Book title or member name..." />
                </div>
            </div>

            <!-- Status Tabs + Table -->
            <div class="space-y-4">
                <Tabs v-model="status">
                    <TabsList>
                        <TabsTrigger value="all">All</TabsTrigger>
                        <TabsTrigger value="pending">Pending</TabsTrigger>
                        <TabsTrigger value="ready">Ready</TabsTrigger>
                        <TabsTrigger value="fulfilled">Fulfilled</TabsTrigger>
                        <TabsTrigger value="expired">Expired</TabsTrigger>
                        <TabsTrigger value="cancelled">Cancelled</TabsTrigger>
                    </TabsList>
                </Tabs>

                <CardContent class="p-0 border rounded-lg overflow-hidden bg-background">
                    <div v-if="reservations.data.length === 0" class="py-12 text-center">
                        <h3 class="mt-4 text-sm font-medium text-foreground">No reservations found</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Try adjusting your filters.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Book</TableHead>
                                    <TableHead>Member</TableHead>
                                    <TableHead>Reserved</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="res in reservations.data" :key="res.id">
                                    <TableCell>
                                        <div class="font-medium">{{ res.book?.title }}</div>
                                        <div class="text-xs text-muted-foreground">{{ res.book?.author_name }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="font-medium">{{ res.user?.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ res.user?.email }}</div>
                                    </TableCell>
                                    <TableCell>{{ formatDate(res.reserved_date) }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="statusConfig[res.status]" class="capitalize">
                                            {{ res.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button
                                                v-if="res.status === 'pending'"
                                                size="sm"
                                                @click="openReadyModal(res)"
                                            >
                                                <CheckCircle2 class="h-3.5 w-3.5 mr-1" /> Mark Ready
                                            </Button>
                                            <Button
                                                v-if="res.status === 'pending' || res.status === 'ready'"
                                                size="sm"
                                                variant="outline"
                                                class="text-destructive hover:text-destructive hover:bg-destructive/10 border-destructive/30"
                                                @click="cancelReservation(res.id)"
                                            >
                                                <XCircle class="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </div>

            <!-- Pagination -->
            <div v-if="reservations.last_page > 1" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing page {{ reservations.current_page }} of {{ reservations.last_page }}
                </div>
                <div class="flex gap-2">
                    <Link
                        v-for="(link, index) in reservations.links"
                        :key="index"
                        :href="link.url || '#'"
                        class="rounded-md px-3 py-2 text-sm"
                        :class="[
                            link.active ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground hover:bg-muted/80',
                            !link.url && 'cursor-not-allowed opacity-50 pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Mark Ready Dialog -->
        <Dialog v-model:open="readyModal">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Mark as Ready</DialogTitle>
                    <DialogDescription>
                        Assign a copy of <span class="font-medium text-foreground">{{ readyReservation?.book?.title }}</span> to this reservation.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2 py-4">
                    <label class="text-sm font-medium text-foreground">Book Copy ID</label>
                    <Input
                        v-model="readyForm.book_copy_id"
                        placeholder="Enter book copy ID..."
                        :class="{ 'border-destructive': readyForm.errors.book_copy_id }"
                    />
                    <p v-if="readyForm.errors.book_copy_id" class="text-xs text-destructive">{{ readyForm.errors.book_copy_id }}</p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="readyModal = false">Cancel</Button>
                    <Button
                        type="button"
                        :disabled="readyForm.processing || !readyForm.book_copy_id"
                        @click="submitReady"
                    >
                        Confirm
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
