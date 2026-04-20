@php
    $widgetReady = ($tenantSupportWidget['ready'] ?? false) === true;
    $widgetThreads = $tenantSupportWidget['threads'] ?? collect();
    $activeThread = $tenantSupportWidget['activeThread'] ?? null;
    $widgetUnreadCount = $tenantSupportWidget['unreadCount'] ?? 0;
    $widgetOpen = ($tenantSupportWidget['open'] ?? false) === true;
@endphp

@if($tenantSupportWidget['enabled'] ?? false)
    <div
        id="tenant-chat-widget"
        data-default-open="{{ $widgetOpen ? 'true' : 'false' }}"
        class="fixed bottom-5 right-5 z-50"
    >
        <div
            id="tenant-chat-panel"
            class="hidden absolute bottom-20 right-0 flex h-[min(42rem,calc(100vh-7.5rem))] w-[min(92vw,24rem)] flex-col overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.24)]"
        >
            <div class="bg-gradient-to-r from-cyan-500 via-sky-500 to-teal-400 px-5 py-4 text-white">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-50/80">Support chat</p>
                        <h2 class="mt-1 text-lg font-semibold">Message support</h2>
                        <p class="mt-1 text-xs leading-5 text-cyan-50/90">Quick support chat for your tenant workspace.</p>
                    </div>
                    <button
                        type="button"
                        id="tenant-chat-close"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/25 bg-white/10 text-sm font-semibold text-white transition hover:bg-white/20"
                        aria-label="Close chat"
                    >
                        X
                    </button>
                </div>
            </div>

            <div id="tenant-chat-live-region" class="min-h-0 flex flex-1 flex-col overflow-hidden">
                @include('support.partials.tenant-widget-live', [
                    'widgetReady' => $widgetReady,
                    'widgetThreads' => $widgetThreads,
                    'activeThread' => $activeThread,
                ])
            </div>
        </div>

        <button
            type="button"
            id="tenant-chat-toggle"
            class="tenant-primary-bg relative ml-auto inline-flex h-16 w-16 items-center justify-center rounded-full text-white shadow-lg shadow-emerald-500/30 transition hover:scale-[1.03] hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/50"
            aria-expanded="{{ $widgetOpen ? 'true' : 'false' }}"
            aria-controls="tenant-chat-panel"
            aria-label="Open support chat"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
            </svg>
            <span class="absolute -right-1 -top-1 inline-flex min-h-6 min-w-6 items-center justify-center rounded-full border-2 border-white bg-slate-900 px-1.5 text-[11px] font-bold text-white {{ $widgetUnreadCount > 0 ? '' : 'hidden' }}">
                {{ $widgetUnreadCount }}
            </span>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const widget = document.getElementById('tenant-chat-widget');
            const panel = document.getElementById('tenant-chat-panel');
            const liveRegion = document.getElementById('tenant-chat-live-region');
            const toggle = document.getElementById('tenant-chat-toggle');
            const closeButton = document.getElementById('tenant-chat-close');
            const unreadBadge = toggle?.querySelector('span');
            const snapshotUrl = @json($activeThread
                ? route('support.tenant.snapshot', ['thread' => $activeThread->id])
                : route('support.tenant.snapshot'));

            if (!widget || !panel || !toggle || !liveRegion) {
                return;
            }

            const setOpen = (open) => {
                panel.classList.toggle('hidden', !open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            setOpen(widget.dataset.defaultOpen === 'true');

            toggle.addEventListener('click', () => {
                setOpen(panel.classList.contains('hidden'));
            });

            closeButton?.addEventListener('click', () => setOpen(false));

            const sendSupportRequest = async (formElement) => {
                const formData = new FormData(formElement);
                const formToken = formElement.querySelector('input[name="_token"]')?.value || '';
                const csrfToken = formToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                if (csrfToken && !formData.has('_token')) {
                    formData.append('_token', csrfToken);
                }

                const response = await fetch(formElement.action, {
                    method: formElement.method || 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'text/html,application/xhtml+xml',
                    },
                });

                if (response.status === 419) {
                    formElement.submit();
                    return null;
                }

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Support request failed:', response.status, errorText);
                    window.showToast?.error?.('Unable to send the message right now.');
                    return null;
                }

                return response;
            };

            const refreshWidget = () => {
                if (!window.realtimeRefresh) {
                    return;
                }

                window.realtimeRefresh.refresh('tenant-chat-live-region', snapshotUrl, (_element, data) => {
                    const previousViewport = liveRegion.querySelector('[data-widget-chat-scroll]');
                    const wasNearBottom = previousViewport
                        ? (previousViewport.scrollHeight - previousViewport.scrollTop - previousViewport.clientHeight) < 80
                        : false;

                    if (data.widget_html) {
                        liveRegion.innerHTML = data.widget_html;
                    }

                    if (unreadBadge) {
                        const unreadCount = Number(data.unread_count || 0);
                        unreadBadge.textContent = `${unreadCount}`;
                        unreadBadge.classList.toggle('hidden', unreadCount < 1);
                    }

                    if (wasNearBottom) {
                        const nextViewport = liveRegion.querySelector('[data-widget-chat-scroll]');
                        if (nextViewport) {
                            nextViewport.scrollTop = nextViewport.scrollHeight;
                        }
                    }

                    bindWidgetForms();
                });
            };

            const bindWidgetForms = () => {
                const replyForm = document.getElementById('tenant-widget-reply-form');
                if (replyForm && !replyForm.dataset.boundSupportSubmit) {
                    replyForm.dataset.boundSupportSubmit = 'true';
                    replyForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const messageInput = replyForm.querySelector('textarea[name="message"]');
                    const message = messageInput.value.trim();
                    
                    if (!message) return;

                    try {
                        const response = await sendSupportRequest(replyForm);
                        
                        if (response) {
                            messageInput.value = '';
                            refreshWidget();
                        }
                    } catch (error) {
                        console.error('Error sending message:', error);
                    }
                });
                }

                const createThreadForm = document.getElementById('tenant-widget-create-thread-form');
                if (createThreadForm && !createThreadForm.dataset.boundSupportSubmit) {
                    createThreadForm.dataset.boundSupportSubmit = 'true';
                    createThreadForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const subjectInput = createThreadForm.querySelector('input[name="subject"]');
                    const messageInput = createThreadForm.querySelector('textarea[name="message"]');
                    const subject = subjectInput.value.trim();
                    const message = messageInput.value.trim();
                    
                    if (!subject || !message) return;

                    try {
                        const response = await sendSupportRequest(createThreadForm);
                        
                        if (response) {
                            subjectInput.value = '';
                            messageInput.value = '';
                            refreshWidget();
                        }
                    } catch (error) {
                        console.error('Error creating thread:', error);
                    }
                });
                }
            };

            bindWidgetForms();

            if (window.realtimeRefresh) {
                window.realtimeRefresh.register('tenant-chat-live-region', snapshotUrl, (_element, data) => {
                    if (data.widget_html) {
                        liveRegion.innerHTML = data.widget_html;
                    }

                    if (unreadBadge) {
                        const unreadCount = Number(data.unread_count || 0);
                        unreadBadge.textContent = `${unreadCount}`;
                        unreadBadge.classList.toggle('hidden', unreadCount < 1);
                    }

                    bindWidgetForms();
                }, 5000);
            }
        });
    </script>
@endif
