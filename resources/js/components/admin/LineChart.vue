<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';
import {
  Chart,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';

Chart.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  LineController,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
  labels: {
    type: Array,
    default: () => [],
  },
  data: {
    type: Array,
    default: () => [],
  },
  height: {
    type: Number,
    default: 280,
  },
});

const chartCanvas = ref(null);
let chartInstance = null;

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      titleColor: '#1e293b',
      bodyColor: '#6366f1',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      padding: 12,
      cornerRadius: 8,
      displayColors: false,
      titleFont: {
        size: 12,
        weight: '500',
      },
      bodyFont: {
        size: 14,
        weight: '700',
      },
      callbacks: {
        label: function (context) {
          return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            minimumFractionDigits: 0,
          }).format(context.raw);
        },
      },
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        color: '#94a3b8',
        font: {
          size: 12,
        },
      },
      border: {
        display: false,
      },
    },
    y: {
      grid: {
        color: '#f1f5f9',
        drawBorder: false,
      },
      ticks: {
        color: '#94a3b8',
        font: {
          size: 12,
        },
        callback: function (value) {
          if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + 'tr';
          } else if (value >= 1000) {
            return (value / 1000).toFixed(0) + 'k';
          }
          return value;
        },
      },
      border: {
        display: false,
      },
    },
  },
};

function destroyChart() {
  if (chartInstance) {
    chartInstance.destroy();
    chartInstance = null;
  }
}

function createChart() {
  if (!chartCanvas.value) return;
  
  // Destroy existing chart first
  destroyChart();

  if (props.labels.length > 0 && props.data.length > 0) {
    const ctx = chartCanvas.value.getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, props.height);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
    gradient.addColorStop(0.5, 'rgba(139, 92, 246, 0.15)');
    gradient.addColorStop(1, 'rgba(139, 92, 246, 0)');

    chartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: props.labels,
        datasets: [
          {
            label: 'Doanh thu',
            data: props.data,
            fill: true,
            backgroundColor: gradient,
            borderColor: '#6366f1',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 6,
            pointHoverBackgroundColor: '#6366f1',
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3,
          },
        ],
      },
      options: chartOptions,
    });
  }
}

watch(
  () => [props.labels, props.data],
  () => {
    nextTick(() => {
      createChart();
    });
  },
  { deep: true }
);

onMounted(() => {
  nextTick(() => {
    createChart();
  });
});

onUnmounted(() => {
  destroyChart();
});
</script>

<template>
  <div class="relative" :style="{ height: height + 'px' }">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>
