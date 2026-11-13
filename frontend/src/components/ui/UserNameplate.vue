<script setup lang="ts">
/**
 * UserNameplate — renders a nameplate image (448x84 / 16:3) with the display name
 * layered on top of it.
 *
 * Sizing is expressed in rem so the app-wide UI Scale (which sets html font-size to
 * 16 * scale px in useLocalPreferences) scales the nameplate together with every
 * other rem-based dimension. A px-locked width would break the layout at any scale
 * other than 100%.
 *
 * 448px = 28rem, 84px = 5.25rem.
 */
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  nameplate: { image_url: string; name?: string | null } | null | undefined
  /** Label text. Only rendered in mode="text"; ignored by mode="fill". */
  name?: string
  /** Rendered width in rem. 28rem == the native 448px asset width at 100% scale. */
  width?: string
  /** Fallback text styling when no nameplate is equipped. */
  fallbackClass?: string
  /**
   * 'fill' turns the plate into a full-bleed background for a container and renders
   * no text of its own, so the host keeps its own name/label positions.
   * 'text' keeps the plate behind the name (used in settings previews only).
   */
  mode?: 'fill' | 'text'
  /** mode="fill": how the plate fills its container. */
  fit?: 'cover' | 'cover-left' | 'cover-right' | 'contain'
  /** mode="fill": dark scrim so foreground text stays readable. */
  scrim?: boolean
}>(), {
  width: '16rem',
  fallbackClass: 'chatify:text-sm chatify:font-medium',
  mode: 'fill',
  fit: 'cover',
  scrim: true,
})

const hasNameplate = computed(() => Boolean(props.nameplate?.image_url))

const fitClass: Record<string, string> = {
  cover: 'chatify:object-cover chatify:object-center',
  'cover-left': 'chatify:object-cover chatify:object-left',
  'cover-right': 'chatify:object-cover chatify:object-right',
  contain: 'chatify:object-contain chatify:object-center',
}
</script>

<template>
  <!-- FILL MODE: full-bleed background for a container. Renders no text. -->
  <span
    v-if="mode === 'fill'"
    class="chatify-user-nameplate-fill chatify:pointer-events-none chatify:absolute chatify:inset-0 chatify:z-0 chatify:overflow-hidden"
    aria-hidden="true"
  >
    <img
      v-if="hasNameplate"
      :src="nameplate!.image_url"
      alt=""
      class="chatify-user-nameplate-fill-img chatify:h-full chatify:w-full chatify:select-none"
      :class="fitClass[fit]"
      draggable="false"
    />
    <span v-if="hasNameplate && scrim" class="chatify-user-nameplate-fill-scrim" />
  </span>

  <!-- TEXT MODE: plate behind the name label (settings previews). -->
  <span
    v-else-if="hasNameplate"
    class="chatify-user-nameplate chatify:relative chatify:inline-flex chatify:shrink-0 chatify:items-center chatify:justify-center"
    :style="{ width, height: `calc(${width} * 0.1875)` }"
  >
    <img
      :src="nameplate!.image_url"
      alt=""
      class="chatify-user-nameplate-img chatify:pointer-events-none chatify:absolute chatify:inset-0 chatify:h-full chatify:w-full chatify:select-none chatify:object-contain"
      draggable="false"
      aria-hidden="true"
    />
    <span class="chatify-user-nameplate-label chatify:relative chatify:z-10 chatify:w-full chatify:truncate chatify:px-3 chatify:text-center">
      {{ name }}
    </span>
  </span>

  <span
    v-else
    :class="fallbackClass"
    class="chatify:truncate"
  >
    {{ name }}
  </span>
</template>
