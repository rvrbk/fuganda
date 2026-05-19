import { onBeforeUnmount, toValue, watchEffect } from 'vue';

const APP = 'Fuganda';
const DEFAULT_DESCRIPTION =
    'Discover properties for rent and sale across Uganda with quick map search.';

function upsertMeta(attr, attrValue, content) {
    if (!content) return;
    let el = document.querySelector(`meta[${attr}="${attrValue}"]`);
    if (!el) {
        el = document.createElement('meta');
        el.setAttribute(attr, attrValue);
        document.head.appendChild(el);
    }
    el.setAttribute('content', content);
}

function upsertCanonical(url) {
    let el = document.querySelector('link[rel="canonical"]');
    if (!el) {
        el = document.createElement('link');
        el.setAttribute('rel', 'canonical');
        document.head.appendChild(el);
    }
    el.setAttribute('href', url);
}

function resolveOptions(optsOrFactory) {
    if (typeof optsOrFactory === 'function') return optsOrFactory();
    return {
        title: toValue(optsOrFactory.title),
        description: toValue(optsOrFactory.description),
        image: toValue(optsOrFactory.image),
        robots: toValue(optsOrFactory.robots),
        type: toValue(optsOrFactory.type),
    };
}

/**
 * Reactively manage document title and meta tags for SEO.
 *
 * @param {Object|Function} optsOrFactory
 *   Plain options object (values may be refs/computeds) or a getter factory () => {...}.
 *   - title       {string}  Page title — site name is appended automatically
 *   - description {string}  Meta description (falls back to site default)
 *   - image       {string}  Absolute URL for og:image / twitter:image
 *   - robots      {string}  robots content, e.g. 'noindex,nofollow'
 *   - type        {string}  og:type, defaults to 'website'
 */
export function usePageMeta(optsOrFactory = {}) {
    const stop = watchEffect(() => {
        const { title, description, image, robots, type } = resolveOptions(optsOrFactory);

        const fullTitle = title ? `${title} | ${APP}` : `${APP} — Uganda Property Listings`;
        const metaDescription = description || DEFAULT_DESCRIPTION;
        const ogType = type || 'website';
        const canonicalUrl = window.location.origin + window.location.pathname;

        document.title = fullTitle;

        upsertMeta('name', 'description', metaDescription);
        upsertMeta('name', 'robots', robots || 'index,follow');
        upsertCanonical(canonicalUrl);

        // Open Graph
        upsertMeta('property', 'og:title', fullTitle);
        upsertMeta('property', 'og:description', metaDescription);
        upsertMeta('property', 'og:type', ogType);
        upsertMeta('property', 'og:url', canonicalUrl);
        upsertMeta('property', 'og:site_name', APP);
        if (image) {
            upsertMeta('property', 'og:image', image);
            upsertMeta('property', 'og:image:width', '1200');
            upsertMeta('property', 'og:image:height', '630');
        }

        // Twitter Card
        upsertMeta('name', 'twitter:card', image ? 'summary_large_image' : 'summary');
        upsertMeta('name', 'twitter:title', fullTitle);
        upsertMeta('name', 'twitter:description', metaDescription);
        if (image) {
            upsertMeta('name', 'twitter:image', image);
        }
    });

    onBeforeUnmount(() => stop());
}
