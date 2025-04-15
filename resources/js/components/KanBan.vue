<!-- resources/js/Components/Kanban.vue -->
<template>
    <div class="kanban-board">
      <kanban-board :stages="stages" :blocks="blocks" @update-block="updateBlock">
        <template v-for="stage in stages" #[stage] :key="stage">
          <h2 class="stage-title">{{ stage.replace('_', ' ').toUpperCase() }}</h2>
        </template>
  
        <template v-for="block in flatBlocks" #[block.id] :key="block.id">
          <div class="card">
            <p><strong>{{ block.year }} {{ block.make }} {{ block.model }}</strong></p>
            <p>VIN: {{ block.vin }}</p>
            <p>Owner: {{ block.full_name }}</p>
            <div v-if="block.photos?.length" class="photos">
              <img v-for="(photo, index) in block.photos" :key="index" :src="'/storage/' + photo" alt="Vehicle Photo" class="photo" />
            </div>
          </div>
        </template>
      </kanban-board>
    </div>
  </template>
  
  <script setup>
  import { computed } from 'vue';
  import { router } from '@inertiajs/vue3';
  import KanbanBoard from 'vue-kanban';
  
  // Define props
  const { stages, blocks } = defineProps(['stages', 'blocks']);
  
  // Compute flat blocks
  const flatBlocks = computed(() => Object.values(blocks).flat());
  
  const updateBlock = (id, stage) => {
    router.post('/responses/update-stage', { id, stage }, {
      preserveScroll: true,
      onSuccess: () => console.log('Stage updated'),
      onError: (errors) => console.error('Update errors:', errors)
    });
  };
  </script>
  
  <style scoped>
  .kanban-board {
    display: flex;
    gap: 1rem;
    padding: 1rem;
  }
  .stage-title {
    font-size: 1.5rem;
    text-align: center;
    background: #f0f0f0;
    padding: 0.5rem;
    border-radius: 4px;
  }
  .card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 1rem;
    margin: 0.5rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .photos {
    margin-top: 0.5rem;
  }
  .photo {
    max-width: 100px;
    margin: 0.5rem;
  }
  </style>