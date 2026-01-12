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
}

const props = defineProps<Props>();
</script>

<template>
    <SidebarGroup>
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Collapsible menu (for items with subitems) -->
                <Collapsible v-if="item.items" as-child :default-open="item.isActive?.value" class="group/collapsible">
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton tooltip-text="{item.title}"
                                :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground': item.isActive?.value }">
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
                                        :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground': subItem.isActive?.value }">
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
                        :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground': item.isActive?.value }">
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