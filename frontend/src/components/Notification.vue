<template>
    <div class="fixed top-4 right-4 z-50">
      <transition-group name="notification">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="mb-2 p-4 rounded-lg shadow-lg max-w-sm"
          :class="{
            'bg-green-500 text-white': notification.type === 'success',
            'bg-red-500 text-white': notification.type === 'error',
            'bg-blue-500 text-white': notification.type === 'info'
          }"
        >
          {{ notification.message }}
        </div>
      </transition-group>
    </div>
  </template>
  
  <script setup lang="ts">
  import { computed } from 'vue';
  
  const props = defineProps<{
    notifications: Array<{
      id: number;
      message: string;
      type: 'success' | 'error' | 'info';
    }>;
  }>();
  
  const notifications = computed(() => props.notifications);
  </script>
  
  <style scoped>
  .notification-enter-active,
  .notification-leave-active {
    transition: all 0.5s ease;
  }
  .notification-enter-from,
  .notification-leave-to {
    opacity: 0;
    transform: translateX(30px);
  }
  </style>