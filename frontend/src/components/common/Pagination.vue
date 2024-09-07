<script setup lang="ts">
import { defineProps, defineEmits } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  totalPages: {
    type: Number,
    required: true,
  },
});

defineEmits(["prev", "next"]);
</script>

<template>
  <div class="mt-6 sm:flex sm:items-center sm:justify-between">
    <div class="text-sm text-gray-500">
      Page
      <span class="font-medium text-gray-700"
        >{{ currentPage }} of {{ totalPages }}</span
      >
    </div>

    <div class="flex items-center mt-4 gap-x-4 sm:mt-0">
      <button
        @click="$emit('prev')"
        :disabled="currentPage === 1"
        class="flex items-center justify-center w-1/2 px-5 py-2 text-sm text-gray-700 capitalize transition-colors duration-200 bg-white border rounded-md sm:w-auto gap-x-2 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <ChevronLeft class="w-5 h-5 rtl:-scale-x-100" />
        <span>Previous</span>
      </button>

      <button
        @click="$emit('next')"
        :disabled="currentPage === totalPages"
        class="flex items-center justify-center w-1/2 px-5 py-2 text-sm text-gray-700 capitalize transition-colors duration-200 bg-white border rounded-md sm:w-auto gap-x-2 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span>Next</span>
        <ChevronRight class="w-5 h-5 rtl:-scale-x-100" />
      </button>
    </div>
  </div>
</template>
