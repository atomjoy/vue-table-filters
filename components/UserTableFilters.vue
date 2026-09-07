<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Search, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Input } from '@/components/ui/input'

// PROPSY DLA DANYCH PAGINACJI I LICZNIKA
defineProps<{
	payload: {
		from: number
		to: number
		total: number
	}
	selectedCount: number
}>()

// EMITY DLA AKCJI MASOWYCH
const emit = defineEmits<{
	(e: 'bulkDelete'): void
}>()

const { t } = useI18n()

// DWUKIERUNKOWE MODELE (dwu-way binding przez v-model)
const searchQuery = defineModel<string>('search', { default: '' })
const filterVerified = defineModel<string>('verified', { default: 'all' })
const filter2FA = defineModel<string>('twoFactor', { default: 'all' })
const perPage = defineModel<string>('perPage', { default: '10' })
</script>

<template>
	<div class="space-y-4">
		<!-- Pasek akcji masowych -->
		<div
			v-if="selectedCount > 0"
			class="bg-muted/50 flex items-center justify-between rounded-lg border border-dashed p-4"
		>
			<span class="text-sm font-medium">
				{{ t('Selected users:') }}
				<span class="text-primary font-bold">{{ selectedCount }}</span>
			</span>
			<Button variant="destructive" size="sm" class="gap-2" @click="emit('bulkDelete')">
				<Trash2 class="h-4 w-4" /> {{ t('Delete selected') }}
			</Button>
		</div>

		<!-- Główna karta z filtrami i nagłówkiem -->
		<Card class="border-0 shadow-none md:border">
			<CardHeader class="px-0 pb-4 md:px-6">
				<CardTitle>{{ t('All users') }}</CardTitle>
				<CardDescription>
					{{ t('You are displaying') }} {{ payload.from }}-{{ payload.to }}
					{{ t('from') }} {{ payload.total }} {{ t('users') }}.
				</CardDescription>

				<!-- KONTENER FILTRÓW -->
				<div class="grid grid-cols-2 gap-3 pt-4 md:flex md:flex-wrap md:items-center">
					<!-- 1. Wyszukiwarka -->
					<div class="relative col-span-1 w-full md:max-w-sm md:flex-1">
						<Search
							class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
						/>
						<Input
							v-model="searchQuery"
							type="text"
							:placeholder="t('Search by name or email')"
							class="focus-visible:border-input h-10 px-3 py-2 pl-9 text-sm shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
						/>
					</div>

					<!-- 2. Select Weryfikacja -->
					<select
						v-model="filterVerified"
						class="border-input bg-background text-foreground col-span-1 h-10 w-full rounded-md border px-3 py-2 text-sm md:max-w-60 md:flex-1"
					>
						<option value="all">{{ t('Verification: All') }}</option>
						<option value="verified">{{ t('Verified') }}</option>
						<option value="unverified">{{ t('Unverified') }}</option>
					</select>

					<!-- 3. Select 2FA -->
					<select
						v-model="filter2FA"
						class="border-input bg-background text-foreground col-span-2 h-10 w-full rounded-md border px-3 py-2 text-sm md:max-w-60 md:flex-1"
					>
						<option value="all">{{ t('2fa status: All') }}</option>
						<option value="enabled">{{ t('2FA: Enabled') }}</option>
						<option value="disabled">{{ t('2FA: Disabled') }}</option>
					</select>

					<!-- 4. Select PerPage -->
					<select
						v-model="perPage"
						class="border-input bg-background text-foreground col-span-2 h-10 w-full rounded-md border px-3 py-2 text-sm md:ml-auto md:w-auto"
					>
						<option value="5">{{ t('Show:') }} 5</option>
						<option value="10">{{ t('Show:') }} 10</option>
						<option value="25">{{ t('Show:') }} 25</option>
						<option value="50">{{ t('Show:') }} 50</option>
					</select>
				</div>
			</CardHeader>

			<!-- Miejsce na slot / dalszą zawartość karty (np. tabelę) -->
			<slot />
		</Card>
	</div>
</template>
