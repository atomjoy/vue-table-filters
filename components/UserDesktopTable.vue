<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import SortButton from '@/components/table/SortButton.vue'
import UserStatusBadge from '@/components/table/admin/users/UserStatusBadge.vue'
import UserActionsDropdown from '@/components/table/admin/users/UserActionsDropdown.vue'

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

// PROPSY
defineProps<{ users: User[] }>()

// JEDYNY EMIT (Dla akcji usunięcia)
const emit = defineEmits<{
	(e: 'delete', id: number): void
	(e: 'toggleSelectAll'): void
}>()

const { t } = useI18n()

// DWUKIERUNKOWE MODELE (defineModel)
const selectedIds = defineModel<number[]>({ default: () => [] })
const isAllSelected = defineModel<boolean>('isAllSelected', { default: false })
const sortBy = defineModel<string>('sortBy', { default: 'id' })
const sortDir = defineModel<string>('sortDir', { default: 'desc' })

// FUNKCJA SORTOWANIA (Przeniesiona bezpośrednio do komponentu!)
const triggerSort = (column: string) => {
	if (sortBy.value === column) {
		sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
	} else {
		sortBy.value = column
		sortDir.value = 'asc'
	}
}

// POJEDYNCZE ZAZNACZANIE WIERZSHA
const toggleSelectUser = (id: number) => {
	if (selectedIds.value.includes(id)) {
		selectedIds.value = selectedIds.value.filter((item) => item !== id)
	} else {
		selectedIds.value.push(id)
	}
}

const getAvatarFallback = (name: string) => (name ? name.substring(0, 2).toUpperCase() : 'UN')
const formatDate = (dateString: string) =>
	new Date(dateString).toLocaleDateString('pl-PL', {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	})
</script>

<template>
	<div class="relative hidden w-full overflow-auto md:block">
		<table class="w-full caption-bottom text-sm">
			<thead class="bg-muted/40 text-muted-foreground border-b font-medium">
				<tr class="transition-colors">
					<th class="h-12 w-12 px-4 text-left align-middle">
						<!-- v-model -->
						<input
							type="checkbox"
							v-model="isAllSelected"
							@change="emit('toggleSelectAll')"
							class="text-primary h-4 w-4 rounded border-gray-300"
						/>
					</th>

					<th
						@click="triggerSort('id')"
						class="hover:bg-muted/80 h-12 w-24 cursor-pointer px-4 text-left align-middle font-medium transition-colors select-none"
					>
						<div class="flex items-center gap-1">
							ID
							<SortButton
								column-field="id"
								:current-sort-by="sortBy"
								:current-sort-dir="sortDir"
							/>
						</div>
					</th>

					<th
						@click="triggerSort('name')"
						class="hover:bg-muted/80 h-12 cursor-pointer px-4 text-left align-middle font-medium transition-colors select-none"
					>
						<div class="flex items-center gap-1">
							{{ t('User') }}
							<SortButton
								column-field="name"
								:current-sort-by="sortBy"
								:current-sort-dir="sortDir"
							/>
						</div>
					</th>

					<th class="h-12 px-4 text-left align-middle font-medium">{{ t('Roles') }}</th>

					<th
						@click="triggerSort('email_verified_at')"
						class="hover:bg-muted/80 h-12 cursor-pointer px-4 text-left align-middle font-medium transition-colors select-none"
					>
						<div class="flex items-center gap-1">
							{{ t('Verification') }}
							<SortButton
								column-field="email_verified_at"
								:current-sort-by="sortBy"
								:current-sort-dir="sortDir"
							/>
						</div>
					</th>

					<th
						@click="triggerSort('two_factor_confirmed_at')"
						class="hover:bg-muted/80 h-12 cursor-pointer px-4 text-left align-middle font-medium transition-colors select-none"
					>
						<div class="flex items-center gap-1">
							{{ t('Status 2FA') }}
							<SortButton
								column-field="two_factor_confirmed_at"
								:current-sort-by="sortBy"
								:current-sort-dir="sortDir"
							/>
						</div>
					</th>

					<th
						@click="triggerSort('created_at')"
						class="hover:bg-muted/80 h-12 cursor-pointer px-4 text-left align-middle font-medium transition-colors select-none"
					>
						<div class="flex items-center gap-1">
							{{ t('Created') }}
							<SortButton
								column-field="created_at"
								:current-sort-by="sortBy"
								:current-sort-dir="sortDir"
							/>
						</div>
					</th>
					<th class="h-12 w-20 px-4 align-middle font-medium"></th>
				</tr>
			</thead>

			<tbody class="divide-y">
				<tr
					v-for="user in users"
					:key="user.id"
					class="hover:bg-muted/50 transition-colors"
					:class="{ 'bg-muted/30': selectedIds.includes(user.id) }"
				>
					<td class="p-4 align-middle">
						<input
							type="checkbox"
							:checked="selectedIds.includes(user.id)"
							@change="toggleSelectUser(user.id)"
							class="text-primary h-4 w-4 rounded border-gray-300"
						/>
					</td>
					<td class="text-muted-foreground p-4 align-middle font-mono text-xs">
						#{{ user.id }}
					</td>
					<td class="p-4 align-middle">
						<div class="flex items-center gap-3">
							<Avatar class="h-9 w-9 border"
								><AvatarFallback
									class="bg-secondary text-secondary-foreground text-xs font-semibold"
									>{{ getAvatarFallback(user.name) }}</AvatarFallback
								></Avatar
							>
							<div class="flex flex-col">
								<span class="text-foreground font-medium">{{ user.name }}</span
								><span class="text-muted-foreground text-xs">{{ user.email }}</span>
							</div>
						</div>
					</td>
					<td class="p-4 align-middle">
						<div class="flex max-w-50 flex-wrap gap-1">
							<span
								v-for="role in user.roles"
								:key="role.id"
								:class="[
									'rounded border px-2 py-0.5 text-[11px] font-medium whitespace-nowrap',
									role.name === 'superadmin'
										? 'border-red-200 bg-red-500/10 text-red-700 dark:border-red-900/50 dark:bg-red-500/20 dark:text-red-400'
										: role.name === 'admin'
											? 'border-amber-200 bg-amber-500/10 text-amber-700 dark:border-amber-900/50 dark:bg-amber-500/20 dark:text-amber-400'
											: 'bg-secondary text-secondary-foreground border-transparent',
								]"
								>{{ role.name }}</span
							>
							<span
								v-if="user.roles.length === 0"
								class="text-muted-foreground text-xs"
								>-</span
							>
						</div>
					</td>
					<td class="p-4 align-middle">
						<UserStatusBadge
							type="email"
							:verifiedAt="user.email_verified_at"
							:twoFactorConfirmedAt="user.two_factor_confirmed_at"
						/>
					</td>
					<td class="p-4 align-middle">
						<UserStatusBadge
							type="2fa"
							:verifiedAt="user.email_verified_at"
							:twoFactorConfirmedAt="user.two_factor_confirmed_at"
						/>
					</td>
					<td class="text-muted-foreground p-4 align-middle text-xs">
						{{ formatDate(user.created_at) }}
					</td>
					<td class="p-4 text-right align-middle">
						<UserActionsDropdown :userId="user.id" @delete="emit('delete', user.id)" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>
