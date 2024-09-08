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
              {{ title }}
            </h2>
  
            <p class="mt-4 leading-relaxed text-white/90">
              {{ description }}
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
                {{ title }}
              </h1>
  
              <p class="mt-4 leading-relaxed text-gray-500">
                {{ description }}
              </p>
            </div>
  
            <form @submit.prevent="onSubmit" class="mt-8 grid grid-cols-6 gap-6">
              <slot></slot>
  
              <div class="col-span-6">
                <span class="text-red-600 text-sm">{{ error }}</span>
              </div>
  
              <div class="col-span-6 sm:flex sm:items-center sm:gap-4">
                <button
                  type="submit"
                  :disabled="isLoading"
                  class="inline-block shrink-0 rounded-md border border-blue-600 bg-blue-600 px-12 py-3 text-sm font-medium text-white transition hover:bg-transparent hover:text-blue-600 focus:outline-none focus:ring active:text-blue-500"
                  :class="{
                    'opacity-50 cursor-not-allowed': isLoading,
                  }"
                >
                  {{ isLoading ? loadingText : submitText }}
                </button>
  
                <p class="mt-4 text-sm text-gray-500 sm:mt-0">
                  <slot name="footer"></slot>
                </p>
              </div>
            </form>
          </div>
        </main>
      </div>
    </section>
  </template>
  
  <script lang="ts" setup>
  import { ref } from 'vue';
  
  const props = defineProps<{
    title: string;
    description: string;
    submitText: string;
    loadingText: string;
    onSubmit: () => Promise<void>;
  }>();
  
  const isLoading = ref(false);
  const error = ref('');
  
  const onSubmit = async () => {
    if (isLoading.value) return;
  
    isLoading.value = true;
    error.value = '';
  
    try {
      await props.onSubmit();
    } catch (err) {
      console.error('Form submission error:', err);
      error.value = err instanceof Error ? err.message : 'An error occurred. Please try again.';
    } finally {
      isLoading.value = false;
    }
  };
  </script>