<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import ChatWidget from '@/components/ChatWidget.vue';
import MemberSidebar from '@/components/MemberSidebar.vue';
import type { BreadcrumbItemType } from '@/types';

withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <MemberSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden bg-slate-50/50">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="px-10 py-4">
                <slot />
            </div>
        </AppContent>
    </AppShell>
    <ChatWidget />
</template>
