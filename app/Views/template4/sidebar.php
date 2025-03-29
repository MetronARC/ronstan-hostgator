<aside>
    <div class="top">
        <div class="logo">
            <img src="<?= base_url(); ?>img/Logo.png" alt="MetronARC Logo"><h3>Metronarc <span class="danger">Technology</span></h3>
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
        <a href="<?= base_url('record'); ?>" class="<?= ($sidebarData == "record") ? 'active' : 'inactive' ?>">
            <span class="fa-regular fa-clipboard"></span>
            <h3>Records</h3>
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