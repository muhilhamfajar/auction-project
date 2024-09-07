<script setup lang="ts">
import CurrentBidsTable from "@/components/users/CurrentBidsTable.vue";
import BidHistoryTable from "@/components/users/BidHistoryTable.vue";
import AwardedItemsTable from "@/components/users/AwardedItemsTable.vue";
import {
  GavelIcon,
  HistoryIcon,
  TrophyIcon,
  ChevronDown,
  ChevronUp,
} from "lucide-vue-next";
import {
  CollapsibleContent,
  CollapsibleRoot,
  CollapsibleTrigger,
} from "radix-vue";
import { ref } from "vue";

const currentBidsOpen = ref(true);
const bidHistoryOpen = ref(true);
const awardedItemsOpen = ref(true);
</script>

<template>
  <div class="min-h-screen container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-8">User Profile</h1>

    <div class="space-y-8">
      <CollapsibleRoot
        v-model:open="currentBidsOpen"
        class="bg-blue-50 rounded-lg overflow-hidden border border-blue-100"
      >
        <div
          class="px-6 py-4 flex items-center justify-between border-b border-blue-100"
        >
          <div class="flex items-center">
            <GavelIcon class="w-6 h-6 text-blue-500 mr-2" />
            <h2 class="text-xl font-semibold text-blue-700">Current Bids</h2>
          </div>
          <CollapsibleTrigger
            class="cursor-pointer rounded-full h-[25px] w-[25px] inline-flex items-center justify-center text-blue-600 hover:bg-blue-100 focus:outline-none"
          >
            <ChevronDown v-if="!currentBidsOpen" class="h-5 w-5" />
            <ChevronUp v-else class="h-5 w-5" />
          </CollapsibleTrigger>
        </div>
        <CollapsibleContent
          class="data-[state=open]:animate-slideDown data-[state=closed]:animate-slideUp overflow-hidden"
        >
          <CurrentBidsTable />
        </CollapsibleContent>
      </CollapsibleRoot>

      <CollapsibleRoot
        v-model:open="bidHistoryOpen"
        class="bg-green-50 rounded-lg overflow-hidden border border-green-100"
      >
        <div
          class="px-6 py-4 flex items-center justify-between border-b border-green-100"
        >
          <div class="flex items-center">
            <HistoryIcon class="w-6 h-6 text-green-500 mr-2" />
            <h2 class="text-xl font-semibold text-green-700">Bid History</h2>
          </div>
          <CollapsibleTrigger
            class="cursor-pointer rounded-full h-[25px] w-[25px] inline-flex items-center justify-center text-green-600 hover:bg-green-100 focus:outline-none"
          >
            <ChevronDown v-if="!bidHistoryOpen" class="h-5 w-5" />
            <ChevronUp v-else class="h-5 w-5" />
          </CollapsibleTrigger>
        </div>
        <CollapsibleContent
          class="data-[state=open]:animate-slideDown data-[state=closed]:animate-slideUp overflow-hidden"
        >
          <BidHistoryTable />
        </CollapsibleContent>
      </CollapsibleRoot>

      <CollapsibleRoot
        v-model:open="awardedItemsOpen"
        class="bg-purple-50 rounded-lg overflow-hidden border border-purple-100"
      >
        <div
          class="px-6 py-4 flex items-center justify-between border-b border-purple-100"
        >
          <div class="flex items-center">
            <TrophyIcon class="w-6 h-6 text-purple-500 mr-2" />
            <h2 class="text-xl font-semibold text-purple-700">Awarded Items</h2>
          </div>
          <CollapsibleTrigger
            class="cursor-pointer rounded-full h-[25px] w-[25px] inline-flex items-center justify-center text-purple-600 hover:bg-purple-100 focus:outline-none"
          >
            <ChevronDown v-if="!awardedItemsOpen" class="h-5 w-5" />
            <ChevronUp v-else class="h-5 w-5" />
          </CollapsibleTrigger>
        </div>
        <CollapsibleContent
          class="data-[state=open]:animate-slideDown data-[state=closed]:animate-slideUp overflow-hidden"
        >
          <AwardedItemsTable />
        </CollapsibleContent>
      </CollapsibleRoot>
    </div>
  </div>
</template>
