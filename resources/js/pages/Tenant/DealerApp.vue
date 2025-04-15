<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import KanBan from '@/components/KanBan.vue';
import KanBanPage from '@/pages/Responses/Kanban.vue';


interface Props {
  stages: string[]
  blocks: Record<string, any[]>
}
const page = usePage();

const props = withDefaults(defineProps<Props>(), {
  stages: () => ['submitted', 'in_review', 'approved'],
  blocks: () => ({}),
})

// `tenant` pulled from page.props
const tenant = computed(() => page.props.tenant);

// Build your breadcrumbs array
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Lead Process Tracker',
    href: route('responses.kanban', { tenant: tenant.value }),
  },
];
</script>

<template>
  <Head title="Lead Process Tracker" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <KanBanPage :stages="props.stages" :blocks="props.blocks" />
  </AppLayout>
</template>