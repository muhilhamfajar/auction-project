<script lang="ts" setup>
import { useNotificationStore } from '@/stores/notificationStore';
import { UserNotification } from '@/types/item';

const props = defineProps<{
  notifications: UserNotification[];
}>();

const emit = defineEmits<{
  (e: "readNotification", notification: UserNotification): void;
}>();

const notificationStore = useNotificationStore()

const markAsRead = async (notification: UserNotification) => {
    try {
        await notificationStore.readNotification(notification.uuid)
        emit('readNotification', notification)
    } catch (error) {
        console.error('Error while mark as read: ', error)
    }
}
</script>

<template>
  <ul class="space-y-4">
    <li
      v-for="notification in notifications"
      :key="notification.uuid"
      class="p-4 border rounded-lg shadow-sm"
      :class="{
        'bg-white': notification.isRead,
        'bg-blue-50': !notification.isRead,
      }"
    >
      <div class="flex justify-between items-start">
        <div class="flex items-start">
          <Info class="h-5 w-5 text-blue-500 mr-2 mt-0.5" />
          <p
            class="text-gray-800"
            :class="{ 'font-semibold': !notification.isRead }"
          >
            {{ notification.message }}
          </p>
        </div>
        <button
          v-if="!notification.isRead"
          @click="markAsRead(notification)"
          class="text-sm text-blue-600 hover:text-blue-800 ml-4"
        >
          Mark as read
        </button>
      </div>
    </li>
  </ul>
</template>
