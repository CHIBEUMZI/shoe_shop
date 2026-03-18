import api from "../api";

export function buildImageUrl(pathOrUrl) {
  if (!pathOrUrl) return "";
  const s = String(pathOrUrl);
  if (s.startsWith("http")) return s;

  const base = api.defaults.baseURL;
  if (s.startsWith("/")) {
    return base + s;
  }
  return `${base}/storage/${s}`;
}