# Laravel Inertia Table

## Backend

```php
<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $query = Todo::query();
        if ($request->filled('search')) {
            $escapedSearch = addcslashes($request->search, "_%\\");
            $query->where('todo', 'like', '%' . $escapedSearch . '%');
            // Wiele kolumn na raz
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('todo', 'like', '%' . $escapedSearch . '%')
                  ->orWhere('description', 'like', '%' . $escapedSearch . '%');
            });
            // Z relacją
            $query->where(function ($q) use ($escapedSearch) {
                // Szukamy w głównej tabeli todo
                $q->where('todo', 'like', '%' . $escapedSearch . '%')
                  // i w relacji np. details lub roles
                  ->orWhereHas('details', function ($relationQuery) use ($escapedSearch) {
                      $relationQuery->where('description', 'like', '%' . $escapedSearch . '%');
                  });
            });
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $isCompleted = $request->status === 'completed';
            $query->where('completed', $isCompleted);
        }
        // // Filtrowanie po tekst
        // if ($request->filled('status') && $request->status !== 'all') {
        //     $query->whereIn('completed', (array) $request->status);
        // }
        // // Sprawdza, czy pole 'status' istnieje, nie jest puste i jest tablicą
        // if ($request->filled('status') && is_array($request->status)) {
        //     // Usuwa z tablicy wartość 'all', jeśli użytkownik przysłał ją obok innych statusów
        //     $statuses = array_diff($request->status, ['all']);
        //     if (!empty($statuses)) {
        //         $query->whereIn('status', $statuses);
        //     }
        // }
        $sortBy = $request->input('sortBy', 'id');
        $order = $request->input('order', 'asc');
        $allowedSorts = ['id', 'todo', 'completed'];
        if (in_array($sortBy, $allowedSorts) && in_array($order, ['asc', 'desc'])) {
            $query->orderBy($sortBy, $order);
        }
        $limit = $request->input('limit', 10);
        $todos = $query->paginate($limit)->withQueryString();

        // Wysyłamy dane do Vue przez Inertia razem z aktualnymi filtrami
        return Inertia::render('Todos/Index', [
            'todos' => $todos,
            'filters' => $request->only(['search', 'status', 'sortBy', 'order', 'limit'])
        ]);
    }
}
```

## Frontend

```vue
<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  todos: Object, // Zawiera dane o wierszach oraz strukturę paginacji (links, total itp.)
  filters: Object, // Pamięta, jakie filtry były aktywne, żeby formularze się nie resetowały
})

// Obiekt stanu aplikacji, zsynchronizowany z tym, co przysłał serwer
const tableState = ref({
  search: props.filters.search || '',
  status: props.filters.status || 'all',
  sortBy: props.filters.sortBy || 'id',
  order: props.filters.order || 'asc',
  limit: props.filters.limit || 10,
})

// Funkcja, która wysyła aktualny stan tabeli do Laravela
function updateTable() {
  router.get(
    '/todos',
    { ...tableState.value }, // Przekazujemy cały obiekt filtrów jako query params (?page=2&search=...)
    {
      preserveState: true, // Zapobiega resetowaniu stanu komponentów Vue (np. focus w input)
      preserveScroll: true, // Zapobiega skakaniu strony do góry po przeładowaniu danych
      replace: true, // Zamiast tworzyć nową historię w przeglądarce przy każdej literce, nadpisuje obecną
    },
  )
}

let debouncerId = null
// Reagujemy na zmiany, natychmiast odświeżamy tabelę
watch(
  () => [
    tableState.value.status,
    tableState.value.limit,
    tableState.value.search,
    props.filters.sortBy,
    props.filters.order,
  ],
  () => {
    // Czyścimy poprzedni timer, jeśli użytkownik zmienił filtr przed upływem 400ms
    clearTimeout(debouncerId)
    // Uruchamiamy licznik, który wyśle zapytanie dopiero gdy użytkownik przestanie klikać
    debouncerid = setTimeout(() => {
      updateTable()
    }, 400) // 400ms to optymalny czas reakcji
  },
)

// Watch if ref
// watch(() => tableState.value,() => {updateTable()},{ deep: true })
// Lub tak
// watchEffect(() => { updateTable() })

// Obsługa wyszukiwania
function onSearch() {
  updateTable()
}

// Funkcja zmieniająca sortowanie kolumny
function handleSort(columnKey) {
  if (tableState.value.sortBy === columnKey) {
    tableState.value.order = tableState.value.order === 'asc' ? 'desc' : 'asc'
  } else {
    tableState.value.sortBy = columnKey
    tableState.value.order = 'asc'
  }
  updateTable()
}
</script>

<template>
  <div class="table-container">
    <h2>Zadania – Laravel 13 + Inertia (Czysty JS)</h2>

    <!-- PANEL FILTRÓW -->
    <div class="filters-panel">
      <input
        v-model="tableState.search"
        @input="onSearch"
        type="text"
        placeholder="Wyszukaj zadanie..."
        class="search-input"
      />

      <select v-model="tableState.status" class="status-select">
        <option value="all">Wszystkie statusy</option>
        <option value="completed">Tylko ukończone ✅</option>
        <option value="pending">W trakcie ⏳</option>
      </select>
    </div>

    <!-- TABELA HTML -->
    <table>
      <thead>
        <tr>
          <th @click="handleSort('id')" class="sortable">
            ID
            <span v-if="tableState.sortBy === 'id'">{{
              tableState.order === 'asc' ? '🔼' : '🔽'
            }}</span>
          </th>
          <th @click="handleSort('todo')" class="sortable">
            Zadanie
            <span v-if="tableState.sortBy === 'todo'">{{
              tableState.order === 'asc' ? '🔼' : '🔽'
            }}</span>
          </th>
          <th @click="handleSort('completed')" class="sortable">
            Status
            <span v-if="tableState.sortBy === 'completed'">{{
              tableState.order === 'asc' ? '🔼' : '🔽'
            }}</span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="todos.data.length === 0">
          <td
            colspan="3"
            style="text-align: center; color: #888; padding: 20px;"
          >
            Brak wyników spełniających kryteria.
          </td>
        </tr>
        <!-- Iterujemy po danych bezpośrednio z paginatora Laravela -->
        <tr v-for="todo in todos.data" :key="todo.id">
          <td>{{ todo.id }}</td>
          <td>{{ todo.todo }}</td>
          <td>{{ todo.completed ? '✅ Ukończone' : '⏳ W trakcie' }}</td>
        </tr>
      </tbody>
    </table>

    <!-- PAGINACJA OPARTA NA LINKACH Z LARAVELA -->
    <div class="pagination-panel">
      <!-- Przycisk Poprzednia -->
      <button
        :disabled="!todos.prev_page_url"
        @click="
          router.get(
            todos.prev_page_url,
            {},
            { preserveScroll: true, preserveState: true },
          )
        "
      >
        Poprzednia
      </button>

      <span>Strona {{ todos.current_page }} z {{ todos.last_page }}</span>

      <!-- Przycisk Następna -->
      <button
        :disabled="!todos.next_page_url"
        @click="
          router.get(
            todos.next_page_url,
            {},
            { preserveScroll: true, preserveState: true },
          )
        "
      >
        Następna
      </button>

      <!-- Wybór limitu wierszy -->
      <select v-model="tableState.limit" class="limit-select">
        <option :value="5">Pokazuj: 5</option>
        <option :value="10">Pokazuj: 10</option>
        <option :value="20">Pokazuj: 20</option>
      </select>
    </div>
  </div>
</template>

<style scoped>
.table-container {
  font-family: sans-serif;
  margin: 20px;
  max-width: 900px;
}
.filters-panel {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}
.search-input {
  flex-grow: 1;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.status-select,
.limit-select {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  cursor: pointer;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
th,
td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: left;
}
th {
  background-color: #f4f4f4;
}
.sortable {
  cursor: pointer;
  user-select: none;
}
.sortable:hover {
  background-color: #e9e9e9;
}
tr:nth-child(even) {
  background-color: #f9f9f9;
}
.pagination-panel {
  display: flex;
  gap: 15px;
  align-items: center;
}
.pagination-panel button {
  padding: 8px 12px;
  cursor: pointer;
}
.pagination-panel button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>
```
