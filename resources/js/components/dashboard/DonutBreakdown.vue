<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';

const props = defineProps<{
    title: string;
    segments: Array<{ label: string; value: number; color: string }>;
}>();

const total = computed(() => props.segments.reduce((a, s) => a + s.value, 0) || 1);
const R = 38;
const CIRC = 2 * Math.PI * R;

const donut = computed(() => {
    let offset = 0;
    return props.segments.map(s => {
        const dash = (s.value / total.value) * CIRC;
        const seg = { ...s, dash, offset };
        offset += dash;
        return seg;
    });
});
</script>

<template>
    <Card>
        <CardHeader class="pb-2 pt-4"><CardTitle class="font-serif text-lg">{{ title }}</CardTitle></CardHeader>
        <CardContent class="flex items-center gap-4 pb-5">
            <svg width="64" height="64" viewBox="0 0 96 96">
                <circle v-for="seg in donut" :key="seg.label" cx="48" cy="48" r="38" fill="none"
                    :stroke="seg.color" stroke-width="12"
                    :stroke-dasharray="`${seg.dash} ${CIRC - seg.dash}`"
                    :stroke-dashoffset="-seg.offset" transform="rotate(-90 48 48)" />
            </svg>
            <div class="flex flex-col gap-1 text-xs">
                <div v-for="seg in segments" :key="seg.label" class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full" :style="{ background: seg.color }" />
                    {{ seg.label }}: {{ seg.value }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>