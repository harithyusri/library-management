<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-2 rounded-xl border hover:shadow-lg transition-shadow',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
