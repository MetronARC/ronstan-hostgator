<?= $this->extend('template3/index') ?>
<?= $this->section('page-content') ?>

<h1>All Machine Charts for <?= htmlspecialchars($date) ?></h1>

<!-- Add Legend -->
<div class="chart-legend">
    <div class="legend-item">
        <span class="legend-color" style="background-color: #228B22;"></span>
        <span class="legend-label">Production</span>
    </div>
    <div class="legend-item">
        <span class="legend-color" style="background-color: #2196F3;"></span>
        <span class="legend-label">MAINTENANCE</span>
    </div>
    <div class="legend-item">
        <span class="legend-color" style="background-color: #FFEA00;"></span>
        <span class="legend-label">SETUP</span>
    </div>
    <div class="legend-item">
        <span class="legend-color" style="background-color: #FFFFFF; border: 1px solid #BDBDBD;"></span>
        <span class="legend-label">TOOLING</span>
    </div>
    <div class="legend-item">
        <span class="legend-color" style="background-color:#383838;"></span>
        <span class="legend-label">DOWN-TIME</span>
    </div>
</div>

<div class="recent-orders">
    <!-- Add loading spinner -->
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Loading charts...</p>
    </div>
    <div id="charts-container" style="display: flex; flex-direction: column; gap: 20px; align-items: center; width: 100%; margin: 0 auto; padding: 10px;">
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

<style>
    /* Legend Styles */
    .chart-legend {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin: 20px auto;
        padding: 15px;
        background-color: var(--color-white);
        border-radius: var(--card-border-radius);
        box-shadow: var(--box-shadow);
        max-width: 1200px;
        width: 95%;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        display: inline-block;
    }

    .legend-label {
        font-size: 1rem;
        color: var(--color-dark);
    }

    @media (max-width: 768px) {
        .chart-legend {
            padding: 10px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .legend-item {
            gap: 5px;
        }

        .legend-color {
            width: 15px;
            height: 15px;
        }

        .legend-label {
            font-size: 0.9rem;
        }

        #charts-container {
            padding: 5px;
        }
        
        .chart-wrapper {
            width: 98% !important;
            padding: 10px !important;
            height: 300px !important;
        }
        
        .chart-wrapper h3 {
            font-size: 1rem !important;
        }

        .chart-container {
            height: 200px !important;
        }
    }

    @media (min-width: 769px) and (max-width: 1024px) {
        .chart-wrapper {
            width: 90% !important;
            height: 350px;
        }

        .chart-container {
            height: 250px;
        }
    }

    .chart-wrapper {
        width: 95%;
        max-width: 1200px;
        height: 500px;
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: var(--color-white);
        padding: 20px;
        border-radius: var(--card-border-radius);
        box-shadow: var(--box-shadow);
    }

    .chart-container {
        width: 100%;
        height: 400px;
        position: relative;
    }

    .chart-canvas {
        width: 100% !important;
        height: 100% !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectedDate = '<?= htmlspecialchars($date) ?>'; // Get the date from PHP
        if (selectedDate) {
            fetchChartData(selectedDate); // Use the selected date
        }
    });

    function fetchChartData(date) {
        // Show loading spinner
        const loadingSpinner = document.querySelector('.loading-spinner');
        loadingSpinner.style.display = 'block';

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

                // Sort machine names alphabetically
                const sortedMachineNames = Object.keys(responseData.data).sort();

                const renderPromises = [];
                sortedMachineNames.forEach(machineName => {
                    const machineData = responseData.data[machineName];
                    
                    // Create a wrapper div for each chart
                    const chartWrapper = document.createElement('div');
                    chartWrapper.className = 'chart-wrapper';

                    // Create and style the title
                    const title = document.createElement('h3');
                    title.textContent = `Machine ID: ${machineName}`;
                    title.style.marginBottom = '15px';
                    title.style.textAlign = 'center';
                    title.style.width = '100%';
                    title.style.color = 'var(--color-dark)';
                    title.style.fontSize = '1.2rem';
                    
                    // Create a container for the chart
                    const chartContainer = document.createElement('div');
                    chartContainer.className = 'chart-container';
                    
                    // Create canvas for the chart
                    const canvas = document.createElement('canvas');
                    canvas.id = `chart-${machineName}`;
                    canvas.className = 'chart-canvas';
                    
                    // Add elements to their containers
                    chartContainer.appendChild(canvas);
                    chartWrapper.appendChild(title);
                    chartWrapper.appendChild(chartContainer);
                    chartsContainer.appendChild(chartWrapper);
                    
                    console.log(`Canvas created for ${machineName}:`, canvas);
                    renderPromises.push(renderChart(machineData, date, machineName, canvas));
                });

                // Wait for all charts to render before hiding the loading spinner
                Promise.all(renderPromises)
                    .finally(() => {
                        loadingSpinner.style.display = 'none';
                    });
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
                // Hide loading spinner on error
                loadingSpinner.style.display = 'none';
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
            let color = '#383838'; // Default grey
            let boxcolor = '#383838';
            let hoverLabel = '';

            data.forEach(interval => {
                if (interval.ArcOn && interval.ArcOff) {
                    const arcOnTime = timeToMinutes(interval.ArcOn);
                    const arcOffTime = timeToMinutes(interval.ArcOff);

                    if (arcOnTime !== null && arcOffTime !== null) {
                        if (i >= arcOnTime && i < arcOffTime) {
                            // Determine color based on State
                            switch ((interval.State || '').toUpperCase()) {
                                case 'ON':
                                    color = '#228B22'; // Green
                                    boxcolor = '#228B22';
                                    break;
                                case 'MAINTENANCE':
                                    color = '#2196F3'; // Blue
                                    boxcolor = '#2196F3';
                                    break;
                                case 'SETUP':
                                    color = '#FFEA00'; // Yellow
                                    boxcolor = '#FFEA00';
                                    break;
                                case 'TOOLING':
                                    color = '#FFFFFF'; // White
                                    boxcolor = '#FFFFFF';
                                    break;
                                default:
                                    color = '#383838'; // Grey
                                    boxcolor = '#383838';
                            }
                            if (i === arcOnTime) {
                                hoverLabel = `ArcOn: ${interval.ArcOn}, ArcOff: ${interval.ArcOff}, State: ${interval.State || 'Unknown'}, ArcTotal: ${arcOffTime - arcOnTime} minutes`;
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

        return new Promise((resolve) => {
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
                    maintainAspectRatio: false,
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
                                enabled: false,
                                mode: 'x',
                                modifierKey: 'ctrl',
                            },
                            zoom: {
                                enabled: false,
                                mode: 'x',
                                drag: {
                                    enabled: false,
                                    backgroundColor: 'rgba(225,225,225,0.3)',
                                },
                                wheel: {
                                    enabled: false,
                                },
                                pinch: {
                                    enabled: false,
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
            resolve();
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
