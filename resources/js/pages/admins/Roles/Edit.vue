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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ShieldIcon } from 'lucide-vue-next';

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
        href: route('admin.roles.index'),
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
    name: props.role.name,
    type: (props.role as any).type ?? 'staff',
    permissions: Array.isArray(props.role.permissions)
        ? props.role.permissions.map((p: Permission) => p.id)
        : [],
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

const togglePermission = (permissionId: number, checked: boolean | 'indeterminate') => {
    if (checked === true) {
        if (!form.permissions.includes(permissionId)) {
            form.permissions = [...form.permissions, permissionId];
        }
    } else {
        form.permissions = form.permissions.filter(id => id !== permissionId);
    }
};

const selectAll = (permissions: Permission[]) => {
    const newIds = permissions.map(p => p.id).filter(id => !form.permissions.includes(id));
    form.permissions = [...form.permissions, ...newIds];
};

const deselectAll = (permissions: Permission[]) => {
    const removeIds = new Set(permissions.map(p => p.id));
    form.permissions = form.permissions.filter(id => !removeIds.has(id));
};

const submitForm = () => {
    form.put(route('admin.roles.update', props.role.id), {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.visit(route('admin.roles.index'));
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
        <div class="flex flex-1 flex-col gap-6 overflow-x-auto">

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

                <!-- Role Details -->
                <Card class="py-5">
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="grid w-full items-center gap-1.5">
                            <Label for="name">Role Name</Label>
                            <Input 
                                id="name" 
                                v-model="form.name" 
                                placeholder="e.g. Editor" 
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid w-full items-center gap-1.5">
                            <Label for="type">Role Type</Label>
                            <Select v-model="form.type">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="staff">Staff (Admin/Librarian)</SelectItem>
                                    <SelectItem value="member">Member</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-xs text-destructive mt-1">{{ form.errors.type }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Permissions by Category -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <Card class="py-3"
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
                                        :model-value="isPermissionChecked(permission.id)"
                                        @update:model-value="(checked) => togglePermission(permission.id, checked)"
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
                <div class="flex justify-end gap-3">
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