<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useFlashMessage } from '@/composables/useFlashMessage';
import AppLayout from '@/layouts/AppLayout.vue';
import CreateAlbumModal from '@/components/app/CreateAlbumModal.vue';
import { index as galleryIndex } from '@/routes/gallery';
import type { Album, PaginatedData, User } from '@/types';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { Image, Plus, Search } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    albums: PaginatedData<Album>;
    profileUser: User;
    isOwner: boolean;
    filters?: {
        search?: string;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters?.search || '');
const showCreateModal = ref(false);

// Flash message
const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);

const handleSearch = () => {
    router.get(
        galleryIndex.url({ username: props.profileUser.username }),
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <AppLayout>

        <Head :title="`${profileUser.name}'s Gallery`">
            <!-- Open Graph Meta Tags -->
            <meta property="og:title" :content="`${profileUser.name}'s Photo Gallery`" />
            <meta property="og:description"
                :content="`Browse ${albums.meta.total || 0} photo albums by ${profileUser.name}`" />
            <meta property="og:type" content="profile" />

            <!-- Twitter Card Meta Tags -->
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:title" :content="`${profileUser.name}'s Photo Gallery`" />
            <meta name="twitter:description"
                :content="`Browse ${albums.meta.total || 0} photo albums by ${profileUser.name}`" />

            <!-- General Meta Tags -->
            <meta name="description"
                :content="`Browse ${albums.meta.total || 0} photo albums by ${profileUser.name}`" />
        </Head>

        <div class="container mx-auto h-full overflow-auto px-4 py-6">
            <!-- Success Message -->
            <Transition enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 transform translate-y-2"
                enter-to-class="opacity-100 transform translate-y-0"
                leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="statusMessage() && showSuccess"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <CheckCircleIcon class="mr-2 h-5 w-5 text-green-600" />
                            <p class="text-sm font-medium text-green-800">
                                {{ statusMessage() }}
                            </p>
                        </div>
                        <button @click="dismissSuccess" class="text-green-600 hover:text-green-800">
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ isOwner ? 'My Gallery' : `${profileUser.name}'s Gallery` }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        {{
                            isOwner
                                ? 'Organize and share your photos in albums'
                                : `Browse ${profileUser.name}'s photo albums`
                        }}
                    </p>
                </div>
                <Button v-if="isOwner" @click="showCreateModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Album
                </Button>
            </div>

            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative max-w-md">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input v-model="searchQuery" type="text" placeholder="Search albums..." class="pl-10"
                        @keyup.enter="handleSearch" />
                </div>
            </div>

            <!-- Albums Grid -->
            <div v-if="albums.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Link v-for="album in albums.data" :key="album.id"
                    :href="`/profile/${profileUser.username}/gallery/${album.slug}`"
                    class="group transition-transform hover:scale-105">
                <Card class="overflow-hidden">
                    <!-- Cover Image -->
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200">
                        <img v-if="album.cover_url" :src="album.cover_url" :alt="album.title"
                            class="h-full w-full object-cover transition-transform group-hover:scale-110" />
                        <div v-else class="flex h-full w-full items-center justify-center">
                            <Image class="h-16 w-16 text-gray-400" />
                        </div>
                    </div>

                    <!-- Album Info -->
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 line-clamp-1">
                            {{ album.title }}
                        </h3>
                        <p v-if="album.description" class="mt-1 text-sm text-gray-600 line-clamp-2">
                            {{ album.description }}
                        </p>

                        <!-- Stats -->
                        <div class="mt-3 flex items-center gap-2 text-sm text-gray-500">
                            <Image class="h-4 w-4" />
                            <span>{{ album.photos_count || 0 }}
                                {{
                                    album.photos_count === 1
                                        ? 'photo'
                                        : 'photos'
                                }}</span>
                        </div>
                    </div>
                </Card>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <Image class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    {{ searchQuery ? 'No albums found' : 'No albums yet' }}
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    {{
                        searchQuery
                            ? 'Try a different search word'
                            : isOwner
                                ? 'Create your first album to get started'
                                : `${profileUser.name} hasn't created any albums yet`
                    }}
                </p>
                <Button v-if="isOwner && !searchQuery" class="mt-4" @click="showCreateModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Album
                </Button>
            </div>

            <!-- Pagination -->
            <div v-if="albums.meta.last_page > 1" class="mt-8 flex items-center justify-center gap-2">
                <Button v-if="albums.links.prev" variant="outline" @click="router.get(albums.links.prev!)">
                    Previous
                </Button>
                <span class="text-sm text-gray-600">
                    Page {{ albums.meta.current_page }} of
                    {{ albums.meta.last_page }}
                </span>
                <Button v-if="albums.links.next" variant="outline" @click="router.get(albums.links.next!)">
                    Next
                </Button>
            </div>
        </div>

        <!-- Create Album Modal -->
        <CreateAlbumModal v-model:isOpen="showCreateModal" />
    </AppLayout>
</template>
