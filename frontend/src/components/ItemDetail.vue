<script setup lang="ts">
import { useAuthStore } from "@/stores/authStore";
import { useItemStore } from "@/stores/itemStore";
import { Bid, ItemMedia, ItemWithHighestBid } from "@/types/item";
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import BidHistory from "@/components/admin/BidHistory.vue";
import BidForm from "@/components/users/BidForm.vue";

const props = defineProps<{
  item: ItemWithHighestBid;
}>();

const emit = defineEmits<{
  (e: 'bidPlaced', bid: Bid): void
}>()

const itemStore = useItemStore();
const authStore = useAuthStore();

const countdown = ref("");
const isAuctionEnded = ref(false);
const itemMedia = ref<ItemMedia | null>();
const imageBaseUrl = ref("");

const isAdmin = computed(() => authStore.isAdmin);

const updateCountdown = () => {
  const now = new Date();
  const auctionEndTime = new Date(props.item.auctionEndTime);
  const timeLeft = auctionEndTime.getTime() - now.getTime();

  if (timeLeft <= 0) {
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
  emit('bidPlaced', newBid)
}


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
            <div
              class="uppercase tracking-wide text-sm text-indigo-500 font-semibold"
            >
              Item #{{ item.id }}
            </div>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">
              {{ item.name }}
            </h1>
            <p class="mt-8 text-lg font-bold text-gray-600">
              Starting Price: ${{ item.startingPrice }}
            </p>
            <p class="mt-2 text-lg font-bold text-gray-600">
              Highest Bid: 
              <span v-if="item.highestBid">
                ${{ Number(item.highestBid.amount).toFixed(2) }}
              </span>
              <span v-else> No bids yet </span>
            </p>
            <div class="mt-8 text-blue-600 font-semibold">
              <p>Time left:</p>
              <p class="text-2xl">{{ countdown }}</p>
            </div>

            <div class="mt-8">
              <h2 class="text-xl font-bold text-gray-900">Description</h2>
              <p class="mt-2 text-gray-600">{{ item.description }}</p>
            </div>

            <!-- Admin View -->
            <div v-if="isAdmin" class="mt-6">
              <h2 class="text-xl font-bold text-gray-900 mb-4">Bid History</h2>
              <BidHistory :item="props.item" />
            </div>

            <!-- User View -->
            <div v-else class="mt-6">
              <h2 class="text-xl font-bold text-gray-900">Place Your Bid</h2>

              <BidForm @bid-placed="handleBidPlaced" :item="item" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
