<!-- resources/js/pages/Responses/Kanban.vue -->
<template>
  <div class="kanban-container">
    <h1 class="kanban-title">Sell my vehicle Lead Board</h1>

    <div class="kanban-stats">
      <p>Total Responses: {{ totalResponses }}</p>
      <p>New Lead: {{ stagesCount.submitted }}</p>
      <p>Appraisal Completed: {{ stagesCount.in_review }}</p>
      <p>Lead Generated: {{ stagesCount.approved }}</p>
    </div>

    <!-- KANBAN COLUMNS -->
    <div class="kanban-board">
      <div
        v-for="stage in stages"
        :key="stage"
        class="kanban-column"
      >
        <h2 class="stage-header">{{ stage.replace('_', ' ').toUpperCase() }}</h2>
        <draggable
          v-model="blockLists[stage]"
          group="kanban"
          @change="handleDragChange($event, stage)"
          item-key="id"
          class="card-container"
          :component-data="{ tag: 'div', type: 'transition-group', name: 'fade' }"
        >
          <template #item="{ element }">
            <div class="card" :data-id="element.id" @click="showDetails(element)">
              <div class="card-header">
                <h3>{{ element.year }} {{ element.make }} {{ element.model }}</h3>
                <p class="card-vin">VIN: {{ element.vin }}</p>
              </div>
              <p><strong>Owner:</strong> {{ element.full_name }}</p>
              <div v-if="element.photos?.length" class="photos">
                <img
                  v-for="(photo, index) in element.photos.slice(0, 1)"
                  :key="index"
                  :src="'/storage/' + photo"
                  alt="Vehicle Photo"
                  class="photo"
                />
                <span v-if="element.photos.length > 1" class="more-photos">
                  +{{ element.photos.length - 1 }} more
                </span>
              </div>
            </div>
          </template>
        </draggable>
      </div>
    </div>

    <!-- REFACTORED: Use the separate modal component -->
    <FormResponseModal
      v-if="showModal"
      :show="showModal"
      :response="selectedBlock"
      @close="closeModal"
      @updated="handleUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import draggable from 'vuedraggable'
import { router, usePage } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import FormResponseModal from '@/components/FormResponseModal.vue'

// Props
const props = defineProps({
  stages: {
    type: Array,
    default: () => ['submitted', 'in_review', 'approved'],
  },
  blocks: {
    type: Object,
    default: () => ({}),
  },
})

// Reactive state
const blockLists = ref({ ...props.blocks })
const showModal = ref(false)
const selectedBlock = ref(null)

// Page & Tenant
const page = usePage()
const tenant = computed(() => page.props.tenant)

// Computed
const totalResponses = computed(() => Object.values(blockLists.value).flat().length)
const stagesCount = computed(() => {
  return {
    submitted: blockLists.value.submitted?.length || 0,
    in_review: blockLists.value.in_review?.length || 0,
    approved: blockLists.value.approved?.length || 0,
  }
})

// Watch for changes in blocks
watch(
  () => props.blocks,
  (newVal) => {
    blockLists.value = { ...newVal }
  },
  { deep: true }
)

// Drag & drop stage update
const handleDragChange = (event, stage) => {
  if (event.added) {
    const movedBlock = event.added.element
    router.post(
      route('responses.updateStage', { tenant: tenant.value }),
      { id: movedBlock.id, stage },
      {
        preserveScroll: true,
        onSuccess: () => console.log('Stage updated'),
        onError: (errors) => console.error('Stage update error', errors),
      }
    )
  }
}

function handleUpdated(updatedResponse) {
  // Update selectedBlock with the new data
  Object.assign(selectedBlock.value, updatedResponse);

  // Ensure the corresponding block in blockLists is updated
  for (const stage in blockLists.value) {
    const index = blockLists.value[stage].findIndex(block => block.id === updatedResponse.id);
    if (index !== -1) {
      Object.assign(blockLists.value[stage][index], updatedResponse);
      break;
    }
  }
}

// Show modal
function showDetails(block) {
  selectedBlock.value = block
  showModal.value = true
}

// Close modal
function closeModal() {
  showModal.value = false
  selectedBlock.value = null
}

// OPTIONAL: Reverb/Echo subscription
onMounted(() => {
  if (window.Echo) {
    window.Echo.channel('form-responses')
      .listen('.App\\Events\\FormResponseUpdated', (event) => {
        console.log('Broadcast event: ', event)
        // Option 1: reload blocks
        router.reload({ only: ['blocks'] })
      })
  }
})

onUnmounted(() => {
  if (window.Echo) {
    window.Echo.leave('form-responses')
  }
})
</script>

<style scoped>
.kanban-container {
  padding: 2rem;
  background: #f3f4f6;
  min-height: 100vh;
}
.kanban-title {
  font-size: 2rem;
  margin-bottom: 1.5rem;
  text-align: center;
  font-weight: 700;
}
.kanban-stats {
  display: flex;
  justify-content: space-around;
  margin-bottom: 1.5rem;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.kanban-board {
  display: flex;
  gap: 1.5rem;
  overflow-x: auto;
}
.kanban-column {
  flex: 0 0 300px;
  background: #f0f0f0;
  border-radius: 8px;
  padding: 1rem;
}
.stage-header {
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
  text-align: center;
}
.card-container {
  min-height: 100px;
}
.card {
  background: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 6px;
  padding: 1rem;
  margin: 0.75rem 0;
  cursor: pointer;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}
.card-header h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #1f2937;
}
.photos {
  margin-top: 0.5rem;
  display: flex;
  gap: 0.5rem;
}
.photo {
  max-width: 80px;
  height: auto;
  border-radius: 4px;
}
.more-photos {
  font-size: 0.8rem;
  color: #6b7280;
  margin-left: 0.5rem;
}
</style>

