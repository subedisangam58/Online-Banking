const labels = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,24,25,26,27,28,29,30];
const data = {
    labels: labels,
    datasets: [{
        label: 'Last 30 Days Transaction',
        data: [65, 59, 80, 81, 56, 55, 40],
        fill: false,
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1
    }]
};
const config = {
    type: 'line',
    data: data,
};
var myChart = new Chart(
    document.getElementById('myChart'),config
);