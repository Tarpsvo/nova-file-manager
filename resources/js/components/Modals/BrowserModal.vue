<script setup lang="ts">
import { Dialog as DialogModal, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { computed } from 'vue'
import Browser from '@/components/Browser.vue'
import useBrowserStore from '@/stores/browser'

defineEmits(['close'])

const store = useBrowserStore()

const dark = computed(() => store.dark)
const isBrowserOpen = computed(() => store.isBrowserOpen)
</script>

<template>
  <TransitionRoot :show="isBrowserOpen" as="template" class="nova-file-manager w-full">
    <DialogModal as="div" class="relative" @close="$emit('close')">
      <TransitionChild
        as="template"
        class="z-[60]"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-800/20 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div :class="['fixed z-[60] inset-0 overflow-y-auto w-full', { dark }]">
        <div class="flex items-start justify-center min-h-full">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative bg-transparent md:rounded-lg overflow-hidden shadow-xl transition-all w-full border border-gray-300 dark:border-gray-800 md:m-8 m-0"
            >
              <Browser />
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </DialogModal>
  </TransitionRoot>
</template>
