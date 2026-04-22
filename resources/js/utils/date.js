const DEFAULT_TIMEZONE = 'Asia/Ho_Chi_Minh';

export function formatDateTime(dateString, options = {}) {
  if (!dateString) return '-';
  
  const d = new Date(dateString);
  if (Number.isNaN(d.getTime())) return dateString;

  const dateStyle = options.dateStyle ?? 'short';
  const timeStyle = options.timeStyle ?? 'short';
  const timeZone = options.timeZone ?? DEFAULT_TIMEZONE;

  return new Intl.DateTimeFormat('vi-VN', {
    dateStyle,
    timeStyle,
    timeZone,
    ...options,
  }).format(d);
}

export function formatDate(dateString, options = {}) {
  return formatDateTime(dateString, {
    timeStyle: undefined,
    ...options,
  });
}

export function formatTime(dateString, options = {}) {
  return formatDateTime(dateString, {
    dateStyle: undefined,
    timeStyle: 'short',
    ...options,
  });
}

export function formatRelativeTime(dateString) {
  if (!dateString) return '-';
  
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;

  const now = new Date();
  const diffTime = now - date;
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 0) {
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
    if (diffHours === 0) return 'Vừa mới';
    return `${diffHours} giờ trước`;
  } else if (diffDays === 1) {
    return 'Hôm qua';
  } else if (diffDays < 7) {
    return `${diffDays} ngày trước`;
  } else {
    return formatDate(dateString);
  }
}
