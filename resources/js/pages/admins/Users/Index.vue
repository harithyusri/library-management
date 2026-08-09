<script setup lang="ts">
import { route } from 'ziggy-js';
import PageHeader from '@/components/PageHeader.vue';
import { reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
import { UsersIcon, ShieldCheckIcon, PowerIcon, RotateCcwIcon, Trash2Icon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'All Users', href: route('admin.users.index') },
];

/* =========================
   Types
========================= */
interface User {
    id: number;
    name: string;
    email: string;
    phone?: string;
    status: 'active' | 'inactive';
    deleted_at: string | null;
    created_at: string;
    roles: string[];
    loans_count: number;
    bookings_count: number;
}

interface PaginatedUsers {
    data: User[];
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
    users: PaginatedUsers;
    roles: string[];
    filters: Record<string, any>;
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    role:   props.filters?.role   ?? 'all',
    status: props.filters?.status ?? 'all',
});

const forceDeleteTarget = ref<User | null>(null);
const showForceDeleteDialog = ref(false);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

/* =========================
   Methods
========================= */
const debounceSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(search, 300);
};

const search = () => {
    router.get(route('admin.users.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.role   = 'all';
    searchForm.status = 'all';
    search();
};

const toggleStatus = (user: User) => {
    router.patch(route('admin.users.toggle-status', user.id), {}, { preserveScroll: true });
};

const restore = (user: User) => {
    router.patch(route('admin.users.restore', user.id), {}, { preserveScroll: true });
};

const confirmForceDelete = (user: User) => {
    forceDeleteTarget.value = user;
    showForceDeleteDialog.value = true;
};

const doForceDelete = () => {
    if (!forceDeleteTarget.value) return;
    router.delete(route('admin.users.force-delete', forceDeleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showForceDeleteDialog.value = false;
            forceDeleteTarget.value = null;
        },
    });
};

const roleBadgeVariant = (role: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (role) {
        case 'Super Admin':   return 'destructive';
        case 'Admin':         return 'default';
        case 'Librarian':     return 'secondary';
        default:              return 'outline';
    }
};

const statusBadge = (user: User) => {
    if (user.deleted_at) return { variant: 'destructive' as const, label: 'Deleted' };
    return user.status === 'active'
        ? { variant: 'default'   as const, label: 'Active' }
        : { variant: 'secondary' as const, label: 'Inactive' };
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
</script>

<template>
    <Head title="All Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-slate-100">
                <div class="space-y-1">
                    <PageHeader title="All Users " description="Super-admin view of every account — members, staff, and admins." />
                </div>

                <div class="flex items-center gap-2 text-sm text-muted-foreground bg-muted px-4 py-2 rounded-lg">
                    <ShieldCheckIcon class="h-4 w-4" />
                    Super Admin Only
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-6">
                    <div class="grid gap-4 md:grid-cols-5 items-end">

                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-foreground">Search</label>
                            <Input
                                v-model="searchForm.search"
                                @input="debounceSearch"
                                placeholder="Name, email, or phone..."
                            />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-foreground">Role</label>
                            <Select v-model="searchForm.role" @update:model-value="search">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Roles" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Roles</SelectItem>
                                    <SelectItem v-for="role in roles" :key="role" :value="role">
                                        {{ role }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
                            <Select v-model="searchForm.status" @update:model-value="search">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="deleted">Soft Deleted</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Button @click="clearFilters" variant="outline" class="w-full">
                                Clear
                            </Button>
                        </div>

                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card>
                <CardContent class="p-0">

                    <!-- Empty State -->
                    <div v-if="users.data.length === 0" class="py-16 text-center">
                        <UsersIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-sm font-medium text-foreground">No users found</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Try adjusting your filters.</p>
                        <Button variant="outline" class="mt-4" @click="clearFilters">Clear Filters</Button>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Role(s)</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Loans</TableHead>
                                    <TableHead>Bookings</TableHead>
                                    <TableHead>Joined</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="user in users.data"
                                    :key="user.id"
                                    :class="user.deleted_at ? 'opacity-50 bg-muted/30' : ''"
                                >
                                    <!-- User info -->
                                    <TableCell>
                                        <div class="font-semibold text-sm">{{ user.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                                        <div v-if="user.phone" class="text-xs text-muted-foreground">{{ user.phone }}</div>
                                    </TableCell>

                                    <!-- Roles -->
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                v-for="role in user.roles"
                                                :key="role"
                                                :variant="roleBadgeVariant(role)"
                                                class="text-xs"
                                            >
                                                {{ role }}
                                            </Badge>
                                            <span v-if="user.roles.length === 0" class="text-xs text-muted-foreground">—</span>
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="statusBadge(user).variant">
                                            {{ statusBadge(user).label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Loans -->
                                    <TableCell class="text-sm">{{ user.loans_count }}</TableCell>

                                    <!-- Bookings -->
                                    <TableCell class="text-sm">{{ user.bookings_count }}</TableCell>

                                    <!-- Joined -->
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(user.created_at) }}
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">

                                            <!-- View -->
                                            <Link :href="route('admin.users.show', user.id)">
                                                <Button variant="ghost" size="sm">View</Button>
                                            </Link>

                                            <!-- Restore (soft-deleted) -->
                                            <Button
                                                v-if="user.deleted_at"
                                                variant="ghost"
                                                size="sm"
                                                title="Restore user"
                                                @click="restore(user)"
                                            >
                                                <RotateCcwIcon class="h-4 w-4 text-green-600" />
                                            </Button>

                                            <!-- Toggle active/inactive (not deleted) -->
                                            <Button
                                                v-if="!user.deleted_at"
                                                variant="ghost"
                                                size="sm"
                                                :title="user.status === 'active' ? 'Deactivate user' : 'Activate user'"
                                                @click="toggleStatus(user)"
                                            >
                                                <PowerIcon
                                                    class="h-4 w-4"
                                                    :class="user.status === 'active' ? 'text-yellow-500' : 'text-green-600'"
                                                />
                                            </Button>

                                            <!-- Force Delete -->
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                title="Permanently delete"
                                                @click="confirmForceDelete(user)"
                                            >
                                                <Trash2Icon class="h-4 w-4 text-destructive" />
                                            </Button>

                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="rounded-xl border border-border bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Page {{ users.current_page }} of {{ users.last_page }} ({{ users.total }} total)
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground hover:bg-muted/80',
                                !link.url && 'cursor-not-allowed opacity-50'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- Force Delete Confirmation -->
            <AlertDialog v-model:open="showForceDeleteDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Permanently delete user?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will <strong>permanently</strong> delete
                            <strong>{{ forceDeleteTarget?.name }}</strong> and cannot be undone.
                            All associated data will be unlinked.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            @click="doForceDelete"
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Permanently Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </AppLayout>
</template>
