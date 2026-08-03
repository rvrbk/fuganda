<template>
	<div class="searchable-select relative" v-clickoutside="closeDropdown">
		<button
			type="button"
			@click="toggleDropdown"
			:disabled="disabled"
			class="searchable-select__button w-full flex items-center justify-between rounded border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-sm hover:border-slate-400 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
			:class="{ 'border-sky-500 ring-1 ring-sky-500': isOpen, 'border-slate-300': !isOpen }"
		>
			<span class="truncate flex-1" :class="{ 'text-slate-400': !selectedLabel }">
				{{ selectedLabel || placeholder }}
			</span>
			<svg class="w-4 h-4 ml-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
			</svg>
		</button>
		
		<div
			v-if="isOpen"
			class="searchable-select__dropdown absolute z-50 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg"
			:style="{ minWidth: minWidth }"
		>
			<input
				ref="searchInput"
				v-model="searchQuery"
				@input="resetHighlight"
				@keydown.enter.prevent="selectHighlighted"
				@keydown.escape.prevent="closeDropdown"
				@keydown.up.prevent="highlightPrev"
				@keydown.down.prevent="highlightNext"
				:placeholder="placeholder"
				class="searchable-select__search w-full border-0 px-3 py-2 text-sm focus:ring-0 focus:outline-none"
			/>
			
			<ul class="searchable-select__options max-h-96 overflow-y-auto">
				<li
					v-for="(option, index) in filteredOptions"
					:key="getKey(option)"
					@click="selectOption(option)"
					class="searchable-select__option px-3 py-2 text-sm cursor-pointer hover:bg-slate-50"
					:class="{
						'bg-sky-50 text-sky-700': highlightedIndex === index,
						'bg-sky-100': getValue(option) === modelValue
					}"
				>
					{{ getLabel(option) }}
				</li>
				<li v-if="filteredOptions.length === 0 && searchQuery" class="px-3 py-2 text-sm text-slate-400">
					No results found
				</li>
			</ul>
		</div>
	</div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
	modelValue: {
		type: [String, Number],
		default: '',
	},
	options: {
		type: Array,
		default: () => [],
	},
	placeholder: {
		type: String,
		default: 'Select...',
	},
	disabled: {
		type: Boolean,
		default: false,
	},
	optionValue: {
		type: String,
		default: 'value',
	},
	optionLabel: {
		type: String,
		default: 'label',
	},
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const searchInput = ref(null);
const selectRef = ref(null);
const minWidth = ref('auto');

const filteredOptions = computed(() => {
	if (!searchQuery.value) return props.options;
	const query = searchQuery.value.toLowerCase();
	return props.options.filter(option => 
		getLabel(option).toLowerCase().includes(query)
	);
});

const selectedLabel = computed(() => {
	const selected = props.options.find(opt => getValue(opt) === props.modelValue);
	if (selected) return getLabel(selected);
	// If no match found but modelValue exists, show it directly (handles case where options are empty or value doesn't match)
	if (props.modelValue && !props.options.length) {
		return String(props.modelValue);
	}
	return '';
});

function getValue(option) {
	if (typeof option === 'object' && option !== null) {
		return option[props.optionValue] ?? option.value ?? option;
	}
	return String(option);
}

function getLabel(option) {
	if (typeof option === 'object' && option !== null) {
		return option[props.optionLabel] ?? option.label ?? option.name ?? String(option.value);
	}
	return String(option);
}

function getKey(option) {
	return getValue(option);
}

function toggleDropdown() {
	if (props.disabled) return;
	isOpen.value = !isOpen.value;
	if (isOpen.value) {
		nextTick(() => {
			searchInput.value?.focus();
			updateMinWidth();
		});
	} else {
		closeDropdown();
	}
}

function closeDropdown() {
	isOpen.value = false;
	searchQuery.value = '';
	highlightedIndex.value = -1;
}

function updateMinWidth() {
	if (selectRef.value) {
		minWidth.value = `${selectRef.value.offsetWidth}px`;
	}
}

function selectOption(option) {
	emit('update:modelValue', getValue(option));
	closeDropdown();
}

function selectHighlighted() {
	if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
		selectOption(filteredOptions.value[highlightedIndex.value]);
	}
}

function resetHighlight() {
	highlightedIndex.value = -1;
}

function highlightPrev() {
	if (filteredOptions.value.length === 0) return;
	
	if (highlightedIndex.value <= 0) {
		highlightedIndex.value = filteredOptions.value.length - 1;
	} else {
		highlightedIndex.value--;
	}
	scrollOptionIntoView(highlightedIndex.value);
}

function highlightNext() {
	if (filteredOptions.value.length === 0) return;
	
	if (highlightedIndex.value >= filteredOptions.value.length - 1) {
		highlightedIndex.value = 0;
	} else {
		highlightedIndex.value++;
	}
	scrollOptionIntoView(highlightedIndex.value);
}

function scrollOptionIntoView(index) {
	nextTick(() => {
		const options = document.querySelectorAll('.searchable-select__option');
		if (options[index]) {
			options[index].scrollIntoView({ block: 'nearest' });
		}
	});
}

// Close dropdown when clicking outside (handled by v-clickoutside directive)
watch(() => props.modelValue, () => {
	if (!isOpen.value) {
		searchQuery.value = '';
		highlightedIndex.value = -1;
	}
});
</script>

<style scoped>
.searchable-select {
	position: relative;
	display: inline-block;
}

.searchable-select__button {
	transition: border-color 0.15s ease, box-shadow 0.15s ease;
	white-space: nowrap;
}

.searchable-select__dropdown {
	max-height: 400px;
	z-index: 1000;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
	border: 1px solid #e2e8f0;
	background: white;
	border-radius: 0.375rem;
	overflow: hidden;
}

.searchable-select__search {
	border-bottom: 1px solid #e2e8f0;
	padding: 0.5rem 0.75rem;
	width: 100%;
	background: white;
	outline: none;
}

.searchable-select__search:focus {
	border-color: #0ea5e9;
	outline: none;
}

.searchable-select__options {
	list-style: none;
	padding: 0;
	margin: 0;
	max-height: 380px;
	overflow-y: auto;
}

.searchable-select__option {
	padding: 0.5rem 0.75rem;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	cursor: pointer;
}

.searchable-select__option:hover {
	background-color: rgb(241, 245, 249);
}

.searchable-select__option:active {
	background-color: rgb(224, 242, 254);
}
</style>
