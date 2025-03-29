<?= $this->extend('template/index') ?>
<?= $this->section('page-content') ?>

<style>
    #output {
        margin-top: 20px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        min-height: 100px;
        background-color: #f9f9f9;
    }
</style>

<script>
    // Function to log messages on the page
    function log(message) {
        console.log(message);
    }

    // Function to send serialNumber via AJAX
    async function sendSerialNumber(serialNumber) {
        try {
            const response = await fetch("<?= base_url('nfc-read/receive') ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ serialNumber: serialNumber }),
            });

            const result = await response.json();
            log(`Server response: ${result.message}`);
        } catch (error) {
            log(`Error sending data: ${error.message}`);
        }
    }

    // Start NFC scanning when the page loads
    window.onload = async function() {
        if (!("NDEFReader" in window)) {
            log("Web NFC is not available. Use Chrome on Android.");
            return;
        }

        log("Initializing NFC scanning...");

        try {
            const ndef = new NDEFReader();
            await ndef.scan();
            log("> Scan started");

            ndef.addEventListener("readingerror", () => {
                log("Error: Cannot read data from the NFC tag. Try another one?");
            });

            ndef.addEventListener("reading", ({ serialNumber }) => {
                log(`> Serial Number: ${serialNumber}`);
                sendSerialNumber(serialNumber);
            });
        } catch (error) {
            log(`Error: ${error.message || error}`);
        }
    };
</script>

<?= $this->endSection() ?>
