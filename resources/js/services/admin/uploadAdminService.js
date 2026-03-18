import api from "../../api";

function uploadImage(file, folder = "uploads") {
  const formData = new FormData();
  formData.append("file", file);
  formData.append("folder", folder);

  return api.post("/api/v1/admin/uploads/images", formData);
}

export default {
  uploadImage,

  uploadProductImage(file) {
    return uploadImage(file, "products");
  },

  uploadBrandLogo(file) {
    return uploadImage(file, "brands");
  },

  uploadCategoryImage(file) {
    return uploadImage(file, "categories");
  },

  uploadAvatar(file) {
    return uploadImage(file, "avatars");
  },

  uploadBannerImage(file) {
    return uploadImage(file, "banners");
  },

  deleteImage(path) {
    return api.delete("/api/v1/admin/uploads/images", {
      data: { path },
    });
  },
};