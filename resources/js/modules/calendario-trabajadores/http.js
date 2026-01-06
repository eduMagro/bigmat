import { CSRF } from "./config.js";

export async function httpJSON(url, options = {}) {
    const opts = {
        headers: { Accept: "application/json", "X-CSRF-TOKEN": CSRF() },
        ...options,
    };
    if (opts.body && typeof opts.body !== "string") {
        opts.headers["Content-Type"] = "application/json";
        opts.body = JSON.stringify(opts.body);
    }
    const resp = await fetch(url, opts);
    let data = null;
    try {
        data = await resp.json();
    } catch (_) {
        /* 204 etc. */
    }
    if (!resp.ok) throw new Error(data?.message || `HTTP ${resp.status}`);
    return data;
}
