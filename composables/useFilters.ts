import { ref, watch, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'

interface ExtraFilters {
	[key: string]: Ref<any>
}

export function useFilters(
	baseRoute: string,
	extraFilters: ExtraFilters = {},
	selectedIdsToClear?: Ref<number[]>,
) {
	const urlParams = new URLSearchParams(window.location.search)

	const searchQuery = ref(urlParams.get('search') || '')
	const perPage = ref(urlParams.get('per_page') || '10')
	const sortBy = ref(urlParams.get('sort_by') || 'id')
	const sortDir = ref(urlParams.get('sort_dir') || 'desc')

	let debounceTimer: ReturnType<typeof setTimeout> | null = null

	const handleSort = (column: string) => {
		if (sortBy.value === column) {
			sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
		} else {
			sortBy.value = column
			sortDir.value = 'asc'
		}
	}

	const fetchResults = () => {
		// Czyszczenie zaznaczonych
		if (selectedIdsToClear) {
			selectedIdsToClear.value = []
		}
		
		const dynamicParams = Object.keys(extraFilters).reduce(
			(acc, key) => {
				acc[key] = extraFilters[key].value
				return acc
			},
			{} as Record<string, any>,
		)

		router.get(
			baseRoute,
			{
				search: searchQuery.value,
				per_page: perPage.value,
				sort_by: sortBy.value,
				sort_dir: sortDir.value,
				...dynamicParams,
			},
			{
				preserveState: true,
				preserveScroll: true,
				replace: true,
			},
		)
	}

	// Obserwuj domyślne filtry + dynamicznie przekazane filtry specyficzne dla widoku
	watch(
		[searchQuery, perPage, sortBy, sortDir, ...Object.values(extraFilters)],
		(newValues, oldValues) => {
			// Sprawdzamy, czy zmieniło się pole wyszukiwania (searchQuery to pierwszy element w tablicy watch)
			const isSearchChanged = newValues[0] !== oldValues[0]

			if (debounceTimer) {
				clearTimeout(debounceTimer)
			}

			if (isSearchChanged) {
				debounceTimer = setTimeout(() => {
					fetchResults()
				}, 350)
			} else {
				// Dla filtrów typu "select" (perPage, status, 2FA) wysyłamy zapytanie natychmiast bez opóźnienia
				fetchResults()
			}
		},
	)

	return {
		searchQuery,
		perPage,
		sortBy,
		sortDir,
		handleSort,
	}
}
