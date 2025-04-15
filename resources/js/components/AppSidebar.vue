<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

// Access page props using Inertia's usePage composable
const page = usePage();

// Define mainNavItems as a computed property to conditionally include the dashboard link
const mainNavItems = computed(() => {
    const items: NavItem[] = [];
    if (page.props.tenant) {
        items.push({
            title: 'Dashboard',
            href: route('dashboard', { tenant: page.props.tenant }),
            icon: LayoutGrid,
        });
    }
     if (page.props.tenant) {  
        items.push({
      title: 'Kanban Board',
      href: route('responses.kanban', { tenant: page.props.tenant }),
      icon: LayoutGrid,
    });
    }
    return items;
});

// Footer navigation items remain static as they don't rely on routes
const footerNavItems: NavItem[] = [
    
];

// Compute the logo link href based on tenant availability
const logoHref = computed(() => 
    page.props.tenant ? route('dashboard', { tenant: page.props.tenant }) : route('home')
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="logoHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
