import { defineStore } from "pinia";
import axiosInstance from "@/config/axios";
import { Bid } from "@/types/item";

export const useUserBidStore = defineStore("userBid", {
  state: () => ({
    userBids: [] as Bid[],
    loading: false,
    error: null as string | null
  }),
  actions: {
    async fetchCurrentBids(
      page = 1,
      limit = 10,
      sort?: string,
      order?: string
    ) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/user/bids/current", {
          params: {
            page,
            limit,
            sort,
            order,
          },
        });
        this.userBids = response.data.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch user bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchAwardedBids(
      page = 1,
      limit = 10,
      sort?: string,
      order?: string
    ) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/user/bids/awarded", {
          params: {
            page,
            limit,
            sort,
            order,
          },
        });
        this.userBids = response.data.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch user bids");
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
