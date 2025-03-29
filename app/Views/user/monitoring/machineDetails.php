<?= $this->extend('template4/index') ?>
<?= $this->section('page-content') ?>

<!-- <p><strong>Name:</strong> <?= esc($machines[0]['Name']) ?></p>
<p><strong>Job Number:</strong> <?= esc($machines[0]['job_number']) ?></p> -->

<h1>Dashboard</h1>

<div class="date"></div>

<div class="insights">
    <div class="sales">
        <span class="material-symbols-outlined">engineering</span>
        <div class="middle">
            <div class="left">
                <h3>Project Name</h3>
                <h1><?= esc($machines[0]['job_name']) ?></h1>
            </div>
        </div>
    </div>
    <div class="sales">
        <span class="material-symbols-outlined">engineering</span>
        <div class="middle">
            <div class="left">
                <h3>Weld Metal Material</h3>
                <h1><?= esc($machines[0]['weldMetal_material']) ?></h1> <!-- Dynamic uptime -->
            </div>
        </div>
    </div>
    <div class="sales">
        <span class="material-symbols-outlined">engineering</span>
        <div class="middle">
            <div class="left">
                <h3>Machine Up Time</h3>
                <h1 class="machine-up-time"></h1>
            </div>
        </div>
    </div>
</div>

<div class="insights" style="grid-template-columns: repeat(1, 1fr)">
<div class="sales">
        <h3 style="margin-bottom: 20px; text-align: center;">Project Details</h3>
        <div class="product-details" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div class="form-group">
                <label for="project-number" style="font-weight: bold;">Project Number:</label>
                <input type="text" id="project-number" name="project-number" value="<?= esc($machines[0]['job_number']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="project-rfid" style="font-weight: bold;">Project RFID Code:</label>
                <input type="text" id="project-rfid" name="project-rfid" value="<?= esc($machines[0]['jobRFID']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="project-qr" style="font-weight: bold;">Project QR Code:</label>
                <input type="text" id="project-qr" name="project-qr" value="<?= esc($machines[0]['jobQR']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label for="project-description" style="font-weight: bold;">Description:</label>
                <textarea id="project-description" name="project-description" rows="4" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?= esc($machines[0]['job_description']) ?></textarea>
            </div>
        </div>
    </div>
</div>

<div class="insights" style="grid-template-columns: repeat(1, 1fr)">
<div class="sales">
        <h3 style="margin-bottom: 20px; text-align: center;">Weld Metal Details</h3>
        <div class="product-details" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
            <div class="form-group">
                <label for="weld-number" style="font-weight: bold;">Weld Metal Number:</label>
                <input type="text" id="weld-number" name="weld-number" value="<?= esc($machines[0]['weldMetal_batchNumber']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="weld-cert" style="font-weight: bold;">Weld Metal Cert Number:</label>
                <input type="text" id="weld-cert" name="weld-cert" value="<?= esc($machines[0]['weldMetal_certNo']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="weld-rfid" style="font-weight: bold;">Weld Metal RFID Code:</label>
                <input type="text" id="weld-rfid" name="weld-rfid" value="<?= esc($machines[0]['weldMetalRFID']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <div class="form-group">
                <label for="weld-qr" style="font-weight: bold;">Weld Metal QR Code:</label>
                <input type="text" id="weld-qr" name="weld-qr" value="<?= esc($machines[0]['weldMetalQR']) ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
        </div>
    </div>
</div>

<script>
    let machinesData = <?= json_encode($machines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PRETTY_PRINT); ?>;

    let machineID = machinesData[0].MachineID;

    console.log("Machines Data:", machinesData);
    console.log("Machine ID:", machineID);
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        function fetchArcTotal() {
            $.ajax({
                url: `<?= base_url('monitoring/getArcTotal/') ?>${machineID}`,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    console.log("Updated ArcTotal:", response.arcTotal);
                    $(".machine-up-time").text(response.arcTotal || '0:00:00');
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching ArcTotal:", error);
                }
            });
        }

        fetchArcTotal();
        setInterval(fetchArcTotal, 1000);
    });
</script>


<!-- END OF INSIGHTS -->

<?= $this->endSection() ?>