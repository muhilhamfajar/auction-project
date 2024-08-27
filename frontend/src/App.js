<template>
  <component :is="layout">
    <router-view></router-view>
  </component>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout from './layouts/AuthLayout.vue'
import GuestLayout from './layouts/GuestLayout.vue'

const route = useRoute()

const layout = computed(() => {
  return route.meta.layout === 'guest' ? GuestLayout : AuthLayout
})
</script>