<template>
	<section class="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
		<h2 class="text-xl font-semibold text-slate-900">{{ $t('nav.messages') }}</h2>

		<div v-if="loading" class="space-y-2">
			<div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded bg-slate-200"></div>
		</div>

		<div v-else-if="!items.length" class="rounded border border-dashed border-slate-300 p-6 text-sm text-slate-500">
			{{ $t('messages.empty') }}
		</div>

		<div v-else class="grid gap-4 md:grid-cols-[18rem_1fr]">
			<aside class="space-y-2 rounded border border-slate-200 p-2">
				<p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('messages.threads') }}</p>
				<button
					v-for="thread in threads"
					:key="thread.key"
					type="button"
					class="w-full rounded border px-3 py-2 text-left transition"
					:class="thread.key === selectedThreadKey
						? 'border-sky-400 bg-sky-50'
						: 'border-slate-200 bg-white hover:bg-slate-50'"
					@click="selectThread(thread.key)"
				>
					<p class="text-sm font-medium text-slate-900">{{ thread.counterpartName }}</p>
					<p class="text-xs text-slate-500">{{ thread.subject }}</p>
					<p class="mt-1 line-clamp-2 text-xs text-slate-600">{{ thread.lastMessageBody }}</p>
				</button>
			</aside>

			<div class="flex min-h-[24rem] flex-col rounded border border-slate-200">
				<div v-if="selectedThread" class="border-b border-slate-200 px-4 py-3">
					<div class="flex items-start justify-between gap-3">
						<div>
							<p class="text-sm font-semibold text-slate-900">{{ selectedThread.counterpartName }}</p>
							<p class="text-xs text-slate-500">{{ selectedThread.subject }}</p>
						</div>
						<RouterLink
							:to="{ name: 'property-detail', params: { id: selectedThread.propertyId } }"
							class="inline-flex shrink-0 rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
						>
							{{ $t('actions.viewDetails') }}
						</RouterLink>
					</div>
				</div>
				<div v-else class="border-b border-slate-200 px-4 py-3 text-sm text-slate-500">
					{{ $t('messages.selectThread') }}
				</div>

				<div v-if="selectedThread" class="flex-1 space-y-3 overflow-y-auto p-4">
					<div
						v-for="message in selectedThread.messages"
						:key="message.id"
						class="flex"
						:class="isMine(message) ? 'justify-end' : 'justify-start'"
					>
						<div
							class="max-w-[85%] rounded-lg px-3 py-2 text-sm"
							:class="isMine(message)
								? 'bg-sky-700 text-white'
								: 'bg-slate-100 text-slate-800'"
						>
							<p>{{ message.body }}</p>
							<p
								class="mt-1 text-[11px]"
								:class="isMine(message) ? 'text-sky-100' : 'text-slate-500'"
							>
								{{ new Date(message.createdAt).toLocaleString() }}
							</p>
						</div>
					</div>
				</div>

				<form v-if="selectedThread" class="border-t border-slate-200 p-3" @submit.prevent="sendReply">
					<textarea
						v-model="replyBody"
						class="w-full rounded border border-slate-300 px-3 py-2 text-sm"
						:placeholder="$t('messages.replyPlaceholder')"
						rows="3"
						required
					></textarea>
					<div class="mt-2 flex justify-end">
						<button class="rounded bg-sky-700 px-3 py-2 text-sm text-white" type="submit">{{ $t('actions.send') }}</button>
					</div>
				</form>
			</div>
		</div>
	</section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { createMessage, listMessages } from '../services/messages';
import { getProfile } from '../services/authProfile';

const emit = defineEmits(['auth-changed']);

const loading = ref(true);
const items = ref([]);
const currentUserId = ref(null);
const selectedThreadKey = ref(null);
const replyBody = ref('');

const threads = computed(() => {
	const grouped = new Map();

	for (const message of items.value) {
		if (!message?.propertyId) {
			continue;
		}

		const counterpartId = message.senderId === currentUserId.value
			? message.receiverId
			: message.senderId;
		if (!counterpartId) {
			continue;
		}

		const key = `${message.propertyId}:${counterpartId}`;
		if (!grouped.has(key)) {
			grouped.set(key, {
				key,
				propertyId: message.propertyId,
				counterpartId,
				counterpartName: message.senderId === currentUserId.value ? message.to : message.from,
				subject: message.subject,
				messages: [],
				lastMessageAt: null,
				lastMessageBody: '',
			});
		}

		const thread = grouped.get(key);
		thread.messages.push(message);
		const createdAt = new Date(message.createdAt).getTime();
		if (!thread.lastMessageAt || createdAt > thread.lastMessageAt) {
			thread.lastMessageAt = createdAt;
			thread.lastMessageBody = message.body;
		}
	}

	const list = Array.from(grouped.values());
	for (const thread of list) {
		thread.messages.sort((a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime());
	}

	list.sort((a, b) => (b.lastMessageAt ?? 0) - (a.lastMessageAt ?? 0));
	return list;
});

const selectedThread = computed(() => {
	return threads.value.find((thread) => thread.key === selectedThreadKey.value) ?? null;
});

watch(threads, (value) => {
	if (!value.length) {
		selectedThreadKey.value = null;
		return;
	}

	if (!selectedThreadKey.value || !value.some((thread) => thread.key === selectedThreadKey.value)) {
		selectedThreadKey.value = value[0].key;
	}
}, { immediate: true });

async function load() {
	loading.value = true;
	const profile = await getProfile();
	currentUserId.value = profile?.id ?? null;
	items.value = await listMessages();
	loading.value = false;
	emit('auth-changed');
}

function selectThread(key) {
	selectedThreadKey.value = key;
	replyBody.value = '';
}

function isMine(message) {
	return message.senderId === currentUserId.value;
}


async function sendReply() {
	const thread = selectedThread.value;
	if (!thread) {
		return;
	}

	const counterpartId = thread.counterpartId;
	if (!counterpartId) {
		return;
	}

	await createMessage({
		propertyId: thread.propertyId,
		receiverId: counterpartId,
		body: replyBody.value,
	});

	replyBody.value = '';
	await load();
}

onMounted(load);
</script>
