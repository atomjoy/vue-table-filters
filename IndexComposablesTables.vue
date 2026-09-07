<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { Plus } from '@lucide/vue'
import { buttonVariants } from '@/components/ui/button'
import { CardContent } from '@/components/ui/card'
import FlashMessages from '@/components/flash/FlashMessages.vue'
import Pagination from '@/components/table/Pagination.vue'
import { useFilters } from '@/composables/pages/admin/users/useFilters'
import { useTableSelection } from '@/composables/pages/admin/users/useTableSelection'
import UserTableFilters from '@/components/pages/admin/users/UserTableFilters.vue'
import UserMobileCards from '@/components/pages/admin/users/UserMobileCards.vue'
import UserDesktopTable from '@/components/pages/admin/users/UserDesktopTable.vue'

import { useI18n } from 'vue-i18n'
const { t } = useI18n()

// TYPY I INTERFEJSY
interface Role {
  id: number
  name: string
  guard_name: string
}

interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
  two_factor_confirmed_at: string | null
  roles: Role[]
}

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

interface LaravelPaginationPayload {
  current_page: number
  data: User[]
  first_page_url: string
  from: number
  last_page: number
  last_page_url: string
  links: PaginationLink[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number
  total: number
}

// PROPSY I ADRES BAZOWY
const routeUrl = '/admin/users'
const props = defineProps<{ payload: LaravelPaginationPayload }>()

// FILTRY UNIKALNE DLA TEGO KOMPONENTU
const urlParams = new URLSearchParams(window.location.search)
const filterVerified = ref(urlParams.get('verified') || 'all')
const filter2FA = ref(urlParams.get('two_factor') || 'all')

// OBSŁUGA ZAZNACZANIA I AKCJI MASOWYCH
const currentUsers = computed(() => props.payload.data)
const {
  selectedIds,
  isAllSelected,
  toggleSelectAll,
  handleSingleDelete,
  handleBulkDelete,
} = useTableSelection(routeUrl, currentUsers)

// OBSŁUGA FILTRÓW I SORTOWANIA
const { searchQuery, perPage, sortBy, sortDir } = useFilters(
  routeUrl,
  {
    filter_verified: filterVerified,
    filter_2fa: filter2FA,
  },
  selectedIds,
)
</script>

<template>
  <div class="w-full space-y-6 p-6">
    <div
      class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="space-y-1">
        <h1 class="text-3xl font-bold tracking-tight">{{ t('Users') }}</h1>
        <p class="text-muted-foreground text-sm">
          {{ t('Manage user accounts, email verification, and 2FA security.') }}
        </p>
      </div>
      <Link
        :href="routeUrl + '/create'"
        :class="buttonVariants({ variant: 'default' })"
        class="gap-2 self-start sm:self-auto"
      >
        <Plus class="h-4 w-4" /> {{ t('Add user') }}
      </Link>
    </div>

    <FlashMessages />

    <!-- Wszystko zamienia się w jeden czytelny znacznik HTML -->
    <UserTableFilters
      v-model:search="searchQuery"
      v-model:verified="filterVerified"
      v-model:twoFactor="filter2FA"
      v-model:perPage="perPage"
      :payload="payload"
      :selected-count="selectedIds.length"
      @bulk-delete="handleBulkDelete"
    >
      <div class="px-0 pb-0 md:px-6">
        <CardContent class="p-0">
          <!-- Widok Mobilny (Karty) -->
          <UserMobileCards
            v-model="selectedIds"
            :users="payload.data"
            @delete="handleSingleDelete"
          />

          <!-- Widok Desktop (Tabela) -->
          <UserDesktopTable
            v-model="selectedIds"
            v-model:isAllSelected="isAllSelected"
            v-model:sortBy="sortBy"
            v-model:sortDir="sortDir"
            :users="payload.data"
            @delete="handleSingleDelete"
            @toggle-select-all="toggleSelectAll"
          />
        </CardContent>
      </div>

      <Pagination :links="payload.links" :total="payload.total" />
    </UserTableFilters>
  </div>
</template>
