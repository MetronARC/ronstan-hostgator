<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>

<h1>Dashboard</h1>

<div class="date"></div>

<div class="insights">
    <?php foreach ($machines as $machine): ?>
        <div class="sales">
            <span class="material-symbols-outlined">zoom_in_map</span>
            <a href="<?= base_url('monitoring/machineDetails/' . $machine['MachineID']); ?>">
                <div class="middle">
                    <div class="left">
                        <h3>Latest Arc On: <?= $machine['lastSeen'] ?? 'No data'; ?></h3>
                        <h1><?= $machine['MachineID']; ?></h1>
                    </div>
                    <div class="progress">
                        <img src="<?= base_url(); ?>img/<?= $machine['State'] === 'OFF' ? 'machineInactive.png' : ($machine['State'] === 'IDLE' ? 'machineIDLE.png' : 'machineActive.png'); ?>" alt="Machine State">
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function updateMachineState() {
        $.ajax({
            url: "<?= base_url('monitoring/getMachineState/' . $areaId); ?>",
            method: "GET",
            dataType: "json",
            success: function(response) {
                if (response.success && response.data) {
                    $('.sales').each(function(index, element) {
                        var machine = response.data[index];
                        if (machine) {
                            $(element).find('h3').text("Latest Arc On: " + (machine.lastSeen || 'No data'));
                            $(element).find('h1').text(machine.MachineID);
                            var stateImage = machine.State === 'OFF' ? 'machineInactive.png' : (machine.State === 'IDLE' ? 'machineIDLE.png' : 'machineActive.png');
                            $(element).find('img').attr('src', '<?= base_url(); ?>img/' + stateImage);
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching machine states:', error);
            }
        });
    }

    setInterval(updateMachineState, 1000);
</script>


<!-- END OF INSIGHTS -->

<?= $this->endSection() ?>