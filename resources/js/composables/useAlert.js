import { reactive, readonly } from "vue";

const state = reactive({
  alerts: [],
});

let seed = 0;

function remove(id) {
  const index = state.alerts.findIndex((item) => item.id === id);
  if (index !== -1) {
    state.alerts.splice(index, 1);
  }
}

function show(type, message, options = {}) {
  const id = ++seed;

  const item = {
    id,
    type,
    title: options.title || "",
    message: String(message || ""),
    duration: Number(options.duration ?? 3000),
    closable: options.closable !== false,
  };

  state.alerts.push(item);

  if (item.duration > 0) {
    window.setTimeout(() => {
      remove(id);
    }, item.duration);
  }

  return id;
}

function success(message, options = {}) {
  return show("success", message, options);
}

function error(message, options = {}) {
  return show("error", message, options);
}

function info(message, options = {}) {
  return show("info", message, options);
}

function warning(message, options = {}) {
  return show("warning", message, options);
}

function clear() {
  state.alerts.splice(0, state.alerts.length);
}

export function useAlert() {
  return {
    alerts: readonly(state.alerts),
    show,
    success,
    error,
    info,
    warning,
    remove,
    clear,
  };
}