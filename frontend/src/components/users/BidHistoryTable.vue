<script setup lang="ts">
import ReusableTable from "@/components/common/ReusableTable.vue";
import Pagination from "@/components/common/Pagination.vue";
import { computed, onMounted, ref, watch } from "vue";
import { Bid } from "@/types/item";
import { useBidStore } from "@/stores/bidStore";
import { useAuthStore } from "@/stores/authStore";
import { format, parseISO } from "date-fns";

const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);
const items = ref<Array<Bid>>([] as Bid[]);
const sortColumn = ref("id");
const sortOrder = ref<"asc" | "desc">("desc");
const isLoading = ref(false)
const bidStore = useBidStore();
const authStore = useAuthStore();

const totalPages = computed(() =>
  Math.max(1, Math.ceil(totalItems.value / itemsPerPage.value))
);

const columns = [
  { key: "itemName", label: "Item Name", sortable: false },
  { key: "amount", label: "Your Bid", sortable: true },
  { key: "itemStatus", label: "Auction Status", sortable: true },
  { key: "yourOutcome", label: "Your Outcome", sortable: false },
  { key: "bidTime", label: "Bid Time", sortable: true },
  { key: "isAutoBid", label: "Auto Bid", sortable: false },
];

const fetchBidHistory = async () => {
  isLoading.value = true
  try {
    const response = await bidStore.fetchByUser(
      authStore.user?.id,
      currentPage.value,
      itemsPerPage.value,
      sortColumn.value,
      sortOrder.value
    );
    items.value = response.data;
    totalItems.value = response.totalItems;
  } catch (error) {
    console.error("Error fetching bids:", error);
  } finally {
    isLoading.value = false
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

const getItemStatus = (status: number): string => {
  return status === 2 ? "Closed" : "Open";
};

const getItemStatusClass = (status: number): string => {
  return status === 2
    ? "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
    : "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800";
};

const getBidStatus = (status: number): string => {
  switch (status) {
    case 1:
      return "In Progress";
    case 2:
      return "Won";
    case 3:
      return "Lose";
    default:
      return "Unknown Status";
  }
};

const getBidStatusClass = (status: number): string => {
  switch (status) {
    case 1:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800";
    case 2:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800";
    case 3:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800";
    default:
      return "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800";
  }
};

const handleSort = (key: string) => {
  const column = columns.find((col) => col.key === key);
  if (!column || !column.sortable) return;

  if (sortColumn.value === key) {
    sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
  } else {
    sortColumn.value = key;
    sortOrder.value = "asc";
  }

  currentPage.value = 1;
  fetchBidHistory();
};

const formatDate = (dateString: string): string => {
  const date = parseISO(dateString);
  return format(date, 'MM/dd/yyyy h:mm a');

};

// Watchers
watch(currentPage, fetchBidHistory);

// Lifecycle hooks
onMounted(fetchBidHistory);
</script>

<template>
  <section class="container px-4 py-8 mx-auto">
    <h2 class="text-lg font-medium text-gray-800">Bid History</h2>
    <p class="mt-1 text-sm text-gray-500">All items you've ever bid on.</p>
    <ReusableTable
      :columns="columns"
      :items="items"
      :show-actions="false"
      :sortColumn="sortColumn"
      :sortOrder="sortOrder"
      :is-loading="isLoading"
      @sort="handleSort"
    >
      <template #itemName="{ item }">
        <router-link
          class="text-blue-600 hover:text-blue-800"
          :to="{
            path: `/items/${item.item.uuid}`,
          }"
          >{{ item.item.name }}</router-link
        >
      </template>

      <template #amount="{ item }">
        ${{ Number(item.amount).toFixed(2) }}
      </template>

      <template #itemStatus="{ item }">
        <span :class="getItemStatusClass(item.item.status)">
          {{ getItemStatus(item.item.status) }}
        </span>
      </template>

      <template #yourOutcome="{ item }">
        <span :class="getBidStatusClass(item.status)">
          {{ getBidStatus(item.status) }}
        </span>
      </template>

      <template #isAutoBid="{ item }">
        {{ item.isAutoBid ? "Yes" : "No" }}
      </template>

      <template #bidTime="{ item }">
        {{ formatDate(item.bidTime) }}
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
