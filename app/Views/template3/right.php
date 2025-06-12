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
    <div class="recent-updates">
        <h2 style="margin-bottom: 0.5em"></h2>
        <div class="updates">
            <div class="update">
                <div class="profile-photo">
                <img src="<?= base_url(); ?>img/Logo.png" alt="AdminLogo">
                </div>
                <div class="message">
                    <h2 style="font-size: 1.2rem">Total Machine Up Time</h2>
                    <h2><?= htmlspecialchars($arcTime) ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>