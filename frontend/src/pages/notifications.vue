<script lang="ts" setup>
import { ref, onMounted, computed, watch } from "vue";
import { Info } from "lucide-vue-next";
import { UserNotification } from "@/types/item";
import NotificationList from "@/components/users/NotificationList.vue";
import { useNotificationStore } from "@/stores/notificationStore";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const notificationStore = useNotificationStore();

const notifications = ref<UserNotification[]>([]);
const isLoading = ref(false);
const currentPage = ref(1);
const totalItems = ref(0);
const itemsPerPage = ref(50);

const totalPages = computed(() =>
  Math.ceil(totalItems.value / itemsPerPage.value)
);

const hasUnreadNotifications = computed(() => notificationStore.hasUnreadNotifications)

const fetchNotifications = async () => {
  isLoading.value = true;

  try {
    const resp = await notificationStore.fetchNotifications(
      currentPage.value,
      itemsPerPage.value,
      "createdAt",
      "desc"
    );
    notifications.value = resp.data;
    totalItems.value = resp.totalItems;
  } catch (error) {
    console.error("Error while fetch notifications.");
  } finally {
    isLoading.value = false;
  }
};

const handleReadNotification = (updatedNotification: UserNotification) => {
  const index = notifications.value.findIndex(
    (notification) => notification.uuid === updatedNotification.uuid
  );
  notifications.value[index].isRead = true;
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const markAllAsRead = async () => {
  try {
    await notificationStore.markAllNotifications()

    notifications.value = notifications.value.map(notification => ({
      ...notification,
      isRead: true
    }))

  } catch (error) {
    console.error('Error while mark all notifications: ', error)
  }
}

onMounted(fetchNotifications);

watch([currentPage], fetchNotifications);
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto p-4">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Notifications</h1>
        <button
          @click="markAllAsRead"
          :disabled="!hasUnreadNotifications"
          class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!hasUnreadNotifications">Marking all as read...</span>
          <span v-else>Mark all as read</span>
        </button>
      </div>
      <div v-if="isLoading" class="text-center">
        <p>Loading notifications...</p>
      </div>
      <NotificationList
        v-else
        :notifications="notifications"
        @readNotification="handleReadNotification"
      />

      <div class="flex justify-center w-full">
        <div class="flex items-center mt-4 gap-x-4 sm:mt-8">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="flex items-center justify-center w-1/2 px-5 py-2 text-sm text-gray-700 capitalize transition-colors duration-200 bg-white border rounded-md sm:w-auto gap-x-2 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ChevronLeft class="w-5 h-5 rtl:-scale-x-100" />
            <span>Previous</span>
          </button>

          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="flex items-center justify-center w-1/2 px-5 py-2 text-sm text-gray-700 capitalize transition-colors duration-200 bg-white border rounded-md sm:w-auto gap-x-2 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span>Next</span>
            <ChevronRight class="w-5 h-5 rtl:-scale-x-100" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
