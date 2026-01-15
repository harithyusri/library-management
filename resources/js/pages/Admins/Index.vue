<script setup lang="ts">
import { route } from "ziggy-js";
import { reactive, ref } from 'vue';
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
import { UserPlusIcon, ShieldIcon, ShieldCheckIcon, TrashIcon, EditIcon } from 'lucide-vue-next';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admins & Staff',
        href: route('admins.index'),
    },
];

/* =========================
   Types
========================= */
interface Role {
    id: number;
    name: string;
}

interface Admin {
    id: number;
    name: string;
    email: string;
    phone?: string;
    status: 'active' | 'inactive' | 'suspended';
    roles: Role[];
    created_at: string;
}

interface PaginatedAdmins {
    data: Admin[];
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
    admins: PaginatedAdmins;
    filters: Record<string, any>;
    can: Record<string, boolean>;
}>();

/* =========================
   State
========================= */
const searchForm = reactive({
    search: props.filters?.search ?? '',
    role: props.filters?.role ?? 'all',
    status: props.filters?.status ?? 'all',
});

const adminToDelete = ref<Admin | null>(null);
const showDeleteDialog = ref(false);

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
    router.get(route('admins.index'), searchForm, {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    searchForm.search = '';
    searchForm.role = 'all';
    searchForm.status = 'all';
    search();
};

const confirmDelete = (admin: Admin) => {
    adminToDelete.value = admin;
    showDeleteDialog.value = true;
};

const deleteAdmin = () => {
    if (!adminToDelete.value) return;

    router.delete(route('admins.destroy', adminToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            adminToDelete.value = null;
        },
    });
};

const getRoleBadge = (roles: Role[]) => {
    if (!roles || roles.length === 0) {
        return { variant: 'outline' as const, label: 'No Role', icon: ShieldIcon };
    }

    const role = roles[0];
    
    if (role.name === 'super-admin') {
        return { variant: 'destructive' as const, label: 'Super Admin', icon: ShieldIcon };
    }
    if (role.name === 'admin') {
        return { variant: 'default' as const, label: 'Admin', icon: ShieldCheckIcon };
    }
    if (role.name === 'librarian') {
        return { variant: 'secondary' as const, label: 'Librarian', icon: ShieldCheckIcon };
    }
    
    return { variant: 'outline' as const, label: role.name, icon: ShieldIcon };
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return { variant: 'default' as const, label: 'Active' };
        case 'inactive':
            return { variant: 'secondary' as const, label: 'Inactive' };
        case 'suspended':
            return { variant: 'destructive' as const, label: 'Suspended' };
        default:
            return { variant: 'outline' as const, label: status };
    }
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Admins & Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Admins & Staff
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage administrators, librarians, and staff accounts
                    </p>
                </div>

                <Link v-if="can.createUsers" :href="route('admins.create')">
                    <Button>
                        <UserPlusIcon class="mr-2 h-4 w-4" />
                        Add Staff
                    </Button>
                </Link>
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
                                placeholder="Search by name or email..."
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
                                    <SelectItem value="super-admin">Super Admin</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                    <SelectItem value="librarian">Librarian</SelectItem>
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
                                    <SelectItem value="suspended">Suspended</SelectItem>
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

            <!-- Admins Table -->
            <Card>
                <CardContent class="p-0">
                    <!-- Empty State -->
                    <div v-if="admins.data.length === 0" class="py-12 text-center">
                        <ShieldIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-sm font-medium text-foreground">
                            No staff found
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            No staff members match your search criteria.
                        </p>
                        <div class="mt-4 flex justify-center gap-2">
                            <Button variant="outline" @click="clearFilters">
                                Clear Filters
                            </Button>
                            <Link v-if="can.createUsers" :href="route('admins.create')">
                                <Button>
                                    <UserPlusIcon class="mr-2 h-4 w-4" />
                                    Add Staff
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Added</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="admin in admins.data" :key="admin.id">
                                    <!-- Name -->
                                    <TableCell>
                                        <div class="font-medium">{{ admin.name }}</div>
                                    </TableCell>

                                    <!-- Email -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">{{ admin.email }}</div>
                                    </TableCell>

                                    <!-- Phone -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ admin.phone || 'N/A' }}
                                        </div>
                                    </TableCell>

                                    <!-- Role -->
                                    <TableCell>
                                        <Badge :variant="getRoleBadge(admin.roles).variant">
                                            <component 
                                                :is="getRoleBadge(admin.roles).icon" 
                                                class="mr-1 h-3 w-3" 
                                            />
                                            {{ getRoleBadge(admin.roles).label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(admin.status).variant">
                                            {{ getStatusBadge(admin.status).label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Added Date -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(admin.created_at) }}
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('admins.show', admin.id)">
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                            <Link v-if="can.editUsers" :href="route('admins.edit', admin.id)">
                                                <Button variant="ghost" size="sm">
                                                    <EditIcon class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="can.deleteUsers"
                                                variant="ghost"
                                                size="sm"
                                                @click="confirmDelete(admin)"
                                            >
                                                <TrashIcon class="h-4 w-4 text-destructive" />
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
            <div v-if="admins.last_page > 1" class="rounded-xl border border-border bg-background p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                        Showing page {{ admins.current_page }} of {{ admins.last_page }} ({{ admins.total }} total)
                    </div>
                    <div class="flex gap-2">
                        <Link
                            v-for="(link, index) in admins.links"
                            :key="index"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground hover:bg-muted/80',
                                !link.url && 'cursor-not-allowed opacity-50'
                            ]"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Dialog -->
            <AlertDialog v-model:open="showDeleteDialog">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the staff account <strong>{{ adminToDelete?.name }}</strong>.
                            This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="deleteAdmin" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </div>
    </AppLayout>
</template>