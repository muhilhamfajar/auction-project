<script lang="ts" setup>
import { Bid, Item } from "@/types/item";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import { computed, onMounted, ref, watch } from "vue";
import { format, parseISO } from "date-fns";
import { useBidStore } from "@/stores/bidStore";

const props = defineProps<{
  item: Item;
}>();

const bidStore = useBidStore();

const bids = ref<Array<Bid>>([]);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);

const totalPages = computed(() =>
  Math.ceil(totalItems.value / itemsPerPage.value)
);

const formatDate = (dateString: string): string => {
  const date = parseISO(dateString);
  return format(date, 'MM/dd/yyyy h:mm a');
};

const fetchBidHistory = async () => {
  try {
    const response = await bidStore.fetchByItems(
      props.item.id,
      currentPage.value,
      itemsPerPage.value
    );

    bids.value = response.data;
    totalItems.value = response.totalItems;
  } catch (error) {
    console.error("Error fetching items:", error);
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

onMounted(() => {
  fetchBidHistory();
});

watch(
  () => props.item,
  () => {
    fetchBidHistory();
  },
  { deep: true }
);

watch(currentPage, fetchBidHistory);
</script>

<template>
  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th
          class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
        >
          User
        </th>
        <th
          class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
        >
          Bid Amount
        </th>
        <th
          class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
        >
          Bid At
        </th>
        <th
          class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
        >
          Auto Bid
        </th>
      </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
      <template v-if="bids.length > 0">
        <tr v-for="bid in bids" :key="bid.id">
          <td class="px-6 py-4 whitespace-nowrap">
            {{ bid.bidder?.name }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">${{ bid.amount }}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            {{ formatDate(bid.bidTime) }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            {{ bid.isAutoBid ? "Yes" : "No" }}
          </td>
        </tr>
      </template>
      <tr v-else>
        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No data</td>
      </tr>
    </tbody>
  </table>
  <div class="mt-6 sm:flex sm:items-center sm:justify-between">
    <div class="text-sm text-gray-500">
      Page
      <span class="font-medium text-gray-700"
        >{{ currentPage }} of {{ totalPages }}</span
      >
    </div>

    <div class="flex items-center mt-4 gap-x-4 sm:mt-0">
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
</template>
