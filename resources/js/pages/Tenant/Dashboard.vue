<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import SlackSettings from '@/components/SlackSettings.vue';

const page = usePage();

// `tenant` pulled from page.props
const tenant = computed(() => page.props.tenant);

// Build your breadcrumbs array
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: route('dashboard', { tenant: tenant.value }),
  },
];
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <SlackSettings />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <PlaceholderPattern />
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
          <PlaceholderPattern />
        </div>
      </div>
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <PlaceholderPattern />
      </div>
    </div>
  </AppLayout>
</template>