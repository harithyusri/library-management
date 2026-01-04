<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle } from 'lucide-vue-next';

interface FlashMessages {
  success?: string;
  error?: string;
  created_genre?: { id: number; name: string; };
}

const page = usePage<PageProps & { flash?: FlashMessages }>();
const visible = ref(false);

watch(
  () => page.props.flash,
  (flash: any) => {
    if (flash?.success || flash?.error) {
      visible.value = true;
      // Keep it visible for 5 seconds
      setTimeout(() => (visible.value = false), 5000);
    }
  },
  { immediate: true, deep: true }
);
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 -translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-4"
    >
      <div v-if="visible" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4 pointer-events-none">
        <div class="pointer-events-auto shadow-lg">

          <Alert v-if="page.props.flash?.success" class="border-green-500 bg-white dark:bg-slate-950">
            <CheckCircle2 class="h-4 w-4 text-green-500" />
            <AlertTitle class="text-green-500 font-bold">Success</AlertTitle>
            <AlertDescription>
              {{ page.props.flash.success }}
            </AlertDescription>
          </Alert>

          <Alert v-if="page.props.flash?.error" variant="destructive" class="shadow-lg">
            <XCircle class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription>
              {{ page.props.flash.error }}
            </AlertDescription>
          </Alert>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>
