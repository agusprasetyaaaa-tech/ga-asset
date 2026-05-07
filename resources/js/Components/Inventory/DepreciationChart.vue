<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    purchasePrice: [Number, String],
    salvageValue: [Number, String],
    usefulLife: Number,
    purchaseDate: String,
    currentBookValue: Number
});

const chartData = computed(() => {
    const price = parseFloat(props.purchasePrice) || 0;
    const salvage = parseFloat(props.salvageValue) || 0;
    const life = props.usefulLife || 0;
    const startDate = props.purchaseDate ? new Date(props.purchaseDate) : new Date();
    const startYear = startDate.getFullYear();

    const labels = [];
    const values = [];
    const currentYear = new Date().getFullYear();
    const currentYearPoint = [];

    // Calculate annual depreciation
    const annualDepreciation = life > 0 ? (price - salvage) / life : 0;

    for (let i = 0; i <= life; i++) {
        const year = startYear + i;
        labels.push(year.toString());
        
        const bookValue = Math.max(price - (annualDepreciation * i), salvage);
        values.push(bookValue);

        // Marker for current year
        if (year === currentYear) {
            currentYearPoint.push(props.currentBookValue);
        } else {
            currentYearPoint.push(null);
        }
    }

    // If useful life is 0 or not provided, just show current point
    if (life <= 0) {
        labels.push(startYear.toString());
        values.push(price);
    }

    return {
        labels,
        datasets: [
            {
                label: 'Nilai Buku (Proyeksi)',
                data: values,
                borderColor: '#10b981', // Emerald 500
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: false,
            ticks: {
                callback: function(value) {
                    return 'Rp ' + (value / 1000000).toFixed(1) + ' jt';
                },
                font: {
                    size: 10,
                    weight: 'bold'
                }
            },
            grid: {
                color: 'rgba(0, 0, 0, 0.05)'
            }
        },
        x: {
            ticks: {
                font: {
                    size: 10,
                    weight: 'bold'
                }
            },
            grid: {
                display: false
            }
        }
    }
};
</script>

<template>
    <div class="h-64 w-full">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
