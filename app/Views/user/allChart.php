<?= $this->extend('template3/index') ?>
<?= $this->section('page-content') ?>

<h1>All Machine Charts for <?= htmlspecialchars($date) ?></h1>
<div class="recent-orders">
<div id="charts-container" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: flex-start; max-width: 1000px; margin: 0 auto;">
        <!-- Charts will be dynamically inserted here -->
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.34/moment-timezone-with-data.min.js"></script>
<!-- Chart.js Zoom Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.0/dist/chartjs-plugin-zoom.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- Chart.js Date Adapter -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0"></script>
<!-- Sweet Alert Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectedDate = '<?= htmlspecialchars($date) ?>'; // Get the date from PHP
        if (selectedDate) {
            fetchChartData(selectedDate); // Use the selected date
        }
    });

    function fetchChartData(date) {
        fetch('<?= base_url('recap/fetchChartData') ?>', { // Fetch from the controller
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    date: date
                }) // Send the selected date to the backend
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(responseData => {
                console.log('Received date:', responseData.date); // Log the received date
                console.log('Fetched data:', responseData.data); // Log the fetched data

                const chartsContainer = document.getElementById('charts-container');
                chartsContainer.innerHTML = ''; // Clear previous charts

                for (const machineName in responseData.data) {
                    const machineData = responseData.data[machineName];
                    const canvas = document.createElement('canvas');
                    canvas.id = `chart-${machineName}`;
                    canvas.style.width = '300px';
                    canvas.style.height = '100px';
                    chartsContainer.appendChild(canvas);
                    console.log(`Canvas created for ${machineName}:`, canvas);
                    renderChart(machineData, date, machineName, canvas);
                }
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
            });
    }


    function renderChart(data, date, machineName, canvas) {
        const dataPoints = [];
        const backgroundColors = [];
        const borderColors = [];
        const hoverLabels = [];
        const boxColors = [];

        for (let i = 0; i < 24 * 60; i++) {
            const time = moment().startOf('day').minutes(i).format('HH:mm');
            let color = '#FFEA00';
            let boxcolor = '#228B22';
            let hoverLabel = '';

            data.forEach(interval => {
                if (interval.ArcOn && interval.ArcOff) {
                    const arcOnTime = timeToMinutes(interval.ArcOn);
                    const arcOffTime = timeToMinutes(interval.ArcOff);

                    if (arcOnTime !== null && arcOffTime !== null) {
                        if (i >= arcOnTime && i < arcOffTime) {
                            color = '#228B22';
                            if (i === arcOnTime) {
                                hoverLabel = `ArcOn: ${interval.ArcOn}, ArcOff: ${interval.ArcOff}, ArcTotal: ${arcOffTime - arcOnTime} minutes`;
                            }
                        }
                    }
                }
            });

            dataPoints.push({
                x: timeToDateTime(time, date),
                y: 1,
                label: hoverLabel
            });
            backgroundColors.push(color);
            borderColors.push(color);
            hoverLabels.push(hoverLabel);
            boxColors.push(boxcolor);
        }

        const ctx = canvas.getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                datasets: [{
                    label: `Usage for ${machineName}`,
                    data: dataPoints,
                    backgroundColor: boxColors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(tooltipItem) {
                                const label = tooltipItem.raw.label;
                                return label ? label : '';
                            }
                        }
                    },
                    zoom: {
                        pan: {
                            enabled: false, // Disable panning
                            mode: 'x',
                            modifierKey: 'ctrl',
                        },
                        zoom: {
                            enabled: false, // Disable zooming
                            mode: 'x',
                            drag: {
                                enabled: false, // Disable drag zooming
                                backgroundColor: 'rgba(225,225,225,0.3)',
                            },
                            wheel: {
                                enabled: false, // Disable wheel zooming
                            },
                            pinch: {
                                enabled: false, // Disable pinch zooming
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute',
                            displayFormats: {
                                minute: 'HH:mm'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Time'
                        },
                        ticks: {
                            source: 'data',
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            major: {
                                enabled: true
                            },
                            callback: function(value, index, values) {
                                const time = moment(value).format('HH:mm');
                                const specificTimes = ['00:01', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00', '23:59'];
                                return specificTimes.includes(time) ? time : '';
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 1,
                        ticks: {
                            stepSize: 1,
                            callback: value => value === 1 ? 'On' : 'Off'
                        },
                        title: {
                            display: true,
                            text: 'Status'
                        }
                    }
                }
            }
        });
    }

    function timeToMinutes(time) {
        if (!time) {
            return null;
        }
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }

    function timeToDateTime(time, date) {
        return moment(date + ' ' + time, 'YYYY-MM-DD HH:mm').toDate();
    }
</script>

<?= $this->endSection() ?>
