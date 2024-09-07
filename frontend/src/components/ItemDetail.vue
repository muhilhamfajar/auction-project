<script setup lang="ts">
import { useAuthStore } from "@/stores/authStore";
import { useItemStore } from "@/stores/itemStore";
import { Bid, ItemMedia, ItemWithHighestBid } from "@/types/item";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import BidHistory from "@/components/admin/BidHistory.vue";
import BidForm from "@/components/users/BidForm.vue";
import { Crown, ChevronDown, ChevronUp } from "lucide-vue-next";
import {
  CollapsibleContent,
  CollapsibleRoot,
  CollapsibleTrigger,
} from "radix-vue";

const props = defineProps<{
  item: ItemWithHighestBid;
}>();

const emit = defineEmits<{
  (e: "bidPlaced", bid: Bid): void;
}>();

const itemStore = useItemStore();
const authStore = useAuthStore();

const countdown = ref("");
const isAuctionEnded = ref(false);
const itemMedia = ref<ItemMedia | null>();
const imageBaseUrl = ref("");
const fullBidHistory = ref(false);

const isAdmin = computed(() => authStore.isAdmin);

const isCurrentUserLeading = computed(() => {
  return props.item.highestBid?.bidder?.uuid === authStore.user?.uuid;
});

const isCurrentUserWinner = computed(() => {
  return isAuctionEnded.value && isCurrentUserLeading.value;
});

const updateCountdown = () => {
  const now = new Date();
  const auctionEndTime = new Date(props.item.auctionEndTime);
  const timeLeft = auctionEndTime.getTime() - now.getTime();

  if (timeLeft <= 0 || props.item.status === 2) {
    countdown.value = "Auction ended";
    isAuctionEnded.value = true;
    return;
  }

  const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
  const hours = Math.floor(
    (timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
  );
  const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

  countdown.value = `${days}d ${hours}h ${minutes}m ${seconds}s`;
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

const loadExistingImage = () => {
  if (!props.item?.medias?.length) {
    return;
  }

  itemMedia.value = props.item.medias[0];
  fetchMediaBaseUrl();
};

const handleBidPlaced = (newBid: Bid) => {
  emit("bidPlaced", newBid);
};

let countdownInterval: number;

onMounted(() => {
  updateCountdown();
  countdownInterval = setInterval(updateCountdown, 1000);
  loadExistingImage();
});

onUnmounted(() => {
  clearInterval(countdownInterval);
});

watch(
  () => props.item,
  () => {
    loadExistingImage();
  },
  { deep: true }
);
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="md:flex">
          <div class="md:flex-shrink-0 md:w-1/2">
            <img
              class="w-full h-64 object-cover md:h-full"
              :src="`${imageBaseUrl}/${itemMedia?.name}`"
              :alt="item.name"
            />
          </div>
          <div class="p-8 md:w-1/2">
            <div class="uppercase tracking-wide text-sm text-indigo-500 font-semibold">
              Item #{{ item.id }}
            </div>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">
              {{ item.name }}
            </h1>
            
            <!-- Bidding Information -->
            <div class="mt-6 bg-blue-50 p-6 rounded-lg border-2 border-blue-200">
              <div class="flex justify-between items-center mb-4">
                <span class="text-lg text-gray-600">Starting Price:</span>
                <span class="text-xl font-bold text-gray-800">
                  ${{ Number(item.startingPrice).toFixed(2) }}
                </span>
              </div>
              
              <div class="bg-white p-4 rounded-lg shadow-md">
                <div class="flex justify-between items-center">
                  <span class="text-xl font-semibold text-gray-700">Current Bid:</span>
                  <span v-if="item.highestBid" class="text-3xl font-bold text-green-600">
                    ${{ Number(item.highestBid.amount).toFixed(2) }}
                  </span>
                  <span v-else class="text-3xl font-bold text-grey-600">
                    -
                  </span>
                </div>
                <div class="mt-2 text-right">
                  <template v-if="isAuctionEnded">
                    <span v-if="isCurrentUserWinner" class="text-blue-600 font-semibold">
                      <Trophy class="h-5 w-5 inline mr-2" />
                      Congratulations! You won this auction!
                    </span>
                    <span v-else class="text-gray-600">
                      <User class="h-5 w-5 inline mr-2" />
                      Winner: {{ item.highestBid?.bidder?.username }}
                    </span>
                  </template>
                  <template v-else-if="isCurrentUserLeading">
                    <span class="text-blue-600 font-semibold">
                      <Crown class="h-5 w-5 inline mr-2" />
                      You're in the lead!
                    </span>
                  </template>
                </div>
              </div>
              
              <div class="mt-4 bg-indigo-100 p-4 rounded-lg">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-semibold text-indigo-800">Time left:</span>
                  <span v-if="!isAuctionEnded" class="text-xl font-bold text-indigo-600">
                    {{ countdown }}
                  </span>
                  <span v-else class="text-xl font-bold text-red-600">
                    Auction ended
                  </span>
                </div>
              </div>
            </div>

            <div class="mt-8">
              <h2 class="text-xl font-bold text-gray-900">Description</h2>
              <p class="mt-2 text-gray-600">{{ item.description }}</p>
            </div>

            <!-- User View -->
            <div v-if="!isAdmin && !isAuctionEnded" class="mt-6">
              <h2 class="text-xl font-bold text-gray-900">Place Your Bid</h2>
              <BidForm @bid-placed="handleBidPlaced" :item="item" />
            </div>

            <!-- Bid History section -->
            <div class="mt-6">
              <CollapsibleRoot v-if="!isAdmin && !isAuctionEnded" v-model:open="fullBidHistory" class="w-full">
                <div class="flex items-center justify-between">
                  <h2 class="text-xl font-bold text-gray-900">Bid History</h2>
                  <CollapsibleTrigger class="cursor-pointer rounded-full h-[25px] w-[25px] inline-flex items-center justify-center text-gray-600 hover:bg-gray-100 focus:outline-none">
                    <ChevronDown v-if="!fullBidHistory" class="h-5 w-5" />
                    <ChevronUp v-else class="h-5 w-5" />
                  </CollapsibleTrigger>
                </div>
                <CollapsibleContent class="data-[state=open]:animate-slideDown data-[state=closed]:animate-slideUp overflow-hidden">
                  <div class="mt-4">
                    <BidHistory :item="item" />
                  </div>
                </CollapsibleContent>
              </CollapsibleRoot>

              <div v-else>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Bid History</h2>
                <BidHistory :item="item" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>