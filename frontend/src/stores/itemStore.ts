import { defineStore } from 'pinia';
import axiosInstance from '@/config/axios';

export interface ItemData {
  name: string;
  description: string;
  startingPrice: number;
  auctionEndTime: string;
}

export interface ItemMediaData {
  item: string;
  name?: string;
  caption?: string;
  imageFile?: File;
}

export const useItemStore = defineStore('item', {
  state: () => ({
    items: [] as any[],
    currentItem: null as any | null,
    loading: false,
    error: null as string | null,
  }),
  actions: {
    async fetchItems(search: string, page = 1, limit = 10, sort?: string, order?: string) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/items", {
          params: { 
            q: search,
            sort,
            order,
            page, 
            limit, 
          }
        });
        this.items = response.data.data;
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to fetch items');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchItem(uuid: string) {
      this.loading = true;
      try {
        const response = await axiosInstance.get(`/items/${uuid}`);
        this.currentItem = response.data;
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to fetch item');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createItem(itemData: ItemData) {
      this.loading = true;
      try {
        const response = await axiosInstance.post("/items", itemData);
        this.items.unshift(response.data);
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to create item');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateItem(uuid: string, itemData: ItemData) {
      this.loading = true;
      try {
        const response = await axiosInstance.put(`/items/${uuid}`, itemData);
        const index = this.items.findIndex(item => item.uuid === uuid);
        if (index !== -1) {
          this.items[index] = response.data;
        }
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to update item');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteItem(uuid: string) {
      this.loading = true;
      try {
        await axiosInstance.delete(`/items/${uuid}`);
        this.items = this.items.filter(item => item.uuid !== uuid);
      } catch (error) {
        this.handleError(error, 'Failed to delete item');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async uploadItemMedia(itemMediaData: ItemMediaData) {
      this.loading = true;
      try {
        const formData = new FormData();
        formData.append('item', itemMediaData.item);
        if (itemMediaData.name) formData.append('name', itemMediaData.name);
        if (itemMediaData.caption) formData.append('caption', itemMediaData.caption);
        if (itemMediaData.imageFile) formData.append('imageFile', itemMediaData.imageFile);

        const response = await axiosInstance.post('/item-medias', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to upload item media');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async getItemMedia(uuid: string) {
      this.loading = true;
      try {
        const response = await axiosInstance.get(`/item-medias/${uuid}`);
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to get item media');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async getItemMediaBaseUrl() {
      this.loading = true;
      try {
        const response = await axiosInstance.get(`/item-medias/base-url`);
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to get item media base url');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateItemMedia(uuid: string, itemMediaData: ItemMediaData) {
      this.loading = true;
      try {
        const formData = new FormData();
        if (itemMediaData.name) formData.append('name', itemMediaData.name);
        if (itemMediaData.caption) formData.append('caption', itemMediaData.caption);
        if (itemMediaData.imageFile) formData.append('imageFile', itemMediaData.imageFile);

        const response = await axiosInstance.post(`/item-medias/${uuid}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        return response.data;
      } catch (error) {
        this.handleError(error, 'Failed to update item media');
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteItemMedia(uuid: string) {
      this.loading = true;
      try {
        await axiosInstance.delete(`/item-medias/${uuid}`);
      } catch (error) {
        this.handleError(error, 'Failed to delete item media');
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