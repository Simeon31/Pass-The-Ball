<script setup lang="ts">
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'

interface User {
   name: string;
   avatar: string;
}

interface Group {
   name: string;
}

interface Post {
   body: string;
   user: User;
   created_at: Date | string;
   group: Group;
   attachments: any[];
}
const props = defineProps<{
   post: Post
}>();

function isImage(attachment: any) {
   const mime = attachment.mime.split('/');
   return mime[0] === 'image';
}
</script>

<template>
   <div class="bg-white border rounded p-3 shadow mb-6 ">
      <div class="flex items-center gap-4 mb-4">
         <a href="javascript:void(0)">
            <img :src="post.user.avatar" alt="User avatar" class="w-12 h-12 rounded-full object-cover
            border border-2 hover-ring-blue-400" />
         </a>
         <div class="flex flex-col">
            <a href="javascript:void(0)" class="hover:underline">
               <span class="font-bold text-gray-900 text-base leading-tight">{{ post.user.name }}</span>

            </a>
            <template v-if="post.group"> Group: {{ post.group.name }}
            </template>
            <span class="text-xs text-gray-400 mt-1">{{ post.created_at }}</span>
         </div>
      </div>
      <div class="mb-4">
         <Disclosure v-slot="{ open }">
            <div v-if="!open" v-html="post.body.substring(0, 200)" />
            <DisclosurePanel>
               <div v-html="post.body" />
            </DisclosurePanel>
            <div class="flex justify-end">
               <DisclosureButton class="text-indigo-600 mt-2 hover:underline cursor-pointer">
                  {{ open ? 'Show Less' : 'Show More' }}
               </DisclosureButton>
            </div>

         </Disclosure>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
         <template v-for="attachment in post.attachments">
            <div
               class="group aspect-square bg-indigo-100 flec flex-col items-center justify-center text-gray-500 relative">
               <button
                  class="w-8 h-8 opacity-0 group-hover:opacity-100 transition-all flex item-center justify-center text-white bg-gray-800 rounded absolute right-2 top-2 cursor-pointer hover:bg-gray-700">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                     stroke="currentColor" class="size-6">
                     <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                  </svg>

               </button>
               <img v-if="isImage(attachment)" :src="attachment.url" alt="No image to show"
                  class="object-cover aspect-square" />
            </div>
         </template>
      </div>
      <div class="flex gap-2">
         <button
            class="text-gray-800 flex gap-1 items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg py-2 px-4 flex-1 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
               stroke="currentColor" class="size-6">
               <path stroke-linecap="round" stroke-linejoin="round"
                  d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
            </svg>
            Like
         </button>
         <button
            class="text-gray-800 flex gap-1 items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-lg py-2 px-4 flex-1 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
               stroke="currentColor" class="size-6">
               <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
            </svg>

            Comment
         </button>
      </div>
   </div>
</template>