<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useFlashMessage } from '@/composables/useFlashMessage';
import AppLayout from '@/layouts/AppLayout.vue';
import EditAlbumModal from '@/components/app/EditAlbumModal.vue';
import UploadPhotosModal from '@/components/app/UploadPhotosModal.vue';
import { destroy as deleteAlbum } from '@/routes/gallery/albums';
import { index as galleryIndex } from '@/routes/gallery';
import type { Album, PaginatedData, Photo, User } from '@/types';
import { CheckCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { Download, Edit, Eye, Image as ImageIcon, Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    album: Album;
    photos: PaginatedData<Photo>;
    profileUser: User;
    isOwner: boolean;
}

const props = defineProps<Props>();

// Modal states
const showEditModal = ref(false);
const showUploadModal = ref(false);

// Flash message
const {
    showMessage: showSuccess,
    message: statusMessage,
    dismiss: dismissSuccess,
} = useFlashMessage('status', 5000);

// SEO helpers
const currentUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.href;
    }
    return '';
});

const coverImage = computed(() => {
    return props.album.cover_url || props.photos.data[0]?.medium_url || '';
});

// Schema.org structured data
const schemaData = computed(() => {
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ImageGallery',
        'name': props.album.title,
        'description': props.album.description || `Photo album by ${props.profileUser.name}`,
        'author': {
            '@type': 'Person',
            'name': props.profileUser.name,
        },
        'image': props.photos.data.slice(0, 10).map(photo => ({
            '@type': 'ImageObject',
            'contentUrl': photo.medium_url,
            'thumbnailUrl': photo.thumbnail_url,
            'name': photo.title || `Photo from ${props.album.title}`,
            'description': photo.description || '',
            'uploadDate': photo.created_at,
            'width': photo.width || '',
            'height': photo.height || '',
        })),
        'numberOfItems': props.album.photos_count || 0,
        'dateCreated': props.album.created_at,
    });
});

// Infinite scroll state
const allPhotos = ref<Photo[]>([...props.photos.data]);
const currentPage = ref(props.photos.meta.current_page);
const hasMore = ref(props.photos.links.next !== null);
const isLoading = ref(false);

// Load more photos
const loadMore = async () => {
    if (!hasMore.value || isLoading.value) return;

    isLoading.value = true;

    try {
        const response = await fetch(
            `${window.location.pathname}?page=${currentPage.value + 1}`,
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            },
        );

        if (response.ok) {
            const data = await response.json();
            if (data.photos && data.photos.data) {
                allPhotos.value.push(...data.photos.data);
                currentPage.value = data.photos.meta.current_page;
                hasMore.value = data.photos.links.next !== null;
            }
        }
    } catch (error) {
        console.error('Failed to load more photos:', error);
    } finally {
        isLoading.value = false;
    }
};

// Delete album
const handleDeleteAlbum = () => {
    if (!confirm('Are you sure you want to delete this album? This action cannot be undone.')) {
        return;
    }

    router.delete(deleteAlbum.url({ username: props.profileUser.username, album: props.album.slug }), {
        onSuccess: () => {
            // Redirect to gallery index after deletion
            router.get(galleryIndex.url({ username: props.profileUser.username }));
        },
    });
};

// Infinite scroll observer
const observer = ref<IntersectionObserver | null>(null);
const loadMoreTrigger = ref<HTMLElement | null>(null);

onMounted(() => {
    observer.value = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && hasMore.value && !isLoading.value) {
                loadMore();
            }
        },
        { threshold: 0.1 },
    );

    if (loadMoreTrigger.value) {
        observer.value.observe(loadMoreTrigger.value);
    }
});

onUnmounted(() => {
    if (observer.value) {
        observer.value.disconnect();
    }
});

// Select photo for lightbox
const selectedPhoto = ref<Photo | null>(null);

const openPhoto = (photo: Photo) => {
    selectedPhoto.value = photo;
    // Navigate to photo detail page
    router.visit(
        `/profile/${props.profileUser.username}/gallery/${props.album.slug}/${photo.slug}`,
    );
};
</script>

<template>
    <AppLayout>

        <Head :title="`${album.title} - ${profileUser.name}'s Gallery`">
            <!-- Open Graph Meta Tags -->
            <meta property="og:title" :content="`${album.title} - ${profileUser.name}'s Gallery`" />
            <meta property="og:description" :content="album.description || `Photo album by ${profileUser.name}`" />
            <meta property="og:image" :content="coverImage" />
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="currentUrl" />

            <!-- Twitter Card Meta Tags -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="`${album.title} - ${profileUser.name}'s Gallery`" />
            <meta name="twitter:description" :content="album.description || `Photo album by ${profileUser.name}`" />
            <meta name="twitter:image" :content="coverImage" />

            <!-- General Meta Tags -->
            <meta name="description"
                :content="album.description || `Photo album by ${profileUser.name} containing ${album.photos_count || 0} photos`" />
        </Head>

        <!-- Schema.org JSON-LD -->
        <component :is="'script'" type="application/ld+json" v-html="schemaData"></component>

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

            <!-- Album Header -->
            <div class="mb-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-bold text-gray-900">
                                {{ album.title }}
                            </h1>
                            <Button v-if="isOwner" variant="ghost" size="icon" @click="showEditModal = true">
                                <Edit class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="album.description" class="mt-2 text-gray-600">
                            {{ album.description }}
                        </p>
                        <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                            <div class="flex items-center">
                                <ImageIcon class="mr-1 h-4 w-4" />
                                <span>{{ album.photos_count || 0 }} photos</span>
                            </div>
                            <span>•</span>
                            <span>{{ album.visibility }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="isOwner" @click="showUploadModal = true">
                            <Plus class="mr-2 h-4 w-4" />
                            Upload Photos
                        </Button>
                        <Button v-if="isOwner" variant="destructive" @click="handleDeleteAlbum">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete Album
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Photos Grid -->
            <div v-if="allPhotos.length > 0" class="grid gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <div v-for="photo in allPhotos" :key="photo.id"
                    class="group relative cursor-pointer overflow-hidden rounded-lg bg-gray-100"
                    @click="openPhoto(photo)">
                    <!-- Photo Image -->
                    <div class="aspect-square">
                        <img :src="photo.thumbnail_url" :alt="photo.title || 'Photo'"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110" />
                    </div>

                    <!-- Overlay on Hover -->
                    <div
                        class="absolute inset-0 bg-black/60 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <div class="flex h-full flex-col justify-between p-3">
                            <div v-if="photo.title" class="text-sm font-medium text-white line-clamp-2">
                                {{ photo.title }}
                            </div>
                            <div class="flex items-center justify-between text-xs text-white">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center">
                                        <Eye class="mr-1 h-3 w-3" />
                                        {{ photo.views_count }}
                                    </div>
                                    <div class="flex items-center">
                                        <Download class="mr-1 h-3 w-3" />
                                        {{ photo.downloads_count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <ImageIcon class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900">
                    No photos in this album yet
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    {{
                        isOwner
                            ? 'Upload photos to start building your collection'
                            : 'This album is empty'
                    }}
                </p>
                <Button v-if="isOwner" class="mt-4" @click="showUploadModal = true">
                    <Plus class="mr-2 h-4 w-4" />
                    Upload Photos
                </Button>
            </div>

            <!-- Infinite Scroll Trigger -->
            <div v-if="hasMore" ref="loadMoreTrigger" class="mt-8 flex justify-center py-4">
                <div v-if="isLoading" class="flex items-center gap-2 text-gray-600">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-gray-300 border-t-gray-600"></div>
                    <span>Loading more photos...</span>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <EditAlbumModal v-model:isOpen="showEditModal" :album="album" :username="profileUser.username" />
        <UploadPhotosModal v-model:isOpen="showUploadModal" :album="album" />
    </AppLayout>
</template>
