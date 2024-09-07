<script lang="ts" setup>
import { ref, computed, inject, onMounted, watch } from "vue";
import { useForm } from "vee-validate";
import * as yup from "yup";
import { Item, ItemMedia } from "@/types/item";
import { Upload } from "lucide-vue-next";
import { useItemStore } from "@/stores/itemStore";
import { useRouter } from "vue-router";
import { AddNotification } from "@/types/notification";
import { parseISO, formatISO, format } from 'date-fns';

const props = defineProps<{
  item?: Item;
}>();

const router = useRouter();
const itemStore = useItemStore();
const addNotification = inject("addNotification") as AddNotification;

const schema = yup.object().shape({
  name: yup
    .string()
    .required("Name is required")
    .max(255, "Name must be at most 255 characters"),
  description: yup.string().nullable(),
  startingPrice: yup
    .number()
    .required("Starting price is required")
    .positive("Starting price must be positive")
    .typeError("Starting price must be a number"),
  auctionEndTime: yup
    .date()
    .required("Auction end time is required")
    .min(new Date(), "Auction end time must be in the future"),
});

const { handleSubmit, errors, values, defineField } = useForm({
  validationSchema: schema,
  initialValues: {
    name: props.item?.name ?? "",
    description: props.item?.description ?? "",
    startingPrice: props.item?.startingPrice ?? "",
    auctionEndTime: props.item?.auctionEndTime
      ? format(new Date(props.item.auctionEndTime), "yyyy-MM-dd'T'HH:mm")
      : "",
  },
});

const [name, nameAttrs] = defineField("name");
const [description, descriptionAttrs] = defineField("description");
const [startingPrice, startingPriceAttrs] = defineField("startingPrice");
const [auctionEndTime, auctionEndTimeAttrs] = defineField("auctionEndTime");

const mediaName = ref("");
const mediaCaption = ref("");
const imageFile = ref<File | null>(null);
const imagePreview = ref<string | null>(null);
const imageError = ref<string | null>(null);
const isSubmitting = ref(false);
const existingImageUrl = ref<string | null>(null);
const itemMedia = ref<ItemMedia | null>(null);


const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    if (file.size > 1024 * 1024) {
      imageError.value = "Image size should not exceed 1MB";
      return;
    }
    if (!["image/jpeg", "image/png"].includes(file.type)) {
      imageError.value = "Only JPEG and PNG images are allowed";
      return;
    }
    imageFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
    imageError.value = null;
  }
};

const handleItemMedia = async (item: Item) => {
  if (imageFile.value) {
    const itemMediaData = {
      item: item.uuid,
      name: mediaName.value,
      caption: mediaCaption.value,
      imageFile: imageFile.value,
    };

    if (props.item && itemMedia.value) {
      await itemStore.updateItemMedia(itemMedia.value.uuid, itemMediaData);
    } else {
      await itemStore.uploadItemMedia(itemMediaData);
    }
  }
};

const onSubmit = handleSubmit(async (formValues) => {
  isSubmitting.value = true;

  const localAuctionEndTime = parseISO(formValues.auctionEndTime);

  try {
    const itemData = {
      name: formValues.name,
      description: formValues.description,
      startingPrice: formValues.startingPrice,
      auctionEndTime: formatISO(localAuctionEndTime)
    };

    let item;
    if (props.item) {
      item = await itemStore.updateItem(props.item.uuid, itemData);
    } else {
      item = await itemStore.createItem(itemData);
    }

    await handleItemMedia(item);

    addNotification("Item successfully created!", "success");
    router.push("/dashboard");
  } catch (error) {
    console.error("Error submitting form:", error);
  } finally {
    isSubmitting.value = false;
  }
});

const formattedStartingPrice = computed(() => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(Number(values.startingPrice) || 0);
});

const fetchMediaBaseUrl = async () => {
  try {
    const response = await itemStore.getItemMediaBaseUrl();
    return response.baseUrl;
  } catch (error) {
    console.error("Error fetching media base URL:", error);
    return null;
  }
};

const loadExistingImage = async () => {
  if (!props.item?.medias?.length) {
    return;
  }

  itemMedia.value = props.item.medias[0];

  if (itemMedia.value.name) {
    const baseUrl = await fetchMediaBaseUrl();
    if (baseUrl) {
      existingImageUrl.value = `${baseUrl}/${itemMedia.value.name}`;
    }
  }
};

onMounted(() => {
  loadExistingImage();
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
  <div class="min-h-screen bg-gray-50 py-4 sm:py-6 lg:py-8">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
      <div
        class="max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl mx-auto mt-4 sm:mt-6 lg:mt-8 p-4 sm:p-6 lg:p-8 bg-white rounded-lg shadow-md"
      >
        <h2 class="text-xl sm:text-2xl font-bold mb-4 sm:mb-6 text-gray-800">
          {{ props.item ? "Update" : "Create New" }} Item
        </h2>

        <form @submit="onSubmit">
          <div class="mb-4">
            <label
              for="name"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Name</label
            >
            <input
              id="name"
              v-model="name"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              :class="{ 'border-red-500': errors.name }"
              v-bind="nameAttrs"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
              {{ errors.name }}
            </p>
          </div>

          <div class="mb-4">
            <label
              for="description"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Description</label
            >
            <textarea
              id="description"
              v-model="description"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              :class="{ 'border-red-500': errors.description }"
              v-bind="descriptionAttrs"
            ></textarea>
            <p v-if="errors.description" class="mt-1 text-sm text-red-600">
              {{ errors.description }}
            </p>
          </div>

          <div class="mb-4">
            <label
              for="startingPrice"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Starting Price</label
            >
            <div class="mt-1 relative rounded-md shadow-sm">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <span class="text-gray-500 sm:text-sm">$</span>
              </div>
              <input
                id="startingPrice"
                v-model="startingPrice"
                type="number"
                step="0.01"
                class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                :class="{ 'border-red-500': errors.startingPrice }"
                v-bind="startingPriceAttrs"
              />
            </div>
            <p v-if="errors.startingPrice" class="mt-1 text-sm text-red-600">
              {{ errors.startingPrice }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
              Current value: {{ formattedStartingPrice }}
            </p>
          </div>

          <div class="mb-4">
            <label
              for="auctionEndTime"
              class="block text-sm font-medium text-gray-700 mb-1"
              >Auction End Time</label
            >
            <input
              id="auctionEndTime"
              v-model="auctionEndTime"
              type="datetime-local"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              :class="{ 'border-red-500': errors.auctionEndTime }"
              v-bind="auctionEndTimeAttrs"
            />
            <p v-if="errors.auctionEndTime" class="mt-1 text-sm text-red-600">
              {{ errors.auctionEndTime }}
            </p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Item Image</label
            >
            <div
              class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md"
            >
              <div class="space-y-1 text-center">
                <Upload class="mx-auto h-12 w-12 text-gray-400" />
                <div class="text-sm text-gray-600">
                  <label
                    for="file-upload"
                    class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                  >
                    <span>Upload Image</span>
                    <input
                      id="file-upload"
                      name="file-upload"
                      type="file"
                      accept="image/*"
                      class="sr-only"
                      @change="handleImageUpload"
                    />
                  </label>
                </div>
                <p class="text-xs text-gray-500">PNG or JPG up to 1MB</p>
              </div>
            </div>
            <div v-if="imagePreview || existingImageUrl" class="mt-2">
              <img
                :src="imagePreview || existingImageUrl"
                alt="Preview"
                class="mt-2 max-w-full h-auto rounded-lg shadow-sm"
              />
            </div>
            <p v-if="imageError" class="mt-1 text-sm text-red-600">
              {{ imageError }}
            </p>
          </div>

          <div class="flex justify-end mt-6">
            <button
              type="submit"
              :disabled="isSubmitting"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              {{ isSubmitting ? "Saving..." : "Submit Item" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
