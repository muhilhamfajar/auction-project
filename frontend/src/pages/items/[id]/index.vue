<script lang="ts" setup>
import { ref, inject, onMounted, watch, computed, Ref } from "vue";
import { Bid, Item, ItemWithHighestBid, NewBid } from "@/types/item";
import { useItemStore } from "@/stores/itemStore";
import { useRoute } from "vue-router";
import { AddNotification } from "@/types/notification";
import ItemDetail from "@/components/ItemDetail.vue";
import LoadingTextComponent from "@/components/LoadingTextComponent.vue";
import { useMercure } from "@/compossables/useMercure";
import { useAuthStore } from "@/stores/authStore";

const addNotification = inject("addNotification") as AddNotification;

const route = useRoute();
const itemStore = useItemStore();
const authStore = useAuthStore()
const item = ref<ItemWithHighestBid | null>(null);
const isLoading = ref(true);
const itemUuid = computed(() => route.params.uuid as string);

const getItem = async () => {
  const uuid = route.params.uuid as string;
  isLoading.value = true;
  try {
    const response = await itemStore.fetchItem(uuid);
    item.value = {
      ...response.item,
      highestBid: response.highestBid,
    };
  } catch (error) {
    console.error("Error fetching item:", error);
    addNotification("Failed to fetch item details", "error");
  } finally {
    isLoading.value = false;
  }
};

const handleBidPlaced = (newBid: Bid) => {
  if (item.value) {
    item.value.highestBid = newBid;
  }
};

const updateHighestBid = (item: Ref<ItemWithHighestBid | null>, newBid: NewBid) => {
  if (!item.value) return;

  if (!item.value.highestBid) {
    item.value.highestBid = {
      amount: newBid.amount,
      uuid: newBid.uuid,
      bidder: { uuid: newBid.bidder },
    };
  } else {
    item.value.highestBid.amount = newBid.amount;
    item.value.highestBid.uuid = newBid.uuid;
    item.value.highestBid.bidder = { uuid: newBid.bidder };
  }
};

const handleNewBidNotification = (newBid: NewBid, currentUserUuid: string | undefined) => {
  if (newBid.bidder !== currentUserUuid) {
    addNotification(`New highest bid: $${newBid.amount}`, 'info');
  }
};


onMounted(() => {
  getItem();
});

watch(
  () => route.params.uuid,
  (newUuid) => {
    if (newUuid) {
      getItem();
    }
  }
);

useMercure<NewBid>({
  topic: `item/${itemUuid.value}`,
  onMessage: (data) => {
    updateHighestBid(item, data.newBid);
    handleNewBidNotification(data.newBid, authStore.user?.uuid);
  },
  onError: (error) => {
    console.error("Mercure subscription error:", error);
  },
});
</script>

<template>
  <LoadingTextComponent v-if="isLoading" />
  <ItemDetail @bid-placed="handleBidPlaced" v-else :item="item" />
</template>
