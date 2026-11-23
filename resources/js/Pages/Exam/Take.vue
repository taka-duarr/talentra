<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    attempt: { type: Object, required: true },
    package: { type: Object, required: true },
    questions: { type: Array, required: true },
    answers: { type: Object, required: true },
    urls: { type: Object, required: true },
});

const letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
const storageKey = `talentra-exam-${props.attempt.id}`;

/* ---------- answers state ---------- */
const emptyAnswer = () => ({ selected_option_ids: [], statement_answers: {}, answer_text: '', is_flagged: false });
const state = reactive({});
for (const question of props.questions) {
    const saved = props.answers[question.id];
    state[question.id] = saved ? { ...emptyAnswer(), ...saved, statement_answers: { ...(saved.statement_answers || {}) } } : emptyAnswer();
}

const currentIndex = ref(0);
const current = computed(() => props.questions[currentIndex.value]);
const currentAnswer = computed(() => state[current.value.id]);
const fontScale = ref(1);
const showNav = ref(false);
const showConfirm = ref(false);
const submitting = ref(false);

function isAnswered(question) {
    const answer = state[question.id];
    if (question.type === 'short_answer') return answer.answer_text.trim() !== '';
    if (question.type === 'true_false') return question.options.every((option) => answer.statement_answers[option.id] !== undefined);
    return answer.selected_option_ids.length > 0;
}

const answeredCount = computed(() => props.questions.filter(isAnswered).length);
const flaggedCount = computed(() => props.questions.filter((question) => state[question.id].is_flagged).length);
const unansweredCount = computed(() => props.questions.length - answeredCount.value);

const groupedQuestions = computed(() =>
    props.package.sections
        .map((section) => ({ ...section, questions: props.questions.filter((question) => question.section_id === section.id) }))
        .filter((section) => section.questions.length > 0),
);

/* ---------- server timer ---------- */
const deadline = Date.now() + props.attempt.remaining_seconds * 1000;
const remaining = ref(props.attempt.remaining_seconds);
let timerHandle = null;

const timeLabel = computed(() => {
    const total = Math.max(0, remaining.value);
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const seconds = total % 60;
    const pad = (value) => String(value).padStart(2, '0');
    return hours > 0 ? `${hours}:${pad(minutes)}:${pad(seconds)}` : `${pad(minutes)}:${pad(seconds)}`;
});
const timeIsLow = computed(() => remaining.value <= 300);

/* ---------- autosave ---------- */
const saveStatus = ref('saved');
const pendingQuestions = new Set();
let saveTimer = null;
let saving = false;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });
    if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }
    return response.json();
}

function backupPending() {
    try {
        const pending = {};
        for (const id of pendingQuestions) pending[id] = state[id];
        localStorage.setItem(storageKey, JSON.stringify(pending));
    } catch (error) {
        // ignore
    }
}

function queueSave(questionId, delay = 250) {
    pendingQuestions.add(questionId);
    saveStatus.value = 'saving';
    backupPending();
    clearTimeout(saveTimer);
    saveTimer = setTimeout(flushSaves, delay);
}

async function flushSaves() {
    if (saving || pendingQuestions.size === 0) return;
    saving = true;

    for (const questionId of [...pendingQuestions]) {
        const answer = state[questionId];
        try {
            const result = await postJson(props.urls.answer, {
                question_id: questionId,
                selected_option_ids: answer.selected_option_ids,
                statement_answers: answer.statement_answers,
                answer_text: answer.answer_text,
            });
            pendingQuestions.delete(questionId);
            if (typeof result.remaining_seconds === 'number') syncRemaining(result.remaining_seconds);
        } catch (error) {
            if (error.status === 422) {
                // Time is over on the server.
                pendingQuestions.clear();
                submitExam();
                break;
            }
            saveStatus.value = 'error';
            saving = false;
            backupPending();
            saveTimer = setTimeout(flushSaves, 3000);
            return;
        }
    }

    saving = false;
    backupPending();
    saveStatus.value = pendingQuestions.size === 0 ? 'saved' : 'saving';
    if (pendingQuestions.size > 0) saveTimer = setTimeout(flushSaves, 250);
}

let serverOffset = 0;
function syncRemaining(serverRemaining) {
    const local = Math.round((deadline + serverOffset - Date.now()) / 1000);
    if (Math.abs(local - serverRemaining) > 3) {
        serverOffset += (serverRemaining - local) * 1000;
    }
}

// Restore answers that were changed offline and never reached the server.
try {
    const pending = JSON.parse(localStorage.getItem(storageKey) || '{}');
    for (const [questionId, answer] of Object.entries(pending)) {
        if (state[questionId]) {
            state[questionId] = { ...state[questionId], ...answer };
            queueSave(Number(questionId));
        }
    }
} catch (error) {
    // Storage unavailable: autosave still works, only the offline backup is lost.
}

/* ---------- answer interactions ---------- */
function chooseSingle(optionId) {
    currentAnswer.value.selected_option_ids = [optionId];
    queueSave(current.value.id);
}

function toggleMultiple(optionId) {
    const selected = currentAnswer.value.selected_option_ids;
    currentAnswer.value.selected_option_ids = selected.includes(optionId)
        ? selected.filter((id) => id !== optionId)
        : [...selected, optionId];
    queueSave(current.value.id);
}

function setStatement(optionId, value) {
    currentAnswer.value.statement_answers = { ...currentAnswer.value.statement_answers, [optionId]: value };
    queueSave(current.value.id);
}

function clearAnswer() {
    const flagged = currentAnswer.value.is_flagged;
    state[current.value.id] = { ...emptyAnswer(), is_flagged: flagged };
    queueSave(current.value.id);
}

watch(
    () => currentAnswer.value.answer_text,
    (value, oldValue) => {
        if (current.value.type === 'short_answer' && value !== oldValue) queueSave(current.value.id, 800);
    },
);

async function toggleFlag() {
    const question = current.value;
    const flagged = !state[question.id].is_flagged;
    state[question.id].is_flagged = flagged;
    try {
        await postJson(props.urls.flag, { question_id: question.id, flagged });
    } catch (error) {
        state[question.id].is_flagged = !flagged;
    }
}

function goTo(index) {
    currentIndex.value = Math.min(Math.max(index, 0), props.questions.length - 1);
    showNav.value = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openConfirm() {
    flushSaves();
    showConfirm.value = true;
}

function submitExam() {
    if (submitting.value) return;
    submitting.value = true;
    clearInterval(timerHandle);
    try {
        localStorage.removeItem(storageKey);
    } catch (error) {
        // ignore
    }
    router.post(props.urls.submit, {}, { onFinish: () => (submitting.value = false) });
}

async function finishNow() {
    await flushSaves();
    submitExam();
}

/* ---------- lifecycle ---------- */
function onVisibilityChange() {
    if (document.visibilityState === 'hidden') {
        postJson(props.urls.activity, {}).catch(() => {});
    }
}

function onKeydown(event) {
    if (showConfirm.value || ['INPUT', 'TEXTAREA'].includes(event.target.tagName)) return;
    if (event.key === 'ArrowRight') goTo(currentIndex.value + 1);
    if (event.key === 'ArrowLeft') goTo(currentIndex.value - 1);
}

onMounted(() => {
    timerHandle = setInterval(() => {
        remaining.value = Math.max(0, Math.round((deadline + serverOffset - Date.now()) / 1000));
        if (remaining.value === 0) {
            finishNow();
        }
    }, 1000);
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    clearInterval(timerHandle);
    clearTimeout(saveTimer);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div class="min-h-screen pb-24 lg:pb-10">
        <Head :title="package.title" />

        <!-- Top bar -->
        <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-extrabold text-ink">{{ package.title }}</p>
                    <p class="text-xs text-subtle" aria-live="polite">
                        <span v-if="saveStatus === 'saved'">✓ Semua jawaban tersimpan</span>
                        <span v-else-if="saveStatus === 'saving'">Menyimpan…</span>
                        <span v-else class="font-semibold text-coral-600">Gagal menyimpan, mencoba lagi…</span>
                    </p>
                </div>

                <div
                    class="flex items-center gap-2 rounded-xl px-3 py-2 font-mono text-lg font-bold tabular-nums"
                    :class="timeIsLow ? 'bg-coral-100 text-coral-600' : 'bg-navy-50 text-navy-600'"
                    role="timer"
                    :aria-label="`Sisa waktu ${timeLabel}`"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="13" r="8" /><path d="M12 9v4l2.5 2.5" /><path d="M9 2h6" /></svg>
                    {{ timeLabel }}
                </div>

                <button type="button" class="btn btn-primary hidden sm:inline-flex" @click="openConfirm">Selesai</button>
            </div>
        </header>

        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-6 lg:grid-cols-[1fr_300px]">
            <!-- Question -->
            <main>
                <div class="card p-5 sm:p-7">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="grid size-9 place-items-center rounded-xl bg-navy-600 text-sm font-bold text-white">{{ current.number }}</span>
                            <span class="text-sm font-semibold text-subtle">{{ current.section }}</span>
                        </div>
                        <div class="flex items-center gap-1" role="group" aria-label="Ukuran huruf">
                            <button type="button" class="btn btn-ghost btn-sm" :disabled="fontScale <= 0.9" @click="fontScale = Math.max(0.9, fontScale - 0.1)" aria-label="Perkecil huruf">A−</button>
                            <button type="button" class="btn btn-ghost btn-sm" :disabled="fontScale >= 1.4" @click="fontScale = Math.min(1.4, fontScale + 0.1)" aria-label="Perbesar huruf">A+</button>
                        </div>
                    </div>

                    <div :style="{ fontSize: `${fontScale}rem` }">
                        <div class="prose-materi mt-5" v-html="current.body" />
                        <p class="mt-3 text-[0.85em] font-semibold text-subtle">{{ current.hint }}</p>

                        <!-- Single / weighted choice -->
                        <div v-if="current.type === 'single_choice' || current.type === 'weighted'" class="mt-5 space-y-2.5" role="radiogroup" :aria-label="`Pilihan jawaban soal ${current.number}`">
                            <button
                                v-for="(option, index) in current.options"
                                :key="option.id"
                                type="button"
                                role="radio"
                                :aria-checked="currentAnswer.selected_option_ids.includes(option.id)"
                                class="flex w-full items-start gap-3 rounded-xl border px-4 py-3 text-left transition"
                                :class="currentAnswer.selected_option_ids.includes(option.id) ? 'border-brand-500 bg-brand-50' : 'border-line hover:border-navy-100'"
                                @click="chooseSingle(option.id)"
                            >
                                <span
                                    class="grid size-7 flex-none place-items-center rounded-lg text-[0.8em] font-bold"
                                    :class="currentAnswer.selected_option_ids.includes(option.id) ? 'bg-brand-600 text-white' : 'bg-navy-50 text-navy-600'"
                                >{{ letters[index] }}</span>
                                <span class="pt-0.5" v-html="option.body" />
                            </button>
                        </div>

                        <!-- Multiple choice -->
                        <div v-else-if="current.type === 'multiple_choice'" class="mt-5 space-y-2.5">
                            <label
                                v-for="(option, index) in current.options"
                                :key="option.id"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
                                :class="currentAnswer.selected_option_ids.includes(option.id) ? 'border-brand-500 bg-brand-50' : 'border-line hover:border-navy-100'"
                            >
                                <input type="checkbox" class="mt-1 size-4 rounded border-line text-brand-600 focus:ring-brand-500" :checked="currentAnswer.selected_option_ids.includes(option.id)" @change="toggleMultiple(option.id)" />
                                <span class="font-bold text-navy-600">{{ letters[index] }}.</span>
                                <span v-html="option.body" />
                            </label>
                        </div>

                        <!-- True / false per statement -->
                        <div v-else-if="current.type === 'true_false'" class="mt-5 overflow-hidden rounded-xl border border-line">
                            <table class="w-full text-left text-[0.95em]">
                                <thead class="bg-canvas text-[0.8em] font-semibold text-subtle">
                                    <tr><th class="px-4 py-2.5">Pernyataan</th><th class="w-24 px-2 py-2.5 text-center">Benar</th><th class="w-24 px-2 py-2.5 text-center">Salah</th></tr>
                                </thead>
                                <tbody>
                                    <tr v-for="option in current.options" :key="option.id" class="border-t border-line">
                                        <td class="px-4 py-3" v-html="option.body" />
                                        <td class="px-2 py-3 text-center">
                                            <input type="radio" :name="`statement-${option.id}`" class="size-5 border-line text-brand-600 focus:ring-brand-500" :aria-label="`Benar: ${option.body}`" :checked="currentAnswer.statement_answers[option.id] === true" @change="setStatement(option.id, true)" />
                                        </td>
                                        <td class="px-2 py-3 text-center">
                                            <input type="radio" :name="`statement-${option.id}`" class="size-5 border-line text-coral-600 focus:ring-coral-500" :aria-label="`Salah: ${option.body}`" :checked="currentAnswer.statement_answers[option.id] === false" @change="setStatement(option.id, false)" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Short answer -->
                        <div v-else-if="current.type === 'short_answer'" class="mt-5">
                            <label :for="`answer-${current.id}`" class="field-label">Jawabanmu</label>
                            <input :id="`answer-${current.id}`" v-model="currentAnswer.answer_text" type="text" maxlength="255" class="field max-w-sm text-[1em]" autocomplete="off" />
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
                        <button type="button" class="btn btn-ghost btn-sm" @click="clearAnswer" :disabled="!isAnswered(current)">Hapus jawaban</button>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline" :disabled="currentIndex === 0" @click="goTo(currentIndex - 1)">← Sebelumnya</button>
                            <button
                                type="button"
                                class="btn"
                                :class="currentAnswer.is_flagged ? 'bg-sun-400 text-ink hover:bg-sun-400/90' : 'btn-outline'"
                                :aria-pressed="currentAnswer.is_flagged"
                                @click="toggleFlag"
                            >{{ currentAnswer.is_flagged ? '★ Ragu-ragu' : '☆ Ragu-ragu' }}</button>
                            <button v-if="currentIndex < questions.length - 1" type="button" class="btn btn-navy" @click="goTo(currentIndex + 1)">Berikutnya →</button>
                            <button v-else type="button" class="btn btn-primary" @click="openConfirm">Selesai</button>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Navigation panel -->
            <aside
                class="fixed inset-x-0 bottom-0 z-40 max-h-[70vh] overflow-y-auto rounded-t-2xl border-t border-line bg-white p-5 shadow-2xl transition-transform lg:static lg:z-auto lg:max-h-none lg:translate-y-0 lg:rounded-2xl lg:border lg:shadow-none"
                :class="showNav ? 'translate-y-0' : 'translate-y-full'"
                aria-label="Navigasi soal"
            >
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Daftar soal</h2>
                    <button type="button" class="btn btn-ghost btn-sm lg:hidden" @click="showNav = false">Tutup</button>
                </div>
                <p class="mt-1 text-xs text-subtle">{{ answeredCount }} dari {{ questions.length }} soal terjawab</p>

                <div v-for="section in groupedQuestions" :key="section.id" class="mt-4">
                    <p class="mb-2 text-xs font-bold text-subtle">{{ section.name }}</p>
                    <div class="grid grid-cols-6 gap-1.5">
                        <button
                            v-for="question in section.questions"
                            :key="question.id"
                            type="button"
                            class="aspect-square rounded-lg text-xs font-bold transition"
                            :class="[
                                state[question.id].is_flagged ? 'bg-sun-400 text-ink' : isAnswered(question) ? 'bg-brand-500 text-white' : 'border border-line bg-white text-muted hover:border-navy-100',
                                current.id === question.id ? 'ring-2 ring-navy-600 ring-offset-2' : '',
                            ]"
                            :aria-label="`Soal ${question.number}${isAnswered(question) ? ', terjawab' : ', belum dijawab'}${state[question.id].is_flagged ? ', ragu-ragu' : ''}`"
                            :aria-current="current.id === question.id ? 'step' : undefined"
                            @click="goTo(question.number - 1)"
                        >{{ question.number }}</button>
                    </div>
                </div>

                <ul class="mt-5 space-y-1.5 text-xs text-muted">
                    <li class="flex items-center gap-2"><span class="size-3 rounded bg-brand-500" /> Terjawab</li>
                    <li class="flex items-center gap-2"><span class="size-3 rounded bg-sun-400" /> Ragu-ragu</li>
                    <li class="flex items-center gap-2"><span class="size-3 rounded border border-line bg-white" /> Belum dijawab</li>
                </ul>

                <button type="button" class="btn btn-primary mt-5 w-full" @click="openConfirm">Selesaikan ujian</button>
            </aside>
        </div>

        <!-- Mobile bottom bar -->
        <div class="fixed inset-x-0 bottom-0 z-30 flex gap-2 border-t border-line bg-white p-3 lg:hidden">
            <button type="button" class="btn btn-outline flex-1" @click="showNav = true">Daftar soal ({{ answeredCount }}/{{ questions.length }})</button>
            <button type="button" class="btn btn-primary" @click="openConfirm">Selesai</button>
        </div>

        <!-- Confirm dialog -->
        <div v-if="showConfirm" class="fixed inset-0 z-50 grid place-items-center bg-ink/40 p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h2 id="confirm-title" class="text-lg font-extrabold">Selesaikan ujian?</h2>
                <p class="mt-2 text-sm text-muted">Setelah diselesaikan, jawaban tidak bisa diubah lagi.</p>
                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-brand-50 p-3"><dt class="text-xs text-brand-700">Terjawab</dt><dd class="text-xl font-extrabold text-brand-700">{{ answeredCount }}</dd></div>
                    <div class="rounded-xl bg-sun-100 p-3"><dt class="text-xs text-sun-600">Ragu-ragu</dt><dd class="text-xl font-extrabold text-sun-600">{{ flaggedCount }}</dd></div>
                    <div class="rounded-xl bg-coral-100 p-3"><dt class="text-xs text-coral-600">Kosong</dt><dd class="text-xl font-extrabold text-coral-600">{{ unansweredCount }}</dd></div>
                </dl>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-outline" @click="showConfirm = false" :disabled="submitting">Kembali</button>
                    <button type="button" class="btn btn-primary" @click="finishNow" :disabled="submitting">{{ submitting ? 'Memproses…' : 'Ya, selesaikan' }}</button>
                </div>
            </div>
        </div>
    </div>
</template>
