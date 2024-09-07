<script setup lang="ts">
import { ref, computed, onMounted, watch, h } from "vue";
import { useItemStore } from "@/stores/itemStore";
import DeleteConfirmationModal from "@/components/common/DeleteConfirmationModal.vue";
import {
  Search,
  Plus,
} from "lucide-vue-next";
import {
  differenceInDays,
  differenceInHours,
  differenceInMinutes,
  isPast,
  format,
  parseISO,
} from "date-fns";
import { Item } from "@/types/item";
import debounce from "lodash/debounce";
import { useRouter } from "vue-router";
import ItemActionDropdown from "@/components/common/ItemActionDropdown.vue";
import ReusableTable from "@/components/common/ReusableTable.vue";
import Pagination from "@/components/common/Pagination.vue";

const itemStore = useItemStore();

// Local state
const currentPage = ref(1);
const itemsPerPage = ref(10);
const totalItems = ref(0);
const items = ref<Array<Item>>([] as Item[]);
const searchQuery = ref("");
const sortColumn = ref("id");
const sortOrder = ref<"asc" | "desc">("desc");
const showDeleteModal = ref(false);
const itemToDelete = ref<Item | null>(null);
const router = useRouter();

const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'status', label: 'Status', sortable: false, hideOnMobile: true },
  { key: 'startingPrice', label: 'Starting Price', sortable: true },
  { key: 'auctionEndTime', label: 'Time Left', sortable: true },
  { key: 'updatedAt', label: 'Last Update', sortable: true, hideOnMobile: true },
];


// Computed properties
const totalPages = computed(() =>
  Math.ceil(totalItems.value / itemsPerPage.value)
);

// Methods
const fetchItems = async () => {
  try {
    const response = await itemStore.fetchItems(
      searchQuery.value,
      currentPage.value,
      itemsPerPage.value,
      sortColumn.value,
      sortOrder.value
    );
    items.value = response.data.map((item) => ({
      ...item.item,
    }));
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

const getTimeLeft = (endTime: string): string => {
  const end = new Date(endTime);
  if (isPast(end)) {
    return "Auction ended";
  }

  const now = new Date();
  const daysLeft = differenceInDays(end, now);
  const hoursLeft = differenceInHours(end, now) % 24;
  const minutesLeft = differenceInMinutes(end, now) % 60;

  if (daysLeft > 0) {
    return `${daysLeft} day${daysLeft > 1 ? "s" : ""}, ${hoursLeft} hour${hoursLeft > 1 ? "s" : ""} left`;
  } else if (hoursLeft > 0) {
    return `${hoursLeft} hour${hoursLeft > 1 ? "s" : ""}, ${minutesLeft} minute${minutesLeft > 1 ? "s" : ""} left`;
  } else {
    return `${minutesLeft} minute${minutesLeft > 1 ? "s" : ""} left`;
  }
};

const getStatus = (status: number, endTime: string): string => {
  return isPast(new Date(endTime)) || status === 2 ? "Closed" : "Open";
};

const getStatusClass = (status: number, endTime: string): string => {
  return isPast(new Date(endTime)) || status === 2
    ? "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
    : "px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800";
};

const debouncedSearch = debounce(() => {
  currentPage.value = 1;
  fetchItems();
}, 300);

const handleSort = (key: string) => {
  const column = columns.find(col => col.key === key);
  if (!column || !column.sortable) return;

  if (sortColumn.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = key;
    sortOrder.value = 'asc';
  }

  currentPage.value = 1;
  fetchItems();
};

const openDeleteModal = (item: Item) => {
  itemToDelete.value = item;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  itemToDelete.value = null;
};

const confirmDelete = async () => {
  if (itemToDelete.value) {
    try {
      await itemStore.deleteItem(itemToDelete.value.uuid);
      fetchItems();
      closeDeleteModal();
    } catch (error) {
      console.error("Error deleting item:", error);
    }
  }
};

const formatCreatedAt = (dateString: string): string => {
  const date = parseISO(dateString);
  return format(date, "MMM d, yyyy HH:mm");
};

const editItem = (item: Item) => {
  router.push(`/items/${item.uuid}/edit`);
};

// Watchers
watch(currentPage, fetchItems);

// Lifecycle hooks
onMounted(fetchItems);
</script>


<template>
  <section class="container px-4 py-8 mx-auto min-h-screen">
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-x-3">
          <h2 class="text-lg font-medium text-gray-800">Auction Items</h2>
          <span class="px-3 py-1 text-xs text-blue-600 bg-blue-100 rounded-full"
            >{{ totalItems }} items</span
          >
        </div>
        <p class="mt-1 text-sm text-gray-500">
          A list of all the items currently up for auction.
        </p>
      </div>
    </div>

    <div class="mt-6 md:flex md:items-center md:justify-between">
      <div class="relative flex items-center mt-4 md:mt-0">
        <span class="absolute">
          <Search class="w-5 h-5 mx-3 text-gray-400" />
        </span>
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Search items"
          class="block w-full py-1.5 pr-5 text-gray-700 bg-white border border-gray-200 rounded-lg md:w-80 placeholder-gray-400/70 pl-11 rtl:pr-11 rtl:pl-5 focus:border-blue-400 focus:ring-blue-300 focus:outline-none focus:ring focus:ring-opacity-40"
        />
      </div>

      <div class="flex items-center mt-4 gap-x-4 sm:mt-0">
        <button
          @click="router.push('/items/add')"
          class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer"
        >
          <Plus class="w-5 h-5 rtl:-scale-x-100" />
          <span>Add Item</span>
        </button>
      </div>
    </div>

    <ReusableTable
      :columns="columns"
      :items="items"
      :sortColumn="sortColumn"
      :sortOrder="sortOrder"
      @sort="handleSort"
    >
      Custom slots for each column
      <template #id="{ item }">
        {{ item.id }}
      </template>

      <template #name="{ item }">
        {{ item.name }}
      </template>

      <template #status="{ item }">
        <span :class="getStatusClass(item.status, item.auctionEndTime)">
          {{ getStatus(item.status, item.auctionEndTime) }}
        </span>
      </template>

      <template #startingPrice="{ item }">
        ${{ item.startingPrice }}
      </template>

      <template #auctionEndTime="{ item }">
        {{ getTimeLeft(item.auctionEndTime) }}
      </template>

      <template #updatedAt="{ item }">
        {{ formatCreatedAt(item.updatedAt) }}
      </template>

      <template #actions="{ item }">
        <ItemActionDropdown
          :item="item"
          :onView="() => router.push(`/items/${item.uuid}`)"
          :onEdit="() => editItem(item)"
          :onDelete="() => openDeleteModal(item)"
        />
      </template>
    </ReusableTable>

    <Pagination
      :currentPage="currentPage"
      :totalPages="totalPages"
      @prev="prevPage"
      @next="nextPage"
    />

    <DeleteConfirmationModal
      :show="showDeleteModal"
      @confirm="confirmDelete"
      @cancel="closeDeleteModal"
    />
  </section>
</template>