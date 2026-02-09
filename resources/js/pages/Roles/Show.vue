<script setup lang="ts">
import { route } from "ziggy-js";
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ShieldIcon, EditIcon, UsersIcon, CheckCircleIcon } from 'lucide-vue-next';

/* =========================
   Types
========================= */
interface Permission {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
    users: User[];
    created_at: string;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    role: Role;
    can: Record<string, boolean>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles & Permissions',
        href: route('roles.index'),
    },
    {
        title: getRoleLabel(props.role.name),
        href: '#',
    },
];

/* =========================
   Methods
========================= */
function getRoleBadge(roleName: string) {
    switch (roleName) {
        case 'super-admin':
            return { variant: 'destructive' as const, label: 'Super Admin' };
        case 'admin':
            return { variant: 'default' as const, label: 'Admin' };
        case 'librarian':
            return { variant: 'secondary' as const, label: 'Librarian' };
        case 'member':
            return { variant: 'outline' as const, label: 'Member' };
        default:
            return { variant: 'outline' as const, label: roleName };
    }
}

function getRoleLabel(roleName: string): string {
    return getRoleBadge(roleName).label;
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

// Group permissions by category
const groupedPermissions = () => {
    const grouped: Record<string, Permission[]> = {};
    
    props.role.permissions.forEach(permission => {
        const parts = permission.name.split(' ');
        const category = parts.length >= 2 ? parts[1] : 'other';
        
        if (!grouped[category]) {
            grouped[category] = [];
        }
        
        grouped[category].push(permission);
    });
    
    return grouped;
};

function formatCategoryName(category: string): string {
    return category
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}
</script>

<template>
    <Head :title="`${getRoleLabel(role.name)} Role`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-foreground">
                            {{ getRoleLabel(role.name) }} Role
                        </h1>
                        <Badge :variant="getRoleBadge(role.name).variant">
                            <ShieldIcon class="mr-1 h-3 w-3" />
                            {{ getRoleBadge(role.name).label }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        View role details and permissions
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link :href="route('roles.index')">
                        <Button variant="outline">
                            Back to Roles
                        </Button>
                    </Link>
                    <Link :href="route('roles.edit', role.id)">
                        <Button>
                            <EditIcon class="mr-2 h-4 w-4" />
                            Edit Permissions
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Total Permissions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            Total Permissions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3">
                            <CheckCircleIcon class="h-8 w-8 text-primary" />
                            <p class="text-3xl font-bold text-foreground">
                                {{ role.permissions.length }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Users -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            Users with this Role
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3">
                            <UsersIcon class="h-8 w-8 text-primary" />
                            <p class="text-3xl font-bold text-foreground">
                                {{ role.users.length }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Created Date -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            Created
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-lg font-medium text-foreground">
                            {{ formatDate(role.created_at) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Permissions by Category -->
            <Card>
                <CardHeader>
                    <CardTitle>Permissions</CardTitle>
                    <CardDescription>
                        All permissions assigned to this role
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div 
                            v-for="(permissions, category) in groupedPermissions()" 
                            :key="category"
                            class="space-y-3"
                        >
                            <h3 class="font-semibold text-foreground">
                                {{ formatCategoryName(category) }}
                            </h3>
                            <div class="space-y-2">
                                <div 
                                    v-for="permission in permissions" 
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm text-muted-foreground"
                                >
                                    <CheckCircleIcon class="h-4 w-4 text-primary" />
                                    <span>{{ permission.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="role.permissions.length === 0" class="py-8 text-center">
                        <p class="text-sm text-muted-foreground">
                            No permissions assigned to this role.
                        </p>
                        <Link v-if="can.manageRoles" :href="route('roles.edit', role.id)">
                            <Button class="mt-4">
                                <EditIcon class="mr-2 h-4 w-4" />
                                Add Permissions
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Users with this Role -->
            <Card>
                <CardHeader>
                    <CardTitle>Users</CardTitle>
                    <CardDescription>
                        All users assigned to this role
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <!-- Empty State -->
                    <div v-if="role.users.length === 0" class="py-12 text-center">
                        <UsersIcon class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-sm font-medium text-foreground">
                            No users with this role
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            No users have been assigned to this role yet.
                        </p>
                    </div>

                    <!-- Users Table -->
                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in role.users" :key="user.id">
                                    <TableCell>
                                        <div class="font-medium">{{ user.name }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ user.email }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <!-- <Link :href="route('users.show', user.id)">
                                            <Button variant="ghost" size="sm">
                                                View
                                            </Button>
                                        </Link> -->
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppLayout>
</template>