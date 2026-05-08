import axios from "axios";

const isLocalhost = ["localhost", "127.0.0.1"].includes(window.location.hostname);
const isFrontendDevServer = window.location.port === "5173";
const baseURL = import.meta.env.VITE_API_URL || (isLocalhost || isFrontendDevServer ? "http://localhost:8080" : window.location.origin);

const api = axios.create({
  baseURL,
  withCredentials: true,
  headers: {
    "X-Requested-With": "XMLHttpRequest",
    "Accept": "application/json",
  },
});

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  failedQueue = [];
};

function getCsrfToken() {
  const token = document.head.querySelector('meta[name="csrf-token"]');
  if (token) {
    return token.content;
  }
  const cookieValue = document.cookie
    .split("; ")
    .find((row) => row.startsWith("XSRF-TOKEN="));
  return cookieValue ? decodeURIComponent(cookieValue.split("=")[1]) : null;
}

api.interceptors.request.use(async (config) => {
  if (isRefreshing) {
    return new Promise((resolve, reject) => {
      failedQueue.push({ resolve, reject });
    }).then((token) => {
      if (token) {
        config.headers["X-XSRF-TOKEN"] = token;
      }
      return config;
    });
  }

  return config;
});

export default api;
