<aside>
    <div class="top">
        <div class="logo">
            <img src="<?= base_url(); ?>img/ronstan.png" alt="Ronstan Logo">
        </div>
        <div class="close" id="close-btn">
            <span class="material-symbols-outlined">close</span>
        </div>
    </div>

    <div class="sidebar">
        <a href="<?= base_url('user'); ?>" class="<?= ($sidebarData == "dashboard") ? 'active' : 'inactive' ?>">
            <span class="lni lni-grid-alt"></span>
            <h3>Dashboard</h3>
        </a>
        <a href="<?= base_url('recap'); ?>" class="<?= ($sidebarData == "recap") ? 'active' : 'inactive' ?>">
            <span class="lni lni-files"></span>
            <h3>Recap</h3>
        </a>
        <a href="<?= base_url('monitoring'); ?>" class="<?= ($sidebarData == "monitoring") ? 'active' : 'inactive' ?>">
            <span class="fa-solid fa-chart-line"></span>
            <h3>Monitoring</h3>
        </a>
        <a href="<?= base_url('logout') ?>">
            <span class="fa-solid fa-arrow-right-from-bracket"></span>
            <h3>Logout</h3>
        </a>
    </div>
</aside>