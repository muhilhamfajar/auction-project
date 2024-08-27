import { defineStore } from 'pinia';
import axiosInstance from '@/config/axios';

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [] as any[],
    hasUnreadNotifications: false,
    loading: false,
    error: null as string | null,
  }),
  actions: {
    async fetchNotifications(page = 1, limit = 10, sort?: string, order?: string) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/notifications", {
          params: { 
            sort,
            order,
            page, 
            limit, 
          }
        });
        this.notifications = response.data.data;
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to fetch items');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async checkUnreadNotifications() {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/notifications/unread-check");
        this.hasUnreadNotifications = response.data.hasUnreadNotifications
        return response.data.hasUnreadNotifications;
      } catch (error) {
        this.handleError(error, 'Failed to fetch items');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async readNotification(uuid: string) {
      this.loading = true;
      try {
        await axiosInstance.post(`/notifications/${uuid}/mark-as-read`);
        this.checkUnreadNotifications()
      } catch (error) {
        this.handleError(error, 'Failed to fetch items');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async markAllNotifications() {
      this.loading = true;
      try {
        await axiosInstance.post('/notifications/mark-all-read');
        this.checkUnreadNotifications()
      } catch (error) {
        this.handleError(error, 'Failed to mark all');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    handleError(error: any, message: string) {
      this.error = message;
      console.error(`${message}:`, error);
    },
  },
});