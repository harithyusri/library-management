<script setup lang="ts">
import { route } from 'ziggy-js'
import { reactive } from 'vue'
import { router, Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import FlashAlert from '@/components/FlashAlert.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'
import { type BreadcrumbItem } from '@/types'

const props = defineProps<{
    staff: any
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admins & Staff', href: route('staffs.index') },
    { title: 'Edit Staff', href: route('staffs.edit', props.staff.id) },
]

const form = reactive({
    name: props.staff.name,
    email: props.staff.email,
    phone: props.staff.phone,
    role: props.staff.roles?.[0]?.name ?? 'staff',
})

const submit = () => {
    router.put(route('staffs.update', props.staff.id), form)
}
</script>

<template>
    <Head title="Edit Staff" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto space-y-6 p-4">

            <FlashAlert />

            <div>
                <h1 class="text-xl font-semibold">Edit Staff</h1>
                <p class="text-sm text-muted-foreground">
                    Update staff account information
                </p>
            </div>

            <Card>
                <CardContent class="p-6 space-y-6">

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <Label>Name</Label>
                            <Input v-model="form.name" />
                        </div>

                        <div>
                            <Label>Email</Label>
                            <Input type="email" v-model="form.email" />
                        </div>

                        <div>
                            <Label>Phone</Label>
                            <Input v-model="form.phone" />
                        </div>

                        <div>
                            <Label>Role</Label>
                            <Select v-model="form.role">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="super-admin">Super Admin</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                    <SelectItem value="librarian">Librarian</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link :href="route('staffs.index')">
                            <Button variant="ghost">Cancel</Button>
                        </Link>
                        <Button>
                            Save Changes
                        </Button>
                    </div>

                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
