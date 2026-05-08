import api from "../api";

async function ensureCsrf() {
    await api.get("/sanctum/csrf-cookie");
}

export async function initRegister(payload) {
    await ensureCsrf();
    const { data } = await api.post("/api/auth/register/init", payload);
    return data;
}

export async function verifyEmail(payload) {
    await ensureCsrf();
    const { data } = await api.post("/api/auth/register/verify", payload);
    return data.user;
}

export async function resendVerificationCode(email) {
    await ensureCsrf();
    const { data } = await api.post("/api/auth/register/resend", { email });
    return data;
}

export async function register(payload) {
    await ensureCsrf();
    const { data } = await api.post("/api/auth/register", payload);
    return data.user;
}

export async function login(payload) {
    await ensureCsrf();
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
