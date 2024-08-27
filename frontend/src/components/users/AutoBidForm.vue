<script lang="ts" setup>
import { ref, computed, onMounted, watch, inject } from "vue";
import { useField, useForm } from "vee-validate";
import * as yup from "yup";
import { useBidStore } from "@/stores/bidStore";
import { AutoBidConfig } from "@/types/item";
import { useAuthStore } from "@/stores/authStore";
import { AddNotification } from "@/types/notification";
import { useRoute, useRouter } from "vue-router";

const bidStore = useBidStore();
const userStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const addNotification = inject("addNotification") as AddNotification;

const reservedAmount = ref(0);

const validationSchema = computed(() => {
  return yup.object().shape({
    maxBidAmount: yup
      .number()
      .required("Max bid amount is required")
      .positive("Max bid amount must be positive")
      .min(1, "Bid amount must be at least 1")
      .test(
        "is-greater-than-reserved",
        "Max bid amount must be greater than reserved amount",
        function (value) {
          return value > this.parent.reservedAmount;
        }
      ),
    bidAlertPercentage: yup
      .number()
      .required("Bid alert percentage is required")
      .min(1, "Bid alert percentage must be at least 1")
      .max(100, "Bid alert percentage cannot exceed 100"),
    reservedAmount: yup.number().required(),
  });
});

const { handleSubmit, resetForm, setValues, errors, values } = useForm({
  validationSchema,
});

const { value: maxBidAmount, errorMessage: maxBidAmountError } =
  useField("maxBidAmount");
const { value: bidAlertPercentage, errorMessage: bidAlertPercentageError } =
  useField("bidAlertPercentage");

const isSubmitting = ref(false);
const config = ref<AutoBidConfig | null>(null);
const touchedFields = ref({
  maxBidAmount: false,
  bidAlertPercentage: false,
});

const isFormValid = computed(() => Object.keys(errors.value).length === 0);
const isFormTouched = computed(() =>
  Object.values(touchedFields.value).some((field) => field)
);

const touchField = (fieldName: keyof typeof touchedFields.value) => {
  touchedFields.value[fieldName] = true;
};

const formattedReservedAmount = computed(() => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(reservedAmount.value);
});

const navigateToItemPage = () => {
  const itemUuid = route.query.item;
  if (itemUuid && typeof itemUuid === "string") {
    router.push({ name: "detailItem", params: { uuid: itemUuid } });
  }
};

const onSubmit = handleSubmit(async (values) => {
  isSubmitting.value = true;
  try {
    const form = {
      user: userStore.user?.uuid,
      maxBidAmount: Number(values.maxBidAmount),
      bidAlertPercentage: Number(values.bidAlertPercentage),
    };

    if (config.value) {
      await bidStore.updateAutoBidConfig(config.value.uuid, form);
    } else {
      await bidStore.insertAutoBidConfig(form);
    }

    addNotification("Auto bid successfully set!", "success");
    navigateToItemPage()

    await getAutoBidConfig();
    Object.keys(touchedFields.value).forEach(
      (key) =>
        (touchedFields.value[key as keyof typeof touchedFields.value] = false)
    );
  } catch (error) {
    console.error("Error submitting form:", error);
  } finally {
    isSubmitting.value = false;
  }
});

const getAutoBidConfig = async () => {
  try {
    config.value = await bidStore.getAutoBidConfig();
    if (config.value) {
      setValues({
        maxBidAmount: config.value.maxBidAmount,
        bidAlertPercentage: config.value.bidAlertPercentage,
        reservedAmount: config.value.reservedAmount ?? 0,
      });
      reservedAmount.value = config.value.reservedAmount ?? 0;
    }
  } catch (error) {
    console.error("Error get auto bid config: ", error);
  }
};

onMounted(() => {
  getAutoBidConfig();
});

watch([maxBidAmount, bidAlertPercentage, reservedAmount], () => {
  setValues({
    ...values,
    reservedAmount: reservedAmount.value,
  });
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
      <div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
          Auto Bid Configuration
        </h2>

        <!-- Reserved Amount Display -->
        <div class="mb-6 p-4 bg-gray-100 rounded-md">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">
            Reserved Amount
          </h3>
          <p class="text-2xl font-bold text-indigo-600">
            {{ formattedReservedAmount }}
          </p>
        </div>

        <form @submit.prevent="onSubmit">
          <div class="mb-4">
            <label
              for="maxBidAmount"
              class="block text-sm font-medium text-gray-700"
              >Max Bid Amount</label
            >
            <input
              id="maxBidAmount"
              v-model="maxBidAmount"
              type="number"
              step="0.01"
              @input="touchField('maxBidAmount')"
              class="mt-1 block w-full rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              :class="{
                'border-red-500':
                  touchedFields.maxBidAmount && maxBidAmountError,
                'border-gray-300':
                  !touchedFields.maxBidAmount || !maxBidAmountError,
              }"
            />
            <p
              v-if="touchedFields.maxBidAmount && maxBidAmountError"
              class="mt-1 text-sm text-red-600"
            >
              {{ maxBidAmountError }}
            </p>
          </div>

          <div class="mb-4">
            <label
              for="bidAlertPercentage"
              class="block text-sm font-medium text-gray-700"
              >Bid Alert Percentage</label
            >
            <input
              id="bidAlertPercentage"
              v-model="bidAlertPercentage"
              type="number"
              min="1"
              max="100"
              @input="touchField('bidAlertPercentage')"
              class="mt-1 block w-full rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              :class="{
                'border-red-500':
                  touchedFields.bidAlertPercentage && bidAlertPercentageError,
                'border-gray-300':
                  !touchedFields.bidAlertPercentage || !bidAlertPercentageError,
              }"
            />
            <p
              v-if="touchedFields.bidAlertPercentage && bidAlertPercentageError"
              class="mt-1 text-sm text-red-600"
            >
              {{ bidAlertPercentageError }}
            </p>
          </div>

          <div class="flex items-center justify-end">
            <button
              type="submit"
              :disabled="isSubmitting || !isFormValid || !isFormTouched"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              :class="{
                'opacity-50 cursor-not-allowed':
                  isSubmitting || !isFormValid || !isFormTouched,
              }"
            >
              {{ isSubmitting ? "Submitting..." : "Save Configuration" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
