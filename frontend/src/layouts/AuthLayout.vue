<template>
  <div class="flex">
    <Sidebar />
    <main class="flex-1 p-4">
      <slot></slot>
    </main>
  </div>
</template>

<script>
import Sidebar from './Sidebar.vue'

export default {
  name: 'AuthLayout',
  components: {
    Sidebar
  }
}
</script>