import axios from "axios";

const api = axios.create({
  baseURL: "http://localhost:8080",
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

  if (["post", "put", "patch", "delete"].includes(config.method)) {
    try {
      isRefreshing = true;
      // Use axios directly to avoid interceptor recursion
      await axios.get("http://localhost:8080/sanctum/csrf-cookie", {
        withCredentials: true,
      });
      const token = getCsrfToken();
      if (token) {
        config.headers["X-XSRF-TOKEN"] = token;
      }
    } catch (error) {
      processQueue(error, null);
      return Promise.reject(error);
    } finally {
      isRefreshing = false;
      processQueue(null, getCsrfToken());
    }
  }

  return config;
});

export default api;
