<script lang="ts" setup>
import { ref, inject, onMounted, watch } from "vue";
import { Item, ItemMedia } from "@/types/item";
import { useItemStore } from "@/stores/itemStore";
import { useRoute } from "vue-router";
import { AddNotification } from "@/types/notification";
import ItemForm from "@/components/admin/ItemForm.vue";
import LoadingTextComponent from "@/components/LoadingTextComponent.vue";

const addNotification = inject("addNotification") as AddNotification;

const route = useRoute()
const itemStore = useItemStore()
const item = ref<Item | null>(null);
const isLoading = ref(true);


const getItem = async () => {
  const uuid = route.params.uuid as string;
  isLoading.value = true;
  try {
    const response = await itemStore.fetchItem(uuid);
    item.value = {
      ...response.item
    };
  } catch (error) {
    console.error("Error fetching item:", error);
    addNotification("Failed to fetch item details", "error");
  } finally {
    isLoading.value = false;
  }
};


onMounted(() => {
    getItem()
});

watch(() => route.params.uuid, (newUuid) => {
  if (newUuid) {
    getItem();
  }
});

</script>

<template>
    <LoadingTextComponent v-if="isLoading" />
    <ItemForm v-else :item="item" />
</template>
