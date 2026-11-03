// The address bar follows an open editor (which record, which saved version) without navigating: the screen keeps its state,
// and a refresh or a shared link comes back to the same place. A value of null (or undefined) removes that part of the address.
export function setQuery(params) {
  const url = new URL(window.location.href)
  for (const [k, v] of Object.entries(params)) {
    if (v === null || v === undefined || v === '') url.searchParams.delete(k)
    else url.searchParams.set(k, v)
  }
  window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash)
}
