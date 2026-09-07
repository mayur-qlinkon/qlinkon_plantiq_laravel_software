{{--
    AI Chatbot Popup Component
    Include this in: resources/views/layouts/admin.blade.php
    Just before the closing </div> of the main layout (after the announcement popup section)

    Usage: @include('components.ai-chatbot-popup')
--}}

<div
    x-data="aiChatbot()"
   
    id="ai-chatbot-root"
    class="fixed bottom-4 right-4 sm:bottom-5 sm:right-5 z-[9990] flex flex-col items-end gap-3"
    style="font-family: inherit;"
>
    {{-- ── Chat Window ─────────────────────────────────────────── --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden w-[calc(100vw-2rem)] sm:w-[380px] h-[calc(100dvh-7rem)] sm:h-[550px] max-h-[800px]"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 sm:py-3.5 border-b border-gray-100 flex-shrink-0"
             style="background: var(--brand-600);">
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center">
                    <svg id="Layer_1" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 191.7 146.7">
                        <!-- Generator: Adobe Illustrator 30.4.0, SVG Export Plug-In . SVG Version: 2.1.4 Build 226)  -->
                        <g>
                            <path d="M168.7,55.6c-.7-3.6-1.8-7.2-3.1-10.5-7.9-18.9-26.6-32.1-48.4-32.1h-42.6c-29,0-52.4,23.5-52.4,52.4v24.9c0,29,23.5,52.4,52.4,52.4h42.6c19.4,0,36.4-10.5,45.4-26.2,2.1-3.6,3.7-7.4,4.9-11.5,1.4-4.7,2.1-9.6,2.1-14.7v-24.9c0-3.4-.3-6.7-.9-9.8ZM158.3,87.7c0,16.2-13.1,29.3-29.3,29.3H63.6c-16.2,0-29.3-13.1-29.3-29.3v-7.2c0-16.2,13.1-29.3,29.3-29.3h65.4c16.2,0,29.3,13.1,29.3,29.3v7.2Z" fill="none" stroke="#fff" stroke-miterlimit="10" stroke-width="4.8"/>
                            <path d="M58.3,89.3c0-5.7,4.7-10.4,10.4-10.4s10.4,4.7,10.4,10.4" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="5.8"/>
                            <path d="M113,89.3c0-5.7,4.7-10.4,10.4-10.4s10.4,4.7,10.4,10.4" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="5.8"/>
                            <path d="M187.4,80.5c0,12.9-5.9,23.6-13.5,25.3-.7.1-1.4.2-2.1.2-1.5,0-3-.4-4.4-1,1.4-4.7,2.1-9.6,2.1-14.7v-24.9c0-3.4-.3-6.7-.9-9.8,1-.4,2.1-.5,3.2-.5s1.4,0,2.1.2c7.6,1.7,13.5,12.4,13.5,25.3Z" fill="none" stroke="#fff" stroke-miterlimit="10" stroke-width="4.8"/>
                            <path d="M4.3,80.5c0,12.9,5.9,23.6,13.5,25.3.7.1,1.4.2,2.1.2,1.5,0,3-.4,4.4-1-1.4-4.7-2.1-9.6-2.1-14.7v-24.9c0-3.4.3-6.7.9-9.8-1-.4-2.1-.5-3.2-.5s-1.4,0-2.1.2c-7.6,1.7-13.5,12.4-13.5,25.3Z" fill="none" stroke="#fff" stroke-miterlimit="10" stroke-width="4.8"/>
                        </g>
                        <path d="M117.3,13h-42.9c.8-5.1,5.2-9,10.6-9h21.8c5.3,0,9.8,3.9,10.6,9Z" fill="none" stroke="#fff" stroke-miterlimit="10" stroke-width="4.8"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white text-sm sm:text-base font-bold leading-none">Plantiq AI</p>
                    <p class="text-white/80 text-[10px] sm:text-xs mt-0.5 sm:mt-1">Ask me anything about your business</p>
                </div>
            </div>
            <div class="flex items-center gap-1 sm:gap-1.5">
                {{-- <button @click="newChat()" title="New Chat"
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button> --}}
                <button @click="toggleHistory()" title="Chat history"
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                        <path d="M3 3v5h5"></path><path d="M12 7v5l3 2"></path>
                    </svg>
                </button>
                <button @click="newChat()" title="New chat"
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
                <button @click="open = false" title="Close"
                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ── History Drawer (DB-backed conversation list) ───────── --}}
        <div x-show="showHistory" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             class="absolute inset-0 top-[57px] sm:top-[61px] z-20 bg-white flex flex-col">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 flex-shrink-0">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Recent chats</span>
                <button @click="showHistory = false" class="text-gray-400 hover:text-gray-700 text-sm">Done</button>
            </div>
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                <p x-show="!historyLoading && conversations.length === 0" class="text-center text-xs text-gray-400 py-8">No conversations yet.</p>
                <template x-for="c in conversations" :key="c.id">
                    <div class="group flex items-center gap-2 rounded-lg hover:bg-gray-50 px-2.5 py-2 cursor-pointer"
                         @click="loadConversation(c.id)">
                        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span class="flex-1 text-[13px] text-gray-700 truncate" x-text="c.title || 'Untitled chat'"></span>
                        <button @click.stop="deleteConversation(c.id)"
                                class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-500 transition-opacity flex-shrink-0">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Language Selector Strip --}}
        <div class="flex items-center gap-2 px-4 py-2 border-b border-gray-100 bg-gray-50/80 flex-shrink-0 overflow-x-auto no-scrollbar flex-nowrap">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider select-none flex-shrink-0">Lang:</span>
            <template x-for="opt in [{code:'en',label:'EN'},{code:'hi',label:'हिंदी'},{code:'gu',label:'ગુજ'},{code:'hinglish',label:'Hinglish'}]" :key="opt.code">
                <button
                    @click="setLanguage(opt.code)"
                    :class="language === opt.code ? 'text-white border-transparent font-bold' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-100 hover:text-gray-900'"
                    :style="language === opt.code ? 'background: var(--brand-600);' : ''"
                    class="text-[11px] px-2.5 py-1 rounded-lg border transition-all flex-shrink-0 whitespace-nowrap shadow-sm cursor-pointer"
                    x-text="opt.label"
                ></button>
            </template>
        </div>

        {{-- Quick Prompts (shown only when no messages) --}}
        <div x-show="messages.length === 0" class="px-3 sm:px-4 pt-3 sm:pt-4 flex flex-wrap gap-1.5 sm:gap-2">
            <template x-for="q in quickPrompts" :key="q">
                <button @click="sendQuick(q)"
                    class="text-[11px] sm:text-xs font-medium px-2.5 py-1.5 sm:px-3 sm:py-2 rounded-lg border border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100 hover:border-gray-300 transition-colors">
                    <span x-text="q"></span>
                </button>
            </template>
        </div>

        {{-- Messages --}}
        <div id="ai-chat-messages" class="flex-1 overflow-y-auto overscroll-contain px-3 py-3 sm:px-4 sm:py-4 space-y-3 sm:space-y-4" style="scroll-behavior: smooth;">

            {{-- Empty state --}}
            <div x-show="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center px-4 pb-8">
                
                <p class="text-sm sm:text-base font-bold text-gray-700">Hi! I'm your Plantiq AI assistant.</p>
                <p class="text-xs sm:text-sm text-gray-400 mt-1 sm:mt-1.5 max-w-[200px] sm:max-w-[240px]">Get instant help with your business operations, insights, and daily tasks.</p>
            </div>

            {{-- Message bubbles --}}
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    {{-- AI avatar --}}
                    <div 
                        x-show="msg.role === 'assistant'"
                        class="w-6 h-6 sm:w-9 sm:h-9 rounded-full flex-shrink-0 flex items-center justify-center mr-2 sm:mr-2.5 mt-0.5"
                        style="background: var(--brand-600);"
                    >
                        <svg 
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 200 200"
                            class="w-3.5 h-3.5 sm:w-5 sm:h-5"
                            fill="none"
                        >
                            <path d="M184.2,80.5c-.7-.2-1.5-.2-2.2-.2-1.2,0-2.3.2-3.4.6-.7-3.9-1.9-7.7-3.4-11.3-8.5-20.3-28.6-34.6-52-34.6-.9-5.5-5.6-9.7-11.4-9.7h-23.5c-5.8,0-10.5,4.2-11.4,9.7-28.2,0-51.3,18.8-55.4,45.9,0-.3,0-.2,0,0-1.1-.4-2.3-.6-3.4-.6s-1.5,0-2.2.2c-8.2,1.8-14.5,13.3-14.5,27.3s6.3,25.5,14.5,27.3c.7.2,1.5.2,2.2.2,1.6,0,3.2-.4,4.7-1.1,0-.1,0-.3-.1-.4,6.7,23.7,28.5,41,54.4,41h46c21,0,39.2-11.4,49-28.3,2.2-3.8,4-8,5.3-12.4.2-.6.4-1.3.5-1.9-.2.6-.3,1.3-.5,1.9,1.5.7,3.1,1.1,4.7,1.1s1.5,0,2.2-.2c8.2-1.8,14.5-13.3,14.5-27.3s-6.3-25.5-14.5-27.3ZM167.3,115.5c0,17.4-14.1,31.5-31.6,31.5h-70.5c-17.4,0-31.5-14.1-31.5-31.5v-7.8c0-17.4,14.1-31.5,31.5-31.5h70.5c17.4,0,31.6,14.1,31.6,31.5v7.8ZM70.7,99.5c-9.7,0-17.7,7.9-17.7,17.7s2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5,2.1-4.7,4.7-4.7,4.8,2.1,4.8,4.7,2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5c0-9.7-7.9-17.7-17.7-17.7ZM129.7,99.5c-9.7,0-17.7,7.9-17.7,17.7s2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5,2.1-4.7,4.7-4.7,4.7,2.1,4.7,4.7,2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5c0-9.7-7.9-17.7-17.7-17.7Z" 
                                fill="#fff"
                            />
                        </svg>
                    </div>

                    <div
                        :class="msg.role === 'user'
                            ? 'bg-brand-600 text-white rounded-2xl rounded-tr-sm px-3.5 py-2.5 sm:px-4 sm:py-3 max-w-[85%] sm:max-w-[80%]'
                            : 'bg-gray-100 text-gray-800 rounded-2xl rounded-tl-sm px-3.5 py-2.5 sm:px-4 sm:py-3 max-w-[85%] sm:max-w-[85%]'"
                        style="word-break: break-word; font-size: 13px; line-height: 1.55;"
                        x-html="formatMessage(msg.content)"
                    ></div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="loading" class="flex justify-start">
                <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex-shrink-0 flex items-center justify-center mr-2 sm:mr-2.5" style="background: var(--brand-600);">
                    <svg 
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 200 200"
                            class="w-3.5 h-3.5 sm:w-5 sm:h-5"
                            fill="none"
                        >
                            <path d="M184.2,80.5c-.7-.2-1.5-.2-2.2-.2-1.2,0-2.3.2-3.4.6-.7-3.9-1.9-7.7-3.4-11.3-8.5-20.3-28.6-34.6-52-34.6-.9-5.5-5.6-9.7-11.4-9.7h-23.5c-5.8,0-10.5,4.2-11.4,9.7-28.2,0-51.3,18.8-55.4,45.9,0-.3,0-.2,0,0-1.1-.4-2.3-.6-3.4-.6s-1.5,0-2.2.2c-8.2,1.8-14.5,13.3-14.5,27.3s6.3,25.5,14.5,27.3c.7.2,1.5.2,2.2.2,1.6,0,3.2-.4,4.7-1.1,0-.1,0-.3-.1-.4,6.7,23.7,28.5,41,54.4,41h46c21,0,39.2-11.4,49-28.3,2.2-3.8,4-8,5.3-12.4.2-.6.4-1.3.5-1.9-.2.6-.3,1.3-.5,1.9,1.5.7,3.1,1.1,4.7,1.1s1.5,0,2.2-.2c8.2-1.8,14.5-13.3,14.5-27.3s-6.3-25.5-14.5-27.3ZM167.3,115.5c0,17.4-14.1,31.5-31.6,31.5h-70.5c-17.4,0-31.5-14.1-31.5-31.5v-7.8c0-17.4,14.1-31.5,31.5-31.5h70.5c17.4,0,31.6,14.1,31.6,31.5v7.8ZM70.7,99.5c-9.7,0-17.7,7.9-17.7,17.7s2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5,2.1-4.7,4.7-4.7,4.8,2.1,4.8,4.7,2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5c0-9.7-7.9-17.7-17.7-17.7ZM129.7,99.5c-9.7,0-17.7,7.9-17.7,17.7s2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5,2.1-4.7,4.7-4.7,4.7,2.1,4.7,4.7,2.9,6.5,6.5,6.5,6.5-2.9,6.5-6.5c0-9.7-7.9-17.7-17.7-17.7Z" 
                                fill="#fff"
                            />
                        </svg>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1.5 sm:gap-2">
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="px-3 pb-3 pt-2 sm:px-4 sm:pb-4 sm:pt-3 border-t border-gray-100 flex-shrink-0 bg-white">
            <div class="flex items-end gap-2 bg-gray-50 rounded-xl border border-gray-200 px-3 py-2 sm:px-3 sm:py-2.5 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500/20 transition-all"
                 style="--brand-500: var(--brand-600)">
                <textarea
                    x-ref="input"
                    x-model="inputText"
                    @keydown.enter="if (!$event.isComposing && !$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                    @input="autoResize($refs.input)"
                    placeholder="Ask about your business.."
                    rows="1"
                    :disabled="loading"
                    class="flex-1 bg-transparent text-[13px] sm:text-sm text-gray-800 placeholder-gray-400 resize-none outline-none leading-relaxed disabled:opacity-50"
                    style="max-height: 100px; min-height: 24px;"
                ></textarea>
                <button @click="sendMessage()" :disabled="loading || !inputText.trim()"
                    class="flex-shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg flex items-center justify-center text-white transition-all disabled:opacity-40 disabled:cursor-not-allowed mb-0.5"
                    style="background: var(--brand-600);">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
            <div class="flex items-center justify-between mt-1.5 sm:mt-2 gap-2">
                <p class="text-[10px] sm:text-xs text-gray-400">Enter to send · Shift+Enter for new line</p>
                <div x-show="usageLabel()" x-cloak class="flex items-center gap-1.5 flex-shrink-0" :title="usageLabel()">
                    <div class="w-12 h-1 rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             :class="usagePct() >= 90 ? 'bg-red-500' : (usagePct() >= 70 ? 'bg-amber-500' : 'bg-emerald-500')"
                             :style="'width:' + usagePct() + '%'"></div>
                    </div>
                    <span class="text-[10px] text-gray-400 tabular-nums" x-text="usagePct() + '%'"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FAB Button ───────────────────────────────────────────── --}}
    {{-- CSS Styles --}}
    <style>
        @keyframes quantum-float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
        }
        @keyframes orbit-alpha {
            0% { transform: rotate(0deg) translateX(30px) rotate(0deg); }
            100% { transform: rotate(360deg) translateX(30px) rotate(-360deg); }
        }
        @keyframes orbit-beta {
            0% { transform: rotate(180deg) translateX(26px) rotate(-180deg); }
            100% { transform: rotate(540deg) translateX(26px) rotate(-540deg); }
        }
        .animate-core-float { animation: quantum-float 4s ease-in-out infinite; }
        .animate-node-a { animation: orbit-alpha 3.5s linear infinite; }
        .animate-node-b { animation: orbit-beta 2.8s linear infinite; }
    </style>

    {{-- Component Code --}}
    <div class="relative inline-block">
        {{-- Orbiting Data Nodes --}}
        <template x-if="!open">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                <div class="absolute top-1/2 left-1/2 w-2 h-2 -mt-1 -ml-1 bg-brand-500 rounded-full brand-glow animate-node-a"></div>
                <div class="absolute top-1/2 left-1/2 w-1.5 h-1.5 -mt-0.75 -ml-0.75 bg-brand-700 rounded-full brand-glow animate-node-b"></div>
            </div>
        </template>

        <button @click="open = !open"
            class="w-14 h-14 sm:w-16 sm:h-16 lg:w-[72px] lg:h-[72px] bg-transparent flex items-center justify-center transition-transform hover:scale-105 active:scale-95 relative"
            title="AI Assistant">
            
            <div class="w-full h-full flex items-center justify-center">
                {{-- Floating Main Core Icon --}}
                <div x-show="!open" class="w-12 h-12 sm:w-14 sm:h-14 lg:w-16 lg:h-16 animate-core-float">
                    <img src="{{ asset('assets/icons/chatbot.png') }}" alt="AI" class="w-full h-full object-contain filter drop-shadow-[0_4px_12px_rgba(0,0,0,0.15)]">
                </div>

                {{-- Close Trigger Window --}}
                <div x-show="open" x-cloak class="w-10 h-10 rounded-full bg-slate-900 border border-white/20 flex items-center justify-center shadow-md">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>

            <span x-show="!open && hasNewReply"
                x-cloak
                class="absolute top-1 right-1 flex h-3.5 w-3.5">

                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-40"></span>

                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-brand-500 border-2 border-white shadow-md"></span>
            </span>
            
        </button>
    </div>


</div>

<style>
    /* Custom Scrollbar for Chat Messages */
    #ai-chat-messages::-webkit-scrollbar { width: 4px; }
    #ai-chat-messages::-webkit-scrollbar-track { background: transparent; }
    #ai-chat-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    #ai-chat-messages::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    
    /* Ensure styles apply properly on specific mobile browsers */
    @supports (-webkit-touch-callout: none) {
        .h-\[calc\(100dvh-7rem\)\] {
            height: calc(100vh - 7rem); /* Fallback for older iOS */
            height: calc(100dvh - 7rem);
        }
    }
</style>

<script>
window.aiChatbot = function () {
    return {
        open:           false,
        loading:        false,
        hasNewReply:    false,
        messages:       [],
        inputText:      '',
        conversationId: null,
        language:       localStorage.getItem('ai_chat_lang') || 'en',
        csrfToken:      document.querySelector('meta[name="csrf-token"]')?.content ?? '',

        // ── Chat history (DB-backed, per user) ──
        showHistory:    false,
        conversations:  [],
        historyLoading: false,

        // ── Daily token budget (for the usage meter) ──
        usage:          { used: 0, limit: 0, remaining: null, unlimited: false },

        quickPrompts: [
            'This month expenses',
            'How many orders today?',
            'Low stock products',
            'Follow-up reminders',
            'This month purchases',
        ],

        urls: {
            chat:          '{{ route("admin.ai-chatbot.chat") }}',
            usage:         '{{ route("admin.ai-chatbot.usage") }}',
            conversations: '{{ route("admin.ai-chatbot.conversations") }}',
        },

        init() {
            this.$watch('open', val => {
                if (val) {
                    this.hasNewReply = false;
                    this.fetchUsage();
                    this.loadConversations(true);
                }
            });

            window.addEventListener('spa:navigated', () => {
                this.$nextTick(() => this.scrollToBottom());
            });
        },

        headers() {
            return {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     this.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':           'application/json',
            };
        },

        async fetchUsage() {
            try {
                const res  = await fetch(this.urls.usage, { headers: this.headers() });
                const data = await res.json();
                if (data && data.tokens) this.usage = data.tokens;
            } catch (e) { /* non-fatal */ }
        },

        async loadConversations(autoOpenLatest = false) {
            this.historyLoading = true;
            try {
                const res  = await fetch(this.urls.conversations, { headers: this.headers() });
                const data = await res.json();
                this.conversations = data.conversations || [];

                if (autoOpenLatest && this.messages.length === 0 && this.conversations.length > 0) {
                    await this.loadConversation(this.conversations[0].id);
                }
            } catch (e) {
                this.conversations = [];
            }
            this.historyLoading = false;
        },

        async loadConversation(id) {
            this.showHistory = false;
            this.loading     = true;
            try {
                const res  = await fetch(this.urls.conversations + '/' + id, { headers: this.headers() });
                const data = await res.json();
                if (data.success) {
                    this.conversationId = data.conversation_id;
                    this.messages = (data.messages || []).map(m => ({ role: m.role, content: m.content }));
                }
            } catch (e) { /* ignore */ }
            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        async deleteConversation(id) {
            try {
                await fetch(this.urls.conversations + '/' + id, { method: 'DELETE', headers: this.headers() });
            } catch (e) { /* ignore */ }
            this.conversations = this.conversations.filter(c => c.id !== id);
            if (this.conversationId === id) this.newChat();
        },

        async toggleHistory() {
            this.showHistory = !this.showHistory;
            if (this.showHistory) await this.loadConversations();
        },

        newChat() {
            this.messages       = [];
            this.conversationId = null;
            this.hasNewReply    = false;
            this.showHistory    = false;
        },

        setLanguage(lang) {
            this.language = lang;
            localStorage.setItem('ai_chat_lang', lang);
        },

        sendQuick(text) {
            this.inputText = text;
            this.sendMessage();
        },

        async sendMessage() {
            const text = this.inputText.trim();
            if (!text || this.loading) return;

            this.messages.push({ role: 'user', content: text });
            this.inputText = '';
            this.loading   = true;
            this.scrollToBottom();

            if (this.$refs.input) {
                this.$refs.input.style.height = 'auto';
            }

            try {
                const res = await fetch(this.urls.chat, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        message:         text,
                        language:        this.language,
                        conversation_id: this.conversationId,
                    }),
                });

                const data = await res.json();

                this.conversationId = data.conversation_id ?? this.conversationId;
                this.messages.push({
                    role:    'assistant',
                    content: data.reply || 'Sorry, something went wrong. Please try again.',
                });

                if (!this.open) this.hasNewReply = true;

                this.fetchUsage();
                this.loadConversations();
            } catch (e) {
                console.error('AI Chatbot network error:', e);
                this.messages.push({ role: 'assistant', content: 'Network error. Please check your connection.' });
            }

            this.loading = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        usageLabel() {
            if (!this.usage || this.usage.unlimited) return '';
            if (!this.usage.limit || this.usage.limit <= 0) return '';
            return this.usage.used.toLocaleString() + ' / ' + this.usage.limit.toLocaleString() + ' tokens today';
        },

        usagePct() {
            if (!this.usage || this.usage.unlimited || !this.usage.limit || this.usage.limit <= 0) return 0;
            return Math.min(100, Math.round((this.usage.used / this.usage.limit) * 100));
        },

        scrollToBottom() {
            const el = document.getElementById('ai-chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        },

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 100) + 'px';
        },

        formatMessage(text) {
            if (!text) return '';
            return text
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code class="bg-black/10 px-1 rounded text-[11px] sm:text-xs font-mono">$1</code>')
                .replace(/\n/g, '<br>');
        },
    };
};
</script>