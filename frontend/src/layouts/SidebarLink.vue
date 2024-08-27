<template>
  <RouterLink
    v-if="to"
    :to="to"
    custom
    v-slot="{ isActive, href, navigate }"
  >
    <a
      :href="href"
      @click="navigate"
      :class="[
        'group relative flex justify-center rounded px-2 py-1.5 text-gray-500 hover:bg-gray-50',
        { 'bg-blue-50 text-blue-500 hover:bg-blue-50': isActive }
      ]"
    >
      <component :is="icon" class="size-5 opacity-75" />
      
      <span
        v-if="hasNotification"
        class="absolute top-2 right-2 block h-2 w-2 rounded-full bg-red-600 transform translate-x-1/2 -translate-y-1/2"
      ></span>

      <span
        class="invisible absolute start-full top-1/2 ms-4 -translate-y-1/2 rounded bg-gray-900 px-2 py-1.5 text-xs font-medium text-white group-hover:visible"
      >
        {{ text }}
      </span>
    </a>
  </RouterLink>

  <a
    v-else
    href="#"
    :class="[
      'group relative flex justify-center rounded px-2 py-1.5 text-gray-500 hover:bg-gray-50 hover:text-gray-700',
    ]"
    @click="$emit('click')"
  >
    <component :is="icon" class="size-5 opacity-75" />

    <span
      class="invisible absolute start-full top-1/2 ms-4 -translate-y-1/2 rounded bg-gray-900 px-2 py-1.5 text-xs font-medium text-white group-hover:visible"
    >
      {{ text }}
    </span>
  </a>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps<{
  icon: object
  text: string
  to?: string
  hasNotification?: boolean
}>()

defineEmits(['click'])
</script>