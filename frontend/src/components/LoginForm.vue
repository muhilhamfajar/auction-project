<template>
  <section class="bg-white">
    <div class="lg:grid lg:min-h-screen lg:grid-cols-12">
      <section
        class="relative flex h-32 items-end bg-gray-900 lg:col-span-5 lg:h-full xl:col-span-6"
      >
        <img
          alt=""
          src="https://images.unsplash.com/photo-1643981904834-86fddc1d1f3d?q=80&w=2835&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
          class="absolute inset-0 h-full w-full object-cover opacity-80"
        />

        <div class="hidden lg:relative lg:block lg:p-12">
          <a class="block text-white" href="#">
            <span class="sr-only">Home</span>
          </a>

          <h2
            class="mt-6 text-2xl font-bold text-white sm:text-3xl md:text-4xl"
          >
            Welcome to Our Auction! 💰
          </h2>

          <p class="mt-4 leading-relaxed text-white/90">
            Discover unique and valuable items up for bid. From rare
            collectibles to exclusive finds, there’s something for everyone.
            Dive in and place your bids on extraordinary treasures!
          </p>
        </div>
      </section>

      <main
        class="flex items-center justify-center px-8 py-8 sm:px-12 lg:col-span-7 lg:px-16 lg:py-12 xl:col-span-6"
      >
        <div class="max-w-xl lg:max-w-3xl w-full">
          <div class="relative -mt-16 block lg:hidden">
            <h1
              class="mt-10 text-2xl font-bold text-gray-900 sm:text-3xl md:text-4xl"
            >
              Welcome to Auction 💰
            </h1>

            <p class="mt-4 leading-relaxed text-gray-500">
              Lorem, ipsum dolor sit amet consectetur adipisicing elit. Eligendi
              nam dolorum aliquam, quibusdam aperiam voluptatum.
            </p>
          </div>

          <form @submit.prevent="onSubmit" class="mt-8 grid grid-cols-6 gap-6">
            <div class="col-span-4">
              <label
                for="Username"
                class="block text-sm font-medium text-gray-700"
              >
                Username
              </label>

              <input
                v-model="username"
                type="text"
                id="Username"
                name="username"
                :class="[
                  'mt-1 w-full rounded-md text-sm shadow-sm',
                  usernameError
                    ? 'border-red-600 bg-white'
                    : 'border-gray-200 bg-white',
                ]"
              />
              <span class="text-red-600 text-sm">{{ usernameError }}</span>
            </div>

            <div class="col-span-4">
              <label
                for="Password"
                class="block text-sm font-medium text-gray-700"
              >
                Password
              </label>

              <input
                v-model="password"
                type="password"
                id="Password"
                name="password"
                :class="[
                  'mt-1 w-full rounded-md text-sm shadow-sm',
                  passwordError
                    ? 'border-red-600 bg-white'
                    : 'border-gray-200 bg-white',
                ]"
              />

              <span class="text-red-600 text-sm">{{ passwordError }}</span>
            </div>

            <div class="col-span-6">
              <span class="text-red-600 text-sm">{{ error }}</span>
            </div>

            <div class="col-span-6 flex items-center justify-between">
              <button
                type="submit"
                :disabled="isLoading"
                class="pinline-block shrink-0 rounded-md border border-blue-600 bg-blue-600 px-12 py-3 text-sm font-medium text-white transition hover:bg-transparent hover:text-blue-600 focus:outline-none focus:ring active:text-blue-500"
                :class="{
                  'opacity-50 cursor-not-allowed': isLoading,
                }"
              >
                {{ isLoading ? "Hang Tight..." : "Login" }}
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { ref, watch } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { useForm, useField, FieldContext, yupToFormErrors } from "vee-validate";
import * as yup from "yup";

const router = useRouter();
const authStore = useAuthStore();

const schema = yup.object({
  username: yup.string().required("Username is required"),
  password: yup.string().required("Password is required"),
});

const { handleSubmit, errors, resetForm, validate } = useForm({
  validationSchema: schema,
});

const { value: username, errorMessage: usernameError } = useField("username");
const { value: password, errorMessage: passwordError } = useField("password");

const isLoading = ref(false);
const error = ref("");

watch(username, () => {
  error.value = "";
});

watch(password, () => {
  error.value = "";
});

const submitForm = async (values: { username: string; password: string }) => {
  if (isLoading.value) return;

  isLoading.value = true;
  error.value = "";

  try {
    const success = await authStore.login(values.username, values.password);
    if (success) {
      await authStore.fetchUserData();
      router.push(authStore.isAdmin ? "/dashboard" : "/");
    } else {
      error.value = "Invalid username or password";
    }
  } catch (err) {
    console.error("Login error:", err);
    error.value = "An error occurred during login. Please try again.";
  } finally {
    isLoading.value = false;
  }
};

const onSubmit = handleSubmit(submitForm);
</script>

<style scoped>
.form-input {
  @apply mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50;
}

.btn {
  @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500;
}

.btn-loading {
  @apply opacity-75 cursor-not-allowed;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.spinner {
  animation: spin 1s linear infinite;
}
</style>
