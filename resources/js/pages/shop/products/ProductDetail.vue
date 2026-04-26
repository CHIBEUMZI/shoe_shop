<template>
  <main class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100">
    <!-- Loading / Error -->
    <div
      v-if="loading"
      class="flex min-h-[60vh] items-center justify-center"
    >
      <div class="flex flex-col items-center gap-4">
        <div class="h-10 w-10 animate-spin rounded-full border-3 border-primary/20 border-t-primary"></div>
        <p class="text-sm text-slate-500">Đang tải sản phẩm...</p>
      </div>
    </div>

    <div
      v-else-if="error"
      class="mx-auto max-w-6xl px-4 py-6"
    >
      <div class="rounded-lg border border-red-200 bg-red-50 p-8 text-center">
        <span class="material-symbols-outlined text-5xl text-red-400">error</span>
        <p class="mt-4 text-lg font-medium text-red-600">{{ error }}</p>
      </div>
    </div>

    <!-- Content -->
    <template v-else-if="product">
      <!-- Breadcrumbs -->
      <nav class="mx-auto max-w-6xl px-4 pt-6 sm:px-5 lg:px-6">
        <div class="flex items-center gap-2 text-sm">
          <a
            class="group flex items-center gap-1 text-slate-500 transition hover:text-primary"
            href="#"
            @click.prevent="goShop"
          >
            <span class="material-symbols-outlined text-[18px]">home</span>
            <span>Home</span>
          </a>
          <span class="text-slate-300">/</span>

          <a
            class="text-slate-500 transition hover:text-primary"
            href="#"
            @click.prevent="goShop"
          >
            Shop
          </a>

          <template v-if="firstCategory">
            <span class="text-slate-300">/</span>
            <a
              class="text-slate-500 transition hover:text-primary"
              href="#"
              @click.prevent="goCategory(firstCategory)"
            >
              {{ firstCategory.name }}
            </a>
          </template>

          <template v-if="product.brand?.name">
            <span class="text-slate-300">/</span>
            <a
              class="text-slate-500 transition hover:text-primary"
              href="#"
              @click.prevent="goBrand(product.brand)"
            >
              {{ product.brand.name }}
            </a>
          </template>

          <span class="text-slate-300">/</span>
          <span class="truncate font-medium text-slate-900 max-w-[200px]">{{ product.name }}</span>
        </div>
      </nav>

      <!-- Hero Section -->
      <section class="mx-auto max-w-6xl px-4 py-4 sm:px-5 lg:px-6 lg:py-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[80px_1fr] xl:grid-cols-[80px_1.2fr_1fr] xl:gap-8">
          <!-- LEFT: Thumbnails Vertical -->
          <div class="relative hidden lg:flex flex-col">
            <!-- Up Button -->
            <button
              @click="prevThumbImages"
              :disabled="thumbIndex <= 0"
              class="mb-1 flex h-6 w-full items-center justify-center rounded-lg bg-white/90 shadow transition-all duration-200 hover:bg-white hover:shadow-md disabled:opacity-30 disabled:cursor-not-allowed"
            >
              <span class="material-symbols-outlined text-[18px] text-slate-600">expand_less</span>
            </button>

            <!-- Thumbnails -->
            <div class="overflow-y-auto py-1 space-y-1.5" style="height: 380px;">
              <button
                v-for="img in visibleThumbImages"
                :key="img"
                type="button"
                class="group relative w-full overflow-hidden rounded-lg border-2 bg-white transition-all duration-200"
                :class="[
                  img === activeImage
                    ? 'border-primary ring-2 ring-primary/20 shadow'
                    : 'border-transparent hover:border-slate-300'
                ]"
                @click="activeImage = img"
              >
                <img
                  class="aspect-square w-full object-cover transition duration-200 group-hover:scale-105"
                  :src="buildImageUrl(img)"
                  alt="thumb"
                />
              </button>
            </div>

            <!-- Down Button -->
            <button
              @click="nextThumbImages"
              :disabled="thumbIndex + thumbVisibleCount >= images.length"
              class="mt-1 flex h-6 w-full items-center justify-center rounded-lg bg-white/90 shadow transition-all duration-200 hover:bg-white hover:shadow-md disabled:opacity-30 disabled:cursor-not-allowed"
            >
              <span class="material-symbols-outlined text-[18px] text-slate-600">expand_more</span>
            </button>
          </div>

          <!-- CENTER: Main Image -->
          <div class="space-y-3">
            <!-- Main Image with Zoom -->
            <div
              class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 via-white to-slate-100 shadow-lg"
              @mouseenter="isZooming = true"
              @mouseleave="isZooming = false"
              @mousemove="handleZoom"
              ref="imageContainer"
            >
              <!-- Badge -->
              <div class="absolute left-3 top-3 z-10">
                <div v-if="hasSale && discountPercent > 0"
                  class="flex items-center gap-1 rounded-full bg-gradient-to-r from-red-500 to-red-600 px-2.5 py-1 text-xs font-bold text-white shadow"
                >
                  <span>-{{ discountPercent }}%</span>
                </div>
                <div v-else
                  class="inline-flex items-center gap-1 rounded-full bg-white/95 backdrop-blur px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-slate-700 shadow"
                >
                  <span class="material-symbols-outlined text-[12px] text-primary">verified</span>
                  New
                </div>
              </div>

              <!-- Wishlist Button -->
              <button
                class="absolute right-3 top-3 z-10 grid h-9 w-9 place-items-center rounded-full bg-white/95 backdrop-blur shadow transition-all duration-200 hover:scale-105 active:scale-95"
                type="button"
                @click="addToWishlist"
              >
                <span class="material-symbols-outlined text-[18px] text-slate-700 hover:text-red-500 transition">favorite</span>
              </button>

              <!-- Main Image -->
              <div class="relative aspect-square overflow-hidden">
                <img
                  :src="buildImageUrl(activeImage) || buildImageUrl(images[0]) || fallbackImage"
                  :alt="product.name"
                  class="h-full w-full object-cover transition-transform duration-500"
                  :class="isZooming ? 'scale-150' : 'group-hover:scale-105'"
                  :style="zoomStyle"
                />
                
                <!-- Zoom hint -->
                <div class="absolute bottom-3 left-1/2 -translate-x-1/2 opacity-0 transition-opacity group-hover:opacity-100">
                  <div class="flex items-center gap-1.5 rounded-full bg-black/60 backdrop-blur px-2.5 py-1 text-xs text-white">
                    <span class="material-symbols-outlined text-[14px]">zoom_in</span>
                    <span>Di chuột để phóng to</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Trust Badges -->
            <div class="grid grid-cols-3 gap-2">
              <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-sm transition-all duration-200 hover:shadow">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-600 shadow">
                  <span class="material-symbols-outlined text-[14px] text-white">local_shipping</span>
                </div>
                <div class="min-w-0">
                  <h4 class="text-[10px] font-bold text-slate-900 truncate">Miễn phí vận chuyển</h4>
                </div>
              </div>

              <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-sm transition-all duration-200 hover:shadow">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-green-600 shadow">
                  <span class="material-symbols-outlined text-[14px] text-white">verified</span>
                </div>
                <div class="min-w-0">
                  <h4 class="text-[10px] font-bold text-slate-900 truncate">100% Chính hãng</h4>
                </div>
              </div>
              <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white p-2.5 shadow-sm transition-all duration-200 hover:shadow">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-green-600 shadow">
                  <span class="material-symbols-outlined text-[14px] text-white">support_agent</span>
                </div>
                <div class="min-w-0">
                  <h4 class="text-[10px] font-bold text-slate-900 truncate">Đổi trả 30 ngày</h4>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT: Product Info -->
          <div class="lg:sticky lg:top-4 lg:h-fit">
            <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-lg sm:p-5 lg:p-6">
              <!-- Brand & Rating -->
              <div class="mb-3 flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-primary/10 to-primary/5 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-primary"
                >
                  <span class="material-symbols-outlined text-[12px]">verified</span>
                  {{ product.brand?.name || "Brand" }}
                </span>

                <div class="flex items-center gap-1.5">
                  <div class="flex items-center">
                    <span
                      v-for="i in 5"
                      :key="i"
                      class="material-symbols-outlined text-[14px]"
                      :class="i <= Math.round(reviewStats.average_rating) ? 'text-amber-400' : 'text-slate-300'"
                    >
                      {{ i <= Math.round(reviewStats.average_rating) ? 'star' : 'star' }}
                    </span>
                  </div>
                  <span class="text-xs font-semibold text-slate-900">{{ reviewStats.average_rating.toFixed(1) }}</span>
                  <span class="text-xs text-slate-500">({{ reviewStats.total_reviews }})</span>
                </div>
              </div>

              <!-- Title -->
              <h1 class="text-xl font-black leading-tight tracking-tight text-slate-900 sm:text-2xl lg:text-3xl">
                {{ product.name }}
              </h1>

              <!-- SKU -->
              <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                <span class="material-symbols-outlined text-[14px]">tag</span>
                <span>SKU: {{ product.sku || "-" }}</span>
              </div>

              <!-- Price -->
              <div class="mt-4 rounded-xl bg-gradient-to-r from-slate-50 to-slate-100/50 p-4 ring-1 ring-slate-200/50">
                <div class="flex flex-wrap items-end gap-2">
                  <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-primary to-primary/70 sm:text-3xl">
                      {{ moneyVND(displayPrice) }}
                    </span>
                    <span
                      v-if="hasSale"
                      class="text-sm text-slate-400 line-through"
                    >
                      {{ moneyVND(Number(product.base_price || 0)) }}
                    </span>
                  </div>
                  
                  <div v-if="hasSale && discountPercent > 0"
                    class="rounded-full bg-gradient-to-r from-red-500 to-red-600 px-2 py-0.5 text-xs font-bold text-white"
                  >
                    -{{ discountPercent }}%
                  </div>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">
                  Giá đã bao gồm ưu đãi hiện tại
                </p>
              </div>

              <!-- Stock Status -->
              <div class="mt-3 flex items-center gap-2 text-xs">
                <span
                  v-if="maxQty > 0"
                  class="flex items-center gap-1 font-medium text-green-600"
                >
                  Còn {{ maxQty }} sản phẩm
                </span>
                <span v-else class="flex items-center gap-1 font-medium text-red-600">
                  Hết hàng
                </span>
              </div>

              <!-- Divider -->
              <div class="my-4 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

              <!-- Options -->
              <div class="space-y-4">
                <!-- Color -->
                <div v-if="colors.length">
                  <div class="mb-2 flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-900">
                      Màu sắc
                    </label>
                    <span v-if="selectedColor" class="text-xs font-medium text-primary">{{ selectedColor }}</span>
                  </div>

                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="c in colors"
                      :key="c"
                      type="button"
                      class="group relative h-9 w-9 rounded-xl border-2 transition-all duration-200"
                      :style="{ backgroundColor: colorToCss(c) }"
                      :class="[
                        c === selectedColor
                          ? 'scale-110 border-slate-900 ring-2 ring-primary/20 shadow'
                          : 'border-white shadow-sm hover:scale-105'
                      ]"
                      @click="selectColor(c)"
                      :title="c"
                    >
                      <span
                        v-if="c === selectedColor"
                        class="absolute inset-0 flex items-center justify-center"
                      >
                        
                      </span>
                    </button>
                  </div>
                </div>

                <!-- Size -->
                <div v-if="sizes.length">
                  <div class="mb-2 flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-900">
                      Kích thước
                    </label>
                    <a
                      class="flex items-center gap-1 text-xs font-medium text-primary transition hover:underline"
                      href="#"
                      @click.prevent
                    >
                      <span class="material-symbols-outlined text-[12px]">straighten</span>
                      Hướng dẫn
                    </a>
                  </div>

                  <div class="grid grid-cols-5 gap-1.5 sm:grid-cols-7">
                    <button
                      v-for="s in sizes"
                      :key="s"
                      type="button"
                      class="relative h-9 rounded-lg border-2 text-xs font-bold transition-all duration-200"
                      :class="[
                        s === selectedSize
                          ? 'border-primary bg-gradient-to-br from-primary to-primary/90 text-white shadow'
                          : 'border-slate-200 bg-white text-slate-700 hover:border-primary active:scale-95'
                      ]"
                      @click="selectSize(s)"
                    >
                      {{ s }}
                    </button>
                  </div>
                </div>

                <!-- Quantity -->
                <div>
                  <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-900">
                    Số lượng
                  </label>

                  <div class="flex items-center gap-3">
                    <div class="inline-flex items-center overflow-hidden rounded-xl border-2 border-slate-200 bg-white shadow-sm">
                      <button
                        type="button"
                        class="grid h-9 w-9 place-items-center text-slate-700 transition-all hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="qty <= 1"
                        @click="decQty"
                      >
                        <span class="material-symbols-outlined text-[16px]">remove</span>
                      </button>

                      <input
                        class="h-9 w-12 border-x-2 border-slate-200 bg-transparent text-center text-sm font-bold text-slate-900 outline-none transition focus:border-primary"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        :max="maxQty"
                        v-model="qtyInput"
                        @blur="commitQty"
                      />

                      <button
                        type="button"
                        class="grid h-9 w-9 place-items-center text-slate-700 transition-all hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="qty >= maxQty"
                        @click="incQty"
                      >
                        <span class="material-symbols-outlined text-[16px]">add</span>
                      </button>
                    </div>

                    <p v-if="variants.length && !selectedVariant"
                      class="flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1.5 text-[10px] font-medium text-amber-700"
                    >
                      <span class="material-symbols-outlined text-[12px]">warning</span>
                      Chọn size & màu
                    </p>
                  </div>
                </div>
              </div>

              <!-- Divider -->
              <div class="my-4 h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>

              <!-- Actions -->
              <div class="space-y-2">
                <button
                  class="group relative flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-primary/90 font-bold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                  type="button"
                  :disabled="!canAddToCart || maxQty <= 0 || addingToCart"
                  @click="addToCart"
                >
                  <span v-if="addingToCart" class="material-symbols-outlined text-[20px] animate-spin">progress_activity</span>
                  <span v-else class="material-symbols-outlined text-[18px]">shopping_bag</span>
                  <span class="text-sm">{{ addingToCart ? "Đang thêm..." : "Thêm vào giỏ hàng" }}</span>
                </button>

                <div class="grid grid-cols-2 gap-2">
                  <button
                    class="flex h-10 items-center justify-center gap-1.5 rounded-xl border-2 border-slate-200 bg-white text-xs font-bold text-slate-900 transition-all duration-200 hover:border-primary hover:text-primary active:scale-95"
                    type="button"
                    @click="buyNow"
                  >
                    <span class="material-symbols-outlined text-[16px]">bolt</span>
                    Mua ngay
                  </button>

                  <button
                    class="flex h-10 items-center justify-center gap-1.5 rounded-xl border-2 border-slate-200 bg-white text-xs font-bold text-slate-900 transition-all duration-200 hover:border-red-400 hover:text-red-500 active:scale-95"
                    type="button"
                    @click="addToWishlist"
                  >
                    <span class="material-symbols-outlined text-[16px]">favorite</span>
                    Yêu thích
                  </button>
                </div>
              </div>

              <!-- Quick Info -->

            </div>
          </div>
        </div>
      </section>

      <!-- Product Details Tabs -->
      <section class="mx-auto max-w-6xl px-4 pb-6 sm:px-5 lg:px-6">
        <div class="mb-4">
          <div class="flex gap-2 overflow-x-auto pb-2">
            <button
              v-for="tabItem in tabs"
              :key="tabItem.key"
              class="group relative flex-shrink-0 rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200"
              :class="[
                tab === tabItem.key
                  ? 'bg-gradient-to-r from-primary to-primary/90 text-white shadow'
                  : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
              ]"
              type="button"
              @click="tab = tabItem.key"
            >
              <span class="material-symbols-outlined text-[14px] align-middle">
                {{ tabItem.icon }}
              </span>
              <span class="ml-1.5">{{ tabItem.label }}</span>
            </button>
          </div>
        </div>

        <div class="rounded-lg border border-slate-200/60 bg-white p-4 shadow sm:p-5">
          <template v-if="tab === 'desc'">
            <div class="prose max-w-none">
              <div class="text-sm text-slate-600 leading-relaxed" v-html="safeHtml(product.description)"></div>
            </div>
          </template>

          <template v-else-if="tab === 'spec'">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div class="rounded-lg bg-slate-50 p-3 transition-all hover:shadow">
                <div class="mb-1 flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-[14px] text-primary">tag</span>
                  <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">SKU</span>
                </div>
                <div class="text-sm font-semibold text-slate-900">{{ product.sku || "-" }}</div>
              </div>

              <div class="rounded-lg bg-slate-50 p-3 transition-all hover:shadow">
                <div class="mb-1 flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-[14px] text-primary">business</span>
                  <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Brand</span>
                </div>
                <div class="text-sm font-semibold text-slate-900">{{ product.brand?.name || "-" }}</div>
              </div>

              <div class="rounded-xl bg-slate-50 p-3 transition-all hover:shadow">
                <div class="mb-1 flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-[14px] text-primary">category</span>
                  <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Danh mục</span>
                </div>
                <div class="text-sm font-semibold text-slate-900">{{ categoriesText }}</div>
              </div>

              <div class="rounded-lg bg-slate-50 p-3 transition-all hover:shadow">
                <div class="mb-1 flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-[14px] text-primary">inventory_2</span>
                  <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kho</span>
                </div>
                <div class="text-sm font-semibold" :class="maxQty > 0 ? 'text-green-600' : 'text-red-500'">
                  {{ maxQty > 0 ? `Còn (${maxQty})` : 'Hết' }}
                </div>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="space-y-6">
              <ReviewStats :product-id="product.id" />
              <ReviewForm :product-id="product.id" @success="refreshReviews" />
              <ReviewList :product-id="product.id" :refresh-trigger="refreshCounter" />
            </div>
          </template>
        </div>
      </section>

      <!-- Related Products -->
      <section class="mx-auto max-w-6xl px-4 pb-10 sm:px-5 lg:px-6">
        <div class="mb-4 flex items-end justify-between">
          <div>
            <p class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-[0.15em] text-primary">
              Khám phá thêm
            </p>
            <h3 class="mt-0.5 text-lg font-black text-slate-900">
              Sản phẩm tương tự
            </h3>
          </div>

          <a
            class="flex items-center gap-1 text-xs font-bold text-primary transition hover:underline"
            href="#"
            @click.prevent="goShop"
          >
            Xem tất cả
            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
          </a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div
            v-if="relatedLoading"
            class="col-span-full rounded-lg border border-slate-200/60 bg-white p-6 text-center shadow"
          >
            <div class="h-6 w-6 animate-spin rounded-full border-3 border-primary/20 border-t-primary mx-auto"></div>
            <p class="mt-3 text-xs text-slate-500">Đang tải...</p>
          </div>

          <article
            v-else
            v-for="p in related"
            :key="p.id"
            class="group cursor-pointer overflow-hidden rounded-2xl border border-slate-200/60 bg-white shadow transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
            @click="goDetail(p.slug)"
          >
            <div class="relative overflow-hidden">
              <img
                class="aspect-square h-full w-full object-cover transition duration-300 group-hover:scale-105"
                :src="buildImageUrl(p.image) || fallbackImage"
                :alt="p.name"
              />
              
              <button
                class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-white/95 backdrop-blur shadow transition-all duration-200 hover:scale-105"
                type="button"
                @click.stop="addToWishlist(p)"
              >
                <span class="material-symbols-outlined text-[16px] text-slate-700 hover:text-red-500 transition">favorite</span>
              </button>
            </div>

            <div class="p-3">
              <h4 class="line-clamp-2 text-xs font-bold text-slate-900">{{ p.name }}</h4>
              <p class="mt-0.5 text-[10px] text-slate-500">{{ p.brand }}</p>
              <p class="mt-1.5 text-sm font-extrabold text-primary">{{ moneyVND(p.price) }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- Sticky Mobile CTA -->
      <div
        v-if="product"
        class="fixed bottom-0 left-0 right-0 z-50 border-t border-slate-200/80 bg-white/95 backdrop-blur-xl lg:hidden"
      >
        <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-2.5">
          <div class="min-w-0 flex-1">
            <div class="truncate text-[10px] text-slate-500">{{ product.brand?.name }}</div>
            <div class="truncate text-sm font-extrabold text-slate-900">{{ moneyVND(displayPrice) }}</div>
          </div>

          <button
            class="flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-primary/90 text-xs font-bold text-white shadow transition-all hover:-translate-y-0.5 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            :disabled="!canAddToCart || maxQty <= 0"
            @click="addToCart"
          >
            <span class="material-symbols-outlined text-[16px]">shopping_bag</span>
            Thêm vào giỏ
          </button>

          <button
            class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white shadow transition-all hover:border-red-400 hover:text-red-500 active:scale-95"
            type="button"
            @click="addToWishlist"
          >
            <span class="material-symbols-outlined text-[18px]">favorite</span>
          </button>
        </div>
      </div>
    </template>

    <div
      v-else
      class="mx-auto max-w-6xl px-4 py-6"
    >
      <div class="rounded-2xl border border-slate-200/60 bg-white p-6 text-center shadow-lg">
        <span class="material-symbols-outlined text-5xl text-slate-300">inventory_2</span>
        <p class="mt-3 text-sm font-medium text-slate-500">Không tìm thấy sản phẩm</p>
        <button
          class="mt-4 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition-all hover:-translate-y-0.5 hover:shadow"
          @click="goShop"
        >
          Quay lại cửa hàng
        </button>
      </div>
    </div>

    <!-- Spacer for mobile sticky CTA -->
    <div v-if="product" class="h-16 lg:hidden"></div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import productService from "../../../services/public/productService";
import { useCartStore } from "../../../stores/cart";
import { useAuthStore } from "../../../stores/auth";
import { buildImageUrl } from "../../../utils/image";
import { useAlert } from "../../../composables/useAlert";
import ReviewStats from "../../../components/shop/ReviewStats.vue";
import ReviewForm from "../../../components/shop/ReviewForm.vue";
import ReviewList from "../../../components/shop/ReviewList.vue";
import reviewService from "../../../services/public/reviewService";

const route = useRoute();
const router = useRouter();
const slug = computed(() => String(route.params.slug || ""));
const notify = useAlert();

const loading = ref(false);
const addingToCart = ref(false);
const error = ref("");

const product = ref(null);
const activeImage = ref("");
const tab = ref("desc");
const refreshCounter = ref(0);

const selectedSize = ref(null);
const selectedColor = ref(null);
const currentIndex = ref(0);

const qty = ref(1);
const qtyInput = ref("1");

const related = ref([]);
const relatedLoading = ref(false);

const reviewStats = ref({
  average_rating: 5,
  total_reviews: 0
});

const fallbackImage = "https://via.placeholder.com/800x800?text=Shoe";

// Zoom functionality
const isZooming = ref(false);
const imageContainer = ref(null);
const zoomPosition = ref({ x: 50, y: 50 });

const tabs = [
  { key: 'desc', label: 'Mô tả', icon: 'description' },
  { key: 'spec', label: 'Thông số', icon: 'tune' },
  { key: 'review', label: 'Đánh giá', icon: 'rate_review' },
];

const visibleCount = ref(4);

const variants = computed(() => product.value?.variants || []);
const hasSale = computed(() => !!product.value?.base_sale_price);

const cartStore = useCartStore();
const selectedVariant = ref(null);

const displayPrice = computed(() => {
  if (!product.value) return 0;
  if (selectedVariant.value) {
    return Number(selectedVariant.value.sale_price ?? selectedVariant.value.price ?? 0);
  }
  return Number(product.value.base_sale_price ?? product.value.base_price ?? 0);
});

const discountPercent = computed(() => {
  const base = Number(product.value?.base_price || 0);
  const sale = Number(product.value?.base_sale_price || 0);
  if (!base || !sale || sale >= base) return 0;
  return Math.round(((base - sale) / base) * 100);
});

const firstCategory = computed(() => (product.value?.categories || [])[0] || null);
const categoriesText = computed(
  () => (product.value?.categories || []).map((c) => c.name).join(", ") || "-"
);

const visibleImages = computed(() => {
  return images.value.slice(currentIndex.value, currentIndex.value + visibleCount.value);
});

const thumbIndex = ref(0);
const thumbVisibleCount = ref(4);

const visibleThumbImages = computed(() => {
  return images.value.slice(thumbIndex.value, thumbIndex.value + thumbVisibleCount.value);
});

function nextThumbImages() {
  if (thumbIndex.value + thumbVisibleCount.value < images.value.length) {
    thumbIndex.value++;
  }
}

function prevThumbImages() {
  if (thumbIndex.value > 0) {
    thumbIndex.value--;
  }
}

const images = computed(() => {
  if (!product.value) return [];
  const imgs = [];

  if (product.value.thumbnail) {
    imgs.push(product.value.thumbnail);
  }

  for (const v of product.value.variants || []) {
    const sorted = [...(v.images || [])].sort(
      (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)
    );

    for (const img of sorted) {
      if (img.url && !imgs.includes(img.url)) {
        imgs.push(img.url);
      }
    }
  }
  return imgs.length ? imgs : [fallbackImage];
});

const sizes = computed(() => {
  const set = new Set();
  for (const v of variants.value) {
    if (v.size !== null && v.size !== undefined && v.size !== "") {
      set.add(String(v.size));
    }
  }
  return Array.from(set).sort((a, b) => Number(a) - Number(b));
});

const colors = computed(() => {
  const set = new Set();
  for (const v of variants.value) {
    if (v.color) set.add(String(v.color));
  }
  return Array.from(set);
});

const colorHexByLabel = computed(() => {
  const map = {};
  for (const v of variants.value) {
    const c = v.color;
    if (!c || map[c] !== undefined) continue;
    const raw = String(v.color_hex || "").trim();
    if (/^#[0-9A-Fa-f]{6}$/i.test(raw)) map[String(c)] = raw;
  }
  return map;
});

const canAddToCart = computed(() => {
  if (!product.value) return false;
  if (variants.value.length) return !!selectedVariant.value;
  return true;
});

const availableQty = computed(() => {
  if (variants.value.length) {
    const s = Number(selectedVariant.value?.stock ?? 0);
    const inCart = getQtyInCart(selectedVariant.value?.id);
    return Math.max(0, s - inCart);
  }
  const ps = Number(product.value?.stock ?? 99);
  const inCart = getQtyInCart(null);
  return Math.max(0, ps - inCart);
});

function getQtyInCart(variantId) {
  const cartStore = useCartStore();
  const items = cartStore.items || [];
  const match = items.find(it => {
    const sameProduct = String(it.product_id) === String(product.value?.id);
    const sameVariant = variantId
      ? String(it.variant_id) === String(variantId)
      : !it.variant_id;
    return sameProduct && sameVariant;
  });
  return match ? Number(match.quantity || 0) : 0;
}

const maxQty = computed(() => {
  return availableQty.value;
});

const plainTextDescription = computed(() => {
  const raw = String(product.value?.description || "");
  return raw.replace(/<[^>]*>/g, "").trim() || "Chưa có mô tả sản phẩm.";
});

const zoomStyle = computed(() => {
  if (!isZooming.value) return {};
  return {
    transformOrigin: `${zoomPosition.value.x}% ${zoomPosition.value.y}%`,
  };
});

function handleZoom(e) {
  if (!imageContainer.value) return;
  const rect = imageContainer.value.getBoundingClientRect();
  const x = Math.round(((e.clientX - rect.left) / rect.width) * 100);
  const y = Math.round(((e.clientY - rect.top) / rect.height) * 100);
  zoomPosition.value = { x, y };
}

function nextImages() {
  if (currentIndex.value + visibleCount.value < images.value.length) {
    currentIndex.value++;
  }
}

function prevImages() {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  }
}

function clampQty(n) {
  const max = maxQty.value || 0;
  if (max <= 0) return 1;
  return Math.min(Math.max(1, n), max);
}

function syncQtyWithStock() {
  if (variants.value.length && !selectedVariant.value) {
    qty.value = 1;
    qtyInput.value = "1";
    return;
  }
  const max = availableQty.value;
  if (max <= 0) {
    qty.value = 1;
    qtyInput.value = "1";
    return;
  }
  const next = clampQty(Number(qty.value || 1));
  qty.value = next;
  qtyInput.value = String(next);
}

function incQty() {
  if (qty.value >= maxQty.value) {
    notify.warning("Không được vượt quá số lượng trong kho", {
      title: "Cảnh báo",
      duration: 2000,
    });
    return;
  }
  qty.value = clampQty(Number(qty.value) + 1);
  qtyInput.value = String(qty.value);
}

function decQty() {
  qty.value = clampQty(Number(qty.value) - 1);
  qtyInput.value = String(qty.value);
}

function commitQty() {
  const parsed = Number(String(qtyInput.value).replace(/[^\d]/g, "")) || 1;
  if (parsed > maxQty.value) {
    notify.warning("Không được vượt quá số lượng trong kho", {
      title: "Cảnh báo",
      duration: 2000,
    });
  }
  qty.value = clampQty(parsed);
  qtyInput.value = String(qty.value);
}

function selectSize(s) {
  selectedSize.value = s;
  syncVariant();
}

function selectColor(c) {
  selectedColor.value = c;
  syncVariant();
}

function syncVariant() {
  if (!variants.value.length) {
    selectedVariant.value = null;
    syncQtyWithStock();
    return;
  }

  const v = variants.value.find((x) => {
    const okSize = selectedSize.value ? String(x.size) === String(selectedSize.value) : true;
    const okColor = selectedColor.value ? String(x.color) === String(selectedColor.value) : true;
    return okSize && okColor;
  });

  selectedVariant.value = v || null;
  syncQtyWithStock();

  const img0 = (v?.images || [])[0]?.url;
  if (img0) activeImage.value = img0;
}

function moneyVND(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(n);
}

function safeHtml(html) {
  if (!html) return "";
  if (!/<[a-z][\s\S]*>/i.test(html)) {
    return html.replace(/\n/g, "<br>");
  }
  return html;
}

function colorToCss(name) {
  const key = String(name || "").trim();
  const fromApi = colorHexByLabel.value[key];
  if (fromApi) return fromApi;

  const s = key.toLowerCase();
  const map = {
    black: "#0f172a",
    trắng: "#ffffff",
    white: "#ffffff",
    red: "#dc2626",
    xanh: "#16a34a",
    green: "#16a34a",
    blue: "#2563eb",
    vàng: "#f59e0b",
    yellow: "#f59e0b",
    gray: "#94a3b8",
    grey: "#94a3b8",
    pink: "#ec4899",
    beige: "#d6c3a1",
    brown: "#92400e",
    navy: "#1e3a8a",
  };
  if (s.startsWith("#")) return s;
  return map[s] || name;
}

function isLightColor(colorName) {
  const hex = colorToCss(colorName);
  if (!hex.startsWith("#")) return false;
  const r = parseInt(hex.slice(1, 3), 16);
  const g = parseInt(hex.slice(3, 5), 16);
  const b = parseInt(hex.slice(5, 7), 16);
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  return luminance > 0.5;
}

async function fetchDetail() {
  loading.value = true;
  error.value = "";
  product.value = null;
  activeImage.value = "";
  selectedSize.value = null;
  selectedColor.value = null;
  selectedVariant.value = null;
  qty.value = 1;
  qtyInput.value = "1";
  tab.value = "desc";
  currentIndex.value = 0;

  try {
    const res = await productService.show(slug.value);
    product.value = res.data?.data ?? res.data ?? null;

    if (!product.value) {
      error.value = "API không trả về data sản phẩm.";
      return;
    }

    activeImage.value = product.value.thumbnail || images.value[0] || "";

    if (sizes.value.length) selectedSize.value = sizes.value[0];
    if (colors.value.length) selectedColor.value = colors.value[0];

    syncVariant();
    syncQtyWithStock();

    fetchRelated();
    fetchReviewStats();
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      (e?.response?.status === 404
        ? "Không tìm thấy sản phẩm (404)"
        : "Không tải được chi tiết sản phẩm");
  } finally {
    loading.value = false;
  }
}

async function fetchReviewStats() {
  try {
    const res = await reviewService.getProductStats(product.value.id);
    reviewStats.value = {
      average_rating: res.data.average_rating || 5,
      total_reviews: res.data.total_reviews || 0
    };
  } catch (e) {
    reviewStats.value = { average_rating: 5, total_reviews: 0 };
  }
}

async function fetchRelated() {
  relatedLoading.value = true;
  related.value = [];
  try {
    const res = await productService.list({ per_page: 8, page: 1, sort: "latest" });
    const list = (res.data?.data ?? []).filter((p) => p.slug !== product.value?.slug);

    related.value = list.slice(0, 4).map((p) => ({
      id: p.id,
      slug: p.slug,
      name: p.name,
      brand: p.brand?.name ?? "Brand",
      price: Number(p.base_sale_price ?? p.base_price ?? 0),
      image: p.thumbnail || "",
    }));
  } catch {
    related.value = [];
  } finally {
    relatedLoading.value = false;
  }
}

async function addToCart() {
  const product_id = product.value?.id;
  const product_variant_id = selectedVariant.value?.id || null;
  const quantity = Number(qty.value || 1);

  try {
    const auth = useAuthStore();
    if (!auth.user) return router.push("/login");

    if (quantity > availableQty.value) {
      notify.warning("Số lượng tối đa có thể thêm là " + availableQty.value, {
        title: "Không thể thêm",
        duration: 3000,
      });
      return;
    }

    addingToCart.value = true;
    await cartStore.addToCart({ product_id, product_variant_id, quantity });
    notify.success("Đã thêm vào giỏ hàng", {
      title: "Thành công",
      duration: 2500,
    });
    qty.value = 1;
    qtyInput.value = "1";
  } catch (e) {
    const errMsg = cartStore.error || "Thêm vào giỏ thất bại";
    notify.error(errMsg, {
      title: "Lỗi",
      duration: 3000,
    });
  } finally {
    addingToCart.value = false;
  }
}

function buyNow() {
  const product_id = product.value?.id;
  const product_variant_id = selectedVariant.value?.id || null;
  
  const auth = useAuthStore();
  if (!auth.user) return router.push("/login");
  
  addToCart().then(() => {
    router.push("/shop/cart");
  });
}

function addToWishlist(p) {
  console.log("WISHLIST", p?.id ? p : { product_id: product.value?.id });
}

function goDetail(s) {
  router.push(`/shop/products/${s}`);
}

function goShop() {
  router.push("/shop/products");
}

function goCategory(c) {
  router.push({ path: "/shop/products", query: { category: c.id } });
}

function goBrand(b) {
  router.push({ path: "/shop/products", query: { brand: b.id } });
}

function refreshReviews() {
  refreshCounter.value++;
}

onMounted(fetchDetail);
watch(slug, fetchDetail);
</script>

<style scoped>
.material-symbols-outlined {
  font-variation-settings: "FILL" 0, "wght" 500, "GRAD" 0, "opsz" 24;
}

::-webkit-scrollbar {
  height: 6px;
}

::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.3);
  border-radius: 999px;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
