<script setup lang="ts">
import { CloudIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline'
import { ApiError, Entity } from '__types__'
import { computed, onMounted, ref, watch } from 'vue'
import FieldCard from '@/components/Cards/FieldCard.vue'
import Button from '@/components/Elements/Button.vue'
import BrowserModal from '@/components/Modals/BrowserModal.vue'
import ConfirmModal from '@/components/Modals/ConfirmModal.vue'
import PreviewModal from '@/components/Modals/PreviewModal.vue'
import { client } from '@/helpers/client'
import { useClipboard } from '@/hooks'
import useBrowserStore from '@/stores/browser'

interface Props {
  field: any
  index: number
  resourceName: string
  resourceId: string | number
  resource?: any
}

const props = defineProps<Props>()

const selected = ref(undefined as Entity | undefined)
const value = ref<Entity[]>(props.field.value ?? [])
const pending = ref<Entity[] | undefined>()
const displayModal = ref(false)
const saveModalName = `nova-file-manager-save-${props.field.attribute}`

const store = useBrowserStore()
const { copy: clipboardCopy } = useClipboard()

const dark = computed(() => store.dark)
const preview = computed(() => store.preview)
const canEdit = computed(
  () => props.field.allowEditOnDetail && !props.field.readonly && props.resource?.authorizedToUpdate,
)

const toPayload = (files: Entity[]) => files.map(({ path, disk }) => ({ path, disk }))

const copy = (file: Entity) => {
  selected.value = file
  clipboardCopy(file.url)

  setTimeout(() => {
    selected.value = undefined
  }, 1000)
}

const prepare = () =>
  store.prepareTool({
    singleDisk: props.field.singleDisk,
    permissions: props.field.permissions,
    tour: props.field.tour,
    usePintura: props.field.usePintura || false,
    pinturaOptions: props.field.pinturaOptions || {},
    cropperOptions: props.field.cropperOptions || {},
  })

const openBrowserModal = () => {
  displayModal.value = true
  pending.value = undefined

  store.openBrowser({
    initialFiles: value.value,
    multiple: props.field.multiple ?? false,
    acceptTypes: props.field.acceptTypes ?? null,
    initialPath: props.field.initialPath ?? null,
    initialView: props.field.initialView ?? null,
    limit: props.field.limit ?? null,
    wrapper: props.field.wrapper ?? null,
    resource: props.resourceName,
    resourceId: props.resourceId,
    attribute: props.field.attribute,
    singleDisk: props.field.singleDisk ?? false,
    permissions: props.field.permissions,
    flexibleGroup: [],
    callback: (selection: Entity[]) => (pending.value = selection),
    usePintura: props.field.usePintura ?? false,
    pinturaOptions: props.field.pinturaOptions ?? {},
    cropperOptions: props.field.cropperOptions ?? {},
    paginationOptions: props.field.paginationOptions ?? undefined,
    autoSelect: props.field.autoSelect ?? true,
  })
}

// Esc / outside click: keep the selection so the user can still choose to save it
const closeBrowserModal = () => {
  pending.value = store.selection
  store.closeBrowser()
}

watch(
  () => store.isBrowserOpen,
  (isOpen, wasOpen) => {
    if (isOpen || !wasOpen || !displayModal.value) {
      return
    }

    displayModal.value = false
    prepare()

    if (pending.value && JSON.stringify(toPayload(pending.value)) !== JSON.stringify(toPayload(value.value))) {
      store.openModal({ name: saveModalName })
    }
  },
)

const save = async () => {
  const files = pending.value ?? []
  const attribute = props.field.attribute
  const data: Record<string, any> = { resourceId: props.resourceId, attribute }

  // same as FormField.fill: an empty selection sends nothing
  if (files.length) {
    data[attribute] = JSON.stringify(toPayload(files))
  }

  try {
    const response = await client().post(`/nova-vendor/nova-file-manager/field/update/${props.resourceName}`, data)

    value.value = files
    window.Nova.success(response.data.message)
    store.closeModal({ name: saveModalName })
  } catch (error: unknown) {
    const bag = (error as ApiError).response?.data

    window.Nova.error(bag?.message || 'An error occurred')
    store.setError({ attribute: 'saveChanges', bag })
  }
}

onMounted(() => {
  store.syncDarkMode()
  prepare()
})
</script>

<template>
  <PanelItem :field="field" :index="index">
    <template v-if="value.length || canEdit" v-slot:value>
      <div class="nova-file-manager">
        <div :class="{ dark }">
          <ul v-if="value.length" class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-2 w-full" role="group">
            <template v-for="file in value" :key="file.id">
              <FieldCard :field="field" :file="file" :attribute="field.attribute" :detail="true" :on-copy="copy" />

              <PreviewModal
                :file="file"
                v-if="!displayModal && !!preview && preview.id === file.id"
                :read-only="true"
              />
            </template>
          </ul>

          <div v-if="canEdit" class="flex flex-row gap-2">
            <button
              class="relative flex flex-row shrink-0 items-center px-4 py-2 rounded-md border border-gray-300 dark:hover:border-blue-500 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 focus:z-10 focus:outline-none"
              type="button"
              @click="openBrowserModal"
            >
              <CloudIcon aria-hidden="true" class="-ml-1 mr-2 h-5 w-5 text-gray-400 dark:text-gray-200" />
              {{ __('NovaFileManager.openBrowser') }}
            </button>
          </div>
        </div>
      </div>

      <template v-if="canEdit">
        <BrowserModal v-if="displayModal" @close="closeBrowserModal" />

        <ConfirmModal
          :content="__('NovaFileManager.saveChangesContent')"
          :icon="QuestionMarkCircleIcon"
          :name="saveModalName"
          :title="__('NovaFileManager.saveChangesTitle')"
          variant="primary"
          attribute="saveChanges"
        >
          <template v-slot:confirmButton>
            <Button class="w-full sm:w-auto" type="button" variant="primary" @click="save">
              {{ __('Yes') }}
            </Button>
          </template>
          <template v-slot:cancelButton="{ close }">
            <Button class="w-full sm:w-auto" type="button" variant="secondary" @click="close">
              {{ __('No') }}
            </Button>
          </template>
        </ConfirmModal>
      </template>
    </template>
  </PanelItem>
</template>
