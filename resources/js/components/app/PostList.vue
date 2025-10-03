<script setup lang="ts">
import PostItem from './PostItem.vue';

const post01 = {

    user: {
        id: 1,
        avatar: 'https://avatar.iran.liara.run/public/31',
        name: 'John Doe',
    },
    group: {
        id: 0,
        name: "General"
    },
    attachments: [
        {
            id: 1,
            name: 'example.pdf',
            mime: 'image/png',
            url: 'https://lipsum.app/random/1920x1080'
        },
        {
            id: 2,
            name: 'example.pdf',
            mime: 'image/png',
            url: 'https://lipsum.app/random/1920x1080'
        },
    ],
    body: 'Hello, this is a sample post body to demonstrate the PostItem component in Vue.js. It includes some text to show how the content will be displayed within the post item layout.',
    created_at: '2024-06-12:00',

}

const post02 = {

    user: {
        id: 2,
        avatar: 'https://avatar.iran.liara.run/public/33',
        name: 'Alex Olson',
    },

    group: {
        id: 1,
        name: "Tennis"
    },
    attachments: [
        {
            id: 3,
            name: 'example2.pdf',
            mime: 'image/png',
            url: 'https://lipsum.app/random/1920x1080'
        }
    ],
    body: 'Hello, this is a sample post body to demonstrate the PostItem component in Vue.js. It includes some text to show how the content will be displayed within the post item layout.',
    created_at: '2024-06-12:00',

}
</script>

<template>
    <div>
        <div class="overflow-y-auto">
            <PostItem :post="post01" />
            <PostItem :post="post02" />
        </div>
    </div>
</template>