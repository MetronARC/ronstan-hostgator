<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPARC Monitoring Dashboard Login</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url(); ?>/img/Logo.png" />
    <link rel="stylesheet" href="<?= base_url(); ?>/css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>

<body style="background: url('<?= base_url(); ?>/img/hero-bg.jpg') center / cover no-repeat;" data-aos="zoom-in">

    <!-- Navigation Bar -->
    <header>
        <nav class="navbar">
            <a href="#" class="logo">
                <img src="<?= base_url(); ?>/img/Logo.png" alt="logo">
            </a>
        </nav>
    </header>
    <!-- Navigation Bar -->

    <!-- Form Popup -->
    <div class="form-popup">
        <div class="form-box">
            <div class="form-details" style="background: url('<?= base_url(); ?>/img/login-img.jpg');">
                <h2>Welcome</h2>
                <p>Please Log in Using Correct Credentials</p>
            </div>
            <div class="form-content">
                <h2>LOGIN</h2>

                <?= view('Myth\Auth\Views\_message_block') ?>
                <form action="<?= url_to('login') ?>" method="post">
                    <?= csrf_field() ?>

                    <!-- Adjusted username/email input field -->
                    <?php if ($config->validFields === ['email']): ?>
                        <div class="input-field">
                            <input type="email"
                                class="form-control form-control-user <?php if (session('errors.login')): ?>is-invalid<?php endif ?>"
                                name="login" aria-describedby="emailHelp" required>
                            <label>Email</label> <!-- Added label for styling -->
                            <?php if (session('errors.login')): ?>
                                <div class="invalid-feedback">
                                    <?= session('errors.login') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="input-field">
                            <input type="text"
                                class="form-control form-control-user <?php if (session('errors.login')): ?>is-invalid<?php endif ?>"
                                name="login" required>
                            <label>Username</label> <!-- Added label for styling -->
                            <?php if (session('errors.login')): ?>
                                <div class="invalid-feedback">
                                    <?= session('errors.login') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Keep the existing class for password input field -->
                    <div class="input-field">
                        <input type="password" name="password"
                            class="form-control form-control-user <?php if (session('errors.password')): ?>is-invalid<?php endif ?>"
                             required>
                        <label>Password</label> <!-- Existing label -->
                        <?php if (session('errors.password')): ?>
                            <div class="invalid-feedback">
                                <?= session('errors.password') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($config->allowRemembering): ?>
                        <div class="policy-text" style="margin-top: 20px;">
                            <input type="checkbox" id="policy" name="remember" <?php if (old('remember')): ?> checked <?php endif; ?>>
                            <label for="policy"><?= lang('Auth.rememberMe') ?></label>
                        </div>
                    <?php endif; ?>

                    <button type="submit">Log in</button>
                </form>
                <hr>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="<?= base_url(); ?>/js/app.js"></script>
</body>

</html>
