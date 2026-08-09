<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    icon: any;
    iconBg: string;
    iconColor?: string;
    value: number | string;
    label: string;
    sparklinePoints?: string;
    sparklineColor?: string;
    trendText?: string;
    trendVariant?: 'up' | 'warning';
}>();
</script>

<template>
    <Card class="relative overflow-hidden transition-shadow hover:shadow-md">
        <div class="absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, #795553, #cba72f)" />
        <CardContent class="pt-5 pb-4 px-5">
            <div class="flex items-start justify-between mb-4">
                <div class="h-10 w-10 rounded-md grid place-items-center border border-border" :style="{ background: iconBg }">
                    <component :is="icon" class="h-4 w-4" :style="{ color: iconColor ?? '#3d2b1f' }" />
                </div>
                <svg v-if="sparklinePoints" width="72" height="24" class="opacity-40 mt-1">
                    <polyline :points="sparklinePoints" fill="none" :stroke="sparklineColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span v-else-if="trendText" class="flex items-center gap-0.5 text-xs font-medium mt-1"
                    :class="trendVariant === 'warning' ? 'text-destructive' : 'text-[#735c00]'">
                    {{ trendText }}
                </span>
            </div>
            <div class="font-serif text-4xl leading-none">{{ value }}</div>
            <p class="text-sm text-muted-foreground mt-2">{{ label }}</p>
        </CardContent>
    </Card>
</template>