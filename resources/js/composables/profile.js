import api from "../api";

export async function updateProfile(payload) {
    await api.get("/sanctum/csrf-cookie");
    const { data } = await api.put("/api/auth/profile", payload);
    return data.user;
}

export async function changePassword(payload) {
    await api.get("/sanctum/csrf-cookie");
    const { data } = await api.post("/api/auth/profile/change-password", payload);
    return data;
}

export async function uploadAvatar(file) {
    await api.get("/sanctum/csrf-cookie");
    const formData = new FormData();
    formData.append("avatar", file);

    const { data } = await api.post("/api/auth/avatar", formData, {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    });
    return data;
}

export async function deleteAvatar() {
    await api.get("/sanctum/csrf-cookie");
    const { data } = await api.delete("/api/auth/avatar");
    return data;
}
