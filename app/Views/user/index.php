<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>

<main>
    <h1>Dashboard</h1>

    <div class="date"></div>

    <div class="insights">
        <!-- ACTIVE AREA -->
        <div class="sales">
            <span class="material-symbols-outlined">zoom_in_map</span>
            <div class="middle">
                <div class="left">
                    <h3>Active Machines</h3>
                    <h1 id="activeMachineCount"><?= esc($activeMachineCount) ?></h1>
                </div>
                <a href="<?= base_url('monitoring'); ?>">
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
        <div class="income">
            <span class="material-symbols-outlined">engineering</span>
            <div class="middle">
                <div class="left">
                    <h3>Machine Up Time Today</h3>
                    <h1><?= esc($machineUptime) ?></h1> <!-- Dynamic uptime -->
                </div>
                <div class="progress">
                    <svg>
                        <circle cx="42" cy="38" r="36"></circle>
                    </svg>
                    <div class="number">
                    </div>
                </div>
            </div>
        </div>
        <!-- END OF MOST ACTIVE WELDERS -->
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

<!-- Production	:	{ ON } 		        < Green >	    #28a745
Maintenance	:	    { MAINTENANCE }     < Yellow >	    #ffc107
Setup 		: 	    { SETUP }	        < Blue >	    #007bff
Tooling 	: 	    { TOOLING }	        < Purple >	    #6f42c1
Else 		:	    { IDLE }	        < Gray >	    #6c757d -->