<script setup lang="ts">
import { route } from 'ziggy-js'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { type BreadcrumbItem } from '@/types'

const props = defineProps<{
    admin: any
    can: Record<string, boolean>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admins & Staff', href: route('admins.index') },
    { title: props.admin.name, href: route('admins.show', props.admin.id) },
]
</script>

<template>
    <Head title="Staff Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto space-y-6 p-4">

            <div>
                <h1 class="text-xl font-semibold">{{ admin.name }}</h1>
                <p class="text-sm text-muted-foreground">{{ admin.email }}</p>
            </div>

            <Card>
                <CardContent class="p-6 space-y-4">

                    <div class="flex items-center gap-2">
                        <Badge>{{ admin.roles[0]?.name }}</Badge>
                        <Badge variant="secondary">{{ admin.status }}</Badge>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-muted-foreground">Phone</p>
                            <p>{{ admin.phone || 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-muted-foreground">Created</p>
                            <p>{{ admin.created_at }}</p>
                        </div>
                    </div>

                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('admins.index')">
                    <Button variant="ghost">Back</Button>
                </Link>
                <Link v-if="can.editUsers" :href="route('admins.edit', admin.id)">
                    <Button>Edit</Button>
                </Link>
            </div>

        </div>
    </AppLayout>
</template>
