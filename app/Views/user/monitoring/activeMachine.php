<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>

<main>
    <h1>Dashboard</h1>

    <div class="date"></div>

    <div class="insights">
        <?php foreach ($machines as $machine): ?>
            <div class="sales">
                <span class="material-symbols-outlined">zoom_in_map</span>
                <div class="middle">
                    <div class="left">
                        <h3>Latest Arc On: <?= $machine['lastBeat']; ?></h3>
                        <h1><?= $machine['MachineID']; ?></h1>
                    </div>
                    <div class="progress">
                        <a><img src="<?= base_url(); ?>img/<?= $machine['State'] === 'OFF' ? 'machineInactive.png' : ($machine['State'] === 'IDLE' ? 'machineIDLE.png' : 'machineActive.png'); ?>" alt="Machine State"></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function updateMachineState() {
            $.ajax({
                url: "<?= base_url('monitoring/getMachineState/' . $areaName); ?>",
                method: "GET",
                dataType: "json",
                success: function(data) {
                    $('.sales').each(function(index, element) {
                        var machine = data[index];
                        $(element).find('h3').text("Latest Arc On: " + machine.lastBeat);
                        $(element).find('h1').text(machine.MachineID);
                        var stateImage = machine.State === 'OFF' ? 'machineInactive.png' : (machine.State === 'IDLE' ? 'machineIDLE.png' : 'machineActive.png');
                        $(element).find('img').attr('src', '<?= base_url(); ?>img/' + stateImage);
                    });
                }
            });
        }

        setInterval(updateMachineState, 1000);
    </script>


    <!-- END OF INSIGHTS -->
</main>

<div class="right">
    <div class="top">
        <button id="menu-btn">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="theme-toggler">
            <span class="lni lni-sun"></span>
            <span class="fa-regular fa-moon"></span>
        </div>
        <div class="profile">
            <div class="info">
                <p>Hey, <b>Ronstan</b></p>
                <small class="text-muted">Admin</small>
            </div>
            <div class="profile-photo">
                <a href="Machine/Machine.php"><img src="<?= base_url(); ?>img/Logo.png" alt="AdminLogo"></a>
            </div>
        </div>
    </div>
    <!-- <div class="recent-updates">
        <h2 style="margin-bottom: 0.5em">Recent Pings</h2>
        <div class="updates">
            <div class="update">
                <div class="profile-photo">
                <img src="<?= base_url(); ?>img/Logo.png" alt="AdminLogo">
                </div>
                <div class="message">
                    <p><b>Rico Roenaldo</b> is Pinging at <b>Area 1</b></p>
                    <small class="text-muted">at <b>13:01:01 AM</b></small>
                </div>
            </div>
        </div>
    </div> -->
</div>



<?= $this->endSection() ?>