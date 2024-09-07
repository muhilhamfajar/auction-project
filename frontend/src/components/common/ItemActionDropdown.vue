<template>
    <DropdownMenuRoot>
      <DropdownMenuTrigger class="flex items-center justify-center w-8 h-8 text-gray-400 rounded-full hover:bg-gray-100">
        <span class="sr-only">Open options</span>
        <MoreVertical class="w-5 h-5" />
      </DropdownMenuTrigger>
  
      <DropdownMenuPortal>
        <DropdownMenuContent 
          class="min-w-[220px] bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none py-1 divide-y divide-gray-100"
          :side-offset="5"
        >
          <div>
            <DropdownMenuItem as="button" @select="onView" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center">
              <Eye class="w-4 h-4 mr-3 text-gray-400 group-hover:text-gray-500" />
              View
            </DropdownMenuItem>
          </div>
          <div>
            <DropdownMenuItem v-if="isOpen" as="button" @select="onEdit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center">
              <Pencil class="w-4 h-4 mr-3 text-gray-400 group-hover:text-gray-500" />
              Edit
            </DropdownMenuItem>
            <DropdownMenuItem v-if="isOpen" as="button" @select="onDelete" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 flex items-center">
              <Trash class="w-4 h-4 mr-3 text-red-400 group-hover:text-red-500" />
              Delete
            </DropdownMenuItem>
          </div>
          <DropdownMenuArrow />
        </DropdownMenuContent>
      </DropdownMenuPortal>
    </DropdownMenuRoot>
  </template>
  
  <script lang="ts" setup>
  import { computed } from 'vue'
  import { 
    DropdownMenuRoot,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuPortal,
    DropdownMenuArrow
  } from 'radix-vue'
  import { MoreVertical, Eye, Pencil, Trash, Lock } from 'lucide-vue-next'
import { Item } from '@/types/item';
  
  interface Props {
    item: Item,
    onView: () => void
    onEdit: () => void
    onDelete: () => void
  }
  
  const props = defineProps<Props>()
  
  const isOpen = computed(() => props.item.status === 1)
  </script>