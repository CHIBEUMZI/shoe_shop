import api from "../api";

export async function register(payload) {
    await api.get("/sanctum/csrf-cookie");
    const { data } = await api.post("/api/auth/register", payload);
    return data.user;
}

export async function login(payload) {
    await api.get("/sanctum/csrf-cookie");
    const { data } = await api.post("/api/auth/login", payload);
    return data.user;
}

export async function me() {
    const { data } = await api.get("/api/auth/me");
    return data.user;
}

export async function logout() {
  await api.post("/api/auth/logout");
}
