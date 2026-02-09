<script setup lang="ts">
import { route } from "ziggy-js";
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ShieldIcon, EditIcon, UsersIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles & Permissions',
        href: route('roles.index'),
    },
];

/* =========================
   Types
========================= */
interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
    users_count: number;
    created_at: string;
}

/* =========================
   Props
========================= */
const props = defineProps<{
    roles: Role[];
    can: Record<string, boolean>;
}>();

/* =========================
   Methods
========================= */
const getRoleBadge = (roleName: string) => {
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
    <Head title="Roles & Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Roles & Permissions
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage roles and their permissions
                    </p>
                </div>
                <Link :href="route('roles.create')">
                    <Button>
                        Create Role
                    </Button>
                </Link>
            </div>

            <!-- <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="role in roles" :key="role.id" class="relative overflow-hidden">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <Badge :variant="getRoleBadge(role.name).variant">
                                <ShieldIcon class="mr-1 h-3 w-3" />
                                {{ getRoleBadge(role.name).label }}
                            </Badge>
                            <Link 
                                v-if="can.manageRoles" 
                                :href="route('roles.edit', role.id)"
                            >
                                <Button variant="ghost" size="sm">
                                    <EditIcon class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <UsersIcon class="h-4 w-4" />
                            <span>{{ role.users_count }} {{ role.users_count === 1 ? 'user' : 'users' }}</span>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-foreground">
                                {{ role.permissions.length }} Permissions
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <Badge 
                                    v-for="permission in role.permissions.slice(0, 3)" 
                                    :key="permission.id"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ permission.name }}
                                </Badge>
                                <Badge 
                                    v-if="role.permissions.length > 3"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    +{{ role.permissions.length - 3 }} more
                                </Badge>
                            </div>
                        </div>

                        <div class="pt-2">
                            <Link :href="route('roles.show', role.id)">
                                <Button variant="outline" size="sm" class="w-full">
                                    View Details
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div> -->

            <!-- Detailed Table -->
            <Card>
                <CardHeader>
                    <CardTitle>All Roles</CardTitle>
                    <CardDescription>
                        Complete list of roles with their permissions
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Users</TableHead>
                                    <TableHead>Permissions</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="role in roles" :key="role.id">
                                    <!-- Role -->
                                    <TableCell>
                                        <Badge :variant="getRoleBadge(role.name).variant">
                                            <ShieldIcon class="mr-1 h-3 w-3" />
                                            {{ getRoleBadge(role.name).label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Users Count -->
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <UsersIcon class="h-4 w-4 text-muted-foreground" />
                                            <span class="font-medium">{{ role.users_count }}</span>
                                        </div>
                                    </TableCell>

                                    <!-- Permissions -->
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge 
                                                v-for="permission in role.permissions.slice(0, 5)" 
                                                :key="permission.id"
                                                variant="outline"
                                                class="text-xs"
                                            >
                                                {{ permission.name }}
                                            </Badge>
                                            <Badge 
                                                v-if="role.permissions.length > 5"
                                                variant="outline"
                                                class="text-xs"
                                            >
                                                +{{ role.permissions.length - 5 }}
                                            </Badge>
                                        </div>
                                    </TableCell>

                                    <!-- Created -->
                                    <TableCell>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(role.created_at) }}
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('roles.show', role.id)">
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                            <Link 
                                                v-if="can.manageRoles" 
                                                :href="route('roles.edit', role.id)"
                                            >
                                                <Button variant="ghost" size="sm">
                                                    <EditIcon class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </div>
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