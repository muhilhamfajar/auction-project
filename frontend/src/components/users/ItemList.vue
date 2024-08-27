<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import {
  differenceInDays,
  differenceInHours,
  differenceInMinutes,
  isPast,
} from "date-fns";
import { Item, ItemWithHighestBid } from "@/types/item";
import { useItemStore } from "@/stores/itemStore";
import { useRouter } from "vue-router";
import debounce from "lodash/debounce";

const itemStore = useItemStore();
const router = useRouter();

const currentPage = ref(1);
const totalItems = ref(0);
const itemsPerPage = ref(8);
const isDropdownOpen = ref(false);
const items = ref<Array<ItemWithHighestBid>>([]);
const currentSort = ref("newAdded");
const searchQuery = ref("");
const imageBaseUrl = ref("");
const isLoading = ref(false);

const sortOptions = {
  newAdded: "New Added",
  // highestBid: "Highest Bid",
  // lowestBid: "Lowest Bid",
  highestStartingPrice: "Highest Starting Price",
  lowestStartingPrice: "Lowest Starting Price",
  longestTimeLeft: "Longest Time Left",
};

const getSortParams = (sortOption: string): { sortBy: string, orderBy: string } => {
  switch (sortOption) {
    case 'newAdded':
      return { sortBy: 'createdAt', orderBy: 'DESC' };
    // case 'highestBid':
    //   return { sortBy: 'highestBid', orderBy: 'DESC' };
    // case 'lowestBid':
    //   return { sortBy: 'highestBid', orderBy: 'ASC' };
    case 'highestStartingPrice':
      return { sortBy: 'startingPrice', orderBy: 'DESC' };
    case 'lowestStartingPrice':
      return { sortBy: 'startingPrice', orderBy: 'ASC' };
    case 'longestTimeLeft':
      return { sortBy: 'auctionEndTime', orderBy: 'DESC' };
    default:
      return { sortBy: 'createdAt', orderBy: 'DESC' };
  }
};

const totalPages = computed(() =>
  Math.ceil(totalItems.value / itemsPerPage.value)
);

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
};

const getTimeLeft = (endTime: Date): string => {
  if (isPast(endTime)) {
    return "Auction ended";
  }

  const daysLeft = differenceInDays(endTime, new Date());
  const hoursLeft = differenceInHours(endTime, new Date()) % 24;
  const minutesLeft = differenceInMinutes(endTime, new Date()) % 60;

  if (daysLeft > 0) {
    return `${daysLeft} day${daysLeft > 1 ? "s" : ""}, ${hoursLeft} hour${hoursLeft > 1 ? "s" : ""} left`;
  } else if (hoursLeft > 0) {
    return `${hoursLeft} hour${hoursLeft > 1 ? "s" : ""}, ${minutesLeft} minute${minutesLeft > 1 ? "s" : ""} left`;
  } else {
    return `${minutesLeft} minute${minutesLeft > 1 ? "s" : ""} left`;
  }
};

const isAuctionEnded = (endTime: Date): boolean => {
  return isPast(endTime);
};

const placeBid = (item: Item): void => {
  router.push(`/items/${item.uuid}`);
};

const fetchItems = async () => {
  isLoading.value = true
  try {
    const { sortBy, orderBy } = getSortParams(currentSort.value);

    const response = await itemStore.fetchItems(
      searchQuery.value,
      currentPage.value,
      itemsPerPage.value,
      sortBy,
      orderBy
    );
    items.value = response.data.map((item) => ({
      ...item.item,
      highestBid: item.highestBid,
    }));

    totalItems.value = response.totalItems;
  } catch (error) {
    console.error("Error fetching items:", error);
  } finally {
    isLoading.value = false
  }
};

const fetchMediaBaseUrl = async () => {
  try {
    const response = await itemStore.getItemMediaBaseUrl();
    imageBaseUrl.value = response.baseUrl;
  } catch (error) {
    console.error("Error fetching media base URL:", error);
    return null;
  }
};

const getImageSrc = (item: Item): string => {
  if (item.medias && item.medias.length > 0 && item.medias[0].name) {
    return `${imageBaseUrl.value}/${item.medias[0].name}`;
  }
  return "";
};

const debouncedSearch = debounce(() => {
  currentPage.value = 1;
  fetchItems();
}, 300);

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

watch([currentPage, currentSort], fetchItems);

onMounted(fetchItems);
onMounted(() => {
  fetchItems();
  fetchMediaBaseUrl();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
      <div
        class="flex flex-col md:flex-row items-center justify-center gap-4 mb-8"
      >
        <!-- Search Form -->
        <form @submit.prevent="onSubmit" class="w-full max-w-2xl">
          <label
            for="default-search"
            class="mb-2 text-sm font-medium text-gray-900 sr-only"
            >Search</label
          >
          <div class="relative">
            <div
              class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none"
            >
              <svg
                class="w-4 h-4 text-gray-500"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 20 20"
              >
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                />
              </svg>
            </div>
            <input
              type="search"
              id="default-search"
              v-model="searchQuery"
              @input="debouncedSearch"
              class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-white focus:ring-blue-500 focus:border-blue-500"
              placeholder="Search items..."
              required
            />
          </div>
        </form>

        <!-- Sort Dropdown -->
        <div class="relative w-full md:w-auto">
          <button
            @click="toggleDropdown"
            class="w-full md:w-auto text-white bg-blue-500 hover:bg-blue-600 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center inline-flex items-center justify-center"
            type="button"
          >
            Sort by:
            <svg
              class="w-2.5 h-2.5 ms-3"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 10 6"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="m1 1 4 4 4-4"
              />
            </svg>
          </button>
          <div
            v-show="isDropdownOpen"
            class="absolute right-0 z-10 w-56 mt-2 bg-white divide-y divide-gray-100 rounded-lg shadow"
          >
            <ul class="p-3 space-y-3 text-sm text-gray-700">
              <li v-for="(label, value) in sortOptions" :key="value">
                <div class="flex items-center">
                  <input
                    :id="'sort-' + value"
                    type="radio"
                    :value="value"
                    v-model="currentSort"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2"
                  />
                  <label
                    :for="'sort-' + value"
                    class="ms-2 text-sm font-medium text-gray-900"
                    >{{ label }}</label
                  >
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="items.length === 0 && !isLoading" class="text-center py-10">
        <h3 class="mt-10 text-sm font-medium text-gray-900">No items available for bidding at the moment. Check back soon!</h3>
      </div>

      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"
      >
        <div
          v-for="item in items"
          :key="item.id"
          class="max-w-xs rounded-lg bg-white p-4 shadow"
        >
          <img
            class="w-full h-48 rounded-lg object-cover object-center mb-4"
            :src="getImageSrc(item)"
            :alt="item.name"
          />
          <h3 class="text-lg font-bold text-gray-800 mb-2">{{ item.name }}</h3>
          <p class="text-sm text-gray-600 mb-2">
            Starting Price: ${{ item.startingPrice }}
          </p>
          <p class="text-sm text-gray-600 mb-2">
            Highest Bid:
            <span v-if="item.highestBid"> ${{ item.highestBid.amount }} </span>
            <span v-else> No bids yet </span>
          </p>
          <p class="text-sm text-blue-600 mb-4">
            {{ getTimeLeft(item.auctionEndTime) }}
          </p>
          <div class="flex justify-between items-center">
            <button
              @click="placeBid(item)"
              class="text-white font-bold py-2 px-4 rounded"
              :class="{
                'bg-blue-500 hover:bg-blue-600 text-white': !isAuctionEnded(
                  item.auctionEndTime
                ),
                'bg-gray-400 text-gray-200 cursor-not-allowed': isAuctionEnded(
                  item.auctionEndTime
                ),
              }"
              :disabled="isAuctionEnded(item.auctionEndTime)"
            >
              Place Bid
            </button>
            <span class="text-lg font-semibold text-gray-800"
              >${{ item.highestBid?.amount || item.startingPrice }}</span
            >
          </div>
        </div>
      </div>

      <div v-if="items.length" class="mt-6 sm:flex sm:items-center sm:justify-center">
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
    </div>
  </div>
</template>
