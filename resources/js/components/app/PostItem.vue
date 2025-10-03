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
   group?: Group;
}
const props = defineProps<{
   post: Post
}>();
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
      <div>
         <Disclosure v-slot="{ open }">
            <div v-if="!open" v-html="post.body.substring(0, 200)" />
            <DisclosurePanel>
               <div v-html="post.body" />
            </DisclosurePanel>
            <div class="flex justify-end">
               <DisclosureButton class="text-indigo-600 mt-2 hover:underline">
                  {{ open ? 'Show Less' : 'Show More' }}
               </DisclosureButton>
            </div>

         </Disclosure>
      </div>
   </div>
</template>