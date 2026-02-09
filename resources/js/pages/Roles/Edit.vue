<script setup lang="ts">
import { route } from "ziggy-js";
import { router, useForm, Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import FlashAlert from '@/components/FlashAlert.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { ShieldIcon } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

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
}

/* =========================
   Props
========================= */
const props = defineProps<{
    role: Role;
    allPermissions: Record<string, Permission[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles & Permissions',
        href: route('roles.index'),
    },
    {
        title: 'Edit Role',
        href: '#',
    },
];

/* =========================
   State
========================= */
const form = useForm({
    permissions: props.role.permissions.map(p => p.id),
});

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

const isPermissionChecked = (permissionId: number): boolean => {
    return form.permissions.includes(permissionId);
};

const togglePermission = (permissionId: number) => {
    const index = form.permissions.indexOf(permissionId);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionId);
    }
};

const selectAll = (permissions: Permission[]) => {
    permissions.forEach(permission => {
        if (!form.permissions.includes(permission.id)) {
            form.permissions.push(permission.id);
        }
    });
};

const deselectAll = (permissions: Permission[]) => {
    permissions.forEach(permission => {
        const index = form.permissions.indexOf(permission.id);
        if (index > -1) {
            form.permissions.splice(index, 1);
        }
    });
};

const submitForm = () => {
    form.put(route('roles.update', props.role.id), {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.visit(route('roles.index'));
};

const formatCategoryName = (category: string): string => {
    return category
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<template>
    <Head title="Edit Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4">

            <FlashAlert />

            <!-- Header -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold text-foreground">
                        Edit Role Permissions
                    </h1>
                    <Badge :variant="getRoleBadge(role.name).variant">
                        <ShieldIcon class="mr-1 h-3 w-3" />
                        {{ getRoleBadge(role.name).label }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Select permissions for this role
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="space-y-6">

                <!-- Permissions by Category -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <Card 
                        v-for="(permissions, category) in allPermissions" 
                        :key="category"
                    >
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-lg">
                                    {{ formatCategoryName(category) }}
                                </CardTitle>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="selectAll(permissions)"
                                    >
                                        Select All
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="deselectAll(permissions)"
                                    >
                                        Deselect All
                                    </Button>
                                </div>
                            </div>
                            <CardDescription>
                                {{ permissions.length }} permissions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div 
                                    v-for="permission in permissions" 
                                    :key="permission.id"
                                    class="flex items-center space-x-3"
                                >
                                    <Checkbox
                                        :id="`permission-${permission.id}`"
                                        :checked="isPermissionChecked(permission.id)"
                                        @update:checked="() => togglePermission(permission.id)"
                                    />
                                    <label
                                        :for="`permission-${permission.id}`"
                                        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 cursor-pointer"
                                    >
                                        {{ permission.name }}
                                    </label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Selected Count -->
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    Selected Permissions
                                </p>
                                <p class="text-2xl font-bold text-primary">
                                    {{ form.permissions.length }}
                                </p>
                            </div>
                            <ShieldIcon class="h-12 w-12 text-muted-foreground opacity-20" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end gap-3 rounded-xl border border-border bg-background p-6">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancel"
                        :disabled="form.processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save Permissions</span>
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>