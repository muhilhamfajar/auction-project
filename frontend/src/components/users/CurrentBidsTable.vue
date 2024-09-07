<script setup lang="ts">
import ReusableTable from "@/components/common/ReusableTable.vue";
import Pagination from "@/components/common/Pagination.vue";
import { computed, onMounted, ref, watch } from "vue";
import { Bid } from "@/types/item";
import { useAuthStore } from "@/stores/authStore";
import { format, parseISO } from "date-fns";
import { useUserBidStore } from "@/stores/userBidStore";

const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);
const currentBids = ref<Array<Bid>>([] as Bid[]);
const sortColumn = ref("auctionEndTime");
const sortOrder = ref<"asc" | "desc">("asc");
const isLoading = ref(false);
const userBidStore = useUserBidStore();
const authStore = useAuthStore();

const totalPages = computed(() =>
  Math.max(1, Math.ceil(totalItems.value / itemsPerPage.value))
);

const currentBidsColumns = [
  { key: "itemName", label: "Item Name", sortable: false },
  { key: "currentBidAmount", label: "Current Bid", sortable: true },
  { key: "auctionEndTime", label: "Auction Ends", sortable: true },
  { key: "status", label: "Status", sortable: false },
];

const fetchCurrentBids = async () => {
  isLoading.value = true;
  try {
    const response = await userBidStore.fetchCurrentBids(
      currentPage.value,
      itemsPerPage.value,
      sortColumn.value,
      sortOrder.value
    );
    currentBids.value = response.data;
    totalItems.value = response.totalItems;
  } catch (error) {
    console.error("Error fetching current bids:", error);
  } finally {
    isLoading.value = false;
  }
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

const getStatusClass = (status: number): string => {
  switch (status) {
    case 2:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800";
    case 3:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800";
    default:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800";
  }
};

const getStatus = (status: number): string => {
  switch (status) {
    case 2:
      return 'Win';
    case 3:
      return 'Lose';
    default:
      return 'Leading';
  }
};

const handleSort = (key: string) => {
  const column = currentBidsColumns.find((col) => col.key === key);
  if (!column || !column.sortable) return;

  if (sortColumn.value === key) {
    sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
  } else {
    sortColumn.value = key;
    sortOrder.value = "asc";
  }

  currentPage.value = 1;
  fetchCurrentBids();
};

const formatDate = (dateString: string): string => {
  const date = parseISO(dateString);
  return format(date, 'MM/dd/yyyy h:mm a');
};

// Watchers
watch(currentPage, fetchCurrentBids);

// Lifecycle hooks
onMounted(fetchCurrentBids);
</script>

<template>
  <section class="container px-4 py-8 mx-auto">
    <h2 class="text-lg font-medium text-gray-800">Current Bids</h2>
    <p class="mt-1 text-sm text-gray-500">Items you're currently bidding on.</p>
    <ReusableTable
      :columns="currentBidsColumns"
      :items="currentBids"
      :show-actions="false"
      :sortColumn="sortColumn"
      :sortOrder="sortOrder"
      :is-loading="isLoading"
      @sort="handleSort"
    >
      <template #itemName="{ item }">
        <router-link class="text-blue-600 hover:text-blue-800" :to="{ path: `/items/${item.item.uuid}` }">
          {{ item.item.name }}
        </router-link>
      </template>

      <template #currentBidAmount="{ item }">
        ${{ Number(item.amount).toFixed(2) }}
      </template>

      <template #auctionEndTime="{ item }">
        {{ formatDate(item.item.auctionEndTime) }}
      </template>

      <template #status="{ item }">
        <span :class="getStatusClass(item.status)">
          {{ getStatus(item.status) }}
        </span>
      </template>
    </ReusableTable>
    <Pagination
      :currentPage="currentPage"
      :totalPages="totalPages"
      @prev="prevPage"
      @next="nextPage"
    />
  </section>
</template>