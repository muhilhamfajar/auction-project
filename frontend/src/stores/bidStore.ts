import { defineStore } from "pinia";
import axiosInstance from "@/config/axios";
import { AutoBidConfig, Bid } from "@/types/item";

export interface AutoBidConfigForm {
  user: string;
  maxBidAmount: number;
  bidAlertPercentage: number;
}

export interface BidFormData {
  bidder: string;
  item: string;
  bidTime: string;
  amount: number;
  isAutoBid: boolean;
}

export interface AutoBidFormData {
  user: string,
  item: string
}

export const useBidStore = defineStore("bid", {
  state: () => ({
    bids: [] as Bid[],
    currentBid: null as Bid | null,
    loading: false,
    error: null as string | null,
    autoBidConfig: null as AutoBidConfig | null,
  }),
  actions: {
    async fetchByItems(
      item: number,
      page = 1,
      limit = 10,
      sort?: string,
      order?: string
    ) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/bids", {
          params: {
            item,
            page,
            limit,
            sort,
            order,
          },
        });
        this.bids = response.data.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchByUser(
      bidder: number,
      page = 1,
      limit = 10,
      sort?: string,
      order?: string,
    ) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/bids", {
          params: {
            bidder,
            page,
            limit,
            sort,
            order
          },
        });
        this.bids = response.data.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async getUserLatestBid(
      item: number,
      bidder: number,
    ) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/bids", {
          params: {
            item,
            bidder,
            page: 1,
            limit: 1,
            sort: 'amount',
            order: 'desc',
          },
        });
        return response.data.data[0]
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async getUserAutoBidForItem(
      item: number,
      user: number,
    ) {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/auto-bids", {
          params: {
            item,
            user,
            page: 1,
            limit: 1
          },
        });
        return response.data.data[0]
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async placeBid(bidForm: BidFormData) {
      this.loading = true;
      try {
        const response = await axiosInstance.post("/bids", bidForm);
        this.currentBid = response.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading;
      }
    },

    async activateAutoBid(form: AutoBidFormData) {
      this.loading = true;
      try {
        const response = await axiosInstance.post("/auto-bids", form);
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to activate auto bids");
        throw error;
      } finally {
        this.loading;
      }
    },

    async deactivateAutoBid(uuid: string) {
      this.loading = true;
      try {
        await axiosInstance.delete(`/auto-bids/${uuid}`);
      } catch (error) {
        this.handleError(error, "Failed to deactivate auto bids");
        throw error;
      } finally {
        this.loading;
      }
    },

    async getAutoBidConfig() {
      this.loading = true;
      try {
        const response = await axiosInstance.get("/bid-configs/me");
        this.autoBidConfig = response.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async insertAutoBidConfig(autoBidConfig: AutoBidConfigForm) {
      this.loading = true;
      try {
        const response = await axiosInstance.post(
          "/bid-configs",
          autoBidConfig
        );
        this.autoBidConfig = response.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateAutoBidConfig(uuid: string, autoBidConfig: AutoBidConfigForm) {
      this.loading = true;
      try {
        const response = await axiosInstance.put(
          `/bid-configs/${uuid}`,
          autoBidConfig
        );
        this.autoBidConfig = response.data;
        return response.data;
      } catch (error) {
        this.handleError(error, "Failed to fetch bids");
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
