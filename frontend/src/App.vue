<template>
  <component :is="layout">
    <router-view></router-view>
  </component>
  <Notification :notifications="notifications" />
</template>

<script lang="ts" setup>
import { computed, provide, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AuthLayout from './layouts/AuthLayout.vue'
import GuestLayout from './layouts/GuestLayout.vue'
import { onMounted } from 'vue'
import Notification from './components/Notification.vue';

const route = useRoute()
const notifications = ref([]);

const layout = computed(() => {
  return route.meta.layout === 'guest' ? GuestLayout : AuthLayout
})

const addNotification = (message: string, type: 'success' | 'error' | 'info' = 'success') => {
  const id = Date.now();
  notifications.value.push({ id, message, type });
  setTimeout(() => {
    notifications.value = notifications.value.filter(n => n.id !== id);
  }, 3000);
};

provide('addNotification', addNotification);
</script>