<script setup lang="ts">
import ReusableTable from "@/components/common/ReusableTable.vue";
import Pagination from "@/components/common/Pagination.vue";
import { computed, onMounted, ref, watch } from "vue";
import { Bid } from "@/types/item";
import { useUserBidStore } from "@/stores/userBidStore";
import { format, parseISO } from "date-fns";

const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);
const awardedItems = ref<Array<Bid>>([] as Bid[]);
const sortColumn = ref("auctionEndDate");
const sortOrder = ref<"asc" | "desc">("desc");
const isLoading = ref(false);
const userBidStore = useUserBidStore();

const totalPages = computed(() =>
  Math.max(1, Math.ceil(totalItems.value / itemsPerPage.value))
);

const awardedItemsColumns = [
  { key: "itemName", label: "Item Name", sortable: false },
  { key: "winningBidAmount", label: "Winning Bid", sortable: true },
  { key: "auctionEndDate", label: "Auction Ended", sortable: true },
  { key: "billLink", label: "View Bill", sortable: false },
];

const fetchAwardedItems = async () => {
  isLoading.value = true;
  try {
    const response = await userBidStore.fetchAwardedBids(
      currentPage.value,
      itemsPerPage.value,
      sortColumn.value,
      sortOrder.value
    );
    awardedItems.value = response.data;
    totalItems.value = response.totalItems;
  } catch (error) {
    console.error("Error fetching awarded items:", error);
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

const handleSort = (key: string) => {
  const column = awardedItemsColumns.find((col) => col.key === key);
  if (!column || !column.sortable) return;

  if (sortColumn.value === key) {
    sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
  } else {
    sortColumn.value = key;
    sortOrder.value = "desc";
  }

  currentPage.value = 1;
  fetchAwardedItems();
};

const formatDate = (dateString: string): string => {
  const date = parseISO(dateString);
  return format(date, 'MM/dd/yyyy h:mm a');
};

// Watchers
watch(currentPage, fetchAwardedItems);

// Lifecycle hooks
onMounted(fetchAwardedItems);
</script>

<template>
  <section class="container px-4 py-8 mx-auto">
    <h2 class="text-lg font-medium text-gray-800">Awarded Items</h2>
    <p class="mt-1 text-sm text-gray-500">Items you've won in auctions.</p>
    <ReusableTable
      :columns="awardedItemsColumns"
      :items="awardedItems"
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

      <template #winningBidAmount="{ item }">
        ${{ Number(item.amount).toFixed(2) }}
      </template>

      <template #auctionEndDate="{ item }">
        {{ formatDate(item.item.auctionEndTime) }}
      </template>

      <template #billLink="{ item }">
        <a
          :href="item.billUrl"
          target="_blank"
          class="text-blue-600 hover:text-blue-800 cursor-pointer"
        >
          View Bill
        </a>
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