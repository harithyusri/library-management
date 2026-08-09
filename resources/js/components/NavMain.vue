<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';

interface Props {
    items: NavItem[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Platform',
});
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>{{ title }}</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Collapsible menu (for items with subitems) -->
                <Collapsible v-if="item.items" as-child :default-open="item.isActive?.value" class="group/collapsible">
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton tooltip-text="{item.title}"
                                :class="item.isActive?.value
                                    ? 'bg-sidebar-accent border-l-2 border-[#c5a059] text-[#c5a059] [&>svg]:text-[#c5a059]'
                                    : ''">
                                <component :is="item.icon" v-if="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.items" :key="subItem.title">
                                    <SidebarMenuSubButton as-child
                                        :class="subItem.isActive?.value
                                            ? 'bg-sidebar-accent border-l-2 border-[#c5a059] text-[#c5a059]'
                                            : ''">
                                        <Link :href="subItem.href || '#'">
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <!-- Regular menu item (no subitems) -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton as-child tooltip-text="{item.title}"
                        :class="item.isActive?.value
                            ? 'bg-sidebar-accent border-l-2 border-[#c5a059] text-[#c5a059] [&>a>svg]:text-[#c5a059]'
                            : ''">
                        <Link :href="item.href || '#'">
                            <component :is="item.icon" v-if="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>