<?= $this->extend('template2/index') ?>
<?= $this->section('page-content') ?>

<h1>Dashboard</h1>

<div class="date"></div>

<div class="insights">
    <?php foreach ($areas as $area): ?>
        <div class="sales">
            <span class="material-symbols-outlined">zoom_in_map</span>
            <a href="<?= base_url('monitoring/activeMachine/' . $area['areaID']); ?>">
                <div class="middle">
                    <div class="left">
                        <h3><?= $area['areaName'] ?></h3>
                        <div class="machine-states" id="machine-states-<?= $area['areaID'] ?>">
                            <?php foreach ($area['machines'] as $machine): ?>
                                <span class="material-symbols-outlined" style="background: <?= $machine['State'] === 'ON' ? 'green' : ($machine['State'] === 'IDLE' ? 'yellow' : 'red') ?>; width: 30px; height: 30px; margin-right: 15px"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function fetchMachineStates(areaId) {
        $.ajax({
            url: '<?= base_url('monitoring/getMachineState/') ?>' + areaId,
            method: 'GET',
            success: function(response) {
                if (!response.success) {
                    console.error('Error fetching machine states for area:', areaId);
                    return;
                }

                const machineStatesContainer = $('#machine-states-' + areaId);
                if (!machineStatesContainer.length) return;
                
                machineStatesContainer.empty();

                response.data.forEach(function(machine) {
                    let stateColor;
                    switch (machine.State) {
                        case 'ON':
                            stateColor = 'green';
                            break;
                        case 'IDLE':
                            stateColor = 'yellow';
                            break;
                        case 'INSPECT':
                            stateColor = 'orange';
                            break;
                        default:
                            stateColor = 'gray';
                    }

                    machineStatesContainer.append('<span class="material-symbols-outlined" style="background: ' + stateColor + '; width: 30px; height: 30px; margin-right: 15px"></span>');
                });
            },
            error: function(xhr, status, error) {
                console.error('AJAX error for area:', areaId, error);
            }
        });
    }

    // Store interval IDs for each area
    const intervalIds = {};

    // Function to start monitoring for an area
    function startMonitoring(areaId) {
        // Clear any existing interval for this area
        if (intervalIds[areaId]) {
            clearInterval(intervalIds[areaId]);
        }

        // Initial fetch
        fetchMachineStates(areaId);

        // Set up new interval
        intervalIds[areaId] = setInterval(function() {
            fetchMachineStates(areaId);
        }, 1000);
    }

    // Start monitoring for each area when the page loads
    $(document).ready(function() {
        <?php foreach ($areas as $area): ?>
            startMonitoring('<?= $area['areaID'] ?>');
        <?php endforeach; ?>
    });

    // Clean up intervals when leaving the page
    $(window).on('unload', function() {
        Object.values(intervalIds).forEach(clearInterval);
    });
</script>

<?= $this->endSection() ?>