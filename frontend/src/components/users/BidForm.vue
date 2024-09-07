<script lang="ts" setup>
import {
  AutoBidConfig,
  Bid,
  ItemWithHighestBid,
  UserAutoBid,
} from "@/types/item";
import { computed, inject, onMounted, ref } from "vue";
import { Info } from "lucide-vue-next";
import { useBidStore } from "@/stores/bidStore";
import { useAuthStore } from "@/stores/authStore";
import { AddNotification } from "@/types/notification";

const props = defineProps<{
  item: ItemWithHighestBid;
}>();

const emit = defineEmits<{
  (e: "bidPlaced", bid: Bid): void;
}>();

const bidStore = useBidStore();
const userStore = useAuthStore();
const addNotification = inject("addNotification") as AddNotification;

const amount = ref(0);
const isAutoBid = ref(false);
const isSubmitting = ref(false);
const latestBid = ref<Bid | null>(null);
const autoBiddingConfig = ref<AutoBidConfig | null>(null);
const userAutoBid = ref<UserAutoBid | null>(null);
const isLoading = ref(false);

const minimumBid = computed(() => {
  return props.item.highestBid
    ? Number(props.item.highestBid.amount) + 1
    : Number(props.item.startingPrice);
});

const isAuctionEnded = computed(() => {
  const now = new Date();
  const auctionEndTime = new Date(props.item.auctionEndTime);
  const timeLeft = auctionEndTime.getTime() - now.getTime();

  return timeLeft <= 0 || props.item.status === 2;
});

const activateAutoBid = async () => {
  try {
    const form = {
      user: userStore.user?.uuid,
      item: props.item.uuid,
    };
    const resp = await bidStore.activateAutoBid(form);
    userAutoBid.value = resp;
  } catch (error) {
    console.error("Error on activate auto bid:", error);
  }
};

const deactivateAutoBid = async () => {
  isSubmitting.value = true;
  try {
    await bidStore.deactivateAutoBid(userAutoBid.value?.uuid);
    userAutoBid.value = null;
  } catch (error) {
    console.error("Error on activate auto bid:", error);
  } finally {
    isSubmitting.value = false;
  }
};

const submitBid = async () => {
  isSubmitting.value = true;

  try {
    const form = {
      bidder: userStore.user?.uuid,
      item: props.item.uuid,
      bidTime: new Date().toISOString(),
      amount: amount.value,
      isAutoBid: isAutoBid.value,
    };

    const bid = await bidStore.placeBid(form);

    emit("bidPlaced", bid);
    latestBid.value = bid;

    if (isAutoBid.value) {
      await activateAutoBid();
    }

    addNotification("🎉 Your bid has been successfully set!", "success");
  } catch (error) {
    console.error("Error place bid:", error);
  } finally {
    isSubmitting.value = false;
  }
};

const getLatestBid = async () => {
  try {
    const resp = await bidStore.getUserLatestBid(
      props.item.id,
      userStore.user?.id
    );
    latestBid.value = resp ?? null;
  } catch (error) {
    console.error("Error get latest bid:", error);
  }
};

const getAutoBidConfig = async () => {
  try {
    autoBiddingConfig.value = await bidStore.getAutoBidConfig();
  } catch (error) {
    console.error("Error get auto bid config: ", error);
  }
};

const checkAutoBidStatus = async () => {
  try {
    const resp = await bidStore.getUserAutoBidForItem(
      props.item.id,
      userStore.user?.id
    );
    if (resp) {
      userAutoBid.value = resp;
      isAutoBid.value = true;
    } else {
      userAutoBid.value = null;
      isAutoBid.value = false;
    }
  } catch (error) {
    console.error("Error check user auto bid for this item: ", error);
  }
};

onMounted(async () => {
  isLoading.value = true;
  try {
    await Promise.all([
      getLatestBid(),
      getAutoBidConfig(),
      checkAutoBidStatus(),
    ]);
  } catch (error) {
    console.error("Error loading data:", error);
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <form v-if="!isLoading" @submit.prevent="submitBid" class="mt-4">
    <div class="mb-4">
      <div className="flex justify-between items-center mb-2">
        <label
          htmlFor="amount"
          className="block text-gray-700 text-sm font-bold"
        >
          Bid Amount
        </label>
        <div v-if="latestBid" class="flex items-center mt-2">
          <span
            :class="[
              'flex items-center text-sm',
              props.item.highestBid?.bidder?.uuid === userStore.user?.uuid
                ? 'text-green-600'
                : 'text-gray-700',
            ]"
          >
            <Info
              class="h-4 w-4 mr-2"
              :class="
                props.item.highestBid?.bidder?.uuid === userStore.user?.uuid
                  ? 'text-green-500'
                  : 'text-gray-400'
              "
            />
            Your latest bid: ${{ latestBid.amount }}
            <span
              v-if="props.item.highestBid?.bidder?.uuid === userStore.user?.uuid"
              class="ml-1"
              aria-label="Highest bid"
              >🔥</span
            >
          </span>
        </div>
      </div>
      <input
        v-if="!userAutoBid"
        type="number"
        id="amount"
        v-model="amount"
        :min="minimumBid"
        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
        required
        :disabled="isAuctionEnded"
      />
    </div>
    <div v-if="!isAuctionEnded">
      <div v-if="userAutoBid" class="bg-blue-100 p-4 rounded-lg shadow-sm mb-4">
        <h3 class="text-lg font-semibold mb-2">
          🌟 Your Auto-Bidding is Now Live! 🌟
        </h3>
        <p class="mb-4">
          Congratulations! 🎉 Auto-bidding is active and working its magic.
          Enjoy hands-free bidding and let our advanced technology optimize your
          strategy while you sit back and relax.
        </p>
      </div>
      <div v-else-if="autoBiddingConfig" class="mb-4">
        <label class="flex items-center">
          <input
            type="checkbox"
            v-model="isAutoBid"
            class="form-checkbox h-5 w-5 text-blue-600"
            :disabled="isAuctionEnded"
          />
          <span class="ml-2 text-gray-700">Enable Auto-bidding 🤖</span>
        </label>
      </div>
      <div v-else class="bg-blue-100 p-4 rounded-lg shadow-sm mb-4">
        <h3 class="text-lg font-semibold mb-2">
          🌟 Ready to Boost Your Bidding Game? 🌟
        </h3>
        <p class="mb-4">
          Want to make the most out of your bidding without lifting a finger? 🤖
          Set up your auto-bidding bot now and let technology do the work for
          you!
        </p>
        <router-link
          :to="{
            path: '/account/auto-bid',
            query: { item: props.item.uuid },
          }"
          class="text-blue-600 hover:underline cursor-pointer"
        >
          Set up now
        </router-link>
      </div>
    </div>

    <button
      v-if="userAutoBid"
      type="button"
      :disabled="isAuctionEnded || isSubmitting"
      class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      :class="{
        'opacity-50 cursor-not-allowed': isAuctionEnded || isSubmitting,
      }"
      @click="deactivateAutoBid"
    >
      {{ isSubmitting ? "Deactivating..." : "Deactivate Auto-Bid" }}
    </button>
    <button
      v-else
      type="submit"
      :disabled="isAuctionEnded || isSubmitting"
      class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      :class="{
        'opacity-50 cursor-not-allowed': isSubmitting || isAuctionEnded,
      }"
    >
      {{ isSubmitting ? "Processing Your Bid..." : "Bid Now" }}
    </button>
  </form>
</template>
