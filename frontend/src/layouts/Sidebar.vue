<template>
  <div class="flex w-16 flex-col justify-between border-e bg-white">
    <div>
      <div class="inline-flex size-16 items-center justify-center">
        <span class="grid size-10 place-content-center rounded-lg bg-gray-100 text-xs text-gray-600">
          A
        </span>
      </div>

      <div class="border-t border-gray-100">
        <div class="px-2">
          <ul class="space-y-1 border-t border-gray-100 pt-4">
            <li v-for="link in links" :key="link.text">
              <SidebarLink 
                :icon="link.icon" 
                :text="link.text" 
                :to="link.to" 
                :hasNotification="link.text === 'Notification' && hasUnreadNotifications"
              />
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="sticky inset-x-0 bottom-0 border-t border-gray-100 bg-white p-2">
      <SidebarLink :icon="LogOut" text="Logout" @click="logout" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/authStore';
import { ref, computed, onMounted, watch } from 'vue';
import { House, UserCog, LogOut, Bell } from 'lucide-vue-next';
import SidebarLink from './SidebarLink.vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notificationStore';

const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const hasUnreadNotifications = computed(() => notificationStore.hasUnreadNotifications)

const links = computed(() => {
  if (!authStore.isAuthChecked) {
    return []
  }

  if (authStore.isAdmin) {
    return [
      { icon: House, text: 'Dashboard', to: '/dashboard' },
    ]
  } else {
    return [
      { icon: House, text: 'Home', to: '/' },
      { icon: Bell, text: 'Notification', to: '/notifications' },
      { icon: UserCog, text: 'Account', to: '/account' },
    ]
  }
})

const checkNotifications = async () => {
  try {
    await notificationStore.checkUnreadNotifications()
  } catch (error) {
    console.error('Error check notifications: ', error)
  }
}

watch(() => authStore.isAuthenticated, (newValue) => {
  if (newValue) {
    checkNotifications()
  }
}, { immediate: true })

onMounted(() => {
  checkNotifications()
})

const logout = () => {
  authStore.logout()
  router.push('/login')
}
</script>