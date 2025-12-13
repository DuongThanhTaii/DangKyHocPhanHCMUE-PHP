/**
 * Centralized API Cache with Request Deduplication
 * 
 * This utility prevents 200+ duplicate requests by:
 * 1. Caching successful GET responses for 5 minutes
 * 2. Deduplicating concurrent requests to the same endpoint
 * 3. Providing cache invalidation for mutations
 */

// Cache storage with TTL
const cache = new Map<string, { data: any; expiry: number }>();

// Pending requests for deduplication
const pendingRequests = new Map<string, Promise<any>>();

// Default TTL: 5 minutes
const DEFAULT_CACHE_TTL = 5 * 60 * 1000;

// Short TTL for frequently changing data: 30 seconds
const SHORT_CACHE_TTL = 30 * 1000;

/**
 * Fetch with caching and request deduplication
 * @param key Cache key (usually the API endpoint)
 * @param fetcher Function that performs the actual fetch
 * @param ttl Cache TTL in milliseconds (default: 5 minutes)
 */
export async function cachedFetch<T>(
    key: string,
    fetcher: () => Promise<T>,
    ttl: number = DEFAULT_CACHE_TTL
): Promise<T> {
    // 1. Check cache first
    const cached = cache.get(key);
    if (cached && Date.now() < cached.expiry) {
        return cached.data as T;
    }

    // 2. Check if there's already a pending request (deduplication)
    if (pendingRequests.has(key)) {
        return pendingRequests.get(key) as Promise<T>;
    }

    // 3. Make new request and cache the promise
    const promise = fetcher()
        .then((result) => {
            // Cache successful results
            cache.set(key, { data: result, expiry: Date.now() + ttl });
            pendingRequests.delete(key);
            return result;
        })
        .catch((error) => {
            pendingRequests.delete(key);
            throw error;
        });

    pendingRequests.set(key, promise);
    return promise;
}

/**
 * Clear entire cache (useful after login/logout)
 */
export function clearAllCache(): void {
    cache.clear();
    pendingRequests.clear();
}

/**
 * Clear cache for a specific key
 */
export function clearCacheKey(key: string): void {
    cache.delete(key);
    pendingRequests.delete(key);
}

/**
 * Clear cache for keys matching a pattern
 * @param pattern Prefix to match (e.g., "sv/" to clear all SV cache)
 */
export function clearCacheByPattern(pattern: string): void {
    for (const key of cache.keys()) {
        if (key.includes(pattern)) {
            cache.delete(key);
        }
    }
    for (const key of pendingRequests.keys()) {
        if (key.includes(pattern)) {
            pendingRequests.delete(key);
        }
    }
}

/**
 * Get cache statistics (for debugging)
 */
export function getCacheStats(): { size: number; keys: string[] } {
    return {
        size: cache.size,
        keys: Array.from(cache.keys()),
    };
}

// Export TTL constants for use in API modules
export { DEFAULT_CACHE_TTL, SHORT_CACHE_TTL };
