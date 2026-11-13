/**
 * Shared Blob cache for profile-effect APNGs (pair-1 entrance and pair-2 loop).
 *
 * The overlay mints a fresh object URL per animation cycle so pair-1 and every
 * pair-2 replay always restart from frame 0. Minting the URL requires the
 * bytes; fetching them on every cycle would re-download multi-megabyte files.
 * This module keeps one Blob per URL for the lifetime of the page, so only the
 * first use touches the network (or the normal HTTP cache) and later cycles are
 * instant.
 */
const blobCache = new Map<string, Promise<Blob>>()
const BLOB_CACHE_LIMIT = 4

export function fetchProfileEffectBlob(url: string): Promise<Blob> {
  let pending = blobCache.get(url)

  if (!pending) {
    pending = fetch(url, { credentials: 'same-origin' }).then((res) => {
      if (!res.ok) {
        throw new Error(`Failed to load profile effect: ${url}`)
      }
      return res.blob()
    })

    // Drop failed fetches so the next open retries instead of caching the error.
    pending.catch(() => blobCache.delete(url))
    blobCache.set(url, pending)

    if (blobCache.size > BLOB_CACHE_LIMIT) {
      const oldest = blobCache.keys().next().value
      if (oldest) blobCache.delete(oldest)
    }
  }

  return pending
}

/** Test helper: drop cached Blobs so fetch counts start from zero. */
export function clearProfileEffectBlobCache() {
  blobCache.clear()
}
