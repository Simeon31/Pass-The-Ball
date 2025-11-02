<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Users, Search, Plus } from 'lucide-vue-next';
import type { Group, PaginatedData } from '@/types';

interface Props {
    groups: PaginatedData<Group>;
    filters: {
        search?: string;
        filter?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const activeFilter = ref(props.filters.filter || 'all');

const handleSearch = () => {
    router.get(
        '/groups',
        {
            search: searchQuery.value,
            filter: activeFilter.value !== 'all' ? activeFilter.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const setFilter = (filter: string) => {
    activeFilter.value = filter;
    handleSearch();
};
</script>

<template>
    <AppLayout>

        <Head title="Discover Groups" />

        <div class="container mx-auto h-full overflow-auto px-4 py-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Discover Groups</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Find and join groups that match your interests
                    </p>
                </div>
                <Link href="/groups/create">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Create Group
                </Button>
                </Link>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input v-model="searchQuery" type="text" placeholder="Search groups..." class="pl-10"
                        @keyup.enter="handleSearch" />
                </div>
                <div class="flex gap-2">
                    <Button :variant="activeFilter === 'all' ? 'default' : 'outline'" @click="setFilter('all')">
                        All Groups
                    </Button>
                    <Button :variant="activeFilter === 'my-groups' ? 'default' : 'outline'"
                        @click="setFilter('my-groups')">
                        My Groups
                    </Button>
                </div>
            </div>

            <!-- Groups Grid -->
            <div v-if="groups.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Link v-for="group in groups.data" :key="group.id" :href="`/groups/${group.slug}`"
                    class="transition-transform hover:scale-105">
                <Card class="overflow-hidden">
                    <!-- Cover Image -->
                    <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600" :style="group.cover_url
                            ? `background-image: url(${group.cover_url}); background-size: cover; background-position: center;`
                            : ''
                        "></div>

                    <!-- Group Info -->
                    <div class="relative p-4">
                        <!-- Thumbnail -->
                        <div
                            class="absolute -top-10 left-4 h-20 w-20 rounded-lg border-4 border-white bg-white shadow-lg">
                            <img v-if="group.thumbnail_url" :src="group.thumbnail_url" :alt="group.name"
                                class="h-full w-full rounded-lg object-cover" />
                            <div v-else
                                class="flex h-full w-full items-center justify-center rounded-lg bg-gradient-to-br from-blue-400 to-purple-500">
                                <Users class="h-8 w-8 text-white" />
                            </div>
                        </div>

                        <div class="mt-12">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ group.name }}
                            </h3>
                            <p v-if="group.about" class="mt-1 line-clamp-2 text-sm text-gray-600">
                                {{ group.about }}
                            </p>

                            <!-- Stats -->
                            <div class="mt-3 flex items-center gap-4 text-sm text-gray-500">
                                <div class="flex items-center">
                                    <Users class="mr-1 h-4 w-4" />
                                    <span>{{ group.member_count || 0 }} members</span>
                                </div>
                                <div v-if="group.is_member">
                                    <span
                                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                        Member
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <Users class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">No groups found</h3>
                <p class="mt-2 text-sm text-gray-600">
                    {{ searchQuery ? 'Try a different search term' : 'Be the first to create a group!' }}
                </p>
                <Link href="/groups/create" class="mt-4 inline-block">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Create Group
                </Button>
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="groups.meta.last_page > 1" class="mt-8 flex justify-center gap-2">
                <Button v-for="page in groups.meta.last_page" :key="page"
                    :variant="page === groups.meta.current_page ? 'default' : 'outline'" size="sm" @click="
                        router.get('/groups', {
                            page,
                            search: searchQuery,
                            filter: activeFilter !== 'all' ? activeFilter : undefined,
                        })
                        ">
                    {{ page }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
