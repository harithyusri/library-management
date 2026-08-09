<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-2 border-b border-border">
        <div class="space-y-1">
            <h1 class="font-serif text-4xl font-normal tracking-tight text-foreground">
                {{ title }}<span class="text-[color:var(--brass)] text-6xl leading-none">.</span>
            </h1>
            <p v-if="description" class="text-muted-foreground font-medium">{{ description }}</p>
        </div>
        <div v-if="$slots.default" class="flex items-center gap-3">
            <slot />
        </div>
    </div>
</template>
