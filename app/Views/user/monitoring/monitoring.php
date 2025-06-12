<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>

<main>
    <h1>Dashboard</h1>

    <div class="date"></div>

    <div class="insights">
        <?php foreach ($areas as $area): ?>
            <!-- ACTIVE AREA -->
            <div class="sales">
                <span class="material-symbols-outlined">zoom_in_map</span>
                <div class="middle">
                    <div class="left">
                        <h3><?= $area['name'] ?></h3>
                        <h2 id="activeMachineCount">Active Machine: <?= $area['rowCount'] ?></h2>
                    </div>
                    <a href="<?= base_url('monitoring/activeMachine/' . $area['name']); ?>">
                        <div class="progress">
                            <svg>
                                <circle cx="42" cy="42" r="36"></circle>
                            </svg>
                            <div class="number">
                                <h3>View</h3>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <!-- END OF ACTIVE AREA -->
        <?php endforeach; ?>
    </div>
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
</div>

<?= $this->endSection() ?>