@props([
'title' => '',
'subtitle' => '',
'icon' => null,
'breadcrumb' => null,
'sections' => [],
'variable' => 'activeTab'
])

<div {{ $attributes->class('sticky -top-px z-40 sm:mb-2 pointer-events-none h-24 transition-all duration-300 ease-in-out') }} x-data="{ 
        scrolled: false,
        isMobile: false,
        dockOpen: false,
        sections: {{ json_encode($sections) }},
        modelKey: @js($variable),
        currentVal: '{{ $sections[0]['id'] ?? '' }}',
        activeSection: null,
        _cleanupFns: [],
        sectionAnimationsEnabled: true,

        shouldAnimateSections() {
            const path = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
            if (path === '/departamentos' || path.startsWith('/departamentos/')) return false;
            if (path === '/epis' || path.startsWith('/epis/')) return false;
            return true;
        },

        isValidSectionId(id) {
            if (!Array.isArray(this.sections) || this.sections.length === 0) {
                return false;
            }
            return this.sections.some((section) => section?.id === id);
        },

        isElementVisible(el) {
            if (!el) return false;
            if (el.classList.contains('hidden')) return false;
            if (!el.getClientRects().length) return false;
            const style = window.getComputedStyle(el);
            return style.visibility !== 'hidden' && style.display !== 'none';
        },

        revealPreAnimatedElements(elements = []) {
            if (!Array.isArray(elements) || elements.length === 0) return;

            elements.forEach((el) => {
                if (!el || !el.hasAttribute?.('data-pre-animate-hidden')) return;
                el.removeAttribute('data-pre-animate-hidden');
            });
        },

        animateElements(elements = []) {
            if (!Array.isArray(elements) || elements.length === 0) return;

            if (!this.sectionAnimationsEnabled || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.revealPreAnimatedElements(elements);
                return;
            }

            elements.forEach((el, idx) => {
                if (!el) return;

                if (el.hasAttribute?.('data-pre-animate-hidden')) {
                    el.removeAttribute('data-pre-animate-hidden');
                }

                if (!this.isElementVisible(el)) return;

                try {
                    el.getAnimations?.().forEach((anim) => anim.cancel());
                } catch (_) {
                    // noop
                }

                try {
                    el.animate(
                        [
                            { opacity: 0, transform: 'translateY(18px)' },
                            { opacity: 1, transform: 'translateY(0)' }
                        ],
                        {
                            duration: 520,
                            easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                            delay: Math.min(idx * 55, 275),
                            // Do not persist transform after animation; otherwise fixed modals
                            // become constrained by transformed ancestors.
                            fill: 'none',
                        }
                    );
                } catch (_) {
                    // noop
                }
            });
        },

        getModelPanels() {
            const scope = this.resolveParentScope();
            if (!scope?.el) return [];

            const candidates = Array.from(scope.el.querySelectorAll('[x-show]'))
                .filter((el) => !this.$el.contains(el))
                .filter((el) => (el.getAttribute('x-show') || '').includes(this.modelKey));

            const isNestedCandidate = (el) => {
                let parent = el.parentElement;
                while (parent && parent !== scope.el) {
                    if (
                        parent.hasAttribute('x-show') &&
                        (parent.getAttribute('x-show') || '').includes(this.modelKey)
                    ) {
                        return true;
                    }
                    parent = parent.parentElement;
                }
                return false;
            };

            return candidates.filter((el) => !isNestedCandidate(el));
        },

        animateCurrentSection(options = {}) {
            const { containerSelector = null } = options || {};

            if (containerSelector) {
                const container = document.querySelector(containerSelector);
                if (container) {
                    const directChildren = Array.from(container.children);
                    if (directChildren.length > 0) {
                        this.animateElements(directChildren);
                        return;
                    }
                }
            }

            const panels = this.getModelPanels();
            if (panels.length > 0) {
                this.animateElements(panels);
            }
        },

        revealCurrentSection(options = {}) {
            const { containerSelector = null } = options || {};

            if (containerSelector) {
                const container = document.querySelector(containerSelector);
                if (container) {
                    const directChildren = Array.from(container.children);
                    if (directChildren.length > 0) {
                        this.revealPreAnimatedElements(directChildren);
                        return;
                    }
                }
            }

            const panels = this.getModelPanels();
            if (panels.length > 0) {
                this.revealPreAnimatedElements(panels);
            }
        },

        animateCurrentSectionWhenReady(options = {}) {
            const run = () => this.animateCurrentSection(options);
            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(run);
                });
            });
        },

        revealCurrentSectionWhenReady(options = {}) {
            const run = () => this.revealCurrentSection(options);
            this.$nextTick(() => {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(run);
                });
            });
        },

        resolveParentScope() {
            let cursor = this.$el?.parentElement || null;
            while (cursor) {
                if (cursor.hasAttribute && cursor.hasAttribute('x-data')) {
                    try {
                        const data = Alpine.$data(cursor);
                        if (data && (this.modelKey in data)) {
                            return { el: cursor, data };
                        }
                    } catch (_) {
                        // noop
                    }
                }
                cursor = cursor.parentElement;
            }
            return null;
        },

        syncParentSection(id) {
            const scope = this.resolveParentScope();
            if (!scope || !scope.data) return;
            scope.data[this.modelKey] = id;
        },

        destroy() {
            if (Array.isArray(this._cleanupFns)) {
                this._cleanupFns.forEach((fn) => {
                    try { fn(); } catch (e) {}
                });
            }
            this._cleanupFns = [];
        },

        init() {
            // Defensive cleanup in case Alpine re-inits the component without destroy.
            this.destroy();

            let main = document.querySelector('main');
            let scrollTarget = main || window;
            const mobileQuery = window.matchMedia('(max-width: 639px)');
            let scrollTicking = false;
            const pushCleanup = (fn) => this._cleanupFns.push(fn);
            
            const onScroll = () => {
                if (this.isMobile) {
                    if (!this.scrolled) {
                        this.scrolled = true;
                    }

                    if (this.dockOpen) {
                        this.dockOpen = false;
                    }
                    return;
                }

                const scrollTop = main ? main.scrollTop : window.pageYOffset;
                // Hysteresis to avoid jitter around the threshold.
                const enterThreshold = 24;
                const leaveThreshold = 8;
                const isScrolled = this.scrolled
                    ? scrollTop > leaveThreshold
                    : scrollTop > enterThreshold;
                
                // Prevent unnecessary updates/re-renders to fix stutter
                if (this.scrolled !== isScrolled) {
                    this.scrolled = isScrolled;
                }
                
                // Force close dock on any scroll
                if (this.dockOpen) {
                     this.dockOpen = false;
                }
            };

            const onScrollRaf = () => {
                if (scrollTicking) return;
                scrollTicking = true;
                window.requestAnimationFrame(() => {
                    onScroll();
                    scrollTicking = false;
                });
            };

            const syncViewportMode = () => {
                this.isMobile = mobileQuery.matches;

                if (this.isMobile) {
                    this.scrolled = true;
                    this.dockOpen = false;
                    return;
                }

                onScroll();
            };

            scrollTarget.addEventListener('scroll', onScrollRaf, { passive: true });
            pushCleanup(() => scrollTarget.removeEventListener('scroll', onScrollRaf));
            if (mobileQuery.addEventListener) {
                mobileQuery.addEventListener('change', syncViewportMode);
                pushCleanup(() => mobileQuery.removeEventListener('change', syncViewportMode));
            } else if (mobileQuery.addListener) {
                mobileQuery.addListener(syncViewportMode);
                pushCleanup(() => mobileQuery.removeListener(syncViewportMode));
            }
            syncViewportMode();

            const onSectionContentUpdated = (event) => {
                const detail = event?.detail || {};
                if (detail.modelKey && detail.modelKey !== this.modelKey) return;
                if (!this.sectionAnimationsEnabled) return;

                this.animateCurrentSectionWhenReady({
                    containerSelector: detail.containerSelector || null,
                });
            };
            window.addEventListener('page-header:section-content-updated', onSectionContentUpdated);
            pushCleanup(() => window.removeEventListener('page-header:section-content-updated', onSectionContentUpdated));

            const onLivewireNavigated = () => {
                this.sectionAnimationsEnabled = this.shouldAnimateSections();
                // Al entrar a una vista por navegación SPA no animamos la sección por defecto.
                this.revealCurrentSectionWhenReady();
            };
            window.addEventListener('livewire:navigated', onLivewireNavigated);
            pushCleanup(() => window.removeEventListener('livewire:navigated', onLivewireNavigated));

            @if(count($sections) > 0)
            this.sectionAnimationsEnabled = this.shouldAnimateSections();
            const fallbackId = this.sections[0]?.id ?? '';

            let parentId = null;
            const parentScope = this.resolveParentScope();
            if (parentScope && parentScope.data && (this.modelKey in parentScope.data)) {
                parentId = parentScope.data[this.modelKey];
            }

            if (this.isValidSectionId(parentId) && parentId !== fallbackId) {
                this.currentVal = parentId;
            } else if (this.isValidSectionId(parentId)) {
                this.currentVal = parentId;
            } else {
                this.currentVal = fallbackId;
            }

            this.updateActiveSection();
            this.syncParentSection(this.currentVal);
            // En primera carga de la vista no animamos la sección por defecto.
            this.revealCurrentSectionWhenReady();

            if (parentScope && parentScope.el) {
                const checkParent = () => {
                    const liveScope = this.resolveParentScope();
                    if (!liveScope || !liveScope.data || !(this.modelKey in liveScope.data)) return;
                    const nextValue = liveScope.data[this.modelKey];
                    if (!this.isValidSectionId(nextValue)) return;
                    if (this.currentVal === nextValue) return;

                    this.currentVal = nextValue;
                    this.updateActiveSection();
                    this.animateCurrentSectionWhenReady();
                };

                const parentSyncInterval = setInterval(checkParent, 300);
                pushCleanup(() => clearInterval(parentSyncInterval));
            }
            @endif
        },

        updateActiveSection() {
            this.activeSection = this.sections.find(s => s.id === this.currentVal) || this.sections[0];
        },
        
        selectSection(id) {
            this.currentVal = id;
            this.updateActiveSection();
            this.dockOpen = false;
            
            this.syncParentSection(id);
            this.animateCurrentSectionWhenReady();
            
            setTimeout(() => {
                let main = document.querySelector('main');
                let currentScroll = main ? main.scrollTop : window.pageYOffset;
                
                // Si ya estamos arriba, no forzar el scroll
                if (currentScroll < 50) return;

                if (main) {
                    main.scrollTo({ top: 11, behavior: 'smooth' });
                } else {
                    window.scrollTo({ top: 11, behavior: 'smooth' });
                }
            }, 300);
        }
    }" style="overflow-anchor: none;">
    <div class="relative w-full px-4 sm:px-6 py-4 flex items-start justify-between">

        {{-- CENTRABLE PILL (Title + Nav) --}}
        <div class="pointer-events-auto transition-all duration-300 ease-in-out group z-50 flex items-center absolute max-w-[calc(100%-2rem)] sm:max-w-[calc(100%-3rem)]"
            @mouseleave="dockOpen = false" :class="{
                'left-1/2 -translate-x-1/2 top-6 group-hover:scale-105 group-hover:rounded-xl py-3 px-2': scrolled,
                'left-4 sm:left-6 top-4 translate-x-0': !scrolled
             }">

            {{-- Invisible Hitbox / Sustain Zone (Matches User's Red Zone) --}}
            {{-- Only interactive when dock is open to sustain it. --}}
            <div x-show="scrolled" @click="dockOpen = false"
                class="absolute left-1/2 -translate-x-1/2 -top-4 w-full max-w-full h-[300px] z-[-1]"
                :class="{ 'pointer-events-auto': dockOpen, 'pointer-events-none': !dockOpen }">
            </div>

            {{-- Visual Background of Pill --}}
            <div class="absolute inset-0 rounded-2xl transition-all duration-300 ease-in-out" :class="{ 
                    'border border-slate-200/90 bg-white/92 shadow-lg backdrop-blur dark:border-slate-700 dark:bg-slate-800/92': scrolled, 
                    '': !scrolled 
                 }"></div>

            {{-- Content Container --}}
            <div class="relative flex items-center min-w-0"
                :class="{ 'gap-3 pr-4 p-2': !scrolled, 'gap-2 p-0 px-2': scrolled }">

                {{-- Title & Icon Block --}}
                <div class="flex items-center gap-3 transition-all duration-300 ease-in-out min-w-0"
                    :class="{ 'border-r border-gray-200 dark:border-gray-700 pr-3 mr-1': scrolled && sections.length > 0 }">
                    @if($icon)
                    <div class="page-header-icon-shell flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-blue-500/25 transition-all shrink-0 shadow-sm"
                        :class="{ 'h-10 w-10': !scrolled, 'h-6 w-6 bg-none dark:bg-none !shadow-none': scrolled }">
                        <div class="page-header-icon"
                            :class="{ 'h-5 w-5': !scrolled, 'text-blue-600 dark:text-white': scrolled }">
                            {!! $icon !!}
                        </div>
                    </div>
                    @endif

                    <div class="flex flex-col transition-all duration-0 cursor-default min-w-0"
                        :class="{ 'block': !scrolled, 'sm:block': scrolled }">
                        <h1 class="font-semibold leading-tight text-slate-800 dark:text-slate-50 transition-all duration-0 whitespace-nowrap"
                            :class="{ 'text-xl sm:text-2xl': !scrolled, 'text-xs uppercase tracking-wider opacity-60 truncate max-w-[42vw] sm:max-w-[24vw]': scrolled }">
                            {{ $title }}
                        </h1>
                        @if($subtitle)
                        <p class="text-xs text-slate-500 dark:text-slate-400 transition-all duration-300 ease-in-out overflow-hidden whitespace-nowrap"
                            :class="{ 'max-h-[20px] opacity-100 mt-0.5': !scrolled, 'max-h-0 opacity-0 w-0': scrolled }">
                            {{ $subtitle }}
                        </p>
                        @endif
                    </div>
                </div>

                {{-- Navigation: Unscrolled (Tabs) / Scrolled (Carousel Text) --}}
                @if($sections && count($sections) > 0)

                {{-- UNSCROLLED DISPLAY --}}
                <div class="flex items-center gap-1 bg-gray-100/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-1 ml-2"
                    x-show="!scrolled" x-transition:enter="transition ease-in-out duration-300"
                    x-transition:enter-start="opacity-0 translate-x-2"
                    x-transition:enter-end="opacity-100 translate-x-0">
                    <template x-for="section in sections" :key="section.id">
                        <button @click="selectSection(section.id)"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all duration-300 ease-in-out whitespace-nowrap"
                            :class="{
                                    'bg-white dark:bg-gray-700 text-blue-700 dark:text-blue-400 shadow-sm ring-1 ring-black/5': currentVal === section.id,
                                    'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-white/50': currentVal !== section.id
                                }">
                            <span x-text="section.label"></span>
                            <span x-show="Number(section.badge || 0) > 0"
                                  class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-extrabold text-white"
                                  x-text="section.badge"></span>
                        </button>
                    </template>
                </div>

                {{-- SCROLLED DISPLAY (Trigger for Dock) --}}
                <div x-show="scrolled" style="display: none;" @mouseenter="dockOpen = true"
                    class="relative h-6 min-w-[140px] overflow-hidden flex items-center cursor-pointer">
                    <template x-for="section in sections" :key="section.id">
                        <div x-show="currentVal === section.id"
                            class="absolute inset-0 flex items-center gap-2 font-bold text-slate-700 dark:text-slate-200 text-sm min-w-0">
                            <span class="truncate max-w-[160px] sm:max-w-[220px]" x-text="section.label"></span>
                            <span x-show="Number(section.badge || 0) > 0"
                                  class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-extrabold text-white"
                                  x-text="section.badge"></span>
                        </div>
                    </template>
                    {{-- Dropdown Indicator (Rotates) --}}
                    <div class="absolute right-0 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4 transition-transform duration-300 ease-in-out" :class="{ 'rotate-180': dockOpen }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
                @endif
            </div>

            {{-- DOCK MENU --}}
            <div x-show="dockOpen && scrolled" x-transition:enter="transition ease-in-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in-out duration-300"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="absolute top-full left-1/2 -translate-x-1/2 pt-2 pointer-events-auto pb-10"
                style="display: none;">

                @if($sections && count($sections) > 0)
                <div class="flex items-center gap-3 p-3">
                    <template x-for="section in sections" :key="section.id">
                        <button @click="selectSection(section.id)"
                            class="group/item relative flex flex-col items-center justify-center transition-all duration-300 ease-in-out hover:scale-125 hover:-translate-y-1 origin-bottom px-1">
                            {{-- Icon --}}
                            <div class="w-10 h-10 flex items-center justify-center rounded-xl shadow-sm transition-all duration-300 ease-in-out bg-gradient-to-br backdrop-blur-md"
                                :class="currentVal === section.id ? 'from-blue-500 to-blue-600 text-white shadow-blue-500/30' : 'from-gray-50/70 to-gray-100/70 dark:from-gray-800/50 dark:to-gray-900/50 text-gray-600 dark:text-gray-400 hover:bg-white dark:hover:bg-black hover:text-blue-600 dark:hover:text-blue-600'">
                                <div class="w-5 h-5"
                                    x-html="section.icon || '<svg viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; class=&quot;w-full h-full&quot;><circle cx=&quot;12&quot; cy=&quot;12&quot; r=&quot;4&quot; stroke-width=&quot;2&quot;/></svg>'">
                                </div>
                            </div>

                            {{-- Tooltip (Fan Animation) --}}
                            <div
                                class="absolute -bottom-9 transform bg-slate-800 text-white text-sm font-bold py-1 px-3 rounded-full opacity-0 group-hover/item:opacity-100 transition-all duration-300 ease-in-out pointer-events-none shadow-lg origin-center scale-x-0 group-hover/item:scale-x-100 whitespace-nowrap z-50">
                                <span x-text="section.label"></span>
                            </div>
                        </button>
                    </template>
                </div>
                @endif
            </div>

        </div>

        {{-- Right Actions Slot --}}
        @if($slot->isNotEmpty())
        <div class="pointer-events-auto relative z-10 flex items-center gap-2 transition-opacity duration-300 ml-auto"
            :class="{ 'opacity-100': !scrolled, 'opacity-40 hover:opacity-100': scrolled }">
            {{ $slot }}
        </div>
        @endif
    </div>
</div>
