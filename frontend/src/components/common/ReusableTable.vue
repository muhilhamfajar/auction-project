<script setup lang="ts">
import { defineProps, defineEmits } from "vue";

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  items: {
    type: Array,
    required: true,
  },
  sortColumn: {
    type: String,
    default: "",
  },
  sortOrder: {
    type: String,
    default: "asc",
  },
  showActions: {
    type: Boolean,
    default: true,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["sort"]);
</script>

<template>
  <div class="flex flex-col mt-6">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
      <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
        <div class="overflow-hidden border border-gray-200 md:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  v-for="column in columns"
                  :key="column.key"
                  @click="$emit('sort', column.key)"
                  class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  :class="{ 'hidden sm:table-cell': column.hideOnMobile,  'cursor-pointer': column.sortable}"
                >
                  {{ column.label }}
                  <span v-if="sortColumn === column.key && column.sortable">
                    {{ sortOrder === "asc" ? "▲" : "▼" }}
                  </span>
                </th>
                <th
                  v-if="showActions"
                  scope="col"
                  class="px-4 py-3.5 text-left text-xs font-medium text-gray-500 uppercase"
                >
                  <span>Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="isLoading">
                <td
                  :colspan="columns.length + (showActions ? 1 : 0)"
                  class="px-4 py-4 text-sm text-gray-500 text-center"
                >
                  <div class="flex justify-center items-center">
                    <svg class="animate-spin h-5 w-5 mr-3 text-gray-500" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                  </div>
                </td>
              </tr>
              <tr v-else-if="items.length === 0">
                <td
                  :colspan="columns.length + (showActions ? 1 : 0)"
                  class="px-4 py-4 text-sm text-gray-500 text-center"
                >
                  No data available
                </td>
              </tr>
              <tr v-for="item in items" :key="item.id" v-else>
                <td
                  v-for="column in columns"
                  :key="column.key"
                  class="px-4 py-4 text-sm text-gray-900 whitespace-nowrap"
                  :class="{ 'hidden sm:table-cell': column.hideOnMobile }"
                >
                  <slot :name="column.key" :item="item">
                    {{ item[column.key] }}
                  </slot>
                </td>
                <td
                  v-if="showActions"
                  class="px-4 py-4 text-sm font-medium text-right whitespace-nowrap"
                >
                  <slot name="actions" :item="item"></slot>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>