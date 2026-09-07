{{-- resources/views/admin/hrm/tasks/partials/_comments.blade.php --}}

<div x-data="taskComments({{ $taskId }})" class="w-full flex flex-col h-full">

    {{-- Header & Polling Indicator --}}
    <div class="flex items-center justify-between mb-4 flex-shrink-0">
        <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
            <i data-lucide="message-square" class="w-4 h-4 text-brand-600"></i>
            Comments (<span x-text="commentCount">0</span>)
        </h3>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="isPolling" x-cloak></i>
            <span x-show="isPolling" x-cloak>Syncing...</span>
        </div>
    </div>

    {{-- Main Comment Input (Modern Toolbar UX) --}}
    <div
        class="mb-5 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden flex-shrink-0 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500 transition-all">

        <textarea x-model="newComment" rows="2"
            x-on:keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); postComment(null); }"
            class="w-full border-0 p-3.5 text-[13px] text-gray-700 outline-none resize-none bg-transparent"
            placeholder="Type your comment... (Enter to post, Shift+Enter for new line)"></textarea>

        {{-- Bottom Toolbar --}}
        <div class="bg-gray-50/80 border-t border-gray-100 px-3 py-2 flex items-center justify-between">
            <span class="text-[10px] text-gray-400 font-medium hidden sm:inline-block">
                <kbd class="bg-white border border-gray-200 px-1 rounded text-gray-500 font-sans shadow-sm">Enter</kbd>
                to post
            </span>

            <button x-on:click="postComment(null)" :disabled="isSubmitting || !newComment.trim()"
                class="ml-auto px-4 py-1.5 bg-brand-600 text-white font-bold rounded-lg text-[11px] hover:bg-brand-700 disabled:opacity-50 transition-colors flex items-center gap-1.5 shadow-sm">
                <i data-lucide="send" class="w-3.5 h-3.5" x-show="!isSubmitting"></i>
                <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="isSubmitting" x-cloak></i>
                Post
            </button>
        </div>
    </div>

    {{-- Reactive Comments List (Scrollable Area) --}}
    <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar flex-1">
        <template x-for="comment in comments" :key="comment.id">
            <div class="flex gap-3">
                <img :src="comment.avatar_url" class="w-8 h-8 rounded-full object-cover shadow-sm flex-shrink-0"
                    onerror="this.style.display='none';">

                <div class="flex-1 min-w-0">
                    <div class="bg-white border border-gray-100 p-3 rounded-xl shadow-sm">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-[13px] font-bold text-gray-900" x-text="comment.user_name"></span>
                            <span class="text-[10px] text-gray-400 font-medium"
                                x-text="comment.created_at_human"></span>
                        </div>
                        <div class="text-[13px] text-gray-700 leading-relaxed whitespace-pre-wrap"
                            x-html="window.taskDescMd ? window.taskDescMd()._inline(comment.body) : comment.body"></div>

                        <button x-on:click="replyingTo = replyingTo === comment.id ? null : comment.id"
                            class="text-[10px] font-bold text-gray-400 hover:text-brand-600 mt-2 flex items-center gap-1 transition-colors uppercase tracking-wider">
                            <i data-lucide="reply" class="w-3 h-3"></i> Reply
                        </button>
                    </div>

                    {{-- Nested Replies --}}
                    <div class="mt-2 pl-4 space-y-2" x-show="comment.replies && comment.replies.length > 0">
                        <template x-for="reply in comment.replies" :key="reply.id">
                            <div class="flex gap-2">
                                <img :src="reply.avatar_url"
                                    class="w-6 h-6 rounded-full object-cover shadow-sm flex-shrink-0"
                                    onerror="this.style.display='none';">
                                <div class="flex-1 bg-gray-50/80 border border-gray-100 p-2.5 rounded-xl">
                                    <div class="flex justify-between items-start mb-0.5">
                                        <span class="text-[12px] font-bold text-gray-900"
                                            x-text="reply.user_name"></span>
                                        <span class="text-[9px] text-gray-400 font-medium"
                                            x-text="reply.created_at_human"></span>
                                    </div>
                                    <div class="text-[12px] text-gray-600 whitespace-pre-wrap"
                                        x-html="window.taskDescMd ? window.taskDescMd()._inline(reply.body) : reply.body">
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Reply Input Form --}}
                    <div class="mt-2 pl-4" x-show="replyingTo === comment.id" x-collapse x-cloak>
                        <div class="relative">
                            <textarea x-model="replyText[comment.id]" rows="2"
                                x-on:keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); postComment(comment.id); }"
                                class="w-full border border-gray-200 rounded-xl p-2.5 pr-20 text-[12px] outline-none focus:border-brand-600 resize-none shadow-sm"
                                placeholder="Write a reply... (Enter to send)"></textarea>
                            <div class="absolute bottom-2.5 right-2.5 flex items-center gap-1">
                                <button x-on:click="replyingTo = null"
                                    class="px-2 py-1 text-[10px] font-bold text-gray-400 hover:bg-gray-100 rounded-lg">Cancel</button>
                                <button x-on:click="postComment(comment.id)"
                                    :disabled="isSubmitting || !(replyText[comment.id] || '').trim()"
                                    class="px-2 py-1 bg-brand-600 text-white font-bold rounded-lg text-[10px] hover:bg-brand-700 disabled:opacity-50 flex items-center gap-1 shadow-sm">
                                    <i data-lucide="loader-2" class="w-3 h-3 animate-spin" x-show="isSubmitting"
                                        x-cloak></i> Send
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="comments.length === 0" class="text-center py-6 bg-gray-50 border border-gray-100 rounded-xl"
            x-cloak>
            <p class="text-[12px] font-semibold text-gray-500">No comments yet. Be the first!</p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function registerTaskCommentsAlpine() {
                function register() {
                    Alpine.data('taskComments', (taskId) => ({
                        taskId: taskId,
                        comments: [],
                        commentCount: 0,
                        newComment: '',
                        replyText: {},
                        replyingTo: null,
                        isSubmitting: false,
                        isPolling: false,
                        pollTimer: null,

                        async init() {
                            this.$watch('task', (newTask) => {
                                if (newTask && newTask.id !== this.taskId) {
                                    this.taskId = newTask.id;
                                    this.fetchComments(false);
                                }
                            });

                            if (this.taskId) {
                                await this.fetchComments(false);
                            }

                            this.pollTimer = setInterval(() => {
                                if (this.taskId && this.$el.offsetParent !== null) {
                                    this.fetchComments(true);
                                }
                            }, 15000);
                        },

                        async fetchComments(isBackground = true) {
                            if (isBackground) this.isPolling = true;
                            try {
                                const res = await fetch(`{{ $commentBase ?? '/admin/hrm/tasks' }}/${this.taskId}/comments`);
                                if (res.ok) {
                                    const data = await res.json();
                                    this.comments = data.comments;
                                    this.commentCount = data.count;
                                    if (typeof lucide !== 'undefined') setTimeout(() => lucide
                                        .createIcons(), 50);
                                }
                            } catch (e) {
                                console.error('Polling error', e);
                            } finally {
                                if (isBackground) this.isPolling = false;
                            }
                        },

                        async postComment(parentId = null) {
                            const body = parentId ? this.replyText[parentId] : this.newComment;
                            if (!body || !body.trim()) return;

                            this.isSubmitting = true;
                            try {
                                const res = await fetch(`{{ $commentBase ?? '/admin/hrm/tasks' }}/${this.taskId}/comments`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        body: body,
                                        parent_id: parentId
                                    })
                                });

                                if (res.ok) {
                                    if (parentId) {
                                        this.replyText[parentId] = '';
                                        this.replyingTo = null;
                                    } else {
                                        this.newComment = '';
                                    }
                                    await this.fetchComments(false);
                                } else {
                                    if (typeof BizAlert !== 'undefined') BizAlert.toast(
                                        'Failed to post comment', 'error');
                                }
                            } catch (e) {
                                if (typeof BizAlert !== 'undefined') BizAlert.toast('Network error',
                                    'error');
                            } finally {
                                this.isSubmitting = false;
                            }
                        }
                    }));
                }

                if (window.Alpine) {
                    register();
                } else {
                    document.addEventListener('alpine:init', register, {
                        once: true
                    });
                }
            })
            ();
        </script>
    @endpush
@endonce
