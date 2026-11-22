<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, ChevronDown, Gift, Mails, Menu } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import { applyAdminTheme } from '@/composables/useAdminTheme';
import AppLogoFull from '@/components/AppLogoFull.vue';
import FrontendNavigationChildContent from '@/components/frontend/FrontendNavigationChildContent.vue';
import PagePreviewBanner from '@/components/pages/PagePreviewBanner.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { home } from '@/routes';
import { index as families } from '@/routes/families';

const props = withDefaults(
    defineProps<{
        isHomePage?: boolean;
    }>(),
    {
        isHomePage: false,
    },
);

const page = usePage();
onMounted(() => applyAdminTheme('neutral'));
const isMobileMenuOpen = ref(false);

function isExternal(url: string): boolean {
    return /^https?:\/\//i.test(url);
}

function isAnchor(url: string): boolean {
    return url.startsWith('#');
}

function resolvedUrl(url: string): string {
    if (isAnchor(url) && !props.isHomePage) {
        return `${home.url()}${url}`;
    }

    return url;
}

function handleAnchorClick(event: MouseEvent, url: string): void {
    isMobileMenuOpen.value = false;

    if (!props.isHomePage || !isAnchor(url)) {
        return;
    }

    event.preventDefault();

    window.setTimeout(() => {
        document.querySelector<HTMLElement>(url)?.scrollIntoView({
            block: 'start',
        });
        window.history.replaceState(null, '', url);
    }, 350);
}
</script>

<template>
    <header
        class="christmas-header sticky top-0 z-40 border-b backdrop-blur-xl"
    >
        <PagePreviewBanner />
        <div
            class="mx-auto flex h-18 max-w-[1240px] items-center justify-between gap-6 px-5 sm:px-8 lg:px-12"
        >
            <Link
                :href="home()"
                class="focus-visible:ring-ring focus-visible:ring-offset-background inline-flex min-w-0 items-center rounded-md outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
                :aria-label="`Accueil de ${page.props.name}`"
            >
                <AppLogoFull
                    v-if="
                        page.props.branding.fullLogoUrl ||
                        page.props.branding.iconUrl
                    "
                    class="text-foreground h-8 w-auto max-w-40"
                />
                <span v-else class="inline-flex items-center gap-2.5">
                    <span class="brand-gift"
                        ><Gift :size="22" :stroke-width="1.7"
                    /></span>
                    <strong
                        class="holiday-serif text-[26px] tracking-[-.055em]"
                        >{{ page.props.name }}</strong
                    >
                </span>
            </Link>

            <nav
                v-if="page.props.navigation.frontend.length > 0"
                aria-label="Navigation principale"
                class="hidden items-center lg:flex"
            >
                <NavigationMenu :viewport="false">
                    <NavigationMenuList class="gap-2">
                        <NavigationMenuItem
                            v-for="(item, index) in page.props.navigation
                                .frontend"
                            :key="`${item.type}-${item.label}-${index}`"
                            :data-test="
                                item.type === 'group'
                                    ? 'desktop-navigation-group'
                                    : 'desktop-navigation-link'
                            "
                        >
                            <template v-if="item.type === 'group'">
                                <NavigationMenuTrigger>
                                    {{ item.label }}
                                </NavigationMenuTrigger>
                                <NavigationMenuContent
                                    class="w-[min(28rem,calc(100vw-2rem))] p-0 md:w-[28rem]"
                                >
                                    <div class="flex flex-col gap-1 p-3">
                                        <NavigationMenuLink
                                            v-for="(
                                                child, childIndex
                                            ) in item.children"
                                            :key="`${child.url}-${childIndex}`"
                                            as-child
                                        >
                                            <a
                                                v-if="
                                                    isExternal(child.url) ||
                                                    isAnchor(child.url)
                                                "
                                                :href="resolvedUrl(child.url)"
                                                :target="
                                                    isExternal(child.url)
                                                        ? '_blank'
                                                        : undefined
                                                "
                                                :rel="
                                                    isExternal(child.url)
                                                        ? 'noopener noreferrer'
                                                        : undefined
                                                "
                                                class="group block"
                                            >
                                                <FrontendNavigationChildContent
                                                    :label="child.label"
                                                    :description="
                                                        child.description
                                                    "
                                                />
                                            </a>
                                            <Link
                                                v-else
                                                :href="resolvedUrl(child.url)"
                                                class="group block"
                                            >
                                                <FrontendNavigationChildContent
                                                    :label="child.label"
                                                    :description="
                                                        child.description
                                                    "
                                                />
                                            </Link>
                                        </NavigationMenuLink>
                                    </div>
                                </NavigationMenuContent>
                            </template>

                            <NavigationMenuLink
                                v-else-if="item.url"
                                as-child
                                :class="navigationMenuTriggerStyle()"
                            >
                                <a
                                    v-if="
                                        isExternal(item.url) ||
                                        isAnchor(item.url)
                                    "
                                    :href="resolvedUrl(item.url)"
                                    :target="
                                        isExternal(item.url)
                                            ? '_blank'
                                            : undefined
                                    "
                                    :rel="
                                        isExternal(item.url)
                                            ? 'noopener noreferrer'
                                            : undefined
                                    "
                                >
                                    {{ item.label }}
                                </a>
                                <Link v-else :href="resolvedUrl(item.url)">
                                    {{ item.label }}
                                </Link>
                            </NavigationMenuLink>
                        </NavigationMenuItem>
                    </NavigationMenuList>
                </NavigationMenu>
            </nav>

            <div class="flex items-center gap-2">
                <Link
                    :href="families()"
                    class="holiday-outline rounded-lg px-3 py-2 text-sm sm:px-3.5"
                    ><Mails class="size-4" :stroke-width="1.8" />
                    <span class="max-[419px]:sr-only">Mes échanges</span></Link
                >

                <Sheet v-model:open="isMobileMenuOpen">
                    <SheetTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="lg:hidden"
                            aria-label="Ouvrir le menu"
                        >
                            <Menu />
                        </Button>
                    </SheetTrigger>
                    <SheetContent class="w-full gap-0 p-0 sm:max-w-sm">
                        <SheetHeader class="border-b px-6 py-5 pr-14 text-left">
                            <SheetTitle class="sr-only">Menu</SheetTitle>
                            <SheetDescription class="sr-only">
                                Accédez aux pages du site et à votre espace.
                            </SheetDescription>
                            <Link
                                :href="home()"
                                class="focus-visible:ring-ring inline-flex w-fit items-center rounded-md outline-none focus-visible:ring-2"
                                :aria-label="`Accueil de ${page.props.name}`"
                                @click="isMobileMenuOpen = false"
                            >
                                <AppLogoFull
                                    v-if="
                                        page.props.branding.fullLogoUrl ||
                                        page.props.branding.iconUrl
                                    "
                                    class="text-foreground h-8 w-auto max-w-40"
                                />
                                <strong v-else class="holiday-serif text-2xl">{{
                                    page.props.name
                                }}</strong>
                            </Link>
                        </SheetHeader>

                        <nav
                            v-if="page.props.navigation.frontend.length > 0"
                            aria-label="Navigation mobile"
                            class="flex flex-1 flex-col gap-2 overflow-y-auto px-4 py-6"
                        >
                            <template
                                v-for="(item, index) in page.props.navigation
                                    .frontend"
                                :key="`${item.type}-${item.label}-${index}`"
                            >
                                <Collapsible
                                    v-if="item.type === 'group'"
                                    class="group/collapsible"
                                    data-test="mobile-navigation-group"
                                >
                                    <CollapsibleTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            class="w-full justify-between px-3 text-base"
                                        >
                                            {{ item.label }}
                                            <ChevronDown
                                                data-icon="inline-end"
                                                class="transition-transform group-data-[state=open]/collapsible:rotate-180"
                                            />
                                        </Button>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <div
                                            class="flex flex-col gap-1 pt-2 pl-3"
                                        >
                                            <template
                                                v-for="(
                                                    child, childIndex
                                                ) in item.children"
                                                :key="`${child.url}-${childIndex}`"
                                            >
                                                <a
                                                    v-if="
                                                        isExternal(child.url) ||
                                                        isAnchor(child.url)
                                                    "
                                                    :href="
                                                        resolvedUrl(child.url)
                                                    "
                                                    :target="
                                                        isExternal(child.url)
                                                            ? '_blank'
                                                            : undefined
                                                    "
                                                    :rel="
                                                        isExternal(child.url)
                                                            ? 'noopener noreferrer'
                                                            : undefined
                                                    "
                                                    class="group hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring block rounded-lg transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                                    @click="
                                                        handleAnchorClick(
                                                            $event,
                                                            child.url,
                                                        )
                                                    "
                                                >
                                                    <FrontendNavigationChildContent
                                                        compact
                                                        :label="child.label"
                                                        :description="
                                                            child.description
                                                        "
                                                    />
                                                </a>
                                                <Link
                                                    v-else
                                                    :href="
                                                        resolvedUrl(child.url)
                                                    "
                                                    class="group hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring block rounded-lg transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                                    @click="
                                                        isMobileMenuOpen = false
                                                    "
                                                >
                                                    <FrontendNavigationChildContent
                                                        compact
                                                        :label="child.label"
                                                        :description="
                                                            child.description
                                                        "
                                                    />
                                                </Link>
                                            </template>
                                        </div>
                                    </CollapsibleContent>
                                </Collapsible>

                                <a
                                    v-else-if="
                                        item.url &&
                                        (isExternal(item.url) ||
                                            isAnchor(item.url))
                                    "
                                    :href="resolvedUrl(item.url)"
                                    :target="
                                        isExternal(item.url)
                                            ? '_blank'
                                            : undefined
                                    "
                                    :rel="
                                        isExternal(item.url)
                                            ? 'noopener noreferrer'
                                            : undefined
                                    "
                                    data-test="mobile-navigation-link"
                                    class="hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring rounded-lg px-3 py-2 text-base font-medium transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                    @click="handleAnchorClick($event, item.url)"
                                >
                                    {{ item.label }}
                                </a>
                                <Link
                                    v-else-if="item.url"
                                    :href="resolvedUrl(item.url)"
                                    data-test="mobile-navigation-link"
                                    class="hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring rounded-lg px-3 py-2 text-base font-medium transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                    @click="isMobileMenuOpen = false"
                                >
                                    {{ item.label }}
                                </Link>
                            </template>
                        </nav>

                        <div class="grid gap-4 p-4">
                            <Button as-child class="w-full">
                                <Link
                                    :href="families()"
                                    @click="isMobileMenuOpen = false"
                                >
                                    Mes échanges
                                    <ArrowRight data-icon="inline-end" />
                                </Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
</template>
