<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@inertiajs/core';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle } from 'lucide-vue-next';

// Define flash message type
interface FlashMessages {
  success?: string;
  error?: string;
}

const page = usePage<PageProps & { flash?: FlashMessages }>();

const visible = ref(false);

watch(
  () => page.props.flash,
  (flash: any) => {
    if (flash?.success || flash?.error) {
      visible.value = true;
      setTimeout(() => (visible.value = false), 5000);
    }
  },
  { immediate: true }
);
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 translate-y-[-10px]"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-[-10px]"
  >
    <div v-if="visible" class="mb-6">
      <Alert v-if="page.props.flash?.success">
        <CheckCircle2 class="h-4 w-4" />
        <AlertTitle>Success</AlertTitle>
        <AlertDescription>
          {{ page.props.flash.success }}
        </AlertDescription>
      </Alert>

      <Alert v-if="page.props.flash?.error" variant="destructive">
        <XCircle class="h-4 w-4" />
        <AlertTitle>Error</AlertTitle>
        <AlertDescription>
          {{ page.props.flash.error }}
        </AlertDescription>
      </Alert>
    </div>
  </Transition>
</template>
