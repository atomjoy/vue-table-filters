<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Avatar, AvatarFallback } from '@/components/ui/avatar' // Dostosuj ścieżki komponentów UI
import UserActionsDropdown from '@/components/table/admin/users/UserActionsDropdown.vue'
import UserStatusBadge from '@/components/table/admin/users/UserStatusBadge.vue'

// TYPY DANYCH
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

// PROPSY I EMITY
defineProps<{
  users: User[]
}>()

const emit = defineEmits<{
  (e: 'delete', id: number): void
}>()

const { t } = useI18n()

// DWUKIERUNKOWY MODEL DLA ZAZNACZONYCH CHECBOXÓW
const selectedIds = defineModel<number[]>({ default: () => [] })

// FUNKCJA ZAZNACZANIA / ODZNACZANIA WIERZSA
const toggleSelectUser = (id: number) => {
  if (selectedIds.value.includes(id)) {
    selectedIds.value = selectedIds.value.filter((item) => item !== id)
  } else {
    selectedIds.value.push(id)
  }
}

// FORMATOWANIE AVATARA
const getAvatarFallback = (name: string) =>
  name ? name.substring(0, 2).toUpperCase() : 'UN'
</script>

<template>
  <div class="flex flex-col space-y-4 md:hidden">
    <div
      v-for="user in users"
      :key="user.id"
      class="bg-card text-card-foreground hover:bg-muted/50 space-y-4 rounded-xl border p-4 shadow-sm transition-colors"
    >
      <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-3">
          <!-- Checkbox zsynchronizowany z tablicą selectedIds -->
          <input
            type="checkbox"
            :checked="selectedIds.includes(user.id)"
            @change="toggleSelectUser(user.id)"
            class="text-primary h-4 w-4 rounded border-gray-300"
          />

          <Avatar class="h-9 w-9 border">
            <AvatarFallback
              class="bg-secondary text-secondary-foreground text-xs font-semibold"
            >
              {{ getAvatarFallback(user.name) }}
            </AvatarFallback>
          </Avatar>

          <div class="flex flex-col">
            <span
              class="text-foreground flex items-center gap-2 text-sm font-medium"
            >
              {{ user.name }}
            </span>
            <span class="text-muted-foreground text-xs">{{ user.email }}</span>
          </div>
        </div>

        <!-- Przekazujemy akcję usuwania w górę przez emit -->
        <UserActionsDropdown
          :userId="user.id"
          @delete="emit('delete', user.id)"
        />
      </div>

      <!-- ROLE W WIDOKU MOBILNYM -->
      <div v-if="user.roles.length > 0" class="flex flex-wrap gap-1">
        <span
          v-for="role in user.roles"
          :key="role.id"
          :class="[
            'rounded border px-2 py-0.5 text-[10px] font-medium',
            role.name === 'superadmin'
              ? 'border-red-200 bg-red-500/10 text-red-700 dark:border-red-900/50'
              : role.name === 'admin'
                ? 'border-amber-200 bg-amber-500/10 text-amber-700 dark:border-amber-900/50'
                : 'bg-secondary text-secondary-foreground',
          ]"
        >
          {{ role.name }}
        </span>
      </div>

      <!-- BADGES WERYFIKACJI I 2FA -->
      <div class="grid grid-cols-2 gap-2 border-t pt-3 text-xs">
        <div>
          <span class="text-muted-foreground mb-1 block">
            {{ t('Verification') }}
          </span>
          <UserStatusBadge
            type="email"
            :verifiedAt="user.email_verified_at"
            :twoFactorConfirmedAt="user.two_factor_confirmed_at"
          />
        </div>
        <div>
          <span class="text-muted-foreground mb-1 block">
            {{ t('Status 2FA') }}
          </span>
          <UserStatusBadge
            type="2fa"
            :verifiedAt="user.email_verified_at"
            :twoFactorConfirmedAt="user.two_factor_confirmed_at"
          />
        </div>
      </div>
    </div>
  </div>
</template>
