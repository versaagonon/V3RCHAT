import { ref } from 'vue'
import { defineStore } from 'pinia'

/**
 * Per-conversation composer drafts, kept in memory for the session.
 *
 * Why a store and not local component state: the composer is unmounted whenever the
 * user leaves a thread, so a local `ref` would be destroyed with it. Drafts have to
 * outlive the component, and the conversation list needs to read them too, so this
 * is the single source of truth for both.
 *
 * Storage is a plain keyed map rather than `localStorage`: drafts are transient by
 * design and a stale draft restored from a previous session tends to surprise the
 * user more than it helps.
 */
export const useDraftsStore = defineStore('drafts', () => {
  const drafts = ref<Record<string, string>>({})

  function key(conversationId: string | number | null | undefined): string {
    return conversationId == null ? '' : String(conversationId)
  }

  function get(conversationId: string | number | null | undefined): string {
    return drafts.value[key(conversationId)] ?? ''
  }

  function has(conversationId: string | number | null | undefined): boolean {
    return get(conversationId).trim().length > 0
  }

  /**
   * Store the draft, trimming to nothing when the field is empty so `has()` and
   * the list label stay correct without extra bookkeeping.
   */
  function set(conversationId: string | number | null | undefined, text: string): void {
    const id = key(conversationId)
    if (id === '') return

    if (text.trim().length === 0) {
      delete drafts.value[id]
      return
    }

    drafts.value[id] = text
  }

  function clear(conversationId: string | number | null | undefined): void {
    const id = key(conversationId)
    if (id === '') return
    delete drafts.value[id]
  }

  /** Single-line preview for the conversation list. */
  function preview(conversationId: string | number | null | undefined, maxLength = 80): string {
    const text = get(conversationId).replace(/\s+/g, ' ').trim()
    if (text.length <= maxLength) return text
    return `${text.slice(0, maxLength).trimEnd()}...`
  }

  return {
    drafts,
    get,
    set,
    clear,
    has,
    preview,
  }
})
